<?php
include 'header.php';
include 'navbar.php';
include_once("connections/connection.php");
$conn = connection();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
  echo "<div class='container mt-5'><div class='alert alert-danger'>Invalid Product ID.</div></div>";
  include 'footer.php';
  exit;
}

$product_id = (int)$_GET['id'];

$stmt = $conn->prepare("
    SELECT p.*, b.name AS brand_name 
    FROM products p
    LEFT JOIN brands b ON p.brand_id = b.id 
    WHERE p.id = ?
");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows < 1) {
  echo "<div class='container mt-5'><div class='alert alert-warning'>Product not found.</div></div>";
  include 'footer.php';
  exit;
}

$product = $result->fetch_assoc();
// Fetch size-based stocks
$size_stock = [];
$size_stmt = $conn->prepare("SELECT size, stock FROM product_sizes WHERE product_id = ?");
$size_stmt->bind_param("i", $product_id);
$size_stmt->execute();
$size_res = $size_stmt->get_result();
while ($row = $size_res->fetch_assoc()) {
  $size_stock[$row['size']] = $row['stock'];
}
$size_stmt->close();

?>

<div class="container mt-5">
  <div class="row g-4">
    <div class="col-md-6">
      <img src="<?php echo $product['image']; ?>" class="img-fluid rounded shadow" alt="Product Image">
    </div>
    <div class="col-md-6">
      <h2 class="fw-bold"><?php echo htmlspecialchars($product['name']); ?></h2>
      <p class="text-muted">Brand: <strong><?php echo htmlspecialchars($product['brand_name']); ?></strong></p>
      <p class="fw-bold m-0 fs-5">₱ <?php echo number_format($product['price'], 2); ?></p>
      <p class="text-muted" style="font-size: 14px;">Tax included. Shipping calculated at checkout.</p>
      <p class="text-muted d-flex align-items-center">
        <?php if ($product['quantity'] > 0): ?>
          <svg width="15" height="15" class="me-2" aria-hidden="true">
            <circle cx="7.5" cy="7.5" r="7.5" fill="rgba(62,214,96,0.3)"></circle>
            <circle cx="7.5" cy="7.5" r="5" stroke="rgb(255, 255, 255)" stroke-width="1" fill="rgb(62,214,96)"></circle>
          </svg>
          In Stock: <?= $product['quantity']; ?>
        <?php else: ?>
          <svg width="15" height="15" class="me-2" aria-hidden="true">
            <circle cx="7.5" cy="7.5" r="7.5" fill="rgba(255,0,0,0.3)"></circle>
            <circle cx="7.5" cy="7.5" r="5" stroke="rgb(255, 255, 255)" stroke-width="1" fill="rgb(255,0,0)"></circle>
          </svg>
          <span class="text-danger fw-bold">Out of Stock</span>
        <?php endif; ?>
      </p>
      <hr>
      <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>

      <form method="post" action="handlers/add_to_cart.php" class="mt-4">
        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">

        <!-- Sizes Section -->
        <?php if (!empty($product['sizes'])): ?>
          <div class="mb-3">
            <label class="form-label fw-bold">Sizes:</label>
            <div class="d-flex flex-wrap gap-2">
              <?php foreach (explode(',', $product['sizes']) as $size):
                $stockCount = $size_stock[$size] ?? 0;

                // Skip showing if stock = 0 (optional)
                if ($stockCount <= 0) {
                  continue; // Hide zero-stock sizes completely
                }
              ?>
                <input
                  type="radio"
                  class="btn-check"
                  name="sizes"
                  id="size_<?php echo $size; ?>"
                  value="<?php echo $size; ?>"
                  data-stock="<?= $stockCount ?>"
                  required>
                <label
                  class="btn btn-outline-dark px-3 py-2"
                  for="size_<?php echo $size; ?>">
                  <?php echo $size; ?>
                </label>
              <?php endforeach; ?>


            </div>
          </div>
        <?php endif; ?>
        <!-- End Sizes Section -->

        <div class="mb-3">
          <label for="quantity" class="form-label">Quantity</label>
          <input type="number"
            name="quantity"
            id="quantity"
            value="1"
            min="1"
            max="<?php echo $product['quantity']; ?>"
            class="form-control"
            style="max-width: 120px;"
            <?php echo ($product['quantity'] <= 0) ? 'disabled' : ''; ?>>
        </div>

        <?php if ($product['quantity'] <= 0): ?>
          <span class="text-danger fw-bold">Out of Stock</span>
        <?php else: ?>
          <button type="submit" name="add_to_cart" class="btn btn-dark">🛒 Add to Cart</button>
        <?php endif; ?>

        <a href="handlers/add_to_wishlist.php?product_id=<?= $product['id'] ?>" class="btn btn-outline-danger ms-2">❤️ Add to Wishlist</a>

        <?php if (isset($_SESSION['message'])): ?>
          <div class="alert alert-success mt-3">
            <?php
            echo $_SESSION['message'];
            unset($_SESSION['message']);
            ?>
          </div>
        <?php endif; ?>

      </form>

      <!-- Size Guide Trigger -->
      <button type="button" class="btn btn-outline-secondary mt-3" data-bs-toggle="modal" data-bs-target="#sizeGuideModal">
        📏 Size Guide
      </button>

    </div>
  </div>

  <!-- Size Guide Modal -->
  <div class="modal fade" id="sizeGuideModal" tabindex="-1" aria-labelledby="sizeGuideLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content shadow-lg border-0 rounded-3">
        <div class="modal-header bg-dark text-white">
          <h5 class="modal-title" id="sizeGuideLabel">Shoe Size Guide (Philippines Standard)</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="table-responsive">
            <table class="table table-bordered text-center align-middle">
              <thead class="table-dark">
                <tr>
                  <th>Size</th>
                  <th>US</th>
                  <th>EU</th>
                  <th>CM (Foot Length)</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>XS</td>
                  <td>5</td>
                  <td>37</td>
                  <td>23 cm</td>
                </tr>
                <tr>
                  <td>S</td>
                  <td>6–7</td>
                  <td>38–39</td>
                  <td>24–25 cm</td>
                </tr>
                <tr>
                  <td>M</td>
                  <td>8–9</td>
                  <td>40–42</td>
                  <td>26–27 cm</td>
                </tr>
                <tr>
                  <td>L</td>
                  <td>10–11</td>
                  <td>43–44</td>
                  <td>28–29 cm</td>
                </tr>
                <tr>
                  <td>XL</td>
                  <td>12–13</td>
                  <td>45–46</td>
                  <td>30–31 cm</td>
                </tr>
              </tbody>
            </table>
          </div>
          <p class="small text-muted mt-3">
            📌 Tip: Stand on a piece of paper, mark your heel and longest toe, then measure in cm.
            Compare with the chart above to pick the closest fit.
            (Philippine shoe sizes typically follow US sizing.)
          </p>
        </div>
      </div>
    </div>
  </div>
  <!-- End Size Guide Modal -->
  <!-- Tab Dropdown Additional Information of the product-->
  <div class="product-tabs mb-5 my-5">
    <div class="accordion" id="productAccordion">

      <!-- Description -->
      <div class="accordion-item border-0 border-bottom border-top">
        <h2 class="accordion-header" id="headingDesc">
          <button class="accordion-button collapsed d-flex justify-content-between align-items-center bg-white px-0"
            type="button" data-bs-toggle="collapse" data-bs-target="#collapseDesc"
            aria-expanded="false" aria-controls="collapseDesc">
            <span class="text-muted">Description</span>
            <span class="toggle-icon ms-auto text-muted">+</span>
          </button>
        </h2>
        <div id="collapseDesc" class="accordion-collapse collapse" aria-labelledby="headingDesc"
          data-bs-parent="#productAccordion">
          <div class="accordion-body px-0">

            <!-- Description Layout -->
            <div class="product-description-tab">
              <div class="description-review-text">
                <div class="product-description">
                  <div class="grid-wrap">

                    <!-- Left Banner Image -->
                    <?php
                    // Prepare product image path (DB value)
                    $dbBanner = !empty($product['image']) ? $product['image'] : '';
                    $fallback = 'images/model/default-model.png';
                    ?>
                    <!-- Left Banner Image -->
                    <div class="grid-wrapper">
                      <div class="banner-img">
                        <!-- show fallback initially, store DB image in data-src -->
                        <img id="product-banner"
                          src="<?php echo htmlspecialchars($fallback); ?>"
                          data-src="<?php echo htmlspecialchars($dbBanner); ?>"
                          alt="<?php echo htmlspecialchars($product['name']); ?>">
                      </div>
                    </div>


                    <!-- Product Specs + Washing Instructions -->
                    <div class="grid-wrapper">
                      <div class="desc-content">
                        <div class="desc-block">
                          <h6 class="sub-title">Product Specifications</h6>
                          <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                        </div>

                        <div class="desc-block">
                          <h6 class="sub-title">Washing Instructions</h6>
                          <p><i class='bx bx-wind icon-wash'></i> Machine wash cold.</p>
                          <p><i class='bx bx-no-entry icon-wash'></i> Do not bleach.</p>
                          <p><i class='bx bx-error-circle'></i> Tumble dry low.</p>
                        </div>
                      </div>
                    </div>

                    <!-- Material + Fit Info -->
                    <div class="grid-wrapper">
                      <div class="desc-content">
                        <div class="desc-block">
                          <h6 class="sub-title">Material</h6>
                          <p>Premium synthetic leather and mesh lining for durability and breathability.</p>
                        </div>

                        <div class="desc-block">
                          <h6 class="sub-title">Wearing</h6>
                          <p>Model height: 1.82m — Wearing Size M</p>
                        </div>
                      </div>
                    </div>

                  </div>
                </div>
              </div>
            </div>
            <!-- End Description Layout -->
          </div>
        </div>
      </div>
      <style>
        /* Product Description Grid Style */
        .product-description-tab {
          background-color: #fff;
          padding: 40px 0;
        }

        .description-review-text {
          max-width: 1200px;
          margin: 0 auto;
          padding: 0 20px;
        }

        .grid-wrap {
          display: grid;
          grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
          gap: 40px;
          align-items: start;
        }

        .banner-img img {
          width: 100%;
          height: 100%;
          object-fit: cover;
          border-radius: 50%;
        }

        .desc-content {
          display: flex;
          flex-direction: column;
          gap: 25px;
        }

        .desc-block {
          margin-bottom: 20px;
        }

        .sub-title {
          font-weight: 600;
          text-transform: uppercase;
          letter-spacing: 1px;
          margin-bottom: 10px;
          color: #111;
        }

        .desc-block p {
          color: #444;
          line-height: 1.6;
          margin-bottom: 8px;
          display: flex;
          align-items: center;
          gap: 8px;
        }

        .desc-block p img {
          width: 20px;
          height: 20px;
          object-fit: contain;
        }

        /* Mobile */
        @media (max-width: 768px) {
          .product-description-tab {
            padding: 20px 0;
          }

          .grid-wrap {
            gap: 20px;
          }
        }
      </style>
      <!-- Additional Information -->
      <div class="accordion-item border-0 border-bottom">
        <h2 class="accordion-header" id="headingInfo">
          <button class="accordion-button collapsed d-flex justify-content-between align-items-center bg-white px-0"
            type="button" data-bs-toggle="collapse" data-bs-target="#collapseInfo"
            aria-expanded="false" aria-controls="collapseInfo">
            <span class="text-muted">Additional Information</span>
            <span class="toggle-icon ms-auto text-muted">+</span>
          </button>
        </h2>
        <div id="collapseInfo" class="accordion-collapse collapse" aria-labelledby="headingInfo"
          data-bs-parent="#productAccordion">
          <div class="accordion-body px-0">
            <div class="table-responsive">
              <table class="table table-bordered">
                <tbody>
                  <tr>
                    <th scope="row" style="width:200px;">Brand</th>
                    <td><?php echo htmlspecialchars($product['brand_name']); ?></td>
                  </tr>
                  <tr>
                    <th scope="row">Price</th>
                    <td>₱ <?php echo number_format($product['price'], 2); ?></td>
                  </tr>
                  <tr>
                    <th scope="row">Stock</th>
                    <td><?php echo $product['quantity'] > 0 ? $product['quantity'] . ' available' : 'Out of Stock'; ?></td>
                  </tr>
                  <tr>
                    <th scope="row">Sizes</th>
                    <td>XS, S, M, L, XL</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <!-- Return Policies -->
      <div class="accordion-item border-0 border-bottom">
        <h2 class="accordion-header" id="headingReturn">
          <button class="accordion-button collapsed d-flex justify-content-between align-items-center bg-white px-0"
            type="button" data-bs-toggle="collapse" data-bs-target="#collapseReturn"
            aria-expanded="false" aria-controls="collapseReturn">
            <span class="text-muted">Return Policies</span>
            <span class="toggle-icon ms-auto text-muted">+</span>
          </button>
        </h2>
        <div id="collapseReturn" class="accordion-collapse collapse" aria-labelledby="headingReturn"
          data-bs-parent="#productAccordion">
          <div class="accordion-body px-0">
            <ul class="mb-0">
              <li>Returns are accepted within <strong>7 days</strong> of delivery.</li>
              <li>Items must be unused and in original packaging.</li>
              <li>Customer is responsible for return shipping costs.</li>
              <li>Refunds are processed within 5–7 business days.</li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- JS to switch + / - -->
  <script>
    document.querySelectorAll('#productAccordion .accordion-button').forEach(btn => {
      btn.addEventListener('click', function() {
        const icon = this.querySelector('.toggle-icon');
        const isOpen = !this.classList.contains('collapsed');
        icon.textContent = isOpen ? '−' : '+';
      });
    });
  </script>

  <style>
    .accordion-button {
      box-shadow: none !important;
      font-size: 1rem;
    }

    .accordion-button::after {
      display: none !important;
      /* Hide default Bootstrap arrow */
    }

    .toggle-icon {
      font-size: 1.5rem;
      line-height: 1;
      transition: all 0.2s;
    }
  </style>
</div>

<!-- Dynamic Product Banner Script (uses DB image if available) -->
<script>
  document.addEventListener("DOMContentLoaded", function() {
    const bannerImg = document.getElementById("product-banner");
    if (!bannerImg) return;

    // DB-stored image path (may be full path or filename)
    const dbImage = bannerImg.dataset.src && bannerImg.dataset.src.trim() !== '' ? bannerImg.dataset.src.trim() : null;

    // If no db image, nothing to change (keep fallback already in src)
    if (!dbImage) return;

    // If dbImage equals current src, nothing to do
    if (dbImage === bannerImg.getAttribute('src')) return;

    // Try preload the DB image
    const preload = new Image();
    preload.src = dbImage;

    preload.onload = () => {
      // Fade-out -> swap -> fade-in
      bannerImg.classList.add('fade-out');
      setTimeout(() => {
        bannerImg.src = dbImage;
        bannerImg.classList.remove('fade-out');
        bannerImg.classList.add('fade-in');
        // remove fade-in after animation end
        setTimeout(() => bannerImg.classList.remove('fade-in'), 450);
      }, 200);
    };

    preload.onerror = () => {
      console.warn('Product banner image not loaded:', dbImage);

      // If DB stored only a filename (no slash), try adding a folder prefix
      if (!dbImage.includes('/') && dbImage.length > 0) {
        const altPath = 'images/model/' + dbImage;
        const altPre = new Image();
        altPre.src = altPath;
        altPre.onload = () => {
          bannerImg.classList.add('fade-out');
          setTimeout(() => {
            bannerImg.src = altPath;
            bannerImg.classList.remove('fade-out');
            bannerImg.classList.add('fade-in');
            setTimeout(() => bannerImg.classList.remove('fade-in'), 450);
          }, 200);
        };
        altPre.onerror = () => {
          // keep fallback — nothing else to do
          console.warn('Fallback also failed:', altPath);
        };
      }
    };
  });
</script>
<script>
  document.addEventListener('DOMContentLoaded', function() {
    const sizeButtons = document.querySelectorAll('input[name="sizes"]');
    const stockText = document.querySelector('.text-muted.d-flex.align-items-center');
    const qtyInput = document.getElementById('quantity');

    sizeButtons.forEach(btn => {
      btn.addEventListener('change', () => {
        const stock = btn.dataset.stock;
        if (stockText) {
          stockText.innerHTML = `
          <svg width="15" height="15" class="me-2" aria-hidden="true">
            <circle cx="7.5" cy="7.5" r="7.5" fill="rgba(62,214,96,0.3)"></circle>
            <circle cx="7.5" cy="7.5" r="5" stroke="rgb(255, 255, 255)" stroke-width="1" fill="rgb(62,214,96)"></circle>
          </svg>
          In Stock: ${stock}
        `;
        }
        qtyInput.max = stock;
        qtyInput.value = stock > 0 ? 1 : 0;
        qtyInput.disabled = (stock <= 0);
      });
    });
  });
</script>





<?php include 'back_to_top.php'; ?>
<?php include 'footer.php'; ?>