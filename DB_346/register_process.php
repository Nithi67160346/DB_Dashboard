<?php
require __DIR__ . '/config_mysqli.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: register.php'); exit;
}

$display_name = trim($_POST['display_name'] ?? '');
$email        = trim($_POST['email'] ?? '');
$password     = $_POST['password'] ?? '';

if ($display_name === '' || $email === '' || $password === '') {
  $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'กรุณากรอกข้อมูลให้ครบถ้วน'];
  header('Location: register.php'); exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'อีเมลไม่ถูกต้อง'];
  header('Location: register.php'); exit;
}
if (strlen($password) < 8) {
  $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร'];
  header('Location: register.php'); exit;
}

try {
  // ตรวจอีเมลซ้ำ
  $stmt = $mysqli->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
  $stmt->bind_param('s', $email);
  $stmt->execute();
  $exists = $stmt->get_result()->fetch_assoc();
  $stmt->close();

  if ($exists) {
    $_SESSION['flash'] = ['type' => 'warning', 'msg' => 'อีเมลนี้ถูกใช้แล้ว'];
    header('Location: register.php'); exit;
  }

  $hash = password_hash($password, PASSWORD_DEFAULT);
  $stmt = $mysqli->prepare('INSERT INTO users (email, display_name, password_hash) VALUES (?, ?, ?)');
  $stmt->bind_param('sss', $email, $display_name, $hash);
  $stmt->execute();
  $stmt->close();

  $_SESSION['flash'] = ['type' => 'success', 'msg' => 'สมัครสมาชิกสำเร็จ! กรุณาเข้าสู่ระบบ'];
  header('Location: login.php'); exit;

} catch (Throwable $e) {
  $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'สมัครสมาชิกไม่สำเร็จ กรุณาลองใหม่'];
  header('Location: register.php'); exit;
}
