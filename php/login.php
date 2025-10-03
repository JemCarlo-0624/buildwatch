<?php
require_once("../config/db.php");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>BuildWatch - Contractor Portal</title>
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
    }
    .auth-right {
      background: #fff;
      padding: 2rem;
      border-radius: 10px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
      width: 100%;
      max-width: 400px;
    }
    /* Enhanced back to home button with proper design */
    .back-to-home {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      padding: 0.6rem 1.2rem;
      background: rgba(10, 99, 165, 0.08);
      color: #0a63a5;
      text-decoration: none;
      font-size: 0.9rem;
      font-weight: 500;
      border-radius: 8px;
      margin-bottom: 1.5rem;
      transition: all 0.3s ease;
      border: 1px solid rgba(10, 99, 165, 0.15);
    }
    .back-to-home:hover {
      background: #0a63a5;
      color: #fff;
      transform: translateX(-3px);
      box-shadow: 0 2px 8px rgba(10, 99, 165, 0.2);
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
    .form-input {
      width: 100%;
      padding: 0.75rem;
      border: 1px solid #ddd;
      border-radius: 6px;
      font-size: 1rem;
      box-sizing: border-box;
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
      color: #007bff;
      text-decoration: none;
      font-size: 0.85rem;
    }
    .btn {
      width: 100%;
      padding: 0.75rem;
      border: none;
      border-radius: 6px;
      font-size: 1rem;
      cursor: pointer;
      margin-bottom: 1rem;
    }
    .btn-primary {
      background: #007bff;
      color: #fff;
    }
    .auth-switch {
      text-align: center;
      font-size: 0.9rem;
    }
  </style>
</head>
<body>
  <div class="auth-container">
    <div class="auth-right">
      <!-- Updated button markup with better structure -->
      <a href="frontpage.php" class="back-to-home">
        <i class="fas fa-arrow-left"></i>
        <span>Back to Home</span>
      </a>
      <!-- </CHANGE> -->

      <form class="auth-form" id="signin-form" action="login_process.php" method="post">
        <h2 class="form-title">Contractor Sign In</h2>
        <p class="form-subtitle">Welcome back! Please sign in to your account</p>
        <div class="form-group">
          <label class="form-label">Email Address</label>
          <input type="email" name="email" class="form-input" required>
        </div>
        <div class="form-group">
          <label class="form-label">Password</label>
          <input type="password" name="password" class="form-input" required>
        </div>
        <div class="form-options">
          <label class="form-checkbox"><input type="checkbox">Remember me</label>
          <a href="#" class="form-link">Forgot your password?</a>
        </div>
        <button type="submit" class="btn btn-primary">Sign In</button>

        <div class="auth-switch"><p>Not a contractor yet? <a href="sign_up.php" class="form-link">Join our network</a></p></div>
      </form>
    </div>
  </div>
</body>
</html>
