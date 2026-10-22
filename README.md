# Đề tài 37 — Website Podcast / Media Library

Thư mục này là bộ khởi đầu để làm website quản lý và nghe podcast/audio bằng PHP + MySQL, đặt sau Nginx reverse proxy và bổ sung giám sát Prometheus/Grafana cùng log Loki.

## Bắt đầu

1. Đọc [hướng dẫn VirtualBox + PowerShell SSH](HUONG_DAN_UBUNTU_VIRTUALBOX.md) nếu dùng đúng môi trường đó.
2. Đọc [hướng dẫn chi tiết đề tài](HUONG_DAN_CHI_TIET.md) để làm website và các phần còn lại.
3. Sao chép `.env.example` thành `.env` và thay các mật khẩu mẫu trước khi chạy.
4. Xem [bảng đối chiếu tài liệu đã gửi](references/README.md) để biết phần nào nên lấy từ từng bài.
5. Không commit mật khẩu, file `.env`, file audio tải lên hoặc bản backup dữ liệu thật.

## Khởi chạy khung cơ bản

Từ thư mục này, sao chép `.env.example` thành `.env`, sửa mật khẩu, rồi chạy:

```bash
sudo docker compose config -q
sudo docker compose up -d --build
```

Mở website tại `http://IP_VM:8080` (bridge trong ảnh hiện tại là `http://192.168.1.19:8080`). phpMyAdmin được bind vào loopback Ubuntu; xem hướng dẫn SSH tunnel để mở từ Windows. Đây mới là khung website công khai + database; làm admin, upload và giám sát theo các giai đoạn trong hướng dẫn.

Các tài liệu gốc phù hợp và một số cấu hình giám sát bài 7 đã được đặt trong `references/` để tiện tra cứu.
