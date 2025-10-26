<?php
// Define base paths for use in the template
define('BASE_PATH', dirname(__DIR__));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>BuildWatch - Construction Management System</title>
  
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  
  <link rel="stylesheet" href="../assets/css/frontpage.css">
</head>
<body>

  <nav>
    <div class="logo">
      <span>Build</span><span>Watch</span>
    </div>
  </nav>

  <section class="hero">
    <div class="hero-overlay"></div>
    <div class="container hero-content">
      <h1>Professional Construction Management at Your Fingertips</h1>
      <p>Access your projects, track progress, and collaborate with your team. Secure login for clients and staff members.</p>
      <div class="cta-buttons">
        <a href="/client/login" class="btn-primary">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 8px;">
            <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path>
            <polyline points="10 17 15 12 10 7"></polyline>
            <line x1="15" y1="12" x2="3" y2="12"></line>
          </svg>
          Client Login
        </a>
        <a href="/login" class="btn-secondary">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 8px;">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
            <circle cx="12" cy="7" r="4"></circle>
          </svg>
          Staff Portal
        </a>
      </div>
    </div>
  </section>

  <section class="features">
    <div class="container">
      <h2>What Makes the Difference</h2>
      <div class="feature-grid">
        <div class="feature-card">
          <h3>Expertise</h3>
          <p>Our team brings decades of experience in construction management and execution, ensuring your project is in capable hands.</p>
        </div>
        <div class="feature-card">
          <h3>Quality</h3>
          <p>We never compromise on materials or craftsmanship, ensuring lasting results that stand the test of time.</p>
        </div>
        <div class="feature-card">
          <h3>Innovation</h3>
          <p>Utilizing the latest technology to improve efficiency, reduce environmental impact, and deliver better outcomes.</p>
        </div>
      </div>
    </div>
  </section>

  <footer>
    <div class="footer-content">
      <div class="footer-section">
        <h3>BuildWatch</h3>
        <p>Transforming visions into structures that stand the test of time. Excellence in every project since 2005.</p>
      </div>
      <div class="footer-section">
        <h3>Quick Links</h3>
        <ul class="footer-links">
          <li><a href="/proposals/submit">Submit a Project</a></li>
          <li><a href="/login">Contractor Login</a></li>
        </ul>
      </div>
      <div class="footer-section">
        <h3>Contact Us</h3>
        <ul class="footer-links">
          <li>Address: 123 Construction Ave, Buildersville</li>
          <li>Phone: (555) 123-4567</li>
          <li>Email: info@buildwatch.com</li>
        </ul>
      </div>
    </div>
    <div class="copyright">
      &copy; <?php echo date('Y'); ?> BuildWatch Construction. All rights reserved.
    </div>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

