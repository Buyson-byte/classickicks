<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include_once("connections/connection.php");
$conn = connection();

$cart_count = 0;
$wishlist_count = 0;

// Cart Count
if (isset($_SESSION['user_id'])) {
  $user_id = $_SESSION['user_id'];

  // CART
  $cart_sql = $conn->prepare("SELECT id FROM carts WHERE user_id = ?");
  $cart_sql->bind_param("i", $user_id);
  $cart_sql->execute();
  $cart_result = $cart_sql->get_result();

  if ($cart_result->num_rows > 0) {
    $cart_id = $cart_result->fetch_assoc()['id'];
    $count_sql = $conn->prepare("SELECT SUM(quantity) AS total_items FROM cart_items WHERE cart_id = ?");
    $count_sql->bind_param("i", $cart_id);
    $count_sql->execute();
    $count_result = $count_sql->get_result();
    $cart_count = $count_result->fetch_assoc()['total_items'] ?? 0;
  }

  // WISHLIST
  $wish_sql = $conn->prepare("SELECT COUNT(*) AS total_items FROM wishlist WHERE user_id = ?");
  $wish_sql->bind_param("i", $user_id);
  $wish_sql->execute();
  $wish_result = $wish_sql->get_result();
  $wishlist_count = $wish_result->fetch_assoc()['total_items'] ?? 0;
}
?>
<style>
  /* Dropdown container */
  #searchResults {
    position: absolute;
    /* already set */
    top: 100%;
    /* start right below the input */
    left: 0;
    width: 100%;
    margin-top: 5px;
    /* extra spacing between input and dropdown */
    max-height: 300px;
    overflow-y: auto;
    border-radius: 5px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    opacity: 0;
    transform: translateY(5px);
    transition: all 0.2s ease;
    z-index: 1000;
  }

  #searchResults.show {
    opacity: 1;
    transform: translateY(0);
  }

  #searchResults a.search-item {
    display: flex;
    align-items: center;
    padding: 8px 10px;
    gap: 10px;
    text-decoration: none;
    color: #333;
    transition: background 0.2s;
  }

  #searchResults a.search-item:hover {
    background-color: #f0f0f0;
    border-radius: 5px;
  }

  #searchResults img.search-img {
    width: 40px;
    height: 40px;
    object-fit: cover;
    border-radius: 5px;
  }
  /* Minimal Search Bar (collapsed state) */
  #searchInput {
    width: 160px;                /* smaller by default */
    font-size: 1rem;
    padding: 0.4rem 0.6rem;
    transition: all 0.3s ease-in-out;
  }

  /* Expanded Search Bar (when clicked/focused) */
  #searchInput:focus {
    width: 770px;                /* expand smoothly */
    font-size: 1.1rem;
    padding: 0.6rem 1rem;
    box-shadow: 0 0 6px rgba(0,0,0,0.15);
  }
</style>
<nav class="navbar navbar-expand-lg navbar-dark navbar-custom">
  <div class="container-fluid">
    <a class="navbar-brand d-flex align-items-center gap-2" href="index.php">
      <img src="images/newlogo.png" alt="Logo" style="height: 60px; width: 80px;">
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarMain">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item"><a class="nav-link" href="index.php">HOME</a></li>
        <li class="nav-item"><a class="nav-link" href="shop.php">SHOP</a></li>
      </ul>

    <!-- Search Bar -->
      <form id="searchForm" class="d-flex me-4" role="search" autocomplete="off">
        <div class="input-group position-relative">
          <span class="input-group-text bg-white border-end-0">
            <i class="ri-search-line"></i>
          </span>
          <input 
            id="searchInput" 
            class="form-control search-input border-start-0" 
            type="search" 
            placeholder="Search..." 
            aria-label="Search"
          >
          <div id="searchResults" class="position-absolute bg-white border w-100 mt-1" style="display:none; z-index: 1000;"></div>
        </div>
      </form>

      <div class="d-flex align-items-center gap-3">
        <?php if (isset($_SESSION['user_id'])): ?>
          <!-- Wishlist -->
          <a href="wishlist.php" class="btn btn-outline-light position-relative">
            ❤️
            <?php if ($wishlist_count > 0): ?>
              <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                <?= $wishlist_count ?>
              </span>
            <?php endif; ?>
          </a>

          <!-- Cart -->
          <a href="cart.php" class="btn btn-outline-light position-relative">
            🛒
            <?php if ($cart_count > 0): ?>
              <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                <?= $cart_count ?>
              </span>
            <?php endif; ?>
          </a>
          <!-- Profile Dropdown -->
          <div class="dropdown">
            <button class="btn btn-outline-light dropdown-toggle position-relative" type="button" id="profileDropdown"
              data-bs-toggle="dropdown" aria-expanded="false">
              🐵
            </button>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
              <li><a class="dropdown-item" href="my_account.php">My Account</a></li>
              <li>
                <hr class="dropdown-divider">
              </li>
              <li><a id="logoutBtn" class="dropdown-item text-danger" href="#">Logout</a></li>
            </ul>
          </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['username'])): ?>
          <span class="text-white d-none d-lg-block">
            Welcome, <?= htmlspecialchars($_SESSION['username']) ?>
          </span>
        <?php else: ?>
          <a href="login.php" class="btn btn-light btn-sm">Login</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>
