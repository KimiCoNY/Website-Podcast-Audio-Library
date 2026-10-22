HƯỚNG DẪN VẬN HÀNH WEBSITE PODCAST / MEDIA LIBRARY
==================================================

Môi trường: Ubuntu Server trong VirtualBox. Lệnh chạy trong Ubuntu/SSH, trừ
lệnh scp được ghi rõ là chạy trên PowerShell Windows.

1. CẤU HÌNH LẦN ĐẦU
-------------------
Vào thư mục project:

    cd ~/Bai_37_Podcast_Media_Library

Chỉ chạy cp nếu chưa có file .env:

    cp .env.example .env
    nano .env

Đặt mật khẩu riêng cho MYSQL_ROOT_PASSWORD và MYSQL_PASSWORD, lưu file
(Ctrl+O, Enter, Ctrl+X), rồi khóa quyền đọc:

    chmod 600 .env

Không đưa .env lên GitHub hoặc gửi cho người khác.

2. KHỞI ĐỘNG VÀ TRUY CẬP
------------------------
Khởi động website và các exporter trong Compose của Podcast:

    cd ~/Bai_37_Podcast_Media_Library
    sudo docker compose config -q
    sudo docker compose up -d --build
    sudo docker compose ps

Khởi động stack giám sát riêng:

    cd ~/bai8
    sudo docker compose up -d
    sudo docker compose ps

Lấy IP máy ảo:

    hostname -I

Dùng IP LAN dạng 192.168.x.x, không dùng IP Docker dạng 172.x.x.x. Thay
<IP_VM> trong các địa chỉ sau bằng IP đang hiển thị:

    Website:       http://<IP_VM>:8082
    phpMyAdmin:    http://<IP_VM>:8081
    Grafana:       http://<IP_VM>:3000
    Prometheus:    http://<IP_VM>:9090/targets

3. THÊM MỘT HOẶC NHIỀU FILE AUDIO
---------------------------------
Chép file vào thư mục uploads trước khi tạo bản ghi. Nếu file đang ở Ubuntu:

    cd ~/Bai_37_Podcast_Media_Library
    cp "/duong/dan/bai-nhac.opus" uploads/
    chmod 644 "uploads/bai-nhac.opus"

Nếu file ở Windows, chạy trên PowerShell. Có thể ghi nhiều file trước đích:

    scp "C:\Users\Admin\Music\bai-1.mp3" "C:\Users\Admin\Music\bai-2.opus" kimicony@<IP_VM>:~/Bai_37_Podcast_Media_Library/uploads/

Trong phpMyAdmin, mở database podcast_library → tab SQL. Xem danh mục và
slug hiện có:

    SELECT id, name, slug FROM categories;

Thêm một bài vào danh mục audio (thay tên, slug, mô tả và tên file cho đúng):

    INSERT INTO episodes
      (category_id, title, slug, description, audio_path, status, published_at)
    SELECT id, 'Tên bài nhạc', 'ten-bai-nhac', 'Mô tả bài nhạc',
           '/uploads/ten-bai-nhac.opus', 'published', NOW()
    FROM categories WHERE slug = 'audio';

Thêm nhiều bài trong một câu SQL; mỗi slug phải khác nhau và mỗi audio_path
phải khớp tên file trong uploads:

    INSERT INTO episodes
      (category_id, title, slug, description, audio_path, status, published_at)
    VALUES
      ((SELECT id FROM categories WHERE slug = 'audio'),
       'Bài thứ nhất', 'bai-thu-nhat', 'Mô tả bài thứ nhất',
       '/uploads/bai-1.mp3', 'published', NOW()),
      ((SELECT id FROM categories WHERE slug = 'audio'),
       'Bài thứ hai', 'bai-thu-hai', 'Mô tả bài thứ hai',
       '/uploads/bai-2.opus', 'published', NOW());

Đổi slug audio thành podcast nếu muốn xếp vào danh mục Podcast. Tập chỉ hiện
trên trang khi status là published. Tên file nên viết gọn, không dấu và không
có khoảng trắng. File .opus được khai báo MIME audio/ogg; codecs=opus.

Kiểm tra bản ghi:

    SELECT e.id, c.name AS danh_muc, e.title, e.audio_path, e.status
    FROM episodes e LEFT JOIN categories c ON c.id = e.category_id
    ORDER BY e.id DESC;

4. TRUY VẤN METRICS TRONG GRAFANA
---------------------------------
Mở Grafana → Explore → chọn datasource prometheus → chọn Code. Dán truy vấn
vào ô query và bấm Run query.

    up

Trạng thái các target; giá trị 1 là UP.

    probe_success{job="podcast-http"}

Kiểm tra Blackbox truy cập website; giá trị 1 là thành công.

    probe_http_status_code{job="podcast-http"}

Mã HTTP trả về website, thường là 200.

    container_memory_working_set_bytes

RAM của các container. Truy vấn này trả nhiều chuỗi vì có nhiều container.

    rate(container_cpu_usage_seconds_total[5m])

Tốc độ CPU container trong 5 phút gần nhất.

Nếu No data: chọn khoảng thời gian Last 1 hour, bấm Run query, rồi mở
http://<IP_VM>:9090/targets để xem target có UP không. Cấu hình hiện tại có
metrics HTTP và container; chưa có metrics số lượt nghe hoặc MySQL Exporter.

5. XEM LOG BẰNG LOKI
--------------------
Trong Grafana → Explore → chọn datasource loki → Code → Run query:

    {container=~".*podcast.*"}

Chỉ xem log có chữ GET:

    {container=~".*podcast.*"} |= "GET"

Lọc dòng lỗi:

    {container=~".*podcast.*"} |= "error"

Nếu không thấy log, kiểm tra khoảng thời gian và chọn label bằng Label browser.

6. XEM TÀI KHOẢN VÀ MẬT KHẨU
----------------------------
Thông tin phpMyAdmin/MySQL nằm trong file .env của project:

    cd ~/Bai_37_Podcast_Media_Library
    grep -E '^(MYSQL_DATABASE|MYSQL_USER|MYSQL_PASSWORD|MYSQL_ROOT_PASSWORD)=' .env

Đăng nhập phpMyAdmin bằng MYSQL_USER và MYSQL_PASSWORD (thường user là
podcast_app). Máy chủ MySQL là db; database là podcast_library. MYSQL_ROOT_PASSWORD
là mật khẩu root; nếu root không đăng nhập được qua phpMyAdmin, dùng tài khoản
podcast_app.

Tìm tài khoản Grafana trong cấu hình stack giám sát:

    cd ~/bai8
    grep -nE 'GRAFANA|GF_SECURITY_ADMIN' .env docker-compose.yml compose.yml compose.yaml 2>/dev/null

Dùng giá trị đã khai báo. Nếu Grafana mới cài và chưa đổi mật khẩu, tài khoản
mặc định thường là admin / admin. Nếu đã đổi mật khẩu nhưng quên, đặt lại:

    cd ~/bai8
    sudo docker compose exec grafana grafana cli admin reset-admin-password 'MAT_KHAU_MOI'

Thay MAT_KHAU_MOI bằng mật khẩu mới của bạn.

7. DỪNG, MỞ LẠI VÀ XEM LOG
--------------------------
Dừng stack Podcast (chạy trong thư mục project):

    cd ~/Bai_37_Podcast_Media_Library
    sudo docker compose stop

Dừng stack giám sát riêng:

    cd ~/bai8
    sudo docker compose stop

Mở lại bằng lệnh sudo docker compose up -d trong từng thư mục. Lệnh stop giữ
container và dữ liệu. Không dùng down -v nếu cần giữ database.

Xem log khi website có lỗi:

    cd ~/Bai_37_Podcast_Media_Library
    sudo docker compose logs --tail=80 app nginx db

Nếu bài nhạc không phát: xác nhận file có trong uploads, đường dẫn trong
episodes.audio_path đúng từng ký tự, trạng thái published, và quyền file là
644.
