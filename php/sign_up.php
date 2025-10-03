<?php
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
  </style>
</head>
<body>
  <div class="auth-container">
    <div class="auth-right">
      <form class="auth-form" id="signup-form" action="register_process.php" method="post">
        <h2 class="form-title">Create Account</h2>
        <p class="form-subtitle">Join our network of professional contractors</p>

        <div class="form-group">
          <label class="form-label">Role <span style="color: red;">*</span></label>
          <select name="role" class="form-input" required>
            <option value="" disabled selected>Select your role</option>
            <option value="pm">Project Manager</option>
            <option value="worker">Worker</option>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label">Full Name <span style="color: red;">*</span></label>
          <input type="text" name="full_name" class="form-input" placeholder="Enter your full name" required>
        </div>

        <div class="form-group">
          <label class="form-label">Email Address <span style="color: red;">*</span></label>
          <input type="email" name="email" class="form-input" placeholder="your.email@example.com" required>
        </div>

        <div class="form-group">
          <label class="form-label">Phone Number (Optional)</label>
          <input type="tel" name="phone" class="form-input" placeholder="+1 (555) 123-4567">
        </div>

        <div class="form-group">
          <label class="form-label">Password <span style="color: red;">*</span></label>
          <input type="password" name="password" class="form-input" placeholder="Create a strong password" required>
          <p class="password-hint">Use at least 8 characters with a mix of letters and numbers</p>
        </div>

        <div class="form-group">
          <label class="form-label">Confirm Password <span style="color: red;">*</span></label>
          <input type="password" name="confirm_password" class="form-input" placeholder="Re-enter your password" required>
        </div>

        <div class="form-options">
          <label class="form-checkbox">
            <input type="checkbox" required>
            <span>I agree to the <a href="#" class="form-link">Terms of Service</a> and <a href="#" class="form-link">Privacy Policy</a></span>
          </label>
        </div>

        <button type="submit" class="btn btn-primary">Create Account</button>

        <div class="auth-switch">
          <p>Already have an account? <a href="login.php" class="form-link">Sign In</a></p>
        </div>
      </form>
    </div>
  </div>
</body>
</html>
