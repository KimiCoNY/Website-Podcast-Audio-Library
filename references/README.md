# Tài liệu tham khảo được đưa vào bộ đề tài

## Bản gốc đã sao chép

`source_materials/` có bốn tài liệu văn bản bạn gửi: bài 5 (reverse proxy), bài 6 (database), bài 8 (log/cảnh báo), bài 9 (Linux hardening). Bảng trong `../HUONG_DAN_CHI_TIET.md` chỉ ra phần nên dùng và phần nên bỏ.

## Cấu hình được chọn từ ZIP bài 7

`bai7_monitoring_templates/` có các cấu hình Prometheus/Blackbox Exporter và provisioning/dashboard Grafana trích từ ZIP bài 7. Chúng dùng làm mẫu, không phải cấu hình hoàn chỉnh cho podcast:

- Đổi target `wordpress` thành hostname dịch vụ website trong Compose của bạn.
- Dashboard có thể cần đổi PromQL và label theo tên service/container mới.
- Không trích Compose bài 7 vì đây là lab WordPress, dùng mật khẩu mẫu yếu và đặt nhiều cổng admin ra host.
- MySQL Exporter nên dùng tài khoản riêng chỉ có quyền đọc metrics; không sao chép mật khẩu app/root từ lab mẫu.

## Lưu ý an toàn

- Không chạy trực tiếp lệnh firewall/SSH của bài 9 trên máy thật; chỉ thực hành trên VM/lab có snapshot và đường khôi phục.
- Không commit `.env`, backup chứa dữ liệu, audio người dùng hoặc secrets.
- Hướng dẫn bài 8 dùng Promtail; tài liệu Grafana hiện ghi Promtail EOL ngày 02/03/2026 và khuyến nghị Alloy. Nếu rubric lớp bắt buộc Promtail, hỏi giảng viên trước khi thay collector.
