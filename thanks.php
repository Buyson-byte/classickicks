<?php
session_start();
include_once("connections/connection.php");
$conn = connection();
include_once("navbar.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$order_id = $_GET['order_id'] ?? null;

if (!$order_id) {
    // Get most recent order of the logged-in user
    $stmt = $conn->prepare("SELECT id FROM orders WHERE user_id = ? ORDER BY order_date DESC LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $order_id = $stmt->get_result()->fetch_assoc()['id'] ?? null;
    $stmt->close();

    if (!$order_id) {
        echo "<p>No recent orders found.</p>";
        exit;
    }
}

// Fetch order details
$sql = "SELECT o.*, a.house_street, a.barangay, a.city, a.province, a.region, a.postal_code, a.country
        FROM orders o
        LEFT JOIN address a ON o.address_id = a.address_id
        WHERE o.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Fetch ordered items
$item_sql = "SELECT oi.*, p.name AS product_name, p.image
             FROM order_items oi
             JOIN products p ON oi.product_id = p.id
             WHERE oi.order_id = ?";
$item_stmt = $conn->prepare($item_sql);
$item_stmt->bind_param("i", $order_id);
$item_stmt->execute();
$order_items = $item_stmt->get_result();
$item_stmt->close();

// Fetch customer details
$user_sql = "SELECT u.email, c.first_name, c.last_name, c.phone
             FROM users u
             LEFT JOIN customer_details c ON u.id = c.user_id
             WHERE u.id = ?";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$customer = $user_stmt->get_result()->fetch_assoc();
$user_stmt->close();

$subtotal = 0;
?>

<?php include 'header.php'; ?>

<div class="container my-5">
    <div class="invoice-box bg-white shadow-lg p-5 rounded">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold text-uppercase">Kicks N’ Style</h2>
                <p class="mb-0">123 Mabini Street, Barangay Central, Quezon City</p>
                <p class="mb-0">Email: kicksnstyle@gmail.com</p>
                <p class="mb-0">Phone: +63 912 345 6789</p>
            </div>
            <div class="text-end">
                <h4 class="fw-bold">INVOICE</h4>
                <p class="mb-0">Order #: <?= htmlspecialchars($order_id) ?></p>
                <p class="mb-0">Date: <?= date("F d, Y", strtotime($order['order_date'])) ?></p>
            </div>
        </div>

        <hr>

        <div class="row mt-4">
            <div class="col-md-6">
                <h6 class="fw-bold">CUSTOMER DETAILS</h6>
                <p class="mb-0">
                    <?= htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']) ?><br>
                    <?= htmlspecialchars($order['house_street'] . ', ' . $order['barangay']) ?><br>
                    <?= htmlspecialchars($order['city'] . ', ' . $order['province']) ?><br>
                    <?= htmlspecialchars($order['region'] . ', ' . $order['postal_code']) ?><br>
                    <?= htmlspecialchars($order['country']) ?><br>
                    📧 <?= htmlspecialchars($customer['email']) ?><br>
                    📞 <?= htmlspecialchars($customer['phone']) ?>
                </p>
            </div>

            <div class="col-md-6 text-end">
                <h6 class="fw-bold">PAYMENT METHOD</h6>
                <p class="mb-0"><?= htmlspecialchars(ucfirst($order['payment_method'])) ?></p>

            </div>
        </div>

        <hr>

        <div class="table-responsive mt-4">
            <table class="table table-bordered align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Product</th>
                        <th class="text-center">Qty</th>
                        <th class="text-end">Unit Price</th>
                        <th class="text-end">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($item = $order_items->fetch_assoc()):
                        $line_total = $item['quantity'] * $item['price'];
                        $subtotal += $line_total;
                    ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <img src="<?= htmlspecialchars($item['image']) ?>" width="60" class="me-2 rounded" alt="">
                                    <?= htmlspecialchars($item['product_name']) ?>
                                </div>
                            </td>
                            <td class="text-center"><?= $item['quantity'] ?></td>
                            <td class="text-end">₱<?= number_format($item['price'], 2) ?></td>
                            <td class="text-end">₱<?= number_format($line_total, 2) ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div class="row mt-4">
            <div class="col-md-6"></div>
            <div class="col-md-6">
                <table class="table">
                    <tr>
                        <td class="fw-bold">Subtotal</td>
                        <td class="text-end">₱<?= number_format($subtotal, 2) ?></td>
                    </tr>
                    <tr>
                    <tr>
                        <td class="fw-bold">Shipping Type</td>
                        <td class="text-end"><?= htmlspecialchars($order['shipping_type']) ?></td>
                    </tr>
                    <tr>
                        <td class="fw-bold">Shipping Fee</td>
                        <td class="text-end">₱<?= number_format($order['shipping_fee'], 2) ?></td>
                    </tr>

                    </tr>
                    <tr>
                        <td class="fw-bold fs-5">Total</td>
                        <td class="text-end fw-bold fs-5">₱<?= number_format($order['total'], 2) ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="text-center mt-4">
            <h5 class="fw-bold text-dark mt-4">THANK YOU FOR SHOPPING WITH US!</h5>
            <button id="printBtn" class="btn btn-outline-dark mt-3" onclick="window.print()">🖨️ Print Invoice</button>
            <a href="shop.php" class="btn btn-dark mt-3">Return to Shop</a>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>



<style>
    @media print {

        #printBtn,
        .btn-dark,
        footer,
        header {
            display: none !important;
        }

        body {
            background: white;
        }

        .invoice-box {
            box-shadow: none !important;
            margin: 0;
            padding: 0;
        }
    }
</style>