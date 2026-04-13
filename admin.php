<?php
session_start();
include_once("connections/connection.php");
include_once("middleware/adminMiddleware.php");
$conn = connection();

$outOfStockWishlist = $conn->query("
    SELECT p.id, p.name, p.image, p.price, COUNT(w.user_id) AS wishlist_count
    FROM products p
    INNER JOIN wishlist w ON p.id = w.product_id
    WHERE p.quantity <= 0
    GROUP BY p.id
    ORDER BY wishlist_count DESC
");
// Orders query
$orders = $conn->query("
    SELECT o.id, u.username, o.order_date, o.total, o.status
    FROM orders o
    JOIN users u ON o.user_id = u.id
    ORDER BY o.order_date DESC
");

// Redirect if not logged in or not admin
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
  header("Location: login.php");
  exit;
}

include_once("handlers/create_product.php");
include_once("handlers/delete_product.php");
include_once("handlers/edit_product.php");
include_once("handlers/chart_data.php");
include_once("handlers/fetch_data.php");
include_once("handlers/create_brand.php");
include_once("handlers/edit_brand.php");
include_once("handlers/delete_brand.php");

?>

<?php include 'header.php'; ?>

<!-- Admin Navbar -->
<nav class="admin-header d-flex align-items-center justify-content-between bg-dark p-3">
  <div class="d-flex align-items-center">
    <h4 class="mb-0 text-white me-4">Admin Panel</h4>
    <a href="admindash.php" class="btn btn-sm btn-outline-light me-2 
      <?php echo basename($_SERVER['PHP_SELF']) == 'admindash.php' ? 'active' : ''; ?>">
      Dashboard
    </a>
    <a href="admin.php" class="btn btn-sm btn-outline-light me-2
      <?php echo basename($_SERVER['PHP_SELF']) == 'admin.php' ? 'active' : ''; ?>">
      Manage Products
    </a>
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



<!-- Admin Content -->
<div class="container mt-5 mb-5">
  <!-- Nav Tabs -->
  <?php if (isset($_SESSION['message'])): ?>
    <div class="alert alert-info"><?php echo $_SESSION['message'];
                                  unset($_SESSION['message']); ?></div>
  <?php endif; ?>
  <ul class="nav admin-nav-tabs" id="adminTab" role="tablist">
    <li class="nav-item" role="presentation">
      <button class="nav-link active" id="create-brand" data-bs-toggle="tab" data-bs-target="#brand" type="button" role="tab">Create Brand</button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="create-tab" data-bs-toggle="tab" data-bs-target="#create" type="button" role="tab">Create Product</button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="list-tab" data-bs-toggle="tab" data-bs-target="#list" type="button" role="tab">Product List</button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="report-tab" data-bs-toggle="tab" data-bs-target="#report" type="button" role="tab">Best Seller Report</button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="orders-tab" data-bs-toggle="tab" data-bs-target="#orders" type="button" role="tab">
        Orders
      </button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link <?= ($active_tab == 'wishlist-alert') ? 'active' : '' ?>"
        id="wishlist-alert-tab"
        data-bs-toggle="tab"
        data-bs-target="#wishlist-alert"
        type="button"
        role="tab">
        Wishlist Alerts
      </button>
    </li>
  </ul>

  <!-- Tab Contents -->
  <div class="tab-content" id="adminTabContent">
    <!-- Create Brand Tab -->
    <!-- Create me a Brand Tab Here -->
  <!-- brand pt2 -->
    <div class="tab-pane fade show" id="brand" role="tabpanel">
      <div class="mt-3">

        <?php
        // Check if editing a brand
        if (isset($_GET['edit_brand']) && is_numeric($_GET['edit_brand'])):
            $edit_id = intval($_GET['edit_brand']);
            $stmt = $conn->prepare("SELECT * FROM brands WHERE id = ?");
            $stmt->bind_param("i", $edit_id);
            $stmt->execute();
            $edit_brand = $stmt->get_result()->fetch_assoc();
            $stmt->close();
        ?>
          <h5>Edit Brand (ID: <?= $edit_id ?>)</h5>
          <form method="POST" class="mb-4">
            <input type="hidden" name="id" value="<?= $edit_brand['id'] ?>">
            <div class="mb-3">
              <label for="brand_name" class="form-label">Brand Name</label>
              <input type="text" class="form-control" id="brand_name" name="name"
                    value="<?= htmlspecialchars($edit_brand['name']) ?>" required>
            </div>
            <button type="submit" name="update_brand" class="btn btn-success">Update Brand</button>
            <a href="admin.php#brand" class="btn btn-secondary ms-2">Cancel</a>
          </form>
        <?php else: ?>
          <!-- Create New Brand -->
          <form method="POST" class="mb-4">
            <div class="mb-3">
              <label for="name" class="form-label">Brand Name</label>
              <input type="text" class="form-control" id="name" name="name" required>
            </div>
            <div class="mb-2">
              <label for="image" class="form-label">Image</label>
              <input type="file" class="form-control" id="image" name="image" required>
            </div>

            <button type="submit" name="create_brand" class="btn btn-dark">Add Brand</button>
          </form>
        <?php endif; ?>

        <?php
        // Display all brands
        $brand_result = $conn->query("SELECT * FROM brands ORDER BY id DESC");
        if ($brand_result->num_rows > 0):
        ?>
          <h5>Existing Brands</h5>
          <div class="table-responsive">
            <table class="table table-bordered align-middle">
              <thead class="table-dark">
                <tr>
                  <th>#</th>
                  <th>Brand Name</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <?php while ($brand = $brand_result->fetch_assoc()): ?>
                  <tr>
                    <td><?= $brand['id'] ?></td>
                    <td><?= htmlspecialchars($brand['name']) ?></td>
                    <td>
                      <a href="admin.php?edit_brand=<?= $brand['id'] ?>#brand" class="btn btn-sm btn-warning">Edit</a>
                      <a href="admin.php?delete_brand=<?= $brand['id'] ?>#brand"
                        class="btn btn-sm btn-danger"
                        onclick="return confirm('Are you sure you want to delete this brand?');">
                        Delete
                      </a>
                    </td>
                  </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <p class="text-muted">No brands added yet.</p>
        <?php endif; ?>
      </div>
    </div>
    <!-- Create Product Tab -->
    <div class="tab-pane fade" id="create" role="tabpanel">
      <div class="mt-3">
    
        <form method="POST" class="mb-5" enctype="multipart/form-data">
          <div class="mb-3">
            <label for="name" class="form-label">Product Name</label>
            <input type="text" class="form-control" id="name" name="name" required>
          </div>

          <div class="mb-3">
            <label for="brand_id" class="form-label">Brand</label>
            <select class="form-select" name="brand_id" id="brand_id" required>
              <option value="" disabled selected>Select Brand</option>
              <?php foreach ($brands as $brand): ?>
                <option value="<?php echo $brand['id']; ?>"><?php echo $brand['name']; ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="mb-2">
            <label for="description" class="form-label">Description</label>
            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
          </div>

          <div class="mb-2">
            <label for="price" class="form-label">Price (₱)</label>
            <input type="number" min="0" step="0.01" class="form-control" id="price" name="price" required>
          </div>

          <!-- <div class="mb-2">
            <label for="quantity" class="form-label">Quantity</label>
            <input type="number" min="0" class="form-control" id="quantity" name="quantity" required>
          </div> -->

          <div class="mb-3">
            <label class="form-label ">Quantity</label>
            <div class="row">
              <?php foreach (['XS', 'S', 'M', 'L', 'XL'] as $size): ?>
                <div class="col-md-2 col-4">
                  <label class="form-label small"><?= $size ?></label>
                  <input type="number" name="stock[<?= $size ?>]" class="form-control" min="0" value="0">
                </div>
              <?php endforeach; ?>
            </div>
          </div>



          <div class="mb-2">
            <label for="image" class="form-label">Image</label>
            <input type="file" class="form-control" id="image" name="image" required>
          </div>

          <button type="submit" name="create_product" class="btn btn-dark">Add Product</button>
        </form>
      </div>
    </div>

    <!-- Product List Tab -->
    <div class="tab-pane fade" id="list" role="tabpanel">
      <div class="mt-4">
        <?php if (!isset($_GET['edit']) && $products_result->num_rows > 0): ?>

          <h5>Product List</h5>
          <div class="table-responsive">
            <table class="table table-bordered align-middle">
              <thead class="table-dark">
                <tr>
                  <th>#</th>
                  <th>Image</th>
                  <th>Name</th>
                  <th>Brand</th>
                  <th>Price (₱)</th>
                  <th>Qty</th>
                  <th>Sizes</th> <!-- NEW -->
                  <th>Description</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <?php while ($product = $products_result->fetch_assoc()): ?>
                  <tr>
                    <td><?php echo $product['id']; ?></td>
                    <td><img src="<?php echo $product['image']; ?>" alt="Product Image" style="width: 60px;"></td>
                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                    <td><?php echo htmlspecialchars($product['brand_name']); ?></td>
                    <td><?php echo number_format($product['price'], 2); ?></td>
                    <td><?php echo $product['total_stock']; ?></td>
                    <td><?php echo htmlspecialchars($product['sizes']); ?></td> <!-- NEW -->
                    <td><?php echo nl2br(htmlspecialchars($product['description'])); ?></td>
                    <td>
                      <a href="admin.php?edit=<?php echo $product['id']; ?>#list" class="btn btn-sm btn-warning">Edit</a>
                      <a href="admin.php?delete=<?php echo $product['id']; ?>"
                        class="btn btn-sm btn-danger"
                        onclick="return confirm('Are you sure you want to delete this product?');">
                        Delete
                      </a>
                    </td>
                  </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>

        <?php elseif (isset($_GET['edit'])): ?>
          <?php
          if (isset($_GET['edit']) && is_numeric($_GET['edit'])):
            $edit_id = (int) $_GET['edit'];
            $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
            $stmt->bind_param("i", $edit_id);
            $stmt->execute();
            $edit_result = $stmt->get_result();
            $edit_product = $edit_result->fetch_assoc();
            $stmt->close();
          ?>
            <h5>Edit Product (ID: <?php echo $edit_id; ?>)</h5>
            <form method="POST">
              <input type="hidden" name="id" value="<?php echo $edit_id; ?>">

              <div class="mb-3">
                <label for="edit_name" class="form-label">Product Name</label>
                <input type="text" name="name" id="edit_name" class="form-control" value="<?php echo htmlspecialchars($edit_product['name']); ?>" required>
              </div>

              <div class="mb-3">
                <label for="edit_brand_id" class="form-label">Brand</label>
                <select name="brand_id" id="edit_brand_id" class="form-select" required>
                  <?php foreach ($brands as $brand): ?>
                    <option value="<?php echo $brand['id']; ?>" <?php if ($brand['id'] == $edit_product['brand_id']) echo 'selected'; ?>>
                      <?php echo htmlspecialchars($brand['name']); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="mb-3">
                <label for="edit_description" class="form-label">Description</label>
                <textarea name="description" id="edit_description" class="form-control" rows="3"><?php echo htmlspecialchars($edit_product['description']); ?></textarea>
              </div>

              <div class="mb-3">
                <label for="edit_price" class="form-label">Price (₱)</label>
                <input type="number" step="0.01" name="price" id="edit_price" class="form-control" value="<?php echo $edit_product['price']; ?>" required>
              </div>

              <!-- <div class="mb-3">
                <label for="edit_quantity" class="form-label">Quantity</label>
                <input type="number" name="quantity" id="edit_quantity" class="form-control" value="<?php echo $edit_product['quantity']; ?>" required>
              </div> -->

              <?php
              // Fetch existing per-size stocks for this product
              $size_stock_data = [];
              $size_stmt = $conn->prepare("SELECT size, stock FROM product_sizes WHERE product_id = ?");
              $size_stmt->bind_param("i", $edit_id);
              $size_stmt->execute();
              $res = $size_stmt->get_result();
              while ($row = $res->fetch_assoc()) {
                $size_stock_data[$row['size']] = $row['stock'];
              }
              $size_stmt->close();
              ?>
              <div class="mb-3">
                <label class="form-label ">Quantity</label>
                <div class="row">
                  <?php foreach (['XS', 'S', 'M', 'L', 'XL'] as $size):
                    $stockValue = $size_stock_data[$size] ?? 0;
                  ?>
                    <div class="col-md-2 col-4">
                      <label class="form-label small"><?= $size ?></label>
                      <input type="number" name="stock[<?= $size ?>]" class="form-control" min="0" value="<?= $stockValue ?>">
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>



              <div class="mb-3">
                <label for="edit_image" class="form-label">Image URL</label>
                <input type="text" name="image" id="edit_image" class="form-control" value="<?php echo htmlspecialchars($edit_product['image']); ?>" required>
              </div>

              <button type="submit" name="update_product" class="btn btn-success">Update Product</button>
              <a href="admin.php#list" class="btn btn-secondary ms-2">Cancel</a>
            </form>
          <?php endif; ?>
        <?php elseif (!isset($_GET['edit'])): ?>
          <p class="text-muted">No products found.</p>
        <?php endif; ?>
      </div>
    </div>

    <!-- Best Seller Report Tab -->
    <div class="tab-pane fade" id="report" role="tabpanel">
      <div class="mt-4" style="height:520px;"> <!-- shorter height -->
        <h5 class="text-center mb-3">Best Sellers (by Quantity Sold)</h5>
        <canvas id="bestSellerChart"></canvas>
      </div>
    </div>

    <!-- Orders Tab -->
    <div class="tab-pane fade" id="orders" role="tabpanel">
      <div class="mt-4">
        <h5>Orders</h5>
        <div class="table-responsive">
          <table class="table table-bordered align-middle">
            <thead class="table-dark">
              <tr>
                <th>#</th>
                <th>User</th>
                <th>Date</th>
                <th>Total</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($orders && $orders->num_rows): while ($order = $orders->fetch_assoc()): ?>
                  <tr>
                    <td><?php echo $order['id']; ?></td>
                    <td><?php echo htmlspecialchars($order['username']); ?></td>
                    <td><?php echo htmlspecialchars($order['order_date']); ?></td>
                    <td>₱<?php echo number_format($order['total'], 2); ?></td>
                    <td>
                      <form method="POST" action="handlers/update_order_status.php" class="d-flex">
                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                        <?php
                        $isCanceled = strtolower($order['status']) === 'canceled';
                        ?>
                        <select name="status" class="form-select form-select-sm me-2"
                          onchange="this.form.submit()"
                          <?= $isCanceled ? 'disabled' : ''; ?>>
                          <option value="pending" <?= $order['status'] == 'pending'   ? 'selected' : ''; ?>>Order Placed</option>
                          <option value="packed" <?= $order['status'] == 'packed'    ? 'selected' : ''; ?>>Packed</option>
                          <option value="shipped" <?= $order['status'] == 'shipped'   ? 'selected' : ''; ?>>Shipped</option>
                          <option value="completed" <?= $order['status'] == 'completed' ? 'selected' : ''; ?>>Delivered</option>
                          <option value="canceled" <?= $order['status'] == 'canceled'  ? 'selected' : ''; ?>>Canceled</option>
                        </select>

                        <?php if ($isCanceled): ?>
                          <span class="badge bg-danger ms-2">User Canceled</span>
                        <?php endif; ?>


                      </form>
                    </td>
                  </tr>
                <?php endwhile;
              else: ?>
                <tr>
                  <td colspan="6" class="text-center text-muted">No orders yet.</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>



    <!-- Wishlist Alerts Tab -->
    <div class="tab-pane fade <?= ($active_tab == 'wishlist-alert') ? 'show active' : '' ?>" id="wishlist-alert" role="tabpanel">
      <div class="mt-4">
        <h5>Out-of-Stock Products in Wishlists</h5>
        <?php if ($outOfStockWishlist->num_rows > 0): ?>
          <div class="table-responsive">
            <table class="table table-bordered align-middle">
              <thead class="table-dark">
                <tr>
                  <th>Image</th>
                  <th>Product</th>
                  <th>Price</th>
                  <th>Wishlists Count</th>
                </tr>
              </thead>
              <tbody>
                <?php while ($item = $outOfStockWishlist->fetch_assoc()): ?>
                  <tr>
                    <td><img src="<?php echo htmlspecialchars($item['image']); ?>" style="width: 60px;"></td>
                    <td><?php echo htmlspecialchars($item['name']); ?></td>
                    <td>₱<?php echo number_format($item['price'], 2); ?></td>
                    <td><?php echo $item['wishlist_count']; ?></td>
                  </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <p class="text-muted">No products in wishlists.</p>
        <?php endif; ?>
      </div>
    </div>
    <script>
      // Save the active tab to localStorage when clicked
      document.querySelectorAll('#adminTab button[data-bs-toggle="tab"]').forEach(tab => {
        tab.addEventListener('shown.bs.tab', function(e) {
          localStorage.setItem('activeAdminTab', e.target.getAttribute('data-bs-target'));
        });
      });

      // On page load, restore the last active tab
      document.addEventListener('DOMContentLoaded', function() {
        const activeTab = localStorage.getItem('activeAdminTab');
        if (activeTab) {
          const tab = document.querySelector(`#adminTab button[data-bs-target="${activeTab}"]`);
          if (tab) {
            new bootstrap.Tab(tab).show();
          }
        }
      });
    </script>


    <script>
      const ctx = document.getElementById('bestSellerChart').getContext('2d');
      const bestSellerChart = new Chart(ctx, {
        type: 'bar',
        data: {
          labels: <?php echo json_encode($labels); ?>,
          datasets: <?php echo json_encode($datasets); ?>
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            title: {
              display: true,
              text: 'Monthly Best Seller Products'
            },
            tooltip: {
              mode: 'index',
              intersect: false
            },
            legend: {
              position: 'bottom'
            }
          },
          scales: {
            x: {
              stacked: false
            },
            y: {
              beginAtZero: true,
              stacked: false
            }
          }
        }
      });
    </script>


  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="js/newlogout.js"></script>
</div>