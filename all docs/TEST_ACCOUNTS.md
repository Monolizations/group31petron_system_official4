# Updated Test Accounts for Petron POS System
# =========================================
# Last Updated: 2026-02-15 17:55
# Status: ✅ All passwords verified and working

## SUPERADMIN ACCOUNT
# =========================================

### Test Superadmin ⭐
Username: testadmin
Password: test123
Email: testadmin@petron.com
Role: superadmin
Station: All stations
Status: active
User ID: 20
Password Hash: $2y$10$OrPSKViT3mVG6KggX88jvuQucHju9bLVigcmsbOx8R0BCnPP2RsUq
✅ Password Verified: YES

## STATION 226 ACCOUNTS
# =========================================

### Test Manager ⭐
Username: testmanager
Password: manager123
Email: testmanager@petron.com
Role: manager
Station: 226
Status: active
User ID: 21
Password Hash: $2y$10$M2CzIZfbEEkwZ6g42wM5J.THhNUal3UNPWWaZ/LKh1FDw9KMGld.i
✅ Password Verified: YES

### Test Staff ⭐
Username: teststaff
Password: staff123
Email: teststaff@petron.com
Role: operations_staff
Station: 226
Status: active
User ID: 22
Password Hash: $2y$10$Z7oJZY.261b5.yWik00hze0QZU4x8.0naXXoNPC.LJHdatGAtpIzS
✅ Password Verified: YES

## LOGIN INSTRUCTIONS
# =========================================

1. Go to: /public/login.php
2. Enter username and password from above
3. Click Login

## TESTING WORKFLOWS
# =========================================

### 1. Supplier Management (Superadmin Only)
Login: testadmin / test123
URL: /public/settings.php?section=suppliers
OR Navigate: Settings → Suppliers tab
- Verify "Petron Supplier" is default
- Add/edit/delete suppliers
- Change default supplier

### 2. Merchandise Receiving (Staff)
Login: teststaff / staff123
URL: /public/receiving_staff.php
- Verify supplier auto-fills to "Petron Supplier (Default)"
- Override supplier if needed
- Submit receiving entry
- Check inventory updates

### 3. Inventory Management
Login: testadmin / test123
URL: /public/inventory.php
- View received items history
- View delivery history with supplier info
- Manage stock levels

### 4. POS Transactions
Login: teststaff / staff123
URL: /public/pos.php
- Create transactions (pending approval)
- View items from database
- Check inventory availability

Login: testadmin / test123
- Approve/reject transactions
- Unlock completed transactions

## PASSWORD VERIFICATION
# =========================================

All passwords have been verified using PHP's password_verify():

✅ testadmin / test123: PASS
✅ testmanager / manager123: PASS
✅ teststaff / staff123: PASS

## SQL TO VERIFY ACCOUNTS
# =========================================

```sql
-- List all test accounts
SELECT id, username, email, role, name, station_id, status, created_at 
FROM users 
WHERE username IN ('testadmin', 'testmanager', 'teststaff')
ORDER BY role;

-- Verify password hash for testadmin
SELECT username, password 
FROM users WHERE username = 'testadmin';
-- Expected: $2y$10$OrPSKViT3mVG6KggX88jvuQucHju9bLVigcmsbOx8R0BCnPP2RsUq

-- Test password verification (PHP)
<?php
$hash = '$2y$10$OrPSKViT3mVG6KggX88jvuQucHju9bLVigcmsbOx8R0BCnPP2RsUq';
echo password_verify('test123', $hash) ? 'PASS' : 'FAIL';
?>
```

## ROLE PERMISSIONS
# =========================================

### Superadmin (testadmin)
- Access to all stations
- Full system configuration
- Settings → Suppliers
- Settings → Service Rates
- Settings → Fuel Calibration
- Can approve/reject all transactions
- Full inventory management

### Manager (testmanager)
- Access to station 226 only
- Can approve pump readings
- Can manage job orders
- Can view inventory
- Limited settings access

### Staff (teststaff)
- Access to station 226 only
- Can create transactions (pending)
- Can encode received items
- Can record pump readings
- Read-only settings

## TROUBLESHOOTING
# =========================================

### If login fails:
1. Check username spelling (case-sensitive)
2. Check password spelling
3. Verify account is active in database:
   ```sql
   SELECT username, status FROM users WHERE username = 'testadmin';
   ```
4. Clear browser cache and cookies
5. Try incognito/private mode

### If password hash doesn't verify:
Run this PHP script:
```php
<?php
$hash = '$2y$10$OrPSKViT3mVG6KggX88jvuQucHju9bLVigcmsbOx8R0BCnPP2RsUq';
$password = 'test123';
echo password_verify($password, $hash) ? 'PASS' : 'FAIL';
?>
```

## SECURITY NOTES
# =========================================

⚠️  IMPORTANT:
- These are TEST accounts for development
- Remove before production deployment
- Change all passwords before production
- Test passwords are simple (test123, manager123, staff123)

## PRODUCTION CLEANUP
# =========================================

To remove test accounts before production:

```sql
-- Delete test accounts
DELETE FROM users WHERE username IN ('testadmin', 'testmanager', 'teststaff');

-- OR disable them
UPDATE users SET status = 'inactive' WHERE username IN ('testadmin', 'testmanager', 'teststaff');
```

---
End of Updated Test Accounts Documentation
