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
        $pklCurrent = $this->pklModel->getDataDiri($idPkl);
        $isInstansi = ! empty($pklCurrent['id_instansi']);

        $namaLengkap = trim((string) $this->request->getPost('nama_lengkap'));
        $namaPanggilan = trim((string) $this->request->getPost('nama_panggilan'));
        $jenisKelamin = trim((string) $this->request->getPost('jenis_kelamin'));
        $tempatLahir = trim((string) $this->request->getPost('tempat_lahir'));
        $tglLahir = trim((string) $this->request->getPost('tgl_lahir'));
        $noWa = trim((string) $this->request->getPost('no_wa'));
        $alamat = trim((string) $this->request->getPost('alamat'));
        $jurusan = trim((string) $this->request->getPost('jurusan'));

        $missingFields = [];
        if ($namaLengkap === '') {
            $missingFields[] = 'Nama Lengkap';
        }
        if ($namaPanggilan === '') {
            $missingFields[] = 'Nama Panggilan';
        }
        if ($jenisKelamin === '') {
            $missingFields[] = 'Jenis Kelamin';
        }
        if ($tempatLahir === '') {
            $missingFields[] = 'Tempat Lahir';
        }
        if ($tglLahir === '') {
            $missingFields[] = 'Tanggal Lahir';
        }
        if ($noWa === '') {
            $missingFields[] = 'No WA';
        }
        if ($alamat === '') {
            $missingFields[] = 'Alamat';
        }
        if ($isInstansi && $jurusan === '') {
            $missingFields[] = 'Jurusan';
        }

        if ($missingFields !== []) {
            session()->setFlashdata('swal_error', $this->buildMissingFieldsMessage($missingFields, $isInstansi ? 8 : 7));
            return redirect()->to(base_url('pkl/profil'))->withInput();
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
            ?? (! in_array($jenisKelamin, ['L', 'P'], true) ? 'Jenis Kelamin tidak valid.' : null)
            ?? $this->validatePatternField('Tempat Lahir', (string) $this->request->getPost('tempat_lahir'), 1, 50, '/^[\p{L}\s]+$/u', 'huruf dan spasi')
            ?? $this->validateDateOnlyValue('Tanggal Lahir', $tglLahir)
            ?? $this->validateWhatsappNumber($noWa, 'No WA')
            ?? $this->validateMultilinePatternField(
                'Alamat',
                (string) $this->request->getPost('alamat'),
                5,
                100,
                "/^[\\p{L}0-9\\s'.,\\-\\/#+]+$/u",
                'huruf, angka, spasi, apostrof, tanda hubung, titik, koma, garis miring, tanda angka (#), dan baris baru'
            )
            ?? ($isInstansi
                ? $this->validatePatternField(
                    'Jurusan',
                    (string) $this->request->getPost('jurusan'),
                    2,
                    100,
                    '/^[\p{L}\s.()\-]+$/u',
                    'huruf, spasi, titik, tanda hubung, dan tanda kurung'
                )
                : null);

        if ($fieldError !== null) {
            session()->setFlashdata('swal_error', $fieldError);
            return redirect()->to(base_url('pkl/profil'))->withInput();
        }

        $this->pklModel->updateDataDiri($idPkl, [
            'nama_lengkap'   => $this->normalizeSingleSpaces($namaLengkap),
            'nama_panggilan' => $this->normalizeSingleSpaces($namaPanggilan),
            'jenis_kelamin'  => $jenisKelamin,
            'tempat_lahir'   => $this->normalizeSingleSpaces($tempatLahir),
            'tgl_lahir'      => $tglLahir,
            'no_wa_pkl'      => $noWa,
            'alamat'         => $this->normalizeMultilineText($alamat),
            'jurusan'        => $isInstansi ? $this->normalizeSingleSpaces($jurusan) : trim((string) ($pklCurrent['jurusan'] ?? '')),
        ]);

        // Sinkron session
        // $panggilan = trim($this->request->getPost('nama_panggilan'))
        //     ?: trim($this->request->getPost('nama_lengkap'));
        // session()->set('panggilan', $panggilan);
        // session()->set('nama', trim($this->request->getPost('nama_lengkap')));

        session()->set('panggilan', $namaPanggilan ?: null);
        session()->set('nama', $namaLengkap);

        session()->setFlashdata('swal_success', 'Data diri berhasil diperbarui.');
        return redirect()->to(base_url('pkl/profil'));
    }

    // ── Update Password ──────────────────────────────────────────────

    public function updatePassword()
    {
        $idUser = (int) session()->get('user_id');

        $passwordBaru = $this->request->getPost('password_baru');
        $konfirmasi   = $this->request->getPost('konfirmasi_password');

        $missingFields = [];
        if (trim((string) $passwordBaru) === '') {
            $missingFields[] = 'Password Baru';
        }
        if (trim((string) $konfirmasi) === '') {
            $missingFields[] = 'Konfirmasi Password';
        }

        if ($missingFields !== []) {
            session()->setFlashdata('swal_error', $this->buildMissingFieldsMessage($missingFields, 2));
            return redirect()->to(base_url('pkl/profil'));
        }

        $error = $this->validateStandardPassword((string) $passwordBaru, (string) $konfirmasi);
        if ($error) {
            session()->setFlashdata('swal_error', $error);
            return redirect()->to(base_url('pkl/profil'));
        }

        $this->userModel->updatePassword($idUser, $passwordBaru);

        session()->setFlashdata('swal_success', 'Password berhasil diperbarui.');
        return redirect()->to(base_url('pkl/profil'));
    }

    // ── Helper: Validasi Password ────────────────────────────────────

}
