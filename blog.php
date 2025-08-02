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
      
      <style>
      /* Blog Card Enhancements */
      .blog .row {
        display: flex;
        flex-wrap: wrap;
      }
      
      .blog .col-md-4 {
        display: flex;
        margin-bottom: 30px;
      }
      
      .blog_box {
        width: 100%;
        height: 100%;
        display: flex;
        flex-direction: column;
        background: #fff;
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
      }
      
      .blog_box:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 35px rgba(0,0,0,0.15);
      }
      
      .blog_img {
        flex-shrink: 0;
        height: 220px;
        overflow: hidden;
      }
      
      .blog_img figure {
        margin: 0;
        padding: 0;
        width: 100%;
        height: 100%;
      }
      
      .blog_img img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s ease;
      }
      
      .blog_box:hover .blog_img img {
        transform: scale(1.05);
      }
      
      .blog_room {
        padding: 25px;
        flex: 1;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
      }
      
      .blog_room h3 {
        color: #333;
        font-weight: 700;
        margin-bottom: 10px;
        font-size: 1.3rem;
        line-height: 1.3;
        min-height: 2.6rem;
        display: flex;
        align-items: center;
      }
      
      .blog_room span {
        color: #666;
        font-size: 0.9rem;
        margin-bottom: 15px;
        display: block;
      }
      
      .blog_room span i {
        margin-right: 5px;
        color: #007bff;
      }
      
      .blog_room p {
        color: #666;
        font-size: 0.95rem;
        line-height: 1.6;
        margin-bottom: 20px;
        flex: 1;
      }
      
      .read_more_btn {
        color: #007bff;
        text-decoration: none;
        font-weight: 600;
        font-size: 0.9rem;
        transition: all 0.3s ease;
        align-self: flex-start;
        margin-top: auto;
      }
      
      .read_more_btn:hover {
        color: #0056b3;
        text-decoration: none;
      }
      
      .read_more_btn i {
        margin-left: 5px;
        transition: transform 0.3s ease;
      }
      
      .read_more_btn:hover i {
        transform: translateX(3px);
      }
      
      /* Newsletter Section */
      .newsletter_section {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        padding: 40px 20px;
        border-radius: 15px;
        margin-top: 50px;
      }
      
      .newsletter_section h3 {
        color: #333;
        font-weight: 700;
        margin-bottom: 15px;
      }
      
      .newsletter_section p {
        color: #666;
        font-size: 1rem;
        margin-bottom: 25px;
      }
      
      .newsletter_form .input-group {
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        border-radius: 25px;
        overflow: hidden;
      }
      
      .newsletter_form .form-control {
        border: none;
        padding: 15px 20px;
        font-size: 1rem;
      }
      
      .newsletter_form .form-control:focus {
        box-shadow: none;
        border-color: #007bff;
      }
      
      .newsletter_form .btn {
        border-radius: 0 25px 25px 0;
        padding: 15px 30px;
        font-weight: 600;
        background: linear-gradient(135deg,rgb(255, 51, 0) 0%,rgb(179, 0, 0) 100%);
        border: none;
        transition: all 0.3s ease;
      }
      
      .newsletter_form .btn:hover {
        background: linear-gradient(135deg,rgb(179, 54, 0) 0%, #004085 100%);
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0, 123, 255, 0.3);
      }
      
      /* Responsive adjustments */
      @media (max-width: 768px) {
        .blog_room {
          padding: 20px;
        }
        
        .blog_room h3 {
          font-size: 1.1rem;
          min-height: auto;
        }
        
        .blog_room p {
          font-size: 0.9rem;
        }
        
        .newsletter_section {
          padding: 30px 15px;
        }
        
        .newsletter_form .form-control,
        .newsletter_form .btn {
          padding: 12px 15px;
          font-size: 0.9rem;
        }
      }
      </style>
   </head>
   <!-- body -->
   <body class="main-layout inner_page">
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
                     <li class="nav-item active">
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
                      <h2>Blog</h2>
                  </div>
               </div>
            </div>
         </div>
      </div>
      <!-- blog -->
      <div  class="blog">
         <div class="container">
            <div class="row">
               <div class="col-md-12">
                  <div class="titlepage">
                     <h2>Latest News & Insights</h2>
                     <p class="margin_0">Discover the latest updates, travel tips, and exclusive insights from our luxury hotel experience</p>
                  </div>
               </div>
            </div>
            <div class="row">
               <div class="col-md-4">
                  <div class="blog_box">
                     <div class="blog_img">
                        <figure><img src="images/blog1.jpg" alt="Luxury Room Experience"/></figure>
                     </div>
                     <div class="blog_room">
                        <h3>Luxury Room Experience</h3>
                        <span><i class="fa fa-calendar"></i> January 15, 2025</span>
                        <p>Experience the epitome of luxury with our newly renovated premium suites. Each room is meticulously designed to provide the perfect blend of comfort and elegance, featuring state-of-the-art amenities and breathtaking city views that will make your stay truly unforgettable.</p>
                        <a href="#" class="read_more_btn">Read More <i class="fa fa-arrow-right"></i></a>
                     </div>
                  </div>
               </div>
               <div class="col-md-4">
                  <div class="blog_box">
                     <div class="blog_img">
                        <figure><img src="images/blog2.jpg" alt="Fine Dining Experience"/></figure>
                     </div>
                     <div class="blog_room">
                        <h3>Fine Dining Experience</h3>
                        <span><i class="fa fa-calendar"></i> January 10, 2025</span>
                        <p>Indulge in culinary excellence at our award-winning restaurant. Our master chefs create extraordinary dishes using the finest local and international ingredients, offering a gastronomic journey that celebrates both traditional flavors and innovative cuisine.</p>
                        <a href="#" class="read_more_btn">Read More <i class="fa fa-arrow-right"></i></a>
                     </div>
                  </div>
               </div>
               <div class="col-md-4">
                  <div class="blog_box">
                     <div class="blog_img">
                        <figure><img src="images/blog3.jpg" alt="Wellness & Spa"/></figure>
                     </div>
                     <div class="blog_room">
                        <h3>Wellness & Spa Retreat</h3>
                        <span><i class="fa fa-calendar"></i> January 5, 2025</span>
                        <p>Rejuvenate your body and mind at our world-class spa and wellness center. From therapeutic massages to advanced skincare treatments, our expert therapists provide personalized care to help you achieve complete relaxation and renewal.</p>
                        <a href="#" class="read_more_btn">Read More <i class="fa fa-arrow-right"></i></a>
                     </div>
                  </div>
               </div>
            </div>
            
            <!-- Additional Blog Posts -->
            <div class="row mt-5">
               <div class="col-md-4">
                  <div class="blog_box">
                     <div class="blog_img">
                        <figure><img src="images/blog1.jpg" alt="Local Attractions"/></figure>
                     </div>
                     <div class="blog_room">
                        <h3>Top Local Attractions</h3>
                        <span><i class="fa fa-calendar"></i> December 28, 2024</span>
                        <p>Explore the vibrant culture and rich history of our city with our curated guide to the best local attractions. From historic landmarks to modern entertainment venues, discover the hidden gems that make our destination truly special.</p>
                        <a href="#" class="read_more_btn">Read More <i class="fa fa-arrow-right"></i></a>
                     </div>
                  </div>
               </div>
               <div class="col-md-4">
                  <div class="blog_box">
                     <div class="blog_img">
                        <figure><img src="images/blog2.jpg" alt="Business Travel"/></figure>
                     </div>
                     <div class="blog_room">
                        <h3>Business Travel Excellence</h3>
                        <span><i class="fa fa-calendar"></i> December 20, 2024</span>
                        <p>Perfect your business travel experience with our comprehensive business facilities and services. From fully-equipped meeting rooms to high-speed internet and business support services, we ensure your work trip is as productive as it is comfortable.</p>
                        <a href="#" class="read_more_btn">Read More <i class="fa fa-arrow-right"></i></a>
                     </div>
                  </div>
               </div>
               <div class="col-md-4">
                  <div class="blog_box">
                     <div class="blog_img">
                        <figure><img src="images/blog3.jpg" alt="Seasonal Events"/></figure>
                     </div>
                     <div class="blog_room">
                        <h3>Seasonal Events & Festivals</h3>
                        <span><i class="fa fa-calendar"></i> December 15, 2024</span>
                        <p>Immerse yourself in the local culture with our guide to seasonal events and festivals. Experience the vibrant atmosphere of traditional celebrations and modern entertainment that showcase the unique character of our destination.</p>
                        <a href="#" class="read_more_btn">Read More <i class="fa fa-arrow-right"></i></a>
                     </div>
                  </div>
               </div>
            </div>
            
            <!-- Newsletter Subscription -->
            <div class="row mt-5">
               <div class="col-md-12">
                  <div class="newsletter_section text-center">
                     <h3>Stay Updated</h3>
                     <p>Subscribe to our newsletter for exclusive offers, travel tips, and the latest updates from our hotel.</p>
                     <form class="newsletter_form">
                        <div class="row justify-content-center">
                           <div class="col-md-6">
                              <div class="input-group">
                                 <input type="email" class="form-control" placeholder="Enter your email address" required>
                                 <div class="input-group-append">
                                    <button class="btn btn-primary" type="submit">Subscribe</button>
                                 </div>
                              </div>
                           </div>
                        </div>
                     </form>
                  </div>
               </div>
            </div>
         </div>
      </div>
      <!-- end blog -->
     
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
                        <li><a href="about.php"> about</a></li>
                        <li><a href="room.php">Our Room</a></li>
                        <li><a href="gallery.php">Gallery</a></li>
                        <li class="active"><a href="blog.php">Blog</a></li>
                        <li><a href="contact.php">Contact Us</a></li>
                     </ul>
                  </div>
                  <div class="col-md-4">
                     <h3>News letter</h3>
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