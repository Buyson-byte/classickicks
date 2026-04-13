<?php
session_start();
include_once("connections/connection.php");
$conn = connection();

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$cart_items = [];
$total_price = 0;

// Get user's cart ID old_code
$stmt = $conn->prepare("SELECT id FROM carts WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($cart_id);
$stmt->fetch();
$stmt->close();


if (isset($cart_id)) {
    // Fetch cart items with product details
    $sql = "SELECT ci.id AS cart_item_id, p.id AS product_id, p.name, p.price, p.image, ci.quantity, ci.sizes
        FROM cart_items ci
        JOIN products p ON ci.product_id = p.id
        WHERE ci.cart_id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $cart_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $cart_items[] = $row;
        $total_price += $row['price'] * $row['quantity'];
    }

    $stmt->close();
}
?>

<?php include 'header.php'; ?>
<?php include 'navbar.php'; ?>

<!-- New Alert -->
<?php if (isset($_SESSION['message'])): ?>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            Swal.fire({
                icon: 'success',
                title: '<?= addslashes($_SESSION['product_name'] ?? "Cart Update"); ?>',
                text: '<?= addslashes($_SESSION['message']); ?>',
                confirmButtonColor: '#000'
            });
        });
    </script>
    <?php
    unset($_SESSION['message']);
    unset($_SESSION['product_name']);
    ?>
<?php endif; ?>

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
                <li class="breadcrumb-item active text-white" aria-current="page">Cart</li>
            </ol>
        </nav>
        <h1 class="fw-bold text-uppercase">Cart</h1>
    </div>
</div>
<!-- ===== END BREADCRUMB SECTION ===== -->


<div class="container mt-5">
    <h2>Your Shopping Cart</h2>

    <?php if (empty($cart_items)): ?>
        <p>Your cart is empty.</p>
    <?php else: ?>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Image</th>
                    <th>Price</th>
                    <th>Quantity</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cart_items as $item): ?>
                    <tr style="vertical-align: middle;">
                        <td>
                            <?= htmlspecialchars($item['name']) ?><br>
                            <small>Size: <?= htmlspecialchars($item['sizes']) ?></small>
                        </td>
                        <?php
                        $image_path = htmlspecialchars($item['image']);
                        ?>
                        <td>
                            <img src="<?= $image_path ?>" width="80" alt="Not found">
                        </td>

                        <td>₱<?= number_format($item['price'], 2) ?></td>
                        <td style="height: 100px; vertical-align: middle; text-align: center;">
                            <div class="d-flex h-100">
                                <form action="handlers/update_cart_quantity.php" method="post" class="d-inline mt-4">
                                    <input type="hidden" name="cart_item_id" value="<?= $item['cart_item_id'] ?>">
                                    <input type="hidden" name="action" value="decrease">
                                    <button type="submit" class="btn btn-sm btn-outline-secondary">−</button>
                                </form>

                                <span class="mx-2 mt-4"><?= $item['quantity'] ?></span>

                                <form action="handlers/update_cart_quantity.php" method="post" class="d-inline mt-4">
                                    <input type="hidden" name="cart_item_id" value="<?= $item['cart_item_id'] ?>">
                                    <input type="hidden" name="action" value="increase">
                                    <button type="submit" class="btn btn-sm btn-outline-secondary">+</button>
                                </form>
                            </div>
                        </td>
                        <td>₱<?= number_format($item['price'] * $item['quantity'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr>
                    <td colspan="4" class="text-end"><strong>Total:</strong></td>
                    <td colspan="2"><strong>₱<?= number_format($total_price, 2) ?></strong></td>
                </tr>
            </tbody>
        </table>

        <div class="text-end">
            <a href="checkout.php" class="btn mb-5" style="background-color: #000; color: #fff; border: none;">
                Proceed to Checkout
            </a>
        </div>

    <?php endif; ?>
</div>
<?php include 'footer.php'; ?>