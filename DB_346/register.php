<?php
require __DIR__ . '/config_mysqli.php';
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<!doctype html>
<html lang="th">
<head>
  <meta charset="utf-8">
  <title>ลงทะเบียน</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <div class="container py-5">
    <div class="mx-auto" style="max-width:520px;">
      <div class="card shadow-sm">
        <div class="card-body p-4">
          <h1 class="h4 mb-3">สร้างบัญชีใหม่</h1>

          <?php if ($flash): ?>
            <div class="alert alert-<?= htmlspecialchars($flash['type']) ?>"><?= htmlspecialchars($flash['msg']) ?></div>
          <?php endif; ?>

          <form method="post" action="register_process.php" autocomplete="on">
            <div class="mb-3">
              <label class="form-label">ชื่อที่แสดง</label>
              <input type="text" name="display_name" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label">อีเมล</label>
              <input type="email" name="email" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label">รหัสผ่าน (อย่างน้อย 8 ตัวอักษร)</label>
              <input type="password" name="password" class="form-control" minlength="8" required>
            </div>
            <button class="btn btn-success w-100">ลงทะเบียน</button>
          </form>

          <hr class="my-4">
          <p class="mb-0 text-center">
            มีบัญชีอยู่แล้ว? <a href="login.php">เข้าสู่ระบบ</a>
          </p>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
