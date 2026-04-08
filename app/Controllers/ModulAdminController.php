<?php

namespace App\Controllers;

use App\Models\KategoriModulModel;
use App\Models\ModulModel;
use CodeIgniter\HTTP\Files\UploadedFile;

class ModulAdminController extends BaseController
{
    private const UPLOAD_SUBDIR        = 'uploads/modul';
    private const MAX_FILE_SIZE_KB     = 307200; // 300 MB
    private const ALLOWED_EXTENSIONS   = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'zip', 'rar'];

    protected KategoriModulModel $kategoriModel;
    protected ModulModel $modulModel;

    public function __construct()
    {
        $this->kategoriModel = new KategoriModulModel();
        $this->modulModel    = new ModulModel();
    }

    public function index()
    {
        $activeTab = $this->request->getGet('tab') === 'modul' ? 'modul' : 'kategori';
        $modulMode = $activeTab === 'modul' ? (string) ($this->request->getGet('mode') ?? 'list') : 'list';
        if (! in_array($modulMode, ['list', 'create', 'detail', 'edit'], true)) {
            $modulMode = 'list';
        }

        $headerSubTitle = match (true) {
            $activeTab === 'kategori' => 'Kategori Modul',
            $modulMode === 'create'   => 'Tambah Modul',
            $modulMode === 'detail'   => 'Detail Modul',
            $modulMode === 'edit'     => 'Ubah Modul',
            default                   => 'Modul',
        };

        $kategoriDropdown = [];
        foreach ($this->kategoriModel->getDropdown() as $id => $nama) {
            $kategoriDropdown[] = [
                'id'   => (int) $id,
                'nama' => $nama,
            ];
        }

        $modulList = array_map(
            fn(array $row): array => $this->formatModulForView($row),
            $this->modulModel->getAllFormatted()
        );

        $data = [
            'page_title'      => 'Data Modul',
            'page_title_sub'  => $headerSubTitle,
            'welcome_heading' => 'Data Modul',
            'welcome_subheading' => 'Kelola kategori modul dan materi pembelajaran dalam satu tempat.',
            'active_menu'     => 'data_modul',
            'active_tab'      => $activeTab,
            'kategoriList'    => $this->kategoriModel->getAllFormatted(),
            'kategoriOptions' => $kategoriDropdown,
            'modulList'       => $modulList,
            'extra_css'       => '<link rel="stylesheet" href="' . base_url('assets/css/modules/admin/data_modul.css?v=20260406-1') . '">',
            'extra_js'        => '<script src="' . base_url('assets/js/modules/admin/data_modul.js?v=20260406-1') . '"></script>',
        ];

        $data['content'] = view('dashboard_admin/data_modul/index', $data);
        return view('Layouts/dashboard_layout', $data);
    }

    public function detail(int $id)
    {
        if (! $this->modulModel->getOneFormatted($id)) {
            session()->setFlashdata('swal_error', 'Modul tidak ditemukan.');
            return redirect()->to($this->buildModulUrl());
        }

        return redirect()->to($this->buildModulUrl('detail', $id));
    }

    public function previewFile(int $id)
    {
        $modul = $this->modulModel->getOneFormatted($id);
        if (! $modul) {
            session()->setFlashdata('swal_error', 'Modul tidak ditemukan.');
            return redirect()->to($this->buildModulUrl());
        }

        if (($modul['tipe'] ?? '') !== 'file') {
            return redirect()->to($this->buildModulUrl('detail', $id));
        }

        $filePath = $this->getStoredFilePath((string) $modul['path']);
        if (! is_file($filePath)) {
            session()->setFlashdata('swal_error', 'File modul tidak ditemukan di server.');
            return redirect()->to($this->buildModulUrl('detail', $id));
        }

        $extension = strtolower(pathinfo((string) $modul['path'], PATHINFO_EXTENSION));
        if ($extension !== 'pdf') {
            return redirect()->to(base_url('admin/data-modul/file/download/' . $id));
        }

        return $this->response
            ->download($filePath, null, true)
            ->setFileName(basename((string) $modul['path']))
            ->inline();
    }

    public function downloadFile(int $id)
    {
        $modul = $this->modulModel->getOneFormatted($id);
        if (! $modul) {
            session()->setFlashdata('swal_error', 'Modul tidak ditemukan.');
            return redirect()->to($this->buildModulUrl());
        }

        if (($modul['tipe'] ?? '') === 'link') {
            return redirect()->to((string) $modul['path']);
        }

        $filePath = $this->getStoredFilePath((string) $modul['path']);
        if (! is_file($filePath)) {
            session()->setFlashdata('swal_error', 'File modul tidak ditemukan di server.');
            return redirect()->to($this->buildModulUrl('detail', $id));
        }

        return $this->response
            ->download($filePath, null, true)
            ->setFileName(basename((string) $modul['path']));
    }

    public function storeKategori()
    {
        $namaRaw = (string) $this->request->getPost('nama_kategori');
        $nama = trim($namaRaw);

        if ($nama === '') {
            session()->setFlashdata('swal_error', 'Semua field harus diisi.');
            return redirect()->to($this->kategoriUrl());
        }

        $error = $this->validatePatternField(
            'Nama Kategori',
            $namaRaw,
            3,
            50,
            '/^[\p{L}0-9\s]+$/u',
            'huruf, angka, dan spasi'
        );
        if ($error !== null) {
            session()->setFlashdata('swal_error', $error);
            return redirect()->to($this->kategoriUrl());
        }

        $nama = $this->normalizeSingleSpaces($namaRaw);

        if ($this->kategoriModel->isNamaExists($nama)) {
            session()->setFlashdata('swal_error', 'Nama kategori "' . $nama . '" sudah terdaftar.');
            return redirect()->to($this->kategoriUrl());
        }

        $this->kategoriModel->insert(['nama_kat_m' => $nama]);

        session()->setFlashdata('swal_success', 'Kategori "' . $nama . '" berhasil ditambahkan.');
        return redirect()->to($this->kategoriUrl());
    }

    public function updateKategori(int $id)
    {
        $kategori = $this->kategoriModel->find($id);
        if (! $kategori) {
            session()->setFlashdata('swal_error', 'Kategori tidak ditemukan.');
            return redirect()->to($this->kategoriUrl());
        }

        $namaRaw = (string) $this->request->getPost('nama_kategori');
        $nama = trim($namaRaw);

        if ($nama === '') {
            session()->setFlashdata('swal_error', 'Semua field harus diisi.');
            return redirect()->to($this->kategoriUrl());
        }

        $error = $this->validatePatternField(
            'Nama Kategori',
            $namaRaw,
            3,
            50,
            '/^[\p{L}0-9\s]+$/u',
            'huruf, angka, dan spasi'
        );
        if ($error !== null) {
            session()->setFlashdata('swal_error', $error);
            return redirect()->to($this->kategoriUrl());
        }

        $nama = $this->normalizeSingleSpaces($namaRaw);

        if ($this->kategoriModel->isNamaExists($nama, $id)) {
            session()->setFlashdata('swal_error', 'Nama kategori "' . $nama . '" sudah digunakan kategori lain.');
            return redirect()->to($this->kategoriUrl());
        }

        $this->kategoriModel->update($id, ['nama_kat_m' => $nama]);

        session()->setFlashdata('swal_success', 'Kategori berhasil diperbarui menjadi "' . $nama . '".');
        return redirect()->to($this->kategoriUrl());
    }

    public function deleteKategori(int $id)
    {
        $kategori = $this->kategoriModel->find($id);
        if (! $kategori) {
            session()->setFlashdata('swal_error', 'Kategori tidak ditemukan.');
            return redirect()->to($this->kategoriUrl());
        }

        $jumlahModul = $this->modulModel->countByKategori($id);
        if ($jumlahModul > 0) {
            session()->setFlashdata(
                'swal_error',
                'Kategori tidak dapat dihapus karena masih memiliki ' . $jumlahModul . ' modul di dalamnya.'
            );
            return redirect()->to($this->kategoriUrl());
        }

        $namaKategori = $kategori['nama_kat_m'];
        $this->kategoriModel->delete($id);

        session()->setFlashdata('swal_success', 'Kategori "' . $namaKategori . '" berhasil dihapus.');
        return redirect()->to($this->kategoriUrl());
    }

    public function storeModul()
    {
        if (! $this->request->isAJAX()) {
            return $this->jsonError('Forbidden.', 403);
        }

        $error = $this->validateModulRequest();
        if ($error !== null) {
            return $this->jsonError($error, 422);
        }

        $file = $this->request->getFile('file_modul');
        $storedFileName = null;

        try {
            $payload = $this->collectModulPayload();

            if ($payload['tipe'] === 'file' && $this->hasUploadedFile($file)) {
                $storedFileName = $this->storeUploadedFile($file);
                $payload['path'] = $storedFileName;
            }

            $id = (int) $this->modulModel->insert($payload, true);

            return $this->response->setJSON([
                'success'      => true,
                'message'      => 'Modul berhasil ditambahkan.',
                'redirect_url' => $this->buildModulUrl('detail', $id),
                'id'           => $id,
            ]);
        } catch (\Throwable $e) {
            if ($storedFileName !== null) {
                $this->deleteStoredFile($storedFileName);
            }

            log_message('error', '[ModulAdminController::storeModul] ' . $e->getMessage());
            return $this->jsonError('Gagal menambahkan modul.', 500);
        }
    }

    public function updateModul(int $id)
    {
        if (! $this->request->isAJAX()) {
            return $this->jsonError('Forbidden.', 403);
        }

        $existing = $this->modulModel->find($id);
        if (! $existing) {
            return $this->jsonError('Modul tidak ditemukan.', 404);
        }

        $error = $this->validateModulRequest($existing);
        if ($error !== null) {
            return $this->jsonError($error, 422);
        }

        $file = $this->request->getFile('file_modul');
        $newStoredFile = null;
        $oldStoredFile = ($existing['tipe'] ?? '') === 'file' ? (string) ($existing['path'] ?? '') : null;

        try {
            $payload = $this->collectModulPayload();

            if ($payload['tipe'] === 'file') {
                if ($this->hasUploadedFile($file)) {
                    $newStoredFile = $this->storeUploadedFile($file);
                    $payload['path'] = $newStoredFile;
                } else {
                    $payload['path'] = (string) ($existing['path'] ?? '');
                }
            }

            $this->modulModel->update($id, $payload);

            if ($oldStoredFile && (($payload['tipe'] ?? '') !== 'file' || $newStoredFile !== null)) {
                $this->deleteStoredFile($oldStoredFile);
            }

            return $this->response->setJSON([
                'success'      => true,
                'message'      => 'Modul berhasil diperbarui.',
                'redirect_url' => $this->buildModulUrl('detail', $id),
                'id'           => $id,
            ]);
        } catch (\Throwable $e) {
            if ($newStoredFile !== null) {
                $this->deleteStoredFile($newStoredFile);
            }

            log_message('error', '[ModulAdminController::updateModul] ' . $e->getMessage());
            return $this->jsonError('Gagal memperbarui modul.', 500);
        }
    }

    public function deleteModul(int $id)
    {
        if (! $this->request->isAJAX()) {
            return $this->jsonError('Forbidden.', 403);
        }

        $modul = $this->modulModel->find($id);
        if (! $modul) {
            return $this->jsonError('Modul tidak ditemukan.', 404);
        }

        $nama = (string) ($modul['nama_modul'] ?? 'Modul');
        $storedFile = ($modul['tipe'] ?? '') === 'file' ? (string) ($modul['path'] ?? '') : '';

        try {
            $this->modulModel->delete($id);

            if ($storedFile !== '') {
                $this->deleteStoredFile($storedFile);
            }

            return $this->response->setJSON([
                'success'      => true,
                'message'      => 'Modul "' . $nama . '" berhasil dihapus.',
                'redirect_url' => $this->buildModulUrl(),
            ]);
        } catch (\Throwable $e) {
            log_message('error', '[ModulAdminController::deleteModul] ' . $e->getMessage());
            return $this->jsonError('Gagal menghapus modul.', 500);
        }
    }

    private function kategoriUrl(): string
    {
        return base_url('admin/data-modul') . '?tab=kategori';
    }

    private function buildModulUrl(string $mode = 'list', ?int $id = null): string
    {
        $url = base_url('admin/data-modul') . '?tab=modul';

        if ($mode !== 'list') {
            $url .= '&mode=' . urlencode($mode);
        }

        if ($id !== null) {
            $url .= '&id=' . $id;
        }

        return $url;
    }

    private function jsonError(string $message, int $status = 400)
    {
        return $this->response
            ->setStatusCode($status)
            ->setJSON([
                'success' => false,
                'message' => $message,
            ]);
    }

    private function validateModulRequest(?array $existing = null): ?string
    {
        $namaModulRaw = (string) $this->request->getPost('nama_modul');
        $idKategori = (int) $this->request->getPost('id_kat_m');
        $deskripsiRaw = (string) $this->request->getPost('ket_modul');
        $tipe = $this->normalizeModulType((string) $this->request->getPost('tipe_modul'));
        $urlRaw = (string) $this->request->getPost('url_modul');

        $missingFields = [];
        if (trim($namaModulRaw) === '') {
            $missingFields[] = 'Nama Modul';
        }
        if ($idKategori <= 0) {
            $missingFields[] = 'Kategori Modul';
        }
        if (trim($deskripsiRaw) === '') {
            $missingFields[] = 'Deskripsi';
        }
        if ($tipe === '') {
            $missingFields[] = 'Tipe Modul';
        }
        if ($tipe === 'link' && trim($urlRaw) === '') {
            $missingFields[] = 'URL Modul';
        }

        $file = $this->request->getFile('file_modul');
        $hasUpload = $this->hasUploadedFile($file);
        $needsUpload = $existing === null
            || ($existing['tipe'] ?? '') !== 'file'
            || empty($existing['path']);
        if ($tipe === 'file' && $needsUpload && ! $hasUpload) {
            $missingFields[] = 'File Modul';
        }

        if ($missingFields !== []) {
            return $this->buildMissingFieldsMessage($missingFields, $tipe === 'file' ? 5 : 5);
        }

        $fieldError = $this->validatePatternField(
            'Nama Modul',
            $namaModulRaw,
            3,
            50,
            '/^[\p{L}0-9\s]+$/u',
            'huruf, angka, dan spasi'
        )
            ?? ($idKategori > 0 && ! $this->kategoriModel->find($idKategori) ? 'Kategori modul tidak valid.' : null)
            ?? $this->validateMultilinePatternField(
                'Deskripsi',
                $deskripsiRaw,
                10,
                255,
                '/^[\p{L}\p{N}\s\p{P}\p{Sc}\p{Sk}]+$/u',
                'huruf, angka, spasi, tanda baca, dan baris baru'
            );

        if ($fieldError !== null) {
            return $fieldError;
        }

        if ($tipe === 'link') {
            $urlError = $this->validateHttpsUrlValue($urlRaw, 'URL Modul');
            if ($urlError !== null) {
                return $urlError;
            }
            if (mb_strlen(trim($urlRaw)) > 2048) {
                return 'URL Modul terlalu panjang.';
            }
            return null;
        }

        if (! $hasUpload) {
            return null;
        }

        if (! $file->isValid()) {
            return 'File upload tidak valid: ' . $file->getErrorString();
        }

        $extension = strtolower($file->getClientExtension() ?: pathinfo($file->getClientName(), PATHINFO_EXTENSION));
        if (! in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            return 'Format file harus PDF, DOC/DOCX, PPT/PPTX, XLS/XLSX, ZIP, atau RAR.';
        }

        $sizeKb = (int) ceil($file->getSize() / 1024);
        if ($sizeKb > self::MAX_FILE_SIZE_KB) {
            return 'Ukuran file maksimal 300 MB.';
        }

        return null;
    }

    private function collectModulPayload(): array
    {
        $tipe = $this->normalizeModulType((string) $this->request->getPost('tipe_modul'));

        $data = [
            'id_kat_m'    => (int) $this->request->getPost('id_kat_m'),
            'nama_modul'  => $this->normalizeSingleSpaces((string) $this->request->getPost('nama_modul')),
            'ket_modul'   => $this->normalizeMultilineText((string) $this->request->getPost('ket_modul')),
            'tipe'        => $tipe,
        ];

        if ($tipe === 'link') {
            $data['path'] = trim((string) $this->request->getPost('url_modul'));
        }

        return $data;
    }

    private function normalizeModulType(string $type): string
    {
        return in_array($type, ['link', 'file'], true) ? $type : '';
    }

    private function isHttpsUrl(string $url): bool
    {
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        return strtolower((string) parse_url($url, PHP_URL_SCHEME)) === 'https';
    }

    private function hasUploadedFile(?UploadedFile $file): bool
    {
        return $file instanceof UploadedFile
            && $file->getError() !== UPLOAD_ERR_NO_FILE;
    }

    private function ensureUploadDirectory(): string
    {
        $dir = WRITEPATH . self::UPLOAD_SUBDIR;
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        return $dir;
    }

    private function storeUploadedFile(UploadedFile $file): string
    {
        $dir = $this->ensureUploadDirectory();
        $extension = strtolower($file->getClientExtension() ?: pathinfo($file->getClientName(), PATHINFO_EXTENSION));
        $baseName = pathinfo($file->getClientName(), PATHINFO_FILENAME);
        $baseName = preg_replace('/[^A-Za-z0-9\-_]+/', '_', $baseName) ?? 'file';
        $baseName = trim($baseName, '_');
        $baseName = $baseName !== '' ? $baseName : 'file';
        $baseName = substr($baseName, 0, 120);

        $storedName = date('Ymd_His') . '_' . $baseName . '.' . $extension;
        $counter = 1;

        while (is_file($dir . DIRECTORY_SEPARATOR . $storedName)) {
            $storedName = date('Ymd_His') . '_' . $counter . '_' . $baseName . '.' . $extension;
            $counter++;
        }

        $file->move($dir, $storedName);

        return $storedName;
    }

    private function getStoredFilePath(string $storedName): string
    {
        return WRITEPATH . self::UPLOAD_SUBDIR . DIRECTORY_SEPARATOR . $storedName;
    }

    private function deleteStoredFile(?string $storedName): void
    {
        if (! $storedName) {
            return;
        }

        $filePath = $this->getStoredFilePath($storedName);
        if (is_file($filePath)) {
            @unlink($filePath);
        }
    }

    private function formatModulForView(array $row): array
    {
        $id = (int) ($row['id'] ?? 0);
        $type = (string) ($row['tipe'] ?? 'link');
        $path = (string) ($row['path'] ?? '');
        $fileName = $type === 'file' ? basename($path) : null;
        $storedPath = $type === 'file' && $path !== '' ? $this->getStoredFilePath($path) : null;
        $fileExists = $type === 'file' && $storedPath !== null && is_file($storedPath);
        $fileSize = $fileExists ? filesize($storedPath) : null;
        $extension = $type === 'file' ? strtoupper(pathinfo($fileName ?? '', PATHINFO_EXTENSION)) : null;
        $isPdf = $type === 'file' && strtolower((string) $extension) === 'pdf';

        $tableAssetLabel = $type === 'file'
            ? ($fileName ?: '-')
            : preg_replace('#^https?://#i', '', $path);

        $assetUrl = null;
        $assetTarget = null;
        $assetLabel = null;

        if ($type === 'link' && $path !== '') {
            $assetUrl = $path;
            $assetTarget = '_blank';
            $assetLabel = 'Buka Link';
        } elseif ($type === 'file' && $fileExists) {
            $assetUrl = $isPdf
                ? base_url('admin/data-modul/file/view/' . $id)
                : base_url('admin/data-modul/file/download/' . $id);
            $assetTarget = $isPdf ? '_blank' : null;
            $assetLabel = $isPdf ? 'Lihat File' : 'Unduh File';
        }

        return [
            'id'               => $id,
            'id_kat_m'         => (int) ($row['id_kat_m'] ?? 0),
            'nama_modul'       => (string) ($row['nama_modul'] ?? ''),
            'ket_modul'        => (string) ($row['ket_modul'] ?? ''),
            'tipe'             => $type,
            'path'             => $path,
            'nama_kategori'    => (string) ($row['nama_kategori'] ?? '-'),
            'tgl_dibuat'       => (string) ($row['tgl_dibuat'] ?? ''),
            'tgl_diubah'       => (string) ($row['tgl_diubah'] ?? ''),
            'table_asset'      => (string) $tableAssetLabel,
            'table_label'      => $type === 'link' ? 'Buka Link' : ($isPdf ? 'Buka File' : 'Unduh Modul'),
            'file_name'        => $fileName,
            'file_ext'         => $extension,
            'is_pdf'           => $isPdf,
            'file_size_label'  => $fileSize !== null ? $this->formatBytes((int) $fileSize) : null,
            'file_exists'      => $fileExists,
            'preview_url'      => $type === 'file' ? base_url('admin/data-modul/file/view/' . $id) : null,
            'download_url'     => $type === 'file' ? base_url('admin/data-modul/file/download/' . $id) : null,
            'external_url'     => $type === 'link' ? $path : null,
            'asset_url'        => $assetUrl,
            'asset_target'     => $assetTarget,
            'asset_label'      => $assetLabel,
            'icon_class'       => $this->resolveFileIcon($type, $path),
        ];
    }

    private function resolveFileIcon(string $type, string $path): string
    {
        if ($type === 'link') {
            return 'fas fa-link';
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return match ($extension) {
            'pdf'          => 'fas fa-file-pdf',
            'doc', 'docx'  => 'fas fa-file-word',
            'xls', 'xlsx'  => 'fas fa-file-excel',
            'ppt', 'pptx'  => 'fas fa-file-powerpoint',
            'zip', 'rar'   => 'fas fa-file-archive',
            default        => 'fas fa-file',
        };
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        }

        $units = ['KB', 'MB', 'GB', 'TB'];
        $value = $bytes / 1024;
        $unitIndex = 0;

        while ($value >= 1024 && $unitIndex < count($units) - 1) {
            $value /= 1024;
            $unitIndex++;
        }

        return number_format($value, $value >= 100 ? 0 : 2, '.', '') . ' ' . $units[$unitIndex];
    }
}
