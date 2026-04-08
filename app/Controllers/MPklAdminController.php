<?php

namespace App\Controllers;

use App\Models\KelompokPklModel;
use App\Models\PklModel;
use App\Models\InstansiModel;
use App\Models\UserModel;
use App\Services\EmailService;

/**
 * MPklAdminController
 * Manajemen PKL: tab Data PKL, tambah, detail, edit, hapus, toggle status
 */
class MPklAdminController extends BaseController
{
    protected KelompokPklModel $kelompokModel;
    protected PklModel         $pklModel;
    protected InstansiModel    $instansiModel;
    protected UserModel        $userModel;

    public function __construct()
    {
        $this->kelompokModel = new KelompokPklModel();
        $this->pklModel      = new PklModel();
        $this->instansiModel = new InstansiModel();
        $this->userModel     = new UserModel();
        helper('tgl'); // load tgl_helper untuk tglShortIndo() & hitungDurasi()
    }

    // ── Halaman Tab Data PKL (redirect ke index instansi dengan tab=pkl) ──

    public function index()
    {
        $subTab = $this->request->getGet('sub') ?? 'aktif';
        if (! in_array($subTab, ['aktif', 'selesai', 'nonaktif'])) $subTab = 'aktif';

        $data = [
            'page_title'     => 'Manajemen PKL',
            'page_title_sub' => 'Data PKL',
            'active_menu'    => 'manajemen_pkl',
            'active_tab'     => 'pkl',
            'sub_tab'        => $subTab,
            'stat_aktif'     => $this->kelompokModel->countAktif(),
            'stat_selesai'   => $this->kelompokModel->countSelesai(),
            'stat_nonaktif'  => $this->kelompokModel->countNonAktif(),
            'pkl_aktif'      => $this->kelompokModel->getAktif(),
            'pkl_selesai'    => $this->kelompokModel->getSelesai(),
            'pkl_nonaktif'   => $this->kelompokModel->getNonAktif(),
            'instansiList'   => $this->instansiModel->getAllFormatted(),
            'extra_css'      => '<link rel="stylesheet" href="' . base_url('assets/css/modules/admin/manajemen_pkl.css') . '">',
            'extra_js'       => '<script src="' . base_url('assets/js/modules/admin/manajemen_pkl.js') . '?v=20260404-2"></script>',
        ];

        $data['content'] = view('dashboard_admin/manajemen_pkl/index', [
            ...$data,
            'instansiList_raw' => $this->instansiModel->getAllFormatted(),
            'kotaList'         => $this->instansiModel->getKotaList(),
            'mode'             => 'list',
            'edit_id'          => 0,
            'edit_data'        => null,
        ]);
        return view('Layouts/dashboard_layout', $data);
    }

    // ── Form Tambah PKL (3-step, dirender server) ────────────────────

    public function tambah()
    {
        $data = [
            'page_title'     => 'Manajemen PKL',
            'page_title_sub' => 'Tambah PKL',
            'active_menu'    => 'manajemen_pkl',
            'instansiList'   => $this->instansiModel->getAllFormatted(),
            'kotaList'       => $this->instansiModel->getKotaList(),
            'extra_css'      => '<link rel="stylesheet" href="' . base_url('assets/css/modules/admin/manajemen_pkl.css') . '">',
            'extra_js'       => '<script src="' . base_url('assets/js/modules/admin/tambah_pkl.js') . '"></script>',
        ];

        $data['content'] = view('dashboard_admin/manajemen_pkl/_form_tambah_pkl', $data);
        return view('Layouts/dashboard_layout', $data);
    }

    // ── Store PKL (submit akhir step 3) ─────────────────────────────

    public function store()
    {
        $raw = $this->request->getPost('payload');
        if (! $raw) return $this->jsonError('Data tidak lengkap.');

        $payload = json_decode($raw, true);
        if (! $payload) return $this->jsonError('Format data tidak valid.');

        $validationMessage = $this->validateStorePayload($payload);
        if ($validationMessage !== null) {
            return $this->jsonError($validationMessage);
        }

        $payload = $this->normalizeStorePayload($payload);

        $db = \Config\Database::connect();
        $db->transStart();

        try {
            $kategori   = $payload['kategori'] ?? 'mandiri';
            $tglMulai   = $payload['tgl_mulai'] ?? null;
            $tglAkhir   = $payload['tgl_akhir'] ?? null;
            $anggotaArr = $payload['anggota']   ?? [];

            // 1. Simpan instansi baru jika ada
            $idInstansi = null;
            if ($kategori === 'instansi') {
                $instansiData = $payload['instansi'] ?? [];
                if (! empty($instansiData['is_new'])) {
                    $idInstansi = $this->instansiModel->insert([
                        'kategori_instansi' => InstansiModel::toDbValue($instansiData['kategori_label']),
                        'nama_instansi'     => $instansiData['nama'],
                        'alamat_instansi'   => $instansiData['alamat'] ?? '',
                        'kota_instansi'     => $instansiData['kota']   ?? '',
                    ]);
                } else {
                    $idInstansi = (int) ($instansiData['id'] ?? 0);
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
            $emailData    = [];
            $ketuaData    = null;
            $allAnggotaEmail = [];

            foreach ($anggotaArr as $idx => $ang) {
                // BUG A3 FIX: mandiri hanya 1 orang → role NULL (bukan 'ketua').
                // Role 'ketua'/'anggota' hanya relevan untuk PKL Instansi (kelompok).
                $role = ($kategori === 'mandiri') ? null : ($idx === 0 ? 'ketua' : 'anggota');
                $passwordPlain = $this->generatePassword();
                $username      = $this->generateUsername($ang['email']);

                $idUser = $this->userModel->insert([
                    'email'    => $ang['email'],
                    'username' => $username,
                    'password' => password_hash($passwordPlain, PASSWORD_DEFAULT),
                    'role'     => 'pkl',
                    'status'   => 'aktif',
                ]);

                $this->pklModel->insert([
                    'id_user'        => $idUser,
                    'id_kelompok'    => $idKelompok,
                    'nama_lengkap'   => $ang['nama_lengkap'],
                    'nama_panggilan' => $ang['nama_panggilan'] ?? null,
                    'tempat_lahir'   => $ang['tempat_lahir']   ?? null,
                    'tgl_lahir'      => $ang['tgl_lahir']      ?? null,
                    'no_wa_pkl'      => $ang['no_wa']          ?? null,
                    'jenis_kelamin'  => $ang['jenis_kelamin']  ?? null,
                    'alamat'         => $ang['alamat']         ?? null,
                    'jurusan'        => $ang['jurusan']        ?? null,
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
                    'email'          => $ang['email'],
                    'username'       => $username,
                    'password_plain' => $passwordPlain,
                    'kategori'       => $kategori,
                    'tgl_mulai'      => $tglMulai,
                    'tgl_akhir'      => $tglAkhir,
                    'role'           => $role,
                ];

                $allAnggotaEmail[] = $angEmail;
                if ($role === 'ketua') $ketuaData = $angEmail;
            }

            $db->transComplete();

            if (! $db->transStatus()) {
                throw new \Exception('Transaksi gagal.');
            }

            // 4. Kirim email (setelah transaksi berhasil)
            $emailService = new EmailService();
            $namaInstansi = $kategori === 'instansi' ? ($payload['instansi']['nama'] ?? null) : null;

            foreach ($allAnggotaEmail as $ang) {
                try {
                    $emailService->sendInfoLoginPkl($ang, $namaInstansi);
                } catch (\Throwable $e) {
                    log_message('error', '[EmailService] ' . $e->getMessage());
                }
            }

            // Ketua instansi dapat rekapan
            if ($kategori === 'instansi' && $ketuaData && count($allAnggotaEmail) > 1) {
                try {
                    $emailService->sendRekapKetua($ketuaData, $allAnggotaEmail, [
                        'nama_kelompok' => $payload['nama_kelompok'] ?? '-',
                        'nama_instansi' => $namaInstansi,
                        'tgl_mulai'     => $tglMulai,
                        'tgl_akhir'     => $tglAkhir,
                    ]);
                } catch (\Throwable $e) {
                    log_message('error', '[EmailService rekap] ' . $e->getMessage());
                }
            }

            // 5. Buat pesan WA
            $waText = $this->buildWaText($payload, $allAnggotaEmail, $namaInstansi);

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Data PKL berhasil disimpan. Email info login telah dikirim.',
                'wa_url'  => 'https://wa.me/6285700978744?text=' . urlencode($waText),
            ]);
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', '[MPklAdminController::store] ' . $e->getMessage());
            return $this->jsonError('Gagal menyimpan data: ' . $e->getMessage());
        }
    }

    // ── Detail PKL ────────────────────────────────────────────────────

    public function detail(int $idPkl)
    {
        // BUG A2 FIX: baca sub-tab asal agar tombol Kembali mengarah ke sub-tab yang benar.
        // Sub-tab diteruskan dari _tab_pkl.php lewat query string ?sub=
        $fromSub = $this->request->getGet('sub') ?? 'aktif';
        if (! in_array($fromSub, ['aktif', 'selesai', 'nonaktif'])) $fromSub = 'aktif';

        $pkl = $this->pklModel->findByIdUser(0);
        // Ambil data pkl by id_pkl
        $pklRow = $this->pklModel->find($idPkl);
        if (! $pklRow) return redirect()->to(base_url('admin/manajemen-pkl?tab=pkl'));

        $kelompok = $this->kelompokModel->getDetailKelompok($pklRow['id_kelompok']);
        $anggota  = $this->kelompokModel->getAnggotaByKelompok($pklRow['id_kelompok']);
        // A5-FIX: @var \stdClass SEBELUM assignment — Intelephense P1006 butuh annotation
        // sebelum deklarasi agar inference-nya override, bukan setelah.
        /** @var \stdClass $user */
        $user     = $this->userModel->find($pklRow['id_user']);

        // Tentukan status dari query based
        $today   = date('Y-m-d');
        $tglAkhir = $kelompok['tgl_akhir'] ?? $today;
        $status   = $user->status === 'nonaktif' ? 'nonaktif'
            : ($tglAkhir < $today ? 'selesai' : 'aktif');

        $data = [
            'page_title'     => 'Manajemen PKL',
            'page_title_sub' => 'Detail PKL',
            'active_menu'    => 'manajemen_pkl',
            'pkl'            => $pklRow,
            'kelompok'       => $kelompok,
            'anggota'        => $anggota,
            'user'           => $user,
            'status'         => $status,
            // BUG A2 FIX: teruskan sub-tab asal agar tombol Kembali kembali ke sub-tab yang benar
            'from_sub'       => $fromSub,
            'extra_css'      => '<link rel="stylesheet" href="' . base_url('assets/css/modules/admin/manajemen_pkl.css') . '">',
            'extra_js'       => '<script src="' . base_url('assets/js/modules/admin/detail_pkl.js') . '"></script>',
        ];

        $data['content'] = view('dashboard_admin/manajemen_pkl/detail_pkl', $data);
        return view('Layouts/dashboard_layout', $data);
    }

    // ── Edit PKL (1 anggota) ─────────────────────────────────────────

    public function edit(int $idPkl)
    {
        $pklRow = $this->pklModel->find($idPkl);
        if (! $pklRow) return redirect()->to(base_url('admin/manajemen-pkl?tab=pkl'));

        $user     = $this->userModel->find($pklRow['id_user']);
        $kelompok = $this->kelompokModel->getDetailKelompok($pklRow['id_kelompok']);
        $anggota  = $this->kelompokModel->getAnggotaByKelompok($pklRow['id_kelompok']);

        $data = [
            'page_title'     => 'Manajemen PKL',
            'page_title_sub' => 'Edit PKL',
            'active_menu'    => 'manajemen_pkl',
            'pkl'            => $pklRow,
            'user'           => $user,
            'kelompok'       => $kelompok,
            'anggota'        => $anggota,
            'extra_css'      => '<link rel="stylesheet" href="' . base_url('assets/css/modules/admin/manajemen_pkl.css') . '">',
            // A1-FIX: detail_pkl.js sekarang berisi AJAX handler #formEditPkl, flatpickr,
            // toggle password, dan validasi password. Tidak pakai tambah_pkl.js agar tidak
            // terjadi crash saat Select2 dipanggil pada elemen yang tidak ada di edit page.
            'extra_js'       => '<script src="' . base_url('assets/js/modules/admin/detail_pkl.js') . '"></script>',
        ];

        $data['content'] = view('dashboard_admin/manajemen_pkl/edit_pkl', $data);
        return view('Layouts/dashboard_layout', $data);
    }

    // ── Update PKL (AJAX) ─────────────────────────────────────────────

    public function update(int $idPkl)
    {
        if (! $this->request->isAJAX()) return $this->jsonError('Forbidden', 403);

        $pklRow = $this->pklModel->find($idPkl);
        if (! $pklRow) return $this->jsonError('Data PKL tidak ditemukan.', 404);

        $kelompok = $this->kelompokModel->getDetailKelompok((int) ($pklRow['id_kelompok'] ?? 0));
        $isInstansi = ! empty($kelompok['id_instansi']);

        $namaLengkapRaw   = (string) ($this->request->getPost('nama_lengkap') ?? '');
        $namaPanggilanRaw = (string) ($this->request->getPost('nama_panggilan') ?? '');
        $tempatLahirRaw   = (string) ($this->request->getPost('tempat_lahir') ?? '');
        $tglLahirRaw      = (string) ($this->request->getPost('tgl_lahir') ?? '');
        $noWaRaw          = (string) ($this->request->getPost('no_wa') ?? '');
        $jenisKelamin     = trim((string) ($this->request->getPost('jenis_kelamin') ?? ''));
        $alamatRaw        = (string) ($this->request->getPost('alamat') ?? '');
        $jurusanRaw       = (string) ($this->request->getPost('jurusan') ?? '');
        $emailRaw         = (string) ($this->request->getPost('email') ?? '');
        $passwordBaruRaw  = (string) ($this->request->getPost('password_baru') ?? '');

        $missingFields = [];
        if (trim($namaLengkapRaw) === '') {
            $missingFields[] = 'Nama Lengkap';
        }
        if (trim($namaPanggilanRaw) === '') {
            $missingFields[] = 'Nama Panggilan';
        }
        if (trim($tempatLahirRaw) === '') {
            $missingFields[] = 'Tempat Lahir';
        }
        if (trim($tglLahirRaw) === '') {
            $missingFields[] = 'Tanggal Lahir';
        }
        if (trim($alamatRaw) === '') {
            $missingFields[] = 'Alamat';
        }
        if (trim($noWaRaw) === '') {
            $missingFields[] = 'No WA';
        }
        if ($jenisKelamin === '') {
            $missingFields[] = 'Jenis Kelamin';
        }
        if (trim($emailRaw) === '') {
            $missingFields[] = 'Email';
        }
        if ($isInstansi && trim($jurusanRaw) === '') {
            $missingFields[] = 'Jurusan';
        }

        $requiredFieldCount = $isInstansi ? 9 : 8;
        if ($missingFields !== []) {
            return $this->jsonError($this->buildMissingFieldsMessage($missingFields, $requiredFieldCount));
        }

        $fieldError = $this->validatePatternField('Nama Lengkap', $namaLengkapRaw, 1, 100, "/^[\\p{L}\\s.,'-]+$/u", 'huruf, spasi, titik, koma, apostrof, dan tanda hubung')
            ?? $this->validateLooseTextField('Nama Panggilan', $namaPanggilanRaw, 1, 10)
            ?? $this->validatePatternField('Tempat Lahir', $tempatLahirRaw, 1, 50, "/^[\\p{L}\\s]+$/u", 'huruf dan spasi')
            ?? $this->validateBirthDateValue($tglLahirRaw)
            ?? $this->validateMultilinePatternField('Alamat', $alamatRaw, 5, 100, "/^[\\p{L}0-9\\s'.,\\-\\/#+]+$/u", 'huruf, angka, spasi, apostrof, tanda hubung, titik, koma, garis miring, tanda angka (#), dan baris baru')
            ?? $this->validateWhatsappNumber($noWaRaw, 'No WA')
            ?? ($jenisKelamin === '' ? 'Jenis Kelamin wajib diisi.' : null)
            ?? ($isInstansi ? $this->validatePatternField('Jurusan', $jurusanRaw, 2, 100, "/^[\\p{L}\\s.()\\-]+$/u", 'huruf, spasi, titik, tanda hubung, dan tanda kurung') : null)
            ?? $this->validateEmailAddress($emailRaw);

        if ($fieldError !== null) {
            return $this->jsonError($fieldError);
        }

        $email = strtolower(trim($emailRaw));

        // Cek email unik (kecuali milik sendiri)
        if ($email && $this->userModel->where('email', $email)->where('id_user !=', $pklRow['id_user'])->countAllResults() > 0) {
            return $this->jsonError('Email sudah digunakan akun lain.');
        }

        $passwordBaru = trim($passwordBaruRaw);
        if ($passwordBaru !== '') {
            $passwordError = $this->validateStandardPassword($passwordBaru);
            if ($passwordError !== null) {
                return $this->jsonError($passwordError);
            }
        }

        $namaLengkap = $this->normalizeSingleSpaces($namaLengkapRaw);
        $namaPanggilan = $this->normalizeSingleSpaces($namaPanggilanRaw);
        $tempatLahir = $this->normalizeSingleSpaces($tempatLahirRaw);
        $alamat = $this->normalizeMultilineText($alamatRaw);
        $jurusan = $isInstansi ? $this->normalizeSingleSpaces($jurusanRaw) : null;
        $noWa = trim($noWaRaw);
        $tglLahir = trim($tglLahirRaw);

        // Update tabel pkl
        $this->pklModel->update($idPkl, [
            'nama_lengkap'   => $namaLengkap,
            'nama_panggilan' => $namaPanggilan,
            'tempat_lahir'   => $tempatLahir,
            'tgl_lahir'      => $tglLahir,
            'no_wa_pkl'      => $noWa,
            'jenis_kelamin'  => $jenisKelamin,
            'alamat'         => $alamat,
            'jurusan'        => $isInstansi ? $jurusan : null,
        ]);

        // Update tabel users
        $userUpdate = [];
        if ($email) $userUpdate['email'] = $email;
        if ($passwordBaru) $userUpdate['password'] = password_hash($passwordBaru, PASSWORD_DEFAULT);
        if ($userUpdate) $this->userModel->update($pklRow['id_user'], $userUpdate);

        return $this->response->setJSON(['success' => true, 'message' => 'Data PKL berhasil diperbarui.']);
    }

    // ── Delete PKL (AJAX) ─────────────────────────────────────────────

    public function delete(int $idPkl)
    {
        if (! $this->request->isAJAX()) return $this->jsonError('Forbidden', 403);

        $pklRow = $this->pklModel->find($idPkl);
        if (! $pklRow) return $this->jsonError('Data PKL tidak ditemukan.', 404);

        $idKelompok = $pklRow['id_kelompok'];
        $isKetua    = $pklRow['role_kel_pkl'] === 'ketua';
        $db         = \Config\Database::connect();

        $db->transStart();
        try {
            $affectedTimIds = [];

            if ($isKetua) {
                // Hapus semua anggota + kelompok
                $semuaAnggota = $this->kelompokModel->getAnggotaByKelompok($idKelompok);
                $affectedPklIds = array_values(array_unique(array_filter(array_map(
                    static fn($ang) => (int) ($ang['id_pkl'] ?? 0),
                    $semuaAnggota
                ))));
                $affectedTimIds = $this->findAffectedTimIds($db, $affectedPklIds);
                /** @var array $ang — getAnggotaByKelompok() returns array of associative arrays */
                foreach ($semuaAnggota as $ang) {
                    $this->pklModel->delete($ang['id_pkl']);
                    $this->userModel->delete($ang['id_user']);
                }
                $this->kelompokModel->delete($idKelompok);
            } else {
                // Hapus anggota ini saja
                $affectedTimIds = $this->findAffectedTimIds($db, [$idPkl]);
                $this->pklModel->delete($idPkl);
                $this->userModel->delete($pklRow['id_user']);

                // Jika ini anggota terakhir, hapus kelompok juga
                if (! $this->kelompokModel->masihAdaAnggotaLain($idKelompok, $idPkl)) {
                    $this->kelompokModel->delete($idKelompok);
                }
            }

            $this->cleanupEmptyTimTugas($db, $affectedTimIds);
            $db->transComplete();
        } catch (\Throwable $e) {
            $db->transRollback();
            return $this->jsonError('Gagal menghapus: ' . $e->getMessage());
        }

        return $this->response->setJSON(['success' => true, 'message' => 'Data PKL berhasil dihapus.']);
    }

    // ── Toggle Status User (AJAX) ─────────────────────────────────────

    public function toggleStatus(int $idPkl)
    {
        if (! $this->request->isAJAX()) return $this->jsonError('Forbidden', 403);

        $pklRow = $this->pklModel->find($idPkl);
        if (! $pklRow) return $this->jsonError('Data PKL tidak ditemukan.', 404);

        // A5-FIX: @var \stdClass SEBELUM assignment — Intelephense P1006
        /** @var \stdClass $user */
        $user       = $this->userModel->find($pklRow['id_user']);
        $newStatus  = $user->status === 'aktif' ? 'nonaktif' : 'aktif';
        $this->userModel->update($pklRow['id_user'], ['status' => $newStatus]);

        return $this->response->setJSON([
            'success'    => true,
            'new_status' => $newStatus,
            'message'    => 'Status PKL berhasil diubah menjadi ' . $newStatus . '.',
        ]);
    }

    // ── Check Email Unik (AJAX — step 2→3) ───────────────────────────

    public function checkEmail()
    {
        if (! $this->request->isAJAX()) return $this->jsonError('Forbidden', 403);

        $emails  = $this->request->getPost('emails') ?? [];
        $results = [];

        foreach ($emails as $email) {
            $exists = $this->userModel->where('email', $email)->countAllResults() > 0;
            $results[$email] = $exists;
        }

        return $this->response->setJSON(['success' => true, 'data' => $results]);
    }

    // ── Helpers ───────────────────────────────────────────────────────

    private function generatePassword(): string
    {
        return 'Pkl@' . str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    private function generateUsername(string $email): string
    {
        $base     = strtolower(explode('@', $email)[0]);
        $base     = preg_replace('/[^a-z0-9_]/', '', $base);
        $username = $base;
        $counter  = 1;

        while ($this->userModel->where('username', $username)->countAllResults() > 0) {
            $username = $base . random_int(100, 999);
            $counter++;
            if ($counter > 20) break; // failsafe
        }

        return $username;
    }

    private function buildWaText(array $payload, array $anggota, ?string $namaInstansi): string
    {
        $kategori = $payload['kategori'] ?? 'mandiri';
        $lines    = ["🎓 *PENDAFTARAN PKL BARU — PT Our Digital Creative*", ''];

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
        $lines[] = "";

        /** @var array $ang — $anggota berisi plain associative arrays dari store() */
        $totalAnggota = count($anggota);

        foreach ($anggota as $idx => $ang) {
            $no   = $idx + 1;
            $role = $ang['role'] === 'ketua' ? ' (Ketua)' : '';
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
            $lines[] = "";
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
                ?? $this->validateMultilinePatternField('Alamat', $alamat, 5, 100, "/^[\\p{L}0-9\\s'.,\\-\\/#+]+$/u", 'huruf, angka, spasi, apostrof, tanda hubung, titik, koma, garis miring, tanda angka (#), dan baris baru')
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
            $anggota['alamat'] = $this->normalizeMultilineText((string) ($anggota['alamat'] ?? ''));
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

    private function findAffectedTimIds($db, array $pklIds): array
    {
        $pklIds = array_values(array_unique(array_filter(array_map('intval', $pklIds))));
        if ($pklIds === []) {
            return [];
        }

        return array_values(array_unique(array_map(
            'intval',
            array_column(
                $db->table('anggota_tim_tugas')
                    ->select('id_tim')
                    ->whereIn('id_pkl', $pklIds)
                    ->get()
                    ->getResultArray(),
                'id_tim'
            )
        )));
    }

    private function cleanupEmptyTimTugas($db, array $timIds): void
    {
        $timIds = array_values(array_unique(array_filter(array_map('intval', $timIds))));
        if ($timIds === []) {
            return;
        }

        $emptyTimIds = array_values(array_unique(array_map(
            'intval',
            array_column(
                $db->table('tim_tugas t')
                    ->select('t.id_tim')
                    ->join('anggota_tim_tugas att', 'att.id_tim = t.id_tim', 'left')
                    ->whereIn('t.id_tim', $timIds)
                    ->groupBy('t.id_tim')
                    ->having('COUNT(att.id_pkl)', 0, false)
                    ->get()
                    ->getResultArray(),
                'id_tim'
            )
        )));

        if ($emptyTimIds !== []) {
            $db->table('tim_tugas')->whereIn('id_tim', $emptyTimIds)->delete();
        }
    }

    private function jsonError(string $message, int $status = 422)
    {
        return $this->response->setStatusCode($status)
            ->setJSON(['success' => false, 'message' => $message]);
    }
}
