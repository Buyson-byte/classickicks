<?php include 'header.php'; ?>
<?php include 'navbar.php'; ?>

<div class="container my-5">
  <h1 class="text-center mb-4">Contact Us</h1>

  <section class="mb-5 text-center">
    <p class="lead">
      Have questions, feedback, or need support? We’d love to hear from you.  
      Our team is here to help!
    </p>
  </section>

  <div class="row g-4">
    <!-- Contact Info -->
    <div class="col-md-5">
      <div class="card shadow-sm border-0 h-100 bg-light">
        <div class="card-body">
          <h4 class="fw-bold mb-3">Get in Touch</h4>
          <p><i class="bi bi-geo-alt-fill me-2"></i>123 Shoe Street, Manila, Philippines</p>
          <p><i class="bi bi-envelope-fill me-2"></i>kicksnstyles@gmail.com</p>
          <p><i class="bi bi-telephone-fill me-2"></i>+63 912 345 6789</p>
        </div>
      </div>
    </div>

    <!-- Contact Form -->
    <div class="col-md-7">
      <div class="card shadow-sm border-0 h-100">
        <div class="card-body">
          <h4 class="fw-bold mb-3">Send Us a Message</h4>
          <form action="send_message.php" method="POST">
            <div class="mb-3">
              <label for="name" class="form-label">Your Name</label>
              <input type="text" class="form-control" id="name" name="name" required>
            </div>
            <div class="mb-3">
              <label for="email" class="form-label">Your Email</label>
              <input type="email" class="form-control" id="email" name="email" required>
            </div>
            <div class="mb-3">
              <label for="subject" class="form-label">Subject</label>
              <input type="text" class="form-control" id="subject" name="subject" required>
            </div>
            <div class="mb-3">
              <label for="message" class="form-label">Message</label>
              <textarea class="form-control" id="message" name="message" rows="4" required></textarea>
            </div>
            <button type="submit" class="btn btn-secondary">Send Message</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include 'footer.php'; ?>
