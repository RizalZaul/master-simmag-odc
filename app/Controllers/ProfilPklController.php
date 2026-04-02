<?php

namespace App\Controllers;

use App\Models\PklModel;
use App\Models\UserModel;

/**
 * ProfilPklController
 *
 * Halaman profil PKL — satu tab saja (tidak ada tab Pengaturan).
 * Section:
 *   1. Durasi PKL card (di samping avatar)
 *   2. Informasi Pribadi (inline edit)
 *   3. Informasi Instansi & Kelompok (readonly, hanya tampil jika ada instansi)
 *   4. Ubah Password (inline edit)
 *
 * Routes (tambahkan di app/Config/Routes.php, group 'pkl'):
 *   $routes->get ('profil',           'ProfilPklController::index');
 *   $routes->post('profil/biodata',   'ProfilPklController::updateBiodata');
 *   $routes->post('profil/password',  'ProfilPklController::updatePassword');
 */
class ProfilPklController extends BaseController
{
    protected PklModel  $pklModel;
    protected UserModel $userModel;

    public function __construct()
    {
        $this->pklModel  = new PklModel();
        $this->userModel = new UserModel();
    }

    // ── Halaman Utama ────────────────────────────────────────────────

    public function index()
    {
        $idUser = (int) session()->get('user_id');
        $idPkl  = (int) session()->get('id_pkl');

        $pkl      = $this->pklModel->getDataDiri($idPkl);
        $user     = $this->userModel->find($idUser);
        $anggota  = $this->pklModel->getAnggotaKelompok((int) ($pkl['id_kelompok'] ?? 0));

        $data = [
            'page_title'  => 'Profil Saya',
            'active_menu' => 'profil',
            'pkl'         => $pkl,
            'user'        => $user,
            'anggota'     => $anggota,
            'extra_css'   => '<link rel="stylesheet" href="' . base_url('assets/css/modules/pkl/profil.css') . '">',
            'extra_js'    => '<script src="' . base_url('assets/js/modules/pkl/profil.js') . '"></script>',
        ];

        $data['content'] = view('dashboard_pkl/profil/profil', $data);
        return view('Layouts/dashboard_layout', $data);
    }

    // ── Update Biodata ────────────────────────────────────────────────

    public function updateBiodata()
    {
        $idUser = (int) session()->get('user_id');
        $idPkl  = (int) session()->get('id_pkl');

        $rules = [
            'nama_lengkap'   => 'required|min_length[3]|max_length[100]',
            'nama_panggilan' => 'permit_empty|max_length[50]',
            'jenis_kelamin'  => 'permit_empty|in_list[L,P]',
            'tempat_lahir'   => 'permit_empty|max_length[100]',
            'tgl_lahir'      => 'permit_empty|valid_date[Y-m-d]',
            'no_wa'          => 'permit_empty|max_length[20]',
            'alamat'         => 'permit_empty|max_length[500]',
            'jurusan'        => 'permit_empty|max_length[100]',
        ];

        if (! $this->validate($rules)) {
            session()->setFlashdata('swal_error', implode(' ', $this->validator->getErrors()));
            return redirect()->to(base_url('pkl/profil'));
        }

        $this->pklModel->updateDataDiri($idPkl, [
            'nama_lengkap'   => trim($this->request->getPost('nama_lengkap')),
            'nama_panggilan' => trim($this->request->getPost('nama_panggilan')),
            'jenis_kelamin'  => $this->request->getPost('jenis_kelamin') ?: null,
            'tempat_lahir'   => trim($this->request->getPost('tempat_lahir')),
            'tgl_lahir'      => $this->request->getPost('tgl_lahir') ?: null,
            'no_wa_pkl'      => trim($this->request->getPost('no_wa')),
            'alamat'         => trim($this->request->getPost('alamat')),
            'jurusan'        => trim($this->request->getPost('jurusan')),
        ]);

        // Sinkron session
        // $panggilan = trim($this->request->getPost('nama_panggilan'))
        //     ?: trim($this->request->getPost('nama_lengkap'));
        // session()->set('panggilan', $panggilan);
        // session()->set('nama', trim($this->request->getPost('nama_lengkap')));

        session()->set('panggilan', trim($this->request->getPost('nama_panggilan')) ?: null);
        session()->set('nama', trim($this->request->getPost('nama_lengkap')));

        session()->setFlashdata('swal_success', 'Data diri berhasil diperbarui.');
        return redirect()->to(base_url('pkl/profil'));
    }

    // ── Update Password ──────────────────────────────────────────────

    public function updatePassword()
    {
        $idUser = (int) session()->get('user_id');

        $passwordBaru = $this->request->getPost('password_baru');
        $konfirmasi   = $this->request->getPost('konfirmasi_password');

        $error = $this->validatePassword($passwordBaru, $konfirmasi);
        if ($error) {
            session()->setFlashdata('swal_error', $error);
            return redirect()->to(base_url('pkl/profil'));
        }

        $this->userModel->updatePassword($idUser, $passwordBaru);

        session()->setFlashdata('swal_success', 'Password berhasil diperbarui.');
        return redirect()->to(base_url('pkl/profil'));
    }

    // ── Helper: Validasi Password ────────────────────────────────────

    private function validatePassword(string $password, string $konfirmasi): ?string
    {
        if (strlen($password) < 8)               return 'Password minimal 8 karakter.';
        if (! preg_match('/[A-Z]/', $password))  return 'Password harus mengandung minimal 1 huruf kapital (A-Z).';
        if (! preg_match('/[a-z]/', $password))  return 'Password harus mengandung minimal 1 huruf kecil (a-z).';
        if (! preg_match('/[0-9]/', $password))  return 'Password harus mengandung minimal 1 angka (0-9).';
        if (! preg_match('/[\W_]/', $password))  return 'Password harus mengandung minimal 1 simbol (!, @, #, dst).';
        if ($password !== $konfirmasi)            return 'Konfirmasi password tidak cocok.';
        return null;
    }
}
