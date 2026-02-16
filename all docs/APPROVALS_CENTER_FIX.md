# Approvals Center Fix - Implementation Guide

## Overview
Fixed the Error 500 in `approvals_center.php` by creating missing database tables and correcting table name references.

## Changes Made

### 1. Fixed Table References in approvals_center.php
**File:** `/public/approvals_center.php` (Lines 75-99)

Changed incorrect table names to actual database tables:
- ❌ `fuel_readings` → ✅ `fuel_daily_readings` (Line 76)
- ❌ `deliveries` → ✅ `fuel_deliveries` (Line 88)

### 2. Created Missing Tables

#### New SQL Files Created:

**File:** `/sql/create_inventory_adjustments_table.sql`
- Table for staff-requested inventory adjustments
- Tracks adjustments for damage, lost items, found items, expiration, count variances, etc.
- Status: pending → approved/rejected

**File:** `/sql/create_stock_requests_table.sql` (Already existed)
- Table for merchandise restocking requests
- Staff can request inventory items
- Manager approval required

**File:** `/sql/create_price_changes_table.sql`
- Table for price change approval requests
- Staff proposes price changes
- Manager reviews and approves/rejects

**File:** `/sql/approvals_center_migration.sql`
- Master migration script combining all three tables
- Ready to run against the database

### 3. Database Tables Summary

| Table Name | Purpose | Status |
|---|---|---|
| `fuel_daily_readings` | Fuel reading verifications | ✅ EXISTING |
| `job_orders` | Job order approvals | ✅ EXISTING |
| `inventory_adjustments` | Stock adjustment requests | ✅ NEW (created) |
| `fuel_deliveries` | Fuel delivery verifications | ✅ EXISTING |
| `stock_requests` | Merchandise restocking requests | ✅ NEW (created) |
| `price_changes` | Price change approvals | ✅ NEW (created) |

## How to Apply the Fix

### Step 1: Run the Migration Script
Connect to your MySQL database and run:
```sql
source /path/to/sql/approvals_center_migration.sql;
```

Or run individual scripts:
```bash
mysql -u root -p petron_db < sql/create_inventory_adjustments_table.sql
mysql -u root -p petron_db < sql/create_stock_requests_table.sql
mysql -u root -p petron_db < sql/create_price_changes_table.sql
```

### Step 2: Verify Tables Created
```sql
SHOW TABLES LIKE '%adjustment%';
SHOW TABLES LIKE '%stock_request%';
SHOW TABLES LIKE '%price_change%';
```

Expected output:
```
inventory_adjustments
stock_requests
price_changes
```

### Step 3: Test the Application
1. Log in as a Manager or Admin
2. Navigate to: `/public/approvals_center.php`
3. Enter your password to verify access
4. Verify dashboard loads without errors
5. All approval counts should display (0 if no pending items)

## Approval Workflow Overview

Once deployed, managers can access these approval workflows:

### Dashboard Tab
- Overview of all pending approvals
- Quick statistics for each approval type
- Click-through to specific approval pages

### Fuel Tab
- Fuel reading verifications
- Links to: `fuel_reconciliation_validation.php`

### Job Orders Tab
- Pending job order approvals
- Links to: `joborder.php?tab=pending`

### Inventory Adjustments Tab
- Stock adjustment approval requests
- Links to: `approvals.php?view=inventory`

### Deliveries Tab
- Fuel delivery verification
- Links to: `approvals.php?view=deliveries`

### Stock Requests Tab
- Merchandise restock requests
- Links to: `manager_review_stock_requests.php`

### Prices Tab
- Price change approvals
- Links to: `manager_approve_prices.php`

## Table Schemas

### inventory_adjustments
```sql
- id (PRIMARY KEY)
- station_id (FK → stations)
- product_id (FK → products)
- requested_by (FK → users)
- adjustment_type (ENUM: damage, lost, found, expiration, count_variance, other)
- qty (DECIMAL 10,2)
- reason (TEXT)
- notes (TEXT)
- status (ENUM: pending, approved, rejected) - DEFAULT: pending
- processed_by (FK → users, nullable)
- processed_at (TIMESTAMP, nullable)
- created_at (TIMESTAMP) - DEFAULT: CURRENT_TIMESTAMP
```

### stock_requests
```sql
- id (PRIMARY KEY)
- station_id (FK → stations)
- requested_by (FK → users)
- type (ENUM: fuel, merch) - DEFAULT: merch
- product_name (VARCHAR 255)
- qty (DECIMAL 10,2)
- notes (TEXT)
- status (ENUM: pending, approved, rejected) - DEFAULT: pending
- processed_by (FK → users, nullable)
- processed_at (TIMESTAMP, nullable)
- created_at (TIMESTAMP) - DEFAULT: CURRENT_TIMESTAMP
```

### price_changes
```sql
- id (PRIMARY KEY)
- station_id (FK → stations)
- product_id (FK → products)
- proposed_by (FK → users)
- old_cost (DECIMAL 10,2, nullable)
- old_price (DECIMAL 10,2, nullable)
- new_cost (DECIMAL 10,2, nullable)
- new_price (DECIMAL 10,2, nullable)
- reason (TEXT)
- notes (TEXT)
- status (ENUM: pending, approved, rejected) - DEFAULT: pending
- reviewed_by (FK → users, nullable)
- reviewed_at (TIMESTAMP, nullable)
- created_at (TIMESTAMP) - DEFAULT: CURRENT_TIMESTAMP
```

## Files Modified

1. **`/public/approvals_center.php`** (Lines 76, 88)
   - Fixed table name references
   - No other logic changes

2. **Created SQL Files:**
   - `/sql/create_inventory_adjustments_table.sql`
   - `/sql/create_price_changes_table.sql`
   - `/sql/approvals_center_migration.sql` (Master migration)

## Verification Checklist

- [ ] Run migration script without errors
- [ ] All three new tables exist in database
- [ ] approvals_center.php loads without Error 500
- [ ] Dashboard displays correctly
- [ ] All tabs are accessible
- [ ] Pending counts display (even if 0)
- [ ] Manager can verify password successfully
- [ ] Links to sub-pages work correctly

## Rollback Instructions

If you need to rollback:
```sql
DROP TABLE IF EXISTS inventory_adjustments;
DROP TABLE IF EXISTS stock_requests;
DROP TABLE IF EXISTS price_changes;
```

Then restore from backup before applying fix.

## Notes

- Tables use InnoDB engine for foreign key support
- All timestamps use CURRENT_TIMESTAMP for consistency
- Indexes created on station_id, status, and created_at for performance
- status column uses ENUM for data integrity
- References to non-existent validation pages (like `manager_review_stock_requests.php`) will need separate implementation
