<?php
require_once '../config.php';
session_start();

// Kiểm tra quyền admin
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Xử lý reply feedback
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_feedback'])) {
    $feedback_id = (int)$_POST['feedback_id'];
    $reply = trim($_POST['reply']);
    
    if (empty($reply)) {
        $error_message = "Vui lòng nhập nội dung phản hồi";
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE Feedback SET Reply = ? WHERE FeedbackID = ?");
            $stmt->execute([$reply, $feedback_id]);
            $success_message = "Phản hồi đã được gửi thành công!";
        } catch (Exception $e) {
            $error_message = "Có lỗi xảy ra: " . $e->getMessage();
        }
    }
}

// Xử lý xóa feedback
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_feedback'])) {
    $feedback_id = (int)$_POST['feedback_id'];
    
    try {
        $stmt = $pdo->prepare("DELETE FROM Feedback WHERE FeedbackID = ?");
        $stmt->execute([$feedback_id]);
        $success_message = "Đánh giá đã được xóa thành công!";
    } catch (Exception $e) {
        $error_message = "Có lỗi xảy ra: " . $e->getMessage();
    }
}

// Lấy danh sách feedback
$sql = "
SELECT 
    f.FeedbackID,
    f.Rating,
    f.Comment,
    f.Reply,
    f.FeedbackDate,
    a.FullName,
    a.Email,
    r.RoomName,
    r.RoomNumber,
    rt.TypeName
FROM Feedback f
JOIN Account a ON f.AccountID = a.AccountID
JOIN Room r ON f.RoomID = r.RoomID
JOIN RoomType rt ON r.RoomTypeID = rt.RoomTypeID
ORDER BY f.FeedbackDate DESC
";

$stmt = $pdo->query($sql);
$feedbacks = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Thống kê tổng quan
$stats_sql = "
SELECT 
    COUNT(*) as total_feedback,
    AVG(Rating) as average_rating,
    COUNT(CASE WHEN Reply IS NULL OR Reply = '' THEN 1 END) as pending_replies
FROM Feedback
";

$stats_stmt = $pdo->query($stats_sql);
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
$stats['average_rating'] = round($stats['average_rating'], 1);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Feedback Management</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        body {
            background: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 0;
            margin-bottom: 30px;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
        }
        .stats-number {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        .stats-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        .feedback-item {
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        .feedback-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
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
            font-size: 1.2rem;
            margin-bottom: 10px;
        }
        .feedback-comment {
            color: #495057;
            line-height: 1.6;
            margin-bottom: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .feedback-reply {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 15px;
            margin-top: 15px;
            border-radius: 0 8px 8px 0;
        }
        .reply-form {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
        }
        .btn-reply {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border: none;
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }
        .btn-reply:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
            color: white;
        }
        .btn-delete {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            border: none;
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }
        .btn-delete:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.3);
            color: white;
        }
        .room-info {
            background: #e9ecef;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 10px;
            font-size: 0.9rem;
        }
        .alert {
            border-radius: 10px;
            border: none;
        }
        .table th {
            background: #f8f9fa;
            border-top: none;
            font-weight: 600;
        }
        .no-reply {
            color: #dc3545;
            font-style: italic;
        }
        .has-reply {
            color: #28a745;
            font-weight: 600;
        }
        
        /* Modal Styling */
        .modal-content {
            border-radius: 18px;
            border: none;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
        }
        
        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            border-radius: 18px 18px 0 0;
            border-bottom: none;
            padding: 20px 25px;
        }
        
        .modal-title {
            font-weight: 600;
            font-size: 1.2rem;
            letter-spacing: 0.5px;
        }
        
        .modal-header .close {
            color: #fff;
            opacity: 0.8;
            font-size: 1.5rem;
            text-shadow: none;
        }
        
        .modal-header .close:hover {
            opacity: 1;
            transform: scale(1.1);
        }
        
        .modal-body {
            padding: 25px;
            background: #fff;
        }
        
        .modal-footer {
            background: #f8f9fa;
            border-top: 1px solid #e9ecef;
            border-radius: 0 0 18px 18px;
            padding: 20px 25px;
        }
        
        .feedback-details {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .feedback-details h6 {
            color: #667eea;
            font-weight: 600;
            margin-bottom: 15px;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 8px 0;
            border-bottom: 1px solid #dee2e6;
        }
        
        .detail-row:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            font-weight: 600;
            color: #495057;
        }
        
        .detail-value {
            color: #6c757d;
        }
        
        .btn-success {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border: none;
            border-radius: 25px;
            font-weight: 600;
            padding: 10px 20px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }
        
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(40, 167, 69, 0.3);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            border: none;
            border-radius: 25px;
            font-weight: 600;
            padding: 10px 20px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }
        
        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(220, 53, 69, 0.3);
        }
        
        /* Modal Animation */
        .modal.fade .modal-dialog {
            transform: scale(0.8);
            transition: transform 0.3s ease-out;
        }
        
        .modal.show .modal-dialog {
            transform: scale(1);
        }
        
        /* Responsive Modal */
        @media (max-width: 576px) {
            .modal-dialog {
                margin: 10px;
            }
            
            .modal-body {
                padding: 20px 15px;
            }
            
            .feedback-details {
                padding: 15px;
            }
            
            .detail-row {
                flex-direction: column;
                gap: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <h2><i class="fas fa-star"></i> Feedback Management</h2>
                <a href="../admin_dashboard.php" class="btn btn-light">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i> <?= $success_message ?>
                <button type="button" class="close" data-dismiss="alert">
                    <span>&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle"></i> <?= $error_message ?>
                <button type="button" class="close" data-dismiss="alert">
                    <span>&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="row">
            <div class="col-md-4">
                <div class="stats-card text-center">
                    <div class="stats-number"><?= $stats['total_feedback'] ?></div>
                    <div class="stats-label">Total Rating</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card text-center">
                    <div class="stats-number"><?= $stats['average_rating'] ?></div>
                    <div class="stats-label">Average Rating</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card text-center">
                    <div class="stats-number"><?= $stats['pending_replies'] ?></div>
                    <div class="stats-label">Pending Reply</div>
                </div>
            </div>
        </div>

        <!-- Feedback List -->
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><i class="fas fa-list"></i> Feedback List</h4>
            </div>
            <div class="card-body">
                <?php if (empty($feedbacks)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-star fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No feedback yet</h5>
                        <p class="text-muted">Customers have not rated any rooms yet.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($feedbacks as $feedback): ?>
                        <div class="feedback-item">
                            <div class="room-info">
                                <i class="fas fa-hotel"></i> 
                                <strong><?= htmlspecialchars($feedback['RoomName']) ?></strong> 
                                (<?= htmlspecialchars($feedback['RoomNumber']) ?> - <?= htmlspecialchars($feedback['TypeName']) ?>)
                            </div>
                            
                            <div class="feedback-header">
                                <div class="feedback-author">
                                    <i class="fas fa-user"></i> <?= htmlspecialchars($feedback['FullName']) ?>
                                </div>
                                <div class="feedback-date">
                                    <i class="fas fa-calendar"></i> <?= date('d/m/Y', strtotime($feedback['FeedbackDate'])) ?>
                                </div>
                            </div>
                            
                            <div class="feedback-rating">
                                <?= str_repeat('★', $feedback['Rating']) . str_repeat('☆', 5 - $feedback['Rating']) ?>
                                <span class="ml-2">(<?= $feedback['Rating'] ?>/5)</span>
                            </div>
                            
                            <div class="feedback-comment">
                                <i class="fas fa-comment"></i> <?= nl2br(htmlspecialchars($feedback['Comment'])) ?>
                            </div>
                            
                            <?php if ($feedback['Reply']): ?>
                                <div class="feedback-reply">
                                    <div class="reply-label">
                                        <i class="fas fa-reply"></i> <strong>Reply from the hotel:</strong>
                                    </div>
                                    <div><?= nl2br(htmlspecialchars($feedback['Reply'])) ?></div>
                                </div>
                            <?php else: ?>
                                <div class="mt-3">
                                    <button type="button" class="btn btn-reply" onclick="openReplyModal(<?= $feedback['FeedbackID'] ?>, '<?= htmlspecialchars($feedback['FullName'], ENT_QUOTES) ?>', '<?= htmlspecialchars($feedback['RoomName'], ENT_QUOTES) ?>', '<?= htmlspecialchars($feedback['Comment'], ENT_QUOTES) ?>', <?= $feedback['Rating'] ?>)">
                                        <i class="fas fa-reply"></i> Send Reply
                                    </button>
                                </div>
                            <?php endif; ?>
                            
                            <div class="mt-3">
                                <button type="button" class="btn btn-delete" onclick="openDeleteModal(<?= $feedback['FeedbackID'] ?>, '<?= htmlspecialchars($feedback['FullName'], ENT_QUOTES) ?>', '<?= htmlspecialchars($feedback['RoomName'], ENT_QUOTES) ?>')">
                                    <i class="fas fa-trash"></i> Delete Rating
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>        
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal Reply Feedback -->
    <div class="modal fade" id="replyFeedbackModal" tabindex="-1" role="dialog" aria-labelledby="replyFeedbackModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="replyFeedbackModalLabel">
                        <i class="fas fa-reply"></i> Send Reply
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="feedback-details">
                        <h6><i class="fas fa-user"></i> Customer Information</h6>
                        <div class="detail-row">
                            <span class="detail-label">Customer Name:</span>
                            <span class="detail-value" id="replyGuestName"></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Room:</span>
                            <span class="detail-value" id="replyRoomName"></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Rating:</span>
                            <span class="detail-value" id="replyRating"></span>
                        </div>
                    </div>
                    
                    <div class="feedback-details">
                        <h6><i class="fas fa-comment"></i> Rating Content</h6>
                        <div class="detail-row">
                            <span class="detail-value" id="replyComment"></span>
                        </div>
                    </div>
                    
                    <form method="post" id="replyForm">
                        <input type="hidden" name="feedback_id" id="replyFeedbackId">
                        <div class="form-group">
                            <label for="replyText"><i class="fas fa-reply"></i> Your Reply:</label>
                            <textarea name="reply" id="replyText" class="form-control" rows="4" placeholder="Enter your reply for the customer..." required></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" form="replyForm" name="reply_feedback" class="btn btn-success">
                        <i class="fas fa-paper-plane"></i> Send Reply
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Delete Feedback -->
    <div class="modal fade" id="deleteFeedbackModal" tabindex="-1" role="dialog" aria-labelledby="deleteFeedbackModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteFeedbackModalLabel">
                        <i class="fas fa-trash"></i> Delete Rating
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body text-center">
                    <div class="feedback-details">
                        <h6><i class="fas fa-exclamation-triangle"></i> Confirm Delete</h6>
                        <div class="detail-row">
                            <span class="detail-label">Customer:</span>
                            <span class="detail-value" id="deleteGuestName"></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Room:</span>
                            <span class="detail-value" id="deleteRoomName"></span>
                        </div>
                    </div>
                    
                    <p class="text-warning font-weight-bold">
                        <i class="fas fa-exclamation-triangle"></i> 
                        Are you sure you want to delete this rating? This action cannot be undone.
                    </p>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <form method="post" style="display: inline;">
                        <input type="hidden" name="feedback_id" id="deleteFeedbackId">
                        <button type="submit" name="delete_feedback" class="btn btn-danger">
                            <i class="fas fa-trash"></i> Confirm Delete
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../js/bootstrap.bundle.min.js"></script>
    <script>
    function openReplyModal(feedbackId, guestName, roomName, comment, rating) {
        // Populate modal with feedback information
        $('#replyFeedbackId').val(feedbackId);
        $('#replyGuestName').text(guestName);
        $('#replyRoomName').text(roomName);
        $('#replyRating').html('★'.repeat(rating) + '☆'.repeat(5 - rating) + ` (${rating}/5)`);
        $('#replyComment').text(comment);
        $('#replyText').val(''); // Clear previous reply
        
        // Show modal
        $('#replyFeedbackModal').modal('show');
    }
    
    function openDeleteModal(feedbackId, guestName, roomName) {
        // Populate modal with feedback information
        $('#deleteFeedbackId').val(feedbackId);
        $('#deleteGuestName').text(guestName);
        $('#deleteRoomName').text(roomName);
        
        // Show modal
        $('#deleteFeedbackModal').modal('show');
    }
    </script>
</body>
</html> 