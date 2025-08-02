<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
   <head>
      <!-- basic -->
      <meta charset="utf-8">
      <meta http-equiv="X-UA-Compatible" content="IE=edge">
      <!-- mobile metas -->
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <meta name="viewport" content="initial-scale=1, maximum-scale=1">
      <!-- site metas -->
      <title>About - MyHotel</title>
      <meta name="keywords" content="">
      <meta name="description" content="">
      <meta name="author" content="">
      <!-- bootstrap css -->
      <link rel="stylesheet" href="css/bootstrap.min.css">
      <!-- style css -->
      <link rel="stylesheet" href="css/style.css">
      <!-- header css -->
      <link rel="stylesheet" href="css/header.css">
      <!-- Responsive-->
      <link rel="stylesheet" href="css/responsive.css">
      <!-- fevicon -->
      <link rel="icon" href="images/fevicon.png" type="image/gif" />
      <!-- Scrollbar Custom CSS -->
      <link rel="stylesheet" href="css/jquery.mCustomScrollbar.min.css">
      <!-- Font Awesome -->
      <link rel="stylesheet" href="https://netdna.bootstrapcdn.com/font-awesome/4.0.3/css/font-awesome.css">
      <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/fancybox/2.1.5/jquery.fancybox.min.css" media="screen">
      <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script><![endif]-->
   </head>
   <!-- body -->
   <body class="main-layout">
      <!-- loader  -->
      <div class="loader_bg">
         <div class="loader"><img src="images/loading.gif" alt="#"/></div>
      </div>
      <!-- end loader -->

      <!-- header -->
      <header>
         <div class="header">
            <div class="logo_section">
               <div class="logo">
                  <a href="index.php"><img src="images/logo.png" alt="Logo"></a>
               </div>
            </div>
            <nav class="navigation navbar navbar-expand-lg navbar-light">
               <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                  <span class="navbar-toggler-icon"></span>
               </button>
               <div class="collapse navbar-collapse" id="navbarNav">
                  <ul class="navbar-nav mx-auto">
                     <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                     </li>
                     <li class="nav-item active">
                        <a class="nav-link" href="about.php">About</a>
                     </li>
                     <li class="nav-item">
                        <a class="nav-link" href="room.php">Our Room</a>
                     </li>
                     <li class="nav-item">
                        <a class="nav-link" href="gallery.php">Gallery</a>
                     </li>
                     <li class="nav-item">
                        <a class="nav-link" href="blog.php">Blog</a>
                     </li>
                     <li class="nav-item">
                        <a class="nav-link" href="contact.php">Contact Us</a>
                     </li>
                  </ul>
               </div>
            </nav>
            <div class="header-actions d-flex align-items-center">
               <div class="search-section mr-3">
                  <button class="btn btn-outline-secondary btn-sm" type="button" data-toggle="modal" data-target="#searchModal">
                     <i class="fa fa-search"></i>
                  </button>
               </div>
               <?php if (isset($_SESSION['account_id'])): ?>
                  <div class="profile-dropdown">
                     <button class="profile-btn">
                        <img src="images/profile.png" class="profile-avatar" alt="avatar">
                        <span class="d-none d-md-inline"><?= htmlspecialchars($_SESSION['username']) ?></span>
                        <i class="fa fa-caret-down"></i>
                     </button>
                     <div class="profile-menu">
                        <a class="dropdown-item" href="profile_customer.php">
                           <i class="fa fa-user mr-2"></i>Personal Information
                        </a>
                        <a class="dropdown-item" href="booked_rooms.php">
                           <i class="fa fa-history mr-2"></i>Booking History
                        </a>
                        <?php if ($_SESSION['role'] === 'Admin'): ?>
                           <a class="dropdown-item" href="admin_dashboard.php">
                              <i class="fa fa-cog mr-2"></i>Admin
                           </a>
                        <?php endif; ?>
                        <div class="dropdown-divider"></div>
                        <form method="post" action="logout.php" style="margin:0;">
                           <button type="submit" class="dropdown-item">
                              <i class="fa fa-sign-out mr-2"></i>Logout
                           </button>
                        </form>
                     </div>
                  </div>
               <?php else: ?>
                  <div class="auth-buttons">
                     <a href="login.php" class="btn btn-outline-primary btn-sm mr-2">
                        <i class="fa fa-sign-in mr-1"></i>Login
                     </a>
                     <a href="register.php" class="btn btn-primary btn-sm">
                        <i class="fa fa-user-plus mr-1"></i>Register
                     </a>
                  </div>
               <?php endif; ?>
            </div>
         </div>
      </header>
      
      <!-- Search Modal -->
      <div class="modal fade" id="searchModal" tabindex="-1" role="dialog" aria-labelledby="searchModalLabel" aria-hidden="true">
         <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
               <div class="modal-header">
                  <h5 class="modal-title" id="searchModalLabel">
                     <i class="fa fa-search mr-2"></i>Search Room
                  </h5>
                  <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                     <span aria-hidden="true">&times;</span>
                  </button>
               </div>
               <div class="modal-body">
                  <form action="room.php" method="GET">
                     <div class="row">
                        <div class="col-md-6">
                           <div class="form-group">
                              <label for="checkin">Check-in</label>
                              <input type="date" class="form-control" id="checkin" name="checkin" required>
                           </div>
                        </div>
                        <div class="col-md-6">
                           <div class="form-group">
                              <label for="checkout">Check-out</label>
                              <input type="date" class="form-control" id="checkout" name="checkout" required>
                           </div>
                        </div>
                     </div>
                     <div class="row">
                        <div class="col-md-6">
                           <div class="form-group">
                              <label for="guests">Guests</label>
                              <select class="form-control" id="guests" name="guests">
                                 <option value="1">1 guest</option>
                                 <option value="2">2 guest</option>
                                 <option value="3">3 guest</option>
                                 <option value="4">4 guest</option>
                                 <option value="5">5+ guest</option>
                              </select>
                           </div>
                        </div>
                        <div class="col-md-6">
                           <div class="form-group">
                                 <label for="room_type">Room Type</label>
                              <select class="form-control" id="room_type" name="room_type">
                                 <option value="">All Room Type</option>
                                 <option value="Standard">Standard Room</option>
                                 <option value="Deluxe">Deluxe Room</option>
                                 <option value="Suite">Suite Room</option>
                              </select>
                           </div>
                        </div>
                     </div>
                     <div class="text-center mt-3">
                        <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fa fa-search mr-2"></i>Search Room
                        </button>
                     </div>
                  </form>
               </div>
            </div>
         </div>
      </div>

      <div class="back_re">
         <div class="container">
            <div class="row">
               <div class="col-md-12">
                  <div class="title">
                     <h2>About Us</h2>
                  </div>
               </div>
            </div>
         </div>
      </div>

      <!-- about -->
      <div class="about">
         <div class="container-fluid">
            <div class="row">
               <div class="col-md-5">
                  <div class="titlepage">
                     <h2>Our Story</h2>
                     <p class="margin_0">Founded in 2010, our hotel has been a cornerstone of luxury hospitality in the heart of the city. What began as a small boutique hotel has grown into one of the most prestigious destinations for discerning travelers. Our commitment to excellence and personalized service has earned us numerous awards and the loyalty of guests from around the world.</p>
                     
                     <h3 class="mt-4">Our Mission</h3>
                     <p>We strive to create extraordinary experiences that exceed expectations, providing a perfect blend of comfort, luxury, and authentic local culture. Every guest interaction is an opportunity to create lasting memories.</p>
                     
                     <h3 class="mt-4">Why Choose Us</h3>
                     <ul class="feature-list">
                        <li><i class="fa fa-check-circle text-success"></i> Prime location in the city center</li>
                        <li><i class="fa fa-check-circle text-success"></i> Award-winning service excellence</li>
                        <li><i class="fa fa-check-circle text-success"></i> Luxurious accommodations</li>
                        <li><i class="fa fa-check-circle text-success"></i> World-class amenities</li>
                        <li><i class="fa fa-check-circle text-success"></i> 24/7 personalized support</li>
                     </ul>
                     <br>
                     
                     <a class="read_more" href="contact.php">Contact Us</a>
                  </div>
               </div>
               <div class="col-md-7">
                  <div class="about_img">
                     <figure><img src="images/about.png" alt="Hotel Exterior"/></figure>
                  </div>
               </div>
            </div>
         </div>
      </div>
      <br><br><br><br>
      <!-- Services Section -->
      <div class="services_section">
         <div class="container">
            <div class="row">
               <div class="col-md-12">
                  <div class="titlepage text-center">
                     <h2>Our Services</h2>
                     <p>Discover the exceptional services that make your stay unforgettable</p>
                  </div>
               </div>
            </div>
            <div class="row">
               <div class="col-md-4">
                  <div class="service_box text-center">
                     <i class="fa fa-bed fa-3x text-primary mb-3"></i>
                     <h4>Luxury Accommodations</h4>
                     <p>Experience comfort and elegance in our carefully designed rooms, featuring premium amenities and stunning city views.</p>
                  </div>
               </div>
               <div class="col-md-4">
                  <div class="service_box text-center">
                     <i class="fa fa-cutlery fa-3x text-primary mb-3"></i>
                     <h4>Fine Dining</h4>
                     <p>Savor exquisite cuisine prepared by our award-winning chefs using the finest local and international ingredients.</p>
                  </div>
               </div>
               <div class="col-md-4">
                  <div class="service_box text-center">
                     <i class="fa fa-heart fa-3x text-primary mb-3"></i>
                     <h4>Wellness & Spa</h4>
                     <p>Rejuvenate your body and mind with our comprehensive spa services and state-of-the-art fitness facilities.</p>
                  </div>
               </div>
            </div>
         </div>
      </div>
      <br><br><br><br>
      <!-- Team Section -->
      <div class="team_section">
         <div class="container">
            <div class="row">
               <div class="col-md-12">
                  <div class="titlepage text-center">
                     <h2>Our Commitment</h2>
                     <p>We are dedicated to providing exceptional service and creating memorable experiences</p>
                  </div>
               </div>
            </div>
            <div class="row">
               <div class="col-md-6">
                  <div class="commitment_box">
                     <h4><i class="fa fa-star text-warning"></i> Service Excellence</h4>
                     <p>Our team of hospitality professionals is committed to delivering personalized service that exceeds your expectations. From the moment you arrive until your departure, we ensure every detail is perfect.</p>
                  </div>
               </div>
               <div class="col-md-6">
                  <div class="commitment_box">
                     <h4><i class="fa fa-leaf text-success"></i> Environmental Responsibility</h4>
                     <p>We are committed to sustainable practices and environmental conservation. Our hotel implements eco-friendly initiatives to minimize our environmental impact while maintaining luxury standards.</p>
                  </div>
               </div>
            </div>
         </div>
      </div>
      <!-- end about -->

      <!-- footer -->
      <footer>
         <div class="footer">
            <div class="container">
               <div class="row">
                  <div class=" col-md-4">
                     <h3>Contact US</h3>
                     <ul class="conta">
                        <li><i class="fa fa-map-marker" aria-hidden="true"></i> Dong Lao, Xa An Khanh, Ha Noi</li>
                        <li><i class="fa fa-mobile" aria-hidden="true"></i> +84 795067992</li>
                        <li> <i class="fa fa-envelope" aria-hidden="true"></i><a href="#"> demo@gmail.com</a></li>
                     </ul>
                  </div>
                  <div class="col-md-4">
                     <h3>Menu Link</h3>
                     <ul class="link_menu">
                        <li><a href="index.php">Home</a></li>
                        <li class="active"><a href="about.php">About</a></li>
                        <li><a href="room.php">Our Room</a></li>
                        <li><a href="gallery.php">Gallery</a></li>
                        <li><a href="blog.php">Blog</a></li>
                        <li><a href="contact.php">Contact Us</a></li>
                     </ul>
                  </div>
                  <div class="col-md-4">
                     <h3>Newsletter</h3>
                     <form class="bottom_form">
                        <input class="enter" placeholder="Enter your email" type="text" name="Enter your email">
                        <button class="sub_btn">subscribe</button>
                     </form>
                     <ul class="social_icon">
                        <li><a href="#"><i class="fa fa-facebook" aria-hidden="true"></i></a></li>
                        <li><a href="#"><i class="fa fa-twitter" aria-hidden="true"></i></a></li>
                        <li><a href="#"><i class="fa fa-linkedin" aria-hidden="true"></i></a></li>
                        <li><a href="#"><i class="fa fa-youtube-play" aria-hidden="true"></i></a></li>
                     </ul>
                  </div>
               </div>
            </div>
            <div class="copyright">
               <div class="container">
                  <div class="row">
                     <div class="col-md-10 offset-md-1">
                     <p>
                        © 2025 All Rights Reserved.
                        </p>
                     </div>
                  </div>
               </div>
            </div>
         </div>
      </footer>

      <!-- scripts -->
      <script src="js/jquery.min.js"></script>
      <script src="js/bootstrap.bundle.min.js"></script>
      <script src="js/jquery.mCustomScrollbar.concat.min.js"></script>
      <script src="js/custom.js"></script>
      
      <!-- Header JavaScript -->
      <script>
      document.addEventListener('DOMContentLoaded', function() {
        // Profile dropdown functionality
        var profileBtn = document.querySelector('.profile-btn');
        if (profileBtn) {
          profileBtn.addEventListener('click', function(e) {
            e.preventDefault();
            var dropdown = this.closest('.profile-dropdown');
            dropdown.classList.toggle('open');
          });
          
          // Close dropdown when clicking outside
          document.addEventListener('click', function(e) {
            var dropdown = document.querySelector('.profile-dropdown');
            if (dropdown && !dropdown.contains(e.target)) {
              dropdown.classList.remove('open');
            }
          });
        }
        
        // Search modal functionality
        var searchModal = document.getElementById('searchModal');
        if (searchModal) {
          // Set minimum date for check-in and check-out
          var today = new Date().toISOString().split('T')[0];
          var checkinInput = document.getElementById('checkin');
          var checkoutInput = document.getElementById('checkout');
          
          if (checkinInput) {
            checkinInput.min = today;
            checkinInput.addEventListener('change', function() {
              if (checkoutInput) {
                checkoutInput.min = this.value;
                if (checkoutInput.value && checkoutInput.value <= this.value) {
                  checkoutInput.value = '';
                }
              }
            });
          }
          
          if (checkoutInput) {
            checkoutInput.min = today;
          }
          
          // Ensure select dropdown works
          var selectElements = searchModal.querySelectorAll('select.form-control');
          selectElements.forEach(function(select) {
            // Disable nice-select for select in modal
            if (select.nextElementSibling && select.nextElementSibling.classList.contains('nice-select')) {
              select.nextElementSibling.style.display = 'none';
            }
            
            // Ensure select displays correctly
            select.style.display = 'block';
            select.style.opacity = '1';
            select.style.visibility = 'visible';
            
            select.addEventListener('click', function(e) {
              e.stopPropagation();
            });
            
            select.addEventListener('change', function() {
              console.log('Select changed:', this.value);
              // Đảm bảo text hiển thị đúng
              this.style.color = '#333';
              this.style.backgroundColor = '#fff';
            });
            
            // Đảm bảo option hiển thị đúng
            var options = select.querySelectorAll('option');
            options.forEach(function(option) {
              option.style.color = '#333';
              option.style.backgroundColor = '#fff';
            });
          });
          
          // Đảm bảo modal không bị đóng khi click vào select
          searchModal.addEventListener('click', function(e) {
            if (e.target.tagName === 'SELECT' || e.target.closest('select')) {
              e.stopPropagation();
            }
          });
          
          // Xử lý khi modal được mở
          $('#searchModal').on('shown.bs.modal', function() {
            var selectElements = this.querySelectorAll('select.form-control');
            selectElements.forEach(function(select) {
              // Vô hiệu hóa nice-select
              if (select.nextElementSibling && select.nextElementSibling.classList.contains('nice-select')) {
                select.nextElementSibling.style.display = 'none';
              }
              select.style.display = 'block';
              select.style.opacity = '1';
              select.style.visibility = 'visible';
            });
          });
        }
        
        // Header scroll effect
        var header = document.querySelector('.header');
        if (header) {
          window.addEventListener('scroll', function() {
            if (window.scrollY > 50) {
              header.classList.add('scrolled');
            } else {
              header.classList.remove('scrolled');
            }
          });
        }
      });
      </script>
   </body>
</html>
