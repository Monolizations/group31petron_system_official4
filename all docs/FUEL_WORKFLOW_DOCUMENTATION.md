# Fuel Inventory Workflow Implementation - Complete Documentation

## Overview

A complete, multi-role fuel inventory management system for Petron POS with automated workflows, audit trails, and stock management. Implements a 3-step approval process for deliveries, manager-approved pump readings, adjustment workflows, and comprehensive audit logging.

## Architecture

### Core Workflow Layers

```
┌─────────────────────────────────────────────────────────────┐
│                    UI Layer (Public)                        │
│  fuel_management.php | fuel_delivery_verify.php            │
│  fuel_delivery_finalize.php | fuel_shift_processing.php    │
└─────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────┐
│                 Backend Operations Layer                     │
│  fuel_delivery_operations.php    (Recording → Verifying → Finalizing)
│  fuel_shift_operations.php       (Reading approval → Stock deduction)
│  fuel_adjustment_operations.php  (Request → Approval/Rejection)
│  fuel_audit_logging.php          (Dual logging: activity + audit)
│  fuel_stock_calculations.php     (Reconciliation & reporting)
└─────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────┐
│                   Database Layer                             │
│  fuel_deliveries      (workflow: Encoded → Verified → Finalized)
│  fuel_daily_readings  (pump readings: Pending → Approved)
│  fuel_adjustments     (adjustments: Pending → Approved/Rejected)
│  fuel_inventory       (actual stock levels - updated on workflow end)
│  fuel_inventory_logs  (immutable audit trail)
│  activity_logs        (activity tracking)
└─────────────────────────────────────────────────────────────┘
```

## Workflow Processes

### 1. Delivery Workflow (3-Step Approval)

**Status Flow:** Encoded → Verified → Finalized

#### Step 1: Record Delivery (Staff Action)
- **Page:** `fuel_management.php` (Fuel Management Tab)
- **Role:** Staff, Manager, Admin
- **Input:** Supplier, Fuel Type, Invoice No, Liters, Tanker #, Notes
- **Output:** Delivery created with status `Encoded`
- **Logging:** Activity log + Initial audit log entry

#### Step 2: Verify Delivery (Manager Action)
- **Page:** `fuel_delivery_verify.php`
- **Role:** Manager, Admin
- **Required:** Manager reviews delivery details
- **Input:** Verification notes (optional)
- **Status Change:** `Encoded` → `Verified`
- **Actions:**
  - ✓ Approve for finalization
  - ✗ Reject with reason (stops workflow)
- **Logging:** Verification logged to audit trail

#### Step 3: Finalize Delivery (Admin Action)
- **Page:** `fuel_delivery_finalize.php`
- **Role:** Admin only
- **Auto-Action:** Triggers immediate stock update
- **Stock Calculation:**
  ```
  fuel_inventory.stock_level += delivery_liters
  ```
- **Status Change:** `Verified` → `Finalized`
- **Logging:** Stock before/after recorded in audit trail
- **Transaction-Safe:** All-or-nothing atomicity

**Database Operations:**
```sql
-- fuel_deliveries
INSERT INTO fuel_deliveries (...) VALUES (..., 'Encoded')
UPDATE fuel_deliveries SET status='Verified', verified_by=?, verified_at=NOW()
UPDATE fuel_deliveries SET status='Finalized', finalized_by=?, finalized_at=NOW()
UPDATE fuel_inventory SET stock_level = stock_level + ?

-- fuel_inventory_logs
INSERT INTO fuel_inventory_logs (action='delivery_recorded', ...)
INSERT INTO fuel_inventory_logs (action='delivery_verified', ...)
INSERT INTO fuel_inventory_logs (action='delivery_finalized', quantity_before, quantity_after, ...)
```

---

### 2. Shift-End Processing Workflow

**Status Flow:** Pending → Approved (all-at-once)

#### Process Overview
- **Page:** `fuel_shift_processing.php`
- **Role:** Manager
- **Trigger:** End of each shift (Morning, Afternoon, Evening)
- **Scope:** All pending pump readings for the selected shift on current date

#### Operations
1. **Display Pending Readings**
   - Show all pumps with pending readings
   - Display previous & current readings
   - Calculate sales liters (current - previous)

2. **Batch Processing**
   - One-click "Process Shift-End" button
   - Processes ALL pending readings in transaction
   - Atomicity: All succeed or all fail

3. **Stock Deduction**
   ```
   FOR EACH reading:
     sales_liters = current_reading - previous_reading
     IF sales_liters > 0:
       fuel_inventory.stock_level -= sales_liters
       log_audit_entry(action='reading_approved', quantity_change=-sales_liters)
   ```

4. **Status Updates**
   - All readings marked as `Approved`
   - Recorded with `approved_by` and `approved_at`
   - No manual approval per reading (all-or-nothing)

5. **Summary Report**
   - Total readings processed
   - Total liters deducted per fuel type
   - Stock before/after for each pump

**Database Operations:**
```sql
-- fuel_daily_readings
UPDATE fuel_daily_readings SET status='Approved', approved_by=?, approved_at=NOW()
WHERE station_id=? AND shift=? AND DATE(reading_date)=CURDATE() AND status='Pending'

-- fuel_inventory
UPDATE fuel_inventory SET stock_level = stock_level - ?
WHERE station_id=? AND product_id=?

-- fuel_inventory_logs
INSERT INTO fuel_inventory_logs (action='reading_approved', quantity_change=-sales_liters, ...)
```

---

### 3. Adjustment Workflow

**Status Flow:** Pending → Approved/Rejected

#### Step 1: Request Adjustment (Staff Action)
- **Page:** `fuel_management.php` (Adjustments Tab)
- **Role:** Staff, Manager, Admin
- **Input:**
  - Adjustment Type (Loss, Variance, Reconciliation, etc.)
  - Liters (can be positive or negative)
  - Reason (min 10 characters)
  - Notes
- **Status:** `Pending`
- **Logging:** Initial audit entry created

#### Step 2: Manager Approval/Rejection
- **Page:** `fuel_management.php` (Pending Adjustments section)
- **Role:** Manager, Admin
- **Approval Path:**
  ```
  IF approved:
    fuel_inventory.stock_level += liters (can be +/-)
    status = 'Approved'
  ELSE:
    status = 'Rejected'
    stock_level UNCHANGED
  ```
- **Logging:**
  - Approved: Includes quantity_before, quantity_after, quantity_change
  - Rejected: No stock change, just status update

**Database Operations:**
```sql
-- fuel_adjustments
INSERT INTO fuel_adjustments (...) VALUES (..., 'Pending')
UPDATE fuel_adjustments SET status='Approved', approved_by=?, approved_at=NOW()

-- fuel_inventory (only on approval)
UPDATE fuel_inventory SET stock_level = stock_level + ?

-- fuel_inventory_logs
INSERT INTO fuel_inventory_logs (action='adjustment_requested', ...)
INSERT INTO fuel_inventory_logs (action='adjustment_approved', quantity_change=?, ...)
INSERT INTO fuel_inventory_logs (action='adjustment_rejected', ...)
```

---

## Stock Calculation & Reconciliation

### Daily Stock Calculation
```php
calculate_daily_beginning_stock($pdo, $station_id, $product_id, $date)
  Returns: [beginning_stock, previous_day_ending, deliveries_today]

calculate_daily_reconciliation($pdo, $station_id, $product_id, $date)
  Theoretical Stock = Beginning + Deliveries - Sales + Adjustments
  Actual Stock = Current fuel_inventory.stock_level
  Variance = Theoretical - Actual
  Status: OK if variance < 1L, MINOR if < 5L, MAJOR otherwise
```

### Variance Analysis
- Get variance summary across 30-day period
- Identify trends and problem areas
- Generate compliance reports

### Stock Trending
- Analyze stock levels over time
- Identify increasing/decreasing/stable trends
- Support decision-making for ordering/adjustments

---

## Database Schema

### Key Tables

#### fuel_deliveries
```sql
CREATE TABLE fuel_deliveries (
  id INT PRIMARY KEY AUTO_INCREMENT,
  station_id INT,
  supplier_id INT,
  delivery_date DATE,
  fuel_type VARCHAR(50),
  invoice_no VARCHAR(50),
  delivery_liters DECIMAL(10,2),
  tanker_number VARCHAR(50),
  received_by INT,
  
  -- Workflow fields (New)
  status ENUM('Encoded','Verified','Finalized','Rejected'),
  verified_by INT,
  verified_at DATETIME,
  finalized_by INT,
  finalized_at DATETIME,
  rejection_reason TEXT,
  
  notes TEXT,
  created_at TIMESTAMP,
  updated_at TIMESTAMP
);
```

#### fuel_daily_readings
```sql
CREATE TABLE fuel_daily_readings (
  id INT PRIMARY KEY AUTO_INCREMENT,
  station_id INT,
  fuel_station_id INT,
  reading_date DATE,
  shift ENUM('Morning','Afternoon','Evening'),
  previous_reading DECIMAL(10,2),
  current_reading DECIMAL(10,2),
  calibration DECIMAL(10,2),
  
  status ENUM('Pending','Approved'),
  approved_by INT,
  approved_at DATETIME,
  
  user_id INT,
  notes TEXT,
  created_at TIMESTAMP
);
```

#### fuel_adjustments
```sql
CREATE TABLE fuel_adjustments (
  id INT PRIMARY KEY AUTO_INCREMENT,
  station_id INT,
  product_id INT,
  adjustment_date DATE,
  adjustment_type VARCHAR(50),
  liters DECIMAL(10,2),
  reason TEXT,
  
  status ENUM('Pending','Approved','Rejected'),
  approved_by INT,
  approved_at DATETIME,
  approval_reason TEXT,
  
  user_id INT,
  notes TEXT,
  created_at TIMESTAMP
);
```

#### fuel_inventory (Core)
```sql
CREATE TABLE fuel_inventory (
  id INT PRIMARY KEY AUTO_INCREMENT,
  station_id INT,
  product_id INT,
  stock_level DECIMAL(10,2),
  last_updated TIMESTAMP,
  UNIQUE KEY (station_id, product_id)
);
```

#### fuel_inventory_logs (Immutable Audit)
```sql
CREATE TABLE fuel_inventory_logs (
  id INT PRIMARY KEY AUTO_INCREMENT,
  station_id INT,
  product_id INT,
  user_id INT,
  
  action ENUM(
    'delivery_recorded', 'delivery_verified', 'delivery_finalized',
    'reading_recorded', 'reading_approved',
    'adjustment_requested', 'adjustment_approved', 'adjustment_rejected',
    'stock_deducted', 'stock_added'
  ),
  
  reference_type ENUM('fuel_delivery', 'fuel_daily_reading', 'fuel_adjustment'),
  reference_id INT,
  status ENUM('pending','approved','finalized','rejected','cancelled'),
  
  quantity_before DECIMAL(10,2),
  quantity_after DECIMAL(10,2),
  quantity_change DECIMAL(10,2),
  
  notes TEXT,
  approval_reason TEXT,
  
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  
  UNIQUE KEY (reference_type, reference_id, action)
);
```

---

## Backend Classes

### FuelDeliveryOperations
```php
class FuelDeliveryOperations {
  public function record_delivery($station_id, $supplier_id, $delivery_date, 
                                   $fuel_type, $invoice_no, $delivery_liters, 
                                   $tanker_number, $notes)
  
  public function verify_delivery($delivery_id, $manager_id, $verification_notes)
  
  public function finalize_delivery($delivery_id, $admin_id, $finalization_remarks)
  
  public function reject_delivery($delivery_id, $user_id, $rejection_reason)
  
  public function get_deliveries_by_status($station_id, $status)
}
```

### FuelShiftOperations
```php
class FuelShiftOperations {
  public function process_shift_end($station_id, $shift, $manager_id)
    // Returns: [success, message, readings_processed, total_liters_deducted, summary]
  
  public function get_pending_readings($station_id, $shift)
  
  public function get_shift_summary($station_id, $shift, $date)
}
```

### FuelAdjustmentOperations
```php
class FuelAdjustmentOperations {
  public function request_adjustment($station_id, $product_id, $adjustment_date, 
                                     $adjustment_type, $liters, $reason, $notes)
  
  public function approve_adjustment($adjustment_id, $manager_id, $approval_reason)
  
  public function reject_adjustment($adjustment_id, $manager_id, $rejection_reason)
  
  public function get_pending_adjustments($station_id)
  
  public function get_adjustment_history($station_id, $limit)
}
```

### Fuel Audit Logging
```php
function log_fuel_inventory_action($pdo, $user_id, $action_type, $reference_type, 
                                   $reference_id, $station_id, $product_id, $details)

function get_fuel_audit_trail($pdo, $reference_type, $reference_id)

function get_fuel_stock_modifications($pdo, $station_id, $start_date, $end_date)

function get_user_fuel_actions($pdo, $user_id, $action_type, $limit)

function generate_fuel_audit_report($pdo, $station_id, $date)

function verify_fuel_audit_integrity($pdo, $station_id)
```

### Stock Calculation Functions
```php
function calculate_daily_beginning_stock($pdo, $station_id, $product_id, $date)

function calculate_daily_reconciliation($pdo, $station_id, $product_id, $date)

function get_reconciliation_history($pdo, $station_id, $product_id, $start_date, $end_date)

function get_variance_summary($pdo, $station_id, $product_id, $days_back)

function calculate_stock_trend($pdo, $station_id, $product_id, $period)

function archive_daily_reconciliation($pdo, $station_id, $date)
```

---

## File Structure

```
├── backend/
│   ├── fuel_delivery_operations.php        [DELIVERY WORKFLOW]
│   ├── fuel_shift_operations.php           [SHIFT PROCESSING]
│   ├── fuel_adjustment_operations.php      [ADJUSTMENT WORKFLOW]
│   ├── fuel_audit_logging.php              [AUDIT TRAIL]
│   └── fuel_stock_calculations.php         [RECONCILIATION]
│
├── public/
│   ├── fuel_management.php                 [MAIN DASHBOARD]
│   ├── fuel_delivery_verify.php            [MANAGER VERIFICATION]
│   ├── fuel_delivery_finalize.php          [ADMIN FINALIZATION]
│   ├── fuel_shift_processing.php           [SHIFT-END PROCESSING]
│   └── fuel_monitoring.php                 [EXISTING MONITORING]
│
├── tests/
│   └── fuel_workflow_tests.php             [TEST SUITE]
│
└── sql/
    ├── phase1_add_supplier_id_to_fuel_deliveries.sql
    ├── phase1_create_fuel_inventory_logs.sql
    ├── phase1_enhance_fuel_deliveries.sql
    ├── phase1_enhance_fuel_adjustments.sql
    └── phase1_complete.sql                 [MASTER MIGRATION]
```

---

## Role-Based Access Control

### Staff
- ✓ Record fuel deliveries
- ✓ Record pump readings
- ✓ Request adjustments
- ✗ Cannot verify or approve

### Manager
- ✓ All staff permissions
- ✓ Verify deliveries (Encoded → Verified)
- ✓ Approve pump readings (shift-end processing)
- ✓ Approve/reject adjustments
- ✗ Cannot finalize deliveries

### Admin
- ✓ All manager permissions
- ✓ Finalize deliveries (Verified → Finalized + stock update)
- ✓ Override any workflow step
- ✓ View complete audit trails

### Superadmin
- ✓ All admin permissions across all stations
- ✓ Access multi-station management
- ✓ System-wide reporting and analytics

---

## Running Tests

```bash
# From project root
php tests/fuel_workflow_tests.php

# Output: Color-coded pass/fail report
# Exit code: 0 if all tests pass, 1 if any fail

# Verbose output controlled by VERBOSE constant in test file
```

---

## Security Features

1. **Audit Trail Immutability**
   - fuel_inventory_logs table cannot be modified
   - All actions recorded with timestamp and user_id
   - Unique constraint prevents duplicate entries

2. **Transaction Safety**
   - Multi-step operations use database transactions
   - All-or-nothing semantics prevent partial updates
   - Automatic rollback on error

3. **Role-Based Access Control**
   - Every endpoint checks user role
   - Operations rejected if user lacks permission
   - All changes logged to activity_logs

4. **Input Validation**
   - All user inputs validated before processing
   - SQL injection prevention via prepared statements
   - Type checking on numeric fields

5. **Data Consistency**
   - Foreign key constraints prevent orphaned records
   - Stock calculations verified against source transactions
   - Audit integrity checks available

---

## Compliance & Reporting

### Audit Reports
- Complete transaction history per delivery/adjustment
- User action tracking
- Daily reconciliation reports
- Variance analysis reports

### Stock Reconciliation
- Daily theoretical vs actual comparison
- Variance investigation workflow
- Trend analysis
- Historical archiving

### User Activity
- Track all fuel-related actions per user
- Generate compliance reports
- Identify bottlenecks in approval process

---

## Implementation Status

✅ **Complete (100%)**
- Phase 1: Database Schema (5/5 files)
- Phase 2: Delivery Operations Backend
- Phase 3: Shift Processing Backend
- Phase 4: Adjustment Operations Backend
- Phase 5: Audit Logging Integration
- Phase 6: Stock Calculations
- Phase 7: UI Pages (4/4 pages)
- Phase 8: Test Suite (Full coverage)

---

## Future Enhancements

1. **Automated Reconciliation**
   - Auto-archive daily reconciliations
   - Automatic variance alerts (> 5%)
   - Suggested adjustments based on variance

2. **Reporting Dashboard**
   - Real-time inventory status
   - Trend charts and graphs
   - Performance KPIs

3. **Integration**
   - Supplier integration for automatic delivery verification
   - Pump system integration for real-time readings
   - Email notifications for approvals needed

4. **Analytics**
   - Predictive stock level forecasting
   - Optimal reorder point calculation
   - Fuel type usage analytics

---

## Contact & Support

For questions or issues with the fuel workflow implementation, contact the development team.

Last Updated: February 2026
Version: 1.0.0 (Complete Implementation)
