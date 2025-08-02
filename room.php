<?php
require 'config.php';
session_start();

// Xử lý lọc phòng
$filter = [];
$params = [];

if (isset($_GET['checkin']) && isset($_GET['checkout']) && !empty($_GET['checkin']) && !empty($_GET['checkout'])) {
    $checkin = $_GET['checkin'];
    $checkout = $_GET['checkout'];
    // Lọc phòng chưa bị đặt trong khoảng ngày này
    $filter[] = "r.RoomID NOT IN (
        SELECT res.RoomID FROM Reservation res
        WHERE NOT (
            res.CheckOutDate <= :checkin OR res.CheckInDate >= :checkout
        )
    )";
    $params['checkin'] = $checkin;
    $params['checkout'] = $checkout;
}
if (isset($_GET['guests']) && !empty($_GET['guests'])) {
    $filter[] = 'r.MaxGuests >= :guests';
    $params['guests'] = (int)$_GET['guests'];
}
if (isset($_GET['room_type']) && $_GET['room_type'] !== '') {
    $filter[] = 'rt.TypeName = :room_type';
    $params['room_type'] = $_GET['room_type'];
}

// Lấy danh sách loại phòng để hiển thị trong menu filter
$roomTypesStmt = $pdo->query('SELECT DISTINCT rt.TypeName FROM RoomType rt JOIN Room r ON rt.RoomTypeID = r.RoomTypeID');
$roomTypes = $roomTypesStmt->fetchAll(PDO::FETCH_COLUMN);

$today = date('Y-m-d');
$sql = "SELECT r.*, rt.TypeName,
        (
            SELECT ImagePath FROM RoomImage 
            WHERE RoomID = r.RoomID LIMIT 1
        ) AS FirstImage,
        (
            SELECT COUNT(*) FROM Reservation res
            WHERE res.RoomID = r.RoomID 
              AND res.CheckInDate <= '$today' 
              AND res.CheckOutDate > '$today'
        ) AS IsBookedToday,
        CASE 
            WHEN r.Status = 'Maintenance' THEN 'Maintenance'
            WHEN r.Status = 'Occupied' THEN 'Occupied'
            WHEN r.Status = 'Reserved' THEN 'Reserved'
            ELSE 'Available'
        END AS DisplayStatus,
        (
            SELECT AVG(Rating) FROM Feedback 
            WHERE RoomID = r.RoomID
        ) AS AverageRating,
        (
            SELECT COUNT(*) FROM Feedback 
            WHERE RoomID = r.RoomID
        ) AS TotalReviews
        FROM Room r
        JOIN RoomType rt ON r.RoomTypeID = rt.RoomTypeID";
if (!empty($filter)) {
    $sql .= ' WHERE ' . implode(' AND ', $filter);
}

$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue(":$key", $value);
}
$stmt->execute();
$rooms = $stmt->fetchAll();
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
      <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/fancybox/2.1.5/jquery.fancybox.min.css" media="screen">
      <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script><![endif]-->
       <title>Danh sách khách sạn</title>
    <link rel="stylesheet" href="style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    
      <style>
         /* Room List Styles */
         .room-list-section {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            min-height: 100vh;
         }
         
         .room-card {
            background: #fff;
            border: none;
            transition: all 0.3s ease;
            cursor: pointer;
            height: 100%;
            display: flex;
            flex-direction: column;
            border-radius: 15px !important;
            overflow: hidden;
            border: 1px solid #e9ecef;
         }
         
         .room-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.1) !important;
         }
         
         .room-image {
            position: relative;
            overflow: hidden;
            flex-shrink: 0;
            height: 250px;
            width: 100%;
         }
         
         .room-image img {
            transition: transform 0.3s ease;
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            margin: 0;
            padding: 0;
         }
         
         .room-card:hover .room-image img {
            transform: scale(1.05);
         }
         
         .room-info {
            padding: 20px;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
         }
         
         .room-title {
            color: #333;
            font-weight: 700;
            margin-bottom: 15px;
            font-size: 1.2rem;
            line-height: 1.3;
            min-height: 2.6rem;
            display: flex;
            align-items: center;
         }
         
         .room-title a {
            color: #333;
            text-decoration: none;
            transition: color 0.3s ease;
         }
         
         .room-title a:hover {
            color: #007bff !important;
         }
         
         .room-details {
            border-top: 1px solid #f0f0f0;
            padding-top: 15px;
            margin-bottom: 15px;
            flex: 1;
         }
         
         .room-details p {
            margin-bottom: 10px;
            color: #666;
            font-size: 0.95rem;
            line-height: 1.4;
         }
         
         .room-details strong {
            color: #333;
            font-weight: 600;
         }
         
         .room-actions {
            border-top: 1px solid #f0f0f0;
            padding-top: 15px;
            margin-top: auto;
            flex-shrink: 0;
         }
         
         .room-actions .btn {
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
         
         .room-actions .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
         }
         
         .room-actions .btn:hover::before {
            left: 100%;
         }
         
         .room-actions .btn-success {
            background: linear-gradient(135deg, #c0392b, #a93226);
            color: white;
            box-shadow: 0 6px 20px rgba(192, 57, 43, 0.3);
         }
         
         .room-actions .btn-success:hover {
            background: linear-gradient(135deg, #a93226, #922b21);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(192, 57, 43, 0.4);
            color: white;
         }
         
         .room-actions .btn-info {
            background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
            color: white;
            box-shadow: 0 6px 20px rgba(23, 162, 184, 0.3);
         }
         
         .room-actions .btn-info:hover {
            background: linear-gradient(135deg, #138496 0%, #117a8b 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(23, 162, 184, 0.4);
            color: white;
         }
         
         .room-actions .btn-secondary {
            background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
            color: white;
            box-shadow: 0 6px 20px rgba(108, 117, 125, 0.3);
            cursor: not-allowed;
         }
         
         .room-actions .btn-secondary:hover {
            background: linear-gradient(135deg, #5a6268 0%, #495057 100%);
            transform: none;
            box-shadow: 0 6px 20px rgba(108, 117, 125, 0.3);
            color: white;
         }
         
         .room-actions .btn-secondary:disabled {
            opacity: 0.7;
            cursor: not-allowed;
         }
         
         .price-tag {
            background: linear-gradient(135deg, #007bff, #0056b3) !important;
            box-shadow: 0 2px 8px rgba(0,123,255,0.3);
         }
         
         .search-results-info {
            border-left: 4px solid #007bff;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef) !important;
         }
         
         .no-results {
            padding: 60px 20px;
         }
         
         .badge {
            border-radius: 20px;
            padding: 8px 15px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
         }
         
         .badge-success {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
         }
         
         .badge-danger {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
         }
         
         .badge-info {
            background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
            box-shadow: 0 4px 15px rgba(23, 162, 184, 0.3);
         }
         
         /* Rating Styles */
         .room-rating {
            display: flex;
            align-items: center;
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
         
         /* Ensure equal height for room cards */
         .room-list-section .row {
            display: flex;
            flex-wrap: wrap;
         }
         
         .room-list-section .col-lg-4 {
            display: flex;
            margin-bottom: 30px;
         }
         
         .room-list-section .col-lg-4 .room-card {
            width: 100%;
         }
         
         /* Responsive adjustments */
         @media (max-width: 768px) {
            .room-card {
               margin-bottom: 20px;
            }
            
            .room-image {
               height: 200px !important;
            }
            
            .room-actions .btn {
               padding: 8px 16px;
               font-size: 0.8rem;
            }
            
            .price-tag {
               font-size: 0.9rem;
               padding: 8px 12px !important;
            }
         }
         
         @media (max-width: 576px) {
            .room-image {
               height: 180px !important;
            }
            
            .room-info {
               padding: 15px !important;
            }
            
            .room-actions .btn {
               font-size: 0.8rem;
               padding: 6px 8px;
            }
            
            .room-actions .row {
               flex-direction: column;
            }
            
            .room-actions .col-6 {
               width: 100%;
               margin-bottom: 10px;
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
         }
         
         /* Room Type Filter Menu Styles */
         .room-filter-section {
            background: linear-gradient(135deg,rgb(215, 76, 76) 0%, #764ba2 100%);
            padding: 30px 0;
            margin-bottom: 30px;
         }
         
         .filter-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
         }
         
         .filter-title {
            color: #333;
            font-weight: 600;
            margin-bottom: 20px;
            text-align: center;
            font-size: 1.2rem;
         }
         
         .filter-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            justify-content: center;
         }
         
         .filter-btn {
            padding: 10px 20px;
            border: 2px solidrgb(215, 76, 76);
            background: transparent;
            color:rgb(215, 76, 76);
            border-radius: 25px;
            font-weight: 500;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            cursor: pointer;
         }
         
         .filter-btn:hover,
         .filter-btn.active {
            background: linear-gradient(135deg,rgb(215, 76, 76) 0%, #764ba2 100%);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
            text-decoration: none;
         }
         
         .filter-btn.active {
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
         }
         
         .filter-btn i {
            margin-right: 8px;
         }
         
         .filter-stats {
            text-align: center;
            margin-top: 15px;
            color: #666;
            font-size: 0.9rem;
         }
         
         .filter-stats .badge {
            background: linear-gradient(135deg,rgb(215, 76, 76) 0%, #764ba2 100%);
            color: white;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 0.8rem;
         }
         
         @media (max-width: 768px) {
            .filter-buttons {
               gap: 8px;
            }
            
            .filter-btn {
               padding: 8px 16px;
               font-size: 0.9rem;
            }
            
            .filter-container {
               padding: 20px 15px;
            }
         }
         
         @media (max-width: 576px) {
            .filter-buttons {
               flex-direction: column;
               align-items: center;
            }
            
            .filter-btn {
               width: 100%;
               max-width: 200px;
               text-align: center;
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
                     <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                     </li>
                     <li class="nav-item">
                        <a class="nav-link" href="about.php">About</a>
                     </li>
                     <li class="nav-item active">
                        <a class="nav-link" href="room.php">Rooms</a>
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
                           <i class="fa fa-history mr-2"></i>Booked Rooms
                        </a>
                        <?php if ($_SESSION['role'] === 'Admin'): ?>
                           <a class="dropdown-item" href="admin_dashboard.php">
                              <i class="fa fa-cog mr-2"></i>Admin Dashboard
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
                     <h2>Our Room</h2>
                  </div>
               </div>
            </div>
         </div>
      </div>
      
      <!-- Room Type Filter Menu -->
      <div class="room-filter-section">
         <div class="container">
            <div class="filter-container">
               <h4 class="filter-title">
                  <i class="fa fa-filter mr-2"></i>Filter by room type
               </h4>
               <div class="filter-buttons">
                  <a href="room.php" class="filter-btn <?= empty($_GET['room_type']) ? 'active' : '' ?>">
                     <i class="fa fa-th-large"></i>All rooms
                  </a>
                  <?php foreach ($roomTypes as $type): ?>
                  <a href="room.php?room_type=<?= urlencode($type) ?>" 
                     class="filter-btn <?= (isset($_GET['room_type']) && $_GET['room_type'] === $type) ? 'active' : '' ?>">
                     <i class="fa fa-bed"></i><?= htmlspecialchars($type) ?>
                  </a>
                  <?php endforeach; ?>
               </div>
               <div class="filter-stats">
                  <span class="badge">
                     <i class="fa fa-info-circle mr-1"></i>
                     <?= count($rooms) ?> rooms are displayed
                  </span>
               </div>
            </div>
         </div>
      </div>
      
      <!-- our_room -->
      <div class="our_room">
         <div class="container">
            <!-- Room List Section -->
            <div class="room-list-section py-5">
               <div class="container">
                  <div class="row">
                     <div class="col-md-12 mb-4">
                        <div class="titlepage text-center">
                           <h2 class="mb-3">🏨 Room List</h2>
                           <p class="text-muted">Explore our high-quality hotel rooms</p>
                        </div>
                     </div>
                  </div>
                  
                  <!-- Search Results Info -->
                  <?php if (!empty($_GET)): ?>
                  <div class="row mb-4">
                     <div class="col-12">
                        <div class="search-results-info bg-light p-3 rounded">
                           <h6 class="mb-2"><i class="fa fa-search mr-2"></i>Search results:</h6>
                           <div class="row">
                              <?php if (isset($_GET['checkin']) && isset($_GET['checkout'])): ?>
                              <div class="col-md-3">
                                 <small class="text-muted">Check-in:</small><br>
                                 <strong><?= date('d/m/Y', strtotime($_GET['checkin'])) ?></strong>
                              </div>
                              <div class="col-md-3">
                                 <small class="text-muted">Check-out:</small><br>
                                 <strong><?= date('d/m/Y', strtotime($_GET['checkout'])) ?></strong>
                              </div>
                              <?php endif; ?>
                              <?php if (isset($_GET['guests']) && !empty($_GET['guests'])): ?>
                              <div class="col-md-3">
                                    <small class="text-muted">Number of guests:</small><br>
                                 <strong><?= $_GET['guests'] ?> guests</strong>
                              </div>
                              <?php endif; ?>
                              <?php if (isset($_GET['room_type']) && !empty($_GET['room_type'])): ?>
                              <div class="col-md-3">
                                 <small class="text-muted">Room type:</small><br>
                                 <strong><?= $_GET['room_type'] ?></strong>
                              </div>
                              <?php endif; ?>
                           </div>
                           <div class="mt-2">
                              <span class="badge badge-info"><?= count($rooms) ?> rooms found</span>
                              <a href="room.php" class="btn btn-sm btn-outline-secondary ml-2">
                                 <i class="fa fa-times mr-1"></i>Clear filters
                              </a>
                           </div>
                        </div>
                     </div>
                  </div>
                  <?php endif; ?>
                  
                  <div class="row">
                     <?php if (empty($rooms)): ?>
                     <div class="col-12 text-center py-5">
                        <div class="no-results">
                           <i class="fa fa-search fa-3x text-muted mb-3"></i>
                           <h4 class="text-muted">No matching rooms found</h4>
                           <p class="text-muted">Please try again with different criteria</p>
                           <a href="room.php" class="btn btn-primary">
                              <i class="fa fa-refresh mr-2"></i>View all rooms
                           </a>
                        </div>
                     </div>
                     <?php else: ?>
                     <?php foreach ($rooms as $room): ?>
                     <div class="col-lg-4 col-md-6 mb-4">
                        <div class="room-card h-100 shadow-sm rounded overflow-hidden">
                           <!-- Room Image -->
                           <div class="room-image position-relative">
                              <img src="uploads/<?= htmlspecialchars($room['FirstImage'] ?? 'default.jpg') ?>" 
                                   alt="<?= htmlspecialchars($room['RoomName']) ?>" 
                                   class="w-100" style="height: 250px; object-fit: cover;">
                              
                              <!-- Status Badge -->
                              <div class="position-absolute top-0 right-0 m-3">
                                 <?php 
                                 $statusClass = '';
                                 $statusIcon = '';
                                 switch($room['DisplayStatus']) {
                                     case 'Available':
                                         $statusClass = 'badge-success';
                                         $statusIcon = 'fa-check';
                                         break;
                                     case 'Reserved':
                                         $statusClass = 'badge-warning';
                                         $statusIcon = 'fa-clock';
                                         break;
                                     case 'Occupied':
                                         $statusClass = 'badge-danger';
                                         $statusIcon = 'fa-user';
                                         break;
                                     case 'Maintenance':
                                         $statusClass = 'badge-secondary';
                                         $statusIcon = 'fa-tools';
                                         break;
                                     default:
                                         $statusClass = 'badge-info';
                                         $statusIcon = 'fa-info';
                                 }
                                 ?>
                                 <span class="badge <?= $statusClass ?>">
                                    <i class="fa <?= $statusIcon ?> mr-1"></i><?= $room['DisplayStatus'] ?>
                                 </span>
                              </div>
                              
                              <!-- Price Tag -->
                              <div class="position-absolute bottom-0 left-0 m-3">
                                 <div class="price-tag bg-primary text-white px-3 py-2 rounded">
                                    <strong><?= number_format($room['PricePerNight']) ?> VNĐ</strong>
                                    <small class="d-block">/night</small>
                                 </div>
                              </div>
                           </div>
                           
                           <!-- Room Info -->
                           <div class="room-info p-4">
                              <h5 class="room-title mb-2">
                                 <a href="room_detail.php?room_id=<?= $room['RoomID'] ?>" 
                                    class="text-dark text-decoration-none">
                                    <?= htmlspecialchars($room['RoomName']) ?>
                                 </a>
                              </h5>
                              
                              <!-- Rating Display -->
                              <div class="room-rating mb-2">
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
                              
                              <div class="room-details mb-3">
                                 <div class="row">
                                    <div class="col-6">
                                       <small class="text-muted d-block">Room type</small>
                                       <strong><?= htmlspecialchars($room['TypeName']) ?></strong>
                                    </div>
                                    <div class="col-6">
                                       <small class="text-muted d-block">Room number</small>
                                       <strong><?= htmlspecialchars($room['RoomNumber']) ?></strong>
                                    </div>
                                 </div>
                                 
                                 <?php if (isset($room['MaxGuests'])): ?>
                                 <div class="mt-2">
                                    <small class="text-muted d-block">Maximum capacity</small>
                                    <strong><i class="fa fa-users mr-1"></i><?= $room['MaxGuests'] ?> guests</strong>
                                 </div>
                                 <?php endif; ?>
                              </div>
                              
                              <!-- Action Buttons -->
                              <div class="room-actions">
                                 <div class="row">
                                    <div class="col-6">
                                       <a href="room_detail.php?room_id=<?= $room['RoomID'] ?>" 
                                          class="btn btn-outline-primary btn-sm w-100">
                                          <i class="fa fa-eye mr-1"></i>Details
                                       </a>
                                    </div>
                                    <div class="col-6">
                                       <?php if ($room['DisplayStatus'] === 'Available'): ?>
                                          <a href="booking.php?room_id=<?= $room['RoomID'] ?>" 
                                             class="btn btn-success btn-sm w-100">
                                             <i class="fa fa-calendar-check mr-1"></i>Book room
                                          </a>
                                       <?php else: ?>
                                          <button class="btn btn-secondary btn-sm w-100" disabled>
                                             <i class="fa fa-times mr-1"></i><?= $room['DisplayStatus'] ?>
                                          </button>
                                       <?php endif; ?>
                                    </div>
                                 </div>
                              </div>
                           </div>
                        </div>
                     </div>
                     <?php endforeach; ?>
                     <?php endif; ?>
                  </div>
               </div>
            </div>
            <!-- end Room List Section -->
         </div>
      </div>
      <!-- end our_room -->
     
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
                        <li><a href="#">Home</a></li>
                        <li><a href="about.php"> About</a></li>
                        <li class="active"><a href="room.php">Rooms</a></li>
                        <li><a href="gallery.php">Gallery</a></li>
                        <li><a href="blog.php">Blog</a></li>
                        <li><a href="contact.php">Contact Us</a></li>
                     </ul>
                  </div>
                  <div class="col-md-4">
                     <h3>News letter</h3>
                     <form class="bottom_form">
                        <input class="enter" placeholder="Enter your email" type="text" name="Enter your email">
                        <button class="sub_btn">Subscribe</button>
                     </form>
                     <ul class="social_icon">
                        <li><a href="#"><i class="fab fa-facebook-f" aria-hidden="true"></i></a></li>
                        <li><a href="#"><i class="fab fa-twitter" aria-hidden="true"></i></a></li>
                        <li><a href="#"><i class="fab fa-linkedin-in" aria-hidden="true"></i></a></li>
                        <li><a href="#"><i class="fab fa-youtube" aria-hidden="true"></i></a></li>
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