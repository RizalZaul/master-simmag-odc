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
            'extra_js'       => '<script src="' . base_url('assets/js/modules/admin/manajemen_pkl.js') . '"></script>',
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
                'nama_kelompok'    => $payload['nama_kelompok']    ?? null,
                'nama_pembimbing'  => $payload['nama_pembimbing']  ?? null,
                'no_wa_pembimbing' => $payload['no_wa_pembimbing'] ?? null,
                'tgl_mulai'        => $tglMulai,
                'tgl_akhir'        => $tglAkhir,
                'status'           => 'aktif',
            ]);

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

        $email        = trim($this->request->getPost('email') ?? '');
        $passwordBaru = trim($this->request->getPost('password_baru') ?? '');

        // Cek email unik (kecuali milik sendiri)
        if ($email && $this->userModel->where('email', $email)->where('id_user !=', $pklRow['id_user'])->countAllResults() > 0) {
            return $this->jsonError('Email sudah digunakan akun lain.');
        }

        // NEW-BUG-FIX: Validasi password baru — sinkron dengan aturan profil & login
        // (min 8 karakter, huruf besar, huruf kecil, angka, simbol)
        if ($passwordBaru !== '') {
            if (strlen($passwordBaru) < 8) {
                return $this->jsonError('Password minimal 8 karakter.');
            }
            if (! preg_match('/[A-Z]/', $passwordBaru)) {
                return $this->jsonError('Password harus mengandung minimal 1 huruf kapital.');
            }
            if (! preg_match('/[a-z]/', $passwordBaru)) {
                return $this->jsonError('Password harus mengandung minimal 1 huruf kecil.');
            }
            if (! preg_match('/[0-9]/', $passwordBaru)) {
                return $this->jsonError('Password harus mengandung minimal 1 angka.');
            }
            if (! preg_match('/[^A-Za-z0-9]/', $passwordBaru)) {
                return $this->jsonError('Password harus mengandung minimal 1 simbol (contoh: @, #, !, _).');
            }
        }

        // Update tabel pkl
        $this->pklModel->update($idPkl, [
            'nama_lengkap'   => trim($this->request->getPost('nama_lengkap') ?? ''),
            'nama_panggilan' => trim($this->request->getPost('nama_panggilan') ?? '') ?: null,
            'tempat_lahir'   => trim($this->request->getPost('tempat_lahir') ?? '') ?: null,
            'tgl_lahir'      => $this->request->getPost('tgl_lahir') ?: null,
            'no_wa_pkl'      => trim($this->request->getPost('no_wa') ?? '') ?: null,
            'jenis_kelamin'  => $this->request->getPost('jenis_kelamin') ?: null,
            'alamat'         => trim($this->request->getPost('alamat') ?? '') ?: null,
            'jurusan'        => trim($this->request->getPost('jurusan') ?? '') ?: null,
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
            if ($isKetua) {
                // Hapus semua anggota + kelompok
                $semuaAnggota = $this->kelompokModel->getAnggotaByKelompok($idKelompok);
                /** @var array $ang — getAnggotaByKelompok() returns array of associative arrays */
                foreach ($semuaAnggota as $ang) {
                    $this->pklModel->delete($ang['id_pkl']);
                    $this->userModel->delete($ang['id_user']);
                }
                $this->kelompokModel->delete($idKelompok);
            } else {
                // Hapus anggota ini saja
                $this->pklModel->delete($idPkl);
                $this->userModel->delete($pklRow['id_user']);

                // Jika ini anggota terakhir, hapus kelompok juga
                if (! $this->kelompokModel->masihAdaAnggotaLain($idKelompok, $idPkl)) {
                    $this->kelompokModel->delete($idKelompok);
                }
            }
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

        $lines[] = "📋 *Data Kelompok*";
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
        foreach ($anggota as $idx => $ang) {
            $no   = $idx + 1;
            $role = $ang['role'] === 'ketua' ? ' (Ketua)' : '';
            $lines[] = "👤 *Anggota {$no}{$role}*";
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

    private function jsonError(string $message, int $status = 422)
    {
        return $this->response->setStatusCode($status)
            ->setJSON(['success' => false, 'message' => $message]);
    }
}
