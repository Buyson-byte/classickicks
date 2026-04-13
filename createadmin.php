<?php
session_start();
include_once("connections/connection.php");
include_once("middleware/adminMiddleware.php");
$conn = connection();

// Restrict access to admin only
if (!isset($_SESSION['username']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'super_admin')) {
  header("Location: login.php");
  exit;
}

// Handle Admin Creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_admin'])) {
  $email = trim($_POST['email']);
  $username = trim($_POST['username']);
  $password = trim($_POST['password']);
  $role = trim($_POST['role']);

  // Validation
  if (empty($email) || empty($username) || empty($password)) {
    $message = "⚠️ All fields are required.";
  } else {
    // Check if email or username already exists
    $check = $conn->prepare("SELECT * FROM users WHERE email = ? OR username = ?");
    $check->bind_param("ss", $email, $username);
    $check->execute();
    $exists = $check->get_result();

    if ($exists->num_rows > 0) {
      $message = "❌ Email or username already exists.";
    } else {
      // Hash password
      $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

      // Insert admin
      $stmt = $conn->prepare("INSERT INTO users (email, username, password, role) VALUES (?, ?, ?, ?)");
      $stmt->bind_param("ssss", $email, $username, $hashedPassword, $role);
      $stmt->execute();
      $message = "✅ Admin account created successfully!";
      $stmt->close();
    }
  }
}

// Fetch all admins
$admins = $conn->query("SELECT id, email, username, role FROM users WHERE role IN ('admin', 'super_admin') ORDER BY role DESC");
?>

<?php include 'header.php'; ?>

<!-- Navbar -->
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

<!-- Admin Management Section -->
<div class="container mt-5 mb-5">
  <h4 class="mb-3">👑 Manage Admin Accounts</h4>

  <?php if (isset($message)): ?>
    <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>

  <div class="row">
    <div class="col-md-5">
      <div class="card shadow-sm">
        <div class="card-body">
          <h5 class="mb-3">➕ Create New Admin</h5>
          <form method="POST">
            <div class="mb-3">
              <label class="form-label">Email</label>
              <input type="email" name="email" class="form-control" required>
            </div>

            <div class="mb-3">
              <label class="form-label">Username</label>
              <input type="text" name="username" class="form-control" required>
            </div>

            <div class="mb-3">
              <label class="form-label">Password</label>
              <input type="password" name="password" class="form-control" required>
            </div>

            <div class="mb-3">
              <label class="form-label">Role</label>
              <select name="role" class="form-select" required>
                <option value="admin">Admin</option>
                <?php if ($_SESSION['role'] === 'super_admin'): ?>
                  <option value="super_admin">Super Admin</option>
                <?php endif; ?>
              </select>
            </div>

            <button type="submit" name="create_admin" class="btn btn-dark w-100">Create Admin</button>
          </form>
        </div>
      </div>
    </div>

    <div class="col-md-7">
      <div class="card shadow-sm">
        <div class="card-body">
          <h5 class="mb-3">👥 Existing Admins</h5>
          <div class="table-responsive">
            <table class="table table-bordered align-middle">
              <thead class="table-dark">
                <tr>
                  <th>#</th>
                  <th>Email</th>
                  <th>Username</th>
                  <th>Role</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <?php $i = 1; while ($admin = $admins->fetch_assoc()): ?>
                  <tr>
                    <td><?= $i++; ?></td>
                    <td><?= htmlspecialchars($admin['email']); ?></td>
                    <td><?= htmlspecialchars($admin['username']); ?></td>
                    <td>
                      <?= ucfirst($admin['role']); ?>
                      <?php if ($_SESSION['role'] === 'super_admin' && $admin['role'] === 'admin'): ?>
                        <a href="handlers/promote_admin.php?id=<?= $admin['id']; ?>" 
                           class="btn btn-sm btn-success ms-2"
                           onclick="return confirm('Promote this admin to super admin?');">
                          Promote
                        </a>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="js/newlogout.js"></script>