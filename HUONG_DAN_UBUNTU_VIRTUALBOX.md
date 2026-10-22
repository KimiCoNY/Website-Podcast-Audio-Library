# Chạy đề tài 37 trên Ubuntu Server VirtualBox, điều khiển bằng PowerShell SSH

Hướng dẫn này dành cho máy Windows chạy VirtualBox và Ubuntu Server. **Ảnh bạn gửi đang chọn Bridged Adapter.** Ubuntu báo IP `192.168.1.19`; các địa chỉ `172.x.x.x` là network Docker, không dùng để SSH/SCP. Với bridge, PowerShell kết nối thẳng tới IP LAN của VM nên không cần port forwarding.

Bạn xác nhận Docker Compose đã cài, vì vậy **bỏ qua phần C** và bắt đầu từ phần D (chuyển bộ dự án). File Doc1 của bạn bạn là mẫu quản lý kho: lấy cách chia bước và tạo file làm tham khảo, nhưng không chép database/bảng/số liệu kho sang đề tài podcast. Ảnh cuối của Doc1 còn có lỗi cú pháp YAML trong Compose, nên kiểm tra bằng `docker compose config -q` trước khi chạy.

Trong ảnh, user Ubuntu là `kimicony`. Nếu IP đổi sau khi reboot, chạy `hostname -I` trong Ubuntu và dùng địa chỉ LAN `192.168...` mới. Không dùng các IP `172...` của Docker.

## A. Kiểm tra Bridge và lấy IP của máy ảo

Ảnh của bạn đã chọn **Attached to: Bridged Adapter** và card Wi-Fi Intel. Giữ cấu hình đó, đồng thời bảo đảm **Cable Connected** đang bật. Trong console Ubuntu hoặc SSH đang mở, chạy:

```bash
whoami
hostname -I
```

Theo ảnh, user là `kimicony`, IP bridge là `192.168.1.19`. Nếu IP đổi, dùng địa chỉ `192.168...` mới; bỏ qua các địa chỉ `172...`.

## B. Đăng nhập SSH từ PowerShell

Mở PowerShell trên Windows và xem SSH/SCP có sẵn không:

```powershell
Get-Command ssh, scp
```

Kiểm tra Windows nhìn thấy cổng SSH của VM:

```powershell
Test-NetConnection 192.168.1.19 -Port 22
```

Kết quả `TcpTestSucceeded : True` nghĩa là SSH port có thể kết nối. Nếu IP đổi thì thay bằng IP LAN hiện tại.

Kết nối tới Ubuntu:

```powershell
ssh kimicony@192.168.1.19
```

Lần đầu có thể hỏi xác nhận host key: nhập `yes`, sau đó nhập mật khẩu Linux. Khi vào Ubuntu, chạy:

```bash
whoami
hostname
sudo systemctl status ssh --no-pager
```

Nếu SSH chưa cài/chưa chạy, mở cửa sổ console của VM trong VirtualBox và chạy tại Ubuntu:

```bash
sudo apt update
sudo apt install -y openssh-server
sudo systemctl enable --now ssh
sudo systemctl status ssh --no-pager
```

Ubuntu hướng dẫn cài `openssh-server` bằng apt và kiểm tra cấu hình trước khi khởi động lại SSH tại [OpenSSH Server documentation](https://ubuntu.com/server/docs/openssh-server/). Quay về PowerShell và thử lệnh `ssh` ở trên lần nữa.

### (Nên làm) Đăng nhập bằng SSH key từ PowerShell

Trong PowerShell Windows, tạo key nếu chưa có:

```powershell
ssh-keygen -t ed25519 -C "podcast-vbox"
```

Nhấn Enter để lưu ở đường dẫn mặc định; đặt passphrase nếu muốn bảo vệ private key. Chép **public key** lên Ubuntu:

```powershell
Get-Content "$env:USERPROFILE\.ssh\id_ed25519.pub" | ssh kimicony@192.168.1.19 "umask 077; mkdir -p ~/.ssh; cat >> ~/.ssh/authorized_keys"
```

Thử ở một cửa sổ PowerShell mới:

```powershell
ssh -i "$env:USERPROFILE\.ssh\id_ed25519" kimicony@192.168.1.19
```

Giữ mật khẩu SSH hoạt động cho tới khi key login thành công. Không gửi file `id_ed25519` cho ai; chỉ public key `.pub` được chép lên server.

## C. Cài Docker Engine và Docker Compose trên Ubuntu (bạn có thể bỏ qua)

Nếu các lệnh `docker --version` và `docker compose version` đã chạy được, bỏ qua phần cài đặt này. Nếu chưa, chạy từng khối lệnh sau **trong SSH Ubuntu**, không chạy trong PowerShell. Đây là cách cài từ kho apt chính thức của Docker; danh sách phiên bản Ubuntu được hỗ trợ có thể thay đổi nên đối chiếu [Docker Engine on Ubuntu](https://docs.docker.com/engine/install/ubuntu/).

```bash
sudo apt update
sudo apt install -y ca-certificates curl
sudo install -m 0755 -d /etc/apt/keyrings
sudo curl -fsSL https://download.docker.com/linux/ubuntu/gpg -o /etc/apt/keyrings/docker.asc
sudo chmod a+r /etc/apt/keyrings/docker.asc
```

Thêm kho Docker:

```bash
sudo tee /etc/apt/sources.list.d/docker.sources >/dev/null <<EOF
Types: deb
URIs: https://download.docker.com/linux/ubuntu
Suites: $(. /etc/os-release && echo "${UBUNTU_CODENAME:-$VERSION_CODENAME}")
Components: stable
Architectures: $(dpkg --print-architecture)
Signed-By: /etc/apt/keyrings/docker.asc
EOF
```

Cài Docker Engine, Buildx và plugin Compose:

```bash
sudo apt update
sudo apt install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
sudo systemctl enable --now docker
sudo docker run hello-world
sudo docker compose version
```

Trong hướng dẫn bên dưới luôn thêm `sudo` trước lệnh Docker. Không cần cấp quyền quản trị Docker cho user Linux để làm lab.

## D. Chép bộ dự án từ Windows sang Ubuntu

Mở **một cửa sổ PowerShell mới** trên Windows (không chạy lệnh này trong SSH) và chép thư mục bộ đề tài:

```powershell
scp -r "C:\Users\Admin\Documents\podcast\Bai_37_Podcast_Media_Library" kimicony@192.168.1.19:~/
```

Nếu IP VM đã đổi thì thay `192.168.1.19`. SCP mặc định dùng SSH port 22. Nhập mật khẩu Ubuntu nếu được hỏi. Lệnh này chép cả Compose, mã PHP, schema, Nginx và tài liệu tham khảo vào thư mục Home của `kimicony`. Sau khi chép xong, quay lại cửa sổ SSH Ubuntu:

```bash
cd ~/Bai_37_Podcast_Media_Library
pwd
ls -la
find . -maxdepth 2 -type f | sort
```

Bạn sẽ thấy `.env.example`, `docker-compose.yml`, `app/`, `database/`, `nginx/`, `uploads/`, `references/`. Dấu chấm đầu tên file khiến `.env.example` chỉ hiện khi dùng `ls -a` hoặc `ls -la`.

Muốn sửa một file trực tiếp trong Ubuntu:

```bash
nano docker-compose.yml
```

Trong nano: sửa nội dung → **Ctrl+O** → Enter để lưu → **Ctrl+X** để thoát. Các file nền đã có trong bộ dự án; không cần gõ lại toàn bộ nếu bạn đã chép thư mục bằng `scp`.

## E. Tạo file cấu hình bí mật `.env`

Trong SSH Ubuntu, từ thư mục dự án:

```bash
cp .env.example .env
openssl rand -hex 24
```

Lệnh `openssl` in ra chuỗi ngẫu nhiên. Tạo hai chuỗi riêng (chạy hai lần) rồi mở file:

```bash
nano .env
```

Thay nội dung thành dạng sau, thay hai giá trị `...` bằng chuỗi riêng vừa tạo:

```dotenv
MYSQL_ROOT_PASSWORD=CHUOI_NGAU_NHIEN_CHO_ROOT
MYSQL_DATABASE=podcast_library
MYSQL_USER=podcast_app
MYSQL_PASSWORD=CHUOI_NGAU_NHIEN_CHO_APP
```

Lưu file bằng Ctrl+O, Enter, Ctrl+X rồi giới hạn quyền đọc:

```bash
chmod 600 .env
```

`.env` đã nằm trong `.gitignore`; không chụp ảnh giá trị mật khẩu và không gửi file này lên GitHub/chat.

## F. Xem các file sẽ chạy và ý nghĩa của chúng

| Đường dẫn trong Ubuntu | Đã có trong bộ dự án? | Vai trò |
|---|---:|---|
| `docker-compose.yml` | Có | Dựng MySQL, PHP/Apache, Nginx và phpMyAdmin. |
| `.env` | Bạn vừa tạo | Mật khẩu MySQL và tên database/user. |
| `database/schema.sql` | Có | Tạo categories, episodes, admin_users và play_events ở lần khởi tạo database đầu. |
| `app/Dockerfile` | Có | Tạo image PHP và bật kết nối PDO MySQL. |
| `app/db.php` | Có | Đọc biến môi trường và mở kết nối MySQL. |
| `app/index.php` | Có | Danh sách, tìm kiếm, lọc tập đã xuất bản. |
| `app/episode.php` | Có | Trang chi tiết và player audio. |
| `app/health.php` | Có | Health endpoint kiểm tra cả kết nối database. |
| `app/style.css` | Có | Giao diện cơ bản. |
| `nginx/default.conf` | Có | Chuyển request từ Nginx sang ứng dụng. |
| `uploads/` | Có | Audio/ảnh bìa; nội dung bị Git bỏ qua. |

Để xem hoặc sửa từng file, ví dụ `nano app/index.php`, `nano database/schema.sql`, `nano nginx/default.conf`. Nếu sửa mã PHP, do thư mục được mount vào container, tải lại trang là thấy thay đổi. Nếu sửa `app/Dockerfile`, cần chạy lại với `--build`.

## G. Khởi động lần đầu

Trong Ubuntu SSH, xác nhận đang ở đúng thư mục:

```bash
cd ~/Bai_37_Podcast_Media_Library
sudo docker compose config -q
sudo docker compose up -d --build
sudo docker compose ps
```

Kỳ vọng các service `db`, `app`, `nginx`, `phpmyadmin` có trạng thái running; `db` có thể cần khoảng nửa phút ở lần đầu. Nếu service lỗi:

```bash
sudo docker compose logs --tail=100 db
sudo docker compose logs --tail=100 app
sudo docker compose logs --tail=100 nginx
```

Mở trình duyệt trên Windows tới:

```text
http://192.168.1.19:8080
```

Trang đầu có thể báo chưa có tập; đó là bình thường trước khi thêm dữ liệu. Nếu không vào được, thay URL bằng IP hiện tại của VM, kiểm tra VM có bật, Nginx đang chạy và máy Windows/VM còn cùng mạng LAN.

## H. Vào phpMyAdmin an toàn qua SSH tunnel

Compose bind phpMyAdmin vào `127.0.0.1` **bên trong Ubuntu**, nên không cần mở thêm VirtualBox port. Mở **cửa sổ PowerShell thứ hai** và giữ kết nối này mở:

```powershell
ssh -L 8081:127.0.0.1:8081 kimicony@192.168.1.19
```

Trong trình duyệt Windows mở `http://127.0.0.1:8081`. Đăng nhập bằng server `db`, user `podcast_app` và mật khẩu `MYSQL_PASSWORD` trong `.env`; hoặc dùng `root` và `MYSQL_ROOT_PASSWORD` để tạo dữ liệu lab. Khi đóng tunnel, nhấn Ctrl+C trong cửa sổ PowerShell thứ hai.

## I. Thêm audio demo để player phát được

Chọn một file audio bạn có quyền sử dụng, ví dụ `demo.mp3`. Từ **PowerShell thứ ba** trên Windows, chép file vào thư mục uploads của VM:

```powershell
scp "C:\duong-dan-den-audio\demo.mp3" kimicony@192.168.1.19:~/Bai_37_Podcast_Media_Library/uploads/
```

Trong phpMyAdmin, chọn database `podcast_library` → tab **SQL**, chạy lệnh mẫu sau một lần:

```sql
INSERT INTO categories (name, slug, description)
VALUES ('Công nghệ', 'cong-nghe', 'Podcast về công nghệ');

INSERT INTO episodes
    (category_id, title, slug, description, audio_path, status, published_at)
SELECT id, 'Tập demo', 'tap-demo', 'Tập dùng kiểm tra trình phát.',
       '/uploads/demo.mp3', 'published', NOW()
FROM categories
WHERE slug = 'cong-nghe';
```

Đổi tên file trong SQL nếu audio không tên `demo.mp3`. Tải lại `http://192.168.1.19:8080`; bấm Play để kiểm tra. Nếu chạy lại SQL bị báo trùng slug, không chạy insert lần nữa; kiểm tra hàng đã có trong bảng `categories` và `episodes`.

## J. Lệnh thường dùng khi làm bài

Các lệnh dưới đây chạy trong SSH Ubuntu tại thư mục dự án:

```bash
cd ~/Bai_37_Podcast_Media_Library
sudo docker compose ps
sudo docker compose logs -f app
sudo docker compose restart app
sudo docker compose up -d --build app
sudo docker compose down
```

Thoát xem log trực tiếp bằng Ctrl+C. `docker compose down` dừng và xóa container/network nhưng giữ database volume. **Không dùng `docker compose down -v` nếu chưa muốn xóa dữ liệu MySQL.**

Muốn đưa thay đổi mới từ Windows sang VM, chạy lại lệnh `scp -r ...` ở phần D. Sau đó trong Ubuntu dùng `sudo docker compose up -d --build` nếu có thay Dockerfile/Compose; với file PHP/CSS chỉ cần tải lại trang.

## K. GitHub và hardening SSH

Trong Ubuntu:

```bash
cd ~/Bai_37_Podcast_Media_Library
git init
git status --short
git add .
git status --short
git branch -M main
```

Trước khi commit, xác nhận `.env` và file audio không có trong danh sách staged. Sau đó commit. Tạo repository trống trên GitHub rồi thay `USERNAME` và `REPOSITORY` bằng tên thật:

```bash
git commit -m "Initial podcast media library"
git remote add origin https://github.com/USERNAME/REPOSITORY.git
git push -u origin main
```

Nếu GitHub đã cấp cho bạn SSH remote, dùng URL SSH thay URL HTTPS. Nếu lệnh báo `origin` đã có, kiểm tra bằng `git remote -v` rồi dùng `git remote set-url origin <URL>`.

Làm hardening ở cuối, sau khi website đã chạy và tạo được SSH key từ Windows. Giữ SSH ở port 22 để tiếp tục kết nối thẳng theo IP bridge; đừng tắt đăng nhập mật khẩu cho tới khi đã thử đăng nhập bằng key trong một cửa sổ PowerShell mới. Tạo snapshot VM trước khi sửa SSH/firewall. Docker cảnh báo các cổng container publish có thể không chịu tác động như dự kiến từ UFW; xem ghi chú firewall trong [Docker Ubuntu install guide](https://docs.docker.com/engine/install/ubuntu/). Chỉ thử iptables/UFW của bài 9 sau khi hiểu rule và có console VirtualBox để khôi phục.

## L. Khi giảng viên cần truy cập từ máy khác

Với Bridged Adapter, thiết bị khác trên cùng LAN có thể truy cập website tại `http://192.168.1.19:8080` nếu mạng cho phép. Chỉ demo trên mạng tin cậy; không cấu hình router để đưa cổng lab ra Internet. Nếu IP đổi, dùng IP mới từ `hostname -I`.
