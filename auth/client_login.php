<?php
session_start();
$error = $_SESSION['error'] ?? '';
$success = $_SESSION['success'] ?? '';
unset($_SESSION['error'], $_SESSION['success']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Client Login - BuildWatch</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <!-- New unified design system -->
  <link rel="stylesheet" href="../assets/css/main.css">
  <link rel="stylesheet" href="../assets/css/authentication.css">
</head>
<body>
  <a href="#main-content" class="skip-link">Skip to main content</a>
  <div class="auth-container">
    <main id="main-content" class="auth-form-wrapper">
      <a href="frontpage.php" class="back-to-home">
        <i class="fas fa-arrow-left"></i>
        <span>Back to Home</span>
      </a>

      <div class="auth-form-header">
        <h1 class="auth-form-title">Client Portal</h1>
        <p class="auth-form-subtitle">Sign in to track your project proposals</p>
      </div>

      <form class="auth-form" id="signin-form" action="client_login_process.php" method="post" novalidate aria-label="Client login form">
        
        <?php if ($error): ?>
          <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i>
            <span><?php echo htmlspecialchars($error); ?></span>
          </div>
        <?php endif; ?>
        <?php if ($success): ?>
          <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <span><?php echo htmlspecialchars($success); ?></span>
          </div>
        <?php endif; ?>
        
        <div class="form-group">
          <label for="email" class="form-label">Email Address</label>
          <input type="email" name="email" id="email" class="form-input" required aria-describedby="email-error">
          <div class="invalid-feedback" id="email-error" role="alert">Please enter a valid email address.</div>
        </div>
        
        <div class="form-group">
          <label for="password" class="form-label">Password</label>
          <div class="password-wrapper">
            <input type="password" name="password" id="password" class="form-input" required aria-describedby="password-error">
            <button type="button" class="password-toggle" id="togglePassword" aria-label="Toggle password visibility" aria-pressed="false">
              <i class="fas fa-eye" id="toggleIcon"></i>
            </button>
          </div>
          <div class="invalid-feedback" id="password-error" role="alert">Password is required.</div>
        </div>
        
        <button type="submit" class="btn btn-primary auth-submit-btn">Sign In</button>

        <div class="auth-form-switch"><p>Don't have an account? <a href="client_signup.php">Sign up here</a></p></div>
      </form>
    </main>
  </div>

  <script>
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');
    const toggleIcon = document.getElementById('toggleIcon');

    togglePassword.addEventListener('click', function() {
      const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
      const isVisible = type === 'text';
      
      passwordInput.setAttribute('type', type);
      togglePassword.setAttribute('aria-pressed', isVisible ? 'true' : 'false');
      
      if (isVisible) {
        toggleIcon.classList.remove('fa-eye');
        toggleIcon.classList.add('fa-eye-slash');
        togglePassword.setAttribute('aria-label', 'Hide password');
      } else {
        toggleIcon.classList.remove('fa-eye-slash');
        toggleIcon.classList.add('fa-eye');
        togglePassword.setAttribute('aria-label', 'Show password');
      }
    });

    const form = document.getElementById('signin-form');
    const emailInput = document.getElementById('email');
    const emailError = document.getElementById('email-error');
    const passwordError = document.getElementById('password-error');

    emailInput.addEventListener('blur', function() {
      validateEmail();
    });

    passwordInput.addEventListener('blur', function() {
      validatePassword();
    });

    emailInput.addEventListener('input', function() {
      if (emailInput.classList.contains('is-invalid')) {
        emailInput.classList.remove('is-invalid');
        emailError.classList.remove('show');
      }
    });

    passwordInput.addEventListener('input', function() {
      if (passwordInput.classList.contains('is-invalid')) {
        passwordInput.classList.remove('is-invalid');
        passwordError.classList.remove('show');
      }
    });

    form.addEventListener('submit', function(e) {
      let isValid = true;

      if (!validateEmail()) {
        isValid = false;
      }

      if (!validatePassword()) {
        isValid = false;
      }

      if (!isValid) {
        e.preventDefault();
      }
    });

    function validateEmail() {
      const email = emailInput.value.trim();
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

      if (!email) {
        emailInput.classList.add('is-invalid');
        emailError.textContent = 'Email is required.';
        emailError.classList.add('show');
        return false;
      } else if (!emailRegex.test(email)) {
        emailInput.classList.add('is-invalid');
        emailError.textContent = 'Please enter a valid email address.';
        emailError.classList.add('show');
        return false;
      } else {
        emailInput.classList.remove('is-invalid');
        emailError.classList.remove('show');
        return true;
      }
    }

    function validatePassword() {
      const password = passwordInput.value;

      if (!password) {
        passwordInput.classList.add('is-invalid');
        passwordError.classList.add('show');
        return false;
      } else {
        passwordInput.classList.remove('is-invalid');
        passwordError.classList.remove('show');
        return true;
      }
    }
  </script>
</body>
</html>
