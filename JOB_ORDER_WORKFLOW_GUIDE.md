# Job Order Workflow - Manual Testing Guide

## Overview
The Job Order system is a multi-stage service workflow with 5 key statuses:
1. **Pending** - Created by staff, awaiting manager review
2. **Reviewed** - Manager approved, awaiting final approval for execution
3. **In Progress** - Job is being worked on by mechanic
4. **Completed** - Job finished, billing locked
5. **Rejected** - Manager rejected (returns to staff for revision)

---

## User Roles & Permissions

### 👨‍💼 STAFF (Operations Staff)
- **Can DO:** Create job orders, view their own jobs
- **Tab Access:** "Create Job Order" tab
- **Example Users:** Juan Carlo, Carla, Miguel, Andrea, Mark

### 👔 MANAGER
- **Can DO:** Review & approve/reject job orders, finalize approved orders, start jobs
- **Tab Access:** "Pending Jobs", "Ongoing Jobs", "Job History"
- **Special:** Can approve with remarks and finalize with password verification
- **Example User:** Manager account (if exists in your system)

### 🛡️ ADMIN / SUPERADMIN
- **Can DO:** Review & approve/reject job orders, finalize approved orders, start jobs
- **Tab Access:** All tabs (view Pending, Ongoing, History)
- **Special:** Same permissions as Manager, can bypass password for finalization
- **Example User:** Admin/Superadmin account

---

## Workflow Stages & Testing Steps

### STAGE 1: STAFF CREATES JOB ORDER
**User:** STAFF (Juan Carlo, Carla, Miguel, Andrea, Mark)  
**Status:** Pending → Initial State  
**Result:** System creates job order and notifies manager for review

#### Test Steps:
1. **Login as:** Staff user (e.g., "juan.carlo" or "carla")
2. **Navigate to:** Job Order page → **"Create Job Order"** tab
3. **Fill in form:**
   - **Customer Name:** (optional) "Maria Santos" or "Walk-in"
   - **Service Type:** Select any service (e.g., "Oil Change", "Brake Service")
   - **Assigned Mechanic:** Choose from list (e.g., "Paolo Reyes - Engine Specialist")
   - **Vehicle Plate:** "ABC-1234" or similar
   - **Vehicle Type:** "Sedan" or "SUV"
   - **Service Description:** "Regular maintenance oil change"
   - **Estimated Duration:** 60 minutes
4. **Click:** "🎯 Create Job Order" button
5. **Expected Response:**
   ```
   ✅ "Job order created successfully"
   Job Order Number: JO-2025-02-15-0001
   ```
6. **Database State:**
   - `job_orders` table: New row with `status = 'Pending'`
   - `created_at` = current timestamp

---

### STAGE 2: MANAGER REVIEWS & APPROVES
**User:** MANAGER or ADMIN  
**Status:** Pending → Reviewed  
**Actions:** Approve or Reject

#### Test Steps:

**A. Login as Manager/Admin:**
1. **Logout** from staff account
2. **Login as:** Manager or Admin user
3. **Navigate to:** Job Order page → **"Pending Jobs"** tab
4. **Find** the job order you just created (should be at top of list)

**B. Review Job Details:**
- Job Order Number: `JO-2025-02-15-0001` (or similar)
- Customer: "Maria Santos" or "Walk-in"
- Mechanic: "Paolo Reyes"
- Service: "Oil Change"
- Estimated Cost: ₱XXX.XX
- Status Badge: 🟡 **PENDING**

**C. APPROVE Action:**
1. **Click:** "✅ Approve" button in the job card
2. **Enter Remarks (optional):** "Approved for processing"
3. **Click:** "Confirm Approve"
4. **Expected Response:**
   ```
   ✅ "Job order approved. Staff cannot edit billing amounts."
   ```
5. **Database State:**
   - `status = 'Reviewed'` (CRITICAL: Must be 'Reviewed', NOT 'Approved')
   - `approved_by = [manager_id]`
   - `approved_at = NOW()`
   - `staff_editable = 0` (locked)

**D. REJECT Action (Alternative):**
1. **Click:** "❌ Reject" button instead
2. **Enter Rejection Reason:** "Mechanic overbooked, try again tomorrow"
3. **Click:** "Confirm Reject"
4. **Expected Response:**
   ```
   ✅ "Job order rejected and returned to staff."
   ```
5. **Database State:**
   - `status = 'Rejected'`
   - `rejected_by = [manager_id]`
   - `rejected_at = NOW()`

---

### STAGE 3: MANAGER FINALIZES (Ready for Execution)
**User:** MANAGER or ADMIN  
**Status:** Reviewed → Pending (second Pending stage)  
**Special:** Requires password verification (unless SuperAdmin)

#### Prerequisites:
- Job must have status = **"Reviewed"** (from Stage 2)
- Manager/Admin must be logged in

#### Test Steps:

**A. Locate Reviewed Job:**
1. **Look for:** "🔐 Reviewed Jobs - Final Approval Required" section
2. **Find** the job you approved (e.g., "JO-2025-02-15-0001")
3. **Check Status:** Should show 🟡 **REVIEWED** badge

**B. Finalize (Manager):**
1. **Click:** "🔓 Unlock & Finalize" button
2. **Enter Manager Password:** Your login password
3. **Click:** "Confirm Finalization"
4. **Expected Response:**
   ```
   ✅ "Job order finalized and ready for execution"
   ```
5. **Database State:**
   - `status = 'Pending'` (second Pending - now ready for execution)
   - `finalized_by = [manager_id]`
   - `finalized_at = NOW()`
   - `started_at = NOW()` (execution timer starts)

**C. Finalize (SuperAdmin):**
- Same steps, but NO password required (SuperAdmin bypass)
- System will accept finalization immediately

**D. What Happens if Status is Wrong:**
- If you see error: `"Job order must be in Reviewed status to finalize"`
- It means the job didn't transition to 'Reviewed' properly in Stage 2
- **Fix:** Check database: `SELECT status FROM job_orders WHERE id = X`

---

### STAGE 4: START JOB (Optional)
**User:** MANAGER or ADMIN  
**Status:** Pending → In Progress  
**Note:** Jobs can auto-start or be manually started

#### Test Steps:
1. **Navigate to:** "Ongoing Jobs" tab
2. **Find** the finalized job (status = 🟡 **PENDING**)
3. **Click:** "▶️ Start Job" button
4. **Expected Response:**
   ```
   ✅ "Job order started"
   ```
5. **Database State:**
   - `status = 'In Progress'` (blue badge 🔵)
   - `started_at = NOW()`

---

### STAGE 5: COMPLETE JOB (Mechanic)
**User:** STAFF or MANAGER or ADMIN  
**Status:** In Progress → Completed  
**Actions:** Deduct parts from inventory, lock billing

#### Test Steps:

**A. In Progress Job:**
1. **Navigate to:** "Ongoing Jobs" tab
2. **Find** the job with status 🔵 **IN PROGRESS**
3. **Click:** "⚙️ Complete" or similar button

**B. Complete Job Form:**
1. **Parts Used (optional):**
   - Select parts from dropdown
   - Enter quantity used
   - (System auto-calculates from inventory)
2. **Actual Labor Hours:** Enter hours spent (e.g., 1.5 hours)
3. **Click:** "✅ Mark Completed"

**C. Expected Response:**
   ```
   ✅ "Job order completed. Billing locked."
   - Parts Cost: ₱XXX.XX
   - Labor Cost: ₱YYY.YY
   - Total Cost: ₱ZZZ.ZZ
   ```

**D. Database State:**
   - `status = 'Completed'` (green badge ✅)
   - `completed_at = NOW()`
   - `actual_parts_cost = [calculated]`
   - `actual_labor_cost = [calculated]`
   - `total_cost = [parts + labor]`
   - `staff_editable = 0` (locked)
   - `billing_locked = 1` (cannot be modified)
   - Station inventory deducted for used parts

---

## Quick Test Scenario (5 Minutes)

**Goal:** Complete one full cycle from creation to completion

### Timeline:
```
1. LOGIN AS STAFF (1 min)
   └─ Create Job Order
      └─ Status: Pending ✓

2. LOGIN AS MANAGER (2 min)
   └─ Approve Job
      └─ Status: Reviewed ✓
   └─ Finalize Job
      └─ Status: Pending (ready) ✓

3. START JOB (30 sec)
   └─ Status: In Progress ✓

4. COMPLETE JOB (1 min)
   └─ Status: Completed ✓
   └─ Billing Locked ✓
```

---

## Status Progression (Must Match Exactly)

```
┌─────────────┐
│  CREATED    │  Status = "Pending"
│  (by staff) │  ✅ Staff can see/edit
└──────┬──────┘
       │
       │ Manager Approves (Stage 2)
       ↓
┌─────────────────┐
│  REVIEWED       │  Status = "Reviewed"
│  (by manager)   │  ❌ Staff CANNOT edit (locked)
└──────┬──────────┘
       │
       │ Manager Finalizes (Stage 3)
       ↓
┌──────────────────┐
│  PENDING (READY) │  Status = "Pending"
│  (finalized)     │  Ready for execution
└──────┬───────────┘
       │
       │ Start Job (Stage 4)
       ↓
┌──────────────────┐
│  IN PROGRESS     │  Status = "In Progress"
│  (mechanic)      │  Job is being worked on
└──────┬───────────┘
       │
       │ Complete Job (Stage 5)
       ↓
┌──────────────────┐
│  COMPLETED       │  Status = "Completed"
│  (finalized)     │  Billing LOCKED
└──────────────────┘
```

---

## Critical Values (After Each Stage)

| Stage | Action | Must Be | Status | Locked | Editable |
|-------|--------|---------|--------|--------|----------|
| 1 | Create | Database inserted | `Pending` | No | Yes |
| 2 | Approve | Manager reviewed | `Reviewed` | No | **No** |
| 3 | Finalize | Ready to start | `Pending` | No | No |
| 4 | Start | Execution begins | `In Progress` | No | No |
| 5 | Complete | Finished & billed | `Completed` | **Yes** | **No** |

---

## Common Issues & Fixes

### Issue: "Job order must be in Reviewed status to finalize"
**Cause:** Job status is not 'Reviewed'  
**Fix:** Go back to Stage 2, check Manager Approve action  
**Verify:** `SELECT status FROM job_orders WHERE id = X` (should = 'Reviewed')

### Issue: "Manager privileges required"
**Cause:** Logged in as wrong role  
**Fix:** Logout and login as Manager or Admin user

### Issue: Status stuck at "Pending"
**Cause:** Approval didn't transition to 'Reviewed'  
**Fix:** Check approval response message in browser console  
**Database:** Check if `approved_at` timestamp exists

### Issue: Can't find job in Pending Jobs tab
**Cause:** Job was created with staff role, viewing as different manager  
**Fix:** Make sure both users are at same station (use `user_station_id()`)

---

## Testing Checklist

- [ ] **Stage 1:** Staff creates job → Status = "Pending" ✅
- [ ] **Stage 2:** Manager approves → Status = "Reviewed" ✅
- [ ] **Stage 2:** Manager rejects → Status = "Rejected" ✅
- [ ] **Stage 3:** Manager finalizes → Status = "Pending" (ready) ✅
- [ ] **Stage 3:** SuperAdmin finalizes without password ✅
- [ ] **Stage 4:** Start job → Status = "In Progress" ✅
- [ ] **Stage 5:** Complete job → Status = "Completed" ✅
- [ ] **Billing:** Total cost locked after completion ✅
- [ ] **Inventory:** Parts deducted from stock ✅
- [ ] **Permissions:** Staff cannot approve/finalize ✅
- [ ] **Permissions:** Non-managers cannot review ✅

---

## Available Test Users

**Staff:**
- juan.carlo
- carla
- miguel
- andrea
- mark

**Mechanics (auto-seeded):**
- Paolo Reyes (Engine Specialist)
- Liza Cruz (Brake & Suspension)
- Marco Dizon (Electrical & Diagnostics)
- Ana Santos (Tire & Vulcanizing)

**Service Categories (7 types):**
- Oil Change
- Tire Rotation
- Car Wash
- Brake Service
- Engine Tune-up
- Battery Check
- General Service

---

## Database Tables Reference

**Main Table:** `job_orders`
- `id` - Unique ID
- `job_order_number` - JO-YYYY-MM-DD-NNNN format
- `status` - Pending | Reviewed | In Progress | Completed | Rejected
- `assigned_mechanic_id` - Foreign key to mechanics
- `customer_id` - Foreign key to customers
- `created_at` - Staff creation time
- `approved_at` - Manager approval time
- `finalized_at` - Manager finalization time
- `started_at` - Job start time
- `completed_at` - Job completion time
- `staff_editable` - 0 = locked after approval
- `billing_locked` - 1 = locked after completion

**Parts Table:** `job_order_parts`
- Records parts used during job completion
- Auto-deducted from `station_inventory`

---

## Success Indicators

✅ **Job Created:** You see job number like "JO-2025-02-15-0001"  
✅ **Reviewed:** Status badge changes from 🟡 PENDING to 🟡 REVIEWED  
✅ **Finalized:** Status badge changes to 🟡 PENDING (ready), job moves to "Ongoing Jobs"  
✅ **In Progress:** Status badge changes to 🔵 IN PROGRESS  
✅ **Completed:** Status badge changes to ✅ COMPLETED, billing shows total cost  

---

## Quick Reference: API Endpoints

All actions POST to `/public/joborder.php`:

```bash
# Create
action=create_job_order
POST fields: customer_name, service_category_id, assigned_mechanic_id, etc.

# Approve
action=manager_review_approve
POST fields: job_id, remarks (optional)

# Reject
action=manager_review_reject
POST fields: job_id, remarks (optional)

# Finalize
action=manager_final_approval
POST fields: job_id, password

# Start
action=start_job_order
POST fields: job_id

# Complete
action=complete_job_order
POST fields: job_id, parts_used (JSON), actual_labor_hours
```

---

**Last Updated:** February 15, 2025  
**System:** Petron POS/Job Order System  
**Version:** 4.0 (Fixed Workflow)
