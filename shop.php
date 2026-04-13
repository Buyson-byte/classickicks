<?php
include 'header.php';
include 'navbar.php';

include_once("connections/connection.php");
$conn = connection();

// Get all brands
$brands = $conn->query("SELECT * FROM brands");

// Filters
$where = [];
$params = [];
$types = "";

if (!empty($_GET['brand'])) {
  $where[] = "brand_id = ?";
  $params[] = (int)$_GET['brand'];
  $types .= "i";
}

if (!empty($_GET['size'])) {
  $where[] = "sizes LIKE ?";
  $params[] = "%" . $_GET['size'] . "%";
  $types .= "s";
}

if (!empty($_GET['min_price'])) {
  $where[] = "price >= ?";
  $params[] = $_GET['min_price'];
  $types .= "d";
}
if (!empty($_GET['max_price'])) {
  $where[] = "price <= ?";
  $params[] = $_GET['max_price'];
  $types .= "d";
}

// Sorting
$sort = isset($_GET['sort']) ? $_GET['sort'] : '';
$order_by = "";

switch ($sort) {
  case 'bestselling':
    $order_by = " ORDER BY (
                    SELECT COALESCE(SUM(oi.quantity),0)
                    FROM order_items oi
                    WHERE oi.product_id = products.id
                  ) DESC";
    break;
  case 'az':
    $order_by = " ORDER BY products.name ASC";
    break;
  case 'za':
    $order_by = " ORDER BY products.name DESC";
    break;
  case 'low_high':
    $order_by = " ORDER BY products.price ASC";
    break;
  case 'high_low':
    $order_by = " ORDER BY products.price DESC";
    break;
  default:
    $order_by = " ORDER BY products.id DESC"; // newest
}

// Pagination setup
$limit = 6; // products per page
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Count total products
$count_sql = "SELECT COUNT(*) as total FROM products";
if ($where) {
  $count_sql .= " WHERE " . implode(" AND ", $where);
}
$count_stmt = $conn->prepare($count_sql);
if ($where) {
  $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$count_result = $count_stmt->get_result()->fetch_assoc();
$total_products = $count_result['total'];
$total_pages = ceil($total_products / $limit);

// Fetch products with filters + sorting + pagination
$sql = "SELECT * FROM products";
if ($where) {
  $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= $order_by;
$sql .= " LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sql);

// Combine all params
$all_params = $params;
$all_types = $types . "ii";
$all_params[] = $limit;
$all_params[] = $offset;

// bind_param requires references
$bind = [];
$bind[] = &$all_types;
foreach ($all_params as $k => $v) {
  $bind[] = &$all_params[$k];
}
call_user_func_array([$stmt, 'bind_param'], $bind);

$stmt->execute();
$products = $stmt->get_result();
?>

<?php if (session_status() === PHP_SESSION_NONE) session_start(); ?>

<style>
  .breadcrumb {
    --bs-breadcrumb-divider-color: white !important;
  }

  .breadcrumb-item+.breadcrumb-item::before {
    color: white !important;
  }

  .breadcrumb a {
    color: white;
  }

  .breadcrumb a:hover {
    text-decoration: underline;
    color: #f8f9fa;
    /* lighter hover */
  }
</style>


<!-- ===== BREADCRUMB SECTION ===== -->
<div class="breadcrumb-container position-relative text-center text-white d-flex align-items-center justify-content-center"
  style="background-image: url('images/bread.png'); background-size: cover; background-position: center; height: 150px;">
  <div class="overlay position-absolute top-0 start-0 w-100 h-100" style="background: rgba(0,0,0,0.4);"></div>

  <div class="position-relative">
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb justify-content-center mb-2">
        <li class="breadcrumb-item"><a href="index.php" class="text-white text-decoration-none">Home</a></li>
        <li class="breadcrumb-item active text-white" aria-current="page">Shop</a></li>
      </ol>
    </nav>
    <h1 class="fw-bold text-uppercase">SHOP</h1>
  </div>
</div>
<!-- ===== END BREADCRUMB SECTION ===== -->

<div class="container mt-5">
  <h2 class="text-center fw-bold mb-4">Shop</h2>

  <div class="row">
    <!-- FILTERS SIDEBAR -->
    <div class="col-md-3 mb-4">
      <div class="card shadow-sm border-0">
        <div class="card-body">
          <h5 class="fw-bold mb-3">Filters</h5>
          <form method="GET" action="shop.php">

            <!-- Brand Filter -->
            <div class="mb-3">
              <label for="brand" class="form-label">Brand</label>
              <select class="form-select" id="brand" name="brand">
                <option value="">All Brands</option>
                <?php
                $brands->data_seek(0);
                while ($brand = $brands->fetch_assoc()):
                  $selected = (isset($_GET['brand']) && $_GET['brand'] == $brand['id']) ? 'selected' : '';
                ?>
                  <option value="<?php echo $brand['id']; ?>" <?= $selected; ?>>
                    <?php echo htmlspecialchars($brand['name']); ?>
                  </option>
                <?php endwhile; ?>
              </select>
            </div>

            <!-- Size Filter -->
            <div class="mb-3">
              <label for="size" class="form-label">Size</label>
              <select class="form-select" id="size" name="size">
                <option value="">All Sizes</option>
                <?php
                $sizes = ['XS', 'S', 'M', 'L', 'XL'];
                foreach ($sizes as $size):
                  $selected = (isset($_GET['size']) && $_GET['size'] == $size) ? 'selected' : '';
                  echo "<option value='$size' $selected> $size</option>";
                endforeach;
                ?>
              </select>
            </div>

            <!-- Price Filter -->
            <div class="mb-3">
              <label class="form-label">Price Range (₱)</label>
              <div class="d-flex">
                <input min="0" type="number" class="form-control me-2" name="min_price" placeholder="Min"
                  value="<?= isset($_GET['min_price']) ? $_GET['min_price'] : '' ?>">
                <input min="0" type="number" class="form-control" name="max_price" placeholder="Max"
                  value="<?= isset($_GET['max_price']) ? $_GET['max_price'] : '' ?>">
              </div>
            </div>

            <!-- Actions -->
            <div class="d-grid gap-2">
              <button type="submit" class="btn btn-dark">Apply Filters</button>
              <a href="shop.php" class="btn btn-outline-secondary">Clear Filters</a>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- PRODUCTS SECTION -->
    <div class="col-md-9">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold mb-0"><?= $total_products ?> Products found</h6>
        <form method="GET" class="d-flex">
          <!-- Preserve filters -->
          <?php foreach ($_GET as $key => $value):
            if ($key != 'sort' && $key != 'page'): ?>
              <input type="hidden" name="<?= htmlspecialchars($key) ?>" value="<?= htmlspecialchars($value) ?>">
          <?php endif;
          endforeach; ?>

          <select class="form-select form-select-sm" name="sort" onchange="this.form.submit()">
            <option value="">Sort By</option>
            <option value="bestselling" <?= ($sort == 'bestselling' ? 'selected' : '') ?>>Best Selling</option>
            <option value="az" <?= ($sort == 'az' ? 'selected' : '') ?>>Name: A–Z</option>
            <option value="za" <?= ($sort == 'za' ? 'selected' : '') ?>>Name: Z–A</option>
            <option value="low_high" <?= ($sort == 'low_high' ? 'selected' : '') ?>>Price: Low to High</option>
            <option value="high_low" <?= ($sort == 'high_low' ? 'selected' : '') ?>>Price: High to Low</option>
          </select>
        </form>
      </div>

      <div class="row g-4">
        <?php if ($products && $products->num_rows > 0): ?>
          <?php while ($product = $products->fetch_assoc()): ?>
            <div class="col-6 col-md-4">
              <div class="card h-100 shadow-sm border-0">
                <img src="<?php echo $product['image']; ?>" class="card-img-top"
                  style="height: 180px; object-fit: cover;" alt="Product Image">
                <div class="card-body d-flex flex-column">
                  <h6 class="fw-bold"><?php echo htmlspecialchars($product['name']); ?></h6>
                  <p class="text-muted small mb-2">₱ <?php echo number_format($product['price'], 2); ?></p>
                  <p class="text-truncate mb-3"><?php echo htmlspecialchars($product['description']); ?></p>
                  <a href="product.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-dark mt-auto">View Product</a>
                  <a href="handlers/add_to_wishlist.php?product_id=<?= $product['id'] ?>&from=shop"
                    class="btn btn-sm btn-outline-danger mt-2">❤️ Wishlist</a>
                </div>
              </div>
            </div>
          <?php endwhile; ?>
        <?php else: ?>
          <p class="text-center text-muted">No products found for these filters.</p>
        <?php endif; ?>
      </div>

      <!-- PAGINATION -->
      <?php if ($total_pages > 1): ?>
        <nav aria-label="Page navigation">
          <ul class="pagination justify-content-center mt-4">
            <!-- Previous -->
            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
              <a class="page-link" 
                href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" 
                aria-label="Previous"
                style="color:#6c757d; background-color:#f8f9fa; border-color:#dee2e6;">
                <span aria-hidden="true">&laquo;</span>
              </a>
            </li>

            <!-- Page Numbers -->
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
              <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                <a class="page-link" 
                  href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"
                  style="<?= ($i == $page) 
                              ? 'background-color:#6c757d; border-color:#6c757d; color:#fff;' 
                              : 'color:#6c757d; background-color:#f8f9fa; border-color:#dee2e6;' ?>">
                  <?= $i ?>
                </a>
              </li>
            <?php endfor; ?>


            <!-- Next -->
            <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
              <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" aria-label="Next"
              style="color:#6c757d; background-color:#f8f9fa; border-color:#dee2e6;">
                <span aria-hidden="true">&raquo;</span>
              </a>
            </li>
          </ul>
        </nav>
      <?php endif; ?>
    </div>
  </div>
</div>


<?php include 'back_to_top.php'; ?>
<?php include 'footer.php'; ?>