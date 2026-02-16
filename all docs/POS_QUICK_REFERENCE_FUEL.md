# POS Fuel Inventory - Quick Reference

## Critical Facts

### What POS Currently Does
- Records fuel sales to `sales` and `sale_items` tables
- Deducts inventory IMMEDIATELY from `station_inventory`
- Uses fuel pricing from `fuel_pricing` table
- Tracks fuel by type only (Gasoline/Diesel/LPG/Premium/Unleaded)
- Does NOT track pump or nozzle numbers

### What POS Does NOT Do
- Apply pump calibration to sales
- Read from `fuel_daily_readings`
- Track which pump/nozzle was used
- Validate against physical pump readings
- Automatically reconcile vs. shift readings

## Key Files

| File | Lines | Purpose |
|------|-------|---------|
| `/public/pos.php` | 923 | Single-item sales interface |
| `/public/pos_multi.php` | 636 | Multi-item sales interface |
| `/public/fuel_staff.php` | 2455 | Pump readings & fuel management |
| `/backend/fuel_shift_operations.php` | - | Shift-end reconciliation |
| `/public/admin_pump_management.php` | - | Pump & nozzle configuration |

## Database Tables (POS-Related)

```
sales               - Transaction header (SALE-xxxxx)
├─ id, station_id, user_id, payment_method, total, status
└─ created_at

sale_items          - Line items
├─ sale_id (FK), product_id, name, quantity, unit_price, total_amount

products            - Fuel type master (Gasoline/Diesel/etc)
├─ id, name, type_id, price

station_inventory   - Current stock levels
├─ station_id, product_id, stock_level, unit ('liters'), capacity

fuel_pricing        - Current prices per station
├─ station_id, fuel_type_id, price_per_liter, is_active

activity_logs       - Audit trail
├─ user_id, action, details, created_at
```

## Critical Line Numbers

### POS.php
- **214-221**: Stock validation before sale
- **255-256**: **IMMEDIATE stock deduction** ← KEY
- **300-310**: Load fuel pricing

### POS_Multi.php
- **142-144**: Stock check per item
- **195-196**: **Stock deduction per item** ← KEY

### Fuel_Shift_Operations.php
- **84-145**: Deduct from SEPARATE fuel_inventory table
- **Note**: Uses DIFFERENT table than POS

## Data Flow Summary

```
POS SALE CREATED
    ↓
Validate stock (station_inventory)
    ↓
Insert into sales table
    ↓
Insert into sale_items table
    ↓
UPDATE station_inventory SET stock_level = stock_level - quantity
    ↓
Commit & Done

SEPARATE PROCESS (NOT Connected):
    ↓
Staff records pump reading
    ↓
Manager approves
    ↓
Admin finalizes
    ↓
UPDATE fuel_inventory (DIFFERENT table)
```

## Important Findings

### Gap Between POS and Pump Readings
- POS uses `station_inventory` 
- Pump readings use `fuel_inventory` (separate!)
- No automatic reconciliation
- Can create inventory discrepancies

### Calibration Not Applied in POS
- Pump calibration stored in:
  - `fuel_pumps.calibration_value`
  - `nozzles.calibration_value`
- But POS DOES NOT read these
- Calibration only applied during shift reconciliation

### Stock Deduction Timing
- **When**: Immediately when transaction is saved
- **Not**: When transaction is approved
- **Reason**: Immediate prevention of overselling

### Price Determination
- **Primary**: `fuel_pricing` table (per station, per fuel type)
- **Fallback**: `products.price` column
- **Granularity**: Fuel type only (not pump-specific)

## Testing Checklist

To understand inventory flow, test these scenarios:

1. **Test 1: Simple Sale**
   - Create fuel sale (10L Gasoline at ₱62/L = ₱620)
   - Check `sales` table for SALE-xxxxx record
   - Check `sale_items` for line item (qty=10, price=62)
   - Check `station_inventory` stock_level DECREASED

2. **Test 2: Insufficient Stock**
   - Try to sell MORE than available
   - Should get error "Insufficient stock"
   - No transaction created
   - Stock unchanged

3. **Test 3: Multi-Item Sale**
   - Add 2 different fuel types to one transaction
   - Each item deducted separately
   - Both in same sale record

4. **Test 4: Payment Types**
   - Cash: Accepts without reference
   - GCash: Requires reference number
   - Both affect same inventory

## Inventory Calculation

### POS Stock Level (Real-Time)
```sql
SELECT stock_level FROM station_inventory 
WHERE product_id = ? AND station_id = ?
-- Result: Always up-to-date with POS sales
```

### Pump-Based Stock (Shift-End)
```sql
sales_liters = current_reading - previous_reading - calibration
-- Deducted from SEPARATE fuel_inventory table
```

### ISSUE: Two Different Counts!
- POS says: 900L remaining (from sales)
- Pump reading says: 850L remaining (from pump diff)
- **Which one is correct?** → Need reconciliation!

## Configuration

### Add Fuel Pricing
```bash
php /setup_fuel_pricing.php
```
Creates `fuel_pricing` table with default prices.

### Configure Pumps
Admin UI: `/public/admin_pump_management.php`
- Creates pump records
- Adds nozzles per pump
- Sets calibration values
- NOTE: Not used by POS!

### Change Fuel Prices
Database:
```sql
UPDATE fuel_pricing 
SET price_per_liter = 65.50 
WHERE station_id = 226 AND fuel_type_id = 1
```

## Questions for Integration

1. Should POS records track which pump/nozzle was used?
2. Should calibration be applied at POS level?
3. Should we merge `station_inventory` and `fuel_inventory`?
4. How often should POS vs. pump readings be reconciled?
5. What happens if they don't match?

## Common Errors

| Error | Cause | Fix |
|-------|-------|-----|
| "Product not found" | Product not in inventory for station | Add to station_inventory |
| "Insufficient stock" | Quantity > stock_level | Reduce quantity or add stock |
| No fuel pricing shown | `fuel_pricing` not setup | Run setup_fuel_pricing.php |
| Stock not deducting | Transaction not committed | Check error logs |
| Wrong price | Using products.price instead of fuel_pricing | Verify fuel_pricing entry |

---

**Last Updated**: 2026-02-16
**Created By**: POS System Analysis
**Location**: `/POS_FUEL_INVENTORY_ANALYSIS.md` (detailed version)
