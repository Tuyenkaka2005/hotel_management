<?php
session_start();
if (isset($_POST['book_now'])) {
    $checkin = $_POST['checkin'];
    $checkout = $_POST['checkout'];
    if (!isset($_SESSION['account_id'])) {
        header('Location: login.php?redirect=booking.php&checkin=' . urlencode($checkin) . '&checkout=' . urlencode($checkout));
        exit;
    } else {
        header('Location: booking.php?checkin=' . urlencode($checkin) . '&checkout=' . urlencode($checkout));
        exit;
    }
}
require_once 'config.php';
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

      <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js" integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
      <script src="js/custom.js"></script>

      <!-- Tweaks for older IEs-->
      <link rel="stylesheet" href="https://netdna.bootstrapcdn.com/font-awesome/4.0.3/css/font-awesome.css">
      <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/fancybox/2.1.5/jquery.fancybox.min.css" media="screen">
      <style>
      .profile-dropdown {
        position: relative;
        display: inline-block;
      }
      .profile-btn {
        background: none;
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 16px;
        color: #333;
        padding: 0;
      }
      .profile-avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        object-fit: cover;
        border: 1.5px solid #eee;
      }
      .profile-menu {
        display: none;
        position: absolute;
        right: 0;
        top: 120%;
        background: #fff;
        min-width: 150px;
        box-shadow: 0 4px 16px rgba(0,0,0,0.12);
        border-radius: 8px;
        z-index: 1000;
      }
      .profile-dropdown.open .profile-menu {
        display: block;
      }
      .profile-menu a, .profile-menu form button {
        display: block;
        width: 100%;
        padding: 10px 18px;
        color: #333;
        text-decoration: none;
        background: none;
        border: none;
        text-align: left;
        font-size: 15px;
        cursor: pointer;
      }
      .profile-menu a:hover, .profile-menu form button:hover {
        background: #f4f6fb;
      }
      
      /* Our Room Button Styles */
      .room .btn {
        border-radius: 25px;
        font-weight: 600;
        font-size: 0.9rem;
        padding: 10px 20px;
        transition: all 0.3s ease;
        border: none;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        position: relative;
        overflow: hidden;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
      }
      
      .room .btn::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
        transition: left 0.5s;
      }
      
      .room .btn:hover::before {
        left: 100%;
      }
      
      .room .btn-success {
        background: linear-gradient(135deg, #c0392b, #a93226);
        color: white;
        box-shadow: 0 6px 20px rgba(40, 167, 69, 0.3);
      }
      
      .room .btn-success:hover {
        background: linear-gradient(135deg, #218838 0%, #1ea085 100%);
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(40, 167, 69, 0.4);
        color: white;
      }
      
      .room .btn-info {
        background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
        color: white;
        box-shadow: 0 6px 20px rgba(23, 162, 184, 0.3);
      }
      
      .room .btn-info:hover {
        background: linear-gradient(135deg, #138496 0%, #117a8b 100%);
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(23, 162, 184, 0.4);
        color: white;
      }
      
      .room .btn-secondary {
        background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
        color: white;
        box-shadow: 0 6px 20px rgba(108, 117, 125, 0.3);
        cursor: not-allowed;
      }
      
      .room .btn-secondary:hover {
        background: linear-gradient(135deg, #5a6268 0%, #495057 100%);
        transform: none;
        box-shadow: 0 6px 20px rgba(108, 117, 125, 0.3);
        color: white;
      }
      
      .room .btn-secondary:disabled {
        opacity: 0.7;
        cursor: not-allowed;
      }
      
      /* Rating and Booking Count Styles */
      .room-rating {
        display: flex;
        align-items: center;
        margin-bottom: 10px;
      }
      
      .rating-stars {
        display: flex;
        align-items: center;
        font-size: 0.9rem;
      }
      
      .rating-stars i {
        margin-right: 2px;
        font-size: 1rem;
      }
      
      .rating-stars .fa-star {
        color: #ffd700 !important;
      }
      
      .rating-stars .fa-star-half-o {
        color: #ffd700 !important;
      }
      
      .rating-stars .fa-star-o {
        color: #ddd !important;
      }
      
      .rating-text {
        font-size: 0.85rem;
        line-height: 1.2;
      }
      
      .rating-text strong {
        color: #333;
        font-weight: 600;
      }
      
      .rating-text small {
        color: #6c757d;
      }
      
      .booking-count {
        margin-bottom: 10px;
        color: #666;
        font-size: 0.9rem;
      }
      
      .booking-count i {
        color: #28a745;
      }
      
      .booking-count strong {
        color: #333;
        font-weight: 600;
      }
      
      /* Room Card Enhancements */
      .room {
        transition: all 0.3s ease;
        border-radius: 15px !important;
        overflow: hidden;
        height: 100%;
        display: flex;
        flex-direction: column;
        background: #fff;
        border: 1px solid #e9ecef;
      }
      
      .room:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 35px rgba(0,0,0,0.1) !important;
      }
      
      .room_img {
        overflow: hidden;
        border-radius: 10px;
        flex-shrink: 0;
        height: 220px;
        width: 100%;
        position: relative;
      }
      
      .room_img figure {
        margin: 0;
        padding: 0;
        width: 100%;
        height: 100%;
      }
      
      .room_img img {
        transition: transform 0.3s ease;
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
        margin: 0;
        padding: 0;
      }
      
      .room:hover .room_img img {
        transform: scale(1.05);
      }
      
      .bed_room {
        padding: 20px;
        flex: 1;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
      }
      
      .bed_room h4 {
        color: #333;
        font-weight: 700;
        margin-bottom: 15px;
        font-size: 1.2rem;
        line-height: 1.3;
        min-height: 2.6rem;
        display: flex;
        align-items: center;
      }
      
      .bed_room p {
        margin-bottom: 10px;
        color: #666;
        font-size: 0.95rem;
        line-height: 1.4;
      }
      
      .bed_room strong {
        color: #333;
        font-weight: 600;
      }
      
      /* Room info section */
      .room-info {
        flex: 1;
        margin-bottom: 15px;
      }
      
      /* Badge Enhancements */
      .badge {
        border-radius: 20px;
        padding: 8px 15px;
        font-size: 0.8rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
      }
      
      .badge.bg-success {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%) !important;
        box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
      }
      
      .badge.bg-danger {
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%) !important;
        box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
      }
      
      /* Button Container */
      .room .btn-container {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        margin-top: auto;
        flex-shrink: 0;
      }
      
      .room .btn-container .btn {
        flex: 1;
        min-width: 120px;
      }
      
      /* Ensure equal height for room cards */
      .our_room .row {
        display: flex;
        flex-wrap: wrap;
      }
      
      .our_room .col-md-4 {
        display: flex;
        margin-bottom: 30px;
      }
      
      .our_room .col-md-4 .room {
        width: 100%;
      }
      
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
      
      /* Responsive adjustments */
      @media (max-width: 768px) {
        .room .btn {
          padding: 8px 16px;
          font-size: 0.8rem;
        }
        
        .room .btn-container {
          flex-direction: column;
        }
        
        .room .btn-container .btn {
          width: 100%;
        }
        
        .rating-stars {
          font-size: 0.8rem;
        }
        
        .rating-stars i {
          font-size: 0.9rem;
        }
        
        .rating-text {
          font-size: 0.8rem;
        }
        
        .booking-count {
          font-size: 0.8rem;
        }
        
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
      }
      </style>
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
                     <li class="nav-item active">
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
                           <i class="fa fa-user mr-2"></i>Profile 
                        </a>
                        <a class="dropdown-item" href="my_bookings.php">
                           <i class="fa fa-calendar mr-2"></i>My Bookings
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
      <!-- end header -->
      <!-- banner -->
      <section class="banner_main">
         <div id="myCarousel" class="carousel slide banner" data-ride="carousel">
            <ol class="carousel-indicators">
               <li data-target="#myCarousel" data-slide-to="0" class="active"></li>
               <li data-target="#myCarousel" data-slide-to="1"></li>
               <li data-target="#myCarousel" data-slide-to="2"></li>
            </ol>
            <div class="carousel-inner">
               <div class="carousel-item active">
                  <img class="first-slide" src="images/banner1.jpg" alt="First slide">
                  <div class="container">
                  </div>
               </div>
               <div class="carousel-item">
                  <img class="second-slide" src="images/banner2.jpg" alt="Second slide">
               </div>
               <div class="carousel-item">
                  <img class="third-slide" src="images/banner3.jpg" alt="Third slide">
               </div>
            </div>
            <a class="carousel-control-prev" href="#myCarousel" role="button" data-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="sr-only">Previous</span>
            </a>
            <a class="carousel-control-next" href="#myCarousel" role="button" data-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="sr-only">Next</span>
            </a>
         </div>
         <div class="booking_ocline">
            <div class="container">
               <div class="row">
                  <div class="col-md-5">
                     <!-- <div class="book_room">
                        <h1>Book a Room Online</h1>
                        <form class="book_now" method="post" action="">
                           <div class="row">
                              <div class="col-md-12">
                                 <span>Arrival</span>
                                 <img class="date_cua" src="images/date.png">
                                 <input class="online_book" placeholder="dd/mm/yyyy" type="date" name="checkin" required>
                              </div>
                              <div class="col-md-12">
                                 <span>Departure</span>
                                 <img class="date_cua" src="images/date.png">
                                 <input class="online_book" placeholder="dd/mm/yyyy" type="date" name="checkout" required>
                              </div>
                              <div class="col-md-12">
                                 <button class="book_btn" type="submit" name="book_now">Book Now</button>
                              </div>
                           </div>
                        </form>
                     </div> -->
                  </div>
               </div>
            </div>
         </div>
      </section>
      <!-- end banner -->
      <!-- about -->
      <div class="about">
         <div class="container-fluid">
            <div class="row">
               <div class="col-md-5">
                  <div class="titlepage">
                     <h2>About Us</h2>
                     <p>Welcome to our luxurious hotel, where comfort meets elegance in the heart of the city. Since our establishment, we have been committed to providing exceptional hospitality services and creating unforgettable experiences for our guests. Our dedicated team ensures that every stay is memorable, from our beautifully appointed rooms to our world-class amenities and personalized service. Whether you're here for business or leisure, we promise to make your stay extraordinary.</p>
                     <a class="read_more" href="about.php"> Read More</a>
                  </div>
               </div>
               <div class="col-md-7">
                  <div class="about_img">
                     <figure><img src="images/about.png" alt="#"/></figure>
                  </div>
               </div>
            </div>
         </div>
      </div>
      <!-- end about -->
      <!-- our_room -->
      <div class="our_room">
   <div class="container">
      <div class="row">
         <div class="col-md-12">
            <div class="titlepage">
               <h2>Our Room</h2>
               <p>Discover our top-rated available rooms with the highest bookings and best customer reviews.</p>
            </div>
         </div>
      </div>
      <div class="row">
         <?php
         require 'config.php';

         $today = date('Y-m-d');

         // Lấy 3 phòng có số lượng đặt và số sao cao nhất, chỉ hiển thị phòng Available
         $sql = "SELECT r.*, rt.TypeName, r.Description AS RoomDescription,
                        (SELECT ImagePath FROM RoomImage WHERE RoomID = r.RoomID LIMIT 1) AS FirstImage,
                        CASE 
                            WHEN r.Status = 'Maintenance' THEN 'Maintenance'
                            WHEN r.Status = 'Occupied' THEN 'Occupied'
                            WHEN r.Status = 'Reserved' THEN 'Reserved'
                            ELSE 'Available'
                        END AS DisplayStatus,
                        (
                            SELECT COUNT(*) FROM Reservation res
                            WHERE res.RoomID = r.RoomID
                        ) AS TotalBookings,
                        (
                            SELECT AVG(Rating) FROM Feedback 
                            WHERE RoomID = r.RoomID
                        ) AS AverageRating,
                        (
                            SELECT COUNT(*) FROM Feedback 
                            WHERE RoomID = r.RoomID
                        ) AS TotalReviews
                 FROM Room r
                 JOIN RoomType rt ON r.RoomTypeID = rt.RoomTypeID
                 WHERE r.Status = 'Available'
                 ORDER BY TotalBookings DESC, AverageRating DESC
                 LIMIT 3";
         $stmt = $pdo->prepare($sql);
         $stmt->execute();
         $rooms = $stmt->fetchAll();

         foreach ($rooms as $room):
            $img = $room['FirstImage'] ? 'uploads/' . htmlspecialchars($room['FirstImage']) : 'images/default.jpg';

            // Tạo status label dựa trên DisplayStatus
            $statusClass = '';
            switch($room['DisplayStatus']) {
                case 'Available':
                    $statusClass = 'bg-success';
                    break;
                case 'Reserved':
                    $statusClass = 'bg-warning';
                    break;
                case 'Occupied':
                    $statusClass = 'bg-danger';
                    break;
                case 'Maintenance':
                    $statusClass = 'bg-secondary';
                    break;
                default:
                    $statusClass = 'bg-info';
            }
            $status_label = '<span class="badge ' . $statusClass . ' text-white">' . $room['DisplayStatus'] . '</span>';
         ?>
         <div class="col-md-4 col-sm-6 mb-4">
            <div id="serv_hover" class="room border shadow-sm p-2 rounded">
               <div class="room_img">
                  <figure>
                     <img src="<?= $img ?>" alt="Room Image">
                  </figure>
               </div>
               <div class="bed_room mt-2">
                  <div class="room-info">
                     <h4><?= htmlspecialchars($room['RoomName']) ?> (<?= htmlspecialchars($room['RoomNumber']) ?>)</h4>
                     <p>Room Type: <strong><?= htmlspecialchars($room['TypeName']) ?></strong></p>
                     <p>Price: <strong><?= number_format($room['PricePerNight'], 0, ',', '.') ?> VNĐ/Night</strong></p>
                     <p><?= htmlspecialchars($room['RoomDescription']) ?></p>
                     <p>Status: <?= $status_label ?></p>
                     
                     <!-- Booking Count -->
                     <p class="booking-count">
                        <i class="fa fa-calendar-check mr-1"></i>
                        <strong><?= $room['TotalBookings'] ?></strong> bookings
                     </p>
                     
                     <!-- Rating Display -->
                     <div class="room-rating">
                        <?php 
                        $avgRating = $room['AverageRating'];
                        $totalReviews = $room['TotalReviews'];
                        
                        if ($avgRating && $totalReviews > 0): 
                            $fullStars = floor($avgRating);
                            $hasHalfStar = ($avgRating - $fullStars) >= 0.5;
                        ?>
                            <div class="rating-stars">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <?php if ($i <= $fullStars): ?>
                                        <i class="fa fa-star text-warning"></i>
                                    <?php elseif ($i == $fullStars + 1 && $hasHalfStar): ?>
                                        <i class="fa fa-star-half-o text-warning"></i>
                                    <?php else: ?>
                                        <i class="fa fa-star-o text-muted"></i>
                                    <?php endif; ?>
                                <?php endfor; ?>
                                <span class="rating-text ml-2">
                                    <strong><?= number_format($avgRating, 1) ?></strong>
                                    <small class="text-muted">(<?= $totalReviews ?> reviews)</small>
                                </span>
                            </div>
                        <?php else: ?>
                            <div class="rating-stars">
                                <i class="fa fa-star-o text-muted"></i>
                                <i class="fa fa-star-o text-muted"></i>
                                <i class="fa fa-star-o text-muted"></i>
                                <i class="fa fa-star-o text-muted"></i>
                                <i class="fa fa-star-o text-muted"></i>
                                <span class="rating-text ml-2">
                                    <small class="text-muted">No reviews yet</small>
                                </span>
                            </div>
                        <?php endif; ?>
                     </div>
                  </div>

                  <div class="btn-container">
                     <?php if ($room['DisplayStatus'] === 'Available'): ?>
                        <a href="booking.php?room_id=<?= $room['RoomID'] ?>" class="btn btn-success">
                           <i class="fa fa-calendar-check mr-1"></i>Book
                        </a>
                     <?php else: ?>
                        <button class="btn btn-secondary" disabled>
                           <i class="fa fa-times mr-1"></i><?= $room['DisplayStatus'] ?>
                        </button>
                     <?php endif; ?>

                     <a href="room_detail.php?room_id=<?= $room['RoomID'] ?>" class="btn btn-info">
                        <i class="fa fa-eye mr-1"></i>View
                     </a>
                  </div>
               </div>
            </div>
         </div>
         <?php endforeach; ?>
      </div>
   </div>
</div>






      <!-- end our_room -->
      <!-- gallery -->
      <div  class="gallery">
         <div class="container">
            <div class="row">
               <div class="col-md-12">
                  <div class="titlepage">
                     <h2>gallery</h2>
                  </div>
               </div>
            </div>
            <div class="row">
               <div class="col-md-3 col-sm-6">
                  <div class="gallery_img">
                     <figure><img src="images/gallery1.jpg" alt="#"/></figure>
                  </div>
               </div>
               <div class="col-md-3 col-sm-6">
                  <div class="gallery_img">
                     <figure><img src="images/gallery2.jpg" alt="#"/></figure>
                  </div>
               </div>
               <div class="col-md-3 col-sm-6">
                  <div class="gallery_img">
                     <figure><img src="images/gallery3.jpg" alt="#"/></figure>
                  </div>
               </div>
               <div class="col-md-3 col-sm-6">
                  <div class="gallery_img">
                     <figure><img src="images/gallery4.jpg" alt="#"/></figure>
                  </div>
               </div>
               <div class="col-md-3 col-sm-6">
                  <div class="gallery_img">
                     <figure><img src="images/gallery5.jpg" alt="#"/></figure>
                  </div>
               </div>
               <div class="col-md-3 col-sm-6">
                  <div class="gallery_img">
                     <figure><img src="images/gallery6.jpg" alt="#"/></figure>
                  </div>
               </div>
               <div class="col-md-3 col-sm-6">
                  <div class="gallery_img">
                     <figure><img src="images/gallery7.jpg" alt="#"/></figure>
                  </div>
               </div>
               <div class="col-md-3 col-sm-6">
                  <div class="gallery_img">
                     <figure><img src="images/gallery8.jpg" alt="#"/></figure>
                  </div>
               </div>
            </div>
         </div>
      </div>
      <!-- end gallery -->
      <!-- blog -->
      <div  class="blog">
         <div class="container">
            <div class="row">
               <div class="col-md-12">
                  <div class="titlepage">
                     <h2>Latest News & Insights</h2>
                     <p>Discover the latest updates, travel tips, and exclusive insights from our luxury hotel experience</p>
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
                        <p>Experience the epitome of luxury with our newly renovated premium suites. Each room is meticulously designed to provide the perfect blend of comfort and elegance, featuring state-of-the-art amenities and breathtaking city views.</p>
                        <a href="blog.php" class="read_more_btn">Read More <i class="fa fa-arrow-right"></i></a>
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
                        <a href="blog.php" class="read_more_btn">Read More <i class="fa fa-arrow-right"></i></a>
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
                        <a href="blog.php" class="read_more_btn">Read More <i class="fa fa-arrow-right"></i></a>
                     </div>
                  </div>
               </div>
            </div>
         </div>
      </div>
      <!-- end blog -->
      <!--  contact -->
      <div class="contact">
         <div class="container">
            <div class="row">
               <div class="col-md-12">
                  <div class="titlepage">
                     <h2>Contact Us</h2>
                  </div>
               </div>
            </div>
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
                        <li class="active"><a href="index.php">Home</a></li>
                        <li><a href="about.php"> About</a></li>
                        <li><a href="room.php">Our Room</a></li>
                        <li><a href="gallery.php">Gallery</a></li>
                        <li><a href="blog.php">Blog</a></li>
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
      <script src="js/jquery-3.6.0.min.js"></script>
      <script src="js/bootstrap.bundle.min.js"></script>
      <script src="js/jquery.mCustomScrollbar.concat.min.js"></script>
      <script src="js/custom.js"></script>
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
        
        // Smooth scrolling for navigation links
        var navLinks = document.querySelectorAll('.navbar-nav .nav-link');
        navLinks.forEach(function(link) {
          link.addEventListener('click', function(e) {
            var href = this.getAttribute('href');
            if (href && href.startsWith('#')) {
              e.preventDefault();
              var target = document.querySelector(href);
              if (target) {
                target.scrollIntoView({
                  behavior: 'smooth',
                  block: 'start'
                });
              }
            }
          });
        });
      });
      </script>
   </body>
</html>