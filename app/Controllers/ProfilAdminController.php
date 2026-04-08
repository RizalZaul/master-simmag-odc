<?php

namespace App\Controllers;

use App\Models\AdminModel;
use App\Models\UserModel;
use App\Models\AppSettingsModel;

/**
 * ProfilAdminController
 *
 * Menangani halaman profil admin dengan dua tab:
 *   1. Bio Pribadi  : edit biodata + ubah password (inline edit)
 *   2. Pengaturan   : toggle form biodata PKL (AJAX)
 *
 * Routes yang perlu didaftarkan di app/Config/Routes.php:
 *   $routes->get ('admin/profil',                   'ProfilAdminController::index');
 *   $routes->post('admin/profil/biodata',            'ProfilAdminController::updateBiodata');
 *   $routes->post('admin/profil/password',           'ProfilAdminController::updatePassword');
 *   $routes->post('admin/profil/toggle-biodata-pkl', 'ProfilAdminController::toggleBiodataPkl');
 */
class ProfilAdminController extends BaseController
{
    protected AdminModel       $adminModel;
    protected UserModel        $userModel;
    protected AppSettingsModel $settingsModel;

    public function __construct()
    {
        $this->adminModel    = new AdminModel();
        $this->userModel     = new UserModel();
        $this->settingsModel = new AppSettingsModel();
    }

    // ── Halaman Utama ───────────────────────────────────────────────

    public function index()
    {
        $idUser  = (int) session()->get('user_id');
        $idAdmin = (int) session()->get('id_admin');

        $admin           = $this->adminModel->findByIdUser($idUser);
        $user            = $this->userModel->find($idUser);
        $formBiodataAktif = $this->settingsModel->getValue('form_biodata_aktif') === '1';
        $biodataToken = $this->settingsModel->getValue('biodata_token');
        $biodataLink  = $biodataToken ? base_url('biodata-pkl/' . $biodataToken) : null;

        $activeTab = $this->request->getGet('tab') === 'setting' ? 'setting' : 'biodata';

        // page_title & page_subheading dinamis sesuai tab aktif.
        // Mengisi: (1) <h1> header.php, (2) browser tab title, (3) welcome card
        // — ketiganya server-rendered, bukan JS.
        $tabMeta = [
            'biodata' => ['title' => 'Profil Saya',  'subheading' => 'Data diri dan informasi akun'],
            'setting' => ['title' => 'Pengaturan',    'subheading' => 'Pengaturan form biodata siswa PKL'],
        ];

        $data = [
            'page_title'        => $tabMeta[$activeTab]['title'],
            'active_menu'       => 'profil',
            'page_subheading'   => $tabMeta[$activeTab]['subheading'],
            'admin'             => $admin,
            'user'              => $user,
            'form_biodata_aktif' => $formBiodataAktif,
            'active_tab'        => $activeTab,
            'biodata_token' => $biodataToken,
            'biodata_link'  => $biodataLink,
            'extra_css'         => '<link rel="stylesheet" href="' . base_url('assets/css/modules/admin/profil.css') . '">',
            'extra_js'          => '<script src="' . base_url('assets/js/modules/admin/profil.js') . '"></script>',
        ];

        $data['content'] = view('dashboard_admin/profil/profil', $data);
        return view('Layouts/dashboard_layout', $data);
    }

    // ── Update Biodata ───────────────────────────────────────────────

    public function updateBiodata()
    {
        $idUser  = (int) session()->get('user_id');
        $idAdmin = (int) session()->get('id_admin');

        $namaLengkap = trim((string) $this->request->getPost('nama_lengkap'));
        $namaPanggilan = trim((string) $this->request->getPost('nama_panggilan'));
        $email = strtolower(trim((string) $this->request->getPost('email')));
        $noWa = trim((string) $this->request->getPost('no_wa'));
        $alamat = trim((string) $this->request->getPost('alamat'));

        $missingFields = [];
        if ($namaLengkap === '') {
            $missingFields[] = 'Nama Lengkap';
        }
        if ($namaPanggilan === '') {
            $missingFields[] = 'Nama Panggilan';
        }
        if ($email === '') {
            $missingFields[] = 'Email';
        }
        if ($noWa === '') {
            $missingFields[] = 'No WA';
        }
        if ($alamat === '') {
            $missingFields[] = 'Alamat';
        }

        if ($missingFields !== []) {
            session()->setFlashdata('swal_error', $this->buildMissingFieldsMessage($missingFields, 5));
            return redirect()->to(base_url('admin/profil?tab=biodata'))->withInput();
        }

        $fieldError = $this->validatePatternField(
            'Nama Lengkap',
            (string) $this->request->getPost('nama_lengkap'),
            1,
            100,
            "/^[\\p{L}\\s.,'-]+$/u",
            'huruf, spasi, titik, koma, apostrof, dan tanda hubung'
        )
            ?? $this->validateLooseTextField('Nama Panggilan', (string) $this->request->getPost('nama_panggilan'), 1, 10)
            ?? $this->validateEmailAddress($email)
            ?? $this->validateWhatsappNumber($noWa, 'No WA')
            ?? $this->validateMultilinePatternField(
                'Alamat',
                (string) $this->request->getPost('alamat'),
                5,
                100,
                "/^[\\p{L}0-9\\s'.,\\-\\/#+]+$/u",
                'huruf, angka, spasi, apostrof, tanda hubung, titik, koma, garis miring, tanda angka (#), dan baris baru'
            );

        if ($fieldError !== null) {
            session()->setFlashdata('swal_error', $fieldError);
            return redirect()->to(base_url('admin/profil?tab=biodata'))->withInput();
        }

        // Pastikan email tidak dipakai user lain
        $existingUser = $this->userModel
            ->where('email', $email)
            ->where('id_user !=', $idUser)
            ->first();

        if ($existingUser) {
            session()->setFlashdata('swal_error', 'Email sudah digunakan oleh akun lain.');
            return redirect()->to(base_url('admin/profil?tab=biodata'));
        }

        // Update tabel admin
        $this->adminModel->updateProfil($idAdmin, [
            'nama_lengkap'   => $this->normalizeSingleSpaces($namaLengkap),
            'nama_panggilan' => $this->normalizeSingleSpaces($namaPanggilan),
            'no_wa_admin'    => $noWa,
            'alamat'         => $this->normalizeMultilineText($alamat),
        ]);

        // Update email di tabel users
        $this->userModel->update($idUser, ['email' => $email]);

        // Sinkron session nama panggilan (untuk greeting di navbar)
        // $panggilan = trim($this->request->getPost('nama_panggilan'))
        //     ?: trim($this->request->getPost('nama_lengkap'));
        // session()->set('panggilan', $panggilan);
        // session()->set('nama', trim($this->request->getPost('nama_lengkap')));

        session()->set('panggilan', $namaPanggilan ?: null);
        session()->set('nama', $namaLengkap);

        session()->setFlashdata('swal_success', 'Biodata berhasil diperbarui.');
        return redirect()->to(base_url('admin/profil?tab=biodata'));
    }

    // ── Update Password ─────────────────────────────────────────────

    public function updatePassword()
    {
        $idUser = (int) session()->get('user_id');

        $passwordBaru       = $this->request->getPost('password_baru');
        $konfirmasiPassword = $this->request->getPost('konfirmasi_password');

        $missingFields = [];
        if (trim((string) $passwordBaru) === '') {
            $missingFields[] = 'Password Baru';
        }
        if (trim((string) $konfirmasiPassword) === '') {
            $missingFields[] = 'Konfirmasi Password';
        }

        if ($missingFields !== []) {
            session()->setFlashdata('swal_error', $this->buildMissingFieldsMessage($missingFields, 2));
            return redirect()->to(base_url('admin/profil?tab=biodata'));
        }

        // Validasi kekuatan password
        $error = $this->validateStandardPassword((string) $passwordBaru, (string) $konfirmasiPassword);
        if ($error) {
            session()->setFlashdata('swal_error', $error);
            return redirect()->to(base_url('admin/profil?tab=biodata'));
        }

        $this->userModel->updatePassword($idUser, $passwordBaru);

        session()->setFlashdata('swal_success', 'Password berhasil diperbarui.');
        return redirect()->to(base_url('admin/profil?tab=biodata'));
    }

    // ── Toggle Form Biodata PKL (AJAX) ──────────────────────────────

    public function toggleBiodataPkl()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Forbidden']);
        }

        $current = $this->settingsModel->getValue('form_biodata_aktif');
        $new     = ($current === '1') ? '0' : '1';

        $this->settingsModel->setValue('form_biodata_aktif', $new);

        return $this->response->setJSON([
            'success' => true,
            'aktif'   => $new === '1',
            'label'   => $new === '1' ? 'AKTIF' : 'NONAKTIF',
            'message' => $new === '1'
                ? 'Form biodata PKL sekarang terbuka. Siswa dapat mengisi dan mengubah data mereka.'
                : 'Form biodata PKL sekarang ditutup. Siswa tidak dapat mengakses form biodata.',
        ]);
    }

    // ── Helper: Validasi Password ───────────────────────────────────

    // ── Generate Token Baru (AJAX) ─────────────────────────────────

    public function generateToken()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Forbidden']);
        }

        $token = bin2hex(random_bytes(20)); // 40 char hex token
        $this->settingsModel->setValue('biodata_token', $token);

        return $this->response->setJSON([
            'success' => true,
            'token'   => $token,
            'link'    => base_url('biodata-pkl/' . $token),
        ]);
    }
}
