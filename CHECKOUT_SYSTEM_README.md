# Hệ thống Quản lý Check-out và Cập nhật Trạng thái Phòng

## Tổng quan
Hệ thống đã được cải thiện để xử lý việc check-out và tự động cập nhật trạng thái phòng, đảm bảo phòng được chuyển về trạng thái "Available" khi khách trả phòng.

## Các tính năng mới

### 1. Trang Quản lý Check-out (`admin/checkout_manage.php`)
- **Hiển thị danh sách phòng đang có khách**
- **Nút check-out** để admin xác nhận khách đã trả phòng
- **Tự động cập nhật trạng thái phòng** thành "Available"
- **Ghi nhận ngày check-out thực tế**

### 2. Script Tự động Cập nhật (`update_room_status_auto.php`)
- **Cập nhật phòng check-in** thành "Occupied"
- **Cập nhật phòng check-out** thành "Available"
- **Xử lý check-out thực tế** từ admin
- **Hiển thị báo cáo chi tiết**

### 3. File Test (`test_room_status.php`)
- **Kiểm tra trạng thái phòng** hiện tại
- **Test cập nhật trạng thái** thủ công
- **Hiển thị logic hiển thị** trạng thái

## Cách sử dụng

### Cho Admin:

#### 1. Quản lý Check-out
1. Đăng nhập vào Admin Dashboard
2. Vào menu "Quản lý check-out"
3. Xem danh sách phòng đang có khách
4. Nhấn nút "Check-out" để xác nhận khách đã trả phòng
5. Phòng sẽ tự động chuyển về trạng thái "Available"

#### 2. Cập nhật Trạng thái Phòng
1. Vào "Quản lý phòng" trong Admin Dashboard
2. Sử dụng dropdown để thay đổi trạng thái phòng
3. Trạng thái sẽ được cập nhật ngay lập tức

#### 3. Chạy Script Tự động
1. Truy cập `update_room_status_auto.php`
2. Script sẽ tự động cập nhật trạng thái phòng
3. Xem báo cáo chi tiết về các thay đổi

### Cho Khách hàng:
- **Trang Room** sẽ hiển thị trạng thái phòng chính xác
- **Chỉ phòng Available** mới có thể đặt
- **Trạng thái được cập nhật real-time**

## Logic hoạt động

### 1. Ưu tiên Trạng thái
```
1. Maintenance (Admin đặt) - Không thể đặt
2. Occupied (Admin đặt) - Không thể đặt  
3. Available (Admin đặt) - Có thể đặt
4. Booked (Tự động từ booking) - Không thể đặt
5. Available (Mặc định) - Có thể đặt
```

### 2. Quy trình Check-out
```
1. Admin xác nhận check-out
2. Cập nhật Room.Status = 'Available'
3. Ghi nhận ActualCheckOutDate
4. Phòng hiển thị "Available" trên trang Room
5. Khách hàng có thể đặt phòng
```

### 3. Cập nhật Tự động
```
- Check-in date = hôm nay → Phòng = 'Occupied'
- Check-out date = hôm nay → Phòng = 'Available'
- ActualCheckOutDate = hôm nay → Phòng = 'Available'
- Không có booking → Phòng = 'Available'
```

## Files đã được tạo/cập nhật

### Files mới:
- `admin/checkout_manage.php` - Trang quản lý check-out
- `test_room_status.php` - File test trạng thái phòng
- `CHECKOUT_SYSTEM_README.md` - Hướng dẫn này

### Files đã cập nhật:
- `admin_dashboard.php` - Thêm menu check-out management
- `update_room_status_auto.php` - Cải thiện script tự động
- `update_room_status.php` - Xử lý AJAX cập nhật trạng thái

## Lưu ý quan trọng

### 1. Database
- Cần có trường `ActualCheckOutDate` trong bảng `Reservation`
- Nếu chưa có, chạy SQL:
```sql
ALTER TABLE Reservation ADD COLUMN ActualCheckOutDate DATE NULL;
```

### 2. Quyền truy cập
- Chỉ Admin mới có thể truy cập trang check-out management
- Cần đăng nhập với role "Admin"

### 3. Tự động hóa
- Có thể chạy script `update_room_status_auto.php` định kỳ
- Có thể setup cron job để chạy hàng ngày

## Troubleshooting

### Nếu phòng không cập nhật trạng thái:
1. Kiểm tra quyền Admin
2. Chạy script `update_room_status_auto.php`
3. Kiểm tra console browser để xem lỗi JavaScript
4. Kiểm tra log lỗi PHP

### Nếu không thể check-out:
1. Kiểm tra reservation có tồn tại không
2. Kiểm tra room_id có đúng không
3. Kiểm tra database connection

### Nếu trang Room không hiển thị đúng:
1. Kiểm tra logic `DisplayStatus` trong `room.php`
2. Chạy file `test_room_status.php` để debug
3. Kiểm tra cache browser

## Kết quả mong đợi

✅ **Khi admin thay đổi trạng thái phòng** → Trang Room cập nhật ngay lập tức

✅ **Khi khách check-out** → Phòng chuyển về "Available"

✅ **Khi phòng Available** → Khách hàng có thể đặt

✅ **Hệ thống tự động** → Cập nhật trạng thái dựa trên ngày

✅ **Đồng bộ hoàn toàn** → Giữa Admin Dashboard và trang Room 