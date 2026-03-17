<?php

namespace App\Controllers;

use App\Models\KelompokPklModel;
use App\Models\KategoriModulModel;
use App\Models\TugasModel;

/**
 * DashboardAdminController
 *
 * Data yang ditampilkan:
 *   1. Stat PKL    : kelompok aktif / selesai / PKL nonaktif
 *   2. Modul       : semua kategori modul + count modul (per kategori)
 *   3. Tugas       : semua tugas aktif + overdue yang belum lunas pengumpulan
 */
class DashboardAdminController extends BaseController
{
    protected KelompokPklModel   $kelompokModel;
    protected KategoriModulModel $kategoriModulModel;
    protected TugasModel         $tugasModel;

    public function __construct()
    {
        $this->kelompokModel      = new KelompokPklModel();
        $this->kategoriModulModel = new KategoriModulModel();
        $this->tugasModel         = new TugasModel();
    }

    public function index()
    {
        $data = [
            'page_title'  => 'Dashboard Admin',
            'active_menu' => 'dashboard',

            // 1. Stat cards PKL
            'stats'       => $this->kelompokModel->getDashboardStats(),

            // 2. Kategori modul + count (ganti dari 6 modul individual)
            'modulList'   => $this->kategoriModulModel->getForDashboard(),

            // 3. Semua tugas aktif + overdue belum lunas
            'tugasList'   => $this->tugasModel->getDashboardAdmin(),
        ];

        $data['content'] = view('dashboard_admin/dashboard', $data);

        return view('Layouts/dashboard_layout', $data);
    }
}
