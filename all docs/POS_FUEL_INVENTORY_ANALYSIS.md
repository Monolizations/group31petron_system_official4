# POS System Architecture - Fuel Inventory & Transaction Analysis

## Executive Summary

The POS system currently operates as a **merchandise and fuel sales recording system** with **deferred inventory management**. Fuel sales are recorded at the point of sale but **NOT directly integrated with fuel pump readings or calibration data**. The system tracks inventory separately through pump readings and reconciliation, creating a gap between POS sales and physical fuel tracking.

---

## 1. POS INTERFACE ARCHITECTURE

### File Structure
- **Primary POS File**: `/public/pos.php` (923 lines)
- **Multi-Product POS**: `/public/pos_multi.php` (636 lines) 
- **Database Connection**: `/public/db_connect.php`
- **Database**: `petron_pos_db_secure` (MySQL)

### How Fuel Sales Are Recorded

**Location**: `pos.php`, lines 182-275

```
1. Staff selects product type (Fuel or Merchandise)
2. Staff selects product from inventory dropdown
3. Staff enters quantity (for fuel = liters)
4. Staff enters price (per liter for fuel)
5. Staff selects payment type (Cash or GCash)
6. Transaction saved to `sales` table
7. Inventory updated in `station_inventory` table
```

**Key Flow (pos.php:230-260)**:
```php
// Line 235: Status depends on user role
$initial_status = $isAdmin ? 'Completed' : 'Pending';

// Line 236-241: Insert transaction
INSERT INTO sales (id, station_id, user_id, sale_date, sale_time, 
                  payment_method, total, status, created_at)
VALUES (?, ?, ?, CURDATE(), CURTIME(), ?, ?, ?, NOW())

// Line 251-252: Insert sale items (with product_id, quantity, price)
INSERT INTO sale_items (sale_id, product_id, name, quantity, 
                        unit_price, total_amount)
VALUES (?, ?, ?, ?, ?, ?)

// Line 255-256: IMMEDIATE stock deduction
UPDATE station_inventory 
SET stock_level = stock_level - ? 
WHERE product_id = ? AND station_id = ?
```

---

## 2. DATA FIELDS CAPTURING FUEL TRANSACTIONS

### Sales Table Schema
```sql
CREATE TABLE `sales` (
  `id` varchar(64) PRIMARY KEY,           -- SALE-{uniqid}
  `station_id` int(11),                   -- Which station
  `user_id` int(11),                      -- Staff member
  `customer_id` int(11),                  -- Optional customer
  `payment_method` varchar(32),           -- Cash/GCash
  `total` decimal(12,2),                  -- Total amount (₱)
  `sale_date` date,                       -- Date of sale
  `sale_time` time,                       -- Time of sale
  `status` varchar(50),                   -- Pending/Completed/Rejected
  `created_at` datetime
)
```

### Sale Items Table Schema
```sql
CREATE TABLE `sale_items` (
  `id` int(11) AUTO_INCREMENT,
  `sale_id` varchar(64),                  -- FK to sales
  `product_id` int(11),                   -- Which product (fuel type)
  `name` varchar(255),                    -- Product name (Gasoline/Diesel/etc)
  `quantity` decimal(12,2),               -- Liters (for fuel) or units (merch)
  `unit_price` decimal(12,2),             -- Price per liter/unit
  `total_amount` decimal(12,2),           -- quantity × unit_price
  FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE
)
```

### Captured Fuel Transaction Data
- **Fuel Type**: Stored as product name (e.g., "Gasoline", "Diesel")
- **Quantity**: In liters, captured as `quantity` in sale_items
- **Price**: Per liter, stored as `unit_price` in sale_items
- **Total Amount**: quantity × unit_price
- **Payment Method**: Cash or GCash (+ reference number if GCash)
- **Customer**: Optional, stored as customer_name or customer_id

---

## 3. FUEL INVENTORY MANAGEMENT

### Current Inventory Structure

**Table: `station_inventory`**
```sql
CREATE TABLE `station_inventory` (
  `id` int(11),
  `station_id` int(11),
  `product_id` int(11),                   -- Links to products table
  `stock_level` decimal(12,2),            -- Current stock (liters for fuel)
  `unit` varchar(50),                     -- "liters" for fuel, "pieces" for merch
  `reorder_level` int(11),                -- Minimum threshold
  `capacity` decimal(12,2),               -- Tank capacity
  `status` enum('active','inactive'),
  `last_updated` datetime
)
```

### How Inventory Is Updated When Transaction is Made

**Timing**: IMMEDIATE (within transaction) - Line 255-256 in pos.php

```php
// Step 1: Validate stock BEFORE insertion
$stmt = $pdo->prepare("SELECT stock_level FROM station_inventory 
                       WHERE product_id = ? AND station_id = ?");
$stmt->execute([$product_id, $station_id]);
$stock = $stmt->fetchColumn();

if ($stock < $quantity) {
    // Error: Insufficient stock
    return error "Insufficient stock";
}

// Step 2: Create transaction (pending approval)
// Step 3: Deduct stock IMMEDIATELY
$stmtStock = $pdo->prepare("UPDATE station_inventory 
                            SET stock_level = stock_level - ? 
                            WHERE product_id = ? AND station_id = ?");
$stmtStock->execute([$quantity, $product_id, $station_id]);

// Step 4: Commit transaction
$pdo->commit();
```

**Key Point**: Stock is deducted **immediately** when transaction is saved, regardless of whether transaction is Pending approval or already Completed.

### Current Inventory Tracking Mechanism

**Multi-Source Inventory Updates**:
1. **POS Sales**: Direct deduction from station_inventory
2. **Fuel Deliveries**: Added via fuel_staff.php (manual entry)
3. **Fuel Readings**: Deducted via fuel_shift_operations.php (shift-end processing)
4. **Adjustments**: Added/Deducted via manual adjustment records

---

## 4. FUEL INVENTORY CONNECTION TO DAILY READINGS

### Does POS Read From `fuel_daily_readings`?

**NO** - There is currently **NO direct connection** between POS sales and `fuel_daily_readings`.

**Evidence**:
- POS.php has NO reference to `fuel_daily_readings` table
- POS.php has NO reference to `fuel_pumps` table
- POS.php has NO reference to `nozzles` table

**Separate Systems**:
```
POS System                          Fuel Tracking System
├─ pos.php                         ├─ fuel_staff.php
├─ sales table                     ├─ fuel_daily_readings table
├─ sale_items table                ├─ fuel_pumps table
├─ station_inventory               ├─ nozzles table
└─ Direct stock deduction          └─ fuel_reconciliation table
```

### How Fuel Stock/Closing Stock Is Calculated

**Two Separate Calculations**:

#### 1. POS Inventory (station_inventory table)
```sql
Closing Stock = Opening Stock - POS Sales Deductions + Deliveries
```

#### 2. Fuel Daily Readings (fuel_daily_readings table)
```sql
sales_liters = current_reading - previous_reading - calibration
```

**No Cross-Validation**: The system does NOT compare POS sales against pump readings.

### Running Balance Logic

**Station Inventory**: Real-time balance after each POS transaction
```sql
SELECT stock_level FROM station_inventory WHERE product_id = ? AND station_id = ?
```

**Fuel Daily Readings**: End-of-shift reconciliation via `fuel_shift_operations.php`
```php
// fuel_shift_operations.php, lines 84-145
$sales_liters = $reading['current_reading'] - $reading['previous_reading'];
// Deduct from fuel_inventory (separate from station_inventory)
UPDATE fuel_inventory SET stock_level = stock_level - ? 
WHERE station_id = ? AND product_id = ?
```

### Real-Time vs End-of-Day Reconciliation

**POS Inventory**:
- ✅ Real-time updates with each sale
- ✅ Prevents overselling (stock check before sale)
- ❌ Does NOT account for pump calibration
- ❌ Does NOT validate against physical pump readings

**Fuel Readings**:
- ❌ Manual entry (staff records pump readings)
- ✅ Includes calibration adjustments
- ✅ Reconciliation at shift-end
- ❌ Deferred until manager approval

---

## 5. TRANSACTION DETAILS

### Where POS Transactions Are Stored

**Primary Tables**:
1. **`sales`** - Transaction header
2. **`sale_items`** - Line items (products in transaction)

**Sample Transaction Flow** (Line 178-256 in pos.php):
```
sales ID:          SALE-5xxx789
sale_date:         2026-02-16
sale_time:         14:35:22
user_id:           15 (staff)
station_id:        226
payment_method:    Cash
total:             650.00
status:            Pending (if staff) / Completed (if admin)

sale_items [1]:
  product_id:      2
  name:            Gasoline
  quantity:        10.50 (liters)
  unit_price:      62.00 (per liter)
  total_amount:    651.00

station_inventory [After Sale]:
  stock_level:     1000 → 989.50 (deducted 10.50L)
```

### Fuel Type Tracking

**Recording Fuel Type**:
- ✅ Captured via `products` table (product name = "Gasoline", "Diesel", etc.)
- ✅ Stored in `sale_items.name` field
- ✅ Product ID links to fuel type via products table

**Connection Path**:
```
sale_items
  └─ product_id → products.id
       └─ type_id → product_types.id
            └─ name (e.g., "fuel")
```

### Quantity Tracking Level

**Granularity**:
- **Pump Level**: NO - POS does NOT record which pump was used
- **Nozzle Level**: NO - POS does NOT record which nozzle was used
- **Product/Fuel Type Level**: YES - Records fuel type (Gasoline/Diesel/LPG/Premium/Unleaded)
- **Quantity**: YES - Liters per transaction

**Missing Link**: POS cannot currently match sales to specific pump/nozzle readings.

### Price Calculations

**Sources**:
1. **Fuel Pricing Table**: `fuel_pricing` (per fuel type, per station)
2. **Fallback**: `products.price` column

**Loading Logic** (pos.php, lines 300-318):
```php
// Primary: Load from fuel_pricing table
SELECT p.id, p.name, p.type_id, p.price,
       fp.price_per_liter as price
FROM products p
INNER JOIN product_types pt ON p.type_id = pt.id
LEFT JOIN fuel_pricing fp ON fp.fuel_type_id = p.type_id 
                          AND fp.station_id = ? 
                          AND fp.is_active = 1

// Fallback if fuel_pricing missing
foreach ($fuelProducts as &$fp) {
    if (!$fp['price'] && $fp['price'] > 0) {
        $fp['price'] = $fp['price'] ?? $fp['price'];  // Use products.price
    }
}
```

**Price Type**: Per-Liter (not pump-specific, not nozzle-specific)

---

## 6. CALIBRATION INTEGRATION

### Current Calibration Status

**Calibration Stored At Two Levels**:

#### 1. Pump-Level Calibration
```sql
Table: fuel_pumps
Column: calibration_value DECIMAL(10,6)
```

#### 2. Nozzle-Level Calibration
```sql
Table: nozzles
Column: calibration_value DECIMAL(10,6)
```

**However**: ❌ **POS does NOT apply calibration to sales**

### Does POS Apply Pump Calibration?

**NO** - Current POS system:
- ✅ Records fuel sales quantity as-is
- ❌ Does NOT read calibration_value from fuel_pumps
- ❌ Does NOT read calibration_value from nozzles
- ❌ Does NOT adjust quantities based on pump calibration

**Evidence**: Search of pos.php finds ZERO references to:
- `calibration_value`
- `calibration`
- `fuel_pumps`
- `nozzles`

### Where Calibration IS Applied

**Location**: `fuel_shift_operations.php`, lines 84-145

```php
// Calibration applied during shift-end reconciliation
$sales_liters = $current_reading - $previous_reading - $calibration;
```

**Process**:
1. Staff records pump readings with calibration value
2. Manager approves readings
3. Admin finalizes and deducts from fuel_inventory
4. Calibration subtracted from pump differential

**Gap**: This is SEPARATE from POS sales tracking.

---

## 7. INTEGRATION POINTS

### What Happens When POS Records a Fuel Sale

**Step-by-Step Process** (pos.php, 182-260):

```
1. VALIDATION PHASE
   ✓ Get product details from products table
   ✓ Check stock availability in station_inventory
   ✓ Validate customer, quantity, payment method

2. TRANSACTION PHASE (begins)
   ✓ Create sale record in sales table
   ✓ Create sale_item record in sale_items table
   
3. INVENTORY PHASE (immediate)
   ✓ UPDATE station_inventory SET stock_level = stock_level - quantity
   
4. LOGGING PHASE
   ✓ Log to activity_logs table
   
5. COMPLETION PHASE
   ✓ Commit transaction
   ✓ Return success message
```

**No Direct Side Effects**:
- ❌ Does NOT update fuel_daily_readings
- ❌ Does NOT update fuel_pumps
- ❌ Does NOT update nozzles
- ❌ Does NOT trigger reconciliation
- ❌ Does NOT validate against pump readings

### Should POS Update `fuel_daily_readings`?

**Current Design**: NO
**Architectural Reason**: Separation of concerns
- POS = Sales recording system
- Fuel readings = Physical measurement system

**However**: Future enhancement could add:
```php
// When POS fuel sale recorded, also log:
INSERT INTO fuel_transaction_log (
  sale_id,           -- FK to sales.id
  pump_id,           -- Which pump was used
  nozzle_id,         -- Which nozzle was used
  quantity_recorded, -- From POS sale
  quantity_pumped,   -- From pump reading (daily_readings)
  variance,          -- Difference (quality control)
  fuel_type_id
)
```

### Should It Calculate Running Balance?

**Current Implementation**: ✅ YES
- station_inventory.stock_level is updated immediately

**Future Enhancement**: Could add
```sql
-- Running balance with timestamp
INSERT INTO fuel_inventory_transactions (
  station_id,
  product_id,
  transaction_type,   -- 'sale'
  quantity,           -- -10.5 (negative for deduction)
  running_balance,    -- After this transaction
  reference_type,     -- 'pos_sale'
  reference_id        -- sale_id
)
```

### Error Handling - Insufficient Inventory

**Current Mechanism** (pos.php, lines 214-221):

```php
// Check stock BEFORE transaction
$stmt = $pdo->prepare("SELECT stock_level FROM station_inventory 
                       WHERE product_id = ? AND station_id = ?");
$stmt->execute([$product_id, $station_id]);
$stock = $stmt->fetchColumn();

if ($stock < $quantity) {
    $msg = "❌ Error: Insufficient stock. Available: {$stock}. Requested: {$quantity}.";
    // Transaction NOT created
    // Stock NOT deducted
}
```

**If Insufficient Stock**:
1. ✅ Transaction rejected before creation
2. ✅ No partial sales allowed
3. ✅ Stock remains unchanged
4. ✅ Error message shown to user

---

## 8. PUMP & NOZZLE CONFIGURATION

### How POS Knows Which Pumps/Nozzles Exist

**Current Status**: ❌ **POS does NOT know about pumps or nozzles**

**Why**: POS operates at fuel type level (Gasoline/Diesel), not pump level.

**Pump Management** (separate system in admin_pump_management.php):
```php
Table: fuel_pumps
  id, station_id, pump_number, fuel_type_id, calibration_value, status

Table: nozzles
  id, pump_id, nozzle_number, fuel_type_id, calibration_value, status
```

**POS Level**: Product-based
```php
Table: products
  id, name ('Gasoline', 'Diesel', etc), type_id, price

Table: station_inventory
  id, station_id, product_id, stock_level, unit ('liters')
```

### Pricing Rules Storage

**Location 1**: `fuel_pricing` table (Recommended)
```sql
CREATE TABLE `fuel_pricing` (
  `id` int(11) AUTO_INCREMENT,
  `station_id` int(11),
  `fuel_type_id` int(11),                 -- Links to fuel_types
  `price_per_liter` decimal(10,2),
  `effective_date` datetime,
  `is_active` tinyint(1),
  PRIMARY KEY (`id`),
  KEY `idx_station_fuel` (`station_id`, `fuel_type_id`, `is_active`)
)
```

**Location 2**: `products` table (Fallback)
```sql
Table: products
  price decimal(10,2)
```

### Pricing Granularity

**Current Implementation**: ✅ **Per Fuel Type, Per Station**
- ❌ NOT per pump
- ❌ NOT per nozzle
- ✅ Per station

**Query Logic** (pos.php, lines 300-310):
```php
// Load fuel pricing
SELECT fp.*, ft.name as fuel_name
FROM fuel_pricing fp
INNER JOIN fuel_types ft ON fp.fuel_type_id = ft.id
WHERE fp.station_id = ? AND fp.is_active = 1
```

**Result**: All Gasoline sold at Station 226 costs ₱62.00/liter (example).

---

## 9. CURRENT ARCHITECTURE SUMMARY

### Tables Involved in POS Fuel Sales

```
┌─────────────────────────────────────────────────────────────┐
│                    POS FUEL SALES                           │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  sales (Transaction Header)                                │
│  ├─ id (PRIMARY KEY)                                       │
│  ├─ station_id (FK to stations)                            │
│  ├─ user_id (FK to users - staff)                          │
│  ├─ payment_method (Cash/GCash)                            │
│  ├─ total (₱ amount)                                       │
│  ├─ status (Pending/Completed)                             │
│  └─ created_at                                             │
│                                                             │
│  sale_items (Line Items)                                   │
│  ├─ sale_id (FK to sales)                                  │
│  ├─ product_id (FK to products)                            │
│  ├─ name (Fuel type: Gasoline/Diesel/etc)                  │
│  ├─ quantity (Liters)                                      │
│  ├─ unit_price (₱/liter)                                   │
│  └─ total_amount (quantity × unit_price)                   │
│                                                             │
│  products (Fuel Type Master)                               │
│  ├─ id                                                     │
│  ├─ name (Gasoline/Diesel/LPG/Premium/Unleaded)            │
│  ├─ type_id (FK to product_types - 'fuel')                 │
│  └─ price (Fallback price)                                 │
│                                                             │
│  station_inventory (Stock Management)                      │
│  ├─ station_id                                             │
│  ├─ product_id (FK to products)                            │
│  ├─ stock_level (Liters - UPDATED on sale)                 │
│  ├─ unit ('liters')                                        │
│  ├─ capacity (Tank capacity)                               │
│  └─ last_updated (timestamp)                               │
│                                                             │
│  fuel_pricing (Current Prices)                             │
│  ├─ station_id                                             │
│  ├─ fuel_type_id (FK to fuel_types)                        │
│  ├─ price_per_liter                                        │
│  ├─ is_active (Boolean)                                    │
│  └─ effective_date                                         │
│                                                             │
│  activity_logs (Audit Trail)                               │
│  ├─ user_id                                                │
│  ├─ action                                                 │
│  ├─ details                                                │
│  └─ created_at                                             │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

### Tables NOT Connected to POS (Separate System)

```
┌─────────────────────────────────────────────────────────────┐
│              FUEL PUMP READINGS (Separate)                 │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  fuel_pumps (Pump Master)                                  │
│  ├─ id                                                     │
│  ├─ station_id                                             │
│  ├─ pump_number                                            │
│  ├─ fuel_type_id (FK to fuel_types)                        │
│  └─ calibration_value (✗ NOT used by POS)                  │
│                                                             │
│  nozzles (Nozzle Master)                                   │
│  ├─ id                                                     │
│  ├─ pump_id (FK to fuel_pumps)                             │
│  ├─ nozzle_number                                          │
│  ├─ fuel_type_id (FK to fuel_types)                        │
│  ├─ calibration_value (✗ NOT used by POS)                  │
│  └─ status                                                 │
│                                                             │
│  fuel_daily_readings (Shift Readings)                      │
│  ├─ pump_id (FK to fuel_pumps)                             │
│  ├─ reading_date                                           │
│  ├─ shift (Morning/Afternoon/Evening)                      │
│  ├─ previous_reading                                       │
│  ├─ current_reading                                        │
│  ├─ calibration (✗ NOT validated against POS)              │
│  ├─ sales_liters (Calculated)                              │
│  └─ status (Pending/Verified/Finalized)                    │
│                                                             │
│  fuel_reconciliation (End-of-Day)                          │
│  ├─ pump_id                                                │
│  ├─ reconciliation_date                                    │
│  ├─ sales_liters (From pump readings)                      │
│  ├─ physical_stock (Manual count)                          │
│  ├─ variance_liters (Discrepancy)                          │
│  └─ status (Pending/Verified/Finalized)                    │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

## 10. DATA FLOW DIAGRAM

```
┌─────────────────────────────────────┐
│      POS SALES TRANSACTION          │
│  (Staff enters fuel sale)            │
└────────────┬────────────────────────┘
             │
             ▼
    ┌────────────────────┐
    │  Validate Stock    │
    │ (station_inventory)│
    └────────┬───────────┘
             │
        ┌────▼─────┐
        │ Sufficient│
        └────┬─────┘
             │
             ▼
    ┌────────────────────┐
    │  Create Sales Txn  │
    │  (sales table)     │
    └────────┬───────────┘
             │
             ▼
    ┌────────────────────┐
    │  Create Sale Items │
    │  (sale_items table)│
    └────────┬───────────┘
             │
             ▼
    ┌────────────────────┐
    │  Update Inventory  │
    │  -stock_level      │
    └────────┬───────────┘
             │
             ▼
    ┌────────────────────┐
    │  Log Activity      │
    │  (activity_logs)   │
    └────────┬───────────┘
             │
             ▼
    ┌────────────────────┐
    │  Commit & Notify   │
    │  User             │
    └────────────────────┘

             ×××××××××××××××××××××××××××
      
      SEPARATE SYSTEM (NOT INTEGRATED)
      
             ×××××××××××××××××××××××××××

┌─────────────────────────────────────┐
│   FUEL PUMP READINGS (Manual)        │
│  (Staff reads pump displays)         │
└────────────┬────────────────────────┘
             │
             ▼
    ┌────────────────────┐
    │  Record Reading    │
    │  (fuel_daily_readings)
    │  + calibration    │
    └────────┬───────────┘
             │
             ▼
    ┌────────────────────┐
    │ Manager Approves   │
    │  (status change)   │
    └────────┬───────────┘
             │
             ▼
    ┌────────────────────┐
    │  Shift End         │
    │  Reconciliation    │
    │  (fuel_shift_operations)
    └────────┬───────────┘
             │
             ▼
    ┌────────────────────┐
    │ Deduct from        │
    │ fuel_inventory     │
    │ (NOT station_inv)  │
    └────────────────────┘

    ⚠️  NO CONNECTION BETWEEN SYSTEMS
    ⚠️  POS doesn't read pump_id/nozzle_id
    ⚠️  Reconciliation doesn't compare to POS sales
```

---

## 11. KEY INVENTORY HANDLING LINE NUMBERS

### POS.php

| Line(s) | Function | Code |
|---------|----------|------|
| 202-206 | Load product details | `SELECT p.name FROM products WHERE p.id = ?` |
| 214-216 | Check stock availability | `SELECT stock_level FROM station_inventory` |
| 220-221 | Insufficient stock error | Validation before transaction |
| 232-258 | Transaction creation | `INSERT INTO sales` and `sale_items` |
| 254-256 | **Immediate stock deduction** | `UPDATE station_inventory SET stock_level = stock_level - ?` |
| 300-310 | Load fuel pricing | `SELECT fp.price_per_liter FROM fuel_pricing` |
| 325-334 | Load fuel pricing for display | `SELECT fp.* FROM fuel_pricing` |

### POS_Multi.php

| Line(s) | Function | Code |
|---------|----------|------|
| 142-144 | Check stock per item | `SELECT stock_level FROM station_inventory` |
| 178-179 | Insert sale transaction | `INSERT INTO sales (...)` |
| 190-192 | Insert sale items | `INSERT INTO sale_items (...)` |
| 195-196 | **Deduct inventory per item** | `UPDATE station_inventory SET stock_level = stock_level - ?` |

### Fuel_Shift_Operations.php

| Line(s) | Function | Code |
|---------|----------|------|
| 44-64 | Fetch pending readings | `SELECT FROM fuel_daily_readings` |
| 84-85 | Calculate sales liters | `sales_liters = current_reading - previous_reading - calibration` |
| 94-110 | Update fuel_inventory | `UPDATE fuel_inventory (separate from station_inventory)` |
| 125-140 | Log to inventory audit trail | Immutable audit logs |

---

## 12. CRITICAL GAPS

### What's Missing

1. **No Pump/Nozzle Tracking in POS**
   - POS cannot tell which pump or nozzle was used
   - Cannot match POS sales to specific pump readings
   
2. **No Calibration Applied to POS**
   - POS records sales as-is
   - Pump calibration applied only during shift reconciliation
   - Creates potential for variance (not captured at POS)

3. **No Real-Time Validation Against Pump Readings**
   - POS doesn't check pump readings
   - Cannot prevent overselling based on physical pump limits
   
4. **Dual Inventory Systems**
   - `station_inventory` (POS-driven)
   - `fuel_inventory` (Pump reading-driven)
   - Not reconciled automatically
   - Creates confusion about "true" stock level

5. **No Transaction Linking**
   - sales/sale_items records have NO reference to pumps/nozzles
   - No way to match a POS sale back to physical pump activity

---

## 13. CONFIGURATION & SETUP

### Database Configuration
- **File**: `/public/db_connect.php`
- **Database**: `petron_pos_db_secure`
- **User**: `root`
- **Password**: Empty (XAMPP default)

### Fuel Pricing Setup
- **Script**: `/setup_fuel_pricing.php` or `/setup_fuel_pricing_web.php`
- **Creates**: `fuel_pricing` table
- **Populates**: Per-station fuel type prices

### Pump Configuration
- **Admin Interface**: `/public/admin_pump_management.php`
- **Creates**: Pumps and nozzles per station
- **Sets**: Calibration values manually
- **NOT integrated to POS**

---

## CONCLUSION

The POS system is currently a **standalone sales recording and inventory management system** that:

✅ **Does Well**:
- Records fuel sales with product type, quantity, and price
- Immediately deducts from station inventory
- Prevents overselling
- Maintains activity audit trail
- Separates concerns (sales vs. pump readings)

❌ **Does NOT Do**:
- Integrate pump readings with POS sales
- Apply calibration adjustments to POS quantities
- Track which pump/nozzle was used
- Validate POS sales against physical pump readings
- Automatically reconcile POS vs. pump inventory

**For Integration**, consider:
1. Adding `pump_id` and `nozzle_id` fields to sales/sale_items
2. Loading pump/nozzle info via POS interface
3. Applying calibration at point-of-sale
4. Creating reconciliation reports comparing POS vs. pump readings
5. Merging station_inventory and fuel_inventory into single source of truth

