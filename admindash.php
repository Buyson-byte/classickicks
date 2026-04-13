<?php
session_start();
include_once("connections/connection.php");
include_once("middleware/adminMiddleware.php");
$conn = connection();

// Redirect if not admin
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
  header("Location: login.php");
  exit;
}

// Summary Cards
$totalOrders = $conn->query("SELECT COUNT(*) AS count FROM orders")->fetch_assoc()['count'];
$totalProducts = $conn->query("SELECT COUNT(*) AS count FROM products")->fetch_assoc()['count'];
$totalSales = $conn->query("SELECT IFNULL(SUM(total),0) AS sum FROM orders WHERE status='completed'")->fetch_assoc()['sum'];
$totalUsers = $conn->query("SELECT COUNT(*) AS count FROM users WHERE role='user'")->fetch_assoc()['count'];

// 🟢 Updated: Total stock from product_sizes
$totalStockRow = $conn->query("
  SELECT IFNULL(SUM(ps.stock), 0) AS total_stock
  FROM product_sizes ps
")->fetch_assoc();
$totalStock = $totalStockRow['total_stock'] ?? 0;

// Recent Orders
$recentOrders = $conn->query("
  SELECT o.id, u.username, o.order_date, o.total, o.status
  FROM orders o
  JOIN users u ON o.user_id = u.id
  ORDER BY o.order_date DESC
  LIMIT 5
");

// 🟢 Updated: Low stock based on product_sizes
$lowStock = $conn->query("
  SELECT p.name, SUM(ps.stock) AS total_stock
  FROM products p
  LEFT JOIN product_sizes ps ON p.id = ps.product_id
  GROUP BY p.id
  HAVING total_stock <= 5
  ORDER BY total_stock ASC
");

// Sales Overview (Revenue per day - last 7 days)
$salesData = $conn->query("
  SELECT DATE(order_date) as date, SUM(total) as revenue
  FROM orders
  WHERE status='completed'
  GROUP BY DATE(order_date)
  ORDER BY date DESC
  LIMIT 7
");

// Orders Overview (Orders per day - last 7 days)
$orderData = $conn->query("
  SELECT DATE(order_date) as date, COUNT(*) as count
  FROM orders
  GROUP BY DATE(order_date)
  ORDER BY date DESC
  LIMIT 7
");
?>

<?php include 'header.php'; ?>

<!-- Admin Navbar -->
<nav class="admin-header d-flex align-items-center justify-content-between bg-dark p-3">
  <div class="d-flex align-items-center">
    <h4 class="mb-0 text-white me-4">Admin Panel</h4>
    <a href="admindash.php" class="btn btn-sm btn-outline-light me-2 <?php echo basename($_SERVER['PHP_SELF']) == 'admindash.php' ? 'active' : ''; ?>">Dashboard</a>
    <a href="admin.php" class="btn btn-sm btn-outline-light me-2 <?php echo basename($_SERVER['PHP_SELF']) == 'admin.php' ? 'active' : ''; ?>">Manage Products</a>
    <a href="createadmin.php" class="btn btn-sm btn-outline-light 
      <?php echo basename($_SERVER['PHP_SELF']) == 'createadmin.php' ? 'active' : ''; ?>">
      Manage Admins
    </a>
  </div>
  <div class="d-flex align-items-center">
    <span class="me-3 text-white">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
    <a id="logoutBtn" href="#" class="btn btn-outline-light btn-sm text-decoration-none">Logout</a>
  </div>
</nav>

<div class="container mt-4 mb-5">
  <h3 class="mb-4">📊 Admin Dashboard</h3>

  <!-- Summary Cards -->
  <div class="row g-4 mb-4">
    <div class="col-md-3">
      <div class="card text-center shadow">
        <div class="card-body">
          <h6>Total Orders</h6>
          <h3><?php echo $totalOrders; ?></h3>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card text-center shadow">
        <div class="card-body">
          <h6>Total Products</h6>
          <h3><?php echo $totalProducts; ?></h3>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card text-center shadow">
        <div class="card-body">
          <h6>Total Stock</h6>
          <h3><?php echo $totalStock; ?></h3>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card text-center shadow">
        <div class="card-body">
          <h6>Total Sales</h6>
          <h3>₱<?php echo number_format($totalSales, 2); ?></h3>
        </div>
      </div>
    </div>
  </div>

  <!-- Graphs -->
  <div class="row g-4 mb-4">
    <!-- Sales Overview -->
    <div class="col-md-6">
      <div class="card shadow">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <h6>Sales Overview</h6>
            <button class="btn btn-sm btn-dark" onclick="printChart('salesChart')">🖨 Print</button>
          </div>
          <canvas id="salesChart"></canvas>
        </div>
      </div>
    </div>

    <!-- Orders Overview -->
    <div class="col-md-6">
      <div class="card shadow">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <h6>Orders Overview</h6>
            <button class="btn btn-sm btn-dark" onclick="printChart('ordersChart')">🖨 Print</button>
          </div>
          <canvas id="ordersChart"></canvas>
        </div>
      </div>
    </div>
  </div>

  <!-- Tables -->
  <div class="row g-4">
    <div class="col-md-7">
      <div class="card shadow">
        <div class="card-body">
          <h6>Recent Orders</h6>
          <table class="table table-striped">
            <thead>
              <tr>
                <th>Order ID</th>
                <th>Customer</th>
                <th>Date</th>
                <th>Total</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($o = $recentOrders->fetch_assoc()): ?>
                <tr>
                  <td><?php echo $o['id']; ?></td>
                  <td><?php echo htmlspecialchars($o['username']); ?></td>
                  <td><?php echo $o['order_date']; ?></td>
                  <td>₱<?php echo number_format($o['total'], 2); ?></td>
                  <td><?php echo ucfirst($o['status']); ?></td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Low Stock Products -->
    <div class="col-md-5">
      <div class="card shadow">
        <div class="card-body">
          <h6>Low Stock Products</h6>
          <table class="table table-striped">
            <thead>
              <tr>
                <th>Product</th>
                <th>Total Stock</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($p = $lowStock->fetch_assoc()): ?>
                <tr  style="background-color: #a5a6a7ff;">
                  <td><?php echo htmlspecialchars($p['name']); ?></td>
                  <td><?php echo $p['total_stock']; ?></td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  // 🟢 Sales Chart
  new Chart(document.getElementById('salesChart'), {
    type: 'line',
    data: {
      labels: <?php echo json_encode(array_column(iterator_to_array($salesData), 'date')); ?>,
      datasets: [{
        label: 'Revenue (₱)',
        data: <?php echo json_encode(array_column(iterator_to_array($salesData), 'revenue')); ?>,
        borderColor: '#007bff',
        fill: false
      }]
    }
  });

  // 🟢 Orders Chart (color changed)
  new Chart(document.getElementById('ordersChart'), {
    type: 'bar',
    data: {
      labels: <?php echo json_encode(array_column(iterator_to_array($orderData), 'date')); ?>,
      datasets: [{
        label: 'Orders',
        data: <?php echo json_encode(array_column(iterator_to_array($orderData), 'count')); ?>,
        backgroundColor: '#0a6704'
      }]
    }
  });

  // 🖨 Print specific chart
  function printChart(chartId) {
    const canvas = document.getElementById(chartId);
    const win = window.open('', '_blank');
    win.document.write(`<html><head><title>Print Chart</title></head><body>`);
    win.document.write('<h3 style="text-align:center;">' + chartId.replace('Chart', '') + '</h3>');
    win.document.write('<img src="' + canvas.toDataURL() + '" style="width:100%;">');
    win.document.write('</body></html>');
    win.document.close();
    win.print();
  }
</script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="js/newlogout.js"></script>
