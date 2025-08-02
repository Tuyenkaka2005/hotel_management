# Hệ thống Cập nhật Trạng thái Phòng

## Tổng quan
Hệ thống đã được cập nhật để đồng bộ trạng thái phòng giữa Admin Dashboard và trang hiển thị phòng cho khách hàng.

## Các trạng thái phòng
1. **Available** - Phòng trống, có thể đặt
2. **Occupied** - Phòng có khách, không thể đặt
3. **Maintenance** - Phòng đang bảo trì, không thể đặt
4. **Booked** - Phòng đã được đặt (tự động từ hệ thống booking)

## Cách hoạt động

### 1. Logic ưu tiên trạng thái
- **Ưu tiên cao nhất**: Trạng thái do Admin đặt (Available/Occupied/Maintenance)
- **Ưu tiên thứ hai**: Trạng thái từ hệ thống booking (Booked)
- **Mặc định**: Available

### 2. Cập nhật từ Admin Dashboard
- Admin có thể thay đổi trạng thái phòng trực tiếp từ dropdown trong bảng quản lý phòng
- Thay đổi được lưu ngay lập tức qua AJAX
- Trạng thái mới sẽ hiển thị ngay trên trang Room cho khách hàng

### 3. Cập nhật tự động
- Khi có booking mới: Phòng chuyển thành 'Occupied'
- Khi xác nhận thanh toán: Phòng chuyển thành 'Occupied'
- Khi khách check-out: Phòng chuyển thành 'Available'

## Files đã được cập nhật

### 1. `room.php`
- Thêm logic `DisplayStatus` để ưu tiên trạng thái từ Admin
- Cập nhật hiển thị badge trạng thái với màu sắc phù hợp
- Cập nhật logic nút "Book room"

### 2. `index.php`
- Thêm logic `DisplayStatus` tương tự
- Cập nhật hiển thị trạng thái phòng trên trang chủ

### 3. `room_detail.php`
- Cập nhật logic hiển thị trạng thái phòng
- Cập nhật nút booking dựa trên trạng thái

### 4. `admin_dashboard.php`
- Thêm dropdown để cập nhật trạng thái phòng trực tiếp
- Thêm JavaScript để xử lý AJAX update

### 5. `room_manage.php`
- Thêm dropdown để cập nhật trạng thái phòng
- Thêm JavaScript để xử lý AJAX update

### 6. `booking_process.php`
- Cập nhật logic kiểm tra trạng thái phòng
- Cập nhật trạng thái phòng khi có booking mới

### 7. `update_room_status.php` (Mới)
- File xử lý AJAX để cập nhật trạng thái phòng
- Kiểm tra quyền admin
- Validate dữ liệu đầu vào

### 8. `update_room_status_auto.php` (Mới)
- Script tự động cập nhật trạng thái phòng
- Có thể chạy định kỳ để đảm bảo tính chính xác

## Cách sử dụng

### Cho Admin:
1. Đăng nhập vào Admin Dashboard
2. Vào phần "Room Management"
3. Thay đổi trạng thái phòng bằng dropdown
4. Trạng thái sẽ được cập nhật ngay lập tức

### Cho Khách hàng:
1. Trang Room sẽ hiển thị trạng thái phòng chính xác
2. Chỉ phòng có trạng thái "Available" mới có thể đặt
3. Trạng thái được cập nhật real-time

## Lưu ý quan trọng

1. **Trạng thái Maintenance**: Phòng đang bảo trì sẽ không bị thay đổi tự động
2. **Ưu tiên Admin**: Trạng thái do Admin đặt sẽ có ưu tiên cao nhất
3. **Đồng bộ**: Tất cả trang đều sử dụng cùng logic hiển thị trạng thái
4. **Bảo mật**: Chỉ Admin mới có thể thay đổi trạng thái phòng

## Troubleshooting

### Nếu trạng thái không cập nhật:
1. Kiểm tra quyền Admin
2. Kiểm tra console browser để xem lỗi JavaScript
3. Chạy script `update_room_status_auto.php` để đồng bộ trạng thái
4. Kiểm tra log lỗi PHP

### Nếu có lỗi AJAX:
1. Kiểm tra file `update_room_status.php` có tồn tại
2. Kiểm tra quyền truy cập file
3. Kiểm tra session admin có hợp lệ 