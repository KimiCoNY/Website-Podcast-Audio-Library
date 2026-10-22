# Hướng dẫn chi tiết — Đề tài 37: Website Podcast / Media Library

## 1. Mục tiêu cần làm

Làm một website có hai phần:

- **Người nghe:** xem danh sách tập podcast/audio, lọc theo danh mục, tìm kiếm, mở trang chi tiết và nghe bằng trình phát trên trình duyệt.
- **Quản trị viên:** đăng nhập, tạo/sửa/xóa danh mục và tập, tải audio/ảnh bìa lên, ẩn bản nháp hoặc xuất bản tập.

Phần triển khai cần chứng minh website chạy sau Nginx reverse proxy, dữ liệu nằm trong MySQL, quản trị được bằng phpMyAdmin, metrics xem trong Prometheus/Grafana, log xem bằng Loki/LogQL, và cấu hình có trong Git.

## 2. Stack đề xuất và sơ đồ

Chọn **PHP + MySQL** vì tài liệu bài 6 đã hướng dẫn MySQL/phpMyAdmin, còn PHP giúp tập trung thời gian vào chức năng media và hạ tầng. Nếu giảng viên yêu cầu Node.js thì giữ nguyên kiến trúc, thay dịch vụ PHP bằng Node.js và dùng cùng MySQL.

```text
Trình duyệt
   |
   v
Nginx reverse proxy :80/:443
   |
   v
Ứng dụng PHP :80 ---- MySQL :3306 (chỉ trong Docker network)
   |                       |
   |                 phpMyAdmin (chỉ dùng quản trị lab)
   |
   +-- audio/ảnh bìa trong volume uploads

Prometheus <-- exporters + Blackbox (kiểm tra HTTP/DB/container)
Grafana     <-- Prometheus (metrics) và Loki (logs)
Alloy       --> Loki (thu log container)
```

Máy người dùng chỉ cần truy cập website qua Nginx. MySQL và các exporter không nên mở thẳng ra Internet. Dashboard quản trị nên chỉ mở trong máy lab, VPN hoặc qua SSH tunnel.

## 3. Tài liệu bạn gửi: phần nào dùng được?

| Tài liệu | Mức độ phù hợp | Lấy phần nào / cần sửa gì |
|---|---|---|
| `Bai_5_Project_Thuc_Hanh_Tung_Buoc.txt` | Dùng trực tiếp làm khung | Docker network, Nginx Proxy Manager, reverse proxy, domain/hosts, HTTPS, kiểm tra log/port, Git và troubleshooting. Thay `static-site`/`wordpress` bằng ứng dụng podcast. |
| `Huong_dan_tung_buoc_Bai_6.txt` | Dùng có chọn lọc | Lấy MySQL + phpMyAdmin, phân quyền tối thiểu, backup/restore, SQL injection và prepared statements. Bỏ PostgreSQL/pgAdmin/RLS/pgcrypto vì đề tài chỉ yêu cầu MySQL. |
| `Project_Bai_7_WordPress_Monitoring_Lab.zip` | Dùng làm mẫu monitoring | Prometheus scrape, Grafana provisioning/dashboard, Blackbox Exporter, Node Exporter, cAdvisor, MySQL Exporter. Đổi target WordPress thành website podcast. Compose gốc có mật khẩu mẫu yếu và website WordPress nên không chạy nguyên trạng. Các cấu hình được chọn đã đặt trong `references/bai7_monitoring_templates/`; Compose gốc không được đưa vào bộ chạy. |
| `Bai_8_Project_Thuc_Hanh_Quan_Ly_Log_Canh_Bao.txt` | Dùng phần logs/alerts | Loki, Grafana, LogQL, cảnh báo và quy trình mô phỏng sự cố. Tài liệu dùng Promtail; với môi trường hiện tại hãy dùng Grafana Alloy hoặc chỉ dùng Promtail nếu giảng viên bắt buộc đúng nội dung bài cũ. Promtail đã hết vòng đời ngày 02/03/2026 theo [tài liệu Grafana](https://grafana.com/docs/loki/latest/send-data/promtail/). |
| `Bai_9_Thuc_Hanh_Bao_Mat_Linux.txt` | Dùng phần hardening | SSH key, không đăng nhập root, Fail2Ban, firewall, cập nhật hệ thống và bằng chứng log. Chỉ làm trên VM/lab, tạo snapshot, giữ phiên SSH dự phòng; không dán nguyên rule iptables vì port và giao diện mạng của đề tài khác. |
| Ảnh đề tài 37 | Yêu cầu/rubric | Dùng để đối chiếu sản phẩm cần nộp: website podcast, MySQL/phpMyAdmin, GitHub, Nginx, Prometheus/Grafana, Loki/LogQL và hardening. |

Các bản tài liệu gốc đã sao chép vào `references/source_materials/`. Bảng chi tiết cấu hình bài 7 ở [references/README.md](references/README.md).

## 4. Chuẩn bị môi trường và thư mục Git

Nếu bạn chạy Ubuntu Server trong VirtualBox và điều khiển từ PowerShell SSH, làm theo [hướng dẫn Bridge, SSH, chép file và chạy Docker từng bước](HUONG_DAN_UBUNTU_VIRTUALBOX.md) trước. Các file nền đã được tạo sẵn trong bộ dự án; chép cả thư mục sang Ubuntu bằng `scp` sẽ tạo đúng cấu trúc, bạn không cần gõ lại toàn bộ mã nguồn. File riêng bạn cần tạo từ ví dụ là `.env`.

Nên làm trên Ubuntu VM hoặc Docker Desktop dùng Linux containers. Cần Docker Engine/Desktop, Docker Compose v2, Git và trình duyệt. Nếu chạy trên VM, ghi lại IP VM và dùng địa chỉ đó thay cho `localhost` khi truy cập từ máy cá nhân.

Kiểm tra trước khi bắt đầu:

```bash
docker --version
docker compose version
git --version
```

Tạo repository GitHub riêng cho đề tài. Bộ khởi đầu trong thư mục này đã có Compose cơ bản, Nginx, Dockerfile PHP, schema và hai trang đọc dữ liệu. Bổ sung dần các phần còn lại theo cấu trúc:

```text
podcast-media-library/
├── docker-compose.yml        # đã có: nginx, app, db, phpMyAdmin
├── .env.example
├── .gitignore
├── app/                     # PHP: Dockerfile, trang công khai và xử lý form
│   ├── index.php            # đã có: danh sách/lọc/tìm kiếm
│   ├── episode.php          # đã có: trang chi tiết và nghe
│   ├── health.php           # đã có: endpoint HTTP health
│   └── admin/               # bạn bổ sung: đăng nhập và CRUD
├── uploads/                 # volume audio/ảnh; không commit file tải lên
├── database/schema.sql
├── nginx/default.conf
├── monitoring/prometheus.yml
├── monitoring/alerts.yml
├── monitoring/alloy.alloy
├── loki/loki-config.yml
└── docs/                     # ảnh minh chứng, báo cáo, sơ đồ
```

Trong `.gitignore`, ít nhất bỏ qua `.env`, `uploads/*` (giữ `.gitkeep` nếu muốn), `backups/`, log và dữ liệu Grafana/Prometheus. Chỉ đưa `.env.example` với giá trị giả như `CHANGE_ME`; không đưa mật khẩu thật lên GitHub.

## 5. Giai đoạn 1 — Cơ sở dữ liệu

Mở `database/schema.sql` và nạp schema vào MySQL. Tệp tạo các bảng `admin_users`, `categories`, `episodes`, `play_events`:

- `categories`: danh mục podcast/audio.
- `episodes`: thông tin tập, đường dẫn file audio/ảnh, thời lượng, trạng thái bản nháp/xuất bản.
- `admin_users`: tài khoản quản trị; cột mật khẩu chỉ lưu kết quả `password_hash()`.
- `play_events`: ghi nhận một lượt bắt đầu nghe để thống kê số lượt phát; không lưu IP người nghe trong bản cơ bản.

Mở phpMyAdmin từ môi trường lab, kiểm tra các bảng và thêm 2–3 danh mục thử. Giao diện web cần kết nối tới hostname dịch vụ MySQL trong Docker, ví dụ `db:3306`, chứ không dùng `localhost` từ bên trong container ứng dụng.

Để có tập thử đầu tiên trước khi hoàn thiện trang admin, đặt một file audio bạn có quyền sử dụng vào thư mục `uploads/` trên máy đang chạy Docker. Trong phpMyAdmin, mở database `podcast_library` → tab SQL rồi chạy một lần:

```sql
INSERT INTO categories (name, slug, description)
VALUES ('Công nghệ', 'cong-nghe', 'Podcast về công nghệ');

INSERT INTO episodes
    (category_id, title, slug, description, audio_path, status, published_at)
SELECT id, 'Tập demo', 'tap-demo', 'Tập dùng để kiểm tra trình phát.',
       '/uploads/demo.mp3', 'published', NOW()
FROM categories
WHERE slug = 'cong-nghe';
```

Đổi `demo.mp3` thành đúng tên file bạn đã đặt trong `uploads/`. Trang chủ sẽ hiện tập và trình phát.

**Bằng chứng:** ảnh phpMyAdmin có tên database và các bảng; một truy vấn `SELECT` trả về danh mục/tập.

## 6. Giai đoạn 2 — Làm website theo thứ tự dễ kiểm tra

### 6.1 Trang người nghe

1. `index.php`: bộ khởi đầu đã hiển thị các tập `published`, có lọc theo danh mục và tìm kiếm.
2. `episode.php?id=...`: bộ khởi đầu đã có trang chi tiết và player.
3. Sau đó tách trang danh mục riêng nếu muốn URL dạng `/category.php?slug=...`.
4. Player HTML5 dùng `<audio controls preload="metadata">` với URL audio của tập.

### 6.2 Trang quản trị

1. Tạo tài khoản admin một lần bằng script/seed; dùng `password_hash()` để lưu hash.
2. Làm form đăng nhập, đăng xuất và session.
3. Thêm chức năng thêm/sửa/xóa danh mục.
4. Thêm chức năng tạo/sửa tập, chọn danh mục, đặt bản nháp/xuất bản.
5. Validate file upload phía server: giới hạn dung lượng, kiểm tra MIME/đuôi cho phép, đổi tên file ngẫu nhiên, không cho thực thi file trong `uploads/`.
6. Khi người nghe nhấn Play, ghi một sự kiện phát theo quy ước đã nêu; tránh ghi lặp mỗi giây.

Mọi truy vấn có dữ liệu từ form/URL phải dùng PDO prepared statements; không ghép chuỗi trực tiếp vào SQL. Tài liệu PHP mô tả prepared statements và cách truyền tham số trong [PDO Prepared Statements](https://www.php.net/manual/en/pdo.prepared-statements.php). Với form thay đổi dữ liệu, thêm token CSRF; escape dữ liệu khi in ra HTML để hạn chế XSS.

**Bằng chứng:** ảnh trang chủ, trang danh mục, trang chi tiết đang phát audio, trang admin quản lý tập, và dữ liệu sau khi sửa trong phpMyAdmin.

## 7. Giai đoạn 3 — Docker Compose và Nginx reverse proxy

Tách dịch vụ thành các container: `nginx` (cổng vào), `app` (PHP/Apache hoặc PHP-FPM), `db` (MySQL), `phpmyadmin`, `prometheus`, `grafana`, `loki`, `alloy`, exporters và `blackbox-exporter`.

Nguyên tắc triển khai:

- Nginx nhận request từ máy người dùng rồi proxy tới `app` bằng tên dịch vụ trong Docker network.
- `app` và `db` nằm chung network ứng dụng; không publish port 3306 ra host.
- phpMyAdmin chỉ dùng trong lab. Nếu publish cổng để tiện kiểm tra, giới hạn ở localhost hoặc firewall; không để mở công khai.
- MySQL dùng named volume để giữ dữ liệu qua lần restart.
- Cấu hình bí mật lấy từ `.env`; `.env` không được commit.
- Thêm `client_max_body_size` ở Nginx để nhận audio phù hợp giới hạn upload của PHP (`upload_max_filesize`, `post_max_size`, `max_execution_time`).

Trong bộ khởi đầu, `docker-compose.yml` dựng Nginx, PHP/Apache, MySQL và phpMyAdmin. `nginx/default.conf` chuyển request tới service `app`; `app/Dockerfile` bật `pdo_mysql`; `database/schema.sql` được nạp khi volume database được tạo lần đầu. Tệp `.env.example` chứa các tên biến cần đổi. Sao chép nó thành `.env` rồi đổi mọi giá trị mẫu trước khi chạy. Trên Linux/macOS dùng `cp .env.example .env`; trên PowerShell dùng `Copy-Item .env.example .env`.

Tại thư mục có `docker-compose.yml`, chạy các lệnh dưới đây trong Ubuntu. Trên Ubuntu, thêm `sudo` trước lệnh Docker nếu user chưa được cấp quyền Docker:

```bash
sudo docker compose config -q
sudo docker compose up -d --build
sudo docker compose ps
sudo docker compose logs --tail=100 nginx app db
```

Mở website qua Nginx tại `http://IP_VM:8080` (ví dụ bridge trong ảnh của bạn là `http://192.168.1.19:8080`); phpMyAdmin được bind vào localhost trong VM ở cổng 8081 nên dùng SSH tunnel theo hướng dẫn VirtualBox. Để dùng domain thật/HTTPS, lấy quy trình Nginx Proxy Manager hoặc SSL từ bài 5; domain giả trong file hosts không cấp được chứng chỉ Let's Encrypt.

**Bằng chứng:** ảnh `docker compose ps`, cấu hình Proxy Host/Nginx, website được mở qua cổng reverse proxy, và `docker compose logs`.

## 8. Giai đoạn 4 — Prometheus và Grafana

Dùng lại cấu trúc từ bài 7, nhưng sửa các target và nhãn cho dịch vụ mới:

- **Blackbox Exporter:** kiểm tra URL website/health endpoint để biết người dùng có mở được site hay không.
- **cAdvisor:** CPU/RAM/network của từng container.
- **Node Exporter:** tài nguyên Linux VM.
- **MySQL Exporter:** tình trạng MySQL; cấp riêng tài khoản chỉ đọc metrics, không dùng tài khoản root.
- **Prometheus:** scrape exporters theo tên service trong Compose.
- **Grafana:** datasource Prometheus và dashboard.

Không cấu hình Prometheus scrape `http://podcast/metrics` trừ khi bạn thật sự viết endpoint Prometheus cho PHP. Website bình thường không tự có endpoint này; Blackbox Exporter đo HTTP là lựa chọn phù hợp cho bản đầu.

Các file dashboard/Blackbox/Prometheus mẫu bài 7 nằm trong `references/bai7_monitoring_templates/`. Trước khi dùng, đổi target `wordpress` sang tên service của Nginx/app phù hợp; dashboard có thể cần chỉnh query/label cho tên container mới.

Mục tiêu kiểm tra: Prometheus **Status → Targets** cho các target chính ở trạng thái `UP`; Grafana có dashboard thấy HTTP probe, CPU/RAM container và MySQL.

## 9. Giai đoạn 5 — Loki, Alloy và truy vấn LogQL

Đưa log container vào Loki và thêm Loki làm datasource trong Grafana. Bài 8 là tài liệu nền cho Loki, Grafana và LogQL. Tuy nhiên Promtail đã hết vòng đời ngày 02/03/2026; Grafana hướng dẫn chuyển sang Alloy. Tài liệu chính thức có ví dụ Alloy đọc log Docker qua Docker socket và gửi đến Loki: [Monitor Docker containers with Alloy](https://grafana.com/docs/alloy/latest/monitor/monitor-docker-containers/).

Tạo log có thể tìm kiếm: log HTTP có status, lỗi kết nối DB, đăng nhập admin thất bại, tạo/sửa tập. Không ghi mật khẩu, token, nội dung riêng tư hoặc dữ liệu nhạy cảm vào log.

Ví dụ LogQL để bắt đầu (tên label thực tế phụ thuộc Alloy và cấu hình của bạn):

```logql
{service_name=~".*(app|nginx).*"}
{service_name=~".*app.*"} |= "ERROR"
{service_name=~".*nginx.*"} |~ " 4[0-9][0-9] | 5[0-9][0-9] "
```

Mở Grafana → Explore → chọn Loki → chạy truy vấn. Nếu không có kết quả, kiểm tra Alloy, Loki readiness, label thực tế trong Explore và logs của các container.

## 10. Giai đoạn 6 — Cảnh báo, an toàn và hardening

Tối thiểu tạo hai cảnh báo có thể demo trong lab:

1. Website HTTP probe thất bại liên tục trong một khoảng ngắn.
2. Một target exporter/service bị mất (`up == 0`).

Nếu dùng Alertmanager, chứng minh cảnh báo xuất hiện trong Prometheus Alerts/Alertmanager; chỉ cấu hình email/webhook khi có thông tin và được phép dùng.

Checklist bảo mật cho báo cáo:

- Không dùng tài khoản MySQL root từ ứng dụng; tạo app user chỉ có quyền trên database đề tài.
- Mật khẩu admin lưu bằng hash; mật khẩu DB đặt trong `.env` và không commit.
- Prepared statements, CSRF token, escape HTML, validate upload.
- MySQL và exporters chỉ nằm trong mạng nội bộ Docker.
- Chỉ công khai 80/443 khi triển khai; bảo vệ Grafana, Prometheus, phpMyAdmin.
- Cập nhật hệ điều hành, SSH key, cấm root SSH, dùng firewall/Fail2Ban theo bài 9.
- Trên VM từ xa, mở rule SSH/HTTP/HTTPS phù hợp trước khi đặt chính sách DROP; giữ một SSH session khác để kiểm tra và có snapshot/console dự phòng.

## 11. GitHub, kiểm thử chức năng và minh chứng

Commit theo từng mốc nhỏ, ví dụ `init schema`, `add public listing`, `add admin upload`, `add monitoring`. README GitHub nên có mục tiêu, sơ đồ, cách chạy, biến môi trường cần khai báo, URL dịch vụ lab, ảnh kết quả và cách dừng stack.

Checklist nghiệm thu chức năng:

- [ ] Người nghe xem được danh sách và lọc theo danh mục.
- [ ] Trang chi tiết phát được audio.
- [ ] Admin đăng nhập được và quản lý danh mục/tập.
- [ ] Upload audio/ảnh bị từ chối khi sai loại hoặc vượt giới hạn.
- [ ] Dữ liệu vẫn còn sau khi container restart.
- [ ] Website chỉ truy cập qua Nginx reverse proxy.
- [ ] Prometheus target UP; Grafana có metrics.
- [ ] Loki nhận log và LogQL tìm ra một lỗi/request.
- [ ] Cảnh báo demo chuyển sang trạng thái firing khi tạo sự cố có kiểm soát.
- [ ] `.env` và uploads không xuất hiện trên GitHub.

Ảnh/video báo cáo nên gồm: sơ đồ, `docker compose ps`, trang chủ, danh mục, audio đang phát, trang admin, phpMyAdmin schema, Grafana dashboard, Prometheus targets/alerts, truy vấn LogQL, cấu hình Nginx, kết quả hardening và GitHub repository.

## 12. Khắc phục lỗi thường gặp

| Hiện tượng | Kiểm tra trước |
|---|---|
| 502 Bad Gateway | app có chạy không; Nginx dùng đúng tên service/port; hai container có chung network không. Tham khảo mục 502 trong bài 5. |
| PHP không kết nối DB | hostname trong DSN phải là `db`; kiểm tra `.env`, log MySQL và app; đợi healthcheck DB trước khi app xử lý request. |
| Sửa `schema.sql` nhưng database không đổi | script init thường chỉ chạy với volume database mới. Dùng backup trước; không xóa volume nếu cần giữ dữ liệu. Tạo migration mới cho schema đã dùng. |
| Tải audio lỗi | đồng bộ giới hạn Nginx, `upload_max_filesize`, `post_max_size`, quyền ghi volume và dung lượng đĩa. |
| Prometheus target DOWN | target phải là tên service và cổng nội bộ Docker; kiểm tra exporter logs và network monitoring. |
| Loki không thấy logs | kiểm tra Alloy, quyền đọc Docker socket, URL push `http://loki:3100/loki/api/v1/push`, labels thực tế và Grafana datasource. |
| Port bị chiếm | đổi host port, giữ port container nội bộ như cũ; ghi rõ thay đổi trong README. |

Khi dừng lab trên Ubuntu, dùng `sudo docker compose down`. Lệnh `sudo docker compose down -v` xóa volume database/Grafana và có thể làm mất dữ liệu; chỉ dùng khi muốn xóa toàn bộ dữ liệu lab.

## 13. Thứ tự làm trong 5 buổi

1. **Buổi 1:** chốt schema, dựng MySQL/phpMyAdmin, nạp dữ liệu mẫu.
2. **Buổi 2:** làm trang nghe công khai và tìm kiếm/lọc.
3. **Buổi 3:** làm admin, upload, phân quyền, bảo mật truy vấn.
4. **Buổi 4:** Docker Compose, Nginx reverse proxy, domain/HTTPS nếu có.
5. **Buổi 5:** Prometheus/Grafana, Loki/Alloy/LogQL, alerts, hardening, chụp minh chứng và hoàn thiện báo cáo.

Ưu tiên hoàn thiện site chạy được trước; sau đó thêm monitoring và hardening. Như vậy khi lỗi, bạn dễ phân biệt lỗi ứng dụng với lỗi hạ tầng.
