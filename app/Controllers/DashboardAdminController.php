<?php

namespace App\Controllers;

use App\Models\KelompokPklModel;
use App\Models\ModulModel;

/**
 * DashboardAdminController
 * Menangani halaman dashboard untuk role admin.
 *
 * Data yang ditampilkan:
 *   1. Stat cards   : jumlah kelompok aktif/selesai, PKL nonaktif
 *   2. Modul terbaru: 6 modul terbaru
 *   3. Tugas aktif  : 5 tugas yang belum melewati deadline (perlu perhatian)
 *
 * Catatan tugas:
 *   Query mengasumsikan tabel `tugas` dan `pengumpulan` sudah ada.
 *   Jika tabel belum tersedia, bagian tugas akan ditampilkan sebagai empty state.
 */
class DashboardAdminController extends BaseController
{
    protected KelompokPklModel $kelompokModel;
    protected ModulModel       $modulModel;

    public function __construct()
    {
        $this->kelompokModel = new KelompokPklModel();
        $this->modulModel    = new ModulModel();
    }

    // ==========================================
    // DASHBOARD
    // ==========================================

    public function index()
    {
        // ── 1. Stat cards ───────────────────────────────────────────────
        $stats = $this->kelompokModel->getDashboardStats();

        // ── 2. Modul terbaru (6 item) ───────────────────────────────────
        $modulTerbaru = $this->modulModel->getDashboardRecent(6);

        // ── 3. Tugas aktif (perlu perhatian) ────────────────────────────
        //    Asumsi tabel: tugas, kategori_tugas, kelompok_pkl, pkl, pengumpulan
        //    Ubah query ini jika struktur tabel berbeda.
        $tugasAktif = $this->getTugasAktif();

        $data = [
            'page_title'   => 'Dashboard Admin',
            'active_menu'  => 'dashboard',
            'stats'        => $stats,
            'modulTerbaru' => $modulTerbaru,
            'tugasAktif'   => $tugasAktif,
        ];

        $data['content'] = view('dashboard_admin/dashboard', $data);

        return view('Layouts/dashboard_layout', $data);
    }

    // ==========================================
    // PRIVATE HELPERS
    // ==========================================

    /**
     * Ambil 5 tugas aktif (deadline >= hari ini) yang membutuhkan perhatian.
     * Dilengkapi jumlah PKL per kelompok dan jumlah yang sudah mengumpulkan.
     *
     * Return: array of [
     *   id_tugas, nama_tugas, deadline, urgensi,
     *   nama_kat_tugas, total_pkl, sudah_kumpul
     * ]
     *
     * Jika tabel belum tersedia → return [].
     */
    private function getTugasAktif(): array
    {
        try {
            $db    = \Config\Database::connect();
            $today = date('Y-m-d');

            $rows = $db->table('tugas t')
                ->select([
                    't.id_tugas',
                    't.nama_tugas',
                    't.deadline',
                    't.urgensi',
                    'kt.nama_kat_tugas',
                    'COUNT(DISTINCT p.id_pkl)  AS total_pkl',
                    'COUNT(DISTINCT pg.id_pkl) AS sudah_kumpul',
                ])
                ->join('kategori_tugas kt',  'kt.id_kat_tugas = t.id_kat_tugas',   'left')
                ->join('pkl p',              'p.id_kelompok = t.id_kelompok',       'left')
                ->join('pengumpulan pg',     'pg.id_tugas = t.id_tugas',            'left')
                ->where('t.deadline >=', $today)
                ->groupBy('t.id_tugas')
                ->orderBy('t.deadline', 'ASC')
                ->orderBy("FIELD(t.urgensi, 'mendesak', 'normal', 'rendah')", '', false)
                ->limit(5)
                ->get()
                ->getResultArray();

            return $rows;
        } catch (\Exception $e) {
            // Tabel belum tersedia atau query error → tampilkan empty state
            log_message('warning', '[DashboardAdmin] getTugasAktif error: ' . $e->getMessage());
            return [];
        }
    }
}
