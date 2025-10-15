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
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  
  <style>
    body {
      margin: 0;
      font-family: Arial, sans-serif;
      background: #f4f6f9;
      color: #333;
    }
    .auth-container {
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      padding: 20px;
    }
    .auth-right {
      background: #fff;
      padding: 2rem;
      border-radius: 10px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
      width: 100%;
      max-width: 400px;
    }
    .back-to-home {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      padding: 0.6rem 1.2rem;
      background: rgba(203, 149, 1, 0.08);
      color: #cb9501;
      text-decoration: none;
      font-size: 0.9rem;
      font-weight: 500;
      border-radius: 8px;
      margin-bottom: 1.5rem;
      transition: all 0.3s ease;
      border: 1px solid rgba(203, 149, 1, 0.15);
    }
    .back-to-home:hover {
      background: #cb9501;
      color: #fff;
      transform: translateX(-3px);
      box-shadow: 0 2px 8px rgba(203, 149, 1, 0.2);
    }
    .back-to-home i {
      font-size: 0.85rem;
      transition: transform 0.3s ease;
    }
    .back-to-home:hover i {
      transform: translateX(-2px);
    }
    .form-title {
      margin: 0 0 0.5rem;
      font-size: 1.5rem;
      color: #222;
    }
    .form-subtitle {
      margin: 0 0 1.5rem;
      color: #666;
      font-size: 0.9rem;
    }
    .form-group {
      margin-bottom: 1rem;
    }
    .form-label {
      display: block;
      margin-bottom: 0.5rem;
      font-weight: 600;
      font-size: 0.9rem;
    }
    .password-wrapper {
      position: relative;
      display: flex;
      align-items: center;
    }
    .form-input {
      width: 100%;
      padding: 0.75rem;
      border: 1px solid #ddd;
      border-radius: 6px;
      font-size: 1rem;
      box-sizing: border-box;
    }
    .password-toggle {
      position: absolute;
      right: 12px;
      background: none;
      border: none;
      color: #666;
      cursor: pointer;
      padding: 0.5rem;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: color 0.2s ease;
    }
    .password-toggle:hover {
      color: #cb9501;
    }
    .password-toggle i {
      font-size: 1rem;
    }
    .form-options {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1rem;
    }
    .form-checkbox {
      display: flex;
      align-items: center;
      font-size: 0.85rem;
    }
    .form-checkbox input {
      margin-right: 0.4rem;
    }
    .form-link {
      color: #cb9501;
      text-decoration: none;
      font-size: 0.85rem;
    }
    .form-link:hover {
      text-decoration: underline;
    }
    .btn {
      width: 100%;
      padding: 0.75rem;
      border: none;
      border-radius: 6px;
      font-size: 1rem;
      cursor: pointer;
      margin-bottom: 1rem;
      font-weight: 600;
      transition: all 0.3s;
    }
    .btn-primary {
      background: #cb9501;
      color: #fff;
    }
    .btn-primary:hover {
      background: #b28101;
    }
    .auth-switch {
      text-align: center;
      font-size: 0.9rem;
    }
    .alert {
      padding: 0.75rem 1rem;
      border-radius: 6px;
      margin-bottom: 1rem;
      font-size: 0.9rem;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }
    .alert-danger {
      background: #fee;
      color: #c33;
      border: 1px solid #fcc;
    }
    .alert-success {
      background: #efe;
      color: #3c3;
      border: 1px solid #cfc;
    }
    .invalid-feedback {
      color: #c33;
      font-size: 0.85rem;
      margin-top: 0.25rem;
      display: none;
    }
    .invalid-feedback.show {
      display: block;
    }
    .form-input.is-invalid {
      border-color: #c33;
    }
  </style>
</head>
<body>
  <div class="auth-container">
    <div class="auth-right">
      <a href="frontpage.php" class="back-to-home">
        <i class="fas fa-arrow-left"></i>
        <span>Back to Home</span>
      </a>

      <form class="auth-form" id="signin-form" action="client_login_process.php" method="post" novalidate>
        <h2 class="form-title">Client Portal</h2>
        <p class="form-subtitle">Sign in to track your project proposals</p>
        
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
          <label class="form-label">Email Address</label>
          <input type="email" name="email" id="email" class="form-input" required>
          <div class="invalid-feedback" id="email-error">Please enter a valid email address.</div>
        </div>
        
        <div class="form-group">
          <label class="form-label">Password</label>
          <div class="password-wrapper">
            <input type="password" name="password" id="password" class="form-input" required>
            <button type="button" class="password-toggle" id="togglePassword" aria-label="Toggle password visibility">
              <i class="fas fa-eye" id="toggleIcon"></i>
            </button>
          </div>
          <div class="invalid-feedback" id="password-error">Password is required.</div>
        </div>
        <button type="submit" class="btn btn-primary">Sign In</button>

        <div class="auth-switch"><p>Don't have an account? <a href="client_signup.php" class="form-link">Sign up here</a></p></div>
      </form>
    </div>
  </div>

  <script>
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');
    const toggleIcon = document.getElementById('toggleIcon');

    togglePassword.addEventListener('click', function() {
      const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
      passwordInput.setAttribute('type', type);
      
      if (type === 'text') {
        toggleIcon.classList.remove('fa-eye');
        toggleIcon.classList.add('fa-eye-slash');
      } else {
        toggleIcon.classList.remove('fa-eye-slash');
        toggleIcon.classList.add('fa-eye');
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
