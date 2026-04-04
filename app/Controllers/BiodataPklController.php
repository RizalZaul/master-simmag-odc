<?php

namespace App\Controllers;

use App\Models\KelompokPklModel;
use App\Models\PklModel;
use App\Models\InstansiModel;
use App\Models\UserModel;
use App\Models\AppSettingsModel;
use App\Services\EmailService;

/**
 * BiodataPklController
 *
 * Menangani form biodata PKL publik (tanpa login).
 * Akses via token unik: GET /biodata-pkl/{token}
 *
 * Routes (tanpa filter auth):
 *   GET  biodata-pkl/(:segment)        → index($1)
 *   GET  biodata-pkl/sukses            → sukses()
 *   POST biodata-pkl/check-email       → checkEmail()
 *   POST biodata-pkl/send-otp          → sendOtp()
 *   POST biodata-pkl/verify-otp        → verifyOtp()
 *   POST biodata-pkl/store             → store()
 *   POST biodata-pkl/generate-token    → generateToken()  [admin only via ProfilAdminController]
 */
class BiodataPklController extends BaseController
{
    private const TOKEN_KEY = 'biodata_token';
    private const OTP_TTL   = 300; // 5 menit

    protected KelompokPklModel $kelompokModel;
    protected PklModel         $pklModel;
    protected InstansiModel    $instansiModel;
    protected UserModel        $userModel;
    protected AppSettingsModel $settingsModel;

    public function __construct()
    {
        $this->kelompokModel = new KelompokPklModel();
        $this->pklModel      = new PklModel();
        $this->instansiModel = new InstansiModel();
        $this->userModel     = new UserModel();
        $this->settingsModel = new AppSettingsModel();
        helper('tgl');
    }

    // ── Halaman Form Publik ─────────────────────────────────────────

    public function index(string $token = '')
    {
        if (! $this->isValidAccess($token)) {
            return view('biodata_pkl/ditutup', [
                'alasan' => $this->getCloseReason($token),
            ]);
        }

        $instansiList = $this->instansiModel->getAllFormatted();
        $kotaList     = $this->instansiModel->getKotaList();

        $data = [
            'token'        => $token,
            'instansiJson' => json_encode(array_values($instansiList), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'kotaListJson' => json_encode($kotaList, JSON_UNESCAPED_UNICODE),
            'instansiList' => $instansiList,
            'minMulai'     => date('Y-m-d', strtotime('+14 days')),
            'maxMulai'     => date('Y-m-d', strtotime('+3 months')),
            'kotaList'     => $kotaList,
        ];

        return view('biodata_pkl/index', $data);
    }

    // ── Halaman Sukses ──────────────────────────────────────────────

    public function sukses()
    {
        // Guard: hanya tampil jika memang baru submit (ada flash)
        $ok = session()->getFlashdata('biodata_sukses');
        if (! $ok) {
            return redirect()->to('/');
        }

        return view('biodata_pkl/sukses', [
            'kategori' => session()->getFlashdata('biodata_kategori') ?? 'mandiri',
        ]);
    }

    // ── Check Email Unik (AJAX) ─────────────────────────────────────

    public function checkEmail()
    {
        if (! $this->request->isAJAX()) {
            return $this->jsonError('Forbidden', 403);
        }

        $emails  = $this->request->getPost('emails') ?? [];
        $results = [];

        foreach ((array) $emails as $email) {
            $results[$email] = $this->userModel
                ->where('email', strtolower(trim($email)))
                ->countAllResults() > 0;
        }

        return $this->response->setJSON(['success' => true, 'data' => $results]);
    }

    // ── Kirim OTP via Email (AJAX) ──────────────────────────────────

    public function sendOtp()
    {
        if (! $this->request->isAJAX()) {
            return $this->jsonError('Forbidden', 403);
        }

        $token = (string) ($this->request->getPost('token') ?? '');
        if (! $this->isValidAccess($token)) {
            return $this->jsonError('Akses tidak valid atau form sudah ditutup.');
        }

        $email = strtolower(trim((string) ($this->request->getPost('email') ?? '')));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->jsonError('Format email tidak valid.');
        }

        // ── FITUR KEAMANAN: Rate Limiting Pengiriman OTP ──
        // Mengambil data throttle dari session berdasarkan email
        $throttleKey  = 'otp_throttle_' . md5($email);
        $throttleSess = session()->get($throttleKey) ?? ['count' => 0, 'lockout_time' => 0];

        // 1. Cek apakah user sedang dalam masa penalti (lockout)
        if (time() < $throttleSess['lockout_time']) {
            $waitMinutes = ceil(($throttleSess['lockout_time'] - time()) / 60);
            return $this->jsonError("Terlalu banyak permintaan. Silakan coba lagi dalam {$waitMinutes} menit.");
        }

        // 2. Cek apakah sudah mencapai batas maksimal kirim (misal 3 kali)
        if ($throttleSess['count'] >= 3) {
            // Kunci pengiriman selama 15 menit
            session()->set($throttleKey, [
                'count'        => 0, // Reset count setelah di-lock
                'lockout_time' => time() + (15 * 60) // 15 Menit
            ]);
            return $this->jsonError("Batas pengiriman OTP tercapai. Silakan coba lagi dalam 15 menit.");
        }

        // 3. Tambah hitungan percobaan (count)
        session()->set($throttleKey, [
            'count'        => $throttleSess['count'] + 1,
            'lockout_time' => 0
        ]);
        // ──────────────────────────────────────────────────

        $otp    = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiry = time() + self::OTP_TTL;

        // Simpan OTP di session
        session()->set('biodata_otp', [
            'code'     => $otp,
            'email'    => $email,
            'expiry'   => $expiry,
            'verified' => false,
            'attempt'  => 0,
        ]);

        // Kirim email OTP
        try {
            (new EmailService())->sendOtpBiodata($email, $otp);
        } catch (\Throwable $e) {
            log_message('error', '[BiodataPklController::sendOtp] ' . $e->getMessage());
            return $this->jsonError('Gagal mengirim OTP ke email. Coba beberapa saat lagi.');
        }

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Kode OTP dikirim ke ' . $email . '. Berlaku 5 menit.',
            'ttl'     => self::OTP_TTL,
        ]);
    }

    // ── Verifikasi OTP (AJAX) ───────────────────────────────────────

    public function verifyOtp()
    {
        if (! $this->request->isAJAX()) {
            return $this->jsonError('Forbidden', 403);
        }

        $token = (string) ($this->request->getPost('token') ?? '');
        if (! $this->isValidAccess($token)) {
            return $this->jsonError('Akses tidak valid atau form sudah ditutup.');
        }

        $inputOtp = trim((string) ($this->request->getPost('otp') ?? ''));
        $otpSess  = session()->get('biodata_otp');

        if (! $otpSess) {
            return $this->jsonError('Sesi OTP tidak ditemukan. Silakan kirim ulang OTP.');
        }

        // Batas percobaan: 5x
        $attempt = (int) ($otpSess['attempt'] ?? 0);
        if ($attempt >= 5) {
            session()->remove('biodata_otp');
            return $this->jsonError('Terlalu banyak percobaan. Silakan kirim ulang OTP.');
        }

        // Update attempt
        session()->set('biodata_otp', array_merge($otpSess, ['attempt' => $attempt + 1]));

        if (time() > (int) $otpSess['expiry']) {
            session()->remove('biodata_otp');
            return $this->jsonError('OTP sudah kadaluarsa. Silakan kirim ulang OTP.');
        }

        if ($inputOtp !== $otpSess['code']) {
            $sisa = 5 - ($attempt + 1);
            return $this->jsonError('Kode OTP salah. Sisa percobaan: ' . $sisa . 'x.');
        }

        // Tandai verified
        session()->set('biodata_otp', array_merge($otpSess, [
            'verified' => true,
            'attempt'  => $attempt + 1,
        ]));

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Email berhasil diverifikasi!',
        ]);
    }

    // ── Store: Simpan Semua Data PKL (AJAX, setelah OTP verified) ──

    public function store()
    {
        if (! $this->request->isAJAX()) {
            return $this->jsonError('Forbidden', 403);
        }

        $token = (string) ($this->request->getPost('token') ?? '');
        if (! $this->isValidAccess($token)) {
            return $this->jsonError('Akses tidak valid atau form sudah ditutup.');
        }

        $raw = $this->request->getPost('payload');
        if (! $raw) return $this->jsonError('Data tidak lengkap.');

        $payload = json_decode($raw, true);
        if (! $payload) return $this->jsonError('Format data tidak valid.');

        $validationMessage = $this->validateStorePayload($payload);
        if ($validationMessage !== null) {
            return $this->jsonError($validationMessage);
        }

        $payload = $this->normalizeStorePayload($payload);

        // Cek OTP sudah diverifikasi
        $otpSess = session()->get('biodata_otp');
        if (! $otpSess || ! ($otpSess['verified'] ?? false)) {
            return $this->jsonError('Verifikasi email belum selesai. Silakan verifikasi OTP terlebih dahulu.');
        }

        // Email OTP harus sesuai email ketua/mandiri (anggota index-0)
        $anggotaArr   = $payload['anggota'] ?? [];
        $emailKetua   = strtolower(trim($anggotaArr[0]['email'] ?? ''));
        $emailOtpSess = strtolower(trim($otpSess['email'] ?? ''));

        if ($emailKetua !== $emailOtpSess) {
            return $this->jsonError('Email OTP tidak sesuai dengan data yang dikirim. Lakukan verifikasi ulang.');
        }

        $db = \Config\Database::connect();
        $db->transStart();

        try {
            $kategori = $payload['kategori'] ?? 'mandiri';
            $tglMulai = $payload['tgl_mulai'] ?? null;
            $tglAkhir = $payload['tgl_akhir'] ?? null;

            // 1. Simpan instansi baru (jika ada)
            $idInstansi = null;
            if ($kategori === 'instansi') {
                $ins = $payload['instansi'] ?? [];
                if (! empty($ins['is_new'])) {
                    $idInstansi = $this->instansiModel->insert([
                        'kategori_instansi' => InstansiModel::toDbValue($ins['kategori_label'] ?? ''),
                        'nama_instansi'     => $ins['nama']   ?? '',
                        'alamat_instansi'   => $ins['alamat'] ?? '',
                        'kota_instansi'     => $ins['kota']   ?? '',
                    ]);
                } else {
                    $idInstansi = (int) ($ins['id'] ?? 0) ?: null;
                }
            }

            // 2. Simpan kelompok
            $idKelompok = $this->kelompokModel->insert([
                'id_instansi'      => $idInstansi,
                'nama_kelompok'    => (($payload['nama_kelompok'] ?? '') !== '' ? $payload['nama_kelompok'] : null),
                'nama_pembimbing'  => (($payload['nama_pembimbing'] ?? '') !== '' ? $payload['nama_pembimbing'] : null),
                'no_wa_pembimbing' => (($payload['no_wa_pembimbing'] ?? '') !== '' ? $payload['no_wa_pembimbing'] : null),
                'tgl_mulai'        => $tglMulai,
                'tgl_akhir'        => $tglAkhir,
                'status'           => 'aktif',
            ]);

            if (! $idKelompok) {
                throw new \RuntimeException('Gagal membuat data PKL. Data kelompok tidak dapat disimpan.');
            }

            // 3. Simpan tiap anggota
            $emailDataArr = [];
            $ketuaData    = null;

            foreach ($anggotaArr as $idx => $ang) {
                $role          = ($kategori === 'mandiri') ? null : ($idx === 0 ? 'ketua' : 'anggota');
                $passwordPlain = $this->generatePassword();
                $username      = $this->generateUsername($ang['email']);

                $idUser = $this->userModel->insert([
                    'email'    => strtolower(trim($ang['email'])),
                    'username' => $username,
                    'password' => password_hash($passwordPlain, PASSWORD_DEFAULT),
                    'role'     => 'pkl',
                    'status'   => 'aktif',
                ]);

                $this->pklModel->insert([
                    'id_user'        => $idUser,
                    'id_kelompok'    => $idKelompok,
                    'nama_lengkap'   => trim($ang['nama_lengkap']   ?? ''),
                    'nama_panggilan' => trim($ang['nama_panggilan'] ?? '') ?: null,
                    'tempat_lahir'   => trim($ang['tempat_lahir']   ?? '') ?: null,
                    'tgl_lahir'      => $ang['tgl_lahir']           ?? null,
                    'no_wa_pkl'      => trim($ang['no_wa']          ?? '') ?: null,
                    'jenis_kelamin'  => $ang['jenis_kelamin']       ?? null,
                    'alamat'         => trim($ang['alamat']         ?? '') ?: null,
                    'jurusan'        => trim($ang['jurusan']        ?? '') ?: null,
                    'role_kel_pkl'   => $role,
                ]);

                $angEmail = [
                    'nama_lengkap'   => $ang['nama_lengkap'],
                    'nama_panggilan' => $ang['nama_panggilan'] ?? null,
                    'tempat_lahir'   => $ang['tempat_lahir'] ?? null,
                    'tgl_lahir'      => $ang['tgl_lahir'] ?? null,
                    'no_wa'          => $ang['no_wa'] ?? null,
                    'jenis_kelamin'  => $ang['jenis_kelamin'] ?? null,
                    'alamat'         => $ang['alamat'] ?? null,
                    'email'          => strtolower(trim($ang['email'])),
                    'username'       => $username,
                    'password_plain' => $passwordPlain,
                    'tgl_mulai'      => $tglMulai,
                    'tgl_akhir'      => $tglAkhir,
                    'role'           => $role,
                ];

                $emailDataArr[] = $angEmail;
                if ($role === 'ketua') $ketuaData = $angEmail;
            }

            $db->transComplete();

            if (! $db->transStatus()) {
                throw new \Exception('Transaksi database gagal.');
            }

            // 4. Kirim email info login ke setiap anggota
            $emailService = new EmailService();
            $namaInstansi = $kategori === 'instansi' ? ($payload['instansi']['nama'] ?? null) : null;

            foreach ($emailDataArr as $angEmail) {
                try {
                    $emailService->sendInfoLoginPkl($angEmail, $namaInstansi);
                } catch (\Throwable $e) {
                    log_message('error', '[BiodataPklController::store email] ' . $e->getMessage());
                }
            }

            // 5. Kirim rekapan ke ketua (jika kelompok > 1 orang)
            if ($kategori === 'instansi' && $ketuaData && count($emailDataArr) > 1) {
                try {
                    $emailService->sendRekapKetua($ketuaData, $emailDataArr, [
                        'nama_kelompok' => $payload['nama_kelompok'] ?? '-',
                        'nama_instansi' => $namaInstansi,
                        'tgl_mulai'     => $tglMulai,
                        'tgl_akhir'     => $tglAkhir,
                    ]);
                } catch (\Throwable $e) {
                    log_message('error', '[BiodataPklController::store rekap] ' . $e->getMessage());
                }
            }

            // Bersihkan sesi OTP
            session()->remove('biodata_otp');

            // Flash untuk halaman sukses
            session()->setFlashdata('biodata_sukses', true);
            session()->setFlashdata('biodata_kategori', $kategori);

            $waText = $this->buildWaText($payload, $emailDataArr, $namaInstansi);

            return $this->response->setJSON([
                'success'  => true,
                'message'  => 'Pendaftaran berhasil! Info login dikirim ke email masing-masing.',
                'redirect' => base_url('biodata-pkl/sukses'),
                'wa_url'   => 'https://wa.me/6285700978744?text=' . urlencode($waText),
            ]);
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', '[BiodataPklController::store] ' . $e->getMessage());
            return $this->jsonError('Gagal menyimpan data: ' . $e->getMessage());
        }
    }

    // ── Helpers ─────────────────────────────────────────────────────

    /**
     * Cek apakah token valid DAN form aktif.
     */
    private function isValidAccess(string $token): bool
    {
        if (trim($token) === '') return false;
        $formAktif   = $this->settingsModel->getValue('form_biodata_aktif') === '1';
        $validToken  = (string) ($this->settingsModel->getValue(self::TOKEN_KEY) ?? '');
        return $formAktif && $validToken !== '' && hash_equals($validToken, $token);
    }

    /**
     * Alasan form ditutup untuk tampil di halaman ditutup.
     */
    private function getCloseReason(string $token): string
    {
        $formAktif  = $this->settingsModel->getValue('form_biodata_aktif') === '1';
        $validToken = (string) ($this->settingsModel->getValue(self::TOKEN_KEY) ?? '');

        if ($validToken === '') return 'belum_ada_token';
        if (! $formAktif) return 'form_nonaktif';
        return 'token_invalid';
    }

    private function generatePassword(): string
    {
        return 'Pkl@' . str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    private function generateUsername(string $email): string
    {
        $base    = strtolower(explode('@', $email)[0]);
        $base    = preg_replace('/[^a-z0-9_]/', '', $base) ?? 'pkl';
        $base    = $base !== '' ? $base : 'pkl';
        $username = $base;
        $counter  = 1;

        while ($this->userModel->where('username', $username)->countAllResults() > 0) {
            $username = $base . random_int(100, 999);
            if (++$counter > 20) break;
        }

        return $username;
    }

    private function buildWaText(array $payload, array $anggota, ?string $namaInstansi): string
    {
        $kategori = $payload['kategori'] ?? 'mandiri';
        $lines    = ["🎓 *PENDAFTARAN PKL BARU — SIMMAG ODC*", ''];

        $lines[] = "📋 *Data PKL*";
        $lines[] = "Kategori : " . ($kategori === 'instansi' ? 'Instansi' : 'Mandiri');
        if ($namaInstansi) {
            $lines[] = "Instansi : " . $namaInstansi;
        }
        if (! empty($payload['nama_kelompok'])) {
            $lines[] = "Kelompok : " . $payload['nama_kelompok'];
        }
        if (! empty($payload['nama_pembimbing'])) {
            $lines[] = "Pembimbing : " . $payload['nama_pembimbing'];
        }
        if (! empty($payload['no_wa_pembimbing'])) {
            $lines[] = "WA Pembimbing : " . $payload['no_wa_pembimbing'];
        }
        $lines[] = "Periode  : " . tglShortIndo($payload['tgl_mulai']) . " s/d " . tglShortIndo($payload['tgl_akhir']);
        $lines[] = '';

        $totalAnggota = count($anggota);

        foreach ($anggota as $idx => $ang) {
            $no   = $idx + 1;
            $role = ($ang['role'] ?? '') === 'ketua' ? ' (Ketua)' : '';

            $lines[] = $totalAnggota > 1
                ? "👤 *Biodata {$no}{$role}*"
                : "👤 *Biodata*";
            $lines[] = "Nama Lengkap : " . ($ang['nama_lengkap'] ?? '-');
            $lines[] = "Panggilan    : " . (($ang['nama_panggilan'] ?? '') !== '' ? $ang['nama_panggilan'] : '-');
            $lines[] = "TTL          : " . $this->formatTempatTanggalLahir($ang['tempat_lahir'] ?? null, $ang['tgl_lahir'] ?? null);
            $lines[] = "No WA        : " . (($ang['no_wa'] ?? '') !== '' ? $ang['no_wa'] : '-');
            $lines[] = "Jenis Kelamin: " . $this->formatJenisKelamin($ang['jenis_kelamin'] ?? null);
            $lines[] = "Alamat       : " . (($ang['alamat'] ?? '') !== '' ? $ang['alamat'] : '-');
            $lines[] = "Username     : " . ($ang['username'] ?? '-');
            $lines[] = "Email        : " . ($ang['email'] ?? '-');
            $lines[] = '';
        }

        return implode("\n", $lines);
    }

    private function formatTempatTanggalLahir(?string $tempatLahir, ?string $tglLahir): string
    {
        $tempat = trim((string) $tempatLahir);
        $tanggal = $tglLahir ? tglShortIndo($tglLahir) : '';

        if ($tempat !== '' && $tanggal !== '') {
            return $tempat . ', ' . $tanggal;
        }

        if ($tempat !== '') {
            return $tempat;
        }

        return $tanggal !== '' ? $tanggal : '-';
    }

    private function formatJenisKelamin(?string $jenisKelamin): string
    {
        return match (strtoupper((string) $jenisKelamin)) {
            'L' => 'Laki-laki',
            'P' => 'Perempuan',
            default => '-',
        };
    }

    private function validateStorePayload(array $payload): ?string
    {
        $kategori = (string) ($payload['kategori'] ?? 'mandiri');
        $tglMulai = (string) ($payload['tgl_mulai'] ?? '');
        $tglAkhir = (string) ($payload['tgl_akhir'] ?? '');
        $totalRequiredStep1 = 2;
        $missingStep1 = [];

        if (trim($tglMulai) === '') {
            $missingStep1[] = 'Tanggal Mulai PKL';
        }
        if (trim($tglAkhir) === '') {
            $missingStep1[] = 'Tanggal Akhir PKL';
        }

        $instansiData = is_array($payload['instansi'] ?? null) ? $payload['instansi'] : [];
        $isInstansi = $kategori === 'instansi';
        $isNewInstansi = $isInstansi && !empty($instansiData['is_new']);

        if ($isInstansi) {
            $totalRequiredStep1 += 6 + ($isNewInstansi ? 2 : 0);

            if (trim((string) ($instansiData['kategori_label'] ?? '')) === '') {
                $missingStep1[] = 'Kategori Instansi';
            }
            if (trim((string) ($instansiData['nama'] ?? '')) === '' && (int) ($instansiData['id'] ?? 0) < 1) {
                $missingStep1[] = 'Nama Instansi';
            }
            if (trim((string) ($payload['nama_pembimbing'] ?? '')) === '') {
                $missingStep1[] = 'Nama Pembimbing';
            }
            if (trim((string) ($payload['no_wa_pembimbing'] ?? '')) === '') {
                $missingStep1[] = 'No WA Pembimbing';
            }
            if (trim((string) ($payload['jumlah_anggota'] ?? '')) === '') {
                $missingStep1[] = 'Jumlah Anggota PKL';
            }
            if (trim((string) ($payload['nama_kelompok'] ?? '')) === '') {
                $missingStep1[] = 'Nama Kelompok';
            }
            if ($isNewInstansi) {
                if (trim((string) ($instansiData['alamat'] ?? '')) === '') {
                    $missingStep1[] = 'Alamat Instansi Baru';
                }
                if (trim((string) ($instansiData['kota'] ?? '')) === '') {
                    $missingStep1[] = 'Kota Instansi Baru';
                }
            }
        }

        if ($missingStep1 !== []) {
            return $this->buildMissingFieldsMessage($missingStep1, $totalRequiredStep1);
        }

        $fieldError = $this->validatePklStartDate($tglMulai)
            ?? $this->validatePklEndDate($tglMulai, $tglAkhir);

        if ($isInstansi) {
            $instansiName = (string) ($instansiData['nama'] ?? '');
            $jumlahAnggota = trim((string) ($payload['jumlah_anggota'] ?? ''));
            $fieldError = $fieldError
                ?? ($instansiName !== '' ? $this->validatePatternField('Nama Instansi', $instansiName, 2, 100, "/^[\\p{L}0-9\\s'.()\\-]+$/u", 'huruf, angka, spasi, apostrof, tanda hubung, tanda kurung, dan titik') : null)
                ?? ($isNewInstansi ? $this->validatePatternField('Alamat Instansi Baru', (string) ($instansiData['alamat'] ?? ''), 5, 100, "/^[\\p{L}0-9\\s'.,\\-\\/#+]+$/u", 'huruf, angka, spasi, apostrof, tanda hubung, titik, koma, garis miring, dan tanda angka (#)') : null)
                ?? ($isNewInstansi ? $this->validatePatternField('Kota Instansi Baru', (string) ($instansiData['kota'] ?? ''), 1, 50, "/^[\\p{L}\\s]+$/u", 'huruf dan spasi') : null)
                ?? $this->validatePatternField('Nama Pembimbing', (string) ($payload['nama_pembimbing'] ?? ''), 1, 100, "/^[\\p{L}\\s.,'-]+$/u", 'huruf, spasi, titik, koma, apostrof, dan tanda hubung')
                ?? $this->validateWhatsappNumber((string) ($payload['no_wa_pembimbing'] ?? ''), 'No WA Pembimbing')
                ?? $this->validateNumberRange('Jumlah Anggota PKL', $jumlahAnggota, 1, 10);

            if ($fieldError === null && (int) $jumlahAnggota < 1) {
                $fieldError = 'Jumlah Anggota PKL minimal 1.';
            }

            $fieldError = $fieldError
                ?? $this->validateLooseTextField('Nama Kelompok', (string) ($payload['nama_kelompok'] ?? ''), 5, 20);
        }

        if ($fieldError !== null) {
            return $fieldError;
        }

        $anggotaArr = is_array($payload['anggota'] ?? null) ? $payload['anggota'] : [];
        if ($anggotaArr === []) {
            return 'Data anggota harus diisi.';
        }

        if ($isInstansi) {
            $jumlahAnggota = (int) ($payload['jumlah_anggota'] ?? 0);
            if ($jumlahAnggota > 0 && count($anggotaArr) !== $jumlahAnggota) {
                return 'Jumlah biodata anggota tidak sesuai dengan Jumlah Anggota PKL.';
            }
        }

        $messages = [];
        $emails = [];
        foreach ($anggotaArr as $index => $anggota) {
            $label = $isInstansi ? 'Anggota ' . ($index + 1) : 'Data Diri';
            $missingAnggota = [];
            $namaLengkap = (string) ($anggota['nama_lengkap'] ?? '');
            $namaPanggilan = (string) ($anggota['nama_panggilan'] ?? '');
            $tempatLahir = (string) ($anggota['tempat_lahir'] ?? '');
            $tglLahir = (string) ($anggota['tgl_lahir'] ?? '');
            $alamat = (string) ($anggota['alamat'] ?? '');
            $noWa = (string) ($anggota['no_wa'] ?? '');
            $email = (string) ($anggota['email'] ?? '');
            $jenisKelamin = trim((string) ($anggota['jenis_kelamin'] ?? ''));
            $jurusan = (string) ($anggota['jurusan'] ?? '');

            if (trim($namaLengkap) === '') {
                $missingAnggota[] = 'Nama Lengkap';
            }
            if (trim($namaPanggilan) === '') {
                $missingAnggota[] = 'Nama Panggilan';
            }
            if (trim($tempatLahir) === '') {
                $missingAnggota[] = 'Tempat Lahir';
            }
            if (trim($tglLahir) === '') {
                $missingAnggota[] = 'Tanggal Lahir';
            }
            if (trim($alamat) === '') {
                $missingAnggota[] = 'Alamat';
            }
            if (trim($noWa) === '') {
                $missingAnggota[] = 'No WA';
            }
            if (trim($email) === '') {
                $missingAnggota[] = 'Email';
            }
            if ($jenisKelamin === '') {
                $missingAnggota[] = 'Jenis Kelamin';
            }
            if ($isInstansi && trim($jurusan) === '') {
                $missingAnggota[] = 'Jurusan';
            }

            $this->appendMissingFieldGroup($messages, $label, $missingAnggota, $isInstansi ? 9 : 8);
            if ($missingAnggota !== []) {
                continue;
            }

            $memberError = $this->validatePatternField('Nama Lengkap', $namaLengkap, 1, 100, "/^[\\p{L}\\s.,'-]+$/u", 'huruf, spasi, titik, koma, apostrof, dan tanda hubung')
                ?? $this->validateLooseTextField('Nama Panggilan', $namaPanggilan, 1, 10)
                ?? $this->validatePatternField('Tempat Lahir', $tempatLahir, 1, 50, "/^[\\p{L}\\s]+$/u", 'huruf dan spasi')
                ?? $this->validateBirthDateValue($tglLahir)
                ?? $this->validatePatternField('Alamat', $alamat, 5, 100, "/^[\\p{L}0-9\\s'.,\\-\\/#+]+$/u", 'huruf, angka, spasi, apostrof, tanda hubung, titik, koma, garis miring, dan tanda angka (#)')
                ?? $this->validateWhatsappNumber($noWa, 'No WA')
                ?? $this->validateEmailAddress($email)
                ?? ($jenisKelamin === '' ? 'Jenis Kelamin wajib diisi.' : null)
                ?? ($isInstansi ? $this->validatePatternField('Jurusan', $jurusan, 2, 100, "/^[\\p{L}\\s.()\\-]+$/u", 'huruf, spasi, titik, tanda hubung, dan tanda kurung') : null);

            if ($memberError !== null) {
                $messages[] = $label . ': ' . $memberError;
                continue;
            }

            $normalizedEmail = strtolower(trim($email));
            if (in_array($normalizedEmail, $emails, true)) {
                $messages[] = 'Setiap anggota harus menggunakan email yang berbeda.';
                continue;
            }

            $emails[] = $normalizedEmail;
            if ($this->userModel->where('email', $normalizedEmail)->countAllResults() > 0) {
                $messages[] = $label . ': Email sudah digunakan akun lain.';
            }
        }

        if ($messages !== []) {
            return implode(' ', array_values(array_unique($messages)));
        }

        return null;
    }

    private function normalizeStorePayload(array $payload): array
    {
        $payload['tgl_mulai'] = trim((string) ($payload['tgl_mulai'] ?? ''));
        $payload['tgl_akhir'] = trim((string) ($payload['tgl_akhir'] ?? ''));
        $payload['nama_kelompok'] = $this->normalizeSingleSpaces((string) ($payload['nama_kelompok'] ?? ''));
        $payload['nama_pembimbing'] = $this->normalizeSingleSpaces((string) ($payload['nama_pembimbing'] ?? ''));
        $payload['no_wa_pembimbing'] = trim((string) ($payload['no_wa_pembimbing'] ?? ''));
        $payload['jumlah_anggota'] = trim((string) ($payload['jumlah_anggota'] ?? ''));

        if (is_array($payload['instansi'] ?? null)) {
            $payload['instansi']['nama'] = $this->normalizeSingleSpaces((string) ($payload['instansi']['nama'] ?? ''));
            $payload['instansi']['alamat'] = $this->normalizeSingleSpaces((string) ($payload['instansi']['alamat'] ?? ''));
            $payload['instansi']['kota'] = $this->normalizeSingleSpaces((string) ($payload['instansi']['kota'] ?? ''));
            $payload['instansi']['kategori_label'] = trim((string) ($payload['instansi']['kategori_label'] ?? ''));
        }

        $payload['anggota'] = array_map(function ($anggota) {
            $anggota = is_array($anggota) ? $anggota : [];
            $anggota['nama_lengkap'] = $this->normalizeSingleSpaces((string) ($anggota['nama_lengkap'] ?? ''));
            $anggota['nama_panggilan'] = $this->normalizeSingleSpaces((string) ($anggota['nama_panggilan'] ?? ''));
            $anggota['tempat_lahir'] = $this->normalizeSingleSpaces((string) ($anggota['tempat_lahir'] ?? ''));
            $anggota['tgl_lahir'] = trim((string) ($anggota['tgl_lahir'] ?? ''));
            $anggota['no_wa'] = trim((string) ($anggota['no_wa'] ?? ''));
            $anggota['jenis_kelamin'] = trim((string) ($anggota['jenis_kelamin'] ?? ''));
            $anggota['alamat'] = $this->normalizeSingleSpaces((string) ($anggota['alamat'] ?? ''));
            $anggota['jurusan'] = $this->normalizeSingleSpaces((string) ($anggota['jurusan'] ?? ''));
            $anggota['email'] = strtolower(trim((string) ($anggota['email'] ?? '')));
            return $anggota;
        }, is_array($payload['anggota'] ?? null) ? $payload['anggota'] : []);

        if ($payload['nama_kelompok'] === '') {
            $payload['nama_kelompok'] = null;
        }
        if ($payload['nama_pembimbing'] === '') {
            $payload['nama_pembimbing'] = null;
        }
        if ($payload['no_wa_pembimbing'] === '') {
            $payload['no_wa_pembimbing'] = null;
        }

        return $payload;
    }

    private function validateBirthDateValue(?string $value): ?string
    {
        $error = $this->validateDateOnlyValue('Tanggal Lahir', $value);
        if ($error !== null) {
            return $error;
        }

        $timestamp = strtotime((string) $value);
        if ($timestamp === false || $timestamp > strtotime(date('Y-m-d'))) {
            return 'Tanggal Lahir tidak valid.';
        }

        return null;
    }

    private function jsonError(string $message, int $status = 422)
    {
        return $this->response->setStatusCode($status)
            ->setJSON(['success' => false, 'message' => $message]);
    }
}
