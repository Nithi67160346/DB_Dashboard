<?php
require __DIR__ . '/config_mysqli.php';

// อ่าน flash message ถ้ามี
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';

  try {
    $stmt = $mysqli->prepare('SELECT id, display_name, email, password_hash FROM users WHERE email = ? LIMIT 1');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $res = $stmt->get_result();
    $user = $res->fetch_assoc();
    $stmt->close();

    if ($user && password_verify($password, $user['password_hash'])) {
      $_SESSION['user_id'] = (int)$user['id'];
      $_SESSION['display_name'] = $user['display_name'];
      $_SESSION['email'] = $user['email'];
      header('Location: dashboard.php'); exit;
    } else {
      $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'อีเมลหรือรหัสผ่านไม่ถูกต้อง'];
      header('Location: login.php'); exit;
    }
  } catch (Throwable $e) {
    $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'เกิดข้อผิดพลาดในการเข้าสู่ระบบ'];
    header('Location: login.php'); exit;
  }
}
?>
<!doctype html>
<html lang="th">
<head>
  <meta charset="utf-8">
  <title>เข้าสู่ระบบ</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <div class="container py-5">
    <div class="mx-auto" style="max-width:420px;">
      <div class="card shadow-sm">
        <div class="card-body p-4">
          <h1 class="h4 mb-3">เข้าสู่ระบบ</h1>

          <?php if ($flash): ?>
            <div class="alert alert-<?= htmlspecialchars($flash['type']) ?>"><?= htmlspecialchars($flash['msg']) ?></div>
          <?php endif; ?>

          <form method="post" action="login.php" autocomplete="on">
            <div class="mb-3">
              <label class="form-label">อีเมล</label>
              <input type="email" name="email" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label">รหัสผ่าน</label>
              <input type="password" name="password" class="form-control" required>
            </div>
            <button class="btn btn-primary w-100">เข้าสู่ระบบ</button>
          </form>

          <hr class="my-4">
          <p class="mb-0 text-center">
            ยังไม่มีบัญชี? <a href="register.php">ลงทะเบียน</a>
          </p>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
