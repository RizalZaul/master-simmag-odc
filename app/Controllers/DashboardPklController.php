<?php

namespace App\Controllers;

use App\Models\KategoriModulModel;
use App\Models\TugasModel;

/**
 * DashboardPklController
 *
 * Data yang ditampilkan:
 *   1. Stat tugas  : total / selesai / pending / belum_dikirim (dari semua 3 jalur sasaran)
 *   2. Modul       : semua kategori modul + count modul (tanpa progress bar)
 *   3. Tugas       : tugas belum selesai (belum kirim + revisi + menunggu review)
 */
class DashboardPklController extends BaseController
{
    protected KategoriModulModel $kategoriModulModel;
    protected TugasModel         $tugasModel;

    public function __construct()
    {
        $this->kategoriModulModel = new KategoriModulModel();
        $this->tugasModel         = new TugasModel();
    }

    public function index()
    {
        $idPkl      = (int) session()->get('id_pkl');
        $idKelompok = (int) session()->get('id_kelompok');

        $data = [
            'page_title'  => 'Dashboard PKL',
            'active_menu' => 'dashboard',

            // 1. Stat tugas PKL ini (semua jalur: individu, kelompok, tim)
            'statsT'      => $this->tugasModel->getStatsPkl($idPkl, $idKelompok),

            // 2. Kategori modul + count (tanpa progress bar)
            'modulList'   => $this->kategoriModulModel->getForDashboard(),

            // 3. Semua tugas aktif + overdue belum dikumpulkan
            'tugasList'   => $this->tugasModel->getDashboardPkl($idPkl, $idKelompok),
        ];

        $data['content'] = view('dashboard_pkl/dashboard', $data);

        return view('Layouts/dashboard_layout', $data);
    }
}
