# POS Transaction Form Revamp - Documentation

## Overview

The POS transaction form has been completely revamped to use inventory-driven automation instead of manual text entry. This improves data integrity, pricing accuracy, and stock management.

---

## What Changed

### ✅ Backend Changes

#### 1. Fuel Pricing Configuration Table

**New Table: `fuel_pricing`**
- Separate fuel pricing configuration per station
- Allows dynamic fuel price changes
- Tracks effective dates and who set prices
- References `fuel_types` table for type validation

**Fields:**
- `id` - Primary key
- `station_id` - Which station this price applies to
- `fuel_type_id` - Reference to fuel_types table
- `price_per_liter` - Price per liter (decimal)
- `effective_date` - When this price became effective
- `created_by` - User who set this price
- `is_active` - Whether this price is currently active (tinyint)
- `created_at` - Timestamp when price was created

**Default Fuel Prices:**
- Gasoline: ₱55.00/L
- Diesel: ₱60.00/L
- LPG: ₱45.00/L
- Premium: ₱60.00/L
- Unleaded: ₱58.00/L

#### 2. Inventory Data Loading

The POS form now loads:
- **Fuel Products**: From `inventory` + `fuel_pricing` tables
- **Merchandise Products**: From `products` + `inventory` tables
- Stock levels per product
- Available units (liters for fuel, pieces for merchandise)

#### 3. Transaction Processing Updates

**Immediate Stock Deduction:**
- Stock is deducted immediately when transaction is saved
- No waiting for Manager approval
- Prevents overselling the same item

**Product Reference:**
- Saves actual `product_id` instead of text name
- Links transaction to specific inventory item
- Better audit trail and reporting

**New Sale Items Column:**
- `name` column added to `sale_items` table (if not exists)
- Stores product name alongside product_id for display

---

### ✅ Frontend Changes

#### 1. Product Type Selection

**New Dropdown:**
```html
<select name="product_type" id="product_type">
    <option value="">Select Type</option>
    <option value="fuel">Fuel</option>
    <option value="merch">Merchandise</option>
</select>
```

**Function:** Load appropriate products based on type

---

#### 2. Product Dropdown with Stock Levels

**New Dropdown:**
- Shows product name + stock level + unit
- Examples:
  - "Diesel (Stock: 500 liters)"
  - "Engine Oil 5W-30 (Stock: 20 pieces)"
- **Out of Stock Indicator:**
  - Products with 0 stock show in RED bold text
  - Appends " (OUT OF STOCK)" to product name
  - Can still be selected but validation will block transaction

**Sample HTML:**
```html
<select name="product_id" id="product_id">
    <option value="">Select Product</option>
    <option value="1">Diesel (Stock: 500 liters)</option>
    <option value="2" style="color: #dc3545; font-weight: bold;">Gasoline (OUT OF STOCK)</option>
</select>
```

---

#### 3. Auto-Populated Price (Read-Only for Staff)

**Changes:**
- Price field becomes `readonly` (cannot be modified by Staff)
- Background color: `#f0f0f0` (gray) to indicate read-only
- Automatically populated when product is selected
- No manual calculation errors possible

**Price Source:**
- **Fuel**: From `fuel_pricing` table
- **Merchandise**: From `products` table

---

#### 4. Unit Display

**New Read-Only Field:**
- Shows unit type automatically (liters/pieces/etc.)
- Helps prevent quantity/unit confusion

---

### ✅ JavaScript Functions

#### 1. `loadProducts()`

**Purpose:** Load products based on selected product type

**Behavior:**
- Clears previous product options
- Loads products from `inventoryData` JavaScript object
- Populates dropdown with name + stock level
- Updates "Found X products" status text

**Code Flow:**
```javascript
1. Get product_type value (fuel/merch)
2. Filter inventoryData[type] array
3. Create <option> elements for each product
4. Add styling for out-of-stock items
5. Append to dropdown
6. Call updatePrice()
```

---

#### 2. `updatePrice()`

**Purpose:** Auto-populate price and unit based on selected product

**Behavior:**
- Gets selected product from dropdown
- Populates price field from product data
- Populates unit display field
- Calculates total automatically

**Code Flow:**
```javascript
1. Get selected option
2. Extract price, stock, unit from data attributes
3. Update price input (readonly)
4. Update unit display (readonly)
5. Call calcTotal()
```

---

#### 3. `calcTotal()` (Updated)

**Purpose:** Calculate total amount

**Behavior:**
- Multiplies quantity × price
- Subtracts discount
- Formats as Philippine Peso (₱) with 2 decimals

---

#### 4. `validatePayment()` (Enhanced)

**New Validations Added:**
1. **Product Type Required**: Must select Fuel or Merchandise
2. **Product Required**: Must select a product from dropdown
3. **Stock Availability**: 
   - Checks if requested quantity > available stock
   - Shows error with both available and requested quantities
   - Blocks transaction if insufficient stock
   - Special error for out-of-stock items

**Existing Validations (Kept):**
- Customer name required
- Quantity > 0
- Price >= 0
- Payment type required
- GCash reference number (if GCash selected)

---

## User Workflow (Staff)

### Step 1: Select Product Type
1. Click "Product Type" dropdown
2. Choose "Fuel" or "Merchandise"
3. Products load automatically

### Step 2: Select Product
1. Click "Product" dropdown
2. See list with stock levels
3. Select desired product
   - Price auto-populates
   - Unit auto-displays

### Step 3: Enter Quantity
1. Enter desired quantity
2. Total auto-calculates
3. System validates against stock

### Step 4: Enter Customer & Payment
1. Enter customer name (or default "Walk-in")
2. Select payment type (Cash/GCash)
3. Enter GCash reference (if GCash)
4. Enter discount (if any)

### Step 5: Save Transaction
1. Click "Save Transaction"
2. System validates all fields
3. Stock deducted immediately
4. Transaction saved with product reference

---

## Error Messages

### Stock Shortage
```
❌ Insufficient stock! 
Available: 500 liters
Requested: 600 liters
```

### Out of Stock
```
❌ This product is out of stock. 
Please select a different product or restock first.
```

### Invalid Selection
```
❌ Product is required. Please select a product from the dropdown.
```

---

## Database Schema Changes

### New Tables

1. **`fuel_pricing`**
   - Stores fuel prices per station
   - Tracks effective dates and changes

### Modified Tables

1. **`sale_items`**
   - Added `name` column (if not exists)
   - Stores product name alongside product_id

2. **`inventory`**
   - Stock levels deducted immediately on transaction
   - No changes to schema (uses existing columns)

---

## Installation

### 1. Run Database Setup

**Option A: Run PHP Script (Recommended)**
```bash
php setup_fuel_pricing.php
```

**Option B: Manual SQL Execution**
```sql
-- Execute this SQL:
SOURCE /opt/lampp/htdocs/group31petron_system_official4/sql/fuel_pricing_table.sql
```

### 2. Verify Installation

**Check Table Exists:**
```sql
SHOW TABLES LIKE 'fuel_pricing';
```

**Check Default Data:**
```sql
SELECT * FROM fuel_pricing WHERE is_active = 1;
```

**Expected Output:**
- 5 rows (one for each fuel type)
- Station ID: 1
- Prices configured per fuel type

---

## Admin Configuration

### Setting Fuel Prices

**Method 1: Direct SQL Insert**
```sql
INSERT INTO fuel_pricing (station_id, fuel_type_id, price_per_liter, created_by, is_active)
VALUES (1, 2, 62.50, 5, 1);
```

**Method 2: Create Management Interface** (Future Enhancement)
- Add fuel pricing management page
- Allow Admin to set prices per station
- Track price change history
- Log who changed prices and when

**Price Change Best Practices:**
1. Deactivate old prices (`is_active = 0`)
2. Insert new price with `effective_date = NOW()`
3. Keep history for audit trail
4. Only one active price per fuel type per station

---

## Benefits Summary

### ✅ For Staff
1. **Faster Entry** - Dropdown selection vs typing
2. **No Pricing Errors** - Auto-populated from database
3. **Stock Awareness** - See stock levels before selling
4. **Unit Clarity** - Always know if it's liters or pieces
5. **Prevent Overselling** - System validates stock availability

### ✅ For Management
1. **Data Integrity** - Actual product IDs, not text names
2. **Better Tracking** - Link transactions to inventory
3. **Stock Accuracy** - Automatic deduction prevents manual errors
4. **Audit Trail** - Complete transaction-to-product mapping

### ✅ For Reports
1. **Accurate Data** - Product references enable better analytics
2. **Stock Analysis** - Track which products sell fastest
3. **Fuel Pricing History** - When prices changed and by whom

---

## Troubleshooting

### Issue: Products Not Loading

**Symptoms:** Product dropdown shows "Select Product" only, no options

**Possible Causes:**
1. Product type not selected
2. Inventory table has no products for station
3. Database connection error

**Solutions:**
1. Verify `inventoryData` object exists in browser console
2. Check database for products linked to station
3. Review PHP error logs

### Issue: Price Not Populating

**Symptoms:** Price field stays empty after selecting product

**Possible Causes:**
1. Product has NULL price in database
2. JavaScript error in `updatePrice()` function
3. Data attributes not set on option elements

**Solutions:**
1. Check browser console for JavaScript errors
2. Verify product records have valid prices
3. Test with known valid product

### Issue: Stock Not Deducting

**Symptoms:** Transaction saves but stock levels unchanged

**Possible Causes:**
1. Product not linked to inventory for station
2. SQL UPDATE statement failing silently
3. Transaction rolled back due to error

**Solutions:**
1. Check `inventory` table for station_id matches
2. Review PHP error logs
3. Verify transaction committed successfully

---

## Future Enhancements

### Recommended Improvements

1. **Fuel Price Management Interface**
   - Admin UI to set/change fuel prices
   - Price change history tracking
   - Effective date scheduling

2. **Barcode Scanner Integration**
   - Scan product barcode instead of dropdown
   - Faster entry for high-volume items

3. **Multi-Product Transactions**
   - Add multiple products to single transaction
   - Common in merchandise sales

4. **Quick Actions**
   - Hotkey shortcuts (F1=Fuel, F2=Merch)
   - Most recent products list

5. **Stock Reorder Alerts**
   - Automatic notifications when stock below reorder level
   - Suggest stock request creation

---

## Files Modified

1. **`/public/pos.php`**
   - Backend: Added inventory/fuel pricing data loading
   - Backend: Modified transaction processing to save product_id and deduct stock
   - Frontend: Replaced Item/Service input with Product Type + Product dropdowns
   - Frontend: Made price read-only
   - Frontend: Added unit display
   - JavaScript: Added loadProducts(), updatePrice() functions
   - JavaScript: Enhanced validatePayment() with stock validation

2. **`/sql/fuel_pricing_table.sql`** (NEW)
   - SQL schema for fuel_pricing table
   - Default data insertion

3. **`/setup_fuel_pricing.php`** (NEW)
   - PHP script to create fuel_pricing table
   - Inserts default fuel pricing data
   - Displays current active prices

---

## Support & Questions

For issues or questions about the POS revamp:
1. Check browser console for JavaScript errors
2. Review PHP error logs
3. Verify database tables exist with correct structure
4. Test with different product types (fuel/merchandise)
5. Validate stock levels in inventory table

---

**Last Updated:** February 15, 2026
**Version:** 2.0
**Status:** ✅ Complete and Ready for Use
