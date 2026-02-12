<?php
require_once __DIR__ . '/../public/db_connect.php';

$station_id = 1; // <-- change this to your station id

$staff = [
  ['username' => 'staff1', 'name' => 'Staff One', 'password' => 'staff123'],
  ['username' => 'staff2', 'name' => 'Staff Two', 'password' => 'staff123'],
  ['username' => 'staff3', 'name' => 'Staff Three', 'password' => 'staff123'],
];

foreach ($staff as $s) {
  $hash = password_hash($s['password'], PASSWORD_DEFAULT);

  // prevent duplicates
  $check = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username=?");
  $check->execute([$s['username']]);
  if ((int)$check->fetchColumn() > 0) {
    echo "SKIP (exists): {$s['username']}<br>";
    continue;
  }

  $ins = $pdo->prepare("
    INSERT INTO users (username, name, role, station_id, status, password)
    VALUES (?, ?, 'staff', ?, 'active', ?)
  ");
  $ins->execute([$s['username'], $s['name'], $station_id, $hash]);

  echo "INSERTED: {$s['username']} / password={$s['password']} / station_id={$station_id}<br>";
}

echo "<hr>Done. DELETE this file after.";
