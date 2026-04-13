<?php 
include 'header.php'; 
include 'navbar.php';

if (isset($_SESSION['message'])): ?>
    <div id="session-alert" class="alert alert-success alert-dismissible fade show floating-alert" role="alert">
        <?php echo $_SESSION['message']; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['message']); ?>
<?php endif; ?>


<?php
include_once("connections/connection.php");
$conn = connection();

// Fetch all brands from the database
$brands_result = $conn->query("SELECT * FROM brands");

// Initialize brand array
$brands = [];

// If query is successful, populate $brands
if ($brands_result && $brands_result->num_rows > 0) {
    while ($row = $brands_result->fetch_assoc()) {
        $brands[] = $row;
    }
}
// Now chunk the brands array into groups of 4
$brand_chunks = array_chunk($brands, 4);
// Get featured products: sold quantity ≥ 10
$sql = "
    SELECT p.*, SUM(oi.quantity) AS total_sold
    FROM products p
    JOIN order_items oi ON p.id = oi.product_id
    JOIN orders o ON oi.order_id = o.id
    WHERE o.status = 'completed'
    GROUP BY p.id
    HAVING total_sold >= 10
    ORDER BY total_sold DESC
    LIMIT 8
";

$featured_products = $conn->query($sql);

?>
<style>
 /* session alert successfully login! */
  .floating-alert {
    position: fixed;
    top: 120px;
    left: 85%;
    transform: translateX(-50%);
    width: auto;
    max-width: 900px;
    z-index: 2000;
    /* make sure it stays on top of slider/nav */
    box-shadow: 0px 4px 12px rgba(0, 0, 0, 0.25);
  }

  .fade-text {
    opacity: 0;
    transform: translateY(20px);
    transition: opacity 0.8s ease, transform 0.8s ease;
  }

  .carousel-item.active .fade-text:nth-child(1) {
    transition-delay: 0.2s;
    opacity: 1;
    transform: translateY(0);
  }

  .carousel-item.active .fade-text:nth-child(2) {
    transition-delay: 0.4s;
    opacity: 1;
    transform: translateY(0);
  }

  .carousel-item.active .fade-text:nth-child(3) {
    transition-delay: 0.6s;
    opacity: 1;
    transform: translateY(0);
  }
</style>
<!-- HERO SLIDER -->
<div id="heroCarousel" class="carousel slide" data-bs-ride="carousel">
  <div class="carousel-inner">
    <div class="carousel-item active" style="background-color: #e2d9ceff; padding: 60px 0;">
      <div class="container d-flex flex-column flex-lg-row align-items-center justify-content-between">
        <div class="text-center text-lg-start mb-4 mb-lg-0">
          <p class="text-muted small fade-text">Every step, game On!</p>
          <h1 class="display-5 fw-bold mb-3 fade-text">Elevate every<br>walking moment!</h1>
          <a href="shop.php" class="btn btn-dark px-4 py-2 rounded-pill fade-text">BUY NOW</a>
        </div>
        <div class="text-center">
          <img src="images/j1.png" alt="Sneakers" class="img-fluid" style="max-height: 450px;">
        </div>
      </div>
    </div>

    <div class="carousel-item" style="background-color: #e2d9ceff; padding: 60px 0;">
      <div class="container d-flex flex-column flex-lg-row align-items-center justify-content-between">
        <div class="text-center text-lg-start mb-4 mb-lg-0">
          <p class="text-muted small fade-text">Every step, game On!</p>
          <h1 class="display-5 fw-bold mb-3 fade-text">Elevate every<br>walking moment!</h1>
          <a href="shop.php" class="btn btn-dark px-4 py-2 rounded-pill fade-text">BUY NOW</a>
        </div>
        <div class="text-center">
          <img src="images/banner2.png" alt="Sneakers" class="img-fluid" style="max-height: 450px;">
        </div>
      </div>
    </div>
  </div>

  <div class="carousel-indicators">
    <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
    <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="1" aria-label="Slide 2"></button>
  </div>
</div>

<!-- Popular Categories Section -->
<style>
  /* Zoom effect on brand cards */
  .cardo img {
    transition: transform 0.4s ease;
  }

  .cardo:hover img {
    transform: scale(1.1);
  }

  /* Optional: subtle shadow effect when hovered */
  .cardo:hover {
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.20);
    transform: translateY(-3px);
    transition: all 0.3s ease;
  }
</style>
<div class="container mt-5 mb-5">
  <h2 class="text-center fw-bold mb-4">Explore Popular Brands</h2>

  <div id="categoryCarousel" class="carousel slide" data-bs-ride="carousel">
    <div class="carousel-inner">
      <?php foreach ($brand_chunks as $index => $chunk): ?>
        <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
          <div class="row g-4 text-center">
            <?php foreach ($chunk as $brand): ?>
              <div class="col-6 col-md-3">
                <a href="shop.php?brand=<?php echo $brand['id']; ?>" class="text-decoration-none text-dark">
                  <div class="card shadow-sm border-0 h-100 cardo">
                    <img src="images/<?php echo strtolower($brand['name']); ?>.svg"
                      class="card-img-top p-3 "
                      style="height: 100px; object-fit: contain;"
                      alt="<?php echo htmlspecialchars($brand['name']); ?>">
                    <div class="card-body ">
                      <h6 class="mb-0"><?php echo htmlspecialchars($brand['name']); ?></h6>
                    </div>
                  </div>
                </a>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <button class="carousel-control-prev" type="button" data-bs-target="#categoryCarousel" data-bs-slide="prev">
      <span class="carousel-control-prev-icon bg-dark rounded-circle p-2" aria-hidden="true"></span>
      <span class="visually-hidden">Previous</span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#categoryCarousel" data-bs-slide="next">
      <span class="carousel-control-next-icon bg-dark rounded-circle p-2" aria-hidden="true"></span>
      <span class="visually-hidden">Next</span>
    </button>
  </div>
</div>

<!-- spacer collection images -->
  <div class="container-fluid p-0 pb-5">
  <div class="row g-0">
    <!-- Column 1 -->
    <div class="col-md-3">
      <div class="position-relative h-100">
        <img src="images/homeadidas.png" class="w-100 h-100 img-fit" alt="Image 1">
      </div>
    </div>

    <!-- Column 2 -->
    <div class="col-md-3">
      <div class="position-relative h-100">
        <img src="images/homenewbalnce.png" class="w-100 h-100 img-fit" alt="Image 2">
      </div>
    </div>

    <!-- Column 3 -->
    <div class="col-md-3">
      <div class="position-relative h-100">
        <img src="images/homenike.png" class="w-100 h-100 img-fit" alt="Image 3">
      </div>
    </div>

    <!-- Column 4 -->
    <div class="col-md-3">
      <div class="position-relative h-100">
        <img src="images/logo.png" class="w-100 h-100 img-fit" alt="Image 4">
      </div>
    </div>
  </div>
</div>

<style>
  .img-fit {
    object-fit: cover;
  }

  .overlay-text {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background-color: rgba(0, 0, 0, 0.5);
    padding: 8px 12px;
    border-radius: 4px;
    color: white;
    text-align: center;
  }
</style>



<!-- banner -->
<section class="marquee-section">
  <div class="container-fluid">
    <div class="offer-text-wrap">
      <ul class="list-unstyled grid-wrap">
        <li class="grid-wrapper"><div class="richtext"><p>Kicks 'n Style</p></div></li>
        <li class="grid-wrapper"><div class="richtext"><p><strong>•</strong></p></div></li>
        <li class="grid-wrapper"><div class="richtext"><p>Kicks 'n Style</p></div></li>
        <li class="grid-wrapper"><div class="richtext"><p><strong>•</strong></p></div></li>
        <li class="grid-wrapper"><div class="richtext"><p>Kicks 'n Style</p></div></li>
        <li class="grid-wrapper"><div class="richtext"><p><strong>•</strong></p></div></li>
        <li class="grid-wrapper"><div class="richtext"><p>Kicks 'n Style</p></div></li>
        <li class="grid-wrapper"><div class="richtext"><p><strong>•</strong></p></div></li>
        <li class="grid-wrapper"><div class="richtext"><p>Kicks 'n Style</p></div></li>
      </ul>
      <ul class="list-unstyled grid-wrap">
        <li class="grid-wrapper"><div class="richtext"><p><strong>•</strong></p></div></li>
        <li class="grid-wrapper"><div class="richtext"><p>Kicks 'n Style</p></div></li>
        <li class="grid-wrapper"><div class="richtext"><p><strong>•</strong></p></div></li>
        <li class="grid-wrapper"><div class="richtext"><p>Kicks 'n Style</p></div></li>
        <li class="grid-wrapper"><div class="richtext"><p><strong>•</strong></p></div></li>
        <li class="grid-wrapper"><div class="richtext"><p>Kicks 'n Style</p></div></li>
        <li class="grid-wrapper"><div class="richtext"><p><strong>•</strong></p></div></li>
        <li class="grid-wrapper"><div class="richtext"><p>Kicks 'n Style</p></div></li>
        <li class="grid-wrapper"><div class="richtext"><p><strong>•</strong></p></div></li>
        <li class="grid-wrapper"><div class="richtext"><p>Kicks 'n Style</p></div></li>        
      </ul>
    </div>
  </div>
</section>
<!-- end banner -->

<!-- Featured Products -->
<div class="container mt-5 mb-5">
  <h2 class="text-center fw-bold mb-4">Featured Products</h2>
  <div class="row g-4">
    <?php if ($featured_products->num_rows > 0): ?>
      <?php while ($product = $featured_products->fetch_assoc()): ?>
        <div class="col-6 col-md-3">
          <div class="card h-100 shadow-sm border-0">
            <img src="<?php echo $product['image']; ?>" class="card-img-top" alt="Product Image" style="height: 180px; object-fit: cover;">
            <div class="card-body d-flex flex-column">
              <h6 class="fw-bold"><?php echo htmlspecialchars($product['name']); ?></h6>
              <p class="text-muted small mb-2">₱ <?php echo number_format($product['price'], 2); ?></p>
              <p class="text-truncate mb-3"><?php echo htmlspecialchars($product['description']); ?></p>
              <a href="product.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-dark mt-auto">View Product</a>
            </div>
          </div>
        </div>
      <?php endwhile; ?>
    <?php else: ?>
      <p class="text-center text-muted">No featured products found.</p>
    <?php endif; ?>
  </div>
</div>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const alert = document.getElementById("session-alert");
    if (alert) {
        setTimeout(() => {
            let bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 4000); // alert for 4 seconds
    }
});
</script>

<?php include 'footer.php'; ?>