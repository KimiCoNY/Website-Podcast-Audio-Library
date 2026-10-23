# Chạy project trên Ubuntu Server trong VirtualBox

Tài liệu này chỉ ghi các bước riêng cho VM và PowerShell. Cấu hình `.env`, chạy Compose, thêm audio và truy vấn metrics/log được hướng dẫn trong [README.md](README.md).

## 1. Kết nối VM

Đặt card mạng VirtualBox thành **Bridged Adapter** và bật **Cable Connected**. Trong Ubuntu chạy:

```bash
hostname -I
```

Dùng địa chỉ LAN dạng `192.168.x.x`; không dùng địa chỉ Docker `172.x.x.x`.

Từ PowerShell trên Windows, SSH vào Ubuntu (thay user/IP bằng thông tin VM):

```powershell
ssh kimicony@<IP_VM>
```

## 2. Chuyển project vào VM

Nếu project đã có trên Windows:

```powershell
scp -r "C:\Users\Admin\Documents\podcast\Bai_37_Podcast_Media_Library" kimicony@<IP_VM>:~/
```

Nếu project đã được clone trong Ubuntu, bỏ qua bước copy. Sau đó trong Ubuntu:

```bash
cd ~/Bai_37_Podcast_Media_Library
```

Làm theo mục **Khởi động trên Ubuntu / VirtualBox** trong README để tạo `.env` và chạy toàn bộ stack bằng Compose.

## 3. Truy cập dịch vụ

Lấy IP mới bằng `hostname -I` rồi mở từ Windows:

- Website: `http://<IP_VM>:8082`
- phpMyAdmin: `http://<IP_VM>:8081`
- Grafana: `http://<IP_VM>:3000`
- Prometheus: `http://<IP_VM>:9090/targets`

Website được publish từ cổng host `8082` vào cổng Nginx `80` trong container (`8082:80`). Không dùng URL cũ cổng `8080`.

Nếu trước đó đã chạy bộ giám sát riêng ở `~/bai8`, dừng stack đó bằng `sudo docker compose stop` trong thư mục `~/bai8` trước khi bật Compose của project; hai stack cùng dùng cổng Grafana/Prometheus. Lệnh `stop` giữ lại volume và dữ liệu cũ.

Chỉ mở các cổng demo trong mạng LAN tin cậy; không cấu hình router để đưa phpMyAdmin/Grafana/Prometheus ra Internet.
