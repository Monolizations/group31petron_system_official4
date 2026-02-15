# Multi-Item Receiving Workflow - Complete Documentation
# =========================================
# Implementation Date: 2026-02-15
# Status: ✅ Implementation Complete

## 📋 Overview

A **three-stage receiving workflow** has been implemented to allow staff to encode multiple items in batches, which then go through manager/admin review and final stock confirmation before being added to inventory.

---

## 🔄 Workflow Stages

### Stage 1: Staff Encodes Batch (Pending)
**Page:** `/public/receiving_batches.php`

**Who can access:** Staff only

**Features:**
- ✅ Multi-item form with dynamic add/remove rows
- ✅ Auto-fill default supplier from settings
- ✅ Supplier dropdown with all available suppliers
- ✅ Product autocomplete from database
- ✅ Edit pending batches before review
- ✅ Batch number auto-generated: `REC-{station_id}-{YYYYMMDD}-{sequence}`
- ✅ Delivery notes field

**Database Actions:**
- Inserts into `receiving_batches` table with status `pending`
- Inserts multiple items into `received_items` table with status `pending`
- Does NOT update `station_inventory` yet

**Batch Example:**
```
Batch Number: REC-226-20260215-001
Station: 226
Supplier: Petron Supplier
Delivery Date: 2026-02-15
Status: pending
Items:
  - Engine Oil 5W-30: 50 pcs
  - Air Filter: 20 pcs
  - Brake Fluid: 10 pcs
```

---

### Stage 2: Manager/Admin Receives Batch (Received)
**Page:** `/public/manager_receiving_review.php`

**Who can access:** Manager, Admin, Superadmin

**Features:**
- ✅ View pending batches awaiting review
- ✅ View received batches ready for confirmation
- ✅ View batch details with all items
- ✅ Approve (Receive) batch with one click
- ✅ Reject batch with reason (minimum 10 characters)
- ✅ View who submitted and when
- ✅ Track who received/approved

**Database Actions (Receive):**
- Updates `receiving_batches`:
  - `status` = `received`
  - `received_by_manager` = current_user_id
  - `received_at` = NOW()
- Updates `received_items`:
  - `status` = `received` (all items in batch)
- Does NOT update `station_inventory` yet

**Database Actions (Reject):**
- Updates `receiving_batches`:
  - `status` = `rejected`
  - `notes` = original notes + rejection reason
  - `rejected_at` = NOW()
- Updates `received_items`:
  - `status` = `rejected` (all items in batch)
- Does NOT update `station_inventory`

**Actions Available:**
- **Receive** - Moves batch to Stage 3 for stock confirmation
- **Reject** - Marks batch as rejected, sends back to staff

---

### Stage 3: Stock Confirmation (Confirmed)
**Page:** `/public/admin_stock_confirmation.php`

**Who can access:** Manager, Admin, Superadmin

**Features:**
- ✅ View received batches ready for confirmation
- ✅ Show current inventory levels for each item
- ✅ Show projected inventory after confirmation
- ✅ **Partial Confirmation** - Confirm fewer items than received
- ✅ Confirm specific quantities per item
- ✅ Return batch to Stage 2 if needed
- ✅ Add confirmation notes
- ✅ Real-time inventory preview

**Database Actions (Confirm Stock):**
- For each confirmed item:
  - Updates or creates record in `station_inventory`
  - Adds quantity to `stock_level`
  - Creates entry in `inventory_logs` with:
    - `quantity_before` = current stock
    - `quantity_after` = new stock
    - `quantity_change` = added quantity
    - `reference_type` = 'receiving_batch'
- Updates `received_items`:
  - `status` = `confirmed` (only for fully confirmed items)
  - Status remains `received` for partial confirmations
- Updates `receiving_batches`:
  - If ALL items confirmed: `status` = `confirmed`, `confirmed_by` = user_id, `confirmed_at` = NOW()
  - If PARTIAL: `status` remains `received` (for remaining items)

**Partial Confirmation Example:**
```
Batch: REC-226-20260215-001
Items Received:
  - Engine Oil 5W-30: 50 pcs
  - Air Filter: 20 pcs
  - Brake Fluid: 10 pcs

Confirmation Actions:
  - Confirm Engine Oil 5W-30: 50 pcs ✅ (full)
  - Confirm Air Filter: 15 pcs ✅ (partial)
  - Leave Brake Fluid: 0 pcs ⏸️ (skip)

Result:
  - Engine Oil 5W-30: status = confirmed, stock +50
  - Air Filter: status = received, stock +15, 5 pending
  - Brake Fluid: status = received, stock +0, 10 pending
  - Batch status: received (not all items confirmed)
```

**Database Actions (Return to Pending):**
- Updates `receiving_batches`:
  - `status` = `pending`
  - `received_by_manager` = NULL
  - `received_at` = NULL
  - `notes` = notes + return reason
- Updates `received_items`:
  - `status` = `pending` (all items)

---

## 📊 Database Schema

### New Table: `receiving_batches`
```sql
CREATE TABLE receiving_batches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    batch_number VARCHAR(50) UNIQUE NOT NULL,
    station_id INT NOT NULL,
    supplier VARCHAR(255) NOT NULL,
    delivery_date DATE NOT NULL,
    notes TEXT,
    received_by INT NOT NULL,           -- Staff who encoded
    received_by_manager INT NULL,         -- Manager/Admin who approved
    confirmed_by INT NULL,                -- Manager/Admin who confirmed stock
    status ENUM('pending', 'received', 'confirmed', 'rejected') DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    received_at DATETIME NULL,
    confirmed_at DATETIME NULL,
    rejected_at DATETIME NULL,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (station_id) REFERENCES stations(id),
    FOREIGN KEY (received_by) REFERENCES users(id),
    FOREIGN KEY (received_by_manager) REFERENCES users(id),
    FOREIGN KEY (confirmed_by) REFERENCES users(id)
);
```

### Modified Table: `received_items`
```sql
-- Added columns:
ALTER TABLE received_items
ADD COLUMN batch_id INT NULL AFTER id,
ADD COLUMN status ENUM('pending', 'received', 'confirmed', 'rejected') DEFAULT 'pending' AFTER created_at,
ADD INDEX (batch_id),
ADD FOREIGN KEY (batch_id) REFERENCES receiving_batches(id) ON DELETE CASCADE;
```

---

## 🔐 Role Permissions

| Role | Stage 1 (Encode) | Stage 2 (Receive) | Stage 3 (Confirm) |
|------|------------------|-------------------|-------------------|
| **Staff** | ✅ Can encode | ❌ Cannot access | ❌ Cannot access |
| **Manager** | ❌ Cannot encode | ✅ Can receive | ✅ Can confirm |
| **Admin** | ❌ Cannot encode | ✅ Can receive | ✅ Can confirm |
| **Superadmin** | ❌ Cannot encode | ✅ Can receive | ✅ Can confirm |
| **All roles** | ✅ Can view own pending batches | ✅ Can view all batches (superadmin) | ✅ Can view all batches (superadmin) |

---

## 🎨 UI/UX Features

### Stage 1: Staff Encoding
- Dynamic item rows with add/remove buttons
- Product autocomplete from database
- Supplier auto-fill from default settings
- Edit pending batches functionality
- Clean, intuitive form layout
- Real-time item count

### Stage 2: Manager Review
- Card-based batch listing
- Color-coded status badges:
  - Pending: 🟡 Yellow
  - Received: 🔵 Blue
  - Rejected: 🔴 Red
- Quick view of batch details
- One-click approve/reject
- Rejection modal with reason field

### Stage 3: Stock Confirmation
- Inventory preview table showing:
  - Current stock level
  - Received quantity
  - Projected stock after confirmation
  - Input field for confirm quantity
- Partial confirmation support
- Clear visual indicators for stock changes
- Return to pending option

---

## 🔗 URLs

### Staff Access
- Encode Batches: `/public/receiving_batches.php`
- View Pending Batches: Available via edit link on encoded batches

### Manager/Admin Access
- Review Batches: `/public/manager_receiving_review.php`
  - View Pending: `?view=pending`
  - View Received: `?view=received`
  - View Details: `?view=pending&batch={id}`

- Confirm Stock: `/public/admin_stock_confirmation.php`
  - View Received: `?view=received`
  - View Details: `?view=received&batch={id}`

---

## 🧪 Testing Checklist

### Stage 1: Staff Encodes
- [ ] Staff logs in
- [ ] Navigate to receiving_batches.php
- [ ] Add multiple items
- [ ] Select supplier (default auto-fills)
- [ ] Set delivery date
- [ ] Add notes
- [ ] Submit batch
- [ ] Verify batch created with status 'pending'
- [ ] Verify items linked to batch
- [ ] Verify inventory NOT updated
- [ ] Edit pending batch
- [ ] Verify edit works

### Stage 2: Manager Receives
- [ ] Manager logs in
- [ ] Navigate to manager_receiving_review.php
- [ ] See pending batches
- [ ] Click on batch to review
- [ ] See all items and quantities
- [ ] Click "Receive" button
- [ ] Verify batch status changed to 'received'
- [ ] Verify all items status = 'received'
- [ ] Verify inventory NOT updated yet
- [ ] Reject a batch with reason
- [ ] Verify batch status = 'rejected'

### Stage 3: Stock Confirmation
- [ ] Manager logs in
- [ ] Navigate to admin_stock_confirmation.php
- [ ] See received batches
- [ ] Click on batch to confirm
- [ ] See current inventory levels
- [ ] See projected inventory
- [ ] Confirm all items
- [ ] Verify batch status = 'confirmed'
- [ ] Verify items status = 'confirmed'
- [ ] Verify inventory updated correctly
- [ ] Verify inventory_logs created
- [ ] Test partial confirmation
  - [ ] Confirm fewer items
  - [ ] Verify batch stays 'received'
  - [ ] Verify confirmed items status = 'confirmed'
  - [ ] Verify remaining items status = 'received'
- [ ] Return batch to pending
  - [ ] Verify batch returns to Stage 2
  - [ ] Verify items status = 'pending'

---

## 📈 Backward Compatibility

**Existing `received_items` without `batch_id`:**
- These entries are considered legacy data
- They remain in the system
- They can still be viewed in inventory
- Their status is implicitly 'confirmed'
- They are NOT affected by new workflow

**Migration (if needed):**
To migrate legacy items to the new structure:
```sql
UPDATE received_items
SET status = 'confirmed'
WHERE batch_id IS NULL;
```

---

## 🎯 Benefits

### For Staff
- ✅ Faster receiving - encode multiple items at once
- ✅ Edit mistakes before manager reviews
- ✅ Clear view of what's pending
- ✅ Product autocomplete reduces errors

### For Manager/Admin
- ✅ Control over stock additions
- ✅ Two-stage approval process
- ✅ Partial confirmation support
- ✅ Audit trail for all changes
- ✅ Return to pending if issues found

### For Business
- ✅ Better inventory control
- ✅ Reduced errors
- ✅ Complete audit trail
- ✅ Flexible approval workflow
- ✅ Partial receipt handling

---

## 🔍 SQL Queries for Verification

### Check Batches
```sql
-- View all batches
SELECT rb.*, u.name as submitted_by
FROM receiving_batches rb
LEFT JOIN users u ON rb.received_by = u.id
ORDER BY rb.created_at DESC;

-- View batches by status
SELECT * FROM receiving_batches WHERE status = 'pending';
SELECT * FROM receiving_batches WHERE status = 'received';
SELECT * FROM receiving_batches WHERE status = 'confirmed';

-- View batch items
SELECT ri.*, p.name as product_name
FROM received_items ri
LEFT JOIN products p ON ri.product_id = p.id
WHERE ri.batch_id = 1;
```

### Check Inventory Logs
```sql
-- View recent stock additions
SELECT il.*, p.name as product_name, u.name as user_name
FROM inventory_logs il
LEFT JOIN products p ON il.product_id = p.id
LEFT JOIN users u ON il.user_id = u.id
WHERE il.reference_type = 'receiving_batch'
ORDER BY il.created_at DESC
LIMIT 20;
```

### Check Current Inventory
```sql
-- View station inventory with batches
SELECT si.*, p.name as product_name,
       COUNT(ri.id) as batch_count
FROM station_inventory si
LEFT JOIN products p ON si.product_id = p.id
LEFT JOIN received_items ri ON si.product_id = ri.product_id
WHERE si.station_id = 226
GROUP BY si.id
ORDER BY si.stock_level DESC;
```

---

## 🚨 Error Handling

### Stage 1 Errors
- "Supplier is required" - Must select supplier
- "At least one item is required" - Must add at least one item
- Database errors on batch creation

### Stage 2 Errors
- "Batch not found or already processed" - Batch doesn't exist or not pending
- "Rejection reason must be at least 10 characters" - Need valid reason

### Stage 3 Errors
- "Batch not found or not ready for confirmation" - Batch doesn't exist or not received
- "Please select at least one item to confirm" - Must confirm at least one item
- Database errors on inventory update

---

## 📝 Next Steps

### Optional Enhancements
1. **Email Notifications** - Notify staff when batch is approved/rejected
2. **SMS Notifications** - Send SMS for urgent batches
3. **Barcode Scanning** - Scan products to auto-populate
4. **Photo Upload** - Add photos of received goods
5. **Quality Check** - Add quality inspection workflow
6. **Signature Capture** - Digital signature on delivery
7. **Batch Templates** - Pre-defined item lists for common deliveries

### Analytics & Reports
1. **Receiving Dashboard** - Show pending/received/confirmed stats
2. **Supplier Performance** - Track delivery accuracy
3. **Staff Performance** - Track encoding speed/accuracy
4. **Inventory Movement** - Stock in/out over time
5. **Partial Confirmation Report** - Track partial receipts

---

## 📞 Support

For issues or questions:
1. Check database tables exist: `receiving_batches`, `received_items`
2. Check column names: `batch_id`, `status` in `received_items`
3. Check foreign keys are correct
4. Verify user has correct role permissions
5. Check browser console for JavaScript errors
6. Check network tab for failed API calls

---

**Implementation Complete:** ✅
**Ready for Testing:** ✅
**Ready for Production:** ✅ (after testing)
