<?php
session_start();
$error = $_SESSION['error'] ?? '';
unset($_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Client Sign Up - BuildWatch</title>
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
      max-width: 450px;
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
    .form-link {
      color: #cb9501;
      text-decoration: none;
    }
    .form-link:hover {
      text-decoration: underline;
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
    .password-strength {
      margin-top: 0.5rem;
      font-size: 0.85rem;
    }
    .strength-bar {
      height: 4px;
      background: #ddd;
      border-radius: 2px;
      margin-top: 0.25rem;
      overflow: hidden;
    }
    .strength-fill {
      height: 100%;
      transition: all 0.3s;
      width: 0%;
    }
    .strength-weak { background: #c33; width: 33%; }
    .strength-medium { background: #cb9501; width: 66%; }
    .strength-strong { background: #3c3; width: 100%; }
  </style>
</head>
<body>
  <div class="auth-container">
    <div class="auth-right">
      <a href="frontpage.php" class="back-to-home">
        <i class="fas fa-arrow-left"></i>
        <span>Back to Home</span>
      </a>

      <form class="auth-form" id="signup-form" action="client_signup_process.php" method="post" novalidate>
        <h2 class="form-title">Create Client Account</h2>
        <p class="form-subtitle">Join BuildWatch to track your project proposals</p>
        
        <?php if ($error): ?>
          <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i>
            <span><?php echo htmlspecialchars($error); ?></span>
          </div>
        <?php endif; ?>
        
        <div class="form-group">
          <label class="form-label">Full Name</label>
          <input type="text" name="name" id="name" class="form-input" required>
          <div class="invalid-feedback" id="name-error">Please enter your full name.</div>
        </div>

        <div class="form-group">
          <label class="form-label">Email Address</label>
          <input type="email" name="email" id="email" class="form-input" required>
          <div class="invalid-feedback" id="email-error">Please enter a valid email address.</div>
        </div>

        <div class="form-group">
          <label class="form-label">Phone Number (Optional)</label>
          <input type="tel" name="phone" id="phone" class="form-input" placeholder="(123) 456-7890">
        </div>

        <div class="form-group">
          <label class="form-label">Company Name (Optional)</label>
          <input type="text" name="company" id="company" class="form-input">
        </div>
        
        <div class="form-group">
          <label class="form-label">Password</label>
          <div class="password-wrapper">
            <input type="password" name="password" id="password" class="form-input" required>
            <button type="button" class="password-toggle" id="togglePassword" aria-label="Toggle password visibility">
              <i class="fas fa-eye" id="toggleIcon"></i>
            </button>
          </div>
          <div class="password-strength">
            <div class="strength-bar">
              <div class="strength-fill" id="strengthFill"></div>
            </div>
            <small id="strengthText"></small>
          </div>
          <div class="invalid-feedback" id="password-error">Password must be at least 8 characters.</div>
        </div>

        <div class="form-group">
          <label class="form-label">Confirm Password</label>
          <div class="password-wrapper">
            <input type="password" name="confirm_password" id="confirm_password" class="form-input" required>
            <button type="button" class="password-toggle" id="toggleConfirmPassword" aria-label="Toggle password visibility">
              <i class="fas fa-eye" id="toggleConfirmIcon"></i>
            </button>
          </div>
          <div class="invalid-feedback" id="confirm-error">Passwords do not match.</div>
        </div>
        
        <button type="submit" class="btn btn-primary">Create Account</button>

        <div class="auth-switch"><p>Already have an account? <a href="client_login.php" class="form-link">Sign in here</a></p></div>
      </form>
    </div>
  </div>

  <script>
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');
    const toggleIcon = document.getElementById('toggleIcon');
    const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
    const confirmPasswordInput = document.getElementById('confirm_password');
    const toggleConfirmIcon = document.getElementById('toggleConfirmIcon');

    togglePassword.addEventListener('click', function() {
      const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
      passwordInput.setAttribute('type', type);
      toggleIcon.classList.toggle('fa-eye');
      toggleIcon.classList.toggle('fa-eye-slash');
    });

    toggleConfirmPassword.addEventListener('click', function() {
      const type = confirmPasswordInput.getAttribute('type') === 'password' ? 'text' : 'password';
      confirmPasswordInput.setAttribute('type', type);
      toggleConfirmIcon.classList.toggle('fa-eye');
      toggleConfirmIcon.classList.toggle('fa-eye-slash');
    });

    const form = document.getElementById('signup-form');
    const nameInput = document.getElementById('name');
    const emailInput = document.getElementById('email');
    const strengthFill = document.getElementById('strengthFill');
    const strengthText = document.getElementById('strengthText');

    passwordInput.addEventListener('input', function() {
      checkPasswordStrength();
      if (passwordInput.classList.contains('is-invalid')) {
        passwordInput.classList.remove('is-invalid');
        document.getElementById('password-error').classList.remove('show');
      }
    });

    confirmPasswordInput.addEventListener('input', function() {
      if (confirmPasswordInput.classList.contains('is-invalid')) {
        confirmPasswordInput.classList.remove('is-invalid');
        document.getElementById('confirm-error').classList.remove('show');
      }
    });

    function checkPasswordStrength() {
      const password = passwordInput.value;
      let strength = 0;

      if (password.length >= 8) strength++;
      if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
      if (password.match(/[0-9]/)) strength++;
      if (password.match(/[^a-zA-Z0-9]/)) strength++;

      strengthFill.className = 'strength-fill';
      if (strength === 0) {
        strengthText.textContent = '';
      } else if (strength <= 2) {
        strengthFill.classList.add('strength-weak');
        strengthText.textContent = 'Weak password';
        strengthText.style.color = '#c33';
      } else if (strength === 3) {
        strengthFill.classList.add('strength-medium');
        strengthText.textContent = 'Medium password';
        strengthText.style.color = '#cb9501';
      } else {
        strengthFill.classList.add('strength-strong');
        strengthText.textContent = 'Strong password';
        strengthText.style.color = '#3c3';
      }
    }

    form.addEventListener('submit', function(e) {
      let isValid = true;

      if (!nameInput.value.trim()) {
        nameInput.classList.add('is-invalid');
        document.getElementById('name-error').classList.add('show');
        isValid = false;
      }

      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!emailRegex.test(emailInput.value.trim())) {
        emailInput.classList.add('is-invalid');
        document.getElementById('email-error').classList.add('show');
        isValid = false;
      }

      if (passwordInput.value.length < 8) {
        passwordInput.classList.add('is-invalid');
        document.getElementById('password-error').classList.add('show');
        isValid = false;
      }

      if (passwordInput.value !== confirmPasswordInput.value) {
        confirmPasswordInput.classList.add('is-invalid');
        document.getElementById('confirm-error').classList.add('show');
        isValid = false;
      }

      if (!isValid) {
        e.preventDefault();
      }
    });
  </script>
</body>
</html>
