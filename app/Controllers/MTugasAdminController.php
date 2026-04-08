<?php

namespace App\Controllers;

use App\Models\KategoriTugasModel;
use App\Models\TugasModel;
use App\Models\TugasSasaranModel;
use App\Models\TimTugasModel;
use App\Models\AnggotaTimTugasModel;
use App\Models\PengumpulanTugasModel;
use App\Models\ItemTugasModel;
use App\Models\PklModel;
use App\Models\KelompokPklModel;

class MTugasAdminController extends BaseController
{
    private const UPLOAD_SUBDIR = 'uploads/tugas';

    protected KategoriTugasModel   $katTugasModel;
    protected TugasModel           $tugasModel;
    protected TugasSasaranModel    $sasaranModel;
    protected TimTugasModel        $timModel;
    protected AnggotaTimTugasModel  $anggotaTimModel;
    protected PengumpulanTugasModel $pengumpulanModel;
    protected ItemTugasModel         $itemTugasModel;
    protected PklModel              $pklModel;
    protected KelompokPklModel      $kelompokModel;

    public function __construct()
    {
        $this->katTugasModel   = new KategoriTugasModel();
        $this->tugasModel      = new TugasModel();
        $this->sasaranModel    = new TugasSasaranModel();
        $this->timModel        = new TimTugasModel();
        $this->anggotaTimModel  = new AnggotaTimTugasModel();
        $this->pengumpulanModel = new PengumpulanTugasModel();
        $this->itemTugasModel   = new ItemTugasModel();
        $this->pklModel         = new PklModel();
        $this->kelompokModel    = new KelompokPklModel();
    }

    private function buildMtugasConfig(): string
    {
        $urlKategori = base_url('admin/manajemen-tugas/kategori');
        $urlTugas    = base_url('admin/manajemen-tugas/tugas');
        $activeTab = strtolower((string) ($this->request->getGet('tab') ?? 'kategori'));
        if (! in_array($activeTab, ['kategori', 'tugas'], true)) {
            $activeTab = 'kategori';
        }
        return '<script>
            window.MTUGAS = {
                urlKategoriList  : "' . $urlKategori . '/list",
                urlKategoriStore : "' . $urlKategori . '/store",
                urlKategoriUpdate: "' . $urlKategori . '/update",
                urlKategoriDelete: "' . $urlKategori . '/delete",
                urlTugasList     : "' . $urlTugas . '/list",
                urlTugasTambah   : "' . $urlTugas . '/tambah",
                urlTugasDetail   : "' . $urlTugas . '/detail",
                urlTugasUbah     : "' . $urlTugas . '/ubah",
                urlTugasDelete   : "' . $urlTugas . '/delete",
                activeTab        : "' . $activeTab . '",
                csrfName : document.querySelector(\'meta[name="csrf-token-name"]\')?.content ?? \'\',
                csrfHash : document.querySelector(\'meta[name="csrf-token-hash"]\')?.content ?? \'\',
            };
        </script>';
    }

    private function baseCss(): string
    {
        return '<link rel="stylesheet" href="' . base_url('assets/css/modules/admin/manajemen_tugas.css') . '">';
    }

    public function index()
    {
        $activeTab = strtolower((string) ($this->request->getGet('tab') ?? 'kategori'));
        if (!in_array($activeTab, ['kategori', 'tugas'], true)) {
            $activeTab = 'kategori';
        }

        $tabLabel = $activeTab === 'tugas' ? 'Tugas' : 'Kategori Tugas';

        $data = [
            'page_title'     => 'Manajemen Tugas / Penugasan',
            'page_title_sub' => $tabLabel,
            'active_menu'    => 'penugasan',
            'active_tab'     => $activeTab,
            'kategoriList'   => $this->katTugasModel->getAllKategori(),
            'extra_css'      => $this->baseCss(),
            'extra_js'       => $this->buildMtugasConfig()
                . '<script src="' . base_url('assets/js/modules/admin/penugasan.js') . '"></script>',
        ];
        $data['content'] = view('dashboard_admin/manajemen_tugas/penugasan/main_penugasan', $data);
        return view('Layouts/dashboard_layout', $data);
    }

    public function loadTab(string $tab)
    {
        $tab = strtolower(trim($tab));
        if (! in_array($tab, ['kategori', 'tugas'], true)) {
            $tab = 'kategori';
        }

        return redirect()->to(base_url('admin/manajemen-tugas/penugasan?tab=' . $tab));
    }

    public function pengumpulan()
    {
        $activeTab = strtolower((string) ($this->request->getGet('tab') ?? 'mandiri'));
        if (! in_array($activeTab, ['mandiri', 'kelompok', 'tim'], true)) {
            $activeTab = 'mandiri';
        }

        $tabLabels = [
            'mandiri'  => 'Tugas Mandiri',
            'kelompok' => 'Tugas Kelompok',
            'tim'      => 'Tim Tugas',
        ];

        $data = [
            'page_title'        => 'Manajemen Tugas / Pengumpulan',
            'page_title_sub'    => $tabLabels[$activeTab],
            'active_menu'       => 'pengumpulan',
            'active_tab'        => $activeTab,
            'welcome_heading'   => 'Pengumpulan Tugas',
            'welcome_subheading' => 'Pantau pengumpulan tugas berdasarkan mode mandiri, kelompok, dan tim.',
            'mandiriRows'       => $this->getPengumpulanMandiriRows(),
            'kelompokRows'      => $this->getPengumpulanKelompokRows(),
            'timRows'           => $this->getPengumpulanTimRows(),
            'extra_css'         => $this->baseCss(),
            'extra_js'          => '<script>window.MTUGAS_PENGUMPULAN = { activeTab: "' . $activeTab . '" };</script>'
                . '<script src="' . base_url('assets/js/modules/admin/pengumpulan_tugas.js') . '"></script>',
        ];

        $data['content'] = view('dashboard_admin/manajemen_tugas/pengumpulan/main_pengumpulan', $data);
        return view('Layouts/dashboard_layout', $data);
    }

    public function detailPengumpulan(string $type, int $primaryId, int $secondaryId = 0)
    {
        $type = strtolower(trim($type));
        $detail = $this->getPengumpulanDetailData($type, $primaryId, $secondaryId);

        if (! $detail) {
            return redirect()->to(base_url('admin/manajemen-tugas/pengumpulan'));
        }

        $tabLabel = match ($type) {
            'kelompok' => 'Tugas Kelompok',
            'tim'      => 'Tim Tugas',
            default    => 'Tugas Mandiri',
        };

        $data = [
            'page_title'     => 'Manajemen Tugas / Pengumpulan',
            'page_title_sub' => 'Detail Pengumpulan',
            'active_menu'    => 'pengumpulan',
            'active_tab'     => $type === 'tim' ? 'tim' : ($type === 'kelompok' ? 'kelompok' : 'mandiri'),
            'tabLabel'       => $tabLabel,
            'detail'         => $detail,
            'extra_css'      => $this->baseCss(),
            'extra_js'       => '<script>window.MTUGAS_PENGUMPULAN_DETAIL = ' . json_encode([
                'itemsMeta'        => array_map(static function (array $item): array {
                    return [
                        'id_item'       => (int) ($item['id_item'] ?? 0),
                        'tipe_item'     => (string) ($item['tipe_item'] ?? 'link'),
                        'display_value' => (string) ($item['display_value'] ?? ''),
                        'status_raw'    => (string) ($item['status_raw'] ?? 'belum_dikirim'),
                        'status_label'  => (string) ($item['status_label'] ?? ''),
                        'status_class'  => (string) ($item['status_class'] ?? ''),
                        'komentar'      => (string) ($item['komentar'] ?? ''),
                        'action_url'    => (string) ($item['action_url'] ?? ''),
                        'action_label'  => (string) ($item['action_label'] ?? ''),
                        'action_target' => $item['action_target'] ?? null,
                    ];
                }, $detail['items'] ?? []),
                'reviewUrlBase'    => base_url('admin/manajemen-tugas/pengumpulan/item'),
                'oldReviewItemId'  => (int) (session()->getFlashdata('review_item_id') ?? 0),
                'oldKomentar'      => (string) (session()->getFlashdata('review_komentar') ?? ''),
                'csrfName'         => csrf_token(),
                'csrfHash'         => csrf_hash(),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . ';</script>'
                . '<script src="' . base_url('assets/js/modules/admin/detail_pengumpulan.js') . '?v=20260404-1"></script>',
        ];

        $data['content'] = view('dashboard_admin/manajemen_tugas/pengumpulan/detail_pengumpulan', $data);
        return view('Layouts/dashboard_layout', $data);
    }

    public function reviewPengumpulanItem(int $idItem)
    {
        $item = $this->itemTugasModel->findAdminItemById($idItem);
        if (! $item) {
            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(404)->setJSON([
                    'success' => false,
                    'message' => 'Item pengumpulan tidak ditemukan.',
                    'csrfHash' => csrf_hash(),
                ]);
            }
            return redirect()->back()->with('error', 'Item pengumpulan tidak ditemukan.');
        }

        $reviewStatus = strtolower(trim((string) ($this->request->getPost('review_status') ?? '')));
        if (! in_array($reviewStatus, ['diterima', 'revisi'], true)) {
            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(422)->setJSON([
                    'success' => false,
                    'message' => 'Aksi review tidak valid.',
                    'csrfHash' => csrf_hash(),
                ]);
            }
            return redirect()->back()
                ->with('error', 'Aksi review tidak valid.')
                ->with('review_item_id', $idItem)
                ->with('review_komentar', trim((string) ($this->request->getPost('komentar') ?? '')));
        }

        if (($item['status_item'] ?? 'belum_dikirim') !== 'dikirim') {
            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(409)->setJSON([
                    'success' => false,
                    'message' => 'Item tugas ini sudah diulas sebelumnya.',
                    'csrfHash' => csrf_hash(),
                ]);
            }
            return redirect()->back()->with('error', 'Item tugas ini sudah diulas sebelumnya.');
        }

        $komentarRaw = (string) ($this->request->getPost('komentar') ?? '');
        $komentar = trim($komentarRaw);
        if ($reviewStatus === 'revisi' && $komentar === '') {
            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(422)->setJSON([
                    'success' => false,
                    'message' => 'Keterangan revisi wajib diisi.',
                    'csrfHash' => csrf_hash(),
                ]);
            }
            return redirect()->back()
                ->with('error', 'Keterangan revisi wajib diisi.')
                ->with('review_item_id', $idItem)
                ->with('review_komentar', $komentar);
        }

        if ($reviewStatus === 'revisi') {
            $commentError = $this->validateMultilinePatternField(
                'Keterangan Revisi',
                $komentarRaw,
                10,
                255,
                '/^[\p{L}\p{N}\s\p{P}\p{Sc}\p{Sk}]+$/u',
                'huruf, angka, spasi, tanda baca, dan baris baru'
            );
            if ($commentError !== null) {
                if ($this->request->isAJAX()) {
                    return $this->response->setStatusCode(422)->setJSON([
                        'success' => false,
                        'message' => $commentError,
                        'csrfHash' => csrf_hash(),
                    ]);
                }
                return redirect()->back()
                    ->with('error', $commentError)
                    ->with('review_item_id', $idItem)
                    ->with('review_komentar', $komentar);
            }
        }

        $payload = [
            'status_item' => $reviewStatus,
            'komentar'    => $reviewStatus === 'revisi' ? $this->normalizeMultilineText($komentarRaw) : null,
        ];

        if ($this->itemTugasModel->update($idItem, $payload) === false) {
            $errors = implode(' ', $this->itemTugasModel->errors());
            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(500)->setJSON([
                    'success' => false,
                    'message' => $errors !== '' ? $errors : 'Gagal menyimpan review tugas.',
                    'csrfHash' => csrf_hash(),
                ]);
            }
            return redirect()->back()
                ->with('error', $errors !== '' ? $errors : 'Gagal menyimpan review tugas.')
                ->with('review_item_id', $idItem)
                ->with('review_komentar', $komentar);
        }

        $message = $reviewStatus === 'diterima'
            ? 'Jawaban tugas berhasil disetujui.'
            : 'Jawaban tugas berhasil dikembalikan untuk revisi.';

        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'success' => true,
                'message' => $message,
                'csrfHash' => csrf_hash(),
                'item' => [
                    'id_item' => $idItem,
                    'status_raw' => $reviewStatus,
                    'status_label' => $this->mapItemStatusLabel($reviewStatus),
                    'status_class' => $this->mapItemStatusClass($reviewStatus),
                    'komentar' => $payload['komentar'] ?? '',
                ],
            ]);
        }

        return redirect()->back()->with('success', $message);
    }

    public function previewPengumpulanItem(int $idItem)
    {
        $item = $this->itemTugasModel->findAdminItemById($idItem);
        if (! $item) {
            return redirect()->back()->with('error', 'Item pengumpulan tidak ditemukan.');
        }

        if (($item['tipe_item'] ?? '') === 'link') {
            return redirect()->to((string) ($item['data_item'] ?? ''));
        }

        $filePath = $this->getStoredTaskFilePath((string) ($item['data_item'] ?? ''));
        if (! is_file($filePath)) {
            return redirect()->back()->with('error', 'File tugas tidak ditemukan di server.');
        }

        $extension = strtolower(pathinfo((string) ($item['data_item'] ?? ''), PATHINFO_EXTENSION));
        if ($extension !== 'pdf') {
            return redirect()->to(base_url('admin/manajemen-tugas/pengumpulan/item/' . $idItem . '/download'));
        }

        return $this->response
            ->download($filePath, null, true)
            ->setFileName(basename((string) ($item['data_item'] ?? '')))
            ->inline();
    }

    public function downloadPengumpulanItem(int $idItem)
    {
        $item = $this->itemTugasModel->findAdminItemById($idItem);
        if (! $item) {
            return redirect()->back()->with('error', 'Item pengumpulan tidak ditemukan.');
        }

        if (($item['tipe_item'] ?? '') === 'link') {
            return redirect()->to((string) ($item['data_item'] ?? ''));
        }

        $filePath = $this->getStoredTaskFilePath((string) ($item['data_item'] ?? ''));
        if (! is_file($filePath)) {
            return redirect()->back()->with('error', 'File tugas tidak ditemukan di server.');
        }

        return $this->response
            ->download($filePath, null, true)
            ->setFileName(basename((string) ($item['data_item'] ?? '')));
    }

    public function getKategoriList()
    {
        return $this->response->setJSON(['data' => $this->katTugasModel->getAllKategori()]);
    }

    public function storeKategori()
    {
        $namaRaw = (string) ($this->request->getPost('nama_kategori') ?? '');
        $nama = trim($namaRaw);
        $mode = $this->request->getPost('mode_pengumpulan');
        $missingFields = [];
        if ($nama === '') {
            $missingFields[] = 'Nama Kategori';
        }
        if (! in_array($mode, ['individu', 'kelompok'], true)) {
            $missingFields[] = 'Mode Pengumpulan';
        }
        if ($missingFields !== []) {
            return $this->jsonError($this->buildMissingFieldsMessage($missingFields, 2));
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
            return $this->jsonError($error);
        }
        if (!in_array($mode, ['individu', 'kelompok'])) return $this->jsonError('Mode pengumpulan tidak valid.');
        $this->katTugasModel->insert(['nama_kat_tugas' => $this->normalizeSingleSpaces($namaRaw), 'mode_pengumpulan' => $mode]);
        return $this->response->setJSON(['success' => true, 'message' => 'Kategori berhasil ditambahkan.']);
    }

    public function updateKategori(int $id)
    {
        $existing = $this->katTugasModel->find($id);
        if (!$existing) return $this->jsonError('Kategori tidak ditemukan.', 404);
        $namaRaw = (string) ($this->request->getPost('nama_kategori') ?? '');
        $nama = trim($namaRaw);
        $mode = $this->request->getPost('mode_pengumpulan');
        $missingFields = [];
        if ($nama === '') {
            $missingFields[] = 'Nama Kategori';
        }
        if (! in_array($mode, ['individu', 'kelompok'], true)) {
            $missingFields[] = 'Mode Pengumpulan';
        }
        if ($missingFields !== []) {
            return $this->jsonError($this->buildMissingFieldsMessage($missingFields, 2));
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
            return $this->jsonError($error);
        }
        if (!in_array($mode, ['individu', 'kelompok'])) return $this->jsonError('Mode tidak valid.');
        $this->katTugasModel->update($id, ['nama_kat_tugas' => $this->normalizeSingleSpaces($namaRaw), 'mode_pengumpulan' => $mode]);
        return $this->response->setJSON(['success' => true, 'message' => 'Kategori berhasil diperbarui.']);
    }

    public function deleteKategori(int $id)
    {
        $existing = $this->katTugasModel->find($id);
        if (!$existing) return $this->jsonError('Kategori tidak ditemukan.', 404);
        if ($this->tugasModel->where('id_kat_tugas', $id)->first()) {
            return $this->jsonError('Kategori "' . $existing['nama_kat_tugas'] . '" tidak dapat dihapus karena masih digunakan oleh data tugas.');
        }
        $this->katTugasModel->delete($id);
        return $this->response->setJSON(['success' => true, 'message' => 'Kategori berhasil dihapus.']);
    }

    public function getTugasList()
    {
        return $this->response->setJSON(['data' => $this->tugasModel->getListTugas()]);
    }

    public function tambahTugas()
    {
        $data = [
            'page_title'     => 'Manajemen Tugas',
            'page_title_sub' => 'Tambah Tugas Baru',
            'active_menu'    => 'penugasan',
            'kategoriList'   => $this->katTugasModel->getAllKategori(),
            'editor_nama'    => session()->get('panggilan') ?: session()->get('nama') ?: 'Admin',
            'extra_css'      => $this->baseCss(),
            'extra_js'       => '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>'
                . '<script>window.BASE_URL_TUGAS = "' . base_url('admin/manajemen-tugas') . '";</script>'
                . '<script src="' . base_url('assets/js/modules/admin/tambah_tugas.js') . '"></script>',
        ];
        $data['content'] = view('dashboard_admin/manajemen_tugas/penugasan/tugas_tambah', $data);
        return view('Layouts/dashboard_layout', $data);
    }

    public function sasaranTugas()
    {
        $data = [
            'page_title'     => 'Manajemen Tugas',
            'page_title_sub' => 'Pilih Sasaran Tugas',
            'active_menu'    => 'penugasan',
            'extra_css'      => $this->baseCss(),
            'extra_js'       => '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>'
                . '<script>
                                    window.BASE_URL_API    = "' . base_url('admin/manajemen-tugas/api') . '";
                                    window.URL_STORE_TUGAS  = "' . base_url('admin/manajemen-tugas/tugas/store') . '";
                                    window.URL_REDIRECT     = "' . base_url('admin/manajemen-tugas/penugasan?tab=tugas') . '";
                                    window.CSRF_HASH        = "' . csrf_hash() . '";
                                 </script>'
                . '<script src="' . base_url('assets/js/modules/admin/sasaran_tugas.js') . '"></script>',
        ];
        $data['content'] = view('dashboard_admin/manajemen_tugas/penugasan/tugas_sasaran', $data);
        return view('Layouts/dashboard_layout', $data);
    }

    public function detailTugas(int $idTugas)
    {
        $tugas = $this->getAdminTugasDetail($idTugas);
        if (! $tugas) {
            return redirect()->to(base_url('admin/manajemen-tugas/penugasan?tab=tugas'));
        }

        $sasaranList = $this->getTugasSasaranDisplay($idTugas);
        $pengumpulanList = $this->getTugasPengumpulanDisplay($idTugas);
        $jumlahTerkumpul = count(array_filter(
            $pengumpulanList,
            static fn(array $row): bool => ! empty($row['tgl_pengumpulan'])
        ));

        $data = [
            'page_title'        => 'Manajemen Tugas',
            'page_title_sub'    => 'Detail Tugas',
            'active_menu'       => 'penugasan',
            'tugas'             => $tugas,
            'sasaranList'       => $sasaranList,
            'pengumpulanList'   => $pengumpulanList,
            'totalPenerima'     => count($pengumpulanList),
            'totalTerkumpul'    => $jumlahTerkumpul,
            'totalBelumKumpul'  => max(count($pengumpulanList) - $jumlahTerkumpul, 0),
            'extra_css'         => $this->baseCss(),
            'extra_js'          => '<script src="' . base_url('assets/js/modules/admin/detail_tugas.js') . '"></script>',
        ];

        $data['content'] = view('dashboard_admin/manajemen_tugas/penugasan/detail_tugas', $data);
        return view('Layouts/dashboard_layout', $data);
    }

    public function ubahTugas(int $idTugas)
    {
        $tugas = $this->getAdminTugasDetail($idTugas);
        if (! $tugas) {
            return redirect()->to(base_url('admin/manajemen-tugas/penugasan?tab=tugas'));
        }

        $data = [
            'page_title'     => 'Manajemen Tugas',
            'page_title_sub' => 'Ubah Tugas',
            'active_menu'    => 'penugasan',
            'tugas'          => $tugas,
            'kategoriList'   => $this->katTugasModel
                ->where('mode_pengumpulan', $tugas['mode_pengumpulan'] ?? 'individu')
                ->orderBy('nama_kat_tugas', 'ASC')
                ->findAll(),
            'sasaranList'    => $this->getTugasSasaranDisplay($idTugas),
            'extra_css'      => $this->baseCss(),
            'extra_js'       => '<script src="' . base_url('assets/js/modules/admin/detail_tugas.js') . '"></script>',
        ];

        $data['content'] = view('dashboard_admin/manajemen_tugas/penugasan/edit_tugas', $data);
        return view('Layouts/dashboard_layout', $data);
    }

    public function storeTugas()
    {
        $json = $this->request->getJSON();
        if (!$json || !isset($json->ketentuan) || !isset($json->sasaran)) {
            return $this->jsonError('Payload data tidak valid.');
        }

        $ketentuan = $json->ketentuan;
        $sasaran   = $json->sasaran;
        $kategoriId = (int) ($ketentuan->kategori_id ?? 0);
        $nama       = trim((string) ($ketentuan->nama ?? ''));
        $deskripsi  = trim((string) ($ketentuan->deskripsi ?? ''));
        $target     = (int) ($ketentuan->target ?? 0);
        $deadlineRaw = (string) ($ketentuan->deadline ?? '');
        $deadlineTs  = strtotime($deadlineRaw);

        $missingFields = [];
        if ($kategoriId < 1) {
            $missingFields[] = 'Kategori Tugas';
        }
        if ($nama === '') {
            $missingFields[] = 'Nama Tugas';
        }
        if ($deskripsi === '') {
            $missingFields[] = 'Deskripsi / Instruksi';
        }
        if ($target < 1) {
            $missingFields[] = 'Target Jumlah Item';
        }
        if ($deadlineRaw === '' || ! $deadlineTs) {
            $missingFields[] = 'Tenggat Waktu (Deadline)';
        }

        if ($missingFields !== []) {
            return $this->jsonError($this->buildMissingFieldsMessage($missingFields, 5));
        }

        $fieldError = $this->validatePatternField(
            'Nama Tugas',
            (string) ($ketentuan->nama ?? ''),
            3,
            50,
            '/^[\p{L}0-9\s]+$/u',
            'huruf, angka, dan spasi'
        )
            ?? $this->validateMultilinePatternField(
                'Deskripsi / Instruksi',
                (string) ($ketentuan->deskripsi ?? ''),
                10,
                255,
                '/^[\p{L}\p{N}\s\p{P}\p{Sc}\p{Sk}]+$/u',
                'huruf, angka, spasi, tanda baca, dan baris baru'
            )
            ?? $this->validateNumberRange('Target Jumlah Item', $target, 1);

        if ($fieldError !== null) {
            return $this->jsonError($fieldError);
        }

        $deadlineError = $this->validateDeadlineValue($deadlineRaw, time() + (30 * 60));
        if ($deadlineError !== null) {
            return $this->jsonError($deadlineError);
        }

        $targetType = $this->normalizeTargetType((string) ($sasaran->tipe ?? ''));
        if (!$targetType) {
            return $this->jsonError('Tipe sasaran tugas tidak valid.');
        }

        $targetIds = array_values(array_unique(array_filter(
            array_map('intval', (array) ($sasaran->target_ids ?? [])),
            static fn($id) => $id > 0
        )));

        if (empty($targetIds)) {
            return $this->jsonError('Pilih minimal 1 sasaran tugas.');
        }

        $currentUserId = (int) (session()->get('user_id') ?? session()->get('id_user') ?? 0);
        if ($currentUserId < 1) {
            return $this->jsonError('Sesi login tidak valid. Silakan login ulang.', 401);
        }

        $db = \Config\Database::connect();
        $db->transStart();
        try {
            $insertedTask = $this->tugasModel->insert([
                'id_user'       => $currentUserId,
                'id_kat_tugas'  => $kategoriId,
                'nama_tugas'    => $this->normalizeSingleSpaces((string) ($ketentuan->nama ?? '')),
                'deskripsi'     => $this->normalizeMultilineText((string) ($ketentuan->deskripsi ?? '')),
                'target_jumlah' => $target,
                'deadline'      => date('Y-m-d H:i:s', $deadlineTs),
            ], true);

            if ($insertedTask === false) {
                $errors = implode(' ', $this->tugasModel->errors());
                throw new \RuntimeException($errors !== '' ? $errors : 'Gagal membuat data tugas.');
            }

            $idTugas = (int) $insertedTask;
            if ($idTugas < 1) {
                throw new \RuntimeException('Gagal membuat data tugas.');
            }

            foreach ($targetIds as $targetId) {
                if ($this->sasaranModel->insert($this->buildSasaranPayload($idTugas, $targetType, $targetId), false) === false) {
                    $errors = implode(' ', $this->sasaranModel->errors());
                    throw new \RuntimeException($errors !== '' ? $errors : 'Gagal menyimpan sasaran tugas.');
                }
            }

            $recipientRows = $this->buildPengumpulanRecipients($targetType, $targetIds);
            if (empty($recipientRows)) {
                throw new \RuntimeException('Tidak ada penerima aktif yang dapat ditugaskan.');
            }

            foreach ($recipientRows as $recipient) {
                if ($this->pengumpulanModel->insert(array_merge(['id_tugas' => $idTugas], $recipient), false) === false) {
                    $errors = implode(' ', $this->pengumpulanModel->errors());
                    throw new \RuntimeException($errors !== '' ? $errors : 'Gagal membuat data penerima tugas.');
                }
            }

            $db->transComplete();
            if (!$db->transStatus()) throw new \Exception('Database transaction error.');
            return $this->response->setJSON(['success' => true, 'message' => 'Tugas dan sasaran berhasil disimpan!']);
        } catch (\Throwable $e) {
            $db->transRollback();
            return $this->jsonError('Gagal menyimpan tugas: ' . $e->getMessage());
        }
    }

    public function updateTugas(int $idTugas)
    {
        $tugas = $this->getAdminTugasDetail($idTugas);
        if (! $tugas) {
            return $this->respondTugasFormError('Data tugas tidak ditemukan.', 404);
        }

        $idKategori = (int) ($this->request->getPost('id_kat_tugas') ?? 0);
        $nama       = trim((string) ($this->request->getPost('nama_tugas') ?? ''));
        $deskripsi  = trim((string) ($this->request->getPost('deskripsi') ?? ''));
        $target     = (int) ($this->request->getPost('target_jumlah') ?? 0);
        $deadline   = trim((string) ($this->request->getPost('deadline') ?? ''));
        $deadlineTs = strtotime($deadline);

        $missingFields = [];
        if ($idKategori < 1) {
            $missingFields[] = 'Kategori Tugas';
        }
        if ($nama === '') {
            $missingFields[] = 'Nama Tugas';
        }
        if ($deskripsi === '') {
            $missingFields[] = 'Deskripsi / Instruksi';
        }
        if ($target < 1) {
            $missingFields[] = 'Target Jumlah Item';
        }
        if ($deadline === '' || ! $deadlineTs) {
            $missingFields[] = 'Tenggat Waktu (Deadline)';
        }

        if ($missingFields !== []) {
            return $this->respondTugasFormError($this->buildMissingFieldsMessage($missingFields, 5));
        }

        $fieldError = $this->validatePatternField(
            'Nama Tugas',
            (string) ($this->request->getPost('nama_tugas') ?? ''),
            3,
            50,
            '/^[\p{L}0-9\s]+$/u',
            'huruf, angka, dan spasi'
        )
            ?? $this->validateMultilinePatternField(
                'Deskripsi / Instruksi',
                (string) ($this->request->getPost('deskripsi') ?? ''),
                10,
                255,
                '/^[\p{L}\p{N}\s\p{P}\p{Sc}\p{Sk}]+$/u',
                'huruf, angka, spasi, tanda baca, dan baris baru'
            )
            ?? $this->validateNumberRange('Target Jumlah Item', $target, 1);

        if ($fieldError !== null) {
            return $this->respondTugasFormError($fieldError);
        }

        $minimumDeadline = max(
            time(),
            strtotime((string) ($tugas['created_at'] ?? 'now')) + (30 * 60)
        );
        $deadlineError = $this->validateDeadlineValue($deadline, $minimumDeadline);
        if ($deadlineError !== null) {
            return $this->respondTugasFormError($deadlineError);
        }

        $kategori = $this->katTugasModel->find($idKategori);
        if (! $kategori) {
            return $this->respondTugasFormError('Kategori tugas tidak ditemukan.');
        }

        if (($kategori['mode_pengumpulan'] ?? '') !== ($tugas['mode_pengumpulan'] ?? '')) {
            return $this->respondTugasFormError('Kategori yang dipilih harus memiliki mode pengumpulan yang sama.');
        }

        $updated = $this->tugasModel->update($idTugas, [
            'id_kat_tugas'  => $idKategori,
            'nama_tugas'    => $this->normalizeSingleSpaces((string) ($this->request->getPost('nama_tugas') ?? '')),
            'deskripsi'     => $this->normalizeMultilineText((string) ($this->request->getPost('deskripsi') ?? '')),
            'target_jumlah' => $target,
            'deadline'      => date('Y-m-d H:i:s', $deadlineTs),
        ]);

        if ($updated === false) {
            $errors = implode(' ', $this->tugasModel->errors());
            return $this->respondTugasFormError($errors !== '' ? $errors : 'Gagal memperbarui tugas.');
        }

        return $this->respondTugasFormSuccess(
            'Tugas berhasil diperbarui.',
            base_url('admin/manajemen-tugas/tugas/detail/' . $idTugas)
        );
    }

    public function deleteTugas(int $id)
    {
        $tugas = $this->tugasModel->find($id);
        if (!$tugas) return $this->jsonError('Tugas tidak ditemukan.', 404);
        $db = \Config\Database::connect();
        $db->transStart();
        $this->sasaranModel->where('id_tugas', $id)->delete();
        $this->tugasModel->delete($id);
        $db->transComplete();
        if (!$db->transStatus()) return $this->jsonError('Gagal menghapus tugas.');
        return $this->response->setJSON(['success' => true, 'message' => 'Tugas "' . $tugas['nama_tugas'] . '" berhasil dihapus.']);
    }

    public function getPklAktif()
    {
        return $this->response->setJSON(['data' => $this->kelompokModel->getPklAktifForTugas()]);
    }

    public function getKelompokAktif()
    {
        return $this->response->setJSON(['data' => $this->kelompokModel->getKelompokAktifForTugas()]);
    }

    public function getTimTugas()
    {
        return $this->response->setJSON(['data' => $this->timModel->getAllWithStats()]);
    }

    public function getPklAktifWithKategori()
    {
        return $this->response->setJSON(['data' => $this->kelompokModel->getPklAktifWithKategori()]);
    }

    public function storeTimTugas()
    {
        $json = $this->request->getJSON();
        if (!$json || empty($json->nama_tim)) {
            return $this->jsonError('Nama tim tidak boleh kosong.');
        }
        $namaTim    = trim($json->nama_tim);
        $deskripsi  = trim($json->deskripsi ?? '');
        $fieldError = $this->validateLooseTextField('Nama Tim', (string) ($json->nama_tim ?? ''), 5, 20);
        if ($fieldError === null && $deskripsi !== '') {
            $fieldError = $this->validateMultilinePatternField(
                'Deskripsi Tim',
                (string) ($json->deskripsi ?? ''),
                1,
                255,
                '/^[\p{L}\p{N}\s\p{P}\p{Sc}\p{Sk}]+$/u',
                'huruf, angka, spasi, tanda baca, dan baris baru'
            );
        }
        if ($fieldError !== null) {
            return $this->jsonError($fieldError);
        }
        $anggotaIds = array_values(array_unique(array_filter(
            array_map('intval', (array) ($json->anggota_ids ?? [])),
            static fn($id) => $id > 0
        )));
        if (empty($anggotaIds)) {
            return $this->jsonError('Tim harus memiliki minimal 1 anggota.');
        }
        $db = \Config\Database::connect();
        $db->transStart();
        try {
            $idTim = $this->timModel->insert([
                'nama_tim' => $this->normalizeSingleSpaces((string) ($json->nama_tim ?? '')),
                'deskripsi' => $deskripsi !== '' ? $this->normalizeMultilineText((string) ($json->deskripsi ?? '')) : null,
            ]);
            foreach ($anggotaIds as $idPkl) {
                $this->anggotaTimModel->insert(['id_tim' => $idTim, 'id_pkl' => (int) $idPkl]);
            }
            $db->transComplete();
            if (!$db->transStatus()) throw new \Exception('Database error.');
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Tim "' . $namaTim . '" berhasil dibuat dengan ' . count($anggotaIds) . ' anggota.',
                'data'    => $this->timModel->getAllWithStats(),
            ]);
        } catch (\Throwable $e) {
            $db->transRollback();
            return $this->jsonError('Gagal membuat tim: ' . $e->getMessage());
        }
    }

    private function jsonError(string $msg, int $code = 400)
    {
        return $this->response->setStatusCode($code)->setJSON(['success' => false, 'message' => $msg]);
    }

    private function normalizeTargetType(string $type): ?string
    {
        return match (strtolower(trim($type))) {
            'mandiri', 'individu' => 'individu',
            'kelompok'            => 'kelompok',
            'tim', 'tim_tugas'    => 'tim_tugas',
            default               => null,
        };
    }

    private function isDeadlineOnOrAfterToday(int $timestamp): bool
    {
        return date('Y-m-d', $timestamp) >= date('Y-m-d');
    }

    private function buildSasaranPayload(int $idTugas, string $targetType, int $targetId): array
    {
        return [
            'id_tugas'    => $idTugas,
            'target_tipe' => $targetType,
            'id_pkl'      => $targetType === 'individu' ? $targetId : null,
            'id_kelompok' => $targetType === 'kelompok' ? $targetId : null,
            'id_tim'      => $targetType === 'tim_tugas' ? $targetId : null,
        ];
    }

    private function buildPengumpulanRecipients(string $targetType, array $targetIds): array
    {
        if (empty($targetIds)) {
            return [];
        }

        if ($targetType === 'individu') {
            $rows = $this->pklModel->getActiveRecipientRowsByPklIds($targetIds);
        } elseif ($targetType === 'kelompok') {
            $rows = $this->pklModel->getActiveRecipientRowsByKelompokIds($targetIds);
        } else {
            $rows = $this->anggotaTimModel->getActiveRecipientRowsByTimIds($targetIds);
        }

        $uniqueRecipients = [];

        foreach ($rows as $row) {
            $idPkl = (int) ($row['id_pkl'] ?? 0);
            if ($idPkl < 1 || isset($uniqueRecipients[$idPkl])) {
                continue;
            }

            $uniqueRecipients[$idPkl] = [
                'id_pkl'      => $idPkl,
                'id_kelompok' => isset($row['id_kelompok']) ? ((int) $row['id_kelompok'] ?: null) : null,
                'id_tim'      => $targetType === 'tim_tugas' ? ((int) ($row['id_tim'] ?? 0) ?: null) : null,
            ];
        }

        return array_values($uniqueRecipients);
    }

    private function getPengumpulanMandiriRows(): array
    {
        $rows = $this->pengumpulanModel->getMandiriRowsForAdmin();

        $statsMap = $this->getItemStatsMap(array_map('intval', array_column($rows, 'id_pengumpulan_tgs')));

        return array_map(function (array $row) use ($statsMap): array {
            $status = $this->summarizePengumpulanStatus(
                [(int) ($row['id_pengumpulan_tgs'] ?? 0)],
                $row['tgl_pengumpulan'] ?? null,
                $statsMap
            );

            return [
                'id'                => (int) ($row['id_pengumpulan_tgs'] ?? 0),
                'nama_target'       => $row['nama_lengkap'] ?? ('PKL #' . (int) ($row['id_pkl'] ?? 0)),
                'nama_tugas'        => $row['nama_tugas'] ?? '-',
                'waktu_pengumpulan' => $row['tgl_pengumpulan'] ?? null,
                'deadline'          => $row['deadline'] ?? null,
                'status_label'      => $status['label'],
                'status_class'      => $status['class'],
                'detail_url'        => base_url('admin/manajemen-tugas/pengumpulan/detail/mandiri/' . (int) ($row['id_pengumpulan_tgs'] ?? 0)),
            ];
        }, $rows);
    }

    private function getPengumpulanKelompokRows(): array
    {
        $rows = $this->kelompokModel->getPengumpulanRowsForAdmin();

        $allPengumpulanIds = [];
        foreach ($rows as $row) {
            $allPengumpulanIds = array_merge($allPengumpulanIds, $this->csvIdsToArray($row['pengumpulan_ids'] ?? ''));
        }
        $statsMap = $this->getItemStatsMap($allPengumpulanIds);

        return array_map(function (array $row) use ($statsMap): array {
            $ids = $this->csvIdsToArray($row['pengumpulan_ids'] ?? '');
            $status = $this->summarizePengumpulanStatus($ids, $row['waktu_pengumpulan'] ?? null, $statsMap);

            return [
                'id_tugas'          => (int) ($row['id_tugas'] ?? 0),
                'id_target'         => (int) ($row['id_kelompok'] ?? 0),
                'nama_target'       => $row['nama_target'] ?? ('Kelompok #' . (int) ($row['id_kelompok'] ?? 0)),
                'nama_tugas'        => $row['nama_tugas'] ?? '-',
                'waktu_pengumpulan' => $row['waktu_pengumpulan'] ?? null,
                'deadline'          => $row['deadline'] ?? null,
                'status_label'      => $status['label'],
                'status_class'      => $status['class'],
                'detail_url'        => base_url('admin/manajemen-tugas/pengumpulan/detail/kelompok/' . (int) ($row['id_tugas'] ?? 0) . '/' . (int) ($row['id_kelompok'] ?? 0)),
            ];
        }, $rows);
    }

    private function getPengumpulanTimRows(): array
    {
        $rows = $this->timModel->getPengumpulanRowsForAdmin();

        $allPengumpulanIds = [];
        foreach ($rows as $row) {
            $allPengumpulanIds = array_merge($allPengumpulanIds, $this->csvIdsToArray($row['pengumpulan_ids'] ?? ''));
        }
        $statsMap = $this->getItemStatsMap($allPengumpulanIds);

        return array_map(function (array $row) use ($statsMap): array {
            $ids = $this->csvIdsToArray($row['pengumpulan_ids'] ?? '');
            $status = $this->summarizePengumpulanStatus($ids, $row['waktu_pengumpulan'] ?? null, $statsMap);

            return [
                'id_tugas'          => (int) ($row['id_tugas'] ?? 0),
                'id_target'         => (int) ($row['id_tim'] ?? 0),
                'nama_target'       => $row['nama_target'] ?? ('Tim #' . (int) ($row['id_tim'] ?? 0)),
                'nama_tugas'        => $row['nama_tugas'] ?? '-',
                'waktu_pengumpulan' => $row['waktu_pengumpulan'] ?? null,
                'deadline'          => $row['deadline'] ?? null,
                'status_label'      => $status['label'],
                'status_class'      => $status['class'],
                'detail_url'        => base_url('admin/manajemen-tugas/pengumpulan/detail/tim/' . (int) ($row['id_tugas'] ?? 0) . '/' . (int) ($row['id_tim'] ?? 0)),
            ];
        }, $rows);
    }

    private function getPengumpulanDetailData(string $type, int $primaryId, int $secondaryId = 0): ?array
    {
        return match ($type) {
            'kelompok' => $this->getKelompokPengumpulanDetail($primaryId, $secondaryId),
            'tim'      => $this->getTimPengumpulanDetail($primaryId, $secondaryId),
            default    => $this->getMandiriPengumpulanDetail($primaryId),
        };
    }

    private function getMandiriPengumpulanDetail(int $idPengumpulan): ?array
    {
        $row = $this->pengumpulanModel->getMandiriDetailRow($idPengumpulan);

        if (! $row) {
            return null;
        }

        $status = $this->summarizePengumpulanStatus(
            [$idPengumpulan],
            $row['tgl_pengumpulan'] ?? null,
            $this->getItemStatsMap([$idPengumpulan])
        );

        return [
            'jenis'            => 'mandiri',
            'title'            => 'Informasi Pengumpulan — Mandiri',
            'badge_label'      => $status['label'],
            'badge_class'      => $status['class'],
            'target_label'     => 'Nama Lengkap',
            'target_value'     => $row['nama_lengkap'] ?? ('PKL #' . (int) ($row['id_pkl'] ?? 0)),
            'kategori_tugas'   => $row['nama_kat_tugas'] ?? '-',
            'nama_tugas'       => $row['nama_tugas'] ?? '-',
            'deadline'         => $row['deadline'] ?? null,
            'tanggal_dikirim'  => $row['tgl_pengumpulan'] ?? null,
            'deskripsi'        => $row['deskripsi'] ?? '-',
            'anggota_title'    => 'Penerima Tugas',
            'anggota'          => [[
                'nama' => $row['nama_lengkap'] ?? ('PKL #' . (int) ($row['id_pkl'] ?? 0)),
            ]],
            'items'            => $this->getPengumpulanItemsByIds([$idPengumpulan]),
            'back_url'         => base_url('admin/manajemen-tugas/pengumpulan?tab=mandiri'),
        ];
    }

    private function getKelompokPengumpulanDetail(int $idTugas, int $idKelompok): ?array
    {
        if ($idTugas < 1 || $idKelompok < 1) {
            return null;
        }

        $row = $this->kelompokModel->getPengumpulanDetailRow($idTugas, $idKelompok);

        if (! $row) {
            return null;
        }

        $pengumpulanIds = $this->csvIdsToArray($row['pengumpulan_ids'] ?? '');
        $status = $this->summarizePengumpulanStatus(
            $pengumpulanIds,
            $row['waktu_pengumpulan'] ?? null,
            $this->getItemStatsMap($pengumpulanIds)
        );

        return [
            'jenis'            => 'kelompok',
            'title'            => 'Informasi Pengumpulan — Kelompok',
            'badge_label'      => $status['label'],
            'badge_class'      => $status['class'],
            'target_label'     => 'Nama Kelompok PKL',
            'target_value'     => $row['nama_target'] ?? ('Kelompok #' . $idKelompok),
            'kategori_tugas'   => $row['nama_kat_tugas'] ?? '-',
            'nama_tugas'       => $row['nama_tugas'] ?? '-',
            'deadline'         => $row['deadline'] ?? null,
            'tanggal_dikirim'  => $row['waktu_pengumpulan'] ?? null,
            'deskripsi'        => $row['deskripsi'] ?? '-',
            'anggota_title'    => 'Anggota Kelompok',
            'anggota'          => $this->kelompokModel->getActiveMemberNames($idKelompok),
            'items'            => $this->getPengumpulanItemsByIds($pengumpulanIds),
            'back_url'         => base_url('admin/manajemen-tugas/pengumpulan?tab=kelompok'),
        ];
    }

    private function getTimPengumpulanDetail(int $idTugas, int $idTim): ?array
    {
        if ($idTugas < 1 || $idTim < 1) {
            return null;
        }

        $row = $this->timModel->getPengumpulanDetailRow($idTugas, $idTim);

        if (! $row) {
            return null;
        }

        $pengumpulanIds = $this->csvIdsToArray($row['pengumpulan_ids'] ?? '');
        $status = $this->summarizePengumpulanStatus(
            $pengumpulanIds,
            $row['waktu_pengumpulan'] ?? null,
            $this->getItemStatsMap($pengumpulanIds)
        );

        return [
            'jenis'            => 'tim',
            'title'            => 'Informasi Pengumpulan — Tim',
            'badge_label'      => $status['label'],
            'badge_class'      => $status['class'],
            'target_label'     => 'Nama Tim',
            'target_value'     => $row['nama_target'] ?? ('Tim #' . $idTim),
            'kategori_tugas'   => $row['nama_kat_tugas'] ?? '-',
            'nama_tugas'       => $row['nama_tugas'] ?? '-',
            'deadline'         => $row['deadline'] ?? null,
            'tanggal_dikirim'  => $row['waktu_pengumpulan'] ?? null,
            'deskripsi'        => $row['deskripsi'] ?? '-',
            'anggota_title'    => 'Anggota Tim',
            'anggota'          => $this->anggotaTimModel->getActiveMemberNamesByTim($idTim),
            'items'            => $this->getPengumpulanItemsByIds($pengumpulanIds),
            'back_url'         => base_url('admin/manajemen-tugas/pengumpulan?tab=tim'),
        ];
    }

    private function getPengumpulanItemsByIds(array $pengumpulanIds): array
    {
        $rows = $this->itemTugasModel->getAdminItemsByPengumpulanIds($pengumpulanIds);

        return array_map(function (array $row): array {
            $type = (string) ($row['tipe_item'] ?? 'link');
            $dataItem = (string) ($row['data_item'] ?? '');
            $extension = $type === 'file' ? strtolower(pathinfo($dataItem, PATHINFO_EXTENSION)) : '';
            $isPdf = $type === 'file' && $extension === 'pdf';

            return [
                'id_item'       => (int) ($row['id_item'] ?? 0),
                'tipe_item'     => $type,
                'tipe_label'    => $type === 'file' ? 'File' : 'Link',
                'data_item'     => $dataItem,
                'display_value' => $type === 'file' ? basename($dataItem) : $dataItem,
                'is_pdf'        => $isPdf,
                'status_label'  => $this->mapItemStatusLabel((string) ($row['status_item'] ?? 'belum_dikirim')),
                'status_class'  => $this->mapItemStatusClass((string) ($row['status_item'] ?? 'belum_dikirim')),
                'status_raw'    => (string) ($row['status_item'] ?? 'belum_dikirim'),
                'nama_pengirim' => $row['nama_pengirim'] ?? 'PKL',
                'komentar'      => $row['komentar'] ?? '',
                'action_url'    => $type === 'link'
                    ? base_url('admin/manajemen-tugas/pengumpulan/item/' . (int) ($row['id_item'] ?? 0) . '/view')
                    : ($isPdf
                        ? base_url('admin/manajemen-tugas/pengumpulan/item/' . (int) ($row['id_item'] ?? 0) . '/view')
                        : base_url('admin/manajemen-tugas/pengumpulan/item/' . (int) ($row['id_item'] ?? 0) . '/download')),
                'action_label'  => ($type === 'link' || $isPdf) ? 'Lihat' : 'Unduh',
                'action_target' => ($type === 'link' || $isPdf) ? '_blank' : null,
            ];
        }, $rows);
    }

    private function getItemStatsMap(array $pengumpulanIds): array
    {
        return $this->itemTugasModel->getStatsMapByPengumpulanIds($pengumpulanIds);
    }

    private function summarizePengumpulanStatus(array $pengumpulanIds, ?string $waktuPengumpulan, array $itemStatsMap): array
    {
        $pengumpulanIds = array_values(array_unique(array_filter(array_map('intval', $pengumpulanIds), static fn($id) => $id > 0)));
        if ($waktuPengumpulan === null || $waktuPengumpulan === '') {
            return ['label' => 'Belum Dikirim', 'class' => 'badge-status-menunggu'];
        }

        $summary = ['total_item' => 0, 'total_dikirim' => 0, 'total_revisi' => 0, 'total_diterima' => 0];
        foreach ($pengumpulanIds as $id) {
            $stats = $itemStatsMap[$id] ?? null;
            if (! $stats) {
                continue;
            }
            $summary['total_item'] += (int) ($stats['total_item'] ?? 0);
            $summary['total_dikirim'] += (int) ($stats['total_dikirim'] ?? 0);
            $summary['total_revisi'] += (int) ($stats['total_revisi'] ?? 0);
            $summary['total_diterima'] += (int) ($stats['total_diterima'] ?? 0);
        }

        if ($summary['total_revisi'] > 0) {
            return ['label' => 'Perlu Revisi', 'class' => 'badge-status-revisi'];
        }
        if ($summary['total_dikirim'] > 0) {
            return ['label' => 'Menunggu Review', 'class' => 'badge-status-review'];
        }
        if ($summary['total_item'] > 0 && $summary['total_item'] === $summary['total_diterima']) {
            return ['label' => 'Selesai', 'class' => 'badge-status-selesai'];
        }

        return ['label' => 'Sudah Dikirim', 'class' => 'badge-status-aktif'];
    }

    private function csvIdsToArray(string $csv): array
    {
        if ($csv === '') {
            return [];
        }

        return array_values(array_unique(array_filter(
            array_map('intval', explode(',', $csv)),
            static fn($id) => $id > 0
        )));
    }

    private function mapItemStatusLabel(string $status): string
    {
        return match ($status) {
            'diterima'      => 'Diterima',
            'revisi'        => 'Perlu Revisi',
            'dikirim'       => 'Menunggu Review',
            default         => 'Belum Dikirim',
        };
    }

    private function mapItemStatusClass(string $status): string
    {
        return match ($status) {
            'diterima'      => 'badge-status-selesai',
            'revisi'        => 'badge-status-revisi',
            'dikirim'       => 'badge-status-review',
            default         => 'badge-status-menunggu',
        };
    }

    private function getAdminTugasDetail(int $idTugas): ?array
    {
        return $this->tugasModel->getAdminDetailById($idTugas);
    }

    private function getTugasSasaranDisplay(int $idTugas): array
    {
        $rows = $this->sasaranModel->where('id_tugas', $idTugas)->findAll();
        if (empty($rows)) {
            return [];
        }

        $items = [];

        foreach ($rows as $row) {
            $targetType = (string) ($row['target_tipe'] ?? '');

            if ($targetType === 'individu' && ! empty($row['id_pkl'])) {
                $detail = $this->pklModel->getTugasTargetDetail((int) $row['id_pkl']);

                $items[] = [
                    'target_tipe' => 'individu',
                    'label'       => $detail['nama_lengkap'] ?? ('PKL #' . (int) $row['id_pkl']),
                    'meta'        => trim(($detail['nama_instansi'] ?? 'Mandiri') . ' • ' . ($detail['nama_kelompok'] ?? 'Mandiri')),
                ];
                continue;
            }

            if ($targetType === 'kelompok' && ! empty($row['id_kelompok'])) {
                $detail = $this->kelompokModel->getTugasTargetDetail((int) $row['id_kelompok']);

                $items[] = [
                    'target_tipe' => 'kelompok',
                    'label'       => $detail['nama_kelompok'] ?? ('Kelompok #' . (int) $row['id_kelompok']),
                    'meta'        => trim(($detail['nama_instansi'] ?? 'Instansi Tidak Diketahui') . ' • ' . ((int) ($detail['jumlah_anggota'] ?? 0)) . ' anggota'),
                ];
                continue;
            }

            if ($targetType === 'tim_tugas' && ! empty($row['id_tim'])) {
                $detail = $this->timModel->getTugasTargetDetail((int) $row['id_tim']);

                $meta = ((int) ($detail['jumlah_anggota'] ?? 0)) . ' anggota';
                if (! empty($detail['deskripsi'])) {
                    $meta .= ' • ' . trim((string) $detail['deskripsi']);
                }

                $items[] = [
                    'target_tipe' => 'tim_tugas',
                    'label'       => $detail['nama_tim'] ?? ('Tim #' . (int) $row['id_tim']),
                    'meta'        => $meta,
                ];
            }
        }

        return $items;
    }

    private function getTugasPengumpulanDisplay(int $idTugas): array
    {
        $rows = $this->pengumpulanModel->getByTugas($idTugas);
        if (empty($rows)) {
            return [];
        }

        $mapped = array_map(static function (array $row): array {
            $tglPengumpulan = $row['tgl_pengumpulan'] ?? null;

            return [
                'nama_pkl'         => $row['nama_pkl'] ?? ('PKL #' . (int) ($row['id_pkl'] ?? 0)),
                'nama_kelompok'    => $row['nama_kelompok'] ?: 'Mandiri',
                'tgl_pengumpulan'  => $tglPengumpulan,
                'status_label'     => $tglPengumpulan ? 'Sudah Mengumpulkan' : 'Belum Mengumpulkan',
                'status_class'     => $tglPengumpulan ? 'badge-status-selesai' : 'badge-status-menunggu',
            ];
        }, $rows);

        usort($mapped, static function (array $a, array $b): int {
            $submittedA = empty($a['tgl_pengumpulan']) ? 0 : 1;
            $submittedB = empty($b['tgl_pengumpulan']) ? 0 : 1;

            if ($submittedA !== $submittedB) {
                return $submittedA <=> $submittedB;
            }

            return strcasecmp($a['nama_pkl'], $b['nama_pkl']);
        });

        return $mapped;
    }

    private function respondTugasFormError(string $message, int $status = 400)
    {
        if ($this->request->isAJAX()) {
            return $this->jsonError($message, $status);
        }

        session()->setFlashdata('error', $message);
        return redirect()->back()->withInput();
    }

    private function respondTugasFormSuccess(string $message, string $redirectUrl)
    {
        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'success'      => true,
                'message'      => $message,
                'redirect_url' => $redirectUrl,
            ]);
        }

        session()->setFlashdata('success', $message);
        return redirect()->to($redirectUrl);
    }

    private function getStoredTaskFilePath(string $storedName): string
    {
        return WRITEPATH . self::UPLOAD_SUBDIR . DIRECTORY_SEPARATOR . $storedName;
    }
}
