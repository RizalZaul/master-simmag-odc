# Panduan Seminar Backend SIMMAG ODC

Dokumen ini saya susun khusus untuk membantu presentasi dan tanya jawab Seminar PKL pada sisi backend aplikasi `SIMMAG ODC`.

Fokus dokumen ini:

1. Memahami sistem backend secara utuh.
2. Menjelaskan fungsi setiap lapisan backend: `Controller`, `Model`, `Filter`, `Helper`, `Service`.
3. Memahami alur data dari login sampai pengelolaan PKL, modul, dan tugas.
4. Menyiapkan jawaban aman ketika dosen penguji bertanya.

Catatan penting:

- Dokumen ini dibuat dari hasil membaca source code backend yang aktif saat ini.
- Ada beberapa file model yang tampak sebagai sisa iterasi lama dan saat ini tidak direferensikan oleh route/controller aktif. Saya beri catatan khusus agar kamu bisa menjelaskannya dengan jujur bila ditanya.

---

## 1. Ringkasan Sistem

Secara sederhana, sistem ini adalah aplikasi manajemen PKL untuk PT Our Digital Creative yang memiliki tiga area utama:

1. Area admin
   Admin dapat login, melihat dashboard, mengelola instansi, mengelola data PKL, mengelola modul pembelajaran, membuat tugas, melihat pengumpulan tugas, mereview hasil pengumpulan, dan mengatur link form biodata PKL.

2. Area PKL
   PKL dapat login, melihat dashboard, melihat modul pembelajaran, melihat tugas yang diberikan, mengumpulkan jawaban tugas, dan memperbarui profilnya.

3. Area publik form biodata
   Calon/anggota PKL dapat mengisi biodata melalui link token khusus. Sebelum data disimpan, email diverifikasi dengan OTP. Setelah berhasil, sistem membuat akun login PKL dan mengirimkan kredensial melalui email.

Jadi, aplikasi ini bukan hanya sistem login, tetapi sistem administrasi PKL yang mencakup:

- pengelolaan akun,
- pengelolaan data PKL,
- distribusi modul,
- penugasan,
- pengumpulan hasil,
- review hasil tugas,
- dan onboarding PKL melalui form biodata publik.

---

## 2. Stack dan Gaya Arsitektur

Stack yang digunakan pada backend:

- PHP `^8.2`
- CodeIgniter `^4.7`
- Driver database `MySQLi`
- Pola arsitektur `MVC` khas CodeIgniter
- Session untuk state login dan OTP tertentu
- Cache untuk pembatasan percobaan OTP reset password
- SMTP email untuk pengiriman OTP dan informasi login
- File storage di folder `writable/uploads/...`

Gaya aplikasi ini adalah:

- mayoritas `server-rendered page`,
- tetapi banyak aksi CRUD dan validasi lanjutan dijalankan dengan `AJAX + JSON response`.

Jadi ini adalah aplikasi web hybrid:

- halaman utama dirender oleh controller ke view,
- aksi interaktif tertentu memakai endpoint JSON.

---

## 3. Gambaran Arsitektur Backend

Alur besar request backend di aplikasi ini:

```text
Browser
  -> Routes.php
  -> AuthFilter (jika route diproteksi)
  -> Controller
  -> Model / Service / Helper
  -> Database / Session / Cache / Email / File Storage
  -> Response (HTML atau JSON)
```

Komponen state yang dipakai backend:

1. Database
   Menyimpan data permanen seperti user, admin, PKL, instansi, modul, tugas, pengumpulan, item tugas, tim, dan setting aplikasi.

2. Session
   Menyimpan status login, identitas user aktif, OTP biodata publik, dan status verifikasi reset password.

3. Cache
   Dipakai terutama untuk pembatasan percobaan OTP reset password.

4. Cookie
   Dipakai oleh auth untuk menandai apakah user sebelumnya login lalu session habis, atau logout manual.

5. File storage
   Modul dan file tugas tidak diletakkan langsung di public root, tetapi disimpan di `writable/uploads/modul` dan `writable/uploads/tugas`, lalu diakses melalui controller.

Ini poin penting untuk seminar karena menunjukkan bahwa backend tidak hanya "insert dan select", tetapi mengelola beberapa lapisan state sesuai kebutuhan.

---

## 4. Teknologi dan Konfigurasi Penting

Beberapa fakta teknis yang perlu kamu hafal:

- Framework: `CodeIgniter 4`
- Autoload helper global: `tgl`
- Route auto-discovery dimatikan karena `setAutoRoute(false)`
- Filter alias `auth` sudah dibuat di `app/Config/Filters.php`
- Password di-hash otomatis melalui callback model `UserModel`
- OTP reset password memakai kombinasi `database + session + cache`
- OTP biodata publik memakai `session`, karena akun user belum tentu sudah ada

Catatan keamanan yang jujur:

- Validasi server-side sudah cukup banyak dan tersebar baik.
- Filter auth sudah ada dan bekerja per role.
- Password di-hash.
- Upload file memakai whitelist ekstensi dan batas ukuran.
- Infrastruktur CSRF dari CodeIgniter sudah tersedia dan token juga sudah banyak disisipkan ke form atau meta frontend.
- Namun filter `csrf` di `Config/Filters.php` saat ini masih dikomentari pada bagian `globals`, dan saya juga tidak menemukan route yang memasang filter `csrf` secara khusus. Artinya proteksi CSRF otomatis belum ditegakkan secara global.

Kalau ditanya keamanan, jawaban amannya:

"Pada implementasi saat ini saya sudah menerapkan validasi server-side, role-based filter, hashing password, OTP, pembatasan upload file, dan transaksi database. Support CSRF dari framework sudah saya siapkan sampai level token di form dan AJAX, tetapi enforcement filter CSRF global memang belum saya aktifkan penuh, jadi itu masih menjadi area hardening berikutnya."

---

## 5. Peta Route Backend

File pusat route ada di `app/Config/Routes.php`.

Pembagian route:

1. Auth
   - `auth/login`
   - `auth/lupa-password`
   - `auth/logout`

2. Admin
   - dashboard
   - manajemen PKL
   - data modul
   - manajemen tugas
   - profil admin

3. PKL
   - dashboard
   - modul
   - tugas
   - profil PKL

4. Publik form biodata
   - `biodata-pkl/{token}`
   - check email
   - send OTP
   - verify OTP
   - store data

Makna arsitektural route ini:

- Route admin dan PKL dipisah tegas berdasarkan role.
- Route publik form biodata dipisah dari area login karena targetnya user yang belum punya session login.
- Sebagian endpoint dipakai untuk HTML, sebagian untuk AJAX.

---

## 6. Peta Database dan Relasi Utama

Migrations menunjukkan ada 15 tabel utama backend:

1. `users`
2. `instansi`
3. `kelompok_pkl`
4. `pkl`
5. `admin`
6. `kategori_modul`
7. `modul`
8. `tim_tugas`
9. `anggota_tim_tugas`
10. `kategori_tugas`
11. `tugas`
12. `tugas_sasaran`
13. `pengumpulan_tugas`
14. `item_tugas`
15. `app_settings`

### 6.1 Makna Setiap Tabel

| Tabel | Fungsi utama |
| --- | --- |
| `users` | akun login umum, menyimpan email, username, password, role, status, OTP reset |
| `admin` | profil khusus admin, relasi 1:1 dengan `users` |
| `pkl` | profil khusus PKL, relasi 1:1 dengan `users`, dan many-to-one ke `kelompok_pkl` |
| `instansi` | master instansi asal PKL |
| `kelompok_pkl` | data kelompok/periode PKL, bisa terkait instansi atau mandiri |
| `kategori_modul` | kategori materi/modul |
| `modul` | materi pembelajaran, bisa berupa file atau link |
| `kategori_tugas` | kategori tugas dan mode pengumpulannya |
| `tugas` | definisi tugas yang dibuat admin |
| `tugas_sasaran` | penentu target tugas: individu, kelompok, atau tim |
| `pengumpulan_tugas` | baris penerima/pengumpulan untuk setiap PKL target |
| `item_tugas` | file atau link jawaban per pengumpulan |
| `tim_tugas` | master tim tugas |
| `anggota_tim_tugas` | relasi anggota PKL terhadap tim |
| `app_settings` | key-value setting seperti token biodata dan status form |

### 6.2 Relasi Paling Penting

1. `users -> admin`
   Relasi 1:1. Satu akun admin punya satu profil admin.

2. `users -> pkl`
   Relasi 1:1. Satu akun PKL punya satu profil PKL.

3. `instansi -> kelompok_pkl`
   Satu instansi bisa memiliki banyak kelompok PKL.

4. `kelompok_pkl -> pkl`
   Satu kelompok bisa memiliki banyak anggota PKL.

5. `kategori_modul -> modul`
   Satu kategori modul punya banyak modul.

6. `kategori_tugas -> tugas`
   Satu kategori tugas punya banyak tugas.

7. `tugas -> tugas_sasaran`
   Satu tugas bisa punya banyak target.

8. `tugas -> pengumpulan_tugas`
   Satu tugas menghasilkan banyak baris pengumpulan, masing-masing untuk penerima.

9. `pengumpulan_tugas -> item_tugas`
   Satu pengumpulan bisa punya banyak item jawaban.

10. `tim_tugas -> anggota_tim_tugas`
    Satu tim punya banyak anggota PKL.

### 6.3 Poin Penting yang Harus Sangat Dipahami

#### A. `tugas.id_user` adalah pembuat tugas, bukan penerima tugas

Ini penting karena sering menipu saat pertama melihat struktur tabel.

Di aplikasi ini:

- `tugas.id_user` menunjuk ke user yang membuat tugas, biasanya admin.
- penerima tugas tidak diambil dari `tugas.id_user`.
- penerima tugas disimpan di `tugas_sasaran`.
- realisasi daftar penerima per PKL disimpan di `pengumpulan_tugas`.

Jadi kalau dosen bertanya "siapa penerima tugas?" jawaban yang benar:

"Penerima tugas tidak disimpan langsung di tabel tugas, tetapi melalui tabel perantara `tugas_sasaran`, lalu sistem membuat baris `pengumpulan_tugas` untuk tiap PKL penerima agar pelacakan status pengumpulan bisa dilakukan per penerima."

#### B. `pengumpulan_tugas` dibuat saat tugas dibagikan, bukan saat PKL upload

Ini desain yang sangat penting.

Saat admin membuat tugas:

- sistem menyimpan definisi tugas,
- menyimpan target di `tugas_sasaran`,
- lalu langsung membuat baris `pengumpulan_tugas` untuk setiap PKL target.

Akibatnya:

- sistem bisa tahu siapa yang "belum mengumpulkan" karena `tgl_pengumpulan` masih `NULL`,
- dashboard PKL bisa menampilkan tugas meski belum ada file apapun,
- admin bisa memantau progres tanpa menunggu upload pertama.

Ini salah satu poin desain paling bagus untuk kamu jelaskan saat seminar.

#### C. Tugas kelompok dan tim tetap dilacak per PKL

Meskipun mode tugas bisa kelompok atau tim:

- sistem tetap membuat baris `pengumpulan_tugas` per PKL,
- tetapi tetap menyimpan `id_kelompok` atau `id_tim` agar nanti bisa digrup untuk tampilan admin.

Manfaatnya:

- pelacakan lebih detail,
- status bisa ditelusuri per penerima,
- tetapi admin tetap bisa melihatnya sebagai satu kelompok atau satu tim.

---

## 7. Alur State yang Dipakai Sistem

### 7.1 Session Login

Saat login berhasil, session menyimpan antara lain:

- `user_id`
- `username`
- `email`
- `role`
- `nama`
- `panggilan`
- `logged_in`
- `login_time`
- `id_admin` untuk admin
- `id_pkl` dan `id_kelompok` untuk PKL

Maknanya:

- `users` menyimpan data akun,
- session menyimpan identitas aktif untuk request berjalan.

### 7.2 Session Reset Password

Reset password memakai session `forgot_password` yang berisi:

- `user_id`
- `email`
- `role`
- `verified`
- `expires_at`

Jadi sistem tidak hanya percaya pada OTP di database, tetapi juga menyimpan status verifikasi sementara di session.

### 7.3 Session OTP Biodata Publik

OTP biodata publik memakai session `biodata_otp` karena user belum tentu sudah punya akun.

Isinya:

- `code`
- `email`
- `expiry`
- `verified`
- `attempt`

### 7.4 Cache untuk OTP Reset Password

Cache dipakai untuk:

- menghitung percobaan OTP reset password,
- mengunci akun admin sementara jika salah terlalu banyak,
- membatasi abuse.

### 7.5 Cookie Marker Auth

Auth memakai dua cookie marker:

- `simmag_auth_marker`
- `simmag_logout_marker`

Gunanya:

- membedakan apakah user diarahkan ke login karena session expired,
- atau karena benar-benar logout manual.

Ini poin kecil, tetapi bagus untuk menunjukkan bahwa UX backend juga dipikirkan.

---

## 8. Penjelasan Per Lapisan Backend

## 8.1 Controller

Controller adalah orchestration layer. Di project ini, controller menangani:

- menerima request,
- validasi awal,
- membaca session,
- memanggil model,
- memanggil service,
- mengelola transaksi,
- memutuskan response HTML atau JSON.

### 8.1.1 `BaseController`

File: `app/Controllers/BaseController.php`

Peran:

- pusat helper validasi umum,
- pusat normalisasi input,
- mengurangi duplikasi antar controller.

Method penting:

- `buildMissingFieldsMessage()`
- `appendMissingFieldGroup()`
- `normalizeSingleSpaces()`
- `normalizeMultilineText()`
- `validatePatternField()`
- `validateLooseTextField()`
- `validateMultilinePatternField()`
- `validateEmailAddress()`
- `validateWhatsappNumber()`
- `validateStandardPassword()`
- `validateNumberRange()`
- `validateDateOnlyValue()`
- `validateDateTimeValue()`
- `validatePklStartDate()`
- `validatePklEndDate()`
- `validateDeadlineValue()`
- `validateHttpsUrlValue()`

Poin seminar:

- Validasi tidak disalin berulang di tiap controller.
- Normalisasi seperti trim, spasi ganda, multiline text dikerjakan konsisten.
- Controller turunan fokus ke bisnis, bukan mengulang validasi dasar.

Jawaban aman:

"Saya jadikan `BaseController` sebagai tempat utility validasi dan normalisasi supaya aturan input konsisten di seluruh modul, misalnya email, nomor WA, password, tanggal, deadline, dan teks multiline."

### 8.1.2 `AuthController`

File: `app/Controllers/AuthController.php`

Tanggung jawab:

- login,
- logout,
- lupa password berbasis OTP,
- set session user,
- set cookie marker auth,
- catat log aktivitas auth.

Method utama:

- `login()`
- `processLogin()`
- `forgotPassword()`
- `sendForgotPasswordOtp()`
- `verifyForgotPasswordOtp()`
- `resetForgotPassword()`
- `logout()`

Poin teknis penting:

1. Login mendukung identifier berupa `username` atau `email`.
2. Session dibentuk berdasarkan role.
3. Untuk admin, session menyimpan `id_admin`.
4. Untuk PKL, session menyimpan `id_pkl` dan `id_kelompok`.
5. Reset password memakai OTP 6 digit.
6. OTP reset berlaku 5 menit.
7. Setelah OTP tervalidasi, jendela reset password berlaku 15 menit.
8. Jika PKL salah OTP 3 kali, akun PKL langsung dinonaktifkan.
9. Jika admin salah OTP 3 kali, akun tidak dinonaktifkan tetapi dikunci 15 menit.
10. OTP reset disimpan ke kolom `kode_otp` dan `tenggat_otp` pada tabel `users`.

Poin seminar yang bagus:

- Arsitektur reset password dibedakan antara PKL dan admin.
- Ada kombinasi DB, cache, session, dan email.
- Ada logging aktivitas login success, failed, dan logout.

Jawaban aman:

"Auth saya tidak hanya cek username dan password, tetapi juga cek status akun aktif, membentuk session sesuai role, dan untuk reset password saya pakai OTP dengan batas waktu serta pembatasan percobaan agar penyalahgunaan lebih sulit."

### 8.1.3 `DashboardAdminController`

File: `app/Controllers/DashboardAdminController.php`

Tanggung jawab:

- menampilkan dashboard admin.

Data yang ditampilkan:

- statistik PKL,
- daftar kategori modul,
- daftar tugas aktif atau overdue yang belum selesai secara pengumpulan.

Nilai arsitektural:

- controller tipis,
- logika query dipindah ke model.

### 8.1.4 `DashboardPklController`

File: `app/Controllers/DashboardPklController.php`

Tanggung jawab:

- menampilkan dashboard PKL.

Data yang ditampilkan:

- statistik tugas PKL,
- kategori modul,
- tugas yang belum selesai.

Poin penting:

- statistik menghitung semua jalur sasaran: individu, kelompok, dan tim.

### 8.1.5 `InstansiAdminController`

File: `app/Controllers/InstansiAdminController.php`

Tanggung jawab:

- halaman tab data instansi,
- tambah instansi,
- ubah instansi,
- hapus instansi,
- ambil daftar kota.

Poin teknis:

- banyak aksi dijalankan via AJAX dan return JSON.
- sebelum hapus, controller mengecek apakah instansi masih dipakai oleh kelompok PKL.
- ada validasi nama, alamat, kota, dan kategori.

Alasan desain:

- pencegahan penghapusan tidak hanya mengandalkan database constraint,
- tetapi juga dicek di level aplikasi agar pesan error lebih ramah.

### 8.1.6 `MPklAdminController`

File: `app/Controllers/MPklAdminController.php`

Ini salah satu controller terpenting.

Tanggung jawab:

- list data PKL,
- tambah PKL,
- detail PKL,
- edit PKL,
- hapus PKL,
- toggle status user PKL,
- cek email unik.

Alur tambah PKL oleh admin:

1. Admin mengisi payload multi-step.
2. Payload divalidasi sangat detail.
3. Input dinormalisasi.
4. Transaksi database dimulai.
5. Jika kategori instansi dan instansi baru, sistem insert instansi dulu.
6. Sistem insert `kelompok_pkl`.
7. Untuk setiap anggota:
   - buat password random,
   - buat username unik,
   - insert `users`,
   - insert `pkl`.
8. Setelah commit, sistem kirim email login ke setiap anggota.
9. Jika instansi dengan lebih dari 1 anggota, sistem kirim rekap ke ketua.
10. Sistem juga menyiapkan text WhatsApp untuk admin.

Poin penting:

- operasi create dilakukan dalam transaksi.
- pengiriman email dilakukan setelah transaksi selesai.
- jika email gagal, data inti tetap aman tersimpan.

Alur edit PKL:

- update data `pkl`,
- update email/password di `users`,
- tetap ada validasi format, uniqueness email, dan validasi tanggal.

Alur hapus PKL:

- jika yang dihapus adalah ketua, sistem bisa menghapus seluruh anggota satu kelompok,
- lalu membersihkan tim tugas kosong bila setelah penghapusan tim tidak punya anggota lagi,
- dilakukan dalam transaksi.

Poin seminar:

- controller ini menunjukkan orkestrasi multi-tabel yang nyata.
- ada integrasi `users`, `pkl`, `kelompok_pkl`, `instansi`, `tim_tugas`, `anggota_tim_tugas`, dan email.

Jawaban aman:

"Pada modul manajemen PKL, fokus backend saya adalah menjaga konsistensi data lintas tabel. Karena satu aksi bisa memengaruhi user, profil PKL, kelompok, dan kadang tim tugas, saya pakai transaksi supaya datanya tidak setengah jadi."

### 8.1.7 `BiodataPklController`

File: `app/Controllers/BiodataPklController.php`

Ini adalah pintu masuk publik untuk onboarding PKL.

Tanggung jawab:

- membuka form biodata publik berdasarkan token,
- cek email,
- kirim OTP,
- verifikasi OTP,
- simpan semua data PKL,
- tampilkan halaman sukses.

Desain penting:

1. Form publik tidak dibuka bebas.
   Akses harus lolos:
   - form aktif,
   - token di URL valid,
   - token sama dengan yang ada di `app_settings`.

2. OTP biodata disimpan di session, bukan di database.
   Alasannya karena user belum tentu sudah memiliki akun.

3. Ada rate limiting pengiriman OTP per email.
   - maksimal 3 pengiriman,
   - lalu lock 15 menit.

4. Verifikasi OTP maksimal 5 kali percobaan.

5. Saat store:
   - OTP harus sudah verified,
   - email OTP harus cocok dengan email ketua/anggota pertama,
   - lalu sistem membuat instansi jika perlu,
   - membuat kelompok,
   - membuat akun `users`,
   - membuat profil `pkl`,
   - kirim email login,
   - kirim rekap jika kelompok.

Kenapa ini bagus dijelaskan saat seminar:

- menunjukkan sistem punya alur onboarding mandiri,
- bukan hanya admin entry manual,
- dan keamanan form publik dipikirkan melalui token + OTP.

Jawaban aman:

"Form biodata publik saya batasi dengan dua lapis, yaitu token akses form dan OTP email. Jadi walaupun URL diketahui, data tetap tidak bisa disimpan tanpa verifikasi email."

### 8.1.8 `ProfilAdminController`

File: `app/Controllers/ProfilAdminController.php`

Tanggung jawab:

- menampilkan profil admin,
- update biodata admin,
- update password admin,
- toggle aktif/nonaktif form biodata publik,
- generate token baru biodata publik.

Poin penting:

- `app_settings` dipakai sebagai pusat setting sederhana.
- admin bisa rotate token biodata.
- admin bisa membuka atau menutup form biodata tanpa ubah kode.

### 8.1.9 `ProfilPklController`

File: `app/Controllers/ProfilPklController.php`

Tanggung jawab:

- menampilkan profil PKL,
- update biodata PKL,
- update password PKL.

Poin penting:

- PKL hanya boleh ubah field data dirinya sendiri.
- data instansi/kelompok tetap dibaca dari relasi.
- validasi jurusan hanya wajib jika PKL berasal dari instansi.

### 8.1.10 `ModulAdminController`

File: `app/Controllers/ModulAdminController.php`

Tanggung jawab:

- halaman data modul admin,
- CRUD kategori modul,
- CRUD modul,
- preview file modul,
- download file modul.

Desain modul:

- modul bisa berupa `link` atau `file`,
- file modul disimpan di `writable/uploads/modul`,
- database hanya menyimpan metadata dan path nama file.

Validasi file:

- whitelist ekstensi:
  `pdf`, `doc`, `docx`, `ppt`, `pptx`, `xls`, `xlsx`, `zip`, `rar`
- ukuran maksimal 300 MB
- URL modul harus `https://`

Poin keamanan:

- file tidak dibuka langsung lewat public URL,
- akses file harus melalui controller,
- PDF bisa preview inline,
- selain PDF biasanya diarahkan ke download.

Jawaban aman:

"Saya simpan file modul di folder writable lalu aksesnya saya kontrol lewat controller, supaya server tetap memvalidasi keberadaan file dan role user yang mengakses."

### 8.1.11 `ModulPklController`

File: `app/Controllers/ModulPklController.php`

Tanggung jawab:

- menampilkan kategori modul untuk PKL,
- menampilkan daftar modul per kategori,
- preview file,
- download file.

Catatan:

- controller ini bersifat read-only,
- semua CRUD modul hanya ada di sisi admin.

### 8.1.12 `MTugasAdminController`

File: `app/Controllers/MTugasAdminController.php`

Ini controller backend paling kompleks karena menangani dua area besar:

1. penugasan,
2. pengumpulan dan review tugas.

Tanggung jawab utama:

- halaman penugasan kategori/tugas,
- halaman pengumpulan mandiri/kelompok/tim,
- detail pengumpulan,
- review item pengumpulan,
- preview/download item pengumpulan,
- CRUD kategori tugas,
- list/detail/ubah/hapus tugas,
- buat tugas baru,
- pilih sasaran tugas,
- ambil API sasaran aktif,
- membuat tim tugas baru.

#### A. Logika kategori tugas

Kategori tugas menyimpan:

- nama kategori,
- mode pengumpulan: `individu` atau `kelompok`.

Mode ini penting karena menentukan cara pengumpulan dan cara target ditampilkan.

#### B. Logika buat tugas

Saat `storeTugas()` dipanggil:

1. payload JSON dibaca,
2. field ketentuan divalidasi,
3. target type dinormalisasi menjadi:
   - `individu`
   - `kelompok`
   - `tim_tugas`
4. target id dibersihkan dari duplikat,
5. tugas disimpan ke tabel `tugas`,
6. target disimpan ke `tugas_sasaran`,
7. penerima aktif dibangun dari:
   - PKL aktif,
   - anggota kelompok aktif,
   - anggota tim aktif,
8. untuk setiap penerima dibuat baris `pengumpulan_tugas`.

Ini sangat penting:

- target level logika disimpan di `tugas_sasaran`,
- target level operasional disimpan di `pengumpulan_tugas`.

#### C. Logika update tugas

Update tugas hanya mengubah:

- kategori,
- nama,
- deskripsi,
- target jumlah item,
- deadline.

Sasaran tugas tidak dibangun ulang di method update saat ini. Jadi update fokus ke metadata tugas.

#### D. Logika pengumpulan admin

Admin melihat pengumpulan berdasarkan 3 tab:

1. mandiri,
2. kelompok,
3. tim.

Untuk kelompok dan tim, controller:

- mengambil daftar `pengumpulan_tugas`,
- mengelompokkan beberapa `id_pengumpulan_tgs` menjadi satu baris tampilan,
- menghitung status agregat dari item-item terkait.

#### E. Review item pengumpulan

Admin bisa memberi status item:

- `diterima`
- `revisi`

Jika `revisi`, komentar wajib diisi.

Status item diturunkan menjadi badge tampilan seperti:

- menunggu review,
- perlu revisi,
- diterima.

#### F. Tim tugas

Tim tugas adalah entitas sendiri:

- `tim_tugas` menyimpan nama dan deskripsi,
- `anggota_tim_tugas` menyimpan anggota PKL.

Tim dipakai sebagai sasaran alternatif selain individu dan kelompok.

Jawaban aman:

"Modul tugas saya desain memakai beberapa tabel karena satu tugas bisa punya banyak sasaran dan setiap sasaran bisa menghasilkan banyak item jawaban. Jadi saya pisahkan antara tabel definisi tugas, tabel sasaran tugas, tabel penerima pengumpulan, dan tabel item jawaban agar struktur datanya tetap fleksibel."

### 8.1.13 `MTugasPklController`

File: `app/Controllers/MTugasPklController.php`

Tanggung jawab:

- list tugas PKL,
- detail tugas,
- upload atau update jawaban,
- download file jawaban milik sendiri.

Alur penting:

1. PKL context diambil dari session atau fallback ke database.
2. Sistem membaca `pengumpulan_tugas` milik PKL.
3. Task detail dirakit dari:
   - data tugas,
   - mode pengumpulan,
   - sumber target,
   - item-item jawaban,
   - status hasil review,
   - apakah masih boleh submit atau tidak.

Status tugas dihitung dari:

- `tgl_pengumpulan`,
- jumlah item,
- jumlah item `dikirim`,
- jumlah item `revisi`,
- jumlah item `diterima`.

Makna status:

- belum dikirim,
- menunggu review,
- perlu revisi,
- selesai.

Alur submit jawaban:

1. cek task dan hak akses PKL,
2. cek boleh submit atau tidak,
3. validasi jumlah jawaban terhadap target,
4. validasi setiap slot jawaban,
5. file disimpan ke `writable/uploads/tugas`,
6. item lama yang diganti bisa dibersihkan,
7. `item_tugas` diinsert/update,
8. `tgl_pengumpulan` diisi,
9. status item menjadi `dikirim`.

Poin bagus untuk seminar:

- PKL hanya bisa download file miliknya sendiri karena query join memeriksa `pt.id_pkl`.
- slot jawaban bisa dikunci bila item sudah `diterima`.

Jawaban aman:

"Di sisi PKL, saya tidak langsung memberi akses ke semua file, tetapi saya cek kepemilikan data melalui relasi pengumpulan tugas. Jadi PKL hanya bisa mengakses item yang memang terkait dengan `id_pkl` miliknya."

---

## 8.2 Model

Model di aplikasi ini berfungsi sebagai data access layer. Sebagian model hanya sederhana CRUD, tetapi sebagian lain memuat query join dan query agregasi yang cukup kompleks.

### 8.2.1 `UserModel`

File: `app/Models/UserModel.php`

Peran:

- model akun utama,
- cari user by username/email,
- update password,
- simpan dan hapus OTP reset password,
- update status user.

Hal penting:

- `beforeInsert` dan `beforeUpdate` memanggil `hashPassword()`,
- artinya password di-hash otomatis sebelum disimpan.

Poin seminar:

- hashing password dipusatkan di model, bukan di semua controller.

### 8.2.2 `AdminModel`

File: `app/Models/AdminModel.php`

Peran:

- profil admin,
- ambil profil singkat untuk session login,
- ambil detail untuk halaman profil,
- update biodata admin.

### 8.2.3 `PklModel`

File: `app/Models/PklModel.php`

Peran:

- profil PKL,
- profil singkat untuk session login,
- data diri lengkap PKL,
- anggota kelompok,
- recipient rows untuk penugasan,
- detail target tugas individu/kelompok.

Hal penting:

- banyak dipakai di auth, profil, tugas, dan manajemen PKL.
- model ini aktif dan sangat sentral.

### 8.2.4 `KelompokPklModel`

File: `app/Models/KelompokPklModel.php`

Peran:

- statistik PKL aktif/selesai/nonaktif,
- list kelompok/anggota,
- query target aktif untuk modul tugas,
- query agregasi pengumpulan kelompok,
- detail target kelompok.

Hal penting:

- status aktif/selesai sering dihitung dari `tgl_akhir` dan status user.
- kelompok mandiri dibedakan dari kelompok instansi.

### 8.2.5 `InstansiModel`

File: `app/Models/InstansiModel.php`

Peran:

- CRUD instansi,
- formatting data untuk view,
- mapping label kategori instansi ke value database,
- cek uniqueness nama,
- cek apakah instansi masih dipakai kelompok.

Hal penting:

- label form `Kuliah` dan `SMK Sederajat` dipetakan ke enum DB `kampus` dan `sekolah`.

### 8.2.6 `KategoriModulModel`

File: `app/Models/KategoriModulModel.php`

Peran:

- list kategori modul,
- statistik jumlah modul per kategori,
- dropdown kategori,
- data dashboard.

Hal penting:

- model ini juga menambahkan `color` dan `icon` untuk kebutuhan tampilan dashboard.

### 8.2.7 `ModulModel`

File: `app/Models/ModulModel.php`

Peran:

- data modul,
- join modul dengan kategori,
- hitung jumlah modul per kategori,
- list modul per kategori.

### 8.2.8 `KategoriTugasModel`

File: `app/Models/KategoriTugasModel.php`

Peran:

- master kategori tugas,
- menyimpan mode pengumpulan `individu` atau `kelompok`.

### 8.2.9 `TugasModel`

File: `app/Models/TugasModel.php`

Ini model tugas paling penting.

Peran:

- dashboard admin,
- dashboard PKL,
- statistik tugas PKL,
- detail tugas admin,
- list tugas.

Hal penting:

- memakai query SQL kompleks untuk menghitung status tugas lintas target.
- mempertimbangkan tiga jalur sasaran:
  - individu,
  - kelompok,
  - tim tugas.

Poin seminar:

- model ini menunjukkan bahwa query dashboard tidak hanya select biasa,
- tetapi ada agregasi dan subquery yang disesuaikan dengan desain data.

### 8.2.10 `TugasSasaranModel`

File: `app/Models/TugasSasaranModel.php`

Peran:

- menyimpan target tugas.

Struktur penting:

- `target_tipe`
- `id_pkl`
- `id_kelompok`
- `id_tim`

### 8.2.11 `PengumpulanTugasModel`

File: `app/Models/PengumpulanTugasModel.php`

Peran:

- data pengumpulan tugas,
- list pengumpulan per tugas,
- list pengumpulan per PKL,
- query pengumpulan untuk admin mode mandiri,
- mapping status item ke label.

Poin penting:

- satu row bukan berarti satu file,
- satu row berarti satu sesi pengumpulan untuk satu penerima,
- detail file/link ada di `item_tugas`.

### 8.2.12 `ItemTugasModel`

File: `app/Models/ItemTugasModel.php`

Peran:

- data item jawaban tugas,
- list item per pengumpulan,
- data item untuk admin review,
- statistik item per pengumpulan.

Hal penting:

- review sebenarnya dilakukan pada level item, bukan hanya level tugas.
- ini memberi fleksibilitas kalau satu tugas butuh beberapa jawaban.

### 8.2.13 `TimTugasModel`

File: `app/Models/TimTugasModel.php`

Peran:

- statistik tim,
- data tim untuk penugasan,
- detail pengumpulan mode tim,
- detail target tim.

### 8.2.14 `AnggotaTimTugasModel`

File: `app/Models/AnggotaTimTugasModel.php`

Peran:

- daftar anggota tim,
- recipient aktif berdasarkan tim,
- daftar nama anggota aktif pada tim.

### 8.2.15 `AppSettingsModel`

File: `app/Models/AppSettingsModel.php`

Peran:

- key-value setting backend.

Setting penting yang dipakai saat ini:

- `form_biodata_aktif`
- `biodata_token`

Kenapa penting:

- memungkinkan admin mengubah perilaku sistem tanpa ubah kode langsung.

### 8.2.16 Model yang Terlihat Legacy atau Tidak Aktif

#### `RegistrasiModel`

File: `app/Models/RegistrasiModel.php`

Catatan:

- file ini tidak terlihat direferensikan oleh controller atau route aktif saat ini.
- komentar di dalamnya menyebut `RegistrasiController`, tetapi controller tersebut tidak ada pada route aktif backend yang saya baca.

Interpretasi aman:

"Model ini tampaknya merupakan sisa iterasi lama saat alur registrasi masih memakai controller terpisah. Pada implementasi aktif sekarang, alur tersebut ditangani oleh `BiodataPklController`."

#### `PklAdminModel`

File: `app/Models/PklAdminModel.php`

Catatan:

- juga tidak terlihat direferensikan oleh controller aktif saat ini.
- ada referensi ke `KelompokPklModel::syncStatus()` yang bahkan tidak terlihat didefinisikan pada model aktif.

Interpretasi aman:

"Model ini tampaknya sisa refactor lama. Modul manajemen PKL aktif sekarang lebih banyak memakai `PklModel`, `KelompokPklModel`, `InstansiModel`, dan `UserModel` langsung dari controller."

Kalau ditanya dosen kenapa ada file tidak dipakai:

"Itu sisa iterasi pengembangan. Secara fungsi aktif, alur sekarang sudah memakai controller dan model lain yang lebih baru. Untuk perapian jangka lanjut, file legacy seperti ini bisa dibersihkan."

---

## 8.3 Filter

### `AuthFilter`

File: `app/Filters/AuthFilter.php`

Peran:

- gerbang autentikasi dan otorisasi route.

Empat kondisi utama yang ditangani:

1. belum login -> redirect atau JSON unauthorized
2. akun sudah dinonaktifkan -> force logout
3. role mismatch admin/pkl -> redirect ke dashboard yang benar
4. guest route diakses saat sudah login -> redirect ke dashboard sesuai role

Hal penting:

- filter membedakan request AJAX dan request normal,
- sehingga AJAX mendapat JSON 401/403, bukan redirect HTML.
- filter cek status user ke database secara real-time pada setiap request penting.

Nilai desain:

- jika admin menonaktifkan akun user yang sedang login, request berikutnya akan langsung diputus dan user diarahkan keluar.

Jawaban aman:

"Saya buat filter auth satu pintu supaya kontrol akses tidak tercecer di banyak controller. Selain cek session, filter juga cek status akun ke database secara real-time agar akun yang dinonaktifkan tidak tetap bisa mengakses sistem."

---

## 8.4 Helper

### `tgl_helper`

File: `app/Helpers/tgl_helper.php`

Fungsi:

- `tglShortIndo()`
  Mengubah tanggal ke format singkat Indonesia.

- `hitungDurasi()`
  Menghitung durasi antara dua tanggal.

Kenapa penting:

- helper ini dipakai lintas modul untuk tampilan yang konsisten.
- helper sudah diautoload di `app/Config/Autoload.php`.

---

## 8.5 Service

### `EmailService`

File: `app/Services/EmailService.php`

Peran:

- service reusable untuk pengiriman email.

Method utama:

- `sendInfoLoginPkl()`
- `sendRekapKetua()`
- `sendOtpBiodata()`
- `sendOtpResetPassword()`

Poin teknis:

- konfigurasi email dibaca dari environment SMTP,
- template email dibangun dalam service,
- controller cukup memanggil method service.

Kenapa ini bagus:

- logic email tidak menumpuk di controller,
- template tetap terpusat,
- mudah diubah kalau desain email berubah.

Jawaban aman:

"Saya pisahkan pengiriman email ke service supaya controller fokus pada alur bisnis, sedangkan konfigurasi SMTP dan template email berada di satu tempat yang reusable."

---

## 9. Alur Sistem End-to-End

## 9.1 Alur Login

1. User membuka `auth/login`.
2. Route menuju `AuthController::login()`.
3. Jika sudah login, user diarahkan sesuai role.
4. Saat submit login, `processLogin()`:
   - validasi field,
   - cari user via username atau email,
   - cek status aktif,
   - verifikasi password,
   - ambil profil admin atau PKL,
   - isi session,
   - set cookie marker,
   - redirect ke dashboard role terkait.

## 9.2 Alur Lupa Password

1. User input email.
2. `sendForgotPasswordOtp()` cek akun dan status.
3. Sistem buat OTP 6 digit dan simpan ke `users`.
4. Session `forgot_password` dibuat.
5. Email OTP dikirim.
6. User input OTP.
7. `verifyForgotPasswordOtp()` cek:
   - OTP ada atau tidak,
   - belum expired,
   - salah berapa kali,
   - role admin atau PKL.
8. Jika lolos, session reset ditandai verified.
9. User kirim password baru.
10. `resetForgotPassword()` update password dan bersihkan OTP.

## 9.3 Alur Tambah PKL oleh Admin

1. Admin isi form multi-step.
2. Payload divalidasi detail.
3. Jika perlu, instansi baru dibuat.
4. Kelompok PKL dibuat.
5. User dan profil PKL tiap anggota dibuat.
6. Password random dan username unik digenerate.
7. Email login dikirim ke tiap anggota.
8. Rekap dikirim ke ketua.

## 9.4 Alur Biodata PKL Publik

1. User buka link biodata dengan token.
2. Sistem cek token dan status form.
3. User isi email dan minta OTP.
4. OTP dikirim dan disimpan di session.
5. User verifikasi OTP.
6. User isi payload biodata.
7. Sistem cek OTP verified dan email cocok.
8. Sistem buat instansi/kelompok/users/pkl.
9. Email login dikirim.
10. User diarahkan ke halaman sukses.

## 9.5 Alur Data Modul

1. Admin membuat kategori modul.
2. Admin membuat modul dengan tipe link atau file.
3. Jika file, file dipindah ke `writable/uploads/modul`.
4. PKL hanya membaca modul berdasarkan kategori.
5. File dapat dibuka inline jika PDF atau diunduh.

## 9.6 Alur Pembuatan Tugas

1. Admin mengisi ketentuan tugas.
2. Admin memilih sasaran:
   - individu,
   - kelompok,
   - tim tugas.
3. Sistem buat record `tugas`.
4. Sistem buat record `tugas_sasaran`.
5. Sistem bangun daftar penerima aktif.
6. Sistem buat record `pengumpulan_tugas` untuk setiap PKL penerima.

## 9.7 Alur Pengumpulan Tugas oleh PKL

1. PKL membuka detail tugas.
2. Sistem membaca baris `pengumpulan_tugas` milik PKL.
3. PKL upload file atau kirim link.
4. Sistem validasi jumlah item dan format.
5. Sistem simpan item ke `item_tugas`.
6. Sistem ubah `tgl_pengumpulan`.
7. Status item menjadi `dikirim`.

## 9.8 Alur Review Pengumpulan oleh Admin

1. Admin buka daftar pengumpulan.
2. Admin masuk ke detail pengumpulan.
3. Admin melihat item-item yang dikirim.
4. Admin memberi status:
   - diterima
   - revisi
5. Jika revisi, komentar wajib diisi.
6. Status agregat tugas berubah sesuai komposisi item.

---

## 10. Keputusan Desain yang Perlu Kamu Kuasai

## 10.1 Kenapa `users` dipisah dari `admin` dan `pkl`?

Karena:

- data login bersifat umum,
- data profil admin dan PKL berbeda,
- login cukup satu tabel akun,
- profil role-specific tetap rapi di tabel masing-masing.

Jawaban aman:

"Saya pisahkan akun umum dengan profil khusus role supaya autentikasi terpusat di `users`, tetapi data domain admin dan PKL tetap rapi dan tidak tercampur."

## 10.2 Kenapa pakai `BaseController`?

Karena:

- banyak field yang divalidasi berulang,
- aturan input harus konsisten,
- normalisasi input harus sama di semua modul.

## 10.3 Kenapa `tugas`, `tugas_sasaran`, `pengumpulan_tugas`, dan `item_tugas` dipisah?

Karena masing-masing punya tanggung jawab berbeda:

- `tugas` = definisi tugas
- `tugas_sasaran` = target logis
- `pengumpulan_tugas` = penerima operasional per PKL
- `item_tugas` = jawaban detail per file/link

Ini desain yang fleksibel dan scalable.

## 10.4 Kenapa `pengumpulan_tugas` dibuat saat tugas dibagikan?

Supaya sistem bisa:

- tahu siapa yang belum submit,
- menampilkan tugas ke PKL sejak awal,
- menghitung statistik progres.

## 10.5 Kenapa OTP biodata di session, tetapi OTP reset di database?

Karena:

- biodata publik belum pasti punya akun,
- reset password selalu terkait user yang sudah ada.

Jawaban aman:

"Saya menyesuaikan media penyimpanan OTP dengan konteks prosesnya. Untuk registrasi publik, OTP cukup sementara di session. Untuk reset password, OTP saya kaitkan langsung ke user yang sudah ada di database."

## 10.6 Kenapa file disimpan di `writable`?

Karena:

- lebih aman daripada langsung expose file di public,
- akses file bisa dikontrol lewat controller,
- mudah diberi pengecekan role dan ownership.

## 10.7 Kenapa pakai transaksi database?

Karena beberapa aksi mengubah banyak tabel sekaligus:

- tambah PKL,
- biodata publik,
- buat tugas,
- buat tim,
- hapus PKL.

Tanpa transaksi, data bisa setengah jadi saat salah satu langkah gagal.

## 10.8 Kenapa filter auth masih cek status user ke DB?

Karena session saja tidak cukup.

Kalau admin menonaktifkan user setelah login, session lama masih ada. Maka filter harus cek status real-time agar akses langsung diputus.

---

## 11. Kekuatan Backend yang Bisa Kamu Tonjolkan

1. Struktur MVC jelas
   Route, filter, controller, model, helper, service terpisah.

2. Validasi server-side cukup kuat
   Nama, email, nomor WA, password, tanggal, deadline, URL, file upload.

3. Role-based access control
   Admin, PKL, dan guest dipisahkan.

4. Auth flow cukup matang
   Ada login, logout, reset password OTP, session role, status account check.

5. Workflow tugas cukup lengkap
   Buat tugas, target multi-mode, pengumpulan, review, revisi, accepted.

6. Form biodata publik tidak sembarangan
   Ada token akses, toggle admin, OTP email, dan rate limiting.

7. Transaksi dipakai pada aksi lintas tabel
   Ini menunjukkan backend tidak asal insert.

8. File handling cukup terkontrol
   File tidak langsung dibuka dari public folder.

9. Email service dipisah
   Menunjukkan perhatian pada separation of concerns.

10. Dashboard tidak sekadar hitung biasa
    Ada query agregasi dan grouping sesuai desain bisnis.

---

## 12. Hal yang Perlu Jujur Jika Ditanya Dosen

Bagian ini penting. Jawaban aman bukan berarti menutupi fakta. Jawaban aman berarti jujur, tetapi tetap terarah dan profesional.

### 12.1 Controller masih cukup tebal

Jawaban aman:

"Benar, beberapa controller masih cukup tebal karena proyek ini memakai pendekatan pragmatis khas aplikasi PKL. Namun saya sudah menahan duplikasi dengan `BaseController`, `Model`, dan `EmailService`. Jika project dikembangkan lebih jauh, sebagian orchestration bisa saya pindahkan ke service layer tambahan."

### 12.2 Ada model lama yang tampaknya tidak aktif

Jawaban aman:

"Ada beberapa file model yang tampak merupakan sisa iterasi pengembangan lama dan saat ini tidak menjadi bagian dari route aktif. Secara fungsi aktif, alurnya sudah digantikan oleh modul yang lebih baru."

### 12.3 CSRF global belum aktif

Jawaban aman:

"Support CSRF dari CodeIgniter sebenarnya sudah tersedia, dan di frontend token juga sudah saya sisipkan lewat `csrf_field()`, `csrf_token()`, `csrf_hash()`, serta meta tag. Tetapi itu baru tahap penyediaan token. Agar proteksi benar-benar aktif, server harus menjalankan filter `csrf` untuk memverifikasi token tersebut. Pada implementasi saat ini filter itu belum aktif global, jadi peningkatan realistis berikutnya adalah menyalakan enforcement CSRF secara penuh."

### 12.4 Pengiriman email tidak membatalkan data inti

Jawaban aman:

"Ya, karena data utama saya prioritaskan tetap konsisten di database. Notifikasi email saya perlakukan sebagai proses lanjutan. Jadi bila email gagal, data PKL atau tugas tidak hilang, tetapi kegagalan email tetap dicatat di log dan bisa ditindaklanjuti."

---

## 13. Kemungkinan Pertanyaan Dosen dan Jawaban Aman

### 13.1 Sistem ini secara backend dibangun dengan apa?

Jawaban aman:

"Backend sistem ini dibangun dengan PHP menggunakan framework CodeIgniter 4. Saya memakai pola MVC, route-based access, session, model database, filter auth, helper validasi, dan service email."

### 13.2 Kenapa kamu memilih CodeIgniter 4?

Jawaban aman:

"Karena CodeIgniter 4 ringan, struktur MVC-nya jelas, cocok untuk aplikasi administrasi seperti ini, dan sudah menyediakan komponen penting seperti routing, model, validation, session, filter, dan email."

### 13.3 Apa inti sistem yang kamu bangun?

Jawaban aman:

"Inti sistemnya adalah manajemen PKL end-to-end, mulai dari onboarding biodata, pengelolaan akun PKL, pengelolaan modul, penugasan, pengumpulan tugas, sampai review hasil oleh admin."

### 13.4 Kenapa akun disimpan di `users`, tetapi profil ada di `admin` dan `pkl`?

Jawaban aman:

"Karena data akun bersifat umum, sedangkan data profil admin dan PKL berbeda. Jadi autentikasi saya pusatkan di `users`, lalu data domain saya pecah agar lebih rapi dan mudah dipelihara."

### 13.5 Bagaimana sistem membedakan admin dan PKL?

Jawaban aman:

"Lewat field `role` di tabel `users`, session login, dan route filter `auth:admin` atau `auth:pkl`."

### 13.6 Bagaimana kamu melindungi route berdasarkan role?

Jawaban aman:

"Saya menggunakan `AuthFilter` sebagai gerbang route. Filter memeriksa session login, role yang dibutuhkan, dan status akun secara real-time."

### 13.7 Kenapa filter masih cek status user ke database?

Jawaban aman:

"Supaya kalau admin menonaktifkan akun user yang sedang login, aksesnya bisa langsung diputus pada request berikutnya, jadi tidak hanya bergantung pada session lama."

### 13.8 Bagaimana alur login bekerja?

Jawaban aman:

"User mengirim username atau email dan password. Controller mencari user, cek status aktif, verifikasi password, ambil profil sesuai role, lalu membentuk session yang berisi identitas user dan role."

### 13.9 Password disimpan seperti apa?

Jawaban aman:

"Password tidak saya simpan plain text. Password di-hash otomatis oleh callback model `UserModel` sebelum insert atau update."

### 13.10 Lupa password di sistemmu pakai apa?

Jawaban aman:

"Saya memakai OTP melalui email. OTP reset password disimpan di tabel user, sedangkan status verifikasi sementaranya saya jaga di session. Untuk admin juga ada lock sementara jika salah OTP terlalu banyak."

### 13.11 Kenapa OTP reset password disimpan di tabel `users`?

Jawaban aman:

"Karena kebutuhan reset password di sistem ini masih sederhana dan satu user cukup punya satu OTP aktif pada satu waktu. Untuk skala lebih besar memang bisa dipisahkan ke tabel token tersendiri."

### 13.12 Kenapa OTP biodata publik tidak disimpan di database?

Jawaban aman:

"Karena pada tahap itu akun user belum tentu sudah ada. Jadi OTP publik lebih cocok ditampung di session sebagai data sementara."

### 13.13 Bagaimana form biodata publik diamankan?

Jawaban aman:

"Form publik saya amankan dengan token link yang disimpan di `app_settings`, toggle aktif/nonaktif oleh admin, OTP email, dan rate limiting pengiriman OTP."

### 13.14 Kenapa admin bisa generate token biodata?

Jawaban aman:

"Supaya link form publik bisa dirotasi sewaktu-waktu dan tidak permanen. Ini memberi kontrol penuh kepada admin."

### 13.15 Kenapa kamu membuat `BaseController`?

Jawaban aman:

"Karena banyak modul memakai aturan validasi yang sama. Saya pusatkan utility validasi dan normalisasi di `BaseController` agar konsisten dan tidak duplikatif."

### 13.16 Bagaimana validasi datanya?

Jawaban aman:

"Saya menerapkan validasi server-side di controller dengan bantuan helper method dari `BaseController`, misalnya validasi email, nomor WA, password, tanggal, deadline, URL, panjang teks, dan pola karakter."

### 13.17 Kenapa modul disimpan sebagai link atau file?

Jawaban aman:

"Karena kebutuhan materi tidak selalu file upload. Ada materi yang cukup berupa link eksternal, tetapi ada juga yang perlu diunduh sebagai dokumen."

### 13.18 File modul dan file tugas disimpan di mana?

Jawaban aman:

"Di folder `writable/uploads/modul` dan `writable/uploads/tugas`. Database hanya menyimpan path atau nama file, sedangkan akses file tetap dikontrol controller."

### 13.19 Kenapa file tidak langsung ditaruh di public?

Jawaban aman:

"Supaya akses file tetap lewat backend, sehingga bisa dicek role user, kepemilikan data, dan keberadaan file sebelum diberikan ke client."

### 13.20 Kenapa struktur tugasmu memakai banyak tabel?

Jawaban aman:

"Karena saya ingin memisahkan definisi tugas, target tugas, penerima pengumpulan, dan item jawaban. Dengan begitu sistem lebih fleksibel untuk mendukung tugas individu, kelompok, dan tim."

### 13.21 Apa fungsi `tugas_sasaran`?

Jawaban aman:

"`tugas_sasaran` adalah tabel untuk menyimpan sasaran logis tugas, apakah tugas ditujukan ke individu, kelompok, atau tim tugas."

### 13.22 Apa fungsi `pengumpulan_tugas`?

Jawaban aman:

"`pengumpulan_tugas` adalah tabel operasional penerima tugas. Baris ini dibuat saat tugas dibagikan, sehingga sistem bisa melacak siapa yang belum, sedang, atau sudah mengumpulkan."

### 13.23 Apa fungsi `item_tugas`?

Jawaban aman:

"`item_tugas` menyimpan detail jawaban per file atau link. Ini penting karena satu tugas bisa meminta lebih dari satu item jawaban."

### 13.24 Kenapa tugas kelompok dan tim tetap punya record per PKL?

Jawaban aman:

"Supaya pelacakan progres tetap detail per penerima, tetapi di sisi admin tetap bisa digrup berdasarkan kelompok atau tim."

### 13.25 `tugas.id_user` itu user siapa?

Jawaban aman:

"Itu user pembuat tugas, bukan penerima tugas. Penerima tugas disimpan melalui `tugas_sasaran` dan `pengumpulan_tugas`."

### 13.26 Bagaimana dashboard admin menghitung tugas yang masih perlu diperhatikan?

Jawaban aman:

"Dashboard admin menghitung tugas aktif dan overdue yang belum selesai secara pengumpulan, termasuk tugas yang masih menunggu review atau perlu revisi."

### 13.27 Bagaimana dashboard PKL tahu status tugas?

Jawaban aman:

"Status tugas dihitung dari data pengumpulan dan item tugas, misalnya apakah belum dikumpulkan, menunggu review, perlu revisi, atau sudah diterima."

### 13.28 Kenapa kamu pakai transaksi database?

Jawaban aman:

"Karena banyak aksi backend mengubah beberapa tabel sekaligus. Dengan transaksi, kalau satu langkah gagal maka semua langkah dibatalkan agar data tidak setengah tersimpan."

### 13.29 Di bagian mana kamu memakai transaksi?

Jawaban aman:

"Contohnya pada tambah PKL oleh admin, registrasi biodata publik, pembuatan tugas, pembuatan tim tugas, dan penghapusan PKL."

### 13.30 Bagaimana sistem review tugas bekerja?

Jawaban aman:

"Admin mereview item jawaban satu per satu. Status item bisa diterima atau revisi. Jika revisi, komentar wajib diisi. Dari sana status agregat tugas untuk PKL ikut berubah."

### 13.31 Kenapa review dilakukan per item, bukan langsung per tugas?

Jawaban aman:

"Karena satu tugas bisa punya beberapa jawaban. Dengan review per item, admin bisa lebih fleksibel menilai mana yang sudah benar dan mana yang masih perlu revisi."

### 13.32 Bagaimana PKL dibatasi agar hanya bisa akses file miliknya?

Jawaban aman:

"Saat download jawaban, query backend join ke `pengumpulan_tugas` dan memeriksa `id_pkl` dari session. Jadi PKL hanya bisa mengakses item yang memang miliknya."

### 13.33 Kenapa ada `EmailService` terpisah?

Jawaban aman:

"Supaya konfigurasi SMTP dan template email reusable dan tidak bercampur dengan controller."

### 13.34 Bagaimana kalau email gagal dikirim?

Jawaban aman:

"Data utama tetap tersimpan. Error email dicatat di log, dan notifikasi bisa ditindaklanjuti. Jadi data inti tidak hilang hanya karena notifikasi gagal."

### 13.35 Kenapa ada model yang tampak tidak dipakai?

Jawaban aman:

"Itu adalah sisa iterasi pengembangan. Pada versi aktif sekarang, alur utamanya sudah dipindahkan ke controller dan model yang lebih baru. File seperti itu merupakan kandidat refactor lanjutan."

### 13.36 Menurutmu apa kekuatan backend yang kamu buat?

Jawaban aman:

"Kekuatan utamanya ada pada pemisahan role yang jelas, alur penugasan yang fleksibel, validasi server-side yang cukup rapi, penggunaan transaksi untuk konsistensi data, dan kontrol file yang tidak langsung diekspos."

### 13.37 Menurutmu apa kekurangannya?

Jawaban aman:

"Kekurangannya, beberapa controller masih cukup tebal dan masih ada file legacy yang bisa dirapikan. Selain itu, ada beberapa hardening keamanan yang masih bisa saya lanjutkan, seperti penguatan CSRF global dan refactor service layer."

### 13.38 Jika sistem ini dikembangkan lagi, apa yang akan kamu tingkatkan?

Jawaban aman:

"Saya akan menambah service layer untuk logic bisnis yang besar, merapikan file legacy, memperketat hardening keamanan, dan menambah automated test untuk flow penting seperti auth, biodata, dan tugas."

---

## 14. Jawaban Cepat 1 Kalimat Saat Kamu Nervous

Kalau kamu gugup, pakai pola jawaban singkat ini:

- "Backend saya dibangun dengan CodeIgniter 4 pola MVC."
- "Autentikasi saya pusatkan di tabel `users`, sedangkan profil role saya pisah ke tabel `admin` dan `pkl`."
- "Kontrol akses saya tangani lewat `AuthFilter` dan session role."
- "Validasi umum saya pusatkan di `BaseController` supaya konsisten."
- "Untuk aksi lintas tabel saya pakai transaksi database."
- "Tugas saya pecah menjadi definisi tugas, sasaran, pengumpulan, dan item jawaban agar mendukung individu, kelompok, dan tim."
- "File saya simpan di `writable` dan saya akses lewat controller agar tetap terkontrol."
- "Form biodata publik saya amankan dengan token akses dan OTP email."
- "Status tugas dihitung dari data pengumpulan dan review item."
- "Saya sengaja membedakan OTP registrasi publik dan OTP reset password karena konteks datanya berbeda."

---

## 15. Checklist Belajar Sebelum Seminar

Sebelum seminar, usahakan kamu benar-benar hafal hal berikut:

1. Tiga aktor sistem
   - admin
   - PKL
   - user publik form biodata

2. Lima alur inti
   - login
   - forgot password
   - tambah PKL atau biodata publik
   - buat tugas
   - pengumpulan dan review tugas

3. Lima relasi tabel yang wajib hafal
   - `users -> admin`
   - `users -> pkl`
   - `kelompok_pkl -> pkl`
   - `tugas -> tugas_sasaran`
   - `pengumpulan_tugas -> item_tugas`

4. Poin desain yang wajib hafal
   - `tugas.id_user` adalah pembuat tugas
   - penerima tugas ada di `tugas_sasaran`
   - `pengumpulan_tugas` dibuat sejak penugasan
   - `item_tugas` adalah detail jawaban
   - OTP biodata dan OTP reset password sengaja dibedakan

5. Kelebihan backend yang bisa kamu banggakan
   - validasi server-side
   - transaksi database
   - role filter
   - file access terkontrol
   - email notification
   - target tugas fleksibel

6. Kekurangan yang harus siap kamu jawab
   - masih ada controller tebal
   - ada file legacy
   - hardening keamanan masih bisa ditingkatkan

---

## 16. Penutup

Kalau kamu diminta menjelaskan backend secara singkat, versi paling aman adalah:

"Backend SIMMAG ODC saya bangun dengan CodeIgniter 4 menggunakan pola MVC. Saya memisahkan akun umum di tabel `users` dari profil role-specific di tabel `admin` dan `pkl`. Untuk keamanan dan konsistensi, saya memakai filter auth, validasi server-side, hashing password, OTP email, transaksi database, dan kontrol file melalui controller. Modul yang saya bangun mencakup login, onboarding biodata PKL, manajemen instansi dan PKL, distribusi modul, penugasan, pengumpulan tugas, dan review hasil tugas."

Kalau kamu bisa menjelaskan kalimat di atas dengan tenang dan lalu menurunkannya ke tabel, alur, dan controller yang sesuai, maka kamu sudah cukup siap untuk menjawab sebagian besar pertanyaan dosen penguji pada sisi backend.
