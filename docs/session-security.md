# Keamanan & Timeout Sesi

Catatan operasional untuk pengelolaan sesi login IPTOOLS Lite.

## Cara kerja

Ada dua batas waktu, keduanya ditegakkan di server oleh
`app/Http/Middleware/EnforceSessionTimeout.php`:

| Batas | Variabel `.env` | Default | Dihitung dari |
| --- | --- | --- | --- |
| Idle timeout | `SESSION_IDLE_TIMEOUT` | 30 menit | Aktivitas nyata pengguna |
| Absolute timeout | `SESSION_ABSOLUTE_TIMEOUT` | 720 menit (12 jam) | Waktu login |
| Peringatan | `SESSION_WARNING_LEAD_TIME` | 120 detik | Sebelum sesi berakhir |

Isi `0` untuk mematikan salah satu pemeriksaan.

`SESSION_LIFETIME` (masa berlaku cookie) dibiarkan 120 menit sebagai lapis
cadangan. Nilainya harus lebih besar dari `SESSION_IDLE_TIMEOUT` supaya idle
timeout yang berlaku adalah yang ditegakkan middleware.

### Aktivitas nyata vs polling

Sidebar melakukan polling badge notifikasi tiap 30 detik. Request itu dikirim
dengan header `X-Silent-Poll: 1` dan **tidak** memperpanjang sesi. Tanpa
penandaan ini, idle timeout tidak akan pernah tercapai selama tab dibiarkan
terbuka.

Aktivitas nyata (klik, ketik, gerak pointer, scroll, kembali ke tab) dilacak di
`resources/js/Layouts/AppLayout.vue` dan diteruskan ke server lewat
`GET /api/session/heartbeat`, maksimal sekali per 5 menit. Pengguna yang
mengisi form panjang tanpa mengirim request apa pun tetap dianggap aktif.

Saat pengguna menganggur lebih dari 5 menit, polling badge dihentikan dan
dilanjutkan lagi begitu ada aktivitas. Ini menurunkan beban query ke Oracle
pada jam sepi.

### Pengalaman pengguna

- Modal peringatan muncul 2 menit sebelum sesi berakhir, dengan hitungan mundur.
- Tombol **Tetap masuk** memperpanjang sesi. Gerakan mouse saat modal terbuka
  sengaja tidak memperpanjang sesi, supaya komputer yang ditinggal tidak
  memperpanjang sesinya sendiri.
- Untuk absolute timeout tombol perpanjang tidak ditampilkan, karena sesi
  memang harus dimulai ulang.
- Setelah keluar, pengguna diarahkan ke `/login?expired=idle` atau
  `/login?expired=absolute` dan halaman login menampilkan alasannya.

## Rate limit login

`POST /api/auth/login` dibatasi `throttle:login`
(`app/Providers/RouteServiceProvider.php`):

- 5 percobaan per menit per kombinasi username + IP
- 60 percobaan per menit per IP

Batas per IP dibuat longgar karena pengguna satu kantor berbagi IP (NAT) dan
bisa login hampir bersamaan di awal shift. Naikkan bila jumlah pengguna
bertambah. Login diteruskan ke LDAP, jadi brute force juga berisiko mengunci
akun domain.

Bila aplikasi berada di belakang reverse proxy, isi `$proxies` di
`app/Http/Middleware/TrustProxies.php`. Tanpa itu `$request->ip()` akan
mengembalikan IP proxy untuk semua pengguna dan batas per IP jadi salah sasaran.

## Checklist sebelum naik produksi

Nilai di bawah ini masih berisi konfigurasi pengembangan di `.env`:

- [ ] `APP_ENV=production`
- [ ] `APP_DEBUG=false` — saat ini `true`, membocorkan jejak error dan konfigurasi
- [ ] `APP_URL` diisi URL produksi (saat ini `http://localhost`)
- [ ] Aplikasi dilayani lewat HTTPS
- [ ] `SESSION_SECURE_COOKIE=true` — hanya berlaku bila sudah HTTPS
- [ ] `SESSION_DOMAIN` diisi domain produksi
- [ ] `php artisan config:cache` dijalankan setelah mengubah `.env`

Tanpa HTTPS, cookie sesi dapat disadap di jaringan dan seluruh pengaturan
timeout di atas kehilangan sebagian besar manfaatnya.

## Bila nanti berjalan di lebih dari satu server

`SESSION_DRIVER=file` hanya aman untuk satu server. Di belakang load balancer
atau dengan beberapa worker, sesi akan hilang-hilangan karena tiap server punya
direktori `storage/framework/sessions` sendiri. Pindah ke `database` (tabel
`sessions`) atau `redis` (sudah dikonfigurasi di `.env`). Driver `database` juga
prasyarat bila nanti dibutuhkan fitur daftar sesi aktif atau batas satu sesi per
pengguna.

Driver `file` juga memakai lock saat menulis, sehingga request paralel dari
pengguna yang sama saling menunggu. Redis menghilangkan kontensi ini.
