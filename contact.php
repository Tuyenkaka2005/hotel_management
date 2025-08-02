<?php
require 'config.php';
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
      <title>HOTEL</title>
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
      <!-- Tweaks for older IEs-->
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
                     <li class="nav-item">
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
                     <li class="nav-item active">
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
                           <i class="fa fa-user mr-2"></i>Profile
                        </a>
                        <a class="dropdown-item" href="booked_rooms.php">
                           <i class="fa fa-history mr-2"></i>Booked rooms
                        </a>
                        <?php if ($_SESSION['role'] === 'Admin'): ?>
                           <a class="dropdown-item" href="admin_dashboard.php">
                              <i class="fa fa-cog mr-2"></i>Admin dashboard
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
                     <i class="fa fa-search mr-2"></i>Search
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
                              <label for="guests">Number of guests</label>
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
                                 <label for="room_type">Room type</label>
                              <select class="form-control" id="room_type" name="room_type">
                                 <option value="">All room types</option>
                                 <option value="Standard">Standard room</option>
                                 <option value="Deluxe">Deluxe room</option>
                                 <option value="Suite">Suite room</option>
                              </select>
                           </div>
                        </div>
                     </div>
                     <div class="text-center mt-3">
                        <button type="submit" class="btn btn-primary btn-lg">
                           <i class="fa fa-search mr-2"></i>Search room
                        </button>
                     </div>
                  </form>
               </div>
            </div>
         </div>
      </div>
      <!-- end header -->
     <div class="back_re">
         <div class="container">
            <div class="row">
               <div class="col-md-12">
                  <div class="title">
                      <h2>Contact Us</h2>
                  </div>
               </div>
            </div>
         </div>
      </div>
      <!--  contact -->
      <div class="contact">
         <div class="container">
            <div class="row">
               <div class="col-md-6">
                  <form id="request" class="main_form">
                     <div class="row">
                        <div class="col-md-12 ">
                           <input class="contactus" placeholder="Name" type="type" name="Name"> 
                        </div>
                        <div class="col-md-12">
                           <input class="contactus" placeholder="Email" type="type" name="Email"> 
                        </div>
                        <div class="col-md-12">
                           <input class="contactus" placeholder="Phone Number" type="type" name="Phone Number">                          
                        </div>
                        <div class="col-md-12">
                           <textarea class="textarea" placeholder="Message" type="type" Message="Name">Message</textarea>
                        </div>
                        <div class="col-md-12">
                           <button class="send_btn">Send</button>
                        </div>
                     </div>
                  </form>
               </div>
               <div class="col-md-6">
                  <div class="map_main">
                     <div class="map-responsive">
                        <iframe src="https://maps.google.com/maps?q=Đ.+Xuân+Thủy+Ngõ+26+Phòng+102,+Cầu+Giấy,+Hà+Nội,+Việt+Nam&t=&z=15&ie=UTF8&iwloc=&output=embed" width="600" height="400" frameborder="0" style="border:0; width: 100%;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                     </div>
                  </div>
               </div>
            </div>
         </div>
      </div>
      <!-- end contact -->
      <!--  footer -->
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
                        <li><a href="about.php"> About</a></li>
                        <li><a href="room.php">Rooms</a></li>
                        <li><a href="gallery.php">Gallery</a></li>
                        <li><a href="blog.php">Blog</a></li>
                        <li class="active"><a href="contact.php">Contact Us</a></li>
                     </ul>
                  </div>
                  <div class="col-md-4">
                     <h3>News letter</h3>
                     <form class="bottom_form">
                        <input class="enter" placeholder="Enter your email" type="text" name="Enter your email">
                        <button class="sub_btn">Subscribe</button>
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
      <!-- end footer -->
      <!-- Javascript files-->
      <script src="js/jquery.min.js"></script>
      <script src="js/bootstrap.bundle.min.js"></script>
      <script src="js/jquery-3.0.0.min.js"></script>
      <!-- sidebar -->
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
          
          // Đảm bảo select dropdown hoạt động
          var selectElements = searchModal.querySelectorAll('select.form-control');
          selectElements.forEach(function(select) {
            // Vô hiệu hóa nice-select cho select trong modal
            if (select.nextElementSibling && select.nextElementSibling.classList.contains('nice-select')) {
              select.nextElementSibling.style.display = 'none';
            }
            
            // Đảm bảo select hiển thị đúng
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