<!-- footer.php -->
<footer class="bg-dark text-light mt-auto py-4">
  <div class="container">
    <div class="row align-items-start">
      <!-- Stay in touch section -->
      <div class="col-md-6 mb-4">
        <h5 class="fw-bold">Stay in touch</h5>
        <form class="d-flex align-items-center border-bottom pb-2">
          <input type="email" class="form-control border-0 shadow-none" placeholder="kicksnstyles@gmail.com" style="max-width: 300px;" />
          <button type="submit" class="btn p-0 ms-2" alt="submit">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="white" class="bi bi-box-arrow-up-right" viewBox="0 0 16 16">
              <path fill-rule="evenodd" d="M6.364 2.05a.5.5 0 0 1 .707 0L13 7.979V4.5a.5.5 0 0 1 1 0v5.5a.5.5 0 0 1-.5.5H8a.5.5 0 0 1 0-1h3.479L6.05 2.757a.5.5 0 0 1 0-.707z"/>
              <path fill-rule="evenodd" d="M13.5 14a.5.5 0 0 1-.5.5H2a1 1 0 0 1-1-1V3a.5.5 0 0 1 1 0v10a.5.5 0 0 0 .5.5H13a.5.5 0 0 1 .5.5z"/>
            </svg>
          </button>
        </form>
      </div>

      <!-- Navigation links -->
      <div class="col-md-6 mb-4 text-md-end">
        <nav>
          <a href="about.php" class="text-light me-3 text-decoration-none">ABOUT US</a>
          <a href="contact.php" class="text-light me-3 text-decoration-none">CONTACT</a>
        </nav>
      </div>
    </div>

    <hr>

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center">
      <div class="mb-2 mb-md-0">
        <strong>Kicks 'N Styles</strong>
      </div>
      <div class="text-center">
        © 2025, Copy all rights reserved
      </div>
    </div>
  </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="js/newlogout.js"></script>
<script src="js/newsearch.js"></script>
<!-- Bootstrap 5.3.3 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  document.addEventListener("DOMContentLoaded", function () {
    const hash = window.location.hash;
    if (hash) {
      const tabTrigger = document.querySelector(`button[data-bs-target="${hash}"]`);
      if (tabTrigger) {
        const tab = new bootstrap.Tab(tabTrigger);
        tab.show();
      }
    }
  });
</script>
</body>
<?php include 'back_to_top.php'; ?>
</html>