<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>BuildWatch - Construction Management System</title>
  

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  

  <link rel="stylesheet" href="../assets/css/frontpage.css">
</head>
<body>

  <nav>
    <div class="logo">
      <span>Build</span><span>Watch</span>
    </div>
    
    <div class="nav-buttons">
      <a href="client_login.php" class="nav-btn client-btn">Client Portal</a>
      <a href="login.php" class="nav-btn contractor-btn">Contractor Sign In</a>
    </div>
  </nav>


  <section class="hero">
    <div class="container">
      <h1>Connect with the Right Contractor for Your Project.</h1>
      <p>Submit your project proposal and get matched with qualified contractors ready to bring your vision to life.</p>
      <div class="cta-buttons">
        <a href="proposals_submit.php" class="btn-primary">Submit a Project</a>
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
          <li><a href="proposals_submit.php">Submit a Project</a></li>
          <li><a href="login.php">Contractor Login</a></li>
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
