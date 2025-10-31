<?php
// dashboard.php
// Retail DW Dashboard (Chart.js + Bootstrap) + Session Guard + Logout (clean layout)
require __DIR__ . '/config_mysqli.php';

// ถ้าไม่ได้ล็อกอิน ให้เด้งไปหน้า login
if (empty($_SESSION['user_id'])) {
  header('Location: login.php'); exit;
}
$display_name = htmlspecialchars($_SESSION['display_name'] ?? 'ผู้ใช้');

// ---------------- Helper ----------------
function fetch_all($mysqli, $sql) {
  $res = $mysqli->query($sql);
  if (!$res) { return []; }
  $rows = [];
  while ($row = $res->fetch_assoc()) { $rows[] = $row; }
  $res->free();
  return $rows;
}
function nf($n) { return number_format((float)$n, 2); }

// ---------------- Queries ----------------
$monthly      = fetch_all($mysqli, "SELECT ym, net_sales FROM v_monthly_sales");
$category     = fetch_all($mysqli, "SELECT category, net_sales FROM v_sales_by_category");
$region       = fetch_all($mysqli, "SELECT region, net_sales FROM v_sales_by_region");
$topProducts  = fetch_all($mysqli, "SELECT product_name, qty_sold, net_sales FROM v_top_products");
$payment      = fetch_all($mysqli, "SELECT payment_method, net_sales FROM v_payment_share");
$hourly       = fetch_all($mysqli, "SELECT hour_of_day, net_sales FROM v_hourly_sales");
$newReturning = fetch_all($mysqli, "SELECT date_key, new_customer_sales, returning_sales FROM v_new_vs_returning ORDER BY date_key");
$kpis = fetch_all($mysqli, "
  SELECT
    (SELECT SUM(net_amount) FROM fact_sales WHERE date_key >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)) AS sales_30d,
    (SELECT SUM(quantity)   FROM fact_sales WHERE date_key >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)) AS qty_30d,
    (SELECT COUNT(DISTINCT customer_id) FROM fact_sales WHERE date_key >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)) AS buyers_30d
");
$kpi = $kpis ? $kpis[0] : ['sales_30d'=>0,'qty_30d'=>0,'buyers_30d'=>0];
?>
<!doctype html>
<html lang="th">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Retail DW — Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root{
      --bg:#f8fafc; --text:#0f172a; --muted:#475569; --muted-2:#64748b;
      --card:#ffffff; --card-border:#e2e8f0; --accent:#0d6efd; --grid:#e5e7eb;
      --ring:#cbd5e1;
    }
    html, body { height: 100%; }
    body{ background:var(--bg); color:var(--text); font-family: 'Inter', system-ui, -apple-system, Segoe UI, Roboto, "Helvetica Neue", Arial, "Noto Sans", "Apple Color Emoji", "Segoe UI Emoji"; }
    .navbar{ background:#fff; border-bottom:1px solid var(--card-border); }
    .navbar .navbar-brand{ color:var(--text); font-weight: 700; letter-spacing:.2px; }
    .navbar .nav-link{ color:var(--muted); }
    .navbar .nav-link.active{ color:var(--text); font-weight:600; }

    .page-title { font-size: clamp(1.4rem, 2.4vw, 1.8rem); font-weight: 700; letter-spacing:.2px; }
    .page-sub { color: var(--muted-2); }

    .card{ background:var(--card); border:1px solid var(--card-border); border-radius:1rem; box-shadow: 0 1px 2px rgba(2,6,23,.04); }
    .card h5{ color:var(--text); font-weight:600; }
    .kpi{ font-size: clamp(1.25rem, 2vw, 1.6rem); font-weight:700; color:var(--text); }
    .kpi-sub{ color:var(--muted); font-size:.9rem; }

    .section{ margin-top: .5rem; margin-bottom: 1.5rem; }
    .section .section-title{ font-weight: 700; font-size: 1.05rem; color: var(--text); }
    .section .section-desc{ color: var(--muted-2); font-size: .95rem; }

    .divider{ height:1px; background: var(--card-border); margin: .75rem 0 1rem; }

    canvas{ max-height:360px; }

    /* Empty state */
    .empty { color: var(--muted-2); font-size: .95rem; }
    .empty .badge { background: #eef2ff; color:#3730a3; }

    /* Improve focus states for accessibility */
    :focus-visible { outline: 3px solid var(--ring); outline-offset: 2px; border-radius: .5rem; }
  </style>
</head>
<body class="p-0">
  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg sticky-top">
    <div class="container-xl px-3 px-md-4">
      <a class="navbar-brand" href="dashboard.php">Retail DW</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="nav">
        <ul class="navbar-nav me-auto">
          <li class="nav-item"><a class="nav-link active" href="dashboard.php">Dashboard</a></li>
        </ul>
        <div class="d-flex align-items-center gap-3">
          <span class="text-secondary small">สวัสดี, <?= $display_name ?></span>
          <form action="logout.php" method="post" class="d-inline">
            <button class="btn btn-outline-dark btn-sm">ออกจากระบบ</button>
          </form>
        </div>
      </div>
    </div>
  </nav>

  <!-- Main -->
  <main class="py-3 py-md-4">
    <div class="container-xl">
      <div class="d-flex align-items-start justify-content-between flex-wrap gap-2 mb-3">
        <div>
          <div class="page-title">ยอดขาย (Retail DW) — Dashboard</div>
          <div class="page-sub">แหล่งข้อมูล: MySQL (mysqli)</div>
        </div>
      </div>

      <!-- KPI row -->
      <section class="section">
        <div class="row g-3">
          <div class="col-12 col-md-4">
            <div class="card p-3 h-100">
              <h5 class="mb-1">ยอดขาย 30 วัน</h5>
              <div class="kpi">฿<?= nf($kpi['sales_30d']) ?></div>
              <div class="kpi-sub">ยอดรวมสุทธิในช่วง 30 วันที่ผ่านมา</div>
            </div>
          </div>
          <div class="col-12 col-md-4">
            <div class="card p-3 h-100">
              <h5 class="mb-1">จำนวนชิ้นขาย 30 วัน</h5>
              <div class="kpi"><?= number_format((int)$kpi['qty_30d']) ?> ชิ้น</div>
              <div class="kpi-sub">จำนวนสินค้าที่ถูกขายทั้งหมด</div>
            </div>
          </div>
          <div class="col-12 col-md-4">
            <div class="card p-3 h-100">
              <h5 class="mb-1">จำนวนผู้ซื้อ 30 วัน</h5>
              <div class="kpi"><?= number_format((int)$kpi['buyers_30d']) ?> คน</div>
              <div class="kpi-sub">ผู้ซื้อที่ไม่ซ้ำในช่วงเวลาเดียวกัน</div>
            </div>
          </div>
        </div>
      </section>

      <!-- Section: Trends & Composition -->
      <section class="section">
        <div class="d-flex align-items-center justify-content-between">
          <div class="section-title">แนวโน้ม & องค์ประกอบยอดขาย</div>
        </div>
        <div class="divider"></div>

        <div class="row g-3">
          <div class="col-12 col-lg-8">
            <div class="card p-3 h-100">
              <h5 class="mb-2">ยอดขายรายเดือน (2 ปี)</h5>
              <?php if (!$monthly): ?>
                <div class="empty">ไม่มีข้อมูล <span class="badge">monthly</span></div>
              <?php else: ?>
                <canvas id="chartMonthly" aria-label="Monthly sales chart" role="img"></canvas>
              <?php endif; ?>
            </div>
          </div>

          <div class="col-12 col-lg-4">
            <div class="card p-3 h-100">
              <h5 class="mb-2">สัดส่วนยอดขายตามหมวด</h5>
              <?php if (!$category): ?>
                <div class="empty">ไม่มีข้อมูล <span class="badge">category</span></div>
              <?php else: ?>
                <canvas id="chartCategory" aria-label="Category share chart" role="img"></canvas>
              <?php endif; ?>
            </div>
          </div>

          <div class="col-12 col-lg-4">
            <div class="card p-3 h-100">
              <h5 class="mb-2">ยอดขายตามภูมิภาค</h5>
              <?php if (!$region): ?>
                <div class="empty">ไม่มีข้อมูล <span class="badge">region</span></div>
              <?php else: ?>
                <canvas id="chartRegion" aria-label="Region sales chart" role="img"></canvas>
              <?php endif; ?>
            </div>
          </div>

          <div class="col-12 col-lg-8">
            <div class="card p-3 h-100">
              <h5 class="mb-2">Top 10 สินค้าขายดี</h5>
              <?php if (!$topProducts): ?>
                <div class="empty">ไม่มีข้อมูล <span class="badge">topProducts</span></div>
              <?php else: ?>
                <canvas id="chartTopProducts" aria-label="Top products chart" role="img"></canvas>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </section>

      <!-- Section: Behaviors -->
      <section class="section">
        <div class="d-flex align-items-center justify-content-between">
          <div class="section-title">พฤติกรรมการซื้อ</div>
        </div>
        <div class="divider"></div>

        <div class="row g-3">
          <div class="col-12 col-lg-6">
            <div class="card p-3 h-100">
              <h5 class="mb-2">วิธีการชำระเงิน</h5>
              <?php if (!$payment): ?>
                <div class="empty">ไม่มีข้อมูล <span class="badge">payment</span></div>
              <?php else: ?>
                <canvas id="chartPayment" aria-label="Payment share chart" role="img"></canvas>
              <?php endif; ?>
            </div>
          </div>
          <div class="col-12 col-lg-6">
            <div class="card p-3 h-100">
              <h5 class="mb-2">ยอดขายรายชั่วโมง</h5>
              <?php if (!$hourly): ?>
                <div class="empty">ไม่มีข้อมูล <span class="badge">hourly</span></div>
              <?php else: ?>
                <canvas id="chartHourly" aria-label="Hourly sales chart" role="img"></canvas>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </section>

      <!-- Section: New vs Returning -->
      <section class="section">
        <div class="d-flex align-items-center justify-content-between">
          <div class="section-title">ลูกค้าใหม่ vs ลูกค้าเดิม (รายวัน)</div>
        </div>
        <div class="divider"></div>
        <div class="card p-3">
          <?php if (!$newReturning): ?>
            <div class="empty">ไม่มีข้อมูล <span class="badge">newReturning</span></div>
          <?php else: ?>
            <canvas id="chartNewReturning" aria-label="New vs Returning chart" role="img"></canvas>
          <?php endif; ?>
        </div>
      </section>

      <footer class="py-4 text-center text-muted small">© <?= date('Y') ?> Retail DW. สร้างด้วย Bootstrap + Chart.js</footer>
    </div>
  </main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ===== PHP -> JS =====
const monthly      = <?= json_encode($monthly, JSON_UNESCAPED_UNICODE) ?>;
const category     = <?= json_encode($category, JSON_UNESCAPED_UNICODE) ?>;
const region       = <?= json_encode($region, JSON_UNESCAPED_UNICODE) ?>;
const topProducts  = <?= json_encode($topProducts, JSON_UNESCAPED_UNICODE) ?>;
const payment      = <?= json_encode($payment, JSON_UNESCAPED_UNICODE) ?>;
const hourly       = <?= json_encode($hourly, JSON_UNESCAPED_UNICODE) ?>;
const newReturning = <?= json_encode($newReturning, JSON_UNESCAPED_UNICODE) ?>;

// Utility: toXY
const toXY = (arr, x, y) => ({ labels: arr.map(o => o[x]), values: arr.map(o => parseFloat(o[y] ?? 0)) });

// —— สีสำหรับโทนสว่าง —— 
const AXIS_COLOR = '#334155';    // slate-700
const GRID_COLOR = '#e5e7eb';    // gray-200
const LEGEND_COLOR = '#0f172a';  // slate-900

// Chart defaults to match UI
Chart.defaults.font.family = 'Inter, system-ui, -apple-system, Segoe UI, Roboto, "Helvetica Neue", Arial, "Noto Sans"';
Chart.defaults.plugins.legend.labels.usePointStyle = true;
Chart.defaults.plugins.legend.labels.pointStyle = 'circle';
Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(15, 23, 42, .9)';
Chart.defaults.plugins.tooltip.titleColor = '#fff';
Chart.defaults.plugins.tooltip.bodyColor = '#e5e7eb';

// Helper to guard when canvas not present (in case of empty state)
const byId = id => document.getElementById(id);

// Monthly
(() => {
  const el = byId('chartMonthly'); if (!el || !monthly?.length) return;
  const {labels, values} = toXY(monthly, 'ym', 'net_sales');
  new Chart(el, {
    type: 'line',
    data: { labels, datasets: [{ label: 'ยอดขาย (฿)', data: values, tension: .25, fill: true }] },
    options: { maintainAspectRatio: false, plugins: { legend: { labels: { color: LEGEND_COLOR } } }, scales: {
      x: { ticks: { color: AXIS_COLOR }, grid: { color: GRID_COLOR } },
      y: { ticks: { color: AXIS_COLOR }, grid: { color: GRID_COLOR } }
    }}
  });
})();

// Category
(() => {
  const el = byId('chartCategory'); if (!el || !category?.length) return;
  const {labels, values} = toXY(category, 'category', 'net_sales');
  new Chart(el, {
    type: 'doughnut',
    data: { labels, datasets: [{ data: values }] },
    options: { maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { color: LEGEND_COLOR } } } }
  });
})();

// Top products
(() => {
  const el = byId('chartTopProducts'); if (!el || !topProducts?.length) return;
  const labels = topProducts.map(o => o.product_name);
  const qty = topProducts.map(o => parseInt(o.qty_sold ?? 0));
  new Chart(el, {
    type: 'bar',
    data: { labels, datasets: [{ label: 'ชิ้นที่ขาย', data: qty }] },
    options: {
      maintainAspectRatio: false,
      indexAxis: 'y',
      plugins: { legend: { labels: { color: LEGEND_COLOR } } },
      scales: {
        x: { ticks: { color: AXIS_COLOR }, grid: { color: GRID_COLOR } },
        y: { ticks: { color: AXIS_COLOR }, grid: { color: GRID_COLOR } }
      }
    }
  });
})();

// Region
(() => {
  const el = byId('chartRegion'); if (!el || !region?.length) return;
  const {labels, values} = toXY(region, 'region', 'net_sales');
  new Chart(el, {
    type: 'bar',
    data: { labels, datasets: [{ label: 'ยอดขาย (฿)', data: values }] },
    options: { maintainAspectRatio: false, plugins: { legend: { labels: { color: LEGEND_COLOR } } }, scales: {
      x: { ticks: { color: AXIS_COLOR }, grid: { color: GRID_COLOR } },
      y: { ticks: { color: AXIS_COLOR }, grid: { color: GRID_COLOR } }
    }}
  });
})();

// Payment
(() => {
  const el = byId('chartPayment'); if (!el || !payment?.length) return;
  const {labels, values} = toXY(payment, 'payment_method', 'net_sales');
  new Chart(el, {
    type: 'pie',
    data: { labels, datasets: [{ data: values }] },
    options: { maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { color: LEGEND_COLOR } } } }
  });
})();

// Hourly
(() => {
  const el = byId('chartHourly'); if (!el || !hourly?.length) return;
  const {labels, values} = toXY(hourly, 'hour_of_day', 'net_sales');
  new Chart(el, {
    type: 'bar',
    data: { labels, datasets: [{ label: 'ยอดขาย (฿)', data: values }] },
    options: { maintainAspectRatio: false, plugins: { legend: { labels: { color: LEGEND_COLOR } } }, scales: {
      x: { ticks: { color: AXIS_COLOR }, grid: { color: GRID_COLOR } },
      y: { ticks: { color: AXIS_COLOR }, grid: { color: GRID_COLOR } }
    }}
  });
})();

// New vs Returning
(() => {
  const el = byId('chartNewReturning'); if (!el || !newReturning?.length) return;
  const labels = newReturning.map(o => o.date_key);
  const newC = newReturning.map(o => parseFloat(o.new_customer_sales ?? 0));
  const retC = newReturning.map(o => parseFloat(o.returning_sales ?? 0));
  new Chart(el, {
    type: 'line',
    data: { labels,
      datasets: [
        { label: 'ลูกค้าใหม่ (฿)', data: newC, tension: .25, fill: false },
        { label: 'ลูกค้าเดิม (฿)', data: retC, tension: .25, fill: false }
      ]
    },
    options: { maintainAspectRatio: false, plugins: { legend: { labels: { color: LEGEND_COLOR } } }, scales: {
      x: { ticks: { color: AXIS_COLOR, maxTicksLimit: 12 }, grid: { color: GRID_COLOR } },
      y: { ticks: { color: AXIS_COLOR }, grid: { color: GRID_COLOR } }
    }}
  });
})();
</script>
</body>
</html>
