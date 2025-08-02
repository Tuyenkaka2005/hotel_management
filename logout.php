<?php
session_start();
// Hủy toàn bộ session
session_unset();
session_destroy();
// Chuyển về trang chủ
header('Location: index.php');
exit; 