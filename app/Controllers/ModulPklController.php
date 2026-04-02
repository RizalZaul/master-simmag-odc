<?php

namespace App\Controllers;

use App\Models\KategoriModulModel;
use App\Models\ModulModel;

/**
 * ModulPklController
 *
 * Menangani halaman Modul untuk role PKL.
 * Hanya READ — tidak ada tambah/ubah/hapus.
 *
 * Routes (group 'pkl'):
 *   GET  pkl/modul                          → index()
 *   GET  pkl/modul/kategori/(:num)          → kategori($1)
 *   GET  pkl/modul/file/view/(:num)         → previewFile($1)
 *   GET  pkl/modul/file/download/(:num)     → downloadFile($1)
 */

class ModulPklController extends BaseController
{
    // Folder penyimpanan file — sama dengan admin agar file bisa diakses kedua role
    private const UPLOAD_SUBDIR = 'uploads/modul';

    protected KategoriModulModel $kategoriModel;
    protected ModulModel         $modulModel;

    public function __construct()
    {
        $this->kategoriModel = new KategoriModulModel();
        $this->modulModel    = new ModulModel();
    }

    // ── Halaman Utama: Daftar Kategori ──────────────────────────────

    public function index()
    {
        $kategoriList = $this->kategoriModel->getAllFormatted();

        // Tambahkan color & icon dari palet berdasarkan index
        $colorPalette = ['teal', 'blue', 'purple', 'orange', 'red', 'green', 'indigo', 'pink'];
        $iconPalette  = [
            'fa-book-open',
            'fa-file-alt',
            'fa-chalkboard-teacher',
            'fa-laptop-code',
            'fa-clipboard-list',
            'fa-graduation-cap',
            'fa-layer-group',
            'fa-puzzle-piece',
        ];

        foreach ($kategoriList as $i => &$kat) {
            $kat['color'] = $colorPalette[$i % count($colorPalette)];
            $kat['icon']  = $iconPalette[$i % count($iconPalette)];
        }
        unset($kat);

        $swalError = session()->getFlashdata('swal_error');

        $data = [
            'page_title'      => 'Data Modul',
            'page_subheading' => 'Modul pembelajaran PKL',
            'active_menu'     => 'modul',
            'kategoriList'    => $kategoriList,
            'swal_error'      => $swalError,
            'extra_css'       => '<link rel="stylesheet" href="' . base_url('assets/css/modules/pkl/modul.css') . '">',
            'extra_js'        => '<script src="' . base_url('assets/js/modules/pkl/modul.js') . '"></script>',
        ];

        $data['content'] = view('dashboard_pkl/modul/index', $data);
        return view('Layouts/dashboard_layout', $data);
    }

    // ── Halaman Daftar Modul per Kategori ───────────────────────────

    public function kategori(int $idKategori)
    {
        $kategori = $this->kategoriModel->find($idKategori);
        if (! $kategori) {
            session()->setFlashdata('swal_error', 'Kategori tidak ditemukan.');
            return redirect()->to(base_url('pkl/modul'));
        }

        $rawList   = $this->modulModel->getByKategori($idKategori);
        $modulList = array_map(
            fn(array $row): array => $this->formatModulForPkl($row),
            $rawList
        );

        $data = [
            'page_title'      => esc($kategori['nama_kat_m']),
            'page_subheading' => 'Daftar modul dalam kategori ini',
            'active_menu'     => 'modul',
            'kategori'        => $kategori,
            'modulList'       => $modulList,
            'extra_css'       => '<link rel="stylesheet" href="' . base_url('assets/css/modules/pkl/modul.css') . '">',
            'extra_js'        => '<script src="' . base_url('assets/js/modules/pkl/modul.js') . '"></script>',
        ];

        $data['content'] = view('dashboard_pkl/modul/kategori', $data);
        return view('Layouts/dashboard_layout', $data);
    }

    // ── Preview File (PDF buka inline, non-PDF redirect ke download) ──

    public function previewFile(int $id)
    {
        $modul = $this->modulModel->find($id);
        if (! $modul || ($modul['tipe'] ?? '') !== 'file') {
            return redirect()->to(base_url('pkl/modul'));
        }

        $filePath  = $this->getStoredFilePath((string) $modul['path']);
        $extension = strtolower(pathinfo((string) $modul['path'], PATHINFO_EXTENSION));

        if (! is_file($filePath)) {
            session()->setFlashdata('swal_error', 'File tidak ditemukan di server.');
            return redirect()->back();
        }

        // PDF → inline (buka di tab baru), selain PDF → redirect ke download
        if ($extension !== 'pdf') {
            return redirect()->to(base_url('pkl/modul/file/download/' . $id));
        }

        return $this->response
            ->download($filePath, null, true)
            ->setFileName(basename((string) $modul['path']))
            ->inline();
    }

    // ── Download File ───────────────────────────────────────────────

    public function downloadFile(int $id)
    {
        $modul = $this->modulModel->find($id);
        if (! $modul) {
            return redirect()->to(base_url('pkl/modul'));
        }

        // Tipe link → redirect langsung ke URL
        if (($modul['tipe'] ?? '') === 'link') {
            return redirect()->to((string) $modul['path']);
        }

        $filePath = $this->getStoredFilePath((string) $modul['path']);
        if (! is_file($filePath)) {
            session()->setFlashdata('swal_error', 'File tidak ditemukan di server.');
            return redirect()->back();
        }

        return $this->response
            ->download($filePath, null, true)
            ->setFileName(basename((string) $modul['path']));
    }

    // ── Helpers ─────────────────────────────────────────────────────

    /**
     * Format satu row modul mentah menjadi array siap pakai untuk view.
     * Menentukan URL aksi, icon, dan label berdasarkan tipe & ekstensi file.
     */
    private function formatModulForPkl(array $row): array
    {
        $id        = (int)    ($row['id_modul']  ?? 0);
        $type      = (string) ($row['tipe']      ?? 'link');
        $path      = (string) ($row['path']      ?? '');
        $fileName  = $type === 'file' ? basename($path) : null;

        $storedPath = ($type === 'file' && $path !== '')
            ? $this->getStoredFilePath($path)
            : null;
        $fileExists = $storedPath !== null && is_file($storedPath);
        $extension  = $type === 'file'
            ? strtolower(pathinfo($path, PATHINFO_EXTENSION))
            : null;
        $isPdf = ($extension === 'pdf');

        // Tentukan URL, target, dan label berdasarkan tipe
        $assetUrl    = null;
        $assetTarget = null;
        $assetLabel  = null;

        if ($type === 'link' && $path !== '') {
            $assetUrl    = $path;
            $assetTarget = '_blank';
            $assetLabel  = 'Buka Link';
        } elseif ($type === 'file' && $fileExists) {
            // PDF → view inline (buka tab baru), selain PDF → download
            $assetUrl    = $isPdf
                ? base_url('pkl/modul/file/view/' . $id)
                : base_url('pkl/modul/file/download/' . $id);
            $assetTarget = $isPdf ? '_blank' : '_self';
            $assetLabel  = $isPdf ? 'Buka File' : 'Unduh Modul';
        } elseif ($type === 'file' && ! $fileExists) {
            // File terdaftar tapi tidak ada di server
            $assetLabel = $fileName ?? '-';
        }

        return [
            'id'           => $id,
            'nama_modul'   => (string) ($row['nama_modul'] ?? ''),
            'ket_modul'    => (string) ($row['ket_modul']  ?? ''),
            'tipe'         => $type,
            'path'         => $path,
            'tgl_dibuat'   => (string) ($row['created_at'] ?? ''),
            'tgl_diubah'   => (string) ($row['updated_at'] ?? ''),
            'asset_url'    => $assetUrl,
            'asset_target' => $assetTarget,
            'asset_label'  => $assetLabel,
            'icon_class'   => $this->resolveFileIcon($type, $path),
            'file_exists'  => $fileExists,
            'is_pdf'       => $isPdf,
            'file_ext'     => $extension ? strtoupper($extension) : null,
        ];
    }

    private function resolveFileIcon(string $type, string $path): string
    {
        if ($type === 'link') return 'fas fa-link';

        return match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'pdf'          => 'fas fa-file-pdf',
            'doc', 'docx'  => 'fas fa-file-word',
            'xls', 'xlsx'  => 'fas fa-file-excel',
            'ppt', 'pptx'  => 'fas fa-file-powerpoint',
            'zip', 'rar'   => 'fas fa-file-archive',
            default        => 'fas fa-file',
        };
    }

    private function getStoredFilePath(string $storedName): string
    {
        return WRITEPATH . self::UPLOAD_SUBDIR . DIRECTORY_SEPARATOR . $storedName;
    }
}
