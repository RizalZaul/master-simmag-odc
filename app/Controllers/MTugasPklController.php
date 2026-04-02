<?php

namespace App\Controllers;

use App\Models\ItemTugasModel;
use App\Models\PengumpulanTugasModel;
use App\Models\PklModel;
use App\Models\TugasModel;
use CodeIgniter\HTTP\Files\UploadedFile;

class MTugasPklController extends BaseController
{
    private const UPLOAD_SUBDIR = 'uploads/tugas';
    private const MAX_FILE_SIZE_KB = 307200;
    private const ALLOWED_EXTENSIONS = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'zip', 'rar'];

    protected TugasModel $tugasModel;
    protected PengumpulanTugasModel $pengumpulanModel;
    protected ItemTugasModel $itemTugasModel;
    protected PklModel $pklModel;

    public function __construct()
    {
        $this->tugasModel = new TugasModel();
        $this->pengumpulanModel = new PengumpulanTugasModel();
        $this->itemTugasModel = new ItemTugasModel();
        $this->pklModel = new PklModel();
    }

    public function index()
    {
        $currentPkl = $this->getCurrentPklContext();
        if ($currentPkl === null) {
            return redirect()->to(base_url('auth/login'));
        }

        $activeTab = strtolower((string) ($this->request->getGet('tab') ?? 'individu'));
        if (! in_array($activeTab, ['individu', 'kelompok'], true)) {
            $activeTab = 'individu';
        }

        $allTasks = $this->getAssignedTasks((int) $currentPkl['id_pkl']);
        $individuTasks = array_values(array_filter(
            $allTasks,
            static fn(array $task): bool => ($task['mode_pengumpulan'] ?? '') === 'individu'
        ));
        $kelompokTasks = array_values(array_filter(
            $allTasks,
            static fn(array $task): bool => ($task['mode_pengumpulan'] ?? '') === 'kelompok'
        ));

        $tabLabels = [
            'individu' => 'Tugas Individu',
            'kelompok' => 'Tugas Kelompok',
        ];

        $data = [
            'page_title' => 'Manajemen Tugas',
            'page_title_sub' => $tabLabels[$activeTab],
            'active_menu' => 'manajemen_tugas',
            'active_tab' => $activeTab,
            'welcome_heading' => 'Manajemen Tugas',
            'welcome_subheading' => 'Kelola tugas individu dan kelompok Anda',
            'individuTasks' => $individuTasks,
            'kelompokTasks' => $kelompokTasks,
            'extra_css' => '<link rel="stylesheet" href="' . base_url('assets/css/modules/pkl/tugas.css') . '?v=20260402-3">',
            'extra_js' => '<script>window.PKL_TUGAS = { activeTab: "' . $activeTab . '" };</script>'
                . '<script src="' . base_url('assets/js/modules/pkl/tugas.js') . '?v=20260402-2"></script>',
        ];

        $data['content'] = view('dashboard_pkl/tugas/index', $data);
        return view('Layouts/dashboard_layout', $data);
    }

    public function detail(int $idTugas)
    {
        $currentPkl = $this->getCurrentPklContext();
        if ($currentPkl === null) {
            return redirect()->to(base_url('auth/login'));
        }

        $detail = $this->getTaskDetail((int) $currentPkl['id_pkl'], $idTugas);
        if ($detail === null) {
            return redirect()->to(base_url('pkl/tugas'))->with('error', 'Tugas tidak ditemukan.');
        }

        $data = [
            'page_title' => 'Manajemen Tugas',
            'page_title_sub' => 'Detail Tugas',
            'active_menu' => 'manajemen_tugas',
            'detail' => $detail,
            'uploadErrors' => session()->getFlashdata('task_upload_errors') ?? [],
            'autoOpenUpload' => (bool) session()->getFlashdata('task_upload_modal'),
            'extra_css' => '<link rel="stylesheet" href="' . base_url('assets/css/modules/pkl/tugas.css') . '?v=20260402-3">',
            'extra_js' => '<script>window.PKL_TUGAS_DETAIL = { autoOpenUpload: ' . ((bool) session()->getFlashdata('task_upload_modal') ? 'true' : 'false') . ' };</script>'
                . '<script src="' . base_url('assets/js/modules/pkl/tugas.js') . '?v=20260402-2"></script>',
        ];

        $data['content'] = view('dashboard_pkl/tugas/detail', $data);
        return view('Layouts/dashboard_layout', $data);
    }

    public function kumpulkan(int $idTugas)
    {
        $currentPkl = $this->getCurrentPklContext();
        if ($currentPkl === null) {
            return redirect()->to(base_url('auth/login'));
        }

        $detail = $this->getTaskDetail((int) $currentPkl['id_pkl'], $idTugas);
        if ($detail === null) {
            return redirect()->to(base_url('pkl/tugas'))->with('error', 'Tugas tidak ditemukan.');
        }

        if (! ($detail['can_submit'] ?? false)) {
            return redirect()->to(base_url('pkl/tugas/detail/' . $idTugas))
                ->with('error', 'Tugas ini belum bisa diperbarui saat ini.');
        }

        $targetJumlah = (int) ($detail['target_jumlah'] ?? 0);
        if ($targetJumlah < 1) {
            return redirect()->to(base_url('pkl/tugas/detail/' . $idTugas))
                ->with('error', 'Jumlah jawaban tugas tidak valid.');
        }

        $postedJawaban = $this->request->getPost('jawaban');
        $postedJawaban = is_array($postedJawaban) ? $postedJawaban : [];
        $existingSlots = $detail['submission_slots'] ?? [];
        $errors = [];
        $preparedSlots = [];

        for ($index = 0; $index < $targetJumlah; $index++) {
            $slot = $existingSlots[$index] ?? ['existing' => null, 'is_locked' => false];
            $existing = $slot['existing'] ?? null;
            $isLocked = (bool) ($slot['is_locked'] ?? false);

            if ($isLocked) {
                $preparedSlots[] = [
                    'mode' => 'keep',
                    'existing' => $existing,
                ];
                continue;
            }

            $input = isset($postedJawaban[$index]) && is_array($postedJawaban[$index]) ? $postedJawaban[$index] : [];
            $type = $this->normalizeItemType((string) ($input['tipe'] ?? ''));

            if ($type === '') {
                $errors[] = 'Tipe jawaban ' . ($index + 1) . ' wajib dipilih.';
                continue;
            }

            if ($type === 'link') {
                $url = trim((string) ($input['url'] ?? ''));
                if ($url === '') {
                    $errors[] = 'Link pada jawaban ' . ($index + 1) . ' wajib diisi.';
                    continue;
                }
                if (! $this->isHttpsUrl($url)) {
                    $errors[] = 'Link pada jawaban ' . ($index + 1) . ' harus valid dan diawali https://.';
                    continue;
                }
                if (mb_strlen($url) > 2048) {
                    $errors[] = 'Link pada jawaban ' . ($index + 1) . ' terlalu panjang.';
                    continue;
                }

                $preparedSlots[] = [
                    'mode' => $existing ? 'update' : 'insert',
                    'existing' => $existing,
                    'type' => 'link',
                    'value' => $url,
                ];
                continue;
            }

            $file = $this->request->getFile('jawaban_file_' . $index);
            if (! $this->hasUploadedFile($file)) {
                $errors[] = 'File pada jawaban ' . ($index + 1) . ' wajib dipilih.';
                continue;
            }
            if (! $file->isValid()) {
                $errors[] = 'File pada jawaban ' . ($index + 1) . ' tidak valid: ' . $file->getErrorString();
                continue;
            }

            $extension = strtolower($file->getClientExtension() ?: pathinfo($file->getClientName(), PATHINFO_EXTENSION));
            if (! in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
                $errors[] = 'Format file jawaban ' . ($index + 1) . ' harus PDF, DOC/DOCX, PPT/PPTX, XLS/XLSX, ZIP, atau RAR.';
                continue;
            }

            $sizeKb = (int) ceil($file->getSize() / 1024);
            if ($sizeKb > self::MAX_FILE_SIZE_KB) {
                $errors[] = 'Ukuran file jawaban ' . ($index + 1) . ' maksimal 300 MB.';
                continue;
            }

            $preparedSlots[] = [
                'mode' => $existing ? 'update' : 'insert',
                'existing' => $existing,
                'type' => 'file',
                'file' => $file,
            ];
        }

        if (! empty($errors)) {
            return redirect()->back()
                ->withInput()
                ->with('task_upload_errors', $errors)
                ->with('task_upload_modal', true);
        }

        $db = \Config\Database::connect();
        $storedFiles = [];
        $replacedFiles = [];

        $db->transStart();
        try {
            foreach ($preparedSlots as $slot) {
                if (($slot['mode'] ?? '') === 'keep') {
                    continue;
                }

                $payload = [
                    'tipe_item' => $slot['type'],
                    'data_item' => '',
                    'komentar' => null,
                    'status_item' => 'dikirim',
                ];

                if ($slot['type'] === 'link') {
                    $payload['data_item'] = $slot['value'];
                } else {
                    $storedName = $this->storeUploadedFile($slot['file']);
                    $storedFiles[] = $storedName;
                    $payload['data_item'] = $storedName;
                }

                $existing = $slot['existing'] ?? null;
                if ($existing) {
                    if (($existing['tipe'] ?? '') === 'file' && ! empty($existing['data'])) {
                        $replacedFiles[] = (string) $existing['data'];
                    }

                    if ($this->itemTugasModel->update((int) $existing['id'], $payload) === false) {
                        $errors = implode(' ', $this->itemTugasModel->errors());
                        throw new \RuntimeException($errors !== '' ? $errors : 'Gagal memperbarui jawaban tugas.');
                    }
                } else {
                    $payload['id_pengumpulan_tgs'] = (int) $detail['id_pengumpulan_tgs'];
                    if ($this->itemTugasModel->insert($payload, false) === false) {
                        $errors = implode(' ', $this->itemTugasModel->errors());
                        throw new \RuntimeException($errors !== '' ? $errors : 'Gagal menyimpan jawaban tugas.');
                    }
                }
            }

            if ($this->pengumpulanModel->update((int) $detail['id_pengumpulan_tgs'], [
                'tgl_pengumpulan' => date('Y-m-d H:i:s'),
            ]) === false) {
                $errors = implode(' ', $this->pengumpulanModel->errors());
                throw new \RuntimeException($errors !== '' ? $errors : 'Gagal memperbarui waktu pengumpulan.');
            }

            $db->transComplete();
            if (! $db->transStatus()) {
                throw new \RuntimeException('Terjadi kesalahan saat menyimpan pengumpulan tugas.');
            }
        } catch (\Throwable $e) {
            $db->transRollback();
            foreach ($storedFiles as $storedFile) {
                $this->deleteStoredFile($storedFile);
            }

            return redirect()->back()
                ->withInput()
                ->with('task_upload_errors', [$e->getMessage()])
                ->with('task_upload_modal', true);
        }

        foreach ($replacedFiles as $replacedFile) {
            $this->deleteStoredFile($replacedFile);
        }

        return redirect()->to(base_url('pkl/tugas/detail/' . $idTugas))
            ->with('success', 'Jawaban tugas berhasil dikirim.');
    }

    public function downloadItem(int $idItem)
    {
        $currentPkl = $this->getCurrentPklContext();
        if ($currentPkl === null) {
            return redirect()->to(base_url('auth/login'));
        }

        $item = \Config\Database::connect()->table('item_tugas it')
            ->select('it.id_item, it.tipe_item, it.data_item')
            ->join('pengumpulan_tugas pt', 'pt.id_pengumpulan_tgs = it.id_pengumpulan_tgs')
            ->where('it.id_item', $idItem)
            ->where('pt.id_pkl', (int) $currentPkl['id_pkl'])
            ->get()
            ->getRowArray();

        if (! $item) {
            return redirect()->back()->with('error', 'File jawaban tidak ditemukan.');
        }

        if (($item['tipe_item'] ?? '') === 'link') {
            return redirect()->to((string) ($item['data_item'] ?? ''));
        }

        $filePath = $this->getStoredFilePath((string) ($item['data_item'] ?? ''));
        if (! is_file($filePath)) {
            return redirect()->back()->with('error', 'File jawaban tidak ditemukan di server.');
        }

        return $this->response
            ->download($filePath, null, true)
            ->setFileName(basename((string) $item['data_item']));
    }

    private function getCurrentPklContext(): ?array
    {
        $idPkl = (int) (session()->get('id_pkl') ?? 0);
        if ($idPkl > 0) {
            return [
                'id_pkl' => $idPkl,
                'id_kelompok' => (int) (session()->get('id_kelompok') ?? 0),
            ];
        }

        $userId = (int) (session()->get('user_id') ?? session()->get('id_user') ?? 0);
        if ($userId < 1) {
            return null;
        }

        $row = $this->pklModel->findByIdUser($userId);
        if (! $row) {
            return null;
        }

        return [
            'id_pkl' => (int) ($row['id_pkl'] ?? 0),
            'id_kelompok' => (int) ($row['id_kelompok'] ?? 0),
        ];
    }

    private function getAssignedTasks(int $idPkl): array
    {
        $rows = \Config\Database::connect()->table('pengumpulan_tugas pt')
            ->select('pt.id_pengumpulan_tgs, pt.id_tugas, pt.id_kelompok, pt.id_tim, pt.tgl_pengumpulan, pt.updated_at AS pengumpulan_updated_at')
            ->select('t.nama_tugas, t.deadline, t.target_jumlah, t.created_at AS tugas_created_at')
            ->select('kt.nama_kat_tugas, kt.mode_pengumpulan')
            ->select("COALESCE(NULLIF(k.nama_kelompok, ''), CONCAT('Kelompok #', k.id_kelompok)) AS nama_kelompok", false)
            ->select('tt.nama_tim')
            ->join('tugas t', 't.id_tugas = pt.id_tugas')
            ->join('kategori_tugas kt', 'kt.id_kat_tugas = t.id_kat_tugas', 'left')
            ->join('kelompok_pkl k', 'k.id_kelompok = pt.id_kelompok', 'left')
            ->join('tim_tugas tt', 'tt.id_tim = pt.id_tim', 'left')
            ->where('pt.id_pkl', $idPkl)
            ->orderBy('t.deadline', 'ASC')
            ->orderBy('t.nama_tugas', 'ASC')
            ->get()
            ->getResultArray();

        if (empty($rows)) {
            return [];
        }

        $statsMap = $this->getItemStatsMap(array_map('intval', array_column($rows, 'id_pengumpulan_tgs')));
        $tasks = [];

        foreach ($rows as $row) {
            $summary = $this->summarizeTaskStatus(
                $row['tgl_pengumpulan'] ?? null,
                $statsMap[(int) ($row['id_pengumpulan_tgs'] ?? 0)] ?? null,
                (int) ($row['target_jumlah'] ?? 0)
            );

            $source = $this->resolveTaskSource($row);
            $mode = (string) ($row['mode_pengumpulan'] ?? 'individu');
            $tab = $mode === 'kelompok' ? 'kelompok' : 'individu';

            $tasks[] = [
                'id_tugas' => (int) ($row['id_tugas'] ?? 0),
                'id_pengumpulan_tgs' => (int) ($row['id_pengumpulan_tgs'] ?? 0),
                'mode_pengumpulan' => $mode,
                'tab_key' => $tab,
                'nama_tugas' => (string) ($row['nama_tugas'] ?? '-'),
                'nama_kategori' => (string) ($row['nama_kat_tugas'] ?? '-'),
                'deadline' => $row['deadline'] ?? null,
                'deadline_display' => $this->formatTaskDate($row['deadline'] ?? null),
                'status_label' => $summary['label'],
                'status_short' => $summary['short'],
                'status_class' => $summary['class'],
                'detail_url' => base_url('pkl/tugas/detail/' . (int) ($row['id_tugas'] ?? 0) . '?tab=' . $tab),
                'source_badge' => $source['badge'],
                'source_name' => $source['name'],
                'search_blob' => strtolower(trim(
                    ($row['nama_tugas'] ?? '') . ' '
                    . ($row['nama_kat_tugas'] ?? '') . ' '
                    . ($source['badge'] ?? '') . ' '
                    . ($source['name'] ?? '')
                )),
            ];
        }

        return $tasks;
    }

    private function getTaskDetail(int $idPkl, int $idTugas): ?array
    {
        $row = \Config\Database::connect()->table('pengumpulan_tugas pt')
            ->select('pt.id_pengumpulan_tgs, pt.id_tugas, pt.id_kelompok, pt.id_tim, pt.tgl_pengumpulan, pt.created_at AS pengumpulan_created_at, pt.updated_at AS pengumpulan_updated_at')
            ->select('t.nama_tugas, t.deskripsi, t.target_jumlah, t.deadline, t.created_at AS tugas_created_at, t.updated_at AS tugas_updated_at')
            ->select('kt.nama_kat_tugas, kt.mode_pengumpulan')
            ->select("COALESCE(NULLIF(k.nama_kelompok, ''), CONCAT('Kelompok #', k.id_kelompok)) AS nama_kelompok", false)
            ->select('tt.nama_tim')
            ->join('tugas t', 't.id_tugas = pt.id_tugas')
            ->join('kategori_tugas kt', 'kt.id_kat_tugas = t.id_kat_tugas', 'left')
            ->join('kelompok_pkl k', 'k.id_kelompok = pt.id_kelompok', 'left')
            ->join('tim_tugas tt', 'tt.id_tim = pt.id_tim', 'left')
            ->where('pt.id_pkl', $idPkl)
            ->where('pt.id_tugas', $idTugas)
            ->get()
            ->getRowArray();

        if (! $row) {
            return null;
        }

        $items = $this->getTaskItems((int) ($row['id_pengumpulan_tgs'] ?? 0), $idPkl);
        $summary = $this->summarizeTaskStatus(
            $row['tgl_pengumpulan'] ?? null,
            $this->getItemStatsMap([(int) ($row['id_pengumpulan_tgs'] ?? 0)])[(int) ($row['id_pengumpulan_tgs'] ?? 0)] ?? null,
            (int) ($row['target_jumlah'] ?? 0)
        );
        $source = $this->resolveTaskSource($row);
        $latestItemUpdated = $this->findLatestItemUpdatedAt($items);
        $lastUpdated = $this->maxDate($row['pengumpulan_updated_at'] ?? null, $latestItemUpdated);
        $answerCount = count($items);
        $tabKey = ($row['mode_pengumpulan'] ?? '') === 'kelompok' ? 'kelompok' : 'individu';
        $canSubmit = (bool) ($summary['can_submit'] ?? false);

        return [
            'id_tugas' => (int) ($row['id_tugas'] ?? 0),
            'id_pengumpulan_tgs' => (int) ($row['id_pengumpulan_tgs'] ?? 0),
            'nama_tugas' => (string) ($row['nama_tugas'] ?? '-'),
            'deskripsi' => (string) ($row['deskripsi'] ?? '-'),
            'nama_kategori' => (string) ($row['nama_kat_tugas'] ?? '-'),
            'mode_pengumpulan' => (string) ($row['mode_pengumpulan'] ?? 'individu'),
            'target_jumlah' => (int) ($row['target_jumlah'] ?? 0),
            'target_label' => (int) ($row['target_jumlah'] ?? 0) . ' item',
            'deadline' => $row['deadline'] ?? null,
            'deadline_display' => $this->formatTaskDate($row['deadline'] ?? null),
            'created_display' => $this->formatTaskDate($row['tugas_created_at'] ?? null),
            'status_label' => $summary['label'],
            'status_short' => $summary['short'],
            'status_class' => $summary['class'],
            'source_badge' => $source['badge'],
            'source_name' => $source['name'],
            'answer_count' => $answerCount,
            'progress_label' => $answerCount . ' / ' . (int) ($row['target_jumlah'] ?? 0) . ' item',
            'submitted_at' => $row['tgl_pengumpulan'] ?? null,
            'submitted_display' => $this->formatTaskDate($row['tgl_pengumpulan'] ?? null, false, 'Belum dikumpulkan'),
            'remaining_time' => $this->describeRemainingTime($row['deadline'] ?? null),
            'last_updated_display' => $this->formatTaskDate($lastUpdated, false, '-'),
            'can_submit' => $canSubmit,
            'submit_label' => $answerCount > 0 ? 'Perbarui Jawaban' : 'Kumpulkan Tugas',
            'back_url' => base_url('pkl/tugas?tab=' . $tabKey),
            'answers' => $items,
            'submission_slots' => $this->buildSubmissionSlots((int) ($row['target_jumlah'] ?? 0), $items),
            'allowed_file_text' => 'Format file: pdf, docx, doc, pptx, ppt, xlsx, xls, zip, rar - maks 300 MB',
        ];
    }

    private function getTaskItems(int $idPengumpulan, int $idPkl): array
    {
        if ($idPengumpulan < 1) {
            return [];
        }

        $rows = \Config\Database::connect()->table('item_tugas it')
            ->select('it.id_item, it.tipe_item, it.data_item, it.status_item, it.komentar, it.created_at, it.updated_at')
            ->join('pengumpulan_tugas pt', 'pt.id_pengumpulan_tgs = it.id_pengumpulan_tgs')
            ->where('it.id_pengumpulan_tgs', $idPengumpulan)
            ->where('pt.id_pkl', $idPkl)
            ->orderBy('it.id_item', 'ASC')
            ->get()
            ->getResultArray();

        return array_map(function (array $row): array {
            $type = (string) ($row['tipe_item'] ?? 'link');
            $data = (string) ($row['data_item'] ?? '');

            return [
                'id' => (int) ($row['id_item'] ?? 0),
                'tipe' => $type,
                'tipe_label' => $type === 'file' ? 'File' : 'Link URL',
                'data' => $data,
                'display_value' => $type === 'file' ? basename($data) : $data,
                'status_raw' => (string) ($row['status_item'] ?? 'belum_dikirim'),
                'status_label' => $this->mapItemStatusLabel((string) ($row['status_item'] ?? 'belum_dikirim')),
                'status_class' => $this->mapItemStatusClass((string) ($row['status_item'] ?? 'belum_dikirim')),
                'komentar' => (string) ($row['komentar'] ?? ''),
                'created_at' => $row['created_at'] ?? null,
                'updated_at' => $row['updated_at'] ?? null,
                'action_url' => $type === 'file'
                    ? base_url('pkl/tugas/item/' . (int) ($row['id_item'] ?? 0) . '/download')
                    : $data,
            ];
        }, $rows);
    }

    private function buildSubmissionSlots(int $targetJumlah, array $items): array
    {
        $slots = [];

        for ($index = 0; $index < $targetJumlah; $index++) {
            $existing = $items[$index] ?? null;
            $statusRaw = (string) ($existing['status_raw'] ?? '');

            $slots[] = [
                'index' => $index,
                'number' => $index + 1,
                'existing' => $existing,
                'is_locked' => $statusRaw === 'diterima',
            ];
        }

        return $slots;
    }

    private function getItemStatsMap(array $pengumpulanIds): array
    {
        $pengumpulanIds = array_values(array_unique(array_filter(array_map('intval', $pengumpulanIds), static fn(int $id): bool => $id > 0)));
        if (empty($pengumpulanIds)) {
            return [];
        }

        $rows = \Config\Database::connect()->table('item_tugas')
            ->select('id_pengumpulan_tgs')
            ->select('COUNT(*) AS total_item', false)
            ->select("SUM(CASE WHEN status_item = 'dikirim' THEN 1 ELSE 0 END) AS total_dikirim", false)
            ->select("SUM(CASE WHEN status_item = 'revisi' THEN 1 ELSE 0 END) AS total_revisi", false)
            ->select("SUM(CASE WHEN status_item = 'diterima' THEN 1 ELSE 0 END) AS total_diterima", false)
            ->groupBy('id_pengumpulan_tgs')
            ->whereIn('id_pengumpulan_tgs', $pengumpulanIds)
            ->get()
            ->getResultArray();

        $map = [];
        foreach ($rows as $row) {
            $map[(int) ($row['id_pengumpulan_tgs'] ?? 0)] = [
                'total_item' => (int) ($row['total_item'] ?? 0),
                'total_dikirim' => (int) ($row['total_dikirim'] ?? 0),
                'total_revisi' => (int) ($row['total_revisi'] ?? 0),
                'total_diterima' => (int) ($row['total_diterima'] ?? 0),
            ];
        }

        return $map;
    }

    private function summarizeTaskStatus(?string $tglPengumpulan, ?array $stats, int $targetJumlah): array
    {
        $stats = $stats ?? [
            'total_item' => 0,
            'total_dikirim' => 0,
            'total_revisi' => 0,
            'total_diterima' => 0,
        ];

        if ($tglPengumpulan === null || $tglPengumpulan === '') {
            return [
                'label' => 'Belum Dikirim',
                'short' => 'Belum',
                'class' => 'warning',
                'can_submit' => true,
            ];
        }

        $totalItem = (int) ($stats['total_item'] ?? 0);
        $totalRevisi = (int) ($stats['total_revisi'] ?? 0);
        $totalDikirim = (int) ($stats['total_dikirim'] ?? 0);
        $totalDiterima = (int) ($stats['total_diterima'] ?? 0);

        if ($totalRevisi > 0) {
            return [
                'label' => 'Perlu Revisi',
                'short' => 'Revisi',
                'class' => 'danger',
                'can_submit' => true,
            ];
        }

        if ($totalItem > 0 && $targetJumlah > 0 && $totalItem < $targetJumlah && $totalDikirim === 0) {
            return [
                'label' => 'Belum Lengkap',
                'short' => 'Belum',
                'class' => 'warning',
                'can_submit' => true,
            ];
        }

        if ($totalItem > 0 && $totalItem === $totalDiterima && ($targetJumlah === 0 || $totalItem >= $targetJumlah)) {
            return [
                'label' => 'Selesai',
                'short' => 'Selesai',
                'class' => 'success',
                'can_submit' => false,
            ];
        }

        if ($totalDikirim > 0 || $totalItem > 0) {
            return [
                'label' => 'Menunggu Review',
                'short' => 'Review',
                'class' => 'info',
                'can_submit' => false,
            ];
        }

        return [
            'label' => 'Sudah Dikirim',
            'short' => 'Dikirim',
            'class' => 'info',
            'can_submit' => false,
        ];
    }

    private function resolveTaskSource(array $row): array
    {
        $mode = (string) ($row['mode_pengumpulan'] ?? 'individu');
        if ($mode === 'individu') {
            return [
                'badge' => 'Individu',
                'name' => 'Tugas individu',
            ];
        }

        if (! empty($row['id_tim'])) {
            return [
                'badge' => 'Tim Tugas',
                'name' => (string) ($row['nama_tim'] ?? ('Tim #' . (int) ($row['id_tim'] ?? 0))),
            ];
        }

        return [
            'badge' => 'Kelompok PKL',
            'name' => (string) ($row['nama_kelompok'] ?? ('Kelompok #' . (int) ($row['id_kelompok'] ?? 0))),
        ];
    }

    private function formatTaskDate(?string $value, bool $withTime = false, string $fallback = '-'): string
    {
        if (! $value) {
            return $fallback;
        }

        $timestamp = strtotime($value);
        if ($timestamp === false) {
            return $fallback;
        }

        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        $month = $months[((int) date('n', $timestamp)) - 1] ?? date('M', $timestamp);
        $dateLabel = date('d', $timestamp) . ' ' . $month . ' ' . date('Y', $timestamp);

        return $withTime ? date('H:i', $timestamp) . ' - ' . $dateLabel : $dateLabel;
    }

    private function describeRemainingTime(?string $deadline): string
    {
        if (! $deadline) {
            return '-';
        }

        $deadlineTs = strtotime($deadline);
        if ($deadlineTs === false) {
            return '-';
        }

        $diff = $deadlineTs - time();
        $suffix = $diff >= 0 ? 'lagi' : 'terlambat';
        $diff = abs($diff);

        $days = intdiv($diff, 86400);
        $hours = intdiv($diff % 86400, 3600);
        $minutes = intdiv($diff % 3600, 60);

        if ($days > 0) {
            return $days . ' hari ' . $hours . ' jam ' . $minutes . ' menit ' . $suffix;
        }

        if ($hours > 0) {
            return $hours . ' jam ' . $minutes . ' menit ' . $suffix;
        }

        return max($minutes, 0) . ' menit ' . $suffix;
    }

    private function maxDate(?string $first, ?string $second): ?string
    {
        if (! $first) {
            return $second;
        }
        if (! $second) {
            return $first;
        }

        return strtotime($first) >= strtotime($second) ? $first : $second;
    }

    private function findLatestItemUpdatedAt(array $items): ?string
    {
        $latest = null;
        foreach ($items as $item) {
            $latest = $this->maxDate($latest, $item['updated_at'] ?? null);
        }

        return $latest;
    }

    private function normalizeItemType(string $type): string
    {
        return in_array($type, ['link', 'file'], true) ? $type : '';
    }

    private function mapItemStatusLabel(string $status): string
    {
        return match ($status) {
            'diterima' => 'Diterima',
            'revisi' => 'Perlu Revisi',
            'dikirim' => 'Menunggu Review',
            default => 'Belum Dikirim',
        };
    }

    private function mapItemStatusClass(string $status): string
    {
        return match ($status) {
            'diterima' => 'success',
            'revisi' => 'danger',
            'dikirim' => 'info',
            default => 'warning',
        };
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
        return $file instanceof UploadedFile && $file->getError() !== UPLOAD_ERR_NO_FILE;
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
        $baseName = preg_replace('/[^A-Za-z0-9\-_]+/', '_', $baseName) ?? 'jawaban';
        $baseName = trim($baseName, '_');
        $baseName = $baseName !== '' ? $baseName : 'jawaban';
        $baseName = substr($baseName, 0, 120);
        $storedName = $baseName . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
        $file->move($dir, $storedName);

        return $storedName;
    }

    private function getStoredFilePath(string $storedName): string
    {
        return WRITEPATH . self::UPLOAD_SUBDIR . DIRECTORY_SEPARATOR . $storedName;
    }

    private function deleteStoredFile(string $storedName): void
    {
        if ($storedName === '') {
            return;
        }

        $path = $this->getStoredFilePath($storedName);
        if (is_file($path)) {
            @unlink($path);
        }
    }
}
