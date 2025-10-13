<?php
session_start();
$error = $_SESSION['error'] ?? '';
$success = $_SESSION['success'] ?? '';
$form_data = $_SESSION['form_data'] ?? [];
unset($_SESSION['error'], $_SESSION['success'], $_SESSION['form_data']);
require_once("../config/db.php");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign Up - BuildWatch</title>
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
      padding: 2rem 1rem;
    }
    .auth-right {
      background: #fff;
      padding: 2rem;
      border-radius: 10px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
      width: 100%;
      max-width: 450px;
    }
    .form-title {
      margin: 0 0 0.5rem;
      font-size: 1.8rem;
      color: #222;
      text-align: center;
    }
    .form-subtitle {
      margin: 0 0 2rem;
      color: #666;
      font-size: 0.95rem;
      text-align: center;
    }
    .form-group {
      margin-bottom: 1.25rem;
    }
    .form-group.password-field {
      position: relative;
    }
    .form-label {
      display: block;
      margin-bottom: 0.5rem;
      font-weight: 600;
      font-size: 0.9rem;
      color: #333;
    }
    .form-input {
      width: 100%;
      padding: 0.75rem;
      border: 1px solid #ddd;
      border-radius: 6px;
      font-size: 1rem;
      box-sizing: border-box;
      transition: border-color 0.3s;
    }
    .form-input:focus {
      outline: none;
      border-color: #007bff;
    }
    .form-options {
      margin-bottom: 1.5rem;
    }
    .form-checkbox {
      display: flex;
      align-items: flex-start;
      font-size: 0.85rem;
      color: #555;
    }
    .form-checkbox input {
      margin-right: 0.5rem;
      margin-top: 0.2rem;
    }
    .form-link {
      color: #007bff;
      text-decoration: none;
    }
    .form-link:hover {
      text-decoration: underline;
    }
    .btn {
      width: 100%;
      padding: 0.85rem;
      border: none;
      border-radius: 6px;
      font-size: 1rem;
      cursor: pointer;
      font-weight: 600;
      transition: background-color 0.3s;
    }
    .btn-primary {
      background: #007bff;
      color: #fff;
    }
    .btn-primary:hover {
      background: #0056b3;
    }
    .auth-switch {
      text-align: center;
      font-size: 0.9rem;
      margin-top: 1.5rem;
      color: #666;
    }
    .password-hint {
      font-size: 0.8rem;
      color: #888;
      margin-top: 0.3rem;
    }
    .password-toggle {
      position: absolute;
      right: 12px;
      top: 38px;
      background: none;
      border: none;
      color: #666;
      cursor: pointer;
      padding: 0;
      font-size: 1rem;
      transition: color 0.3s;
    }
    .password-toggle:hover {
      color: #007bff;
    }
    .password-toggle:focus {
      outline: none;
    }
    /* Added styles for alert messages and validation feedback */
    .alert {
      padding: 0.75rem 1rem;
      border-radius: 6px;
      margin-bottom: 1.5rem;
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
    /* </CHANGE> */
  </style>
</head>
<body>
  <div class="auth-container">
    <div class="auth-right">
      <form class="auth-form" id="signup-form" action="register_process.php" method="post" novalidate>
        <h2 class="form-title">Create Account</h2>
        <p class="form-subtitle">Join our network of professional contractors</p>

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
         </CHANGE> 

        <div class="form-group">
          <label class="form-label">Role <span style="color: red;">*</span></label>
          <select name="role" id="role" class="form-input" required>
            <option value="" disabled <?php echo empty($form_data['role']) ? 'selected' : ''; ?>>Select your role</option>
            <option value="pm" <?php echo ($form_data['role'] ?? '') === 'pm' ? 'selected' : ''; ?>>Project Manager</option>
            <option value="worker" <?php echo ($form_data['role'] ?? '') === 'worker' ? 'selected' : ''; ?>>Worker</option>
          </select>

          <div class="invalid-feedback" id="role-error">Please select your role.</div>
           </CHANGE> 
        </div>

        <div class="form-group">
          <label class="form-label">Full Name <span style="color: red;">*</span></label>
          <input type="text" name="full_name" id="full_name" class="form-input" placeholder="Enter your full name" value="<?php echo htmlspecialchars($form_data['full_name'] ?? ''); ?>" required>
 
          <div class="invalid-feedback" id="full_name-error">Full name is required.</div>
           </CHANGE> 
        </div>

        <div class="form-group">
          <label class="form-label">Email Address <span style="color: red;">*</span></label>
          <input type="email" name="email" id="email" class="form-input" placeholder="your.email@example.com" value="<?php echo htmlspecialchars($form_data['email'] ?? ''); ?>" required>

          <div class="invalid-feedback" id="email-error">Please enter a valid email address.</div>
           </CHANGE> 
        </div>

        <div class="form-group">
          <label class="form-label">Phone Number (Optional)</label>
          <input type="tel" name="phone" id="phone" class="form-input" placeholder="+1 (555) 123-4567" value="<?php echo htmlspecialchars($form_data['phone'] ?? ''); ?>">
        </div>

        <div class="form-group password-field">
          <label class="form-label">Password <span style="color: red;">*</span></label>
          <input type="password" id="password" name="password" class="form-input" placeholder="Create a strong password" required>
          <button type="button" class="password-toggle" onclick="togglePassword('password', this)">
            <i class="fas fa-eye"></i>
          </button>
          <p class="password-hint">Use at least 8 characters with a mix of letters and numbers</p>

          <div class="invalid-feedback" id="password-error">Password must be at least 8 characters.</div>
           </CHANGE> 
        </div>

        <div class="form-group password-field">
          <label class="form-label">Confirm Password <span style="color: red;">*</span></label>
          <input type="password" id="confirm_password" name="confirm_password" class="form-input" placeholder="Re-enter your password" required>
          <button type="button" class="password-toggle" onclick="togglePassword('confirm_password', this)">
            <i class="fas fa-eye"></i>
          </button>

          <div class="invalid-feedback" id="confirm_password-error">Passwords do not match.</div>
           </CHANGE> 
        </div>

        <div class="form-options">
          <label class="form-checkbox">
            <input type="checkbox" id="terms" required>
            <span>I agree to the <a href="#" class="form-link">Terms of Service</a> and <a href="#" class="form-link">Privacy Policy</a></span>
          </label>

          <div class="invalid-feedback" id="terms-error">You must agree to the terms and conditions.</div>
           </CHANGE> 
        </div>

        <button type="submit" class="btn btn-primary">Create Account</button>

        <div class="auth-switch">
          <p>Already have an account? <a href="login.php" class="form-link">Sign In</a></p>
        </div>
      </form>
    </div>
  </div>

  <script>
    function togglePassword(inputId, button) {
      const input = document.getElementById(inputId);
      const icon = button.querySelector('i');
      
      if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
      } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
      }
    }

    const form = document.getElementById('signup-form');
    const roleInput = document.getElementById('role');
    const fullNameInput = document.getElementById('full_name');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    const termsInput = document.getElementById('terms');

    // Validation functions
    function validateRole() {
      const role = roleInput.value;
      const error = document.getElementById('role-error');
      
      if (!role) {
        roleInput.classList.add('is-invalid');
        error.classList.add('show');
        return false;
      } else {
        roleInput.classList.remove('is-invalid');
        error.classList.remove('show');
        return true;
      }
    }

    function validateFullName() {
      const fullName = fullNameInput.value.trim();
      const error = document.getElementById('full_name-error');
      
      if (!fullName) {
        fullNameInput.classList.add('is-invalid');
        error.classList.add('show');
        return false;
      } else {
        fullNameInput.classList.remove('is-invalid');
        error.classList.remove('show');
        return true;
      }
    }

    function validateEmail() {
      const email = emailInput.value.trim();
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      const error = document.getElementById('email-error');
      
      if (!email) {
        emailInput.classList.add('is-invalid');
        error.textContent = 'Email is required.';
        error.classList.add('show');
        return false;
      } else if (!emailRegex.test(email)) {
        emailInput.classList.add('is-invalid');
        error.textContent = 'Please enter a valid email address.';
        error.classList.add('show');
        return false;
      } else {
        emailInput.classList.remove('is-invalid');
        error.classList.remove('show');
        return true;
      }
    }

    function validatePassword() {
      const password = passwordInput.value;
      const error = document.getElementById('password-error');
      
      if (!password) {
        passwordInput.classList.add('is-invalid');
        error.textContent = 'Password is required.';
        error.classList.add('show');
        return false;
      } else if (password.length < 8) {
        passwordInput.classList.add('is-invalid');
        error.textContent = 'Password must be at least 8 characters.';
        error.classList.add('show');
        return false;
      } else {
        passwordInput.classList.remove('is-invalid');
        error.classList.remove('show');
        return true;
      }
    }

    function validateConfirmPassword() {
      const password = passwordInput.value;
      const confirmPassword = confirmPasswordInput.value;
      const error = document.getElementById('confirm_password-error');
      
      if (!confirmPassword) {
        confirmPasswordInput.classList.add('is-invalid');
        error.textContent = 'Please confirm your password.';
        error.classList.add('show');
        return false;
      } else if (password !== confirmPassword) {
        confirmPasswordInput.classList.add('is-invalid');
        error.textContent = 'Passwords do not match.';
        error.classList.add('show');
        return false;
      } else {
        confirmPasswordInput.classList.remove('is-invalid');
        error.classList.remove('show');
        return true;
      }
    }

    function validateTerms() {
      const error = document.getElementById('terms-error');
      
      if (!termsInput.checked) {
        error.classList.add('show');
        return false;
      } else {
        error.classList.remove('show');
        return true;
      }
    }

    // Add blur event listeners for real-time validation
    roleInput.addEventListener('blur', validateRole);
    roleInput.addEventListener('change', validateRole);
    fullNameInput.addEventListener('blur', validateFullName);
    emailInput.addEventListener('blur', validateEmail);
    passwordInput.addEventListener('blur', validatePassword);
    confirmPasswordInput.addEventListener('blur', validateConfirmPassword);
    termsInput.addEventListener('change', validateTerms);

    // Clear validation on input
    roleInput.addEventListener('input', function() {
      if (roleInput.classList.contains('is-invalid')) {
        roleInput.classList.remove('is-invalid');
        document.getElementById('role-error').classList.remove('show');
      }
    });

    fullNameInput.addEventListener('input', function() {
      if (fullNameInput.classList.contains('is-invalid')) {
        fullNameInput.classList.remove('is-invalid');
        document.getElementById('full_name-error').classList.remove('show');
      }
    });

    emailInput.addEventListener('input', function() {
      if (emailInput.classList.contains('is-invalid')) {
        emailInput.classList.remove('is-invalid');
        document.getElementById('email-error').classList.remove('show');
      }
    });

    passwordInput.addEventListener('input', function() {
      if (passwordInput.classList.contains('is-invalid')) {
        passwordInput.classList.remove('is-invalid');
        document.getElementById('password-error').classList.remove('show');
      }
      // Re-validate confirm password if it has a value
      if (confirmPasswordInput.value) {
        validateConfirmPassword();
      }
    });

    confirmPasswordInput.addEventListener('input', function() {
      if (confirmPasswordInput.classList.contains('is-invalid')) {
        confirmPasswordInput.classList.remove('is-invalid');
        document.getElementById('confirm_password-error').classList.remove('show');
      }
    });

    // Form submission validation
    form.addEventListener('submit', function(e) {
      let isValid = true;

      if (!validateRole()) isValid = false;
      if (!validateFullName()) isValid = false;
      if (!validateEmail()) isValid = false;
      if (!validatePassword()) isValid = false;
      if (!validateConfirmPassword()) isValid = false;
      if (!validateTerms()) isValid = false;

      if (!isValid) {
        e.preventDefault();
        // Scroll to first error
        const firstError = document.querySelector('.is-invalid');
        if (firstError) {
          firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
          firstError.focus();
        }
      }
    });
    // </CHANGE>
  </script>
</body>
</html>
