<?php
session_start();
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'admin') {
    header('Location: login.php');
    exit;
}
require_once 'config.php';

// Thêm mới hoặc cập nhật
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $room_id = isset($_POST['room_id']) ? $_POST['room_id'] : null;
    $room_name = trim($_POST['room_name']);
    $room_number = trim($_POST['room_number']);
    $room_type = $_POST['room_type'];
    $price = $_POST['price'];
    $status = $_POST['status'];
    $max_guests = $_POST['max_guests'];
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $replace_images = isset($_POST['replace_images']) ? $_POST['replace_images'] : 'add'; // 'add' hoặc 'replace'

    // Validation
    $errors = [];
    
    // Kiểm tra tên phòng
    if (empty($room_name)) {
        $errors[] = "Room name is required.";
    } elseif (strlen($room_name) > 100) {
        $errors[] = "Room name cannot exceed 100 characters.";
    }
    
    // Kiểm tra số phòng
    if (empty($room_number)) {
        $errors[] = "Room number is required.";
    } else {
        // Kiểm tra trùng số phòng
        if ($room_id) {
            // Update - kiểm tra trùng với phòng khác (trừ phòng hiện tại)
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM Room WHERE RoomNumber = ? AND RoomID != ?");
            $stmt->execute([$room_number, $room_id]);
        } else {
            // Insert - kiểm tra trùng với tất cả phòng
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM Room WHERE RoomNumber = ?");
            $stmt->execute([$room_number]);
        }
        
        if ($stmt->fetchColumn() > 0) {
            $errors[] = "Room number '$room_number' already exists.";
        }
    }
    
    // Kiểm tra giá
    if ($price <= 0) {
        $errors[] = "Price must be greater than 0.";
    }
    
    // Kiểm tra số khách tối đa
    if ($max_guests < 1 || $max_guests > 10) {
        $errors[] = "Max guests must be between 1 and 10.";
    }

    // Nếu có lỗi, redirect với thông báo lỗi
    if (!empty($errors)) {
        $error_message = implode(" ", $errors);
        header("Location: " . $_SERVER['PHP_SELF'] . "?error=" . urlencode($error_message));
        exit;
    }

    // Xử lý upload ảnh
    $uploadDir = 'uploads/';
    if (!is_dir($uploadDir)) mkdir($uploadDir);
    $imageNames = [];
    if (!empty($_FILES['room_image']['name'][0])) {
        foreach ($_FILES['room_image']['name'] as $index => $name) {
            $tmpName = $_FILES['room_image']['tmp_name'][$index];
            if ($tmpName) {
                // Kiểm tra loại file
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                $fileType = mime_content_type($tmpName);
                if (!in_array($fileType, $allowedTypes)) {
                    header("Location: " . $_SERVER['PHP_SELF'] . "?error=" . urlencode("Invalid file type. Only JPG, PNG, GIF, and WebP are allowed."));
                    exit;
                }
                
                // Kiểm tra kích thước file (max 5MB)
                if ($_FILES['room_image']['size'][$index] > 5 * 1024 * 1024) {
                    header("Location: " . $_SERVER['PHP_SELF'] . "?error=" . urlencode("File size too large. Maximum size is 5MB."));
                    exit;
                }
                
                $imageName = uniqid() . '_' . basename($name);
                move_uploaded_file($tmpName, $uploadDir . $imageName);
                $imageNames[] = $imageName;
            }
        }
    }

    try {
        if ($room_id) {
            // Update room info
            $stmt = $pdo->prepare("UPDATE Room SET RoomName=?, RoomNumber=?, RoomTypeID=?, PricePerNight=?, Status=?, MaxGuests=?, Description=? WHERE RoomID=?");
            $stmt->execute([$room_name, $room_number, $room_type, $price, $status, $max_guests, $description, $room_id]);

            // Xử lý ảnh khi update
            if (!empty($imageNames)) {
                if ($replace_images === 'replace') {
                    // Thay thế tất cả ảnh cũ bằng ảnh mới
                    // 1. Lấy danh sách ảnh cũ
                    $imgs = $pdo->prepare("SELECT ImagePath FROM RoomImage WHERE RoomID=?");
                    $imgs->execute([$room_id]);
                    foreach ($imgs as $img) {
                        $imgPath = $uploadDir . $img['ImagePath'];
                        if (file_exists($imgPath)) unlink($imgPath);
                    }
                    // 2. Xóa bản ghi ảnh cũ trong DB
                    $pdo->prepare("DELETE FROM RoomImage WHERE RoomID=?")->execute([$room_id]);
                } else {
                    // Kiểm tra giới hạn số ảnh (ví dụ: tối đa 10 ảnh)
                    $currentImagesCount = $pdo->prepare("SELECT COUNT(*) FROM RoomImage WHERE RoomID=?");
                    $currentImagesCount->execute([$room_id]);
                    $currentCount = $currentImagesCount->fetchColumn();
                    
                    if (($currentCount + count($imageNames)) > 10) {
                        header("Location: " . $_SERVER['PHP_SELF'] . "?error=" . urlencode("Maximum 10 images allowed per room. Current: $currentCount, Adding: " . count($imageNames)));
                        exit;
                    }
                }

                // 3. Thêm ảnh mới vào DB
                foreach ($imageNames as $img) {
                    $stmtImg = $pdo->prepare("INSERT INTO RoomImage (ImagePath, RoomID) VALUES (?, ?)");
                    $stmtImg->execute([$img, $room_id]);
                }
            }
            
            header("Location: " . $_SERVER['PHP_SELF'] . "?success=edit");
            exit;
        } else {
            // Insert new room
            $stmt = $pdo->prepare("INSERT INTO Room (RoomName, RoomNumber, RoomTypeID, PricePerNight, Status, MaxGuests, Description) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$room_name, $room_number, $room_type, $price, $status, $max_guests, $description]);
            $room_id = $pdo->lastInsertId();
            
            // Lưu ảnh
            foreach ($imageNames as $img) {
                $stmtImg = $pdo->prepare("INSERT INTO RoomImage (ImagePath, RoomID) VALUES (?, ?)");
                $stmtImg->execute([$img, $room_id]);
            }
            
            header("Location: " . $_SERVER['PHP_SELF'] . "?success=add");
            exit;
        }
    } catch (Exception $e) {
        header("Location: " . $_SERVER['PHP_SELF'] . "?error=" . urlencode("Database error: " . $e->getMessage()));
        exit;
    }
}

// Xóa phòng
if (isset($_GET['delete'])) {
    $room_id = $_GET['delete'];

    try {
        // Kiểm tra xem phòng có đang được đặt không
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM Reservation WHERE RoomID = ? AND CheckOutDate > CURDATE()");
        $stmt->execute([$room_id]);
        if ($stmt->fetchColumn() > 0) {
            header("Location: " . $_SERVER['PHP_SELF'] . "?error=" . urlencode("Cannot delete room. It has active reservations."));
            exit;
        }

        // Xóa ảnh vật lý
        $imgs = $pdo->prepare("SELECT ImagePath FROM RoomImage WHERE RoomID=?");
        $imgs->execute([$room_id]);
        foreach ($imgs as $img) {
            $imgPath = 'uploads/' . $img['ImagePath'];
            if (file_exists($imgPath)) unlink($imgPath);
        }

        // Xóa DB
        $pdo->prepare("DELETE FROM RoomImage WHERE RoomID=?")->execute([$room_id]);
        $pdo->prepare("DELETE FROM Room WHERE RoomID=?")->execute([$room_id]);

        header("Location: " . $_SERVER['PHP_SELF'] . "?success=delete");
        exit;
    } catch (Exception $e) {
        header("Location: " . $_SERVER['PHP_SELF'] . "?error=" . urlencode("Error deleting room: " . $e->getMessage()));
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Room Management</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="css/imgprev.css">
    <style>
        body {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            min-height: 100vh;
            font-family: 'Inter', Arial, sans-serif;
        }
        .container {
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 8px 32px rgba(102, 126, 234, 0.10), 0 1.5px 8px rgba(118, 75, 162, 0.08);
            padding: 36px 28px 28px 28px;
            margin-top: 40px;
            margin-bottom: 40px;
        }
        h3 {
            font-weight: 700;
            color: #4b3fa7;
            letter-spacing: 1px;
        }
        .btn-success, .btn-primary {
            border-radius: 25px;
            font-weight: 600;
            padding: 8px 22px;
            font-size: 1rem;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.10);
            transition: all 0.2s;
        }
        .btn-success:hover, .btn-primary:hover {
            transform: translateY(-2px) scale(1.04);
            box-shadow: 0 8px 25px rgba(40, 167, 69, 0.18);
        }
        .btn-warning, .btn-danger, .btn-secondary {
            border-radius: 25px;
            font-weight: 600;
            font-size: 0.95rem;
            padding: 7px 18px;
        }
        .alert {
            border-radius: 12px;
            border: none;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .alert-success {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: #fff;
        }
        .alert-danger {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: #fff;
        }
        .alert i {
            margin-right: 8px;
        }
        .table {
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.07);
        }
        .table thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
        }
        .table th, .table td {
            vertical-align: middle !important;
            text-align: center;
            white-space: nowrap;
        }
        .table td {
            font-size: 0.98rem;
            padding: 12px 8px;
        }
        .actions-column {
            min-width: 160px;
            white-space: nowrap;
        }
        .actions-column .btn {
            margin: 2px;
            display: inline-block;
        }
        .table th {
            font-size: 1.05rem;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .table img {
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.10);
            border: 1.5px solid #eee;
        }
        .modal-content {
            border-radius: 18px;
            box-shadow: 0 8px 32px rgba(102, 126, 234, 0.13);
        }
        .modal-header {
            border-bottom: none;
            background: linear-gradient(135deg,rgb(215, 76, 76)  0%, #764ba2 100%);
            color: #fff;
            border-top-left-radius: 18px;
            border-top-right-radius: 18px;
        }
        .modal-title {
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        .form-control, .form-control-file {
            border-radius: 10px;
            border: 1.5px solid #e0e0e0;
            font-size: 1rem;
        }
        .form-group label {
            font-weight: 500;
            color: #4b3fa7;
        }
        .modal-footer {
            border-top: none;
            padding-top: 0;
        }
        
        /* Styles for existing images */
        .existing-image-item {
            position: relative;
            display: inline-block;
            margin: 4px;
        }
        .existing-image-item img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid #28a745;
        }
        .delete-existing-image {
            position: absolute;
            top: -5px;
            right: -5px;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            padding: 0;
            font-size: 12px;
            line-height: 1;
        }
        
        /* Image upload mode selection */
        .image-upload-mode {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
        }
        .image-upload-mode label {
            margin-bottom: 0;
            cursor: pointer;
            display: flex;
            align-items: center;
        }
        .image-upload-mode input[type="radio"] {
            margin-right: 8px;
        }
        
        @media (max-width: 767px) {
            .container {
                padding: 18px 4px 12px 4px;
            }
            .table th, .table td {
                font-size: 0.92rem;
            }
            .actions-column {
                min-width: 140px;
            }
            .actions-column .btn {
                font-size: 0.8rem;
                padding: 5px 10px;
            }
            h3 {
                font-size: 1.2rem;
            }
        }
    </style>
</head>
<body>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>Room Management</h3>
        <a href="admin_dashboard.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <button class="btn btn-success mb-3" data-toggle="modal" data-target="#roomModal">
        <i class="fas fa-plus"></i> Add Room
    </button>
    
    <?php if (isset($_GET['success'])): ?>
        <?php if ($_GET['success'] === 'add'): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i> Room added successfully!
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php elseif ($_GET['success'] === 'edit'): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-edit"></i> Room updated successfully!
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php elseif ($_GET['success'] === 'delete'): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-trash-alt"></i> Room deleted successfully!
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>
    <?php endif; ?>
    
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($_GET['error']) ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>

    <div class="table-responsive">
        <table class="table table-bordered table-hover">
            <thead class="thead-dark">
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Number</th>
                    <th>Type</th>
                    <th>Price</th>
                    <th>Max Guests</th>
                    <th>Status</th>
                    <th>Images</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $stmt = $pdo->query('SELECT r.*, rt.TypeName FROM Room r JOIN RoomType rt ON r.RoomTypeID = rt.RoomTypeID');
            while ($row = $stmt->fetch()):
            ?>
                <tr>
                    <td><?= $row['RoomID'] ?></td>
                    <td><?= htmlspecialchars($row['RoomName']) ?></td>
                    <td><?= htmlspecialchars($row['RoomNumber']) ?></td>
                    <td><?= htmlspecialchars($row['TypeName']) ?></td>
                    <td><?= number_format($row['PricePerNight']) ?> VNĐ</td>
                    <td><?= $row['MaxGuests'] ?? 'N/A' ?></td>
                    <td>
                        <select class="form-control form-control-sm status-select" 
                                data-room-id="<?= $row['RoomID'] ?>" 
                                onchange="updateRoomStatus(<?= $row['RoomID'] ?>, this.value)">
                            <option value="Available" <?= $row['Status'] === 'Available' ? 'selected' : '' ?>>Available</option>
                            <option value="Reserved" <?= $row['Status'] === 'Reserved' ? 'selected' : '' ?>>Reserved</option>
                            <option value="Occupied" <?= $row['Status'] === 'Occupied' ? 'selected' : '' ?>>Occupied</option>
                            <option value="Maintenance" <?= $row['Status'] === 'Maintenance' ? 'selected' : '' ?>>Maintenance</option>
                        </select>
                    </td>
                    <td>
                        <?php
                        $imgs = $pdo->prepare("SELECT ImagePath FROM RoomImage WHERE RoomID=?");
                        $imgs->execute([$row['RoomID']]);
                        $imageCount = 0;
                        foreach ($imgs as $img):
                            $imgPath = 'uploads/' . $img['ImagePath'];
                            if (!file_exists($imgPath) || empty($img['ImagePath'])) {
                                $imgPath = 'uploads/default.jpg';
                            }
                            $imageCount++;
                        ?>
                            <img src="<?= htmlspecialchars($imgPath) ?>" width="60" style="margin:2px;">
                        <?php endforeach; ?>
                        <?php if ($imageCount > 0): ?>
                            <small class="text-muted d-block"><?= $imageCount ?> image(s)</small>
                        <?php endif; ?>
                    </td>
                    <td class="actions-column">
                        <button class="btn btn-warning btn-sm" data-toggle="modal"
                                data-target="#roomModal"
                                data-id="<?= $row['RoomID'] ?>"
                                data-name="<?= htmlspecialchars($row['RoomName'], ENT_QUOTES) ?>"
                                data-number="<?= htmlspecialchars($row['RoomNumber'], ENT_QUOTES) ?>"
                                data-type="<?= $row['RoomTypeID'] ?>"
                                data-price="<?= $row['PricePerNight'] ?>"
                                data-status="<?= $row['Status'] ?>"
                                data-max-guests="<?= $row['MaxGuests'] ?? '' ?>"
                                data-description="<?= htmlspecialchars($row['Description'], ENT_QUOTES) ?>">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <button class="btn btn-danger btn-sm" onclick="confirmDelete(<?= $row['RoomID'] ?>, '<?= htmlspecialchars($row['RoomName'], ENT_QUOTES) ?>')">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="roomModal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
    <form method="post" enctype="multipart/form-data" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add / Edit Room</h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="room_id" id="room_id">
        <div class="form-group">
          <label>Room Name</label>
          <input type="text" name="room_name" id="room_name" class="form-control" required>
        </div>
        <div class="form-group">
          <label>Room Number</label>
          <input type="text" name="room_number" id="room_number" class="form-control" required>
        </div>
        <div class="form-group">
          <label>Room Type</label>
          <select name="room_type" id="room_type" class="form-control" required>
            <?php
            $types = $pdo->query("SELECT * FROM RoomType");
            foreach ($types as $type):
            ?>
              <option value="<?= $type['RoomTypeID'] ?>"><?= htmlspecialchars($type['TypeName']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Price per Night</label>
          <input type="number" name="price" id="price" class="form-control" required>
        </div>
        <div class="form-group">
          <label>Max Guests</label>
          <input type="number" name="max_guests" id="max_guests" class="form-control" min="1" max="10" required>
        </div>
        <div class="form-group">
          <label>Status</label>
          <select name="status" id="status" class="form-control" required>
            <option value="Available">Available</option>
            <option value="Reserved">Reserved</option>
            <option value="Occupied">Occupied</option>
            <option value="Maintenance">Maintenance</option>
          </select>
        </div>
        <div class="form-group">
          <label>Description</label>
          <textarea name="description" id="description" class="form-control" rows="2" maxlength="500"></textarea>
        </div>
        
        <!-- Image Upload Mode Selection (chỉ hiện khi edit) -->
        <div class="image-upload-mode" id="imageUploadMode" style="display: none;">
          <h6><i class="fas fa-images"></i> Image Upload Mode:</h6>
          <div class="form-check">
            <label class="form-check-label">
              <input type="radio" name="replace_images" value="add" class="form-check-input" checked>
              <i class="fas fa-plus text-success"></i> Add new images (keep existing images)
            </label>
          </div>
          <div class="form-check">
            <label class="form-check-label">
              <input type="radio" name="replace_images" value="replace" class="form-check-input">
              <i class="fas fa-sync-alt text-warning"></i> Replace all images (delete existing images)
            </label>
          </div>
        </div>
        
        <div class="form-group">
          <label>Room Images</label>
          <input type="file" name="room_image[]" class="form-control-file" id="room_image" multiple accept="image/*">
          <small class="text-muted">Maximum 10 images per room. Accepted formats: JPG, PNG, GIF, WebP. Max size: 5MB per image.</small>
          <div id="existingImagesContainer" class="mt-2"></div>
          <div id="imagePreview" class="mt-2" style="display:flex;gap:8px;flex-wrap:wrap;"></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i> Save Room
        </button>
        <button type="button" class="btn btn-secondary" data-dismiss="modal">
            <i class="fas fa-times"></i> Cancel
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Modal xác nhận xóa phòng -->
<div class="modal fade" id="deleteRoomModal" tabindex="-1" role="dialog" aria-labelledby="deleteRoomModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="deleteRoomModalLabel">
          <i class="fas fa-exclamation-triangle text-danger"></i> Confirm Delete Room
        </h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body text-center">
        <p>Are you sure you want to delete this room?</p>
        <p class="text-danger font-weight-bold" id="roomNameToDelete"></p>
        <p class="text-muted">This action cannot be undone. All room images and related data will be permanently deleted.</p>
      </div>
      <div class="modal-footer justify-content-center">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">
            <i class="fas fa-times"></i> Cancel
        </button>
        <a href="#" id="confirmDeleteBtn" class="btn btn-danger">
            <i class="fas fa-trash"></i> Delete Room
        </a>
      </div>
    </div>
  </div>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script>
$('#roomModal').on('show.bs.modal', function (event) {
    var button = $(event.relatedTarget);
    var roomId = button.data('id');
    
    // Clear previous data
    $('#room_id').val(roomId || '');
    $('#room_name').val(button.data('name') || '');
    $('#room_number').val(button.data('number') || '');
    $('#room_type').val(button.data('type') || '');
    $('#price').val(button.data('price') || '');
    $('#max_guests').val(button.data('max-guests') || '2');
    $('#status').val(button.data('status') || 'Available');
    $('#description').val(button.data('description') || '');
    $('#existingImagesContainer').empty();
    $('#imagePreview').empty();
    $('#room_image').val('');
    
    // Show/hide image upload mode based on whether we're editing
    if (roomId) {
        $('#imageUploadMode').show();
        $('.modal-title').text('Edit Room');
        loadExistingImages(roomId);
    } else {
        $('#imageUploadMode').hide();
        $('.modal-title').text('Add New Room');
    }
});

// Function to update room status via AJAX
function updateRoomStatus(roomId, status) {
    $.ajax({
        url: 'update_room_status.php',
        type: 'POST',
        data: {
            room_id: roomId,
            status: status
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                alert('Room status updated successfully!');
                location.reload();
            } else {
                alert('Error: ' + response.error);
            }
        },
        error: function(xhr, status, error) {
            alert('Error updating room status: ' + error);
        }
    });
}

// Function to confirm delete room
function confirmDelete(roomId, roomName) {
    $('#roomNameToDelete').text(roomName);
    $('#confirmDeleteBtn').attr('href', '?delete=' + roomId);
    $('#deleteRoomModal').modal('show');
}

// Load existing images for editing
function loadExistingImages(roomId) {
    $.ajax({
        url: 'get_room_images.php',
        type: 'GET',
        data: { room_id: roomId },
        dataType: 'json',
        success: function(response) {
            if (response.success && response.images && response.images.length > 0) {
                $('#existingImagesContainer').append('<div class="existing-images-label mb-2"><strong><i class="fas fa-images text-primary"></i> Current Images (' + response.images.length + '):</strong></div>');
                
                response.images.forEach(function(image) {
                    const imageContainer = $(`
                        <div class="existing-image-item" data-image-id="${image.id}" style="position: relative; display: inline-block; margin: 4px;">
                            <img src="uploads/${image.path}" style="
                                width: 80px; 
                                height: 80px; 
                                object-fit: cover; 
                                border-radius: 8px; 
                                border: 2px solid #28a745;
                            ">
                            <button type="button" class="btn btn-danger btn-sm delete-existing-image" style="
                                position: absolute; 
                                top: -5px; 
                                right: -5px; 
                                width: 20px; 
                                height: 20px; 
                                border-radius: 50%; 
                                padding: 0; 
                                font-size: 12px;
                                line-height: 1;
                            ">&times;</button>
                            <div style="font-size: 10px; text-align: center; margin-top: 2px; color: #28a745;">
                                Existing
                            </div>
                        </div>
                    `);
                    $('#existingImagesContainer').append(imageContainer);
                });
            }
        },
        error: function() {
            console.log('Error loading existing images');
        }
    });
}

// Delete existing image
$(document).on('click', '.delete-existing-image', function() {
    const imageId = $(this).closest('.existing-image-item').data('image-id');
    const imageElement = $(this).closest('.existing-image-item');
    
    if (confirm('Are you sure you want to delete this image?')) {
        $.ajax({
            url: 'delete_room_image.php',
            type: 'POST',
            data: { image_id: imageId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    imageElement.remove();
                    
                    // Update counter
                    const remainingImages = $('.existing-image-item').length;
                    if (remainingImages === 0) {
                        $('.existing-images-label').remove();
                    } else {
                        $('.existing-images-label').html('<strong><i class="fas fa-images text-primary"></i> Current Images (' + remainingImages + '):</strong>');
                    }
                } else {
                    alert('Error deleting image: ' + response.error);
                }
            },
            error: function() {
                alert('Error deleting image');
            }
        });
    }
});

// Enhanced image preview with delete functionality
$('#room_image').on('change', function() {
    $('#imagePreview').empty();
    const files = this.files;
    if (files && files.length > 0) {
        $('#imagePreview').append('<div class="new-images-label mb-2"><strong><i class="fas fa-plus text-success"></i> New Images (' + files.length + '):</strong></div>');
        
        Array.from(files).forEach((file, index) => {
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const imageContainer = $(`
                        <div class="image-preview-item" data-index="${index}" style="position: relative; display: inline-block; margin: 4px;">
                            <img src="${e.target.result}" style="
                                width: 80px; 
                                height: 80px; 
                                object-fit: cover; 
                                border-radius: 8px; 
                                border: 2px solid #007bff;
                            ">
                            <button type="button" class="btn btn-warning btn-sm remove-image" style="
                                position: absolute; 
                                top: -5px; 
                                right: -5px; 
                                width: 20px; 
                                height: 20px; 
                                border-radius: 50%; 
                                padding: 0; 
                                font-size: 12px;
                                line-height: 1;
                            ">&times;</button>
                            <div style="font-size: 10px; text-align: center; margin-top: 2px; color: #007bff;">
                                ${file.name.substring(0, 12)}${file.name.length > 12 ? '...' : ''}
                            </div>
                        </div>
                    `);
                    $('#imagePreview').append(imageContainer);
                };
                reader.readAsDataURL(file);
            }
        });
    }
});

// Remove image from preview
$(document).on('click', '.remove-image', function() {
    const index = $(this).closest('.image-preview-item').data('index');
    $(this).closest('.image-preview-item').remove();
    
    // Remove file from input
    const input = document.getElementById('room_image');
    const dt = new DataTransfer();
    
    Array.from(input.files).forEach((file, i) => {
        if (i !== index) {
            dt.items.add(file);
        }
    });
    
    input.files = dt.files;
    
    // Update counter
    const remainingFiles = input.files.length;
    if (remainingFiles === 0) {
        $('.new-images-label').remove();
    } else {
        $('.new-images-label').html('<strong><i class="fas fa-plus text-success"></i> New Images (' + remainingFiles + '):</strong>');
    }
});

// Form validation before submit
$('#roomModal form').on('submit', function(e) {
    const fileInput = document.getElementById('room_image');
    const roomId = $('#room_id').val();
    const replaceMode = $('input[name="replace_images"]:checked').val();
    
    if (fileInput.files.length > 0) {
        // Validate file types and sizes
        const maxSize = 5 * 1024 * 1024; // 5MB
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        
        for (let file of fileInput.files) {
            if (!allowedTypes.includes(file.type)) {
                alert(`File ${file.name} is not a valid image type`);
                e.preventDefault();
                return false;
            }
            
            if (file.size > maxSize) {
                alert(`File ${file.name} is too large. Maximum size is 5MB`);
                e.preventDefault();
                return false;
            }
        }
        
        // Check total image limit for existing rooms
        if (roomId && replaceMode === 'add') {
            const currentImages = $('.existing-image-item').length;
            const newImages = fileInput.files.length;
            const totalImages = currentImages + newImages;
            
            if (totalImages > 10) {
                alert(`Maximum 10 images allowed per room. Current: ${currentImages}, Adding: ${newImages}, Total would be: ${totalImages}`);
                e.preventDefault();
                return false;
            }
        }
    }
});

// Show upload mode info based on selection
$('input[name="replace_images"]').on('change', function() {
    const mode = $(this).val();
    const currentImages = $('.existing-image-item').length;
    
    if (mode === 'replace') {
        if (currentImages > 0) {
            if (!confirm(`This will delete all ${currentImages} existing image(s) and replace them with new ones. Are you sure?`)) {
                $('input[name="replace_images"][value="add"]').prop('checked', true);
            }
        }
    }
});
</script>
</body>
</html>