# Đề tài 37 — Podcast / Audio Library

Website thư viện podcast/audio bằng PHP, MySQL, Nginx và Docker Compose. Một lệnh Compose chạy website, phpMyAdmin, Prometheus, Grafana, Loki, Promtail và exporter.

## Thành phần và địa chỉ

| Thành phần | URL trong LAN |
|---|---|
| Website | `http://<IP_VM>:8082` |
| phpMyAdmin | `http://<IP_VM>:8081` |
| Grafana | `http://<IP_VM>:3000` |
| Prometheus targets | `http://<IP_VM>:9090/targets` |

Trong Ubuntu, lấy IP bằng `hostname -I`, rồi thay `<IP_VM>` bằng IP LAN dạng `192.168.x.x`. Website được map chính xác là `8082:80`. MySQL không publish cổng trực tiếp ra host.

## Khởi động trên Ubuntu / VirtualBox

Yêu cầu Docker Engine và Docker Compose plugin. Dùng Bridged Adapter nếu cần truy cập từ Windows qua IP LAN. Vào thư mục project:

```bash
cd ~/Bai_37_Podcast_Media_Library
```

Tạo `.env` lần đầu và sinh bốn mật khẩu ngẫu nhiên:

```bash
cp .env.example .env
openssl rand -hex 24
openssl rand -hex 24
openssl rand -hex 24
openssl rand -hex 24
nano .env
chmod 600 .env
```

Đặt các mật khẩu vào `MYSQL_ROOT_PASSWORD`, `MYSQL_PASSWORD`, `MYSQL_EXPORTER_PASSWORD`, `GRAFANA_ADMIN_PASSWORD`; giữ database `podcast_library`, user ứng dụng `podcast_app`, Grafana user `admin`. Dùng mật khẩu khác nhau. Không commit/gửi `.env`.

Khởi động và xem trạng thái:

```bash
sudo docker compose config -q
sudo docker compose up -d --build
sudo docker compose ps
hostname -I
```

Compose chạy cả website và monitoring. Nếu trước đó đang chạy stack monitoring riêng ở `~/bai8`, dừng stack cũ trước để nhường các cổng 3000 và 9090; `stop` giữ dữ liệu cũ:

```bash
cd ~/bai8
sudo docker compose stop
cd ~/Bai_37_Podcast_Media_Library
sudo docker compose up -d --build
```

Nếu database volume đã tồn tại từ trước, file khởi tạo không tự chạy lại. Tạo/cập nhật tài khoản metrics riêng một lần:

```bash
sudo docker compose exec db bash /docker-entrypoint-initdb.d/02-create-metrics-user.sh
sudo docker compose restart mysql-exporter prometheus
```

## Thêm một hoặc nhiều file audio

Chép file vào `uploads/` trước. Nếu file ở Windows, chạy trong PowerShell:

```powershell
scp "C:\Users\Admin\Music\bai-1.mp3" "C:\Users\Admin\Music\bai-2.opus" kimicony@<IP_VM>:~/Bai_37_Podcast_Media_Library/uploads/
```

Trong phpMyAdmin chọn database `podcast_library` → **SQL**. Xem danh mục:

```sql
SELECT id, name, slug FROM categories;
```

Thêm một bài vào danh mục audio (sửa title, slug, mô tả và file cho đúng; slug phải mới):

```sql
INSERT INTO episodes
  (category_id, title, slug, description, audio_path, status, published_at)
SELECT id, 'Tên bài nhạc', 'ten-bai-nhac', 'Mô tả bài nhạc',
       '/uploads/ten-bai-nhac.opus', 'published', NOW()
FROM categories WHERE slug = 'audio';
```

Thêm nhiều bài trong một lệnh:

```sql
INSERT INTO episodes
  (category_id, title, slug, description, audio_path, status, published_at)
VALUES
  ((SELECT id FROM categories WHERE slug = 'audio'), 'Bài thứ nhất', 'bai-thu-nhat', 'Mô tả thứ nhất', '/uploads/bai-1.mp3', 'published', NOW()),
  ((SELECT id FROM categories WHERE slug = 'audio'), 'Bài thứ hai', 'bai-thu-hai', 'Mô tả thứ hai', '/uploads/bai-2.opus', 'published', NOW());
```

Dùng category `podcast` cho podcast. `audio_path` phải khớp đường dẫn file; tập chỉ hiện khi `status='published'`. `.opus` được khai báo `audio/ogg; codecs=opus`. Đổi tên bài đã có bằng `UPDATE` theo slug hiện tại; không chèn lại slug cũ.

## Prometheus và Grafana

Grafana đã provision datasource **Prometheus**, **Loki** và dashboard **Podcast Library**. Mở Grafana → **Dashboards → Podcast Library** để xem HTTP, MySQL và CPU/RAM các container `app`, `db`, `nginx`.

Để nhập PromQL: Grafana → **Explore** → Prometheus → **Code** → dán truy vấn → **Run query**.

```promql
up
```

Scrape target còn hoạt động: `1` là UP.

```promql
probe_success{job="podcast-http"}
```

Blackbox kiểm tra website và `/health.php` (endpoint này xác nhận kết nối DB).

```promql
mysql_global_status_threads_connected
```

Số kết nối hiện tại trên MySQL.

```promql
sum by (container_label_com_docker_compose_service) (container_memory_working_set_bytes{container_label_com_docker_compose_service=~"app|db|nginx"})
```

RAM container theo service. CPU:

```promql
sum by (container_label_com_docker_compose_service) (rate(container_cpu_usage_seconds_total{container_label_com_docker_compose_service=~"app|db|nginx"}[5m]))
```

Trong Prometheus → **Status → Targets**, kiểm tra `podcast-http`, `podcast-cadvisor`, `podcast-mysql` và `prometheus` đều UP. Nếu `No data`, đặt thời gian Grafana thành **Last 1 hour**, chạy lại query và xem Targets.

## Loki / Promtail

Grafana → **Explore** → Loki → **Code**. Promtail đọc Docker JSON logs và gửi vào Loki. Ví dụ LogQL:

```logql
{job="docker"}
```

```logql
{job="docker"} |= "GET"
```

```logql
{job="docker"} |~ "(?i)error|denied|failed"
```

Chọn **Last 1 hour** và **Run query**. Nếu chưa có log, tải website một lần rồi truy vấn lại.

## Tài khoản và mật khẩu

- phpMyAdmin/MySQL app: host `db`, database `podcast_library`, user `podcast_app`, mật khẩu `MYSQL_PASSWORD` trong `.env`.
- MySQL root: `MYSQL_ROOT_PASSWORD` trong `.env`, chỉ dùng khi quản trị.
- Grafana: `GRAFANA_ADMIN_USER` và `GRAFANA_ADMIN_PASSWORD` trong `.env`.
- MySQL exporter dùng user riêng `podcast_metrics` và `MYSQL_EXPORTER_PASSWORD`.

Xem tại Ubuntu trong terminal riêng tư; không chụp/gửi nội dung có mật khẩu:

```bash
grep -E '^(MYSQL_DATABASE|MYSQL_USER|MYSQL_PASSWORD|MYSQL_ROOT_PASSWORD|MYSQL_EXPORTER_PASSWORD|GRAFANA_ADMIN_USER|GRAFANA_ADMIN_PASSWORD)=' .env
```

## Bảo mật, dừng và mở lại

- `.env` và audio trong `uploads/` bị Git bỏ qua; chỉ `.env.example` được commit.
- MySQL không publish cổng; app user và metrics user tách biệt khỏi root.
- Các network Docker tách frontend, backend và monitoring; cấu hình giám sát mount read-only khi phù hợp.
- Nginx bật security headers cơ bản. phpMyAdmin/Grafana/Prometheus chỉ dùng trong LAN tin cậy; không forward chúng ra Internet.
- cAdvisor đọc thông tin host để đo tài nguyên; các mount dữ liệu host được đặt read-only.

Dừng nhưng giữ dữ liệu, rồi mở lại:

```bash
sudo docker compose stop
sudo docker compose up -d
```

Xem trạng thái và log:

```bash
sudo docker compose ps
sudo docker compose logs --tail=80 app nginx db prometheus grafana loki promtail
```

`docker compose down` xóa container/network nhưng giữ named volumes. Không chạy `docker compose down -v` trừ khi muốn xóa database, dashboard và dữ liệu metrics/logs.
