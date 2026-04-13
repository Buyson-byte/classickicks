<?php
session_start();
include 'header.php';
include 'navbar.php';
include_once("connections/connection.php");
$conn = connection();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

if ($order_id <= 0) {
    die("<h3 style='text-align:center;margin-top:50px;'>Invalid Order ID.</h3>");
}

// ✅ Fetch order timeline from database
$sql = "SELECT * FROM order_timeline WHERE order_id = ? ORDER BY created_at ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();
$timeline = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

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
                <li class="breadcrumb-item"><a href="home.php" class="text-decoration-none text-white">Home</a></li>
                <li class="breadcrumb-item"><a href="my_account.php" class="text-decoration-none text-white">My Account</a></li>
                <li class="breadcrumb-item active text-white" aria-current="page">Track Order #<?php echo $order_id; ?></li>
            </ol>
        </nav>
        <h1 class="fw-bold text-uppercase">Order</h1>
    </div>
</div>
<!-- ===== END BREADCRUMB SECTION ===== -->

<!-- Amazon-style Tracking Section -->
<div class="tracking-wrapper">
    <h2>Order Tracking #<?php echo $order_id; ?></h2>

    <?php if (!empty($timeline)): ?>
        <div class="progressbar">
            <?php
            $statuses = array_column($timeline, 'status');
            $steps = ['Order Placed', 'Packed', 'Shipped', 'Delivered'];
            foreach ($steps as $step):
                $isActive = in_array($step, $statuses);
            ?>
                <div class="step <?php echo $isActive ? 'active' : ''; ?>">
                    <p><?php echo htmlspecialchars($step); ?></p>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="timeline-details">
            <?php foreach ($timeline as $row): ?>
                <div class="timeline-item">
                    <h4><?php echo htmlspecialchars($row['status']); ?></h4>
                    <p><?php echo htmlspecialchars($row['message']); ?></p>
                    <small style="color:#999;">
                        <?php echo date("M d, Y h:i A", strtotime($row['created_at'])); ?>
                    </small>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p style="text-align:center;color:#666;">No tracking updates available yet.</p>
    <?php endif; ?>
</div>

<style>
    body {
        font-family: 'Poppins', sans-serif;
        background-color: #f8f9fa;
        margin: 0;
        padding: 0;
    }

    .tracking-wrapper {
        max-width: 800px;
        background: #fff;
        margin: 60px auto;
        padding: 40px;
        border-radius: 10px;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    }

    h2 {
        text-align: center;
        font-weight: 600;
        color: #333;
        margin-bottom: 40px;
    }

    .progressbar {
        counter-reset: step;
        display: flex;
        justify-content: space-between;
        position: relative;
        margin-bottom: 50px;
    }

    .progressbar::before {
        content: "";
        position: absolute;
        top: 50%;
        left: 50px;
        right: 50px;
        transform: translateY(-50%);
        height: 5px;
        background: #dcdcdc;
        z-index: 0;
    }

    .step {
        position: relative;
        z-index: 1;
        text-align: center;
        flex: 1;
    }

    .step::before {
        counter-increment: step;
        content: counter(step);
        width: 40px;
        height: 40px;
        line-height: 40px;
        display: block;
        background: #dcdcdc;
        border-radius: 50%;
        margin: 0 auto 10px;
        font-weight: bold;
        color: #555;
        transition: 0.3s;
    }

    .step.active::before {
        background: #28a745;
        color: #fff;
    }

    .step.active p {
        color: #28a745;
        font-weight: 600;
    }

    .step p {
        margin: 0;
        color: #777;
        font-size: 14px;
    }

    .timeline-details {
        margin-top: 40px;
    }

    .timeline-item {
        margin-bottom: 25px;
        padding: 15px 20px;
        border-left: 4px solid #28a745;
        background: #f9f9f9;
        border-radius: 5px;
    }

    .timeline-item h4 {
        margin: 0 0 5px;
        color: #333;
    }

    .timeline-item p {
        margin: 0;
        color: #666;
        font-size: 14px;
    }
</style>

<?php include 'footer.php'; ?>