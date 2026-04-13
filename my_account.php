<?php
session_start();
include_once("connections/connection.php");
$conn = connection();

// 🔒 Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Handle username update
if (isset($_POST['update_username'])) {
    $new_username = trim($_POST['username']);

    if (!empty($new_username)) {
        $check_sql = "SELECT id FROM users WHERE username = ? AND id != ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("si", $new_username, $user_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $_SESSION['message'] = "Username already taken.";
            $_SESSION['message_class'] = "alert-danger";
        } else {
            $update_sql = "UPDATE users SET username = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("si", $new_username, $user_id);
            if ($update_stmt->execute()) {
                $_SESSION['username'] = $new_username;
                $_SESSION['message'] = "Username updated successfully!";
                $_SESSION['message_class'] = "alert-success";
            }
        }
    }
    header("Location: my_account.php");
    exit();
}

// Handle password update
if (isset($_POST['update_password'])) {
    $current_pass = $_POST['current_password'];
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];

    $check_sql = "SELECT password FROM users WHERE id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $user_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $row = $result->fetch_assoc();

    if ($row && password_verify($current_pass, $row['password'])) {
        if ($new_pass === $confirm_pass) {
            $hashed_pass = password_hash($new_pass, PASSWORD_DEFAULT);
            $update_sql = "UPDATE users SET password = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("si", $hashed_pass, $user_id);
            if ($update_stmt->execute()) {
                $_SESSION['message'] = "Password updated successfully!";
                $_SESSION['message_class'] = "alert-success";
            }
        } else {
            $_SESSION['message'] = "New passwords do not match.";
            $_SESSION['message_class'] = "alert-danger";
        }
    } else {
        $_SESSION['message'] = "Current password is incorrect.";
        $_SESSION['message_class'] = "alert-danger";
    }

    header("Location: my_account.php");
    exit();
}

// Fetch current user info (users table)
$sql = "SELECT email, username FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Fetch customer details (customer_details table)
$customer = null;
$details_sql = "SELECT email, first_name, last_name, phone
                FROM customer_details WHERE user_id = ?";
$details_stmt = $conn->prepare($details_sql);
$details_stmt->bind_param("i", $user_id);
$details_stmt->execute();
$details_result = $details_stmt->get_result();
if ($details_result->num_rows > 0) {
    $customer = $details_result->fetch_assoc();
}

// ✅ Flash message display
$message = "";
$message_class = "";
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_class = $_SESSION['message_class'] ?? "alert-info"; // fallback class
    unset($_SESSION['message'], $_SESSION['message_class']);
}

// Now load header + navbar
include 'header.php';
include 'navbar.php';
?>
<?php if (!empty($message)): ?>
    <div class="alert <?= htmlspecialchars($message_class) ?> alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="container mt-5 mb-5">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3">
            <h2 class="fw-bold mb-4">My Account</h2>

            <div class="d-flex align-items-center mb-4">
                <img src="https://cdn-icons-png.flaticon.com/512/616/616408.png"
                    alt="User Icon" width="50" class="me-2">
                <span class="fw-bold fs-5"><?= htmlspecialchars($user['username']) ?></span>
            </div>

            <div class="nav flex-column nav-pills gap-2" role="tablist">
                <button class="btn btn-outline-dark text-start active"
                    data-bs-toggle="tab" data-bs-target="#account" type="button">
                    Account
                </button>
                <button class="btn btn-outline-dark text-start"
                    data-bs-toggle="tab" data-bs-target="#orders" type="button">
                    Orders
                </button>
                <button class="btn btn-outline-dark text-start"
                    data-bs-toggle="tab" data-bs-target="#orderNotifications" type="button">
                    Order Notifications
                </button>

                <a class="btn btn-danger text-start" id="logoutBtn" href="#">Logout</a>
            </div>
        </div>

        <!-- Content -->
        <div class="col-md-9">
            <div class="tab-content">

                <!-- Account Tab -->
                <div class="tab-pane fade show active" id="account">
                    <!-- Personal Info Card -->
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Personal Information</h5>
                            <button class="btn btn-sm btn-dark" data-bs-toggle="modal" data-bs-target="#editAccountModal">
                                Edit
                            </button>
                        </div>
                        <div class="card-body row g-3">
                            <div class="col-md-6"><strong>First Name:</strong> <?= htmlspecialchars($customer['first_name'] ?? '') ?></div>
                            <div class="col-md-6"><strong>Last Name:</strong> <?= htmlspecialchars($customer['last_name'] ?? '') ?></div>
                            <div class="col-md-6"><strong>Email:</strong> <?= htmlspecialchars($customer['email'] ?? $user['email']) ?></div>
                            <div class="col-md-6"><strong>Phone:</strong><span>&nbsp;+63</span> <?= htmlspecialchars($customer['phone'] ?? '') ?></div>
                            <div class="col-12 mt-3">
                                <button class="btn btn-dark me-2" data-bs-toggle="modal" data-bs-target="#changeUsernameModal">
                                    Change Username
                                </button>
                                <button class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                                    Change Password
                                </button>
                            </div>
                        </div>
                    </div>

                    <?php
                    // Fetch all addresses of user (address table)
                    $addresses_sql = "SELECT * FROM address WHERE user_id = ? ORDER BY is_default DESC, created_at ASC";
                    $addresses_stmt = $conn->prepare($addresses_sql);
                    $addresses_stmt->bind_param("i", $user_id);
                    $addresses_stmt->execute();
                    $addresses_result = $addresses_stmt->get_result();
                    ?>

                    <!-- Address Section -->
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Address Information</h5>
                            <button class="btn btn-sm btn-dark" data-bs-toggle="modal" data-bs-target="#addAddressModal">
                                + Add New Address
                            </button>
                        </div>
                        <div class="card-body">
                            <?php if ($addresses_result->num_rows > 0): ?>
                                <div class="row g-3">
                                    <?php while ($addr = $addresses_result->fetch_assoc()): ?>
                                        <div class="col-md-6">
                                            <div class="border rounded p-3 h-100">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div>
                                                        <h6 class="fw-bold mb-1"><?= htmlspecialchars($addr['address_name']) ?></h6>
                                                        <p class="mb-1 small">
                                                            <?= htmlspecialchars($addr['house_street']) ?><br>
                                                            <?= htmlspecialchars($addr['barangay']) ?>, <?= htmlspecialchars($addr['city']) ?><br>
                                                            <?= htmlspecialchars($addr['province']) ?>, <?= htmlspecialchars($addr['region']) ?><br>
                                                            <?= htmlspecialchars($addr['postal_code']) ?>, <?= htmlspecialchars($addr['country']) ?>
                                                        </p>
                                                        <?php if ($addr['is_default']): ?>
                                                            <span class="badge bg-primary">Default</span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div>
                                                        <button class="btn btn-sm btn-dark"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#editAddressModal<?= $addr['address_id'] ?>">
                                                            Edit
                                                        </button>

                                                        <!-- Delete Form -->
                                                        <form method="POST" action="handlers/delete_address.php" class="d-inline">
                                                            <input type="hidden" name="address_id" value="<?= $addr['address_id'] ?>">
                                                            <button type="submit" name="delete_address" class="btn btn-sm btn-danger"
                                                                onclick="return confirm('Are you sure you want to delete this address?')">
                                                                Delete
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Edit Address Modal for this address -->
                                        <div class="modal fade" id="editAddressModal<?= $addr['address_id'] ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-lg modal-dialog-centered">
                                                <div class="modal-content">
                                                    <form method="POST" action="handlers/update_address.php">
                                                        <input type="hidden" name="address_id" value="<?= $addr['address_id'] ?>">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Edit Address (<?= htmlspecialchars($addr['address_name']) ?>)</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body row g-3">
                                                            <div class="col-md-6">
                                                                <label class="form-label">Address Name</label>
                                                                <input type="text" name="address_name" class="form-control"
                                                                    value="<?= htmlspecialchars($addr['address_name']) ?>" required>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label class="form-label">House / Street</label>
                                                                <input type="text" name="house_street" class="form-control"
                                                                    value="<?= htmlspecialchars($addr['house_street']) ?>" required>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label class="form-label">Barangay</label>
                                                                <input type="text" name="barangay" class="form-control"
                                                                    value="<?= htmlspecialchars($addr['barangay']) ?>" required>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label class="form-label">City</label>
                                                                <input type="text" name="city" class="form-control"
                                                                    value="<?= htmlspecialchars($addr['city']) ?>" required>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label class="form-label">Province</label>
                                                                <input type="text" name="province" class="form-control"
                                                                    value="<?= htmlspecialchars($addr['province']) ?>" required>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label class="form-label">Region</label>
                                                                <input type="text" name="region" class="form-control"
                                                                    value="<?= htmlspecialchars($addr['region']) ?>" required>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label for="postal">Postal Code</label>
                                                                <input id="postal" name="postal_code" class="form-control"
                                                                    type="number"
                                                                    min="0" max="9999"
                                                                    oninput="if(this.value.length > 4) this.value = this.value.slice(0,4);"
                                                                    value="<?= htmlspecialchars($addr['postal_code']) ?>"
                                                                    placeholder="e.g., 1100"
                                                                    required>
                                                            </div>
                                                            <div class="col-md-6 d-flex align-items-center">
                                                                <div class="form-check mt-4">
                                                                    <input type="checkbox" class="form-check-input" name="is_default" value="1"
                                                                        <?= $addr['is_default'] ? 'checked' : '' ?>>
                                                                    <label class="form-check-label">Set as Default</label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" name="update_address" class="btn btn-primary">Save Changes</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-muted">You haven't added any addresses yet.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Add New Address Modal -->
                    <div class="modal fade" id="addAddressModal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-lg modal-dialog-centered">
                            <div class="modal-content">
                                <form method="POST" action="handlers/add_address.php">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Add New Address</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Address Name</label>
                                            <input type="text" name="address_name" class="form-control" placeholder="Home / Office / Parents" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">House / Street</label>
                                            <input type="text" name="house_street" class="form-control" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Barangay</label>
                                            <input type="text" name="barangay" class="form-control" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">City</label>
                                            <input type="text" name="city" class="form-control" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Province</label>
                                            <input type="text" name="province" class="form-control" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Region</label>
                                            <input type="text" name="region" class="form-control" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="postal">Postal Code</label>
                                            <input id="postal" name="postal_code" class="form-control"
                                                type="number"
                                                min="0" max="9999"
                                                oninput="if(this.value.length > 4) this.value = this.value.slice(0,4);"
                                                placeholder="e.g., 1100"
                                                required>
                                        </div>
                                        <div class="col-md-6 d-flex align-items-center">
                                            <div class="form-check mt-4">
                                                <input type="checkbox" class="form-check-input" name="is_default" value="1">
                                                <label class="form-check-label">Set as Default</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" name="add_address" class="btn btn-success">Save Address</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Change Username Modal -->
                <div class="modal fade" id="changeUsernameModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <form method="POST" action="my_account.php">
                                <div class="modal-header">
                                    <h5 class="modal-title">Change Username</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <label class="form-label">New Username</label>
                                    <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($user['username']) ?>" required>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" name="update_username" class="btn btn-primary">Save Username</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Change Password Modal -->
                <div class="modal fade" id="changePasswordModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <form method="POST" action="my_account.php">
                                <div class="modal-header">
                                    <h5 class="modal-title">Change Password</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <label class="form-label">Current Password</label>
                                    <input type="password" name="current_password" class="form-control mb-3" required>

                                    <label class="form-label">New Password</label>
                                    <input type="password" name="new_password" class="form-control mb-3" required>

                                    <label class="form-label">Confirm New Password</label>
                                    <input type="password" name="confirm_password" class="form-control" required>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" name="update_password" class="btn btn-primary">Save Password</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Orders Tab -->
                <?php
                // Fetch user orders along with delivery address
                $sql = "SELECT 
    o.id AS order_id, o.order_date, o.total, o.status, o.address_id,
    oi.quantity, oi.price, oi.size,
    p.name AS product_name, p.image,
    a.house_street, a.barangay, a.city, a.province, a.region, a.postal_code, a.country
FROM orders o
LEFT JOIN order_items oi ON o.id = oi.order_id
LEFT JOIN products p ON oi.product_id = p.id
LEFT JOIN address a ON o.address_id = a.address_id
WHERE o.user_id = ?
ORDER BY o.order_date DESC";

                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();

                $orders = [];
                while ($row = $result->fetch_assoc()) {
                    $order_id = $row['order_id'];
                    $orders[$order_id]['order_date'] = $row['order_date'];
                    $orders[$order_id]['total']      = $row['total'];
                    $orders[$order_id]['status']     = $row['status'];

                    // Save address info separately
                    $orders[$order_id]['address'] = [
                        'house_street' => $row['house_street'],
                        'barangay'     => $row['barangay'],
                        'city'         => $row['city'],
                        'province'     => $row['province'],
                        'region'       => $row['region'],
                        'postal_code'  => $row['postal_code'],
                        'country'      => $row['country']
                    ];

                    // Save order items
                    $orders[$order_id]['items'][] = [
                        'product_name' => $row['product_name'],
                        'image'        => $row['image'],
                        'quantity'     => $row['quantity'],
                        'price'        => $row['price'],
                        'size'         => $row['size']
                    ];
                }
                ?>

                <!-- Orders Information Tab -->

                <div class="tab-pane fade" id="orders">
                    <div class="card shadow-sm border-0">
                        <div class="card-body">
                            <h5 class="fw-bold mb-3">My Orders</h5>

                            <?php if (!empty($orders)): ?>
                                <?php foreach ($orders as $order_id => $order): ?>
                                    <div class="border rounded p-3 mb-4 bg-light position-relative">
                                        <!-- Delete button at top-right -->
                                        <div class="position-absolute" style="top:10px; right:10px;">
                                            <!-- Delete Order -->
                                            <form method="POST" action="handlers/delete_order.php" class="d-inline">
                                                <input type="hidden" name="order_id" value="<?= $order_id ?>">
                                                <button type="submit" class="btn btn-sm btn-danger delete-order-btn"
                                                    onclick="return confirm('Are you sure you want to delete this order receipt?')">
                                                    Delete
                                                </button>
                                            </form>

                                            <!-- Cancel Order -->
                                            <?php
                                            $order_time = strtotime($order['order_date']);
                                            $time_diff = time() - $order_time;
                                            $can_cancel = ($time_diff <= 3600) && ($order['status'] == 'Pending'); // within 1 hour and still pending
                                            ?>

                                            <?php if ($can_cancel): ?>
                                                <form method="POST" action="handlers/cancel_order.php" class="d-inline">
                                                    <input type="hidden" name="order_id" value="<?= $order_id ?>">
                                                    <button type="submit" class="btn btn-sm btn-dark"
                                                        onclick="return confirm('Are you sure you want to cancel this order?')">
                                                        Cancel Order
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>


                                        <h6 class="mb-1">Order #: <?= $order_id ?></h6>
                                        <h6 class="text-muted small mb-1">Status:<span class="badge <?php
                                                                                                    if ($order['status'] === 'Canceled') echo 'bg-danger';
                                                                                                    elseif ($order['status'] === 'Pending') echo 'bg-warning text-dark';
                                                                                                    else echo 'bg-success';
                                                                                                    ?>"><?= ucfirst($order['status']) ?></span></h6>

                                        <p class="text-muted small mb-1">
                                            Date: <?= date("M d, Y", strtotime($order['order_date'])) ?>
                                        </p>
                                        <?php if (!empty($order['address']['house_street'])): ?>
                                            <p class="text-muted small mb-1">
                                                Delivered To: <?= htmlspecialchars(
                                                                    $order['address']['house_street'] . ', ' .
                                                                        $order['address']['barangay'] . ', ' .
                                                                        $order['address']['city'] . ', ' .
                                                                        $order['address']['province'] . ', ' .
                                                                        $order['address']['region'] . ', ' .
                                                                        $order['address']['postal_code'] . ', ' .
                                                                        $order['address']['country']
                                                                ) ?>
                                            </p>
                                        <?php endif; ?>
                                        <p class="fw-bold mb-3">Total: ₱ <?= number_format($order['total'], 2) ?></p>

                                        <!-- 🧾 View Receipt Button -->
                                        <a href="thanks.php?order_id=<?= $order_id ?>"
                                            class="btn btn-primary btn-sm mb-3"
                                            target="_self">
                                            🧾 View Receipt
                                        </a>


                                        <div class="row g-3">
                                            <?php foreach ($order['items'] as $item): ?>
                                                <div class="col-md-6 d-flex align-items-center border rounded p-2 bg-white">
                                                    <img src="/softeng/<?= htmlspecialchars($item['image']) ?>"
                                                        alt="<?= htmlspecialchars($item['product_name']) ?>"
                                                        class="me-2" style="width:60px;height:60px;object-fit:cover;border-radius:8px;">
                                                    <div>
                                                        <p class="mb-1 fw-bold"><?= htmlspecialchars($item['product_name']) ?></p>
                                                        <small>
                                                            Qty: <?= $item['quantity'] ?> × ₱ <?= number_format($item['price'], 2) ?><br>
                                                            Size: <?= htmlspecialchars($item['size']) ?>
                                                        </small>

                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-muted">You haven’t placed any orders yet.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <!-- Order Notifications Tab -->
                <div class="tab-pane fade" id="orderNotifications" role="tabpanel">
                    <div class="card shadow-sm border-0">
                        <div class="card-body">
                            <h5 class="fw-bold mb-3">Order Notifications & Tracking</h5>
                            <?php
                            // Fetch orders for notifications
                            $notif_sql = "SELECT id AS order_id, order_date, status, total 
                          FROM orders 
                          WHERE user_id = ? 
                          ORDER BY order_date DESC";
                            $notif_stmt = $conn->prepare($notif_sql);
                            $notif_stmt->bind_param("i", $user_id);
                            $notif_stmt->execute();
                            $notif_result = $notif_stmt->get_result();

                            if ($notif_result->num_rows > 0):
                                while ($order = $notif_result->fetch_assoc()):
                            ?>
                                    <div class="card mb-3 shadow-sm p-3">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-1 fw-bold">Order #<?= htmlspecialchars($order['order_id']) ?></h6>
                                                <p class="mb-1 text-muted small">
                                                    Date: <?= date("M d, Y", strtotime($order['order_date'])) ?>
                                                </p>
                                                <p class="mb-1">
                                                    Status:
                                                    <strong>
                                                        <span class="<?php
                                                                        echo ($order['status'] === 'Canceled') ? 'text-danger' : (($order['status'] === 'Pending') ? 'text-warning' : 'text-success');
                                                                        ?>">
                                                            <?= htmlspecialchars(ucfirst($order['status'])) ?>
                                                        </span>
                                                    </strong>
                                                </p>
                                                <p class="mb-0 fw-bold">Total: ₱ <?= number_format($order['total'], 2) ?></p>
                                            </div>
                                            <div>
                                                <a href="order_tracking.php?order_id=<?= urlencode($order['order_id']) ?>"
                                                    class="btn btn-sm btn-primary">Track Order</a>
                                            </div>
                                        </div>
                                    </div>
                            <?php
                                endwhile;
                            else:
                                echo '<p class="text-muted">No orders found.</p>';
                            endif;
                            ?>
                        </div>
                    </div>
                </div>




            </div>
        </div>
    </div>

    <!-- Edit Account Modal -->
    <div class="modal fade" id="editAccountModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="handlers/update_account.php">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Personal Information</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body row g-3">
                        <div class="col-md-6">
                            <label class="form-label">First Name</label>
                            <input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($customer['first_name'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Last Name</label>
                            <input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($customer['last_name'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($customer['email'] ?? $user['email']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <div class="input-group mb-3">
                                <div class="input-group-prepend">
                                    <span class="input-group-text" id="basic-addon1">+63</span>
                                </div>
                                <input id="postal" name="phone" class="form-control"
                                    type="number"
                                    min="0" max="9999999999"
                                    oninput="if(this.value.length > 10) this.value = this.value.slice(0,10);"
                                    value="<?= htmlspecialchars($customer['phone'] ?? '') ?>"
                                    required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_account" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Auto dismiss alerts -->
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const alert = document.querySelector(".alert");
            if (alert) {
                setTimeout(() => {
                    alert.classList.remove("show");
                    alert.classList.add("fade");
                    setTimeout(() => alert.remove(), 500);
                }, 2000);
            }
        });
    </script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Restore last active tab
            const lastTab = localStorage.getItem("activeTab");
            if (lastTab) {
                const tabTrigger = document.querySelector(`[data-bs-target="${lastTab}"]`);
                if (tabTrigger) {
                    new bootstrap.Tab(tabTrigger).show();
                }
            }

            // Save tab on click
            const tabButtons = document.querySelectorAll('[data-bs-toggle="tab"]');
            tabButtons.forEach(btn => {
                btn.addEventListener("shown.bs.tab", function(e) {
                    const target = e.target.getAttribute("data-bs-target");
                    localStorage.setItem("activeTab", target);
                });
            });
        });
    </script>
</div>


<?php include 'footer.php'; ?>