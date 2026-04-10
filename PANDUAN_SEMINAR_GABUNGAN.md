# Panduan Seminar SIMMAG ODC

Dokumen ini adalah panduan gabungan Backend dan Frontend untuk membantu presentasi serta tanya jawab Seminar PKL pada proyek `SIMMAG ODC`.

Tujuan dokumen ini:

1. Membantu menjelaskan sistem secara utuh, bukan terpisah-pisah antara BE dan FE.
2. Membantu memahami alur bisnis sistem dari sudut pandang user, backend, dan frontend sekaligus.
3. Menyiapkan jawaban aman untuk pertanyaan dosen.
4. Meluruskan hal-hal yang sering membingungkan, terutama soal CSRF, validasi, role, dan alur tugas.

Catatan penting:

- Dokumen ini disusun dari hasil membaca source code aktif yang ada sekarang.
- Isi dokumen ini tidak mengubah implementasi program, hanya membantu memahami apa yang benar-benar terjadi di kode.

---

## 1. Gambaran Singkat Sistem

SIMMAG ODC adalah sistem informasi manajemen PKL untuk PT Our Digital Creative.

Kalau dijelaskan sangat singkat, sistem ini mempunyai tiga pengguna utama:

1. Admin
   Mengelola instansi, data PKL, modul pembelajaran, tugas, pengumpulan tugas, review, dan pengaturan link biodata PKL.

2. PKL
   Melihat dashboard, membaca modul, melihat tugas, mengumpulkan tugas, dan memperbarui profil.

3. Calon atau peserta PKL yang belum login
   Mengisi form biodata melalui link token publik, lalu diverifikasi emailnya dengan OTP sebelum data disimpan.

Kalau dosen bertanya "sistem ini sebenarnya apa?", jawaban aman:

"SIMMAG ODC adalah sistem administrasi PKL yang menangani alur onboarding peserta, pengelolaan data PKL, distribusi modul pembelajaran, penugasan, pengumpulan tugas, review hasil, dan manajemen akun berbasis role admin dan PKL."

---

## 2. Cara Menjelaskan Arsitektur Secara Umum

Secara arsitektur, sistem ini memakai pendekatan `server-rendered web application` berbasis CodeIgniter 4.

Artinya:

- backend mengelola route, controller, model, database, session, email, cache, dan file
- frontend merender halaman dari view PHP
- JavaScript dipakai untuk membuat interaksi tertentu lebih nyaman, misalnya login AJAX, OTP, wizard, filter tabel, modal upload, dan review

Kalau digambar sederhana:

```text
User
  -> Browser
  -> Routes CodeIgniter
  -> Filter Auth (jika route diproteksi)
  -> Controller
  -> Model / Helper / Service / DB / Session / Email / Cache
  -> View PHP
  -> HTML + CSS + JS
  -> Interaksi lanjutan (AJAX / form submit)
```

Jadi sistem ini bukan SPA React atau Vue, tetapi juga bukan halaman statis biasa. Ini adalah aplikasi hybrid:

- render awal dilakukan server
- interaksi tertentu dibuat dinamis dengan JavaScript

Jawaban aman:

"Saya memakai pendekatan hybrid. Render awal halaman dilakukan server agar sederhana dan stabil, lalu bagian yang perlu interaksi cepat saya buat dinamis dengan JavaScript dan AJAX."

---

## 3. Teknologi yang Dipakai

## 3.1 Backend

- PHP `^8.2`
- CodeIgniter 4
- MySQLi
- Session
- Cache
- SMTP email
- File storage di `writable/uploads`

## 3.2 Frontend

- View PHP CodeIgniter
- CSS modular
- JavaScript vanilla + jQuery
- DataTables
- Select2
- Flatpickr
- SweetAlert2
- Font Awesome
- Google Fonts Montserrat

Kalau ditanya "stack proyeknya apa?", jawaban aman:

"Backend saya bangun dengan CodeIgniter 4 dan MySQL, sedangkan frontend saya bangun dengan view PHP, CSS modular, dan JavaScript berbasis jQuery serta beberapa plugin seperti DataTables, Select2, Flatpickr, dan SweetAlert2."

---

## 4. Struktur Sistem dari Sudut Pandang Folder

## 4.1 Backend

Bagian backend utama ada di:

- `app/Controllers`
- `app/Models`
- `app/Filters`
- `app/Helpers`
- `app/Services`
- `app/Config`
- `app/Database/Migrations`

## 4.2 Frontend

Bagian frontend utama ada di:

- `app/Views`
- `public/assets/css`
- `public/assets/js`
- `public/assets/images`

Cara menjelaskannya:

- `Controllers` mengatur alur request dan response
- `Models` berbicara dengan database
- `Views` menampilkan halaman
- `public/assets` menyiapkan tampilan dan interaksi

---

## 5. Pemetaan Area Sistem

## 5.1 Area Auth

Fungsinya:

- login
- lupa password
- logout

Backend utama:

- `AuthController`
- `UserModel`
- `EmailService`

Frontend utama:

- `app/Views/auth/login.php`
- `app/Views/auth/lupa_password.php`
- `public/assets/css/modules/auth.css`
- `public/assets/js/modules/auth.js`
- `public/assets/js/modules/auth_forgot_password.js`

## 5.2 Area Publik Biodata PKL

Fungsinya:

- membuka form biodata dengan token
- mengisi data PKL
- verifikasi email dengan OTP
- menyimpan biodata

Backend utama:

- `BiodataPklController`
- `PklModel`
- `KelompokPklModel`
- `InstansiModel`
- `UserModel`
- `EmailService`

Frontend utama:

- `app/Views/biodata_pkl/index.php`
- `app/Views/biodata_pkl/ditutup.php`
- `app/Views/biodata_pkl/sukses.php`
- `public/assets/css/modules/biodata_pkl.css`
- `public/assets/js/modules/biodata_pkl.js`

## 5.3 Area Admin

Fungsinya:

- dashboard admin
- manajemen instansi
- manajemen PKL
- data modul
- penugasan
- pengumpulan dan review tugas
- profil admin
- pengaturan link biodata PKL

Backend utama:

- `DashboardAdminController`
- `InstansiAdminController`
- `MPklAdminController`
- `ModulAdminController`
- `MTugasAdminController`
- `ProfilAdminController`

Frontend utama:

- `app/Views/dashboard_admin/...`
- `public/assets/css/modules/admin/...`
- `public/assets/js/modules/admin/...`

## 5.4 Area PKL

Fungsinya:

- dashboard PKL
- melihat modul
- melihat tugas
- mengumpulkan tugas
- memperbarui profil

Backend utama:

- `DashboardPklController`
- `ModulPklController`
- `MTugasPklController`
- `ProfilPklController`

Frontend utama:

- `app/Views/dashboard_pkl/...`
- `public/assets/css/modules/pkl/...`
- `public/assets/js/modules/pkl/...`

---

## 6. Role dan Hak Akses

Role utama di sistem:

1. `admin`
2. `pkl`

Hak akses dijaga dengan:

- route grouping
- `auth` filter alias
- session role
- pembatasan tampilan menu di sidebar

Contoh:

- route admin dibungkus `auth:admin`
- route PKL dibungkus `auth:pkl`
- halaman login atau lupa password memakai `auth:guest`

Jadi hak akses tidak hanya dikendalikan di tampilan, tetapi juga di level route.

Jawaban aman:

"Role-based access saya terapkan di dua sisi: backend memakai auth filter pada route, sedangkan frontend menampilkan menu yang sesuai role agar user tidak melihat fitur yang bukan haknya."

---

## 7. Cara Kerja Backend dan Frontend Secara Bersama

Supaya mudah dipahami, berikut logika kerjanya:

1. Controller mempersiapkan data
2. Data dikirim ke view
3. View merender HTML
4. CSS dan JS dari `public/assets` menghidupkan tampilan dan interaksi
5. Jika user melakukan aksi tertentu, request baru dikirim:
   - lewat form submit biasa
   - atau lewat AJAX
6. Backend memproses, lalu membalas:
   - HTML baru
   - atau JSON response

Contoh paling sederhana:

### Login

- frontend menampilkan form login
- user klik masuk
- JS mengirim AJAX ke `AuthController::processLogin`
- backend memverifikasi user
- jika sukses, backend membalas JSON berisi redirect
- frontend mengarahkan user ke halaman sesuai role

---

## 8. Alur Bisnis Sistem yang Paling Penting

## 8.1 Alur Login

### Backend

- route `auth/login`
- `AuthController`
- cek username atau email
- verifikasi password
- isi session user
- arahkan sesuai role

### Frontend

- `login.php`
- `auth.js`
- validasi field kosong
- submit AJAX
- tampilkan pesan gagal atau redirect sukses

### Narasi seminar

"Login dirancang sederhana di tampilan, tetapi backend tetap menentukan identitas, role, dan arah halaman setelah user berhasil masuk."

## 8.2 Alur Lupa Password

### Backend

- cek email user
- generate OTP
- simpan OTP dan tenggatnya
- kirim email
- verifikasi OTP
- reset password

### Frontend

- step email
- step OTP
- step password baru
- countdown OTP
- lock timer jika terlalu banyak percobaan

### Narasi seminar

"Lupa password saya buat bertahap agar user lebih mudah memahami proses, sedangkan backend menjaga validitas OTP dan perubahan password."

## 8.3 Alur Biodata PKL Publik

### Backend

- cek token biodata aktif
- tampilkan form
- cek email
- kirim OTP
- verifikasi OTP
- simpan data instansi atau kelompok atau anggota
- buat akun login PKL

### Frontend

- wizard 3 langkah
- data PKL
- biodata anggota
- konfirmasi + OTP
- preview data
- form dinamis berdasarkan jumlah anggota

### Narasi seminar

"Saya buat alur biodata dalam bentuk wizard supaya user tidak kewalahan. Validasi awal dibantu frontend, tetapi penyimpanan final tetap ditentukan backend setelah email diverifikasi."

## 8.4 Alur Admin Mengelola PKL

### Backend

- admin membuka modul manajemen PKL
- backend membedakan data instansi dan data PKL
- backend memproses create, update, delete, dan toggle status

### Frontend

- tab instansi dan tab PKL
- custom filter
- tabel responsif
- wizard tambah PKL

### Narasi seminar

"Secara UI admin melihatnya dalam satu modul manajemen PKL, tetapi secara data saya tetap memisahkan master instansi dan data peserta PKL karena keduanya memiliki peran yang berbeda."

## 8.5 Alur Admin Mengelola Modul

### Backend

- kategori modul dan modul dipisah
- backend mengelola CRUD kategori
- backend mengelola CRUD modul
- modul bisa berupa file atau link

### Frontend

- tab kategori modul
- tab modul
- DataTables
- panel filter
- detail modul
- form create atau edit

### Narasi seminar

"Saya memisahkan kategori modul dan isi modul supaya materi pembelajaran lebih terstruktur dan mudah dikelola."

## 8.6 Alur Admin Membuat Tugas

### Backend

- admin membuat kategori tugas
- kategori menentukan mode pengumpulan
- admin membuat definisi tugas
- admin memilih sasaran
- backend membentuk data pengumpulan sesuai sasaran

### Frontend

- step 1: ketentuan tugas
- step 2: pilih sasaran
- sasaran bisa individu, kelompok, atau tim
- step 1 disimpan sementara di `sessionStorage`

### Narasi seminar

"Saya membagi proses pembuatan tugas menjadi dua langkah. Langkah pertama menjawab apa isi tugasnya, sedangkan langkah kedua menjawab siapa target pengerjaannya."

## 8.7 Alur Pengumpulan dan Review Tugas

### Backend

- PKL mengirim jawaban
- backend menyimpan pengumpulan dan item tugas
- admin membuka detail pengumpulan
- admin bisa menyetujui atau meminta revisi

### Frontend

- PKL melihat daftar tugas
- PKL membuka detail tugas
- PKL mengunggah file atau mengirim link
- admin membuka detail pengumpulan
- admin melakukan review item per item

### Narasi seminar

"Alur tugas saya buat lengkap dari penugasan sampai review. Jadi sistem tidak berhenti di pemberian tugas, tetapi mendukung pengumpulan, evaluasi, revisi, dan status hasil."

---

## 9. Penjelasan Khusus Tentang CSRF

Bagian ini penting karena memang sering membingungkan.

## 9.1 CSRF itu apa?

CSRF adalah singkatan dari `Cross Site Request Forgery`.

Gambaran sederhananya:

- user sedang login di sistem kita
- lalu user membuka situs lain yang jahat
- situs jahat itu mencoba memanfaatkan browser user untuk mengirim request ke sistem kita
- kalau sistem tidak punya perlindungan, browser korban bisa saja mengirim aksi yang tidak diinginkan

Contoh sederhananya:

- menghapus data
- mengganti password
- mengirim form tertentu

Jadi inti serangan CSRF adalah:

"Browser korban dipakai untuk mengirim request seolah-olah korban yang sengaja melakukannya."

## 9.2 Bagaimana cara kerja perlindungan CSRF?

Perlindungan CSRF biasanya bekerja dengan token rahasia.

Langkah sederhananya:

1. Server membuat token
2. Token dimasukkan ke form atau halaman
3. Saat user submit form, token ikut dikirim lagi ke server
4. Server mengecek apakah token yang dikirim cocok
5. Jika tidak cocok, request ditolak

Jadi ada dua bagian penting:

1. Token tersedia di frontend
2. Server benar-benar memeriksa token tersebut

Kalau salah satu tidak ada, perlindungan belum lengkap.

## 9.3 Kenapa kamu tadi bilang CSRF global belum aktif, padahal di frontend ada `csrf_field()` dan meta token?

Inilah inti kebingungannya.

Jawaban sederhananya:

- `csrf_field()`, `csrf_meta()`, `csrf_token()`, dan `csrf_hash()` hanya menyiapkan token di HTML atau JavaScript
- tetapi token itu baru efektif kalau backend memaksa verifikasi token tersebut

Di project ini saya menemukan fakta berikut:

1. Di `app/Config/Security.php`, konfigurasi CSRF CodeIgniter memang ada.
2. Di banyak view, token juga sudah disisipkan:
   - `csrf_field()`
   - meta `csrf-token-name`
   - meta `csrf-token-hash`
3. Beberapa JavaScript juga sudah siap menerima `csrfHash` baru dari response.
4. Tetapi di `app/Config/Filters.php`, filter global `csrf` masih dikomentari.
5. Saya juga tidak menemukan route yang memasang filter `csrf` secara khusus.

Kesimpulannya:

- frontend sudah `ready for CSRF`
- backend sudah punya `infrastruktur CSRF`
- tetapi `enforcement` atau penegakan verifikasi CSRF otomatis belum aktif secara global

Jadi dua pernyataan berikut sama-sama benar:

1. "Token CSRF ada di frontend"
2. "CSRF global belum aktif"

Karena yang pertama berbicara tentang `ketersediaan token`, sedangkan yang kedua berbicara tentang `penegakan verifikasi oleh server`.

## 9.4 Analogi supaya gampang diingat

Analogi yang aman:

"Token CSRF itu seperti tiket masuk yang sudah dicetak dan dibagikan ke penonton. Tetapi keamanan baru benar-benar berjalan kalau di pintu masuk ada petugas yang memeriksa tiket tersebut. Di sistem ini, tiketnya sudah banyak disiapkan, tetapi petugas pemeriksa globalnya belum diaktifkan penuh."

## 9.5 Kalimat aman saat seminar kalau ditanya soal CSRF

Jawaban aman:

"Di implementasi saat ini, support CSRF dari CodeIgniter sudah ada dan token juga sudah banyak saya sisipkan ke form dan JavaScript. Namun setelah saya cek konfigurasi aktifnya, filter CSRF global memang belum dinyalakan penuh, jadi bagian yang sudah ada sekarang lebih tepat disebut persiapan infrastruktur token dan belum enforcement global secara penuh."

## 9.6 Kenapa frontend tetap menyisipkan token kalau filter global belum aktif?

Ada beberapa alasan yang masuk akal:

1. Karena helper CSRF dari CodeIgniter memang sudah tersedia dan mudah dipakai.
2. Karena struktur frontend sudah disiapkan agar siap jika filter CSRF diaktifkan penuh nanti.
3. Karena beberapa endpoint AJAX memang sudah dibuat dengan pola refresh token.

Jadi ini bukan salah atau kontradiksi, tetapi lebih tepat disebut:

- implementasi sudah setengah jalan ke arah CSRF penuh
- hanya enforcement globalnya yang belum dinyalakan

---

## 10. Penjelasan Validasi: Frontend vs Backend

Ini juga pertanyaan yang sering muncul.

## 10.1 Validasi frontend

Validasi frontend dipakai untuk:

- mencegah input kosong
- membatasi karakter yang tidak valid
- mempercepat feedback
- memperbaiki pengalaman pengguna

Contoh:

- email format
- nomor WA
- nama
- tanggal
- URL
- ukuran file

## 10.2 Validasi backend

Validasi backend dipakai untuk:

- memastikan data tetap aman walau frontend dilewati
- mengecek rule bisnis
- memverifikasi hak akses
- menyimpan data secara valid

Kesimpulan penting:

- frontend membantu
- backend menentukan hasil akhir

Jawaban aman:

"Saya membedakan jelas antara validasi frontend dan backend. Frontend membantu user mengisi data dengan benar, tetapi backend tetap menjadi lapisan validasi utama."

---

## 11. Mengapa Sistem Ini Tidak Memakai SPA?

Kalau dosen bertanya kenapa tidak memakai React, Vue, atau SPA lain, jawaban amannya:

"Karena kebutuhan proyek PKL ini lebih cocok dengan aplikasi multipage yang sederhana, mudah dipelihara, cepat diintegrasikan dengan CodeIgniter, dan tetap cukup interaktif berkat jQuery dan plugin yang relevan. Jadi saya memilih arsitektur yang proporsional terhadap kebutuhan proyek."

Versi teknisnya:

- routing server lebih sederhana
- render awal lebih mudah
- deployment lebih mudah
- tidak perlu build pipeline terpisah
- cocok untuk sistem administrasi internal

---

## 12. Kelebihan Sistem dari Sudut Pandang Seminar

Hal-hal yang aman untuk kamu tonjolkan:

1. Sistem sudah menangani proses bisnis utuh.
   Dari onboarding PKL sampai review tugas.

2. Role dipisah dengan jelas.
   Admin dan PKL memiliki route, menu, dan halaman yang berbeda.

3. Struktur kode cukup terorganisasi.
   Backend memakai pola MVC, frontend dipisah antara `Views` dan `public/assets`.

4. Interaksi frontend cukup kaya.
   Ada wizard, DataTables, Select2, Flatpickr, SweetAlert2, modal upload, dan countdown OTP.

5. Validasi tidak hanya di tampilan.
   Backend tetap berperan utama.

6. Sistem mendukung data file dan link.
   Baik untuk modul maupun tugas.

7. Ada alur review tugas, bukan hanya submit tugas.

---

## 13. Keterbatasan Sistem yang Jujur

Kalau diminta menjelaskan kekurangan, kamu bisa jawab jujur tapi tetap aman:

1. Beberapa controller masih cukup besar.
2. Masih ada file legacy yang dapat dirapikan.
3. Frontend belum memakai modular build modern.
4. Beberapa asset pihak ketiga masih memakai CDN.
5. Hardening keamanan seperti enforcement CSRF global masih bisa ditingkatkan.
6. Automated testing masih bisa ditambah.

Jawaban aman:

"Saya sadar sistem ini masih punya ruang perbaikan, terutama pada refactor modularisasi, hardening keamanan, dan automated test. Tetapi untuk scope PKL, fondasi utamanya sudah berjalan dan alur bisnis inti sudah terpenuhi."

---

## 14. Pertanyaan Dosen yang Paling Mungkin Keluar

## 14.1 Pertanyaan umum sistem

### Q: Sistem ini dibuat untuk apa?

Jawaban aman:

"Sistem ini dibuat untuk mendukung manajemen PKL secara end-to-end, mulai dari pendaftaran biodata, pengelolaan data PKL, distribusi modul, penugasan, pengumpulan tugas, sampai review hasil tugas."

### Q: Siapa saja pengguna sistem ini?

Jawaban aman:

"Pengguna utamanya ada tiga: admin, peserta PKL yang sudah punya akun, dan calon atau peserta PKL yang mengisi biodata melalui link publik."

## 14.2 Pertanyaan arsitektur

### Q: Kenapa kamu memilih CodeIgniter 4?

Jawaban aman:

"Karena CodeIgniter 4 cukup ringan, cocok untuk proyek PKL, mudah dipelajari, dan sudah menyediakan fondasi penting seperti MVC, routing, filter, helper, session, validation, dan security utilities."

### Q: Apakah ini SPA?

Jawaban aman:

"Tidak. Ini aplikasi server-rendered multipage. Render awal dilakukan server melalui view PHP, lalu interaksi tertentu dibuat dinamis dengan JavaScript."

### Q: Kenapa tidak pakai React atau Vue?

Jawaban aman:

"Karena scope proyek ini lebih cocok dengan pendekatan yang sederhana, stabil, dan cepat diintegrasikan dengan backend CodeIgniter."

## 14.3 Pertanyaan backend

### Q: Bagaimana backend memisahkan tugas admin dan PKL?

Jawaban aman:

"Pemisahan dilakukan melalui route group, auth filter per role, controller yang berbeda, dan query data yang disesuaikan dengan role user aktif."

### Q: Bagaimana password disimpan?

Jawaban aman:

"Password disimpan dalam bentuk hash. Implementasi aktifnya memakai bcrypt melalui `password_hash` dan diverifikasi dengan `password_verify`."

### Q: Bagaimana OTP dipakai di sistem?

Jawaban aman:

"OTP dipakai pada dua alur, yaitu reset password dan verifikasi biodata publik. Backend mengatur pembuatan OTP, masa berlaku, pembatasan percobaan, dan verifikasi sebelum proses lanjut."

## 14.4 Pertanyaan frontend

### Q: Kenapa banyak wizard di sistem ini?

Jawaban aman:

"Karena ada beberapa proses yang cukup panjang, seperti biodata PKL, tambah PKL, dan tambah tugas. Dengan wizard, user lebih fokus dan risiko salah input lebih kecil."

### Q: Kenapa pakai DataTables?

Jawaban aman:

"Karena banyak data admin ditampilkan dalam bentuk tabel dan saya membutuhkan pagination, sorting, responsive detail, dan filter yang rapi."

### Q: Kenapa pakai Select2 dan Flatpickr?

Jawaban aman:

"Agar input pilihan dan tanggal lebih nyaman digunakan serta lebih jelas di sisi user."

## 14.5 Pertanyaan keamanan

### Q: Apakah sistem ini sudah aman?

Jawaban aman:

"Untuk implementasi PKL, saya sudah menerapkan validasi server-side, role-based access, hashing password, OTP, pembatasan upload file, dan pengelolaan session. Tetapi saya juga jujur bahwa hardening keamanan masih bisa ditingkatkan, termasuk enforcement CSRF global."

### Q: Jelaskan lagi soal CSRF di sistemmu.

Jawaban aman:

"Token CSRF di frontend sudah banyak disiapkan melalui helper CodeIgniter. Namun token yang tersedia itu belum sama dengan enforcement. Setelah saya cek konfigurasi aktifnya, filter CSRF global masih belum diaktifkan, jadi yang sudah ada sekarang adalah infrastruktur tokennya, sedangkan penegakan globalnya masih bisa ditingkatkan."

## 14.6 Pertanyaan pengembangan lanjut

### Q: Kalau proyek ini dikembangkan lagi, apa yang akan kamu lakukan?

Jawaban aman:

"Saya akan fokus pada refactor controller yang besar, memperkuat modularisasi helper atau service, menambah automated test, mengurangi ketergantungan CDN, dan menyalakan hardening keamanan tambahan seperti enforcement CSRF global."

---

## 15. Cara Presentasi 3 Menit yang Aman

Kalau kamu butuh narasi singkat sekitar 3 menit, kamu bisa pakai struktur ini:

"SIMMAG ODC adalah sistem informasi manajemen PKL untuk PT Our Digital Creative. Sistem ini saya bangun untuk menangani proses PKL secara lengkap, mulai dari pendaftaran biodata melalui link publik, pengelolaan data PKL oleh admin, distribusi modul pembelajaran, penugasan, pengumpulan tugas, sampai review hasil.

Secara teknis, backend sistem ini menggunakan CodeIgniter 4 dengan pola MVC. Route, controller, model, session, email, cache, dan database dikelola di backend. Di sisi frontend, saya menggunakan view PHP yang dirender server, lalu saya tambahkan JavaScript modular dengan jQuery, DataTables, Select2, Flatpickr, dan SweetAlert2 agar pengalaman pengguna lebih baik.

Saya membagi sistem menjadi tiga area, yaitu area admin, area PKL, dan area publik biodata. Admin dapat mengelola instansi, PKL, modul, tugas, dan review pengumpulan. PKL dapat melihat modul, melihat tugas, mengumpulkan tugas, dan mengubah profil. Sedangkan calon atau peserta PKL yang belum login dapat mengisi biodata melalui link token dan diverifikasi dengan OTP.

Untuk keamanan dan kualitas data, saya menerapkan validasi server-side, role-based filter, hashing password, OTP, dan pembatasan upload file. Untuk sisi frontend, saya juga menambahkan validasi client-side dan alur wizard agar proses yang kompleks lebih mudah digunakan."

---

## 16. Cheat Sheet Hafalan Cepat

Kalau kamu butuh versi super singkat:

- Sistem ini adalah manajemen PKL end-to-end.
- Pengguna utama: admin, PKL, dan pengisi biodata publik.
- Backend: CodeIgniter 4, MVC, MySQL, session, email, cache.
- Frontend: view PHP, CSS modular, JS + jQuery.
- Library utama: DataTables, Select2, Flatpickr, SweetAlert2.
- Area utama:
  - auth
  - biodata publik
  - dashboard admin
  - dashboard PKL
- Flow penting:
  - login
  - lupa password OTP
  - biodata publik OTP
  - manajemen PKL
  - data modul
  - penugasan
  - pengumpulan dan review
- Validasi frontend membantu user.
- Validasi backend menentukan hasil akhir.
- Token CSRF sudah banyak disiapkan.
- Tetapi filter CSRF global belum aktif penuh.

---

## 17. Kesimpulan

Kalimat penutup yang aman:

"Secara keseluruhan, SIMMAG ODC saya rancang sebagai sistem PKL yang terstruktur dan praktis dipakai. Backend mengelola logika bisnis, data, dan hak akses, sedangkan frontend mengelola tampilan dan kenyamanan interaksi. Walaupun masih ada ruang pengembangan, fondasi sistem dan alur bisnis intinya sudah berjalan dengan baik."
