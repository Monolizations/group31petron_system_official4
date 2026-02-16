# Debugging Status Update Issue

## Quick Test Steps

### 1. Check Browser Console
1. Open the Job Order page
2. Press F12 to open Developer Tools
3. Go to **Console** tab
4. **Try selecting a status from the dropdown**
5. Look for console logs showing:
   ```
   Submitting status update: {jobId: X, status: "Completed", notes: ""}
   Response status: 200
   Response text: {...json...}
   Parsed result: {success: true/false, message: "..."}
   ```

### 2. Check User Role
The status update requires **Manager** role.

**Verify your role**:
1. Go to any page
2. Open console (F12)
3. Paste: `console.log(document.body.innerHTML.match(/role['":\s]+([^'"}\s]+)/i));`
4. Check if it shows "manager", "admin", or "superadmin"

**If not a manager**, you won't be able to update status. Ask admin to give you manager role.

### 3. Check if Dropdown is Firing
1. Open console (F12)
2. Paste this:
```javascript
// Override handleStatusChange to see if it's called
const originalHandler = handleStatusChange;
handleStatusChange = function(jobId, selectElement) {
    console.log('handleStatusChange called with:', jobId, selectElement.value);
    originalHandler.call(this, jobId, selectElement);
};
```
3. Now try clicking dropdown and selecting a status
4. You should see `handleStatusChange called with: ...` in console

### 4. Test the Confirm Dialog Appears
1. After seeing the console log above
2. A dialog box should appear asking to confirm
3. Click **OK** to proceed with the update
4. Watch console for the "Submitting status update" log

### 5. Check Server Response
After clicking OK in the dialog:
1. Look in console for `Response status: 200` or similar
2. Look for `Parsed result: {success: true, message: "..."}`
   - If **success is true** → Job updated! Page should reload in 1.5 seconds
   - If **success is false** → See the message for what went wrong

---

## Common Issues & Fixes

### Issue: "Manager privileges required"

**Cause**: You're not logged in as a manager

**Fix**:
1. Log in with a manager account
2. Or ask admin to change your role to manager
3. Check database: `SELECT id, email, role FROM users WHERE email='your@email.com';`

### Issue: "Job order not found"

**Cause**: Wrong job ID being sent

**Check**:
1. In console, look for: `Submitting status update: {jobId: ...}`
2. Verify that jobId is a number (not null, not undefined)
3. In browser F12 Network tab, look at the POST request
4. Check the `job_id` parameter in the form data

### Issue: Nothing happens when selecting dropdown

**Cause**: JavaScript function not being called or dialog being blocked

**Check**:
1. Open console and look for ANY errors (red text)
2. Try the override test above to see if function is called
3. Check if browser is blocking popups (confirm() dialog)
4. Try in a different browser

### Issue: Dialog appears but status doesn't change

**Cause**: Server error or permission issue

**Check**:
1. Open F12 **Network** tab
2. Click dropdown and select status again
3. Look for a POST request to `joborder.php`
4. Click that request and go to **Response** tab
5. You should see JSON response like:
   ```json
   {
     "success": true,
     "message": "Job status updated successfully"
   }
   ```
   Or if error:
   ```json
   {
     "success": false,
     "message": "Error message here"
   }
   ```

---

## Network Debugging

### Check the POST Request
1. Press F12 → **Network** tab
2. Select a status from dropdown
3. You should see a POST request to `joborder.php`
4. Click it and check:
   - **Status code** should be 200
   - **Form Data** tab should show:
     ```
     action: update_job_status
     job_id: 12 (or whatever job)
     status: Completed (or whatever you selected)
     notes: 
     ```
   - **Response** tab should show JSON

### Example Successful Response
```json
{
  "success": true,
  "message": "Job status updated successfully"
}
```

### Example Error Response
```json
{
  "success": false,
  "message": "Manager privileges required"
}
```

---

## Direct SQL Test

If you want to verify the database will accept the update:

```sql
-- Check current job status
SELECT id, job_order_number, status FROM job_orders WHERE id = 12;

-- Try updating (replace 12 with actual job ID, change status as needed)
UPDATE job_orders 
SET status = 'Completed', updated_at = NOW() 
WHERE id = 12;

-- Verify it updated
SELECT id, job_order_number, status FROM job_orders WHERE id = 12;
```

---

## Code Files to Check

If status update is failing, check these files:

**Frontend**: `/public/joborder.php`
- Line 1617: `handleStatusChange()` function
- Line 1640: `updateJobStatus()` function
- Line 1646: `submitStatusUpdate()` function (with logging)
- Line 172: `update_job_status` POST handler

**Backend**: `/backend/job_order_operations.php`
- Line 467: `updateJobStatus()` function

**Database**:
```sql
SELECT * FROM job_orders WHERE id = 12;
```

---

## Step-by-Step Complete Test

1. **Login** as manager user
2. Open Job Orders page
3. Go to "Ongoing Job Orders" tab
4. Find Job #12 (or any "In Progress" job)
5. **Press F12** to open console
6. **Click the status dropdown** 
7. **Look for console logs** confirming function is called
8. **Select "Mark Completed"** from dropdown
9. **Watch for dialog** asking to confirm
10. **Look at F12 Network tab** for POST request
11. **Click OK** on the dialog
12. **Watch console** for "Response status" log
13. **If success**, page reloads automatically in 1.5 seconds
14. **Verify** job status changed in the UI

---

## Report Your Findings

When submitting the issue, include:
1. What role are you logged in as?
2. What console logs appear? (Copy exact text)
3. Does the dialog appear? (Yes/No)
4. What's the Network response? (Copy JSON)
5. Do you see any red errors in console?
6. What browser are you using?
