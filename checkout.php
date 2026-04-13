<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
include 'header.php';
include 'navbar.php';

$user_id = $_SESSION['user_id'];
$cart_items = [];
$total_price = 0;

// Get user's cart ID
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

// Fetch user details from customer_details
$user_details = null;
$sql = "SELECT email, first_name, last_name, phone 
        FROM customer_details 
        WHERE user_id = ? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $user_details = $result->fetch_assoc();
}
$stmt->close();


// Fetch user addresses from address table
$addresses = [];
$sql = "SELECT address_id, house_street, barangay, city, province, region, postal_code, country 
        FROM address 
        WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $addresses[] = $row;
}
$stmt->close();
?>

<div class="container my-5">
    <div class="row">
        <!-- Left Column: Delivery Form -->
        <div class="col-lg-7">
            <h4 class="mb-4">Delivery Details</h4>

            <?php if (isset($_SESSION['checkout_errors'])): ?>
                <div class="alert alert-danger">
                    <ul>
                        <?php foreach ($_SESSION['checkout_errors'] as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php unset($_SESSION['checkout_errors']); ?>
            <?php endif; ?>

            <!-- ✅ Main Checkout Form -->
            <form id="checkoutForm" action="handlers/place_order.php" method="POST">
                <!-- Delivery Type -->
                <div class="mb-4">
                    <label class="form-label fw-bold">Shipping Option</label>
                    <select class="form-select" name="delivery_type" required>
                        <option value="delivery">Delivery</option>
                    </select>
                </div>

                <!-- Contact Info -->
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" name="email"
                            value="<?= $user_details ? htmlspecialchars($user_details['email']) : '' ?>"
                            class="form-control" placeholder="you@example.com" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Phone Number</label>
                        <input type="text" name="phone"
                            value="<?= $user_details ? htmlspecialchars($user_details['phone']) : '' ?>"
                            class="form-control" placeholder="09XXXXXXXXX" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">First Name</label>
                        <input type="text" name="first_name"
                            value="<?= $user_details ? htmlspecialchars($user_details['first_name']) : '' ?>"
                            class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Last Name</label>
                        <input type="text" name="last_name"
                            value="<?= $user_details ? htmlspecialchars($user_details['last_name']) : '' ?>"
                            class="form-control" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Address</label>
                        <select name="address_id" class="form-select" required onchange="handleAddressChange(this)">
                            <?php if (empty($addresses)): ?>
                                <option value="" selected disabled>No saved address. Please create one.</option>
                                <option value="create">➕ Create New Address</option>
                            <?php else: ?>
                                <option value="" selected disabled>-- Select Address --</option>
                                <?php foreach ($addresses as $addr): ?>
                                    <option value="<?= $addr['address_id'] ?>">
                                        <?= htmlspecialchars($addr['house_street'] . ', ' . $addr['barangay'] . ', ' . $addr['city']) ?>
                                    </option>
                                <?php endforeach; ?>
                                <option value="create">➕ Create New Address</option>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>

                <!-- Shipping Buttons -->
                <div class="mb-4">
                    <h5>Shipping Details</h5>
                    <label class="form-label">When would you like to get your order?</label>
                    <div class="d-flex gap-2">
                        <input type="hidden" name="shipping" id="shippingInput">

                        <button type="button" class="btn btn-outline-secondary shipping-btn active" id="freeShipping">
                            Free Shipping<br><small></small>
                        </button>

                        <button type="button" class="btn btn-outline-secondary shipping-btn" id="expressShipping">
                            Express Shipping<br><small></small>
                        </button>
                    </div>
                </div>

                <!-- Payment Method -->
                <div class="mb-4">
                    <h5>Payment Method</h5>
                    <div class="list-group">
                        <label class="list-group-item d-flex justify-content-between align-items-center">
                            <span>GCash</span>
                            <input class="form-check-input ms-2" type="radio" name="payment_method" value="GCASH" checked>
                        </label>
                        <label class="list-group-item d-flex justify-content-between align-items-center">
                            <span>Cash on Delivery</span>
                            <input class="form-check-input ms-2" type="radio" name="payment_method" value="CASH ON DELIVERY">
                        </label>
                    </div>
                </div>

                <!-- ✅ Confirmation Modal Trigger -->
                <button type="button" class="btn btn-dark btn-lg mt-3" data-bs-toggle="modal" data-bs-target="#confirmOrderModal">
                    Place Order
                </button>

                <!-- 🧾 Confirmation Modal -->
                <div class="modal fade" id="confirmOrderModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Confirm Your Order</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <p>Are you sure you want to place this order?<br>Once placed, it cannot be canceled after 1 hour.</p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Review Again</button>
                                <button type="submit" class="btn btn-success">Yes, Place Order</button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Right Column: Order Summary -->
        <div class="col-lg-4">
            <div class="position-sticky" style="top: 20px;">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="mb-3">Order Summary</h5>

                        <?php if (empty($cart_items)): ?>
                            <p>Your cart is empty.</p>
                        <?php else: ?>
                            <?php foreach ($cart_items as $item): ?>
                                <div class="d-flex mb-3">
                                    <img src="<?= htmlspecialchars($item['image']) ?>" class="me-3" style="width:80px;height:80px;object-fit:cover;">
                                    <div>
                                        <h6 class="mb-1"><?= htmlspecialchars($item['name']) ?></h6>
                                        <small>Size: <?= htmlspecialchars($item['sizes']) ?></small><br>
                                        <small>Qty: <?= $item['quantity'] ?></small>
                                        <p class="mb-0">₱<?= number_format($item['price'] * $item['quantity'], 2) ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>

                            <hr>
                            <div class="d-flex justify-content-between">
                                <span>Subtotal</span>
                                <span id="subtotal">₱<?= number_format($total_price, 2) ?></span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Delivery</span>
                                <span id="delivery-fee">Free</span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between fw-bold">
                                <span>Total</span>
                                <span id="total">₱<?= number_format($total_price, 2) ?></span>
                            </div>
                            <p class="text-muted mt-2" id="arrival-date">Arrives Day, Date - Day, Date</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function handleAddressChange(select) {
    if (select.value === "create") {
        alert("You need to add your address before checkout.");
        window.location.href = "my_account.php#address";
    }
}

// Shipping option logic (same as before)
document.querySelectorAll('.shipping-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.shipping-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        document.getElementById('shippingInput').value = this.getAttribute('data-value');
        const [fee, feeText, arrival] = this.getAttribute('data-value').split('|');
        const subtotal = <?= $total_price ?>;
        document.getElementById('delivery-fee').textContent = feeText;
        document.getElementById('total').textContent = '₱' + (subtotal + parseFloat(fee)).toLocaleString('en-PH', {minimumFractionDigits: 2});
        document.getElementById('arrival-date').textContent = 'Arrives ' + arrival;
    });
});

function formatDate(date) {
    return date.toLocaleDateString("en-US", { weekday: "long", month: "short", day: "numeric" });
}

function updateShippingOptions() {
    let today = new Date();
    let freeStart = new Date(today);
    freeStart.setDate(today.getDate());
    let freeEnd = new Date(today);
    freeEnd.setDate(today.getDate() + 6);

    document.querySelector("#freeShipping small").textContent =
        `Arrives ${formatDate(freeStart)} - ${formatDate(freeEnd)}`;
    document.querySelector("#freeShipping").setAttribute(
        "data-value", `0|Free|${formatDate(freeStart)} - ${formatDate(freeEnd)}`
    );

    let expressStart = new Date(today);
    let expressEnd = new Date(today);
    expressEnd.setDate(today.getDate() + 4);

    document.querySelector("#expressShipping small").textContent =
        `Arrives ${formatDate(expressStart)} - ${formatDate(expressEnd)}`;
    document.querySelector("#expressShipping").setAttribute(
        "data-value", `150|₱150|${formatDate(expressStart)} - ${formatDate(expressEnd)}`
    );

    document.querySelector("#shippingInput").value =
        document.querySelector("#freeShipping").getAttribute("data-value");
}

updateShippingOptions();
</script>

<?php include 'back_to_top.php'; ?>
<?php include 'footer.php'; ?>
