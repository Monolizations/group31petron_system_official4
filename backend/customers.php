<?php
require_once __DIR__ . '/lib.php';
require_once __DIR__ . '/../public/db_connect.php';
require_login();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_GET['action'] ?? '';

if($method === 'GET'){
  if ($action === 'get' && !empty($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($customer) {
      json_response(['success'=>true, 'customer'=>$customer]);
    } else {
      json_response(['success'=>false, 'error'=>'Customer not found'], 404);
    }
  } else {
    $stmt = $pdo->query("SELECT * FROM customers ORDER BY name ASC");
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    json_response(['ok'=>true, 'data'=>['customers'=>$customers]]);
  }
}

if($method === 'POST'){
  // Check role - only manager, admin, superadmin can create/update customers
  $u = current_user();
  $role_key = function($r) {
    $r = strtolower(trim((string)$r));
    if(in_array($r, ['superadmin','super admin','super_admin'])) return 'superadmin';
    if(in_array($r, ['admin','station admin','station_admin'])) return 'admin';
    if(in_array($r, ['manager','supervisor','manager / supervisor','manager/supervisor','supervisor/manager'])) return 'manager';
    return 'staff';
  };
  $role = $role_key($u['role'] ?? 'staff');
  if(!in_array($role, ['manager', 'admin', 'superadmin'])){
    json_response(['ok'=>false,'error'=>'Unauthorized. Only Manager, Admin, or Super Admin can manage customers.'], 403);
  }

  $id = $_POST['id'] ?? '';
  $name = trim((string)($_POST['name'] ?? ''));
  $contact = trim((string)($_POST['contact_person'] ?? ''));
  $phone = trim((string)($_POST['phone'] ?? ''));
  $email = trim((string)($_POST['email'] ?? ''));
  $address = trim((string)($_POST['address'] ?? ''));
  $type = ($_POST['type'] ?? 'cash') === 'credit' ? 'credit' : 'cash';
  $credit_limit = (float)($_POST['credit_limit'] ?? 0);
  $status = in_array(($_POST['status'] ?? 'active'), ['active','suspended','inactive']) ? $_POST['status'] : 'active';
  $merchandise_type = in_array(($_POST['merchandise_type'] ?? ''), ['oil_lube_grease', 'car_accessories', 'oil_fuel_filter', 'others', 'multiple']) ? $_POST['merchandise_type'] : null;

  // basic validation
  if($name === ''){
    json_response(['ok'=>false,'error'=>'Customer name is required'], 400);
  }
  if($type !== 'credit'){
    $credit_limit = 0;
  }

  try {
    if($id){
      // Update
      $stmt = $pdo->prepare("UPDATE customers SET name=?, contact_person=?, phone=?, email=?, address=?, type=?, credit_limit=?, merchandise_type=?, status=? WHERE id=?");
      $stmt->execute([$name, $contact, $phone, $email, $address, $type, $credit_limit, $merchandise_type, $status, $id]);
    } else {
      // Insert
      $stmt = $pdo->prepare("INSERT INTO customers (name, contact_person, phone, email, address, type, credit_limit, merchandise_type, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
      $stmt->execute([$name, $contact, $phone, $email, $address, $type, $credit_limit, $merchandise_type, $status]);
      $id = $pdo->lastInsertId();
    }
    json_response(['ok'=>true,'id'=>$id]);
  } catch (Exception $e) {
    json_response(['ok'=>false,'error'=>$e->getMessage()], 500);
  }
}

if($method === 'DELETE'){
  // Check role - only admin and superadmin can delete customers
  $u = current_user();
  $role_key = function($r) {
    $r = strtolower(trim((string)$r));
    if(in_array($r, ['superadmin','super admin','super_admin'])) return 'superadmin';
    if(in_array($r, ['admin','station admin','station_admin'])) return 'admin';
    return 'staff';
  };
  $role = $role_key($u['role'] ?? 'staff');
  if(!in_array($role, ['admin', 'superadmin'])){
    json_response(['ok'=>false,'error'=>'Unauthorized. Only Admin or Super Admin can delete customers.'], 403);
  }

  $id = $_GET['id'] ?? '';
  if(!$id) json_response(['ok'=>false,'error'=>'Missing id'], 400);
  try {
    $stmt = $pdo->prepare("DELETE FROM customers WHERE id=?");
    $stmt->execute([$id]);
    json_response(['ok'=>true]);
  } catch (Exception $e) {
    json_response(['ok'=>false,'error'=>$e->getMessage()], 500);
  }
}

json_response(['ok'=>false,'error'=>'Method not allowed'], 405);
?>