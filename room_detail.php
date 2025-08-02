<?php
require 'config.php';

$room_id = isset($_GET['room_id']) ? (int)$_GET['room_id'] : 0;
if ($room_id <= 0) {
    echo '<div style="padding:40px;text-align:center;">Room not found!</div>';
    exit;
}

// Get room info
$stmt = $pdo->prepare("SELECT Room.*, RoomType.TypeName
   FROM Room
   JOIN RoomType ON Room.RoomTypeID = RoomType.RoomTypeID
   WHERE Room.RoomID = ?");
$stmt->execute([$room_id]);
$room = $stmt->fetch();
if (!$room) {
    echo '<div style="padding:40px;text-align:center;">Room not found!</div>';
    exit;
}

// Check room status with priority to admin-set status
$today = date('Y-m-d');
$stmtBooked = $pdo->prepare("SELECT COUNT(*) FROM Reservation WHERE RoomID = ? AND CheckInDate <= ? AND CheckOutDate > ?");
$stmtBooked->execute([$room_id, $today, $today]);
$isBookedToday = $stmtBooked->fetchColumn() > 0;

// Determine display status with priority to admin-set status
$displayStatus = 'Available';
if ($room['Status'] === 'Maintenance') {
    $displayStatus = 'Maintenance';
} elseif ($room['Status'] === 'Occupied') {
    $displayStatus = 'Occupied';
} elseif ($room['Status'] === 'Reserved') {
    $displayStatus = 'Reserved';
}

// Get room images
$stmtImg = $pdo->prepare("SELECT * FROM RoomImage WHERE RoomID = ?");
$stmtImg->execute([$room_id]);
$images = $stmtImg->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
   <head>
      <meta charset="utf-8">
      <meta http-equiv="X-UA-Compatible" content="IE=edge">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <title>Room Detail - <?= htmlspecialchars($room['RoomName']) ?></title>
      <link rel="stylesheet" href="css/bootstrap.min.css">
      <link rel="stylesheet" href="css/style.css">
      <link rel="stylesheet" href="css/header.css">
      <link rel="stylesheet" href="css/responsive.css">
      <link rel="icon" href="images/fevicon.png" type="image/gif" />
      <link rel="stylesheet" href="css/jquery.mCustomScrollbar.min.css">
      <link rel="stylesheet" href="https://netdna.bootstrapcdn.com/font-awesome/4.0.3/css/font-awesome.css">
      <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/fancybox/2.1.5/jquery.fancybox.min.css" media="screen">
      <link rel="preconnect" href="https://fonts.googleapis.com">
      <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
      <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
      <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
      <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&display=swap" rel="stylesheet">
      <link rel="stylesheet" href="https://unpkg.com/photoswipe@5/dist/photoswipe.css">
      
      <style>
         body {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            font-family: 'Inter', sans-serif;
            padding-top: 80px;
         }

         .hero-section {
            background: linear-gradient(135deg,rgb(215, 76, 76) 0%, #764ba2 100%);
            color: white;
            padding: 60px 0;
            margin-bottom: 40px;
         }

         .hero-content {
            text-align: center;
         }

         .hero-title {
            font-family: 'Playfair Display', serif;
            font-size: 3.5rem;
            font-weight: 600;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
         }

         .hero-subtitle {
            font-size: 1.2rem;
            opacity: 0.9;
            margin-bottom: 20px;
         }

         .room-detail-container {
            max-width: 1200px;
            margin: 0 auto 40px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            overflow: hidden;
         }

         .room-header {
            background: linear-gradient(135deg,rgb(215, 76, 76) 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            position: relative;
         }

         .room-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            opacity: 0.3;
         }

         .room-header-content {
            position: relative;
            z-index: 1;
         }

         .room-title {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            font-weight: 600;
            margin-bottom: 10px;
         }

         .room-number {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 20px;
         }

         .room-price {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 20px;
            color: #fff !important;
            text-shadow: 1px 2px 8px rgba(60,40,120,0.25), 0 1px 0 #333;
            background: none !important;
         }

         .room-status {
            display: inline-block;
            padding: 10px 32px;
            border-radius: 30px;
            font-weight: 700;
            font-size: 1.1rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s ease;
            border: none;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
         }

         .status-available {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            color: #fff !important;
            box-shadow: 0 4px 15px rgba(67,233,123,0.3);
         }

         .status-available i {
            color: #fff !important;
         }

         .status-available:hover {
            background: linear-gradient(135deg, #38f9d7 0%, #43e97b 100%);
            color: #fff !important;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(67,233,123,0.4);
         }

         .status-booked {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
            color: #fff !important;
            box-shadow: 0 4px 15px rgba(255,107,107,0.3);
         }

         .status-booked i {
            color: #fff !important;
         }

         .status-booked:hover {
            background: linear-gradient(135deg, #ee5a24 0%, #ff6b6b 100%);
            color: #fff !important;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255,107,107,0.4);
         }

         .room-content {
            padding: 40px;
         }

         .room-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
         }

         .info-card {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 15px;
            border-left: 4px solidrgb(215, 76, 76);
            transition: all 0.3s ease;
         }

         .info-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
         }

         .info-icon {
            font-size: 2rem;
            color:rgb(215, 76, 76);
            margin-bottom: 15px;
         }

         .info-title {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            font-size: 1.1rem;
         }

         .info-value {
            color: #666;
            font-size: 1rem;
         }

         .room-description {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 40px;
            border-left: 4px solid #28a745;
         }

         .description-title {
            font-weight: 600;
            color: #333;
            margin-bottom: 15px;
            font-size: 1.3rem;
         }

         .description-text {
            color: #666;
            line-height: 1.6;
            font-size: 1rem;
         }

         .gallery-section {
            margin-bottom: 40px;
         }

         .gallery-title {
            font-weight: 600;
            color: #333;
            margin-bottom: 25px;
            font-size: 1.5rem;
            text-align: center;
         }

         .gallery {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
         }

         .gallery-item {
            position: relative;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
         }

         .gallery-item:hover {
            transform: scale(1.02);
            box-shadow: 0 15px 30px rgba(0,0,0,0.2);
         }

         .gallery-item img {
            width: 100%;
            height: 250px;
            object-fit: cover;
            cursor: pointer;
         }

         .gallery-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
         }

         .gallery-item:hover .gallery-overlay {
            opacity: 1;
         }

         .gallery-overlay i {
            color: white;
            font-size: 2rem;
         }

         .room-actions {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 15px;
            text-align: center;
         }

         .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
         }

         .btn-custom {
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
         }

         .btn-primary-custom {
            background: linear-gradient(135deg,rgb(215, 76, 76), #764ba2);
            color: white;
         }

         .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
            color: white;
            text-decoration: none;
         }

         .btn-secondary-custom {
            background: linear-gradient(135deg, #6c757d, #495057);
            color: white;
         }

         .btn-secondary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(108, 117, 125, 0.3);
            color: white;
            text-decoration: none;
         }

         .btn-disabled {
            background: #6c757d;
            color: #adb5bd;
            cursor: not-allowed;
         }

         .btn-disabled:hover {
            transform: none;
            box-shadow: none;
         }

         /* Feedback Section Styles */
         .feedback-section {
            padding: 30px;
            border-top: 1px solid #e9ecef;
         }

         .feedback-title {
            color: #333;
            font-size: 1.5rem;
            margin-bottom: 25px;
            font-weight: 600;
         }

         .feedback-stats {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            border: 1px solid #dee2e6;
         }

         .stats-overview {
            display: flex;
            align-items: center;
            gap: 30px;
            margin-bottom: 20px;
         }

         .average-rating {
            text-align: center;
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            min-width: 150px;
         }

         .rating-number {
            font-size: 3.5rem;
            font-weight: 700;
            color: #333;
            line-height: 1;
         }
         
         .rating-number::after {
            content: '/5';
            font-size: 1.5rem;
            color: #999;
            font-weight: 400;
         }

         .rating-stars {
            color: #ffd700;
            font-size: 1.5rem;
            margin: 8px 0;
         }

         .total-reviews {
            color: #6c757d;
            font-size: 0.9rem;
            font-weight: 500;
         }
         
         .stats-overview {
            display: flex;
            align-items: center;
            gap: 30px;
            margin-bottom: 20px;
            padding: 0;
            background: transparent;
            border-radius: 10px;
         }

         .rating-breakdown {
            width: 100%;
            max-width: 500px;
            min-width: 320px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
         }

                  .rating-bar {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 15px;
            border-radius: 8px;
            transition: all 0.3s ease;
            margin-bottom: 8px;
            background: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            min-width: 320px;
         }

         .rating-bar:hover {
            background: rgba(255, 107, 107, 0.05);
            transform: translateX(3px);
            box-shadow: 0 3px 10px rgba(255, 107, 107, 0.15);
         }

         .rating-label {
            min-width: 50px;
            font-size: 1rem;
            color: #333;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
         }
         
         .rating-label::after {
            content: '★';
            color: #ffd700;
            font-size: 1.2rem;
         }

         .rating-progress {
            flex: 1 1 200px;
            min-width: 120px;
            max-width: 400px;
            height: 16px;
            background: #f0f0f0;
            border-radius: 8px;
            overflow: hidden;
            margin: 0 15px;
            position: relative;
         }

         .rating-fill {
            height: 100%;
            background: #ffd700;
            border-radius: 8px;
            transition: width 0.8s ease;
            position: relative;
            box-shadow: 0 1px 3px rgba(255, 107, 107, 0.3);
         }
         
         .rating-fill::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(90deg, 
                transparent 0%, 
                rgba(255,255,255,0.2) 50%, 
                transparent 100%);
            animation: shimmer 2s infinite;
         }
         
         @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
         }

         .rating-count {
            min-width: 80px;
            font-size: 0.9rem;
            color: #333;
            text-align: right;
            font-weight: 600;
         }
         
         .rating-count.zero {
            color: #999;
            font-style: normal;
         }

         .feedback-list {
            margin-bottom: 30px;
         }

         .feedback-item {
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
         }

         .feedback-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
         }

         .feedback-author {
            font-weight: 600;
            color: #333;
         }

         .feedback-date {
            color: #6c757d;
            font-size: 0.9rem;
         }

         .feedback-rating {
            color: #ffd700;
            margin-bottom: 10px;
         }

         .feedback-comment {
            color: #495057;
            line-height: 1.6;
            margin-bottom: 10px;
         }

         .feedback-reply {
            background: #f8f9fa;
            border-left: 3px solid #007bff;
            padding: 10px 15px;
            margin-top: 10px;
            border-radius: 0 8px 8px 0;
         }

         .reply-label {
            font-weight: 600;
            color: #007bff;
            font-size: 0.9rem;
            margin-bottom: 5px;
         }

         .feedback-form {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 15px;
            padding: 25px;
            border: 1px solid #dee2e6;
         }

         .feedback-form h4 {
            color: #333;
            margin-bottom: 20px;
            font-weight: 600;
         }

         .rating-input {
            margin-bottom: 20px;
         }

         .rating-input label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: #495057;
         }

         .star-rating {
            display: flex;
            flex-direction: row-reverse;
            gap: 5px;
         }

         .star-rating input {
            display: none;
         }

         .star-rating label {
            cursor: pointer;
            font-size: 1.8rem;
            color: #ddd;
            transition: color 0.2s ease;
         }

         .star-rating label:hover,
         .star-rating label:hover ~ label,
         .star-rating input:checked ~ label {
            color: #ffd700;
         }

         .comment-input {
            margin-bottom: 20px;
         }

         .comment-input label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: #495057;
         }

         .comment-input textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-family: inherit;
            font-size: 0.95rem;
            resize: vertical;
            transition: border-color 0.3s ease;
         }

         .comment-input textarea:focus {
            outline: none;
            border-color: #007bff;
         }

         .char-count {
            text-align: right;
            font-size: 0.8rem;
            color: #6c757d;
            margin-top: 5px;
         }

         .btn-submit-feedback {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 25px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.95rem;
         }

         .btn-submit-feedback:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 123, 255, 0.3);
         }

         .loading-spinner {
            text-align: center;
            color: #6c757d;
            padding: 20px;
         }

         .no-feedback {
            text-align: center;
            color: #6c757d;
            padding: 30px;
            font-style: italic;
         }

         .alert {
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            border: 1px solid transparent;
         }

         .alert-success {
            background: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
         }

         .alert-danger {
            background: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
         }

         @media (max-width: 768px) {
            .hero-title {
               font-size: 2.5rem;
            }

            .room-title {
               font-size: 2rem;
            }

            .room-header {
               padding: 30px 20px;
            }

            .room-content {
               padding: 30px 20px;
            }

            .room-info-grid {
               grid-template-columns: 1fr;
               gap: 20px;
            }

            .gallery {
               grid-template-columns: 1fr;
            }

            .action-buttons {
               flex-direction: column;
               align-items: center;
            }

            .btn-custom {
               width: 100%;
               max-width: 300px;
               justify-content: center;
            }
            
            .stats-overview {
               flex-direction: column;
               gap: 20px;
               text-align: center;
            }
            
            .rating-breakdown {
               width: 100%;
            }
            
            .rating-progress {
               height: 12px;
               margin: 0 10px;
            }
            
            .rating-bar {
               gap: 8px;
               padding: 8px 10px;
            }
            
            .rating-label {
               min-width: 40px;
               font-size: 0.9rem;
            }
            
            .rating-count {
               min-width: 70px;
               font-size: 0.8rem;
            }
         }

         @media (max-width: 576px) {
            .hero-title {
               font-size: 2rem;
            }

            .room-title {
               font-size: 1.8rem;
            }

            .room-price {
               font-size: 1.5rem;
            }
         }
      </style>
   </head>
   <body class="main-layout">
      <!-- Hero Section -->
      <section class="hero-section">
         <div class="container">
            <div class="hero-content">
               <h1 class="hero-title"><?= htmlspecialchars($room['RoomName']) ?></h1>
               <p class="hero-subtitle">Great vacation experience.</p>
            </div>
         </div>
      </section>

      <!-- Room Detail Container -->
      <div class="room-detail-container">
         <!-- Room Header -->
         <div class="room-header">
            <div class="room-header-content">
               <h1 class="room-title"><?= htmlspecialchars($room['RoomName']) ?></h1>
               <p class="room-number">Room Number: <?= htmlspecialchars($room['RoomNumber']) ?></p>
               <!-- <div class="room-price"><?= number_format($room['PricePerNight']) ?> VNĐ/đêm</div> -->
               <?php 
               $statusClass = '';
               $statusIcon = '';
               switch($displayStatus) {
                   case 'Available':
                       $statusClass = 'status-available';
                       $statusIcon = 'fa-check';
                       break;
                   case 'Reserved':
                       $statusClass = 'status-booked';
                       $statusIcon = 'fa-clock';
                       break;
                   case 'Occupied':
                       $statusClass = 'status-booked';
                       $statusIcon = 'fa-user';
                       break;
                   case 'Maintenance':
                       $statusClass = 'status-booked';
                       $statusIcon = 'fa-tools';
                       break;
                   default:
                       $statusClass = 'status-available';
                       $statusIcon = 'fa-info';
               }
               ?>
               <div class="room-status <?= $statusClass ?>">
                  <i class="fa <?= $statusIcon ?> mr-2"></i>
                  <?= $displayStatus ?>
               </div>
            </div>
         </div>

         <!-- Room Content -->
         <div class="room-content">
            <!-- Room Information Grid -->
            <div class="room-info-grid">
               <div class="info-card">
                  <div class="info-icon">
                     <i class="fa fa-bed"></i>
                  </div>
                  <div class="info-title">Room Type</div>
                  <div class="info-value"><?= htmlspecialchars($room['TypeName']) ?></div>
               </div>

               <div class="info-card">
                  <div class="info-icon">
                     <i class="fa fa-users"></i>
                  </div>
                  <div class="info-title">Capacity</div>
                  <div class="info-value"><?= isset($room['MaxGuests']) ? $room['MaxGuests'] . ' guests' : 'Not updated' ?></div>
               </div>

               <div class="info-card">
                  <div class="info-icon">
                     <i class="fa fa-money"></i>
                  </div>
                  <div class="info-title">Room Price</div>
                  <div class="info-value"><?= number_format($room['PricePerNight']) ?> VNĐ/night</div>
               </div>

               <div class="info-card">
                  <div class="info-icon">
                     <i class="fa fa-info-circle"></i>
                  </div>
                  <div class="info-title">Status</div>
                  <div class="info-value"><?= $displayStatus ?></div>
               </div>
            </div>

            <!-- Room Description -->
            <div class="room-description">
               <div class="description-title">
                  <i class="fa fa-info-circle mr-2"></i>Room Description
               </div>
               <div class="description-text">
                  <?= htmlspecialchars($room['Description']) ?>
               </div>
            </div>

            <!-- Gallery Section -->
            <?php if ($images): ?>
            <div class="gallery-section">
               <h3 class="gallery-title">
                  <i class="fa fa-images mr-2"></i>Room Images
               </h3>
               <div class="gallery">
                  <?php foreach ($images as $index => $img): ?>
                  <div class="gallery-item">
                     <a href="uploads/<?= htmlspecialchars($img['ImagePath']) ?>"
                        data-pswp-width="1600"
                        data-pswp-height="1000"
                        data-index="<?= $index ?>">
                        <img src="uploads/<?= htmlspecialchars($img['ImagePath']) ?>" 
                             alt="<?= htmlspecialchars($img['Caption'] ?? $room['RoomName']) ?>">
                        <div class="gallery-overlay">
                           <i class="fa fa-search-plus"></i>
                        </div>
                     </a>
                  </div>
                  <?php endforeach; ?>
               </div>
            </div>
            <?php endif; ?>

            <!-- Feedback Section -->
            <div class="feedback-section">
               <h3 class="feedback-title">
                  <i class="fa fa-star mr-2"></i>Feedback
               </h3>
               
               <!-- Feedback Stats -->
               <div class="feedback-stats" id="feedbackStats">
                  <div class="loading-spinner">
                     <i class="fa fa-spinner fa-spin"></i> Loading feedback...
                  </div>
               </div>
               
               <!-- Feedback List -->
               <div class="feedback-list" id="feedbackList">
                  <!-- Feedback items will be loaded here -->
               </div>
               
               <!-- Feedback Form -->
               <div class="feedback-form" id="feedbackForm">
                  <h4><i class="fa fa-edit mr-2"></i>Write feedback</h4>
                  <form id="submitFeedbackForm">
                     <input type="hidden" name="room_id" value="<?= $room['RoomID'] ?>">
                     
                     <div class="rating-input">
                        <label>Your rating:</label>
                        <div class="star-rating">
                           <input type="radio" name="rating" value="5" id="star5">
                           <label for="star5"><i class="fa fa-star"></i></label>
                           <input type="radio" name="rating" value="4" id="star4">
                           <label for="star4"><i class="fa fa-star"></i></label>
                           <input type="radio" name="rating" value="3" id="star3">
                           <label for="star3"><i class="fa fa-star"></i></label>
                           <input type="radio" name="rating" value="2" id="star2">
                           <label for="star2"><i class="fa fa-star"></i></label>
                           <input type="radio" name="rating" value="1" id="star1">
                           <label for="star1"><i class="fa fa-star"></i></label>
                        </div>
                     </div>
                     
                     <div class="comment-input">
                        <label for="comment">Comment:</label>
                        <textarea name="comment" id="comment" rows="4" placeholder="Share your experience about this room..." maxlength="500"></textarea>
                        <div class="char-count">
                           <span id="charCount">0</span>/500
                        </div>
                     </div>
                     
                     <button type="submit" class="btn-submit-feedback">
                        <i class="fa fa-paper-plane"></i> Submit feedback
                     </button>
                  </form>
               </div>
            </div>

            <!-- Room Actions -->
            <div class="room-actions">
               <div class="action-buttons">
                  <?php if ($displayStatus === 'Available'): ?>
                     <a href="booking.php?room_id=<?= $room['RoomID'] ?>" class="btn-custom btn-primary-custom">
                        <i class="fa fa-calendar-check"></i>
                        Book Room
                     </a>
                  <?php else: ?>
                     <button class="btn-custom btn-disabled" disabled>
                        <i class="fa fa-times"></i>
                        <?= $displayStatus ?>
                     </button>
                  <?php endif; ?>
                  
                  <a href="room.php" class="btn-custom btn-secondary-custom">
                     <i class="fa fa-arrow-left"></i>
                     Back to List
                  </a>
               </div>
            </div>
         </div>
      </div>

      <script src="js/jquery.min.js"></script>
      <script src="js/bootstrap.bundle.min.js"></script>
      <script src="js/jquery-3.0.0.min.js"></script>
      <script src="js/jquery.mCustomScrollbar.concat.min.js"></script>
      <script src="js/custom.js"></script>
      <script type="module">
         import PhotoSwipeLightbox from 'https://unpkg.com/photoswipe@5/dist/photoswipe-lightbox.esm.js';
         const lightbox = new PhotoSwipeLightbox({
             gallery: '.gallery',
             children: 'a',
             pswpModule: () => import('https://unpkg.com/photoswipe@5/dist/photoswipe.esm.js')
         });
         lightbox.init();
      </script>
      
      <!-- Feedback JavaScript -->
      <script>
      $(document).ready(function() {
          const roomId = <?= $room['RoomID'] ?>;
          
          // Load feedback data
          loadFeedback();
          
          // Character count for comment
          $('#comment').on('input', function() {
              const count = $(this).val().length;
              $('#charCount').text(count);
          });
          
          // Submit feedback form
          $('#submitFeedbackForm').on('submit', function(e) {
              e.preventDefault();
              
              const rating = $('input[name="rating"]:checked').val();
              const comment = $('#comment').val().trim();
              
              if (!rating) {
                  showAlert('Please select a rating', 'danger');
                  return;
              }
              
              if (!comment) {
                  showAlert('Please enter a comment', 'danger');
                  return;
              }
              
              // Disable submit button
              const submitBtn = $('.btn-submit-feedback');
              const originalText = submitBtn.html();
              submitBtn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Sending...');
              
              $.ajax({
                  url: 'submit_feedback.php',
                  type: 'POST',
                  data: {
                      room_id: roomId,
                      rating: rating,
                      comment: comment
                  },
                  dataType: 'json',
                  success: function(response) {
                      if (response.success) {
                          showAlert(response.message, 'success');
                          $('#submitFeedbackForm')[0].reset();
                          $('#charCount').text('0');
                          loadFeedback(); // Reload feedback
                      } else {
                          showAlert(response.message, 'danger');
                      }
                  },
                  error: function() {
                      showAlert('An error occurred, please try again', 'danger');
                  },
                  complete: function() {
                      submitBtn.prop('disabled', false).html(originalText);
                  }
              });
          });
          
          function loadFeedback() {
              $.ajax({
                  url: 'get_feedback.php',
                  type: 'GET',
                  data: { room_id: roomId },
                  dataType: 'json',
                  success: function(response) {
                      if (response.success) {
                          displayFeedbackStats(response.stats);
                          displayFeedbackList(response.feedbacks);
                      } else {
                          $('#feedbackStats').html('<div class="no-feedback">No feedback yet</div>');
                          $('#feedbackList').html('<div class="no-feedback">No feedback yet for this room</div>');
                      }
                  },
                  error: function() {
                        $('#feedbackStats').html('<div class="alert alert-danger">Error loading feedback</div>');
                      $('#feedbackList').html('');
                  }
              });
          }
          
          function displayFeedbackStats(stats) {
              console.log('Stats received:', stats); // Debug log
              
              if (stats.total_reviews === 0) {
                  $('#feedbackStats').html('<div class="no-feedback">No feedback yet</div>');
                  return;
              }
              
              const stars = '★'.repeat(Math.floor(stats.average_rating)) + '☆'.repeat(5 - Math.floor(stats.average_rating));
              
              let statsHtml = `
                  <div class="stats-overview">
                      <div class="average-rating">
                          <div class="rating-number">${stats.average_rating.toFixed(1)}</div>
                          <div class="rating-stars">${stars}</div>
                          <div class="total-reviews">${stats.total_reviews} reviews</div>
                      </div>
                      <div class="rating-breakdown">
              `;
              
              // Rating breakdown
              const starKeys = {
                  5: 'five_star',
                  4: 'four_star', 
                  3: 'three_star',
                  2: 'two_star',
                  1: 'one_star'
              };
              
              for (let i = 5; i >= 1; i--) {
                  const count = stats[starKeys[i]] || 0;
                  const percentage = stats.total_reviews > 0 ? (count / stats.total_reviews * 100) : 0;
                  
                  console.log(`${i} stars: count=${count}, percentage=${percentage}%`); // Debug log
                  
                  statsHtml += `
                      <div class="rating-bar">
                          <div class="rating-label">${i}</div>
                          <div class="rating-progress">
                              <div class="rating-fill" data-width="${percentage}%" style="width: 0%"></div>
                          </div>
                           <div class="rating-count ${count === 0 ? 'zero' : ''}">${count} reviews</div>
                      </div>
                  `;
              }
              
              statsHtml += `
                      </div>
                  </div>
              `;
              
              $('#feedbackStats').html(statsHtml);
              
              // Animate progress bars
              setTimeout(() => {
                  $('.rating-fill').each(function() {
                      const targetWidth = $(this).data('width');
                      console.log('Animating to width:', targetWidth); // Debug log
                      $(this).animate({width: targetWidth}, 800);
                  });
              }, 300);
              
              // Fallback: Set width immediately if animation fails
              setTimeout(() => {
                  $('.rating-fill').each(function() {
                      const targetWidth = $(this).data('width');
                      if ($(this).width() === 0) {
                          $(this).css('width', targetWidth);
                      }
                  });
              }, 1200);
          }
          
          function displayFeedbackList(feedbacks) {
              if (feedbacks.length === 0) {
                  $('#feedbackList').html('<div class="no-feedback">No feedback yet for this room</div>');
                  return;
              }
              
              let listHtml = '';
              feedbacks.forEach(function(feedback) {
                  const stars = '★'.repeat(feedback.Rating) + '☆'.repeat(5 - feedback.Rating);
                  
                  listHtml += `
                      <div class="feedback-item">
                          <div class="feedback-header">
                              <div class="feedback-author">${feedback.FullName}</div>
                              <div class="feedback-date">${feedback.FeedbackDate}</div>
                          </div>
                          <div class="feedback-rating">${stars}</div>
                          <div class="feedback-comment">${feedback.Comment}</div>
                          ${feedback.Reply ? `
                              <div class="feedback-reply">
                                  <div class="reply-label">Hotel response:</div>
                                  <div>${feedback.Reply}</div>
                              </div>
                          ` : ''}
                      </div>
                  `;
              });
              
              $('#feedbackList').html(listHtml);
          }
          
          function showAlert(message, type) {
              const alertHtml = `<div class="alert alert-${type}">${message}</div>`;
              $('.feedback-form').prepend(alertHtml);
              
              // Auto remove after 5 seconds
              setTimeout(function() {
                  $('.alert').fadeOut();
              }, 5000);
          }
      });
      </script>
   </body>
</html>
