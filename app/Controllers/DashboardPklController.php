<?php

namespace App\Controllers;

use App\Models\KategoriModulModel;

/**
 * DashboardPklController
 * Menangani halaman dashboard untuk role PKL.
 *
 * Data yang ditampilkan:
 *   1. Stat cards     : total/selesai/pending tugas milik PKL ini
 *   2. Modul list     : kategori modul dari KategoriModulModel::getForPklDashboard()
 *   3. Tugas terbaru  : 5 tugas terbaru untuk kelompok PKL ini
 *
 * Catatan tugas:
 *   Query mengasumsikan tabel `tugas` dan `pengumpulan` sudah ada.
 *   Jika tabel belum tersedia, bagian tugas akan ditampilkan sebagai empty state.
 */
class DashboardPklController extends BaseController
{
    protected KategoriModulModel $kategoriModulModel;

    public function __construct()
    {
        $this->kategoriModulModel = new KategoriModulModel();
    }

    // ==========================================
    // DASHBOARD
    // ==========================================

    public function index()
    {
        $idKelompok = (int) session()->get('id_kelompok');
        $idPkl      = (int) session()->get('id_pkl');

        // ── 1. Stat cards ───────────────────────────────────────────────
        $statsT = $this->getStatsTugas($idKelompok, $idPkl);

        // ── 2. Modul list ───────────────────────────────────────────────
        $modulList = $this->kategoriModulModel->getForPklDashboard();

        // ── 3. Tugas terbaru ────────────────────────────────────────────
        $tugasTerbaru = $this->getTugasTerbaru($idKelompok, $idPkl);

        $data = [
            'page_title'   => 'Dashboard PKL',
            'active_menu'  => 'dashboard',
            'statsT'       => $statsT,
            'modulList'    => $modulList,
            'tugasTerbaru' => $tugasTerbaru,
        ];

        $data['content'] = view('dashboard_pkl/dashboard', $data);

        return view('Layouts/dashboard_layout', $data);
    }

    // ==========================================
    // PRIVATE HELPERS
    // ==========================================

    /**
     * Hitung statistik tugas untuk PKL ini (berdasarkan kelompok).
     *
     * Return: ['total' => int, 'selesai' => int, 'pending' => int]
     */
    private function getStatsTugas(int $idKelompok, int $idPkl): array
    {
        $default = ['total' => 0, 'selesai' => 0, 'pending' => 0];

        if (! $idKelompok) return $default;

        try {
            $db = \Config\Database::connect();

            // Total tugas yang diberikan ke kelompok ini
            $total = $db->table('tugas')
                ->where('id_kelompok', $idKelompok)
                ->countAllResults();

            // Tugas yang sudah dikumpulkan oleh PKL ini
            $selesai = $db->table('tugas t')
                ->join('pengumpulan pg', 'pg.id_tugas = t.id_tugas AND pg.id_pkl = ' . $idPkl, 'inner')
                ->where('t.id_kelompok', $idKelompok)
                ->countAllResults();

            return [
                'total'   => $total,
                'selesai' => $selesai,
                'pending' => max(0, $total - $selesai),
            ];
        } catch (\Exception $e) {
            log_message('warning', '[DashboardPkl] getStatsTugas error: ' . $e->getMessage());
            return $default;
        }
    }

    /**
     * Ambil 5 tugas terbaru untuk kelompok PKL ini.
     * Ditandai apakah PKL ini sudah mengumpulkan atau belum.
     *
     * Return: array of [
     *   id_tugas, nama_tugas, deadline,
     *   nama_kat_tugas, sudah_kumpul (bool)
     * ]
     */
    private function getTugasTerbaru(int $idKelompok, int $idPkl): array
    {
        if (! $idKelompok) return [];

        try {
            $db = \Config\Database::connect();

            $rows = $db->table('tugas t')
                ->select([
                    't.id_tugas',
                    't.nama_tugas',
                    't.deadline',
                    'kt.nama_kat_tugas',
                    // Jika PKL ini sudah mengumpulkan → 1, else NULL
                    'pg.id_pkl AS sudah_kumpul',
                ])
                ->join('kategori_tugas kt', 'kt.id_kat_tugas = t.id_kat_tugas', 'left')
                ->join(
                    'pengumpulan pg',
                    'pg.id_tugas = t.id_tugas AND pg.id_pkl = ' . $idPkl,
                    'left'
                )
                ->where('t.id_kelompok', $idKelompok)
                ->orderBy('t.created_at', 'DESC')
                ->limit(5)
                ->get()
                ->getResultArray();

            return $rows;
        } catch (\Exception $e) {
            log_message('warning', '[DashboardPkl] getTugasTerbaru error: ' . $e->getMessage());
            return [];
        }
    }
}
