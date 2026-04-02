<?php

namespace App\Controllers;

use App\Models\InstansiModel;
use App\Models\KelompokPklModel;

/**
 * InstansiAdminController
 *
 * Menangani halaman Manajemen PKL → Tab Data Instansi.
 * Semua aksi (store/update/delete) dilayani via AJAX → return JSON.
 *
 * Routes (group 'admin'):
 *   GET  manajemen-pkl                          → index()
 *   POST manajemen-pkl/instansi/store           → storeInstansi()
 *   POST manajemen-pkl/instansi/update/(:num)   → updateInstansi($1)
 *   POST manajemen-pkl/instansi/delete/(:num)   → deleteInstansi($1)
 *   GET  manajemen-pkl/instansi/kota-list       → getKotaList()
 */
class InstansiAdminController extends BaseController
{
    protected InstansiModel    $instansiModel;
    protected KelompokPklModel $kelompokModel;

    public function __construct()
    {
        $this->instansiModel = new InstansiModel();
        $this->kelompokModel  = new KelompokPklModel();
    }

    // ── Halaman Utama ────────────────────────────────────────────────

    public function index()
    {
        $activeTab = $this->request->getGet('tab') === 'pkl' ? 'pkl' : 'instansi';
        $mode      = $this->request->getGet('mode') ?? 'list'; // list | tambah | edit
        $editId    = (int) ($this->request->getGet('id') ?? 0);

        // Validasi mode
        if (! in_array($mode, ['list', 'tambah', 'edit'])) {
            $mode = 'list';
        }

        // Untuk mode edit, pastikan data instansi ada
        $editData = null;
        if ($mode === 'edit' && $editId > 0) {
            $editData = $this->instansiModel->getOneFormatted($editId);
            if (! $editData) {
                // Data tidak ditemukan → fallback ke list
                $mode   = 'list';
                $editId = 0;
            }
        }

        // Tentukan page_title_sub berdasarkan mode
        $subTitle = match ($mode) {
            'tambah' => 'Tambah Instansi',
            'edit'   => 'Ubah Instansi',
            default  => ($activeTab === 'pkl' ? 'Data PKL' : 'Data Instansi'),
        };

        // Data PKL — dibutuhkan oleh _tab_pkl.php saat tab=pkl
        $subTabPkl = $this->request->getGet('sub') ?? 'aktif';
        if (! in_array($subTabPkl, ['aktif', 'selesai', 'nonaktif'])) $subTabPkl = 'aktif';

        $data = [
            'page_title'     => 'Manajemen PKL',
            'page_title_sub' => $subTitle,
            'active_menu'    => 'manajemen_pkl',
            'active_tab'     => $activeTab,
            'mode'           => $mode,
            'edit_id'        => $editId,
            'edit_data'      => $editData,
            'instansiList'   => $this->instansiModel->getAllFormatted(),
            'kotaList'       => $this->instansiModel->getKotaList(),
            // Data PKL untuk tab Data PKL
            'sub_tab'        => $subTabPkl,
            'stat_aktif'     => $this->kelompokModel->countAktif(),
            'stat_selesai'   => $this->kelompokModel->countSelesai(),
            'stat_nonaktif'  => $this->kelompokModel->countNonAktif(),
            'pkl_aktif'      => $this->kelompokModel->getAktif(),
            'pkl_selesai'    => $this->kelompokModel->getSelesai(),
            'pkl_nonaktif'   => $this->kelompokModel->getNonAktif(),
            'instansiList_raw' => $this->instansiModel->getAllFormatted(),
            'extra_css'      => '<link rel="stylesheet" href="' . base_url('assets/css/modules/admin/manajemen_pkl.css') . '">',
            'extra_js'       => '<script src="' . base_url('assets/js/modules/admin/manajemen_pkl.js') . '"></script>',
        ];

        $data['content'] = view('dashboard_admin/manajemen_pkl/index', $data);
        return view('Layouts/dashboard_layout', $data);
    }

    // ── Store Instansi (AJAX) ────────────────────────────────────────

    public function storeInstansi()
    {
        if (! $this->request->isAJAX()) {
            return $this->jsonError('Forbidden', 403);
        }

        $nama     = trim($this->request->getPost('nama_instansi') ?? '');
        $kategori = trim($this->request->getPost('kategori_instansi') ?? '');
        $alamat   = trim($this->request->getPost('alamat_instansi') ?? '');
        $kota     = trim($this->request->getPost('kota_instansi') ?? '');

        // Validasi
        if (empty($nama) || empty($kategori) || empty($kota)) {
            return $this->jsonError('Nama instansi, kategori, dan kota wajib diisi.');
        }

        if ($this->instansiModel->isNamaExists($nama)) {
            return $this->jsonError("Instansi dengan nama \"{$nama}\" sudah terdaftar.");
        }

        $id = $this->instansiModel->insert([
            'nama_instansi'    => $nama,
            'kategori_instansi' => InstansiModel::toDbValue($kategori),
            'alamat_instansi'  => $alamat,
            'kota_instansi'    => $kota,
        ]);

        $row = $this->instansiModel->getOneFormatted((int) $id);

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Instansi berhasil ditambahkan.',
            'data'    => $row,
        ]);
    }

    // ── Update Instansi (AJAX) ───────────────────────────────────────

    public function updateInstansi(int $id)
    {
        if (! $this->request->isAJAX()) {
            return $this->jsonError('Forbidden', 403);
        }

        $existing = $this->instansiModel->find($id);
        if (! $existing) {
            return $this->jsonError('Data instansi tidak ditemukan.', 404);
        }

        $nama     = trim($this->request->getPost('nama_instansi') ?? '');
        $kategori = trim($this->request->getPost('kategori_instansi') ?? '');
        $alamat   = trim($this->request->getPost('alamat_instansi') ?? '');
        $kota     = trim($this->request->getPost('kota_instansi') ?? '');

        if (empty($nama) || empty($kategori) || empty($kota)) {
            return $this->jsonError('Nama instansi, kategori, dan kota wajib diisi.');
        }

        if ($this->instansiModel->isNamaExists($nama, $id)) {
            return $this->jsonError("Instansi dengan nama \"{$nama}\" sudah terdaftar.");
        }

        $this->instansiModel->update($id, [
            'nama_instansi'    => $nama,
            'kategori_instansi' => InstansiModel::toDbValue($kategori),
            'alamat_instansi'  => $alamat,
            'kota_instansi'    => $kota,
        ]);

        $row = $this->instansiModel->getOneFormatted($id);

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Instansi berhasil diperbarui.',
            'data'    => $row,
        ]);
    }

    // ── Delete Instansi (AJAX) ───────────────────────────────────────

    public function deleteInstansi(int $id)
    {
        if (! $this->request->isAJAX()) {
            return $this->jsonError('Forbidden', 403);
        }

        $existing = $this->instansiModel->find($id);
        if (! $existing) {
            return $this->jsonError('Data instansi tidak ditemukan.', 404);
        }

        if ($this->instansiModel->isUsedByKelompok($id)) {
            return $this->jsonError(
                'Instansi "' . $existing['nama_instansi'] . '" tidak dapat dihapus '
                    . 'karena masih digunakan oleh Kelompok PKL. '
                    . 'Lepaskan instansi dari semua kelompok terlebih dahulu.'
            );
        }

        try {
            $this->instansiModel->delete($id);
        } catch (\Exception $e) {
            // Fallback: tangkap DB constraint RESTRICT jika ada relasi lain di level DB
            log_message('error', '[deleteInstansi] ' . $e->getMessage());
            return $this->jsonError(
                'Instansi tidak dapat dihapus karena masih terhubung dengan data lain.'
            );
        }

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Instansi "' . $existing['nama_instansi'] . '" berhasil dihapus.',
        ]);
    }

    // ── Kota List (AJAX — untuk Select2) ────────────────────────────

    public function getKotaList()
    {
        if (! $this->request->isAJAX()) {
            return $this->jsonError('Forbidden', 403);
        }

        return $this->response->setJSON([
            'success' => true,
            'data'    => $this->instansiModel->getKotaList(),
        ]);
    }

    // ── Helper ───────────────────────────────────────────────────────

    private function jsonError(string $message, int $status = 422): \CodeIgniter\HTTP\ResponseInterface
    {
        return $this->response
            ->setStatusCode($status)
            ->setJSON(['success' => false, 'message' => $message]);
    }
}
