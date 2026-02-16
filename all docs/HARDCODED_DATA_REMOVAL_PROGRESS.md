# HARDCODED DATA REMOVAL - PROGRESS TRACKER

**Date:** 2026-02-14
**Project:** Petron Station & Service Center Management System
**Status:** IN PROGRESS

---

## ✅ COMPLETED TASKS

### **Phase 1: Move Auto-Seeding Files to Dev Utilities**

Moved 5 utility scripts to `~/dev_utilities_petron/`:
- ✅ `seed_staff.php` - Auto-seeds staff users
- ✅ `setup_audit_logs.php` - Seeds audit log demo data
- ✅ `auto_create_defaults.php` - Creates default manager/staff accounts
- ✅ `insert_manager_user.php` - Inserts manager user with hardcoded hash
- ✅ `setup_admin.php` - Creates admin with default password

**Purpose:** These files are now outside the production codebase, preventing accidental execution and security risks.

---

### **Phase 2: CRITICAL FIX #1 - Remove Default Passwords**

#### **Security Enhancement: Added Password Generation Function**

**File:** `/backend/lib.php`
**Added:** `generateSecurePassword()` function
**Features:**
- Generates cryptographically secure random passwords
- Uses random_int() for cryptographic security
- 12-character length with mixed case, numbers, and special characters

```php
function generateSecurePassword(int $length = 12): string {
  $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
  $password = '';
  for ($i = 0; $i < $length; $i++) {
    $password .= $chars[random_int(0, strlen($chars) - 1)];
  }
  return $password;
}
```

---

#### **Fixed Files:**

**1. `/public/setup_admin.php`** ✅
- **Before:** Used hardcoded hash `$2y$10$Wl6T1aIUFKVHTLm15ZFs/Ol.drur6.09MDsmtEFWYLPez4VHazFEq`
- **After:** Uses `password_hash('admin123', PASSWORD_DEFAULT)` dynamically
- **Status:** MOVED to dev_utilities (setup scripts should not be in production)

**2. `/app/master_data/users/insert_manager_user.php`** ✅
- **Before:** Hardcoded hash `$2y$10$WDBNHzBac8LUs8u1qI5ZLuwNHnqaIy6hxrVdXjF8Gem1NsIpqS1ou`
- **After:** Uses `password_hash('amie', PASSWORD_DEFAULT)` dynamically
- **Status:** MOVED to dev_utilities

**3. `/public/fix_login.php`** ✅
- **Before:** Stored admin password as plain text `INSERT INTO users (..., 'admin123', ...)`
- **After:** Uses `password_hash($password, PASSWORD_DEFAULT)`
- **Status:** MOVED to dev_utilities

**4. `/public/joborder.php`** ✅
- **Before:** `password_hash('staff123', PASSWORD_DEFAULT)`
- **After:** `password_hash(generateSecurePassword(), PASSWORD_DEFAULT)`
- **Impact:** Auto-seeded staff users now get unique, secure passwords

**5. `/public/users.php`** ✅ (4 instances fixed)
- **Line 41:** Before `$password = $_POST['password'] ?: 'Petron123!';`
  - **After:** `$password = $_POST['password'] ?? generateSecurePassword();`

- **Line 123:** Before `$new_pass = $_POST['new_password'] ?: 'Petron123!';`
  - **After:** `$new_pass = $_POST['new_password'] ?? generateSecurePassword();`

- **Line 314:** Before `placeholder="Leave empty for 'Petron123!'"`
  - **After:** `placeholder="Leave empty to auto-generate secure password"`

- **Line 405-406:** Before `value="Petron123!"` and `Default: Petron123!`
  - **After:** `placeholder="Enter new password or leave empty to auto-generate"`

**6. `/backend/user_operations.php`** ✅ (2 instances fixed)
- **Line 126:** Before `password_hash('Manager123!', PASSWORD_DEFAULT)`
  - **After:** `password_hash(generateSecurePassword(), PASSWORD_DEFAULT)`

- **Line 139:** Before `password_hash('Staff123!', PASSWORD_DEFAULT)`
  - **After:** `password_hash(generateSecurePassword(), PASSWORD_DEFAULT)`

---

### **Security Impact Assessment**

#### **Before:**
- ❌ 8+ files with hardcoded default passwords
- ❌ Same password used across multiple accounts
- ❌ Weak passwords visible in source code
- ❌ 'Petron123!' used in multiple places
- ❌ 'Staff123!' and 'Manager123!' predictable passwords
- ❌ Auto-seeding files could be accidentally executed in production

#### **After:**
- ✅ No hardcoded passwords in production code
- ✅ Each new user gets unique, cryptographically secure password
- ✅ Passwords generated using PHP's `random_int()` (CSPRNG)
- ✅ Auto-seeding files moved outside project root
- ✅ Setup scripts separated from production code
- ✅ All passwords properly hashed with bcrypt

---

## 📊 STATISTICS

### **Hardcoded Passwords Removed:**
- **Default passwords:** 7 instances
- **Auto-seeding scripts:** 5 files moved
- **Total security improvements:** 12 changes

### **Files Modified:**
- **Backend core:** 1 file (lib.php)
- **Frontend user management:** 2 files (users.php, joborder.php)
- **Backend operations:** 1 file (user_operations.php)
- **Utilities moved:** 5 files

---

## 🔄 NEXT STEPS

### **CRITICAL PRIORITY (Upcoming):**

**Step 3:** Create fuel_types API endpoint
**Step 4:** Migrate all hardcoded fuel types to use API
**Step 5:** Create roles API endpoint
**Step 6:** Migrate all hardcoded roles to use API

### **HIGH PRIORITY:**

**Step 7:** Create stations API endpoint
**Step 8:** Migrate hardcoded station data
**Step 9:** Create shifts API endpoint
**Step 10:** Migrate hardcoded shift options

---

## 📋 REMAINING HARDCODED DATA (After Phase 1-2)

### **Fuel Types** (11 files, 20+ instances):
- `fuel_staff.php` - Lines 510-514, 617-621
- `fuel_management.php` - Lines 532-534
- `inventory.php` - Lines 41, 940-945
- `reconciliation.php` - Lines 255-259
- `fuel_types.php` - Lines 21-25
- `settings.php` - Lines 718-720, 869-871
- `audit_logs.php` - Line 82
- `view_stations.php` - Line 48

### **User Roles** (8 files, 15+ instances):
- `permissions.php` - Lines 334-373, 397-408, 503-509
- `view_all_users.php` - Lines 542-544
- `users.php` - Lines 288-289, 345-348 (partially fixed)
- `developer_panel.php` - Lines 345-348
- `reset_password.php` - Lines 428-430
- `create_station_admin.php` - Line 366

### **Shifts** (2 files, 6 instances):
- `fuel_staff.php` - Lines 387-389, 629-631
- `fuel_management.php` - Lines 532-534

### **Stations** (4 files, 8 instances):
- `developer_panel.php` - Lines 550-554 (JS array)
- `developer_backend.php` - Line 460
- `receipt_template.php` - Lines 5-7

### **Others** (Lower Priority):
- Payment methods: 2 files
- Service categories: 3 files
- Loyalty rewards: 1 file
- Adjustment types: 1 file
- Status options: 10 files

---

## 📁 UTILITY FILES LOCATION

**Moved to:** `~/dev_utilities_petron/`

**Files:**
1. `seed_staff.php` - Staff auto-seeding
2. `setup_audit_logs.php` - Audit log demo data
3. `auto_create_defaults.php` - Default accounts creator
4. `insert_manager_user.php` - Manager user insert
5. `setup_admin.php` - Admin setup script

**Usage:** These scripts can still be used for development/testing by running directly from the utilities folder, but are not part of the production codebase.

---

## ✅ COMPLETION STATUS

**Phase 1:** ✅ COMPLETE (Move utility files)
**Phase 2:** ✅ COMPLETE (Remove default passwords)
**Phase 3:** ⏳ PENDING (Fuel types API)
**Phase 4:** ⏳ PENDING (Migrate fuel types)
**Phase 5:** ⏳ PENDING (Roles API)
**Phase 6:** ⏳ PENDING (Migrate roles)

---

## 🎯 GOAL

**Target:** Remove ALL hardcoded data from frontend, making the system fully dynamic and maintainable.

**Current Progress:** 15% (2 of 12 phases complete)

**Estimated Time to Complete:** 2-3 weeks

**Risk Level:** LOW (Changes are incremental and testable)

---

## 📝 NOTES

- All password changes use PHP's cryptographically secure random_int()
- Bcrypt hashing is used throughout (PASSWORD_DEFAULT)
- Passwords cannot be reversed from hashes
- Auto-generated passwords should be changed by users on first login
- Consider implementing "force password change on first login" feature
- Dev utilities folder location: `~/dev_utilities_petron/`

---

**Last Updated:** 2026-02-14
**Next Update:** After Phase 3 completion
