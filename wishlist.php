<?php
session_start();
include_once("connections/connection.php");
$conn = connection();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$sql = "SELECT w.id AS wishlist_id, p.* 
        FROM wishlist w
        JOIN products p ON w.product_id = p.id
        WHERE w.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<?php include 'header.php'; ?>
<?php include 'navbar.php'; ?>

<style>
  .breadcrumb {
    --bs-breadcrumb-divider-color: white !important;
  }
  .breadcrumb-item + .breadcrumb-item::before {
    color: white !important;
  }
  .breadcrumb a {
    color: white;
  }
  .breadcrumb a:hover {
    text-decoration: underline;
    color: #f8f9fa; /* lighter hover */
  }
</style>


<!-- ===== BREADCRUMB SECTION ===== -->
<div class="breadcrumb-container position-relative text-center text-white d-flex align-items-center justify-content-center"
     style="background-image: url('images/bread.png'); background-size: cover; background-position: center; height: 150px;">
    <div class="overlay position-absolute top-0 start-0 w-100 h-100" style="background: rgba(0,0,0,0.4);"></div>

    <div class="position-relative">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb justify-content-center mb-2">
                <li class="breadcrumb-item"><a href="shop.php" class="text-white text-decoration-none">Shop</a></li>
                <li class="breadcrumb-item active text-white" aria-current="page">Wishlist</li>
            </ol>
        </nav>
        <h1 class="fw-bold text-uppercase">Wishlist</h1>
    </div>
</div>
<!-- ===== END BREADCRUMB SECTION ===== -->

<div class="container mt-5">
    <h2>Your Wishlist</h2>

    <?php if ($result->num_rows > 0): ?>
        <div class="row g-4">
            <?php while ($item = $result->fetch_assoc()): ?>
                <div class="col-6 col-md-3 d-flex">
                    <div class="card h-100 shadow-sm border-0 flex-fill">
                        <img src="<?= htmlspecialchars($item['image']) ?>"
                            class="card-img-top"
                            style="height: 180px; object-fit: cover;">
                        <div class="card-body d-flex flex-column">
                            <h6 class="fw-bold"><?= htmlspecialchars($item['name']) ?></h6>
                            <p class="text-muted small mb-2">₱ <?= number_format($item['price'], 2) ?></p>
                            <a href="product.php?id=<?= $item['id'] ?>" class="btn btn-sm btn-dark">View</a>

                            <?php if ($item['quantity'] <= 0): ?>
                                <span class="text-danger fw-bold mt-2 text-center">Out of Stock</span>
                            <?php else: ?>

                                <form method="post" action="handlers/add_to_cart.php" class="w-100 mt-2">
                                    <input type="hidden" name="product_id" value="<?= $item['id'] ?>">
                                    <input type="hidden" name="quantity" value="1">
                                    <button type="submit" name="add_to_cart" class="btn btn-sm w-100" 
                                            style="background-color:#212529; color:#fff; border:none;">
                                        Add to Cart
                                    </button>

                                </form>
                            <?php endif; ?>

                            <a href="handlers/remove_from_wishlist.php?id=<?= $item['wishlist_id'] ?>"
                                class="btn btn-sm btn-outline-danger mt-2"
                                onclick="return confirm('Remove this item from wishlist?')">
                                ❌ Remove
                            </a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <p>Your wishlist is empty.</p>
    <?php endif; ?>
</div>
<!-- we forgot back-to-top -->
<?php include 'footer.php'; ?>