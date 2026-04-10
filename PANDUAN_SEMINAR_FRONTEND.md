# Panduan Seminar Frontend SIMMAG ODC

Dokumen ini saya susun khusus untuk membantu presentasi dan tanya jawab Seminar PKL pada sisi frontend aplikasi `SIMMAG ODC`.

Fokus dokumen ini:

1. Memahami struktur frontend yang aktif saat ini.
2. Menjelaskan hubungan antara `Views` dan asset di folder `public`.
3. Menjelaskan alur halaman publik, admin, dan PKL dari sudut pandang antarmuka.
4. Menyiapkan jawaban aman jika dosen penguji bertanya tentang arsitektur frontend, alasan desain, validasi, interaksi dengan backend, dan maintainability.

Catatan penting:

- Dokumen ini dibuat dari hasil membaca source code frontend yang aktif saat ini.
- Frontend proyek ini bukan SPA React/Vue, tetapi aplikasi web `server-rendered` berbasis CodeIgniter 4 yang diperkuat dengan JavaScript modular.
- Jadi cara menjelaskannya saat seminar harus menekankan bahwa halaman tetap dirender server, lalu interaksi tertentu dibuat lebih nyaman dengan `AJAX`, `DataTables`, `Select2`, `Flatpickr`, dan `SweetAlert2`.

---

## 1. Ringkasan Frontend

Secara sederhana, frontend SIMMAG ODC adalah antarmuka multi-halaman yang dibagi menjadi tiga area besar:

1. Halaman publik
   Digunakan untuk login, lupa password, dan pengisian biodata PKL melalui token.

2. Dashboard admin
   Digunakan admin untuk mengelola instansi, PKL, modul, tugas, pengumpulan tugas, review hasil, dan pengaturan form biodata PKL.

3. Dashboard PKL
   Digunakan peserta PKL untuk melihat dashboard, membuka modul, melihat tugas, mengumpulkan tugas, dan memperbarui profil.

Jadi kalau backend menjawab pertanyaan "sistem ini mengelola apa", maka frontend menjawab pertanyaan "siapa melihat apa, dari halaman mana, dan bagaimana interaksinya".

Kalimat singkat yang aman untuk seminar:

"Frontend SIMMAG ODC saya bangun sebagai server-rendered multipage app. Setiap halaman utama dirender oleh CodeIgniter melalui view PHP, lalu saya tambahkan JavaScript modular untuk validasi, filter, wizard, AJAX, dan pengalaman pengguna yang lebih responsif."

---

## 2. Karakter Arsitektur Frontend

Karakter frontend proyek ini:

- Bukan SPA
- Tidak memakai bundler seperti Vite/Webpack
- Tidak memakai framework frontend seperti React atau Vue
- View dirender langsung dari PHP di `app/Views`
- Asset statis disimpan di `public/assets`
- Banyak interaksi modern tetap ada melalui JavaScript modular per fitur

Artinya, pola kerjanya seperti ini:

```text
Controller CodeIgniter
  -> mengirim data ke View PHP
  -> View menghasilkan HTML awal
  -> browser memuat CSS/JS dari public/assets
  -> JS mengaktifkan interaksi seperti tab, filter, wizard, AJAX, modal, datepicker, select, dan toast
```

Ini penting untuk seminar karena sering muncul pertanyaan:

"Apakah ini website biasa atau aplikasi interaktif?"

Jawaban amannya:

"Ini bukan SPA penuh, tetapi juga bukan halaman statis biasa. Saya memakai pendekatan hybrid: render awal dilakukan server supaya alur lebih sederhana dan stabil, lalu interaksi penting dibuat dinamis dengan JavaScript."

---

## 3. Stack Frontend yang Dipakai

### 3.1 Teknologi inti

- HTML dari PHP View CodeIgniter
- CSS custom modular
- JavaScript vanilla + jQuery
- Font Awesome untuk icon
- Google Fonts Montserrat untuk tipografi utama

### 3.2 Library yang dipakai

- `jQuery`
  Untuk event handling, AJAX, manipulasi DOM, dan integrasi plugin.

- `DataTables`
  Untuk tabel data admin dan beberapa tabel PKL. Fungsinya sorting, pagination, responsive detail row, dan integrasi pencarian/filter.

- `Select2`
  Untuk combo box yang lebih nyaman, termasuk fitur tag input untuk menambah instansi atau kota baru.

- `Flatpickr`
  Untuk datepicker dan datetime picker pada biodata, profil, dan tugas.

- `SweetAlert2`
  Untuk toast, konfirmasi, warning, feedback sukses/gagal, dan dialog yang lebih rapi.

### 3.3 Ciri penting arsitektur asset

- Tidak ada proses build frontend terpisah.
- File CSS dan JS dimuat langsung dari folder `public/assets`.
- Sebagian library dimuat lewat CDN di view/layout.

Kalau ditanya "kenapa tidak memakai React atau bundler modern?", jawaban aman:

"Karena ruang lingkup proyek PKL ini lebih cocok dengan multipage app yang ringan, mudah dijalankan di shared hosting atau lingkungan sederhana, dan tetap cukup interaktif dengan jQuery serta plugin yang relevan."

---

## 4. Struktur Folder Frontend

## 4.1 Folder `app/Views`

Folder ini berisi semua template tampilan.

### Layout shared

| File | Fungsi |
| --- | --- |
| `app/Views/layouts/dashboard_layout.php` | shell utama dashboard admin dan PKL |
| `app/Views/layouts/header.php` | header atas dashboard |
| `app/Views/layouts/sidebar_admin.php` | sidebar role admin |
| `app/Views/layouts/sidebar_pkl.php` | sidebar role PKL |

### Halaman auth dan publik

| File | Fungsi |
| --- | --- |
| `app/Views/auth/login.php` | halaman login |
| `app/Views/auth/lupa_password.php` | halaman reset password berbasis OTP |
| `app/Views/biodata_pkl/index.php` | form biodata publik multi-step |
| `app/Views/biodata_pkl/ditutup.php` | halaman saat form biodata dinonaktifkan |
| `app/Views/biodata_pkl/sukses.php` | halaman sukses setelah biodata tersimpan |

### Halaman admin

| File | Fungsi |
| --- | --- |
| `app/Views/dashboard_admin/dashboard.php` | dashboard utama admin |
| `app/Views/dashboard_admin/data_modul/index.php` | manajemen kategori modul dan modul |
| `app/Views/dashboard_admin/manajemen_pkl/index.php` | wrapper manajemen PKL |
| `app/Views/dashboard_admin/manajemen_pkl/_tab_instansi.php` | tab data instansi |
| `app/Views/dashboard_admin/manajemen_pkl/_tab_pkl.php` | tab data PKL |
| `app/Views/dashboard_admin/manajemen_pkl/_form_tambah_pkl.php` | wizard tambah PKL |
| `app/Views/dashboard_admin/manajemen_pkl/detail_pkl.php` | detail PKL |
| `app/Views/dashboard_admin/manajemen_pkl/edit_pkl.php` | edit PKL |
| `app/Views/dashboard_admin/manajemen_tugas/penugasan/main_penugasan.php` | wrapper penugasan |
| `app/Views/dashboard_admin/manajemen_tugas/penugasan/_tab_kategori.php` | tab kategori tugas |
| `app/Views/dashboard_admin/manajemen_tugas/penugasan/_tab_tugas.php` | tab daftar tugas |
| `app/Views/dashboard_admin/manajemen_tugas/penugasan/tugas_tambah.php` | wizard step 1 tambah tugas |
| `app/Views/dashboard_admin/manajemen_tugas/penugasan/tugas_sasaran.php` | wizard step 2 pilih sasaran |
| `app/Views/dashboard_admin/manajemen_tugas/penugasan/detail_tugas.php` | detail tugas |
| `app/Views/dashboard_admin/manajemen_tugas/penugasan/edit_tugas.php` | edit tugas |
| `app/Views/dashboard_admin/manajemen_tugas/pengumpulan/main_pengumpulan.php` | daftar pengumpulan |
| `app/Views/dashboard_admin/manajemen_tugas/pengumpulan/detail_pengumpulan.php` | review detail pengumpulan |
| `app/Views/dashboard_admin/profil/profil.php` | profil admin dan pengaturan link biodata |

### Halaman PKL

| File | Fungsi |
| --- | --- |
| `app/Views/dashboard_pkl/dashboard.php` | dashboard utama PKL |
| `app/Views/dashboard_pkl/modul/index.php` | daftar kategori modul |
| `app/Views/dashboard_pkl/modul/kategori.php` | daftar modul dalam kategori |
| `app/Views/dashboard_pkl/tugas/index.php` | daftar tugas individu dan kelompok |
| `app/Views/dashboard_pkl/tugas/detail.php` | detail dan pengumpulan tugas |
| `app/Views/dashboard_pkl/profil/profil.php` | profil PKL |

### Error views

| File | Fungsi |
| --- | --- |
| `app/Views/errors/html/*.php` | tampilan error browser |
| `app/Views/errors/html/debug.css` | styling halaman error |
| `app/Views/errors/html/debug.js` | script halaman error |

Error view bukan fitur utama sistem, tetapi tetap bagian dari frontend karena berpengaruh pada pengalaman pengguna saat terjadi error.

---

## 4.2 Folder `public/assets`

Folder ini berisi seluruh asset statis frontend.

### CSS inti

| File | Fungsi |
| --- | --- |
| `public/assets/css/core/variables.css` | token warna, shadow, radius, font, reset global |
| `public/assets/css/core/dashboard.css` | layout dashboard global, sidebar, topbar, card, responsive |

### CSS modul

| File | Fungsi |
| --- | --- |
| `public/assets/css/modules/auth.css` | login dan lupa password |
| `public/assets/css/modules/biodata_pkl.css` | form biodata publik |
| `public/assets/css/modules/admin/data_modul.css` | halaman data modul admin |
| `public/assets/css/modules/admin/manajemen_pkl.css` | halaman manajemen PKL admin |
| `public/assets/css/modules/admin/manajemen_tugas.css` | penugasan dan pengumpulan admin |
| `public/assets/css/modules/admin/profil.css` | profil admin |
| `public/assets/css/modules/pkl/modul.css` | modul PKL |
| `public/assets/css/modules/pkl/profil.css` | profil PKL |
| `public/assets/css/modules/pkl/tugas.css` | tugas PKL |

### JS inti

| File | Fungsi |
| --- | --- |
| `public/assets/js/core/dashboard.js` | sidebar, dropdown profile, mobile overlay, logout confirm, flash dismiss |
| `public/assets/js/core/simmag_validation.js` | helper validasi dan sanitasi lintas modul |

### JS modul auth dan publik

| File | Fungsi |
| --- | --- |
| `public/assets/js/modules/auth.js` | login AJAX |
| `public/assets/js/modules/auth_forgot_password.js` | reset password multi-step OTP |
| `public/assets/js/modules/biodata_pkl.js` | wizard biodata publik dan verifikasi email |

### JS modul admin

| File | Fungsi |
| --- | --- |
| `public/assets/js/modules/admin/data_modul.js` | tab data modul, DataTables, CRUD kategori/modul |
| `public/assets/js/modules/admin/manajemen_pkl.js` | tab instansi/PKL, filter, AJAX CRUD instansi |
| `public/assets/js/modules/admin/tambah_pkl.js` | wizard tambah PKL |
| `public/assets/js/modules/admin/detail_pkl.js` | interaksi halaman detail PKL |
| `public/assets/js/modules/admin/penugasan.js` | tab kategori tugas dan daftar tugas |
| `public/assets/js/modules/admin/tambah_tugas.js` | wizard tambah tugas step 1 |
| `public/assets/js/modules/admin/sasaran_tugas.js` | pilih sasaran tugas dan tim tugas |
| `public/assets/js/modules/admin/detail_tugas.js` | interaksi detail tugas |
| `public/assets/js/modules/admin/pengumpulan_tugas.js` | tab pengumpulan tugas |
| `public/assets/js/modules/admin/detail_pengumpulan.js` | review item pengumpulan |
| `public/assets/js/modules/admin/profil.js` | profil admin dan pengaturan biodata |

### JS modul PKL

| File | Fungsi |
| --- | --- |
| `public/assets/js/modules/pkl/modul.js` | pencarian kategori modul dan DataTables modul |
| `public/assets/js/modules/pkl/profil.js` | inline edit profil PKL |
| `public/assets/js/modules/pkl/tugas.js` | tab tugas, search, modal upload, drag-drop file |

### Image asset

| File | Fungsi |
| --- | --- |
| `public/assets/images/logo.png` | logo utama |
| `public/assets/images/logo_2.png` | logo alternatif |
| `public/assets/images/logo_hijau.png` | logo variasi hijau |
| `public/assets/images/logo_hor.png` | logo horizontal |
| `public/assets/images/bg_login.jpg` | background panel kiri auth |

---

## 5. Shared Layout Dashboard

Pusat layout dashboard ada di `app/Views/layouts/dashboard_layout.php`.

Peran file ini sangat penting karena menjadi kerangka semua halaman dashboard admin dan PKL.

Isi fungsi utamanya:

1. Membuat HTML dasar halaman dashboard.
2. Menyisipkan meta CSRF:
   - `csrf-token-name`
   - `csrf-token-hash`
3. Memuat library dari CDN:
   - Google Fonts
   - Font Awesome
   - DataTables
   - Select2
   - Flatpickr
   - SweetAlert2
   - jQuery
4. Memuat CSS inti:
   - `variables.css`
   - `dashboard.css`
5. Memuat CSS/JS tambahan dari setiap halaman melalui:
   - `$extra_css`
   - `$extra_js`
6. Memilih sidebar berdasarkan role session:
   - admin -> `Layouts/sidebar_admin`
   - pkl -> `Layouts/sidebar_pkl`
7. Menampilkan flash message sukses atau gagal.

Kesimpulan sederhananya:

`dashboard_layout.php` adalah "kerangka rumah", sedangkan view lain adalah "isi ruangan"-nya.

Catatan penting supaya tidak rancu:

- keberadaan meta CSRF di layout berarti frontend sudah disiapkan untuk membawa token
- tetapi itu belum otomatis berarti proteksi CSRF global sedang ditegakkan di server
- pengecekan akhirnya tetap bergantung pada filter `csrf` di backend

Kalau ditanya peran layout, jawaban aman:

"Saya membuat layout bersama supaya sidebar, header, asset global, dan struktur dashboard tidak diulang di semua halaman. Jadi setiap halaman fokus pada konten fiturnya saja."

---

## 6. Sidebar dan Header

### 6.1 Sidebar admin

`app/Views/layouts/sidebar_admin.php` memuat menu:

- Dashboard
- Manajemen PKL
- Data Modul
- Manajemen Tugas
  - Penugasan
  - Pengumpulan
- Profil
- Logout

Ciri penting:

- menu aktif dikontrol oleh variabel `$active_menu`
- submenu tugas bisa terbuka otomatis bila halaman aktif ada di penugasan atau pengumpulan
- nama user diambil dari session
- logout ditangani dengan konfirmasi JS

### 6.2 Sidebar PKL

`app/Views/layouts/sidebar_pkl.php` memuat menu:

- Dashboard
- Data Modul
- Manajemen Tugas
- Profil
- Logout

Ini menunjukkan desain role-based UI yang jelas: menu PKL lebih sempit karena fokusnya hanya melihat tugas, modul, dan profil.

### 6.3 Header

`app/Views/layouts/header.php` berisi:

- judul halaman
- subjudul atau breadcrumb sederhana
- logo horizontal

Fungsi utamanya adalah menjaga konteks pengguna: mereka selalu tahu sedang berada di modul apa.

---

## 7. Asset Core yang Paling Penting

## 7.1 `variables.css`

`public/assets/css/core/variables.css` berfungsi sebagai pusat token desain.

Isi utamanya:

- warna primer dan sekunder
- warna status
- background
- border
- shadow
- radius
- font family
- ukuran layout seperti sidebar width dan topbar height
- reset dasar CSS

Alasan file ini penting:

- memudahkan konsistensi warna antar halaman
- mempercepat perubahan tema
- mengurangi hardcoded color di tiap file CSS

Kalau ditanya "kenapa pakai CSS variable?", jawaban aman:

"Supaya tampilan konsisten, mudah dirawat, dan perubahan desain global bisa dilakukan dari satu tempat."

## 7.2 `dashboard.css`

`public/assets/css/core/dashboard.css` adalah stylesheet global dashboard.

Tugasnya:

- layout sidebar dan main content
- perilaku sidebar collapse
- topbar
- card umum
- stat card
- komponen daftar tugas dan modul
- flash message
- dropdown profile
- responsive desktop dan mobile

Artinya, modul CSS per halaman tidak perlu menulis ulang layout dasar dashboard dari nol.

## 7.3 `dashboard.js`

`public/assets/js/core/dashboard.js` berfungsi untuk pengalaman dashboard global.

Fitur utamanya:

- menyimpan status sidebar collapse di `localStorage`
- membuka dan menutup sidebar pada mobile
- hover expand di desktop
- toggle submenu
- profile dropdown
- konfirmasi logout dengan SweetAlert
- auto-dismiss flash message

Hal teknis yang bagus untuk dijelaskan:

- `localStorage` dipakai hanya untuk preferensi UI sidebar, bukan data sensitif
- state dashboard global dipisahkan dari JS modul halaman

## 7.4 `simmag_validation.js`

Ini adalah salah satu file frontend paling penting.

`public/assets/js/core/simmag_validation.js` menyediakan helper validasi dan sanitasi yang dipakai banyak halaman.

Fungsi yang diekspos ke `window.SimmagValidation` meliputi:

- `normalizeSpaces`
- `normalizeMultilineValue`
- `buildMissingFieldsMessage`
- `validatePatternField`
- `validateLooseField`
- `validateMultilinePatternField`
- `validateEmail`
- `validatePassword`
- `validatePhone`
- `validateNumberRange`
- `validateHttpsUrl`
- `validateDateOnly`
- `validateDateTime`
- `validatePklStartDate`
- `validatePklEndDate`
- `applyInputRules`
- `sanitizeLiveField`

Makna arsitektural file ini:

- validasi client-side tidak disalin berkali-kali di setiap halaman
- rule sanitasi nama, alamat, email, nomor telepon, dan lain-lain dibuat terpusat
- mengurangi inkonsistensi antar form

Kalau ditanya "bagaimana menjaga validasi frontend tetap konsisten?", jawaban aman:

"Saya membuat helper validasi global bernama `SimmagValidation`, jadi banyak field umum seperti nama, email, nomor WA, alamat, tanggal, dan URL mengikuti rule yang sama di berbagai modul."

---

## 8. Pola Umum Interaksi Frontend

Ada beberapa pola yang berulang di sistem ini.

## 8.1 Server-rendered dulu, lalu JS menghidupkan interaksi

Contoh:

- view menampilkan form dan tabel
- JS lalu mengaktifkan DataTables, Select2, atau Flatpickr

Jadi HTML dasarnya tetap ada walau plugin belum aktif.

## 8.2 Form biasa untuk proses yang sederhana atau final submit

Contoh:

- edit profil
- submit pengumpulan tugas
- beberapa create atau update yang tetap post ke controller

## 8.3 AJAX untuk proses yang butuh feedback cepat

Contoh:

- login
- lupa password OTP
- biodata OTP
- beberapa CRUD admin
- review pengumpulan

## 8.4 Custom filter di atas DataTables

Banyak halaman admin mematikan search bawaan DataTables lalu menggantinya dengan panel filter buatan sendiri.

Alasannya:

- UI lebih konsisten
- filter bisa lebih spesifik per kolom
- layout filter lebih mudah disesuaikan dengan kebutuhan halaman

## 8.5 Wizard untuk proses yang kompleks

Wizard dipakai pada:

- biodata publik
- tambah PKL admin
- tambah tugas admin

Alasannya:

- mencegah form terlalu panjang dalam satu layar
- memecah proses menjadi langkah yang mudah dipahami
- meminimalkan kesalahan input

---

## 9. Halaman Publik

## 9.1 Login

File utama:

- `app/Views/auth/login.php`
- `public/assets/css/modules/auth.css`
- `public/assets/js/modules/auth.js`

### Struktur UI

Halaman login dibagi dua panel:

1. Panel kiri
   Menampilkan branding perusahaan, deskripsi sistem, dan keunggulan sistem.

2. Panel kanan
   Menampilkan kartu login yang berisi:
   - username atau email
   - password
   - toggle tampilkan password
   - link lupa password
   - feedback error atau sukses

### Perilaku JS

`auth.js` menangani:

- toggle show atau hide password
- validasi field kosong
- penampilan inline error
- submit login via AJAX
- redirect setelah sukses

### Makna desain

Halaman login dibuat lebih presentatif dibanding form sederhana karena login adalah pintu masuk utama sistem.

Jawaban aman bila ditanya:

"Saya membagi halaman login menjadi sisi branding dan sisi form agar identitas sistem tetap terlihat, tetapi fokus pengguna tetap pada proses login."

## 9.2 Lupa Password

File utama:

- `app/Views/auth/lupa_password.php`
- `public/assets/css/modules/auth.css`
- `public/assets/js/modules/auth_forgot_password.js`

### Struktur UI

Halaman ini memakai `stepper` 3 langkah:

1. Input email
2. Verifikasi OTP
3. Input password baru

### Perilaku JS

`auth_forgot_password.js` menangani:

- kirim OTP via AJAX
- update CSRF hash dari response
- countdown masa berlaku OTP
- countdown lock jika terlalu banyak percobaan
- validasi OTP 6 digit
- validasi password baru
- reset state jika expired

### Nilai plus untuk seminar

Halaman ini menunjukkan frontend tidak hanya form biasa, tetapi memiliki state machine sederhana:

- state email
- state OTP
- state lock
- state verifikasi
- state submit reset

Jawaban aman:

"Reset password saya buat bertahap supaya lebih aman dan mudah dipahami user. Frontend mengatur state setiap langkah, sementara backend tetap memverifikasi OTP dan mengganti password."

## 9.3 Form Biodata PKL Publik

File utama:

- `app/Views/biodata_pkl/index.php`
- `public/assets/css/modules/biodata_pkl.css`
- `public/assets/js/modules/biodata_pkl.js`

Ini adalah salah satu halaman frontend paling kompleks di sistem.

### Struktur langkah

1. Data PKL
   - kategori mandiri atau instansi
   - kategori instansi
   - pilih instansi atau tambah instansi baru
   - pembimbing
   - jumlah anggota
   - nama kelompok
   - tanggal mulai dan akhir PKL

2. Biodata
   - biodata tiap anggota
   - accordion anggota
   - ketua kelompok diberi penanda

3. Konfirmasi
   - preview data
   - verifikasi email OTP
   - simpan pendaftaran

### Komponen yang dipakai

- `Flatpickr` untuk tanggal
- `Select2` untuk instansi dan kota
- `SweetAlert2` untuk toast dan feedback
- `SimmagValidation` untuk rule input

### Perilaku JS penting

`biodata_pkl.js` menangani:

- state wizard
- toggle kategori mandiri atau instansi
- filter instansi berdasarkan kategori
- mode instansi existing vs new
- render accordion biodata anggota secara dinamis
- preview nama anggota secara live
- validasi tanggal PKL
- check email
- kirim OTP
- verifikasi OTP
- refresh CSRF hash

### Hal yang menarik untuk seminar

1. Form publik ini tidak langsung menyimpan data.
   Data diverifikasi dulu melalui OTP email.

2. Jumlah anggota membuat field biodata dirender dinamis.
   Artinya frontend tidak hardcode hanya untuk satu orang.

3. Ada pemisahan yang jelas antara data kelompok dan data individu.

Jawaban aman:

"Form biodata saya pecah menjadi wizard tiga langkah agar user tidak kewalahan. Untuk PKL instansi, frontend bisa menangani beberapa anggota secara dinamis. Sebelum disimpan, email ketua diverifikasi dengan OTP agar data lebih valid."

## 9.4 Halaman Biodata Ditutup dan Sukses

File:

- `app/Views/biodata_pkl/ditutup.php`
- `app/Views/biodata_pkl/sukses.php`

Peran kedua halaman ini sederhana tetapi penting:

- `ditutup.php` memberi tahu bahwa form sedang tidak tersedia
- `sukses.php` memberi konfirmasi akhir setelah proses berhasil

Ini menunjukkan frontend memperhatikan seluruh siklus user, bukan hanya kondisi normal.

---

## 10. Dashboard Admin

Area admin paling kaya fitur dan paling besar cakupan frontend-nya.

## 10.1 Dashboard Admin

File:

- `app/Views/dashboard_admin/dashboard.php`

Fungsinya sebagai ringkasan cepat:

- statistik utama
- shortcut atau ringkasan area manajemen

Walau secara teknis bukan halaman paling kompleks, dashboard penting karena menjadi orientasi pertama admin setelah login.

## 10.2 Manajemen PKL

File utama:

- `app/Views/dashboard_admin/manajemen_pkl/index.php`
- `app/Views/dashboard_admin/manajemen_pkl/_tab_instansi.php`
- `app/Views/dashboard_admin/manajemen_pkl/_tab_pkl.php`
- `public/assets/css/modules/admin/manajemen_pkl.css`
- `public/assets/js/modules/admin/manajemen_pkl.js`
- `public/assets/js/modules/admin/tambah_pkl.js`

### Pola halaman

Halaman ini memakai tab utama:

1. Data Instansi
2. Data PKL

### Data Instansi

Fitur UI:

- tabel instansi
- custom filter
- form tambah atau edit
- Select2 kota
- AJAX store, update, delete

### Data PKL

Fitur UI:

- stat card:
  - aktif
  - selesai
  - nonaktif
- sub tab status
- tabel per status
- filter nama, kategori, instansi, tanggal
- tombol detail, toggle status, hapus

### Tambah PKL

`tambah_pkl.js` mengelola wizard tambah PKL dari sisi admin.

Mirip biodata publik, tetapi konteksnya admin:

- kategori PKL
- instansi
- anggota
- tanggal
- validasi email
- penyusunan payload

### Makna arsitektural

Halaman ini memisahkan master data:

- master instansi
- data peserta PKL

Ini bagus dijelaskan ke dosen karena menunjukkan pemodelan data tidak dicampur semua dalam satu tabel atau satu UI besar.

Jawaban aman:

"Saya pisahkan manajemen instansi dan PKL karena instansi adalah master data, sedangkan PKL adalah entitas operasional yang bisa berubah status. Dari sisi UI, admin tetap melihat keduanya dalam satu modul, tetapi secara logika tetap dipisah."

## 10.3 Data Modul Admin

File utama:

- `app/Views/dashboard_admin/data_modul/index.php`
- `public/assets/css/modules/admin/data_modul.css`
- `public/assets/js/modules/admin/data_modul.js`

### Struktur halaman

Ada dua tab:

1. Kategori Modul
2. Modul

### Fitur

- DataTables kategori
- form kategori yang bisa tampil atau sembunyi
- DataTables modul
- filter modul
- detail modul
- create, edit, delete modul
- dukungan asset file atau link
- update heading dan URL state sesuai mode

### Ciri teknis yang menarik

`data_modul.js` cukup rapi karena:

- ada `pageMeta` untuk sinkron judul halaman
- ada helper URL state
- ada helper CSRF
- ada helper validasi field
- tabel kategori dan tabel modul dikelola terpisah

### Kenapa ini penting saat seminar

Karena modul pembelajaran bukan hanya upload file, tetapi dikelola per kategori dan bisa berupa file maupun link. Dari sisi frontend, UI dibagi agar admin tidak bingung antara master kategori dan isi modul.

Jawaban aman:

"Saya membuat data modul dalam dua level, yaitu kategori dan isi modul, supaya pengelolaan materi lebih terstruktur. Frontend mengikuti struktur itu melalui dua tab terpisah."

## 10.4 Manajemen Tugas Admin - Penugasan

File utama:

- `app/Views/dashboard_admin/manajemen_tugas/penugasan/main_penugasan.php`
- `app/Views/dashboard_admin/manajemen_tugas/penugasan/_tab_kategori.php`
- `app/Views/dashboard_admin/manajemen_tugas/penugasan/_tab_tugas.php`
- `app/Views/dashboard_admin/manajemen_tugas/penugasan/tugas_tambah.php`
- `app/Views/dashboard_admin/manajemen_tugas/penugasan/tugas_sasaran.php`
- `app/Views/dashboard_admin/manajemen_tugas/penugasan/detail_tugas.php`
- `app/Views/dashboard_admin/manajemen_tugas/penugasan/edit_tugas.php`
- `public/assets/css/modules/admin/manajemen_tugas.css`
- `public/assets/js/modules/admin/penugasan.js`
- `public/assets/js/modules/admin/tambah_tugas.js`
- `public/assets/js/modules/admin/sasaran_tugas.js`
- `public/assets/js/modules/admin/detail_tugas.js`

### Pola halaman

Ada dua level besar:

1. Kategori Tugas
2. Data Tugas

### Kategori Tugas

UI ini mengelola:

- nama kategori tugas
- mode pengumpulan:
  - individu
  - kelompok

Artinya kategori tidak hanya label, tetapi ikut menentukan logika bisnis penugasan.

### Tambah Tugas step 1

`tugas_tambah.php` dan `tambah_tugas.js` mengelola:

- kategori tugas
- nama tugas
- deskripsi
- target jumlah item
- deadline

Setelah lolos validasi, data step 1 disimpan ke `sessionStorage` lalu user diarahkan ke step 2.

Ini poin seminar yang bagus:

- `sessionStorage` dipakai untuk menyimpan data wizard antar halaman
- bukan untuk data permanen
- hanya jembatan sementara sebelum final submit

### Pilih Sasaran step 2

`tugas_sasaran.php` dan `sasaran_tugas.js` mengelola tiga sasaran:

1. Individu
2. Kelompok
3. Tim Tugas

Fitur di step ini:

- load daftar PKL aktif via AJAX
- load kelompok aktif via AJAX
- load tim tugas via AJAX
- checkbox massal
- pencarian
- sinkronisasi mobile dan desktop
- pembuatan tim tugas baru
- counter sasaran terpilih

### Nilai desainnya

Frontend ini cukup matang karena:

- wizard memecah beban user
- data step 1 dan step 2 dipisahkan jelas
- sasaran tugas tidak dibatasi satu model saja
- admin bisa menugaskan ke individu, kelompok, atau tim

Jawaban aman:

"Saya memisahkan ketentuan tugas dan sasaran tugas ke dua langkah berbeda. Langkah pertama menjawab 'apa tugasnya', langkah kedua menjawab 'siapa targetnya'. Dengan begitu admin tidak bingung dan data yang dikirim ke backend juga lebih terstruktur."

## 10.5 Manajemen Tugas Admin - Pengumpulan

File utama:

- `app/Views/dashboard_admin/manajemen_tugas/pengumpulan/main_pengumpulan.php`
- `app/Views/dashboard_admin/manajemen_tugas/pengumpulan/detail_pengumpulan.php`
- `public/assets/js/modules/admin/pengumpulan_tugas.js`
- `public/assets/js/modules/admin/detail_pengumpulan.js`

### Halaman daftar pengumpulan

Tab:

- tugas mandiri
- tugas kelompok
- tim tugas

Fitur:

- DataTables
- search per tab
- URL state per tab
- responsive detail row

### Halaman detail pengumpulan

Salah satu halaman paling penting dari sisi proses bisnis.

`detail_pengumpulan.js` mengelola:

- tombol setujui
- tombol revisi
- form komentar revisi
- update status item
- refresh CSRF hash
- update tampilan item setelah direview

### Makna arsitektural

Ini menunjukkan frontend mendukung lifecycle tugas secara lengkap:

1. admin membuat tugas
2. PKL mengumpulkan
3. admin membuka detail
4. admin menyetujui atau meminta revisi

Jawaban aman:

"Frontend pengumpulan saya buat tiga level, yaitu daftar pengumpulan, detail pengumpulan, lalu review per item. Jadi admin tidak hanya melihat siapa yang mengumpulkan, tetapi juga bisa mengevaluasi hasilnya secara langsung."

## 10.6 Profil Admin

File utama:

- `app/Views/dashboard_admin/profil/profil.php`
- `public/assets/css/modules/admin/profil.css`
- `public/assets/js/modules/admin/profil.js`

### Tab utama

1. Bio Pribadi
2. Pengaturan Form Biodata PKL

### Fitur Bio Pribadi

- tampilan avatar dan info akun
- inline edit biodata
- inline edit password
- password strength indicator
- toggle show atau hide password
- URL param `?mode=` agar refresh tetap di mode edit

### Fitur Pengaturan Biodata PKL

- toggle aktif atau nonaktif form biodata
- tampilkan status terbuka atau tertutup
- tampilkan link token biodata
- copy link
- generate token baru

### Poin yang bagus dijelaskan

1. Profil admin tidak hanya data diri, tetapi juga pusat pengaturan onboarding PKL.
2. Inline edit dipilih agar user tidak pindah halaman hanya untuk perubahan kecil.
3. State edit disimpan ke URL param agar refresh tidak membingungkan user.

Jawaban aman:

"Halaman profil admin saya pakai bukan hanya untuk biodata, tetapi juga sebagai tempat pengaturan link form biodata PKL. Ini sengaja supaya proses onboarding calon PKL tetap dikendalikan dari satu tempat oleh admin."

---

## 11. Dashboard PKL

## 11.1 Dashboard PKL

File:

- `app/Views/dashboard_pkl/dashboard.php`

Fungsi UI:

- menampilkan statistik tugas:
  - total
  - selesai
  - menunggu atau revisi
  - belum dikirim
- menampilkan shortcut modul pembelajaran
- menampilkan daftar tugas terdekat

Nilai plusnya:

- dashboard PKL berorientasi pada aktivitas yang benar-benar dibutuhkan peserta
- tidak terlalu padat seperti dashboard admin

## 11.2 Modul PKL

File utama:

- `app/Views/dashboard_pkl/modul/index.php`
- `app/Views/dashboard_pkl/modul/kategori.php`
- `public/assets/css/modules/pkl/modul.css`
- `public/assets/js/modules/pkl/modul.js`

### Halaman daftar kategori

Fitur:

- search kategori
- count badge
- empty state
- card kategori dengan icon dan warna

### Halaman kategori modul

Fitur:

- DataTables daftar modul
- pencarian custom
- reset pencarian
- link ke file atau URL modul

### Alasan desain

Modul untuk PKL dibuat sederhana:

- pertama pilih kategori
- lalu lihat daftar modul di dalam kategori

Ini membantu user pemula karena tidak langsung dibanjiri daftar materi panjang.

## 11.3 Tugas PKL

File utama:

- `app/Views/dashboard_pkl/tugas/index.php`
- `app/Views/dashboard_pkl/tugas/detail.php`
- `public/assets/css/modules/pkl/tugas.css`
- `public/assets/js/modules/pkl/tugas.js`

### Halaman daftar tugas

Ada dua tab:

- tugas individu
- tugas kelompok

Fitur:

- pencarian tugas
- count per tab
- kartu tugas dengan status singkat
- empty state

### Halaman detail tugas

Fitur:

- detail tugas
- status pengumpulan
- daftar jawaban yang sudah terkirim
- modal upload atau kirim jawaban
- pilihan tipe jawaban:
  - link
  - file
- drag and drop file
- validasi ekstensi dan ukuran file
- dukungan revisi jika ada catatan admin

### Hal teknis menarik

- file upload maksimal 300 MB dicek di frontend
- ekstensi dibatasi
- modal bisa auto-open
- tab aktif disimpan di URL state

Jawaban aman:

"Di sisi PKL saya bedakan dulu antara daftar tugas dan detail tugas. Pada detail tugas, user bisa melihat status saat ini, file atau link yang sudah pernah dikirim, dan bila masih boleh submit maka bisa mengirim jawaban baru melalui modal agar fokus pengguna tetap di halaman yang sama."

## 11.4 Profil PKL

File utama:

- `app/Views/dashboard_pkl/profil/profil.php`
- `public/assets/css/modules/pkl/profil.css`
- `public/assets/js/modules/pkl/profil.js`

### Isi halaman

- hero informasi akun
- card durasi PKL
- informasi pribadi
- informasi instansi dan kelompok
- ubah password

### Fitur interaktif

- inline edit biodata
- inline edit password
- konfirmasi jika pindah section saat ada edit belum disimpan
- password strength indicator
- lazy init Flatpickr untuk tanggal lahir
- URL param `?mode=edit_biodata` atau `?mode=edit_password`

### Makna desain

Berbeda dari admin, profil PKL memisahkan data yang boleh diubah sendiri dan data yang readonly.

Contohnya:

- email dikunci
- role kelompok dikunci
- informasi instansi atau kelompok ditandai dikelola admin

Ini bagus untuk seminar karena menunjukkan kontrol hak akses juga diterapkan di UI, bukan hanya di backend.

Jawaban aman:

"Di profil PKL saya sengaja membedakan field yang bisa diubah sendiri dan field yang dikelola admin. Jadi user tidak salah paham terhadap data mana yang menjadi tanggung jawab sistem dan mana yang menjadi tanggung jawab admin."

---

## 12. Pola Styling Frontend

Ada beberapa pola styling yang konsisten di proyek ini.

## 12.1 Token desain terpusat

Semua warna, shadow, radius, dan font ditaruh di `variables.css`.

Manfaatnya:

- konsistensi visual
- mudah diubah
- file modul CSS jadi lebih fokus ke layout halaman

## 12.2 CSS dibagi antara `core` dan `modules`

- `core`
  untuk layout global dan aturan bersama

- `modules`
  untuk styling halaman tertentu

Ini adalah pemisahan yang baik untuk proyek tanpa bundler.

## 12.3 Desain dashboard dan publik dipisah

- halaman publik seperti login dan biodata punya styling sendiri
- area dashboard admin dan PKL memakai shell yang sama

Ini membuat pengalaman pengguna lebih tepat konteks:

- publik lebih fokus pada onboarding
- dashboard lebih fokus pada produktivitas

## 12.4 Perhatian pada responsive behavior

Contoh nyata:

- sidebar desktop bisa collapse, mobile bisa slide open
- DataTables memakai responsive child row
- tugas sasaran punya tampilan desktop dan mobile
- auth page punya mobile logo
- biodata dan dashboard punya layout grid yang menyesuaikan layar

Kalau ditanya "bagaimana dukungan mobile?", jawaban aman:

"Saya tidak membuat aplikasi mobile native, tetapi tampilan web-nya sudah saya buat responsif. Untuk tabel di layar kecil, saya tidak hanya mengecilkan tampilan, tetapi memindahkan detail ke mode expand agar tetap terbaca."

---

## 13. Pola JavaScript Frontend

Secara umum JavaScript di proyek ini dibagi menjadi tiga level:

1. Core JS
   Contoh: `dashboard.js`, `simmag_validation.js`

2. JS halaman publik
   Contoh: `auth.js`, `auth_forgot_password.js`, `biodata_pkl.js`

3. JS per modul admin atau PKL
   Contoh: `data_modul.js`, `manajemen_pkl.js`, `penugasan.js`, `tugas.js`, `profil.js`

### Ciri pendekatan yang dipakai

- banyak file dibungkus `$(document).ready(...)`
- beberapa helper memakai `window.<CONFIG>` dari view
- banyak endpoint dan data awal dikirim dari PHP ke JS lewat object global
- AJAX mayoritas memakai jQuery

### Kelebihan pendekatan ini

- sederhana dan cocok untuk proyek PKL
- mudah dipahami tanpa toolchain kompleks
- tiap halaman cukup mandiri

### Kelemahannya yang jujur

- file JS bisa panjang jika fitur bertambah
- tidak ada module bundler atau import modern
- beberapa helper masih diulang antar file

Jawaban aman bila ditanya kekurangan:

"Karena ini proyek PKL berbasis multipage app, saya memilih pendekatan yang sederhana dan stabil. Ke depan, refactor yang mungkin dilakukan adalah memecah helper yang berulang ke utility yang lebih terpusat atau memakai struktur module yang lebih formal."

---

## 14. Pola Komunikasi Frontend dan Backend

Frontend berkomunikasi dengan backend dalam beberapa bentuk.

## 14.1 Render data awal dari controller ke view

Contoh:

- daftar kategori modul
- daftar tugas
- data profil
- statistik dashboard

Ini cocok untuk halaman yang butuh render awal lengkap.

## 14.2 AJAX JSON

Contoh:

- login
- send OTP
- verify OTP
- beberapa CRUD admin
- review detail pengumpulan
- load sasaran tugas

Alasan penggunaan AJAX:

- feedback lebih cepat
- user tidak perlu reload penuh
- cocok untuk operasi kecil tapi sering

## 14.3 Hidden input dan meta CSRF

Frontend sudah menyiapkan pola CSRF melalui:

- `csrf_field()`
- meta tag `csrf-token-name`
- meta tag `csrf-token-hash`

Beberapa JS juga sudah menulis ulang hash CSRF setelah menerima response.

Yang perlu dipahami:

- token CSRF di frontend itu ibarat "tiket" yang disiapkan untuk dikirim kembali ke server
- tetapi tiket itu baru benar-benar berguna kalau server memeriksa tiket tersebut
- pada proyek ini, token sudah banyak disisipkan di form dan JavaScript, tetapi filter CSRF global di backend belum aktif
- jadi secara jujur, frontend sudah `ready for CSRF`, sedangkan enforcement global di backend belum sepenuhnya dinyalakan

Ini bagus dijelaskan secara jujur:

"Di frontend saya sudah siapkan pola integrasi token CSRF baik di form maupun AJAX. Jadi token bisa dikirim dan diperbarui. Tetapi saya juga paham bahwa perlindungan penuh baru terjadi kalau backend menjalankan verifikasi CSRF-nya."

## 14.4 SessionStorage dan LocalStorage

`sessionStorage`:

- dipakai pada wizard tambah tugas
- menyimpan data step 1 sebelum masuk step 2

`localStorage`:

- dipakai untuk status sidebar collapse

Artinya storage browser dipakai hanya untuk state UI sementara, bukan data sensitif.

---

## 15. Alur Frontend End-to-End

Berikut alur yang bisa kamu ceritakan saat seminar.

## 15.1 Login sampai masuk dashboard

1. User membuka `login.php`
2. User isi username atau email dan password
3. `auth.js` memvalidasi field kosong
4. Form dikirim via AJAX
5. Jika sukses, frontend redirect ke dashboard sesuai role

## 15.2 Lupa password

1. User masuk halaman reset password
2. Input email
3. OTP dikirim via AJAX
4. Frontend menampilkan countdown
5. User input OTP
6. Jika valid, tampil form password baru
7. Password baru dikirim via AJAX

## 15.3 Biodata PKL publik

1. User buka link token biodata
2. Isi data PKL
3. Isi biodata anggota
4. Cek konfirmasi
5. Verifikasi email ketua via OTP
6. Setelah verified, tombol simpan diaktifkan
7. Data disimpan dan user diarahkan ke halaman sukses

## 15.4 Admin membuat tugas

1. Admin masuk penugasan
2. Buka `tugas_tambah.php`
3. Isi ketentuan tugas
4. Data step 1 disimpan ke `sessionStorage`
5. Admin pindah ke step pilih sasaran
6. Admin pilih individu, kelompok, atau tim
7. Final submit ke backend

## 15.5 PKL mengumpulkan tugas

1. PKL buka daftar tugas
2. Pilih detail tugas
3. Baca status dan instruksi
4. Klik kumpulkan tugas
5. Pilih jenis jawaban link atau file
6. Submit jawaban
7. Admin mereview di halaman detail pengumpulan

---

## 16. Kelebihan Frontend Proyek Ini

Berikut hal-hal yang bisa kamu banggakan saat seminar.

1. Struktur frontend cukup rapi untuk ukuran proyek PKL.
   Ada pemisahan `layout`, `view`, `core asset`, dan `module asset`.

2. Role-based UI jelas.
   Admin dan PKL mendapat menu dan halaman yang berbeda.

3. Banyak proses kompleks dibuat bertahap.
   Contoh: biodata publik, tambah PKL, tambah tugas, reset password.

4. Validasi client-side terpusat.
   `SimmagValidation` membuat rule input lebih konsisten.

5. UX cukup diperhatikan.
   Ada toast, konfirmasi, empty state, step indicator, countdown, drag-drop, dan responsive table.

6. Tidak terlalu bergantung pada reload penuh.
   Banyak interaksi cepat memakai AJAX.

7. Struktur asset modular memudahkan perawatan.

---

## 17. Keterbatasan Frontend yang Jujur

Bagian ini penting jika dosen bertanya kritis.

1. Belum memakai module system modern.
   Masih banyak mengandalkan file JS per halaman dan object global.

2. Beberapa file JS cukup panjang.
   Contohnya modul biodata, data modul, manajemen PKL, dan sasaran tugas.

3. Ketergantungan pada CDN.
   Jika koneksi ke CDN bermasalah, asset pihak ketiga bisa ikut terdampak.

4. Validasi frontend tetap bukan pengganti validasi backend.
   Frontend hanya membantu pengalaman pengguna.

5. Belum ada build pipeline atau test frontend otomatis.

Jawaban aman:

"Karena ini proyek PKL, saya prioritaskan kestabilan, keterbacaan, dan kemudahan deployment. Jika dikembangkan lebih lanjut, perbaikannya bisa diarahkan ke modularisasi JS yang lebih modern, pengurangan ketergantungan CDN, dan automated testing frontend."

---

## 18. Pertanyaan Dosen yang Mungkin Muncul dan Jawaban Aman

## 18.1 Tentang arsitektur

### Q: Frontend ini pakai framework apa?

Jawaban aman:

"Frontend-nya tidak memakai framework SPA seperti React atau Vue. Saya menggunakan view PHP dari CodeIgniter 4 sebagai render utama, lalu interaksi dinamis saya tambahkan dengan JavaScript modular, jQuery, DataTables, Select2, Flatpickr, dan SweetAlert2."

### Q: Kenapa tidak membuat SPA?

Jawaban aman:

"Saya memilih pendekatan multipage server-rendered karena lebih sederhana untuk scope PKL, lebih mudah diintegrasikan dengan CodeIgniter, dan sudah cukup untuk kebutuhan sistem. Untuk interaksi yang butuh respons cepat, saya tetap gunakan AJAX."

### Q: Bagaimana frontend dan backend saling terhubung?

Jawaban aman:

"Data awal dikirim controller ke view saat render halaman. Untuk aksi yang lebih interaktif seperti login, OTP, filter data, atau review tugas, frontend memanggil endpoint backend melalui AJAX dan menerima JSON response."

## 18.2 Tentang desain UI

### Q: Kenapa halaman login dibagi dua panel?

Jawaban aman:

"Supaya branding sistem tetap terlihat, tetapi area form tetap fokus. Panel kiri untuk identitas dan informasi sistem, panel kanan untuk aksi login."

### Q: Kenapa biodata dibuat wizard?

Jawaban aman:

"Karena field-nya cukup banyak dan ada kemungkinan lebih dari satu anggota. Kalau semua ditampilkan sekaligus, user mudah bingung. Dengan wizard, user menyelesaikan proses per langkah."

### Q: Kenapa admin dan PKL punya UI berbeda?

Jawaban aman:

"Karena kebutuhan informasinya berbeda. Admin butuh halaman operasional yang lebih kompleks, sedangkan PKL butuh tampilan yang lebih fokus pada aktivitas pribadi seperti modul, tugas, dan profil."

## 18.3 Tentang validasi dan keamanan

### Q: Apakah validasi hanya di frontend?

Jawaban aman:

"Tidak. Frontend hanya membantu mencegah kesalahan input dan meningkatkan UX. Validasi utama tetap ada di backend."

### Q: Kenapa ada file validasi global?

Jawaban aman:

"Supaya rule input seperti nama, email, nomor WA, alamat, tanggal, dan URL konsisten di banyak halaman. Jadi saya tidak menulis ulang validasi yang sama di setiap file."

### Q: Apakah frontend mengelola CSRF?

Jawaban aman:

"Di sisi frontend saya sudah menyiapkan pola CSRF melalui hidden input dan meta token. Beberapa endpoint AJAX juga memperbarui hash token setelah response dari backend. Tetapi saya juga membedakan antara token yang tersedia di frontend dan enforcement di backend, karena verifikasi final tetap terjadi di server."

## 18.4 Tentang maintainability

### Q: Bagaimana cara kamu menjaga kode frontend tetap teratur?

Jawaban aman:

"Saya membagi asset menjadi `core` dan `modules`, lalu membagi view per area fitur seperti auth, biodata, admin, dan PKL. Dengan begitu file bersama dan file spesifik halaman tidak tercampur."

### Q: Bagaimana kalau sistem ini dikembangkan lebih lanjut?

Jawaban aman:

"Langkah berikutnya yang masuk akal adalah memecah helper JS yang berulang, menambah automated testing, mengurangi ketergantungan CDN, dan jika skala fitur makin besar baru dipertimbangkan module system yang lebih modern."

## 18.5 Tentang UX

### Q: Kenapa banyak toast dan popup?

Jawaban aman:

"Saya pakai toast untuk feedback cepat seperti reset filter atau sukses simpan, sedangkan popup dipakai untuk aksi yang butuh perhatian lebih seperti delete, logout, dan warning validasi. Jadi notifikasi disesuaikan dengan tingkat pentingnya."

### Q: Kenapa tabel admin memakai DataTables tapi search bawaan sering dimatikan?

Jawaban aman:

"Karena saya ingin panel filter yang lebih konsisten dan sesuai kebutuhan tiap halaman. Search bawaan DataTables bagus untuk umum, tetapi kebutuhan modul ini sering memerlukan filter per kolom atau filter dengan layout khusus."

## 18.6 Tentang pilihan teknis tertentu

### Q: Kenapa pakai `sessionStorage` di tambah tugas?

Jawaban aman:

"Karena proses tambah tugas dibagi dua halaman wizard. `sessionStorage` saya pakai sebagai penyimpanan sementara step 1 sebelum final submit di step 2. Data permanen tetap disimpan di backend saat proses selesai."

### Q: Kenapa pakai `localStorage`?

Jawaban aman:

"Hanya untuk preferensi tampilan sidebar collapse. Jadi bukan untuk data penting, hanya untuk kenyamanan pengguna."

### Q: Kenapa profil pakai inline edit?

Jawaban aman:

"Karena perubahan profil biasanya kecil dan spesifik. Inline edit membuat alurnya lebih cepat dan user tidak perlu pindah ke halaman edit terpisah."

---

## 19. Cara Menjelaskan Frontend Saat Presentasi

Kalau kamu harus menjelaskan frontend dalam 2 sampai 4 menit, narasi aman yang bisa dipakai adalah:

"Frontend SIMMAG ODC saya bangun dengan pendekatan server-rendered menggunakan view PHP di CodeIgniter 4. Saya membagi antarmuka menjadi tiga area, yaitu halaman publik, dashboard admin, dan dashboard PKL. Struktur tampilannya dibuat modular: layout global ada di folder `layouts`, sedangkan halaman fitur dipisah per modul di `app/Views`. Asset statis saya tempatkan di `public/assets`, lalu saya bagi menjadi CSS dan JavaScript inti serta modul per halaman.

Untuk sisi pengalaman pengguna, saya tidak hanya mengandalkan form biasa. Saya memakai DataTables untuk tabel admin, Select2 untuk dropdown yang lebih fleksibel, Flatpickr untuk input tanggal, SweetAlert2 untuk feedback, dan helper validasi global agar rule input konsisten. Proses yang kompleks saya buat bertahap, misalnya login OTP reset password, pengisian biodata PKL, tambah PKL, dan penugasan tugas. Jadi walaupun bukan SPA, frontend tetap interaktif, terstruktur, dan sesuai kebutuhan sistem PKL."

---

## 20. Cheat Sheet Hafalan Cepat

Kalau kamu perlu versi hafalan singkat:

- Frontend ini adalah `server-rendered multipage app`, bukan SPA.
- View ada di `app/Views`, asset ada di `public/assets`.
- Layout dashboard terpusat di `dashboard_layout.php`.
- Role admin dan PKL punya sidebar serta halaman berbeda.
- Library utama: jQuery, DataTables, Select2, Flatpickr, SweetAlert2.
- Validasi client-side dipusatkan di `simmag_validation.js`.
- Wizard dipakai untuk proses kompleks:
  - biodata publik
  - tambah PKL
  - tambah tugas
  - lupa password
- `sessionStorage` dipakai untuk wizard tambah tugas.
- `localStorage` dipakai untuk state sidebar.
- Halaman paling kompleks frontend:
  - biodata publik
  - manajemen PKL
  - data modul
  - penugasan tugas
  - detail tugas PKL
  - profil admin atau PKL

---

## 21. Kesimpulan

Frontend SIMMAG ODC bisa dijelaskan sebagai antarmuka berbasis CodeIgniter yang:

- terstruktur per role
- modular per fitur
- cukup interaktif walau bukan SPA
- memakai plugin yang tepat guna
- memperhatikan validasi, UX, dan alur kerja nyata admin maupun PKL

Kalau dosen bertanya secara umum, jawaban paling aman adalah:

"Frontend sistem ini saya rancang agar sederhana untuk dipelihara, jelas untuk tiap role, dan tetap interaktif pada proses yang memang memerlukannya. Jadi saya menggabungkan kekuatan render server untuk stabilitas dengan JavaScript modular untuk pengalaman pengguna."
