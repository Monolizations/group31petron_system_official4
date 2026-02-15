# Inventory Consolidation - Phase 4 Testing Guide

## Access the Automated Test Suite

**URL**: `http://localhost/group31petron_system_official4/test_inventory_consolidation.php`

This automated test will verify:
✅ Table structure and columns exist
✅ Data integrity (no orphaned products/stations)
✅ Query compatibility (SELECT, UPDATE, JOIN queries work)
✅ Audit tables (inventory_logs, inventory_transactions)
✅ Backup table (inventory_legacy_backup)
✅ Multi-station isolation

---

## Manual Testing Steps (After Automated Tests Pass)

### 4.2: POS Workflow Testing

**Objective**: Verify POS transactions work correctly with station_inventory

**Steps**:
1. Login as staff user at `/public/pos.php`
2. Select product type: "merch" or "fuel"
3. Verify products load from station_inventory dropdown
4. Select a product with available stock
5. Enter quantity (less than available stock)
6. Select payment method
7. Complete transaction
8. Verify stock_level decreased in database:
   ```sql
   SELECT stock_level FROM station_inventory 
   WHERE product_id = [product_id] AND station_id = [station_id]
   ```

**Expected Result**: Stock level should decrease by transaction quantity

---

### 4.3: Receiving Workflow Testing

**Objective**: Complete end-to-end receiving workflow

**Steps**:
1. **Staff**: Login to `/public/receiving_staff.php`
   - Create new receiving batch
   - Add 2-3 merchandise items with quantities
   - Submit batch (status: pending)

2. **Manager**: Login to `/public/approvals_center.php`
   - Review pending batch
   - Approve batch
   - Verify station_inventory records created/updated

3. **Manager**: Login to `/public/pricing_received_items.php`
   - Set prices for received items
   - Confirm batch (status: confirmed)

4. **Verify**: Check database
   ```sql
   SELECT * FROM station_inventory 
   WHERE product_id IN (received_product_ids)
   ```

**Expected Result**: station_inventory should show updated quantities and prices

---

### 4.4: Dashboard & Reports Testing

**Objective**: Verify UI pages display correct inventory data

**Steps**:
1. Load `/public/home.php`
   - Should display without errors
   - Check inventory overview data

2. Load `/public/dashboard.php`
   - Verify inventory stats display
   - Check total inventory value calculation
   - Check stock levels by station

3. Run `/public/reports.php`
   - Generate inventory report
   - Verify products and quantities match database

**Expected Result**: All pages load without errors, data matches database

---

### 4.5: Reconciliation & Audit Testing

**Objective**: Verify fuel reconciliation and audit logging work

**Steps**:
1. Login as staff to `/public/reconciliation.php`
   - Record pump readings
   - Submit readings

2. Login as manager to approve readings

3. Check audit trail: `/public/audit_logs.php`
   - Filter by inventory actions
   - Verify all changes logged

4. Verify inventory_logs table:
   ```sql
   SELECT * FROM inventory_logs 
   WHERE created_at > NOW() - INTERVAL 1 HOUR
   ```

**Expected Result**: All inventory changes captured in audit logs

---

## Quick Checklist

After running automated tests and manual workflows:

- [ ] Automated test suite at test_inventory_consolidation.php shows all green
- [ ] POS transactions work and stock decreases correctly
- [ ] Receiving workflow completes successfully
- [ ] Dashboard displays correct inventory totals
- [ ] Reports generate without errors
- [ ] Audit logs capture all changes
- [ ] No error messages in browser console
- [ ] All products load in dropdowns from station_inventory

## Sign-Off

Once all tests pass:

**Phase 4 Status**: ✅ COMPLETE

Next step: Phase 5 (Drop old table, add constraints, finalize)

---

## Troubleshooting

If any test fails:

1. Check browser console for JavaScript errors
2. Check PHP error logs in `/var/log/apache2/error.log`
3. Review git diff for recent changes: `git diff 4803ade~1..4803ade`
4. Verify database connection is working
5. Ensure `station_inventory` table has data

**If critical issues found**: Run `git revert 4803ade` to rollback all changes
