# Job Order Workflow - Testing Guide

## Quick Test Steps

### Test 1: Status Dropdown Confirmation Dialog ✅

**Location**: Ongoing Job Orders tab → Job #12 (or any "In Progress" job)

**Steps**:
1. Click on the "Ongoing Job Orders" tab
2. Find any job card with status "In Progress"
3. Look for the dropdown that says "-- Change Status --"
4. **Click on the dropdown** to see the options:
   - In Progress
   - Mark Completed
   - Cancel Job

**Expected Behavior**:
- When you select "Mark Completed" → A dialog should appear saying:
  ```
  ⚠️ Mark job as COMPLETED?

  This will:
  • Move to History
  • Lock all edits
  • Finalize the order
  ```
- When you select "Cancel Job" → A dialog should appear saying:
  ```
  ⚠️ Cancel this job?

  This cannot be easily undone.
  ```
- When you select "In Progress" → A simple dialog: `Change job status to "In Progress"?`

**If dialog doesn't appear**:
1. Open browser console (F12 → Console tab)
2. Try selecting a status again
3. Check for any JavaScript errors in red
4. Look for console logs showing `Selected status: ...`

---

### Test 2: Parts Used Table ✅

**Location**: Ongoing Job Orders tab → "📦 Parts Used" button

**Steps**:
1. Click on the "Ongoing Job Orders" tab
2. Find any job card and click the "📦 Parts Used" button
3. A modal window should open with a table showing:
   - Part Name (input field)
   - Quantity (number input)
   - Unit Price (number input, shows ₱)
   - Total (auto-calculated)
   - Action (Remove button)

**Test Auto-Calculation**:
1. Enter Part Name: "Oil Filter"
2. Enter Quantity: "2"
3. Enter Unit Price: "150.00"
4. The Total should automatically show: "₱300.00"
5. If you change Quantity to "3", Total should update to "₱450.00"

**Test Add More Parts**:
1. Click "Add Another Part" button
2. A new row should appear with empty fields
3. Fill in the new part details
4. It should calculate automatically

**Test Remove Part**:
1. Click the "Remove" button on any row
2. That row should disappear
3. Other rows should remain intact

**Test Submit**:
1. Add at least one part with all fields filled
2. You can add optional notes in the "Additional Notes" field
3. Click "Confirm Parts" button
4. Should see success message: "Parts confirmed and inventory updated!"
5. Page should reload

---

### Test 3: Complete Workflow (End-to-End)

**Setup**: You need:
- A pending job order (Staff creates it)
- Manager logged in
- Access to Ongoing Jobs tab

**Steps**:

#### Step 1: Manager Approves Job
1. Go to "Pending Job Orders" tab
2. Find a pending job
3. Click "Approve" button (not the dropdown!)
4. Job should move to "In Progress" in the Ongoing Jobs tab

#### Step 2: Add Parts During Work
1. Go to "Ongoing Job Orders" tab
2. Click "📦 Parts Used" on the job
3. Add parts used (e.g., Oil, Filter, etc.)
4. Click "Confirm Parts"
5. Should see success message

#### Step 3: Complete the Job
1. In "Ongoing Job Orders", select "Mark Completed" from dropdown
2. Should see warning dialog about finalization
3. Click OK to confirm
4. Job should move to "History" tab

#### Step 4: Verify in History
1. Go to "History" tab (Job Order History)
2. Find the completed job
3. Status should show as "Completed"

---

## Testing the JavaScript Flow

### Browser Console Test

Open your browser console (F12) and paste this to verify the dropdown works:

```javascript
// Simulate what happens when you select a status
function testDropdown() {
    const status = "Completed";
    let confirmMessage = `Change job status to "${status}"?`;
    
    if (status === 'Completed') {
        confirmMessage = '⚠️ Mark job as COMPLETED?\n\nThis will:\n• Move to History\n• Lock all edits\n• Finalize the order';
    }
    
    console.log('Message:', confirmMessage);
    if (confirm(confirmMessage)) {
        console.log('User confirmed!');
    } else {
        console.log('User cancelled');
    }
}

testDropdown(); // This should show a dialog
```

If this works in console, the JavaScript is functional.

---

## Database Status

### Jobs Available for Testing

Run this to see available test jobs:

```sql
SELECT id, job_order_number, status, customer_name, mechanic_name 
FROM job_orders 
WHERE status IN ('Pending', 'In Progress', 'Completed')
ORDER BY created_at DESC 
LIMIT 10;
```

### Check Parts Recorded

```sql
SELECT jp.*, jo.job_order_number
FROM job_order_parts jp
JOIN job_orders jo ON jp.job_order_id = jo.id
ORDER BY jp.created_at DESC
LIMIT 10;
```

---

## Troubleshooting

### Dialog doesn't appear when selecting dropdown

**Check**:
1. Is JavaScript console showing any errors? (F12 → Console)
2. Is `handleStatusChange()` function defined? (Console: `typeof handleStatusChange`)
3. Try manually testing in console with the test code above

**Solution**:
- Make sure browser has JavaScript enabled
- Clear browser cache (Ctrl+Shift+Delete)
- Try a different browser
- Check that joborder.php loaded completely (look for `<script>` tag at bottom)

### Parts table not showing

**Check**:
1. Is the modal window opening? (Check browser console)
2. Is `confirmPartsUsed()` function being called?

**Solution**:
- Verify `partsModal` element exists in HTML (search page for `id="partsModal"`)
- Check console for errors when clicking Parts Used button
- Verify browser is not blocking popups

### Parts not being saved

**Check**:
1. Are you filling in all fields? (Part Name, Quantity, Unit Price)
2. Does console show any network errors? (F12 → Network tab)
3. Is there a "success" or "error" message in the UI?

**Solution**:
- At least one part must be added
- Check server logs: `tail /opt/lampp/logs/error_log`
- Verify database table `job_order_parts` has the `part_name` column

---

## Code Files Modified

- **Backend**: `/backend/job_order_operations.php` - Added `confirmPartsUsed()` function
- **Frontend**: `/public/joborder.php` - Updated dropdown and parts modal
- **Database**: Added `part_name` column to `job_order_parts` table

---

## Notes

- The dropdown resets after selection to allow re-selecting the same status
- Parts are not required to complete a job (optional feature)
- Each part entry is logged in the activity log
- Confirmation dialogs use browser's native `confirm()` for simplicity
