# 🚀 Inventory Consolidation - Quick Start Testing

## Step 1: Run Automated Tests (5 minutes)

**Open this URL in your browser:**
```
http://localhost/group31petron_system_official4/test_inventory_consolidation.php
```

**What it tests:**
- ✅ Table structure
- ✅ Data integrity
- ✅ Query compatibility
- ✅ Audit and backup tables
- ✅ Multi-station support

**Expected Result:** All tests show green ✅

---

## Step 2: Quick Manual Test (10 minutes)

### Test POS Transaction
1. Open: `http://localhost/group31petron_system_official4/public/pos.php`
2. Login as **staff**
3. Select product type: **merch** or **fuel**
4. Select any product with stock > 0
5. Enter quantity (e.g., 1)
6. Complete transaction
7. **Verify**: Product stock decreased in database

**Expected**: No errors, transaction completes, stock updates

### Test Receiving Workflow
1. Open: `http://localhost/group31petron_system_official4/public/receiving_staff.php`
2. Create new batch with 5 units of any product
3. Login as **manager** to: `http://localhost/group31petron_system_official4/public/approvals_center.php`
4. Approve the batch
5. Go to pricing page and set prices
6. **Verify**: `station_inventory` shows updated quantities

**Expected**: Batch approved, inventory updated, no errors

### Test Dashboard
1. Open: `http://localhost/group31petron_system_official4/public/dashboard.php`
2. Check inventory statistics display correctly
3. **Verify**: Numbers match database

**Expected**: Dashboard loads, displays correct data

---

## Step 3: Sign-Off

Once all tests pass:

**Report**: "Phase 4 testing complete - all tests passed ✅"

Then I'll execute Phase 5 (final cleanup).

---

## Troubleshooting

| Issue | Solution |
|-------|----------|
| Test page won't load | Ensure `test_inventory_consolidation.php` exists |
| POS dropdown empty | Check `station_inventory` has data |
| Error on transaction | Check database for stock_level column |
| Receiving fails | Verify `receiving_staff.php` updated correctly |

---

## If All Tests Pass

✅ Inventory consolidation is complete
✅ All workflows functioning
✅ Ready for Phase 5 (drop old table)

## If Any Test Fails

⚠️ Do NOT proceed to Phase 5
⚠️ Rollback: `git revert 55d3c03`
⚠️ Investigate root cause
⚠️ Ask for help

---

**Let me know when you've completed testing!** 🎉
