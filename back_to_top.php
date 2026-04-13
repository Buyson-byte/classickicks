<!-- Back to Top Button -->
<button id="back-to-top" class="back-to-top hide" aria-label="back-to-top">
  <span id="progress-bar"></span>
  <i class='bx bx-up-arrow-alt' ></i>
</button>

<script type="text/javascript">
  document.addEventListener('DOMContentLoaded', () => {
      const backToTopButton = document.getElementById('back-to-top');
      const progressBar = document.getElementById('progress-bar');

      // Show or hide button on scroll
      window.addEventListener('scroll', () => {
          const scrollTop = window.scrollY;
          const docHeight = document.documentElement.scrollHeight - window.innerHeight;
          const scrollPercent = (scrollTop / docHeight) * 100;
          progressBar.style.height = scrollPercent + '%';

          if (scrollTop > 300) {
              backToTopButton.classList.add('show');
              backToTopButton.classList.remove('hide');
          } else {
              backToTopButton.classList.add('hide');
              backToTopButton.classList.remove('show');
          }
      });

      // Scroll to top when clicked
      backToTopButton.addEventListener('click', () => {
          window.scrollTo({
              top: 0,
              behavior: 'smooth'
          });
      });
  });
</script>

<style>
  /* Example styling for the button */
  button.back-to-top i {
    position: relative;
    font-weight: 100;
  }
  button.back-to-top {
    position: fixed;
    bottom: 60px;
    inset-inline-end: 30px;
    z-index: 2;
    opacity: 0;
    visibility: hidden;
    font-size: 16px;
    line-height: 1;
    height: 40px;
    width: 40px;
    background-color: #ffffff;
    border-radius: 10px;
    z-index: 999;
    transition: opacity 0.3s ease, visibility 0.3s ease;
  }
  
  .back-to-top.hide { opacity: 0; visibility: hidden; }
  .back-to-top.show { opacity: 1; visibility: visible; }
  #progress-bar {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 0;
  }
  button.back-to-top #progress-bar {
    position: absolute;
    bottom: 0;
    left: 0;
    width: 2px;
    background-color: #333333;
}
</style>