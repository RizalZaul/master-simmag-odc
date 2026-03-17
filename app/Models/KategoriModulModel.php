<?php

namespace App\Models;

use CodeIgniter\Model;

class KategoriModulModel extends Model
{
    protected $table            = 'kategori_modul';
    protected $primaryKey       = 'id_kat_m';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';

    protected $allowedFields = [
        'nama_kat_m',
    ];

    // ── Custom Methods ──────────────────────────────────────────────

    /**
     * Semua kategori — format siap pakai untuk view & API response.
     * Menambahkan jumlah modul per kategori sekalian.
     */
    public function getAllFormatted(): array
    {
        return $this->db->table('kategori_modul')
            ->select('kategori_modul.id_kat_m        AS id,
                              kategori_modul.nama_kat_m      AS nama_kategori,
                              COUNT(modul.id_modul)          AS jumlah_modul,
                              kategori_modul.created_at      AS tgl_dibuat,
                              kategori_modul.updated_at      AS tgl_diubah')
            ->join('modul', 'modul.id_kat_m = kategori_modul.id_kat_m', 'left')
            ->groupBy('kategori_modul.id_kat_m')
            ->orderBy('kategori_modul.nama_kat_m', 'ASC')
            ->get()->getResultArray();
    }

    /**
     * Satu kategori by ID — format siap pakai untuk API response.
     */
    public function getOneFormatted(int $id): ?array
    {
        return $this->db->table('kategori_modul')
            ->select('kategori_modul.id_kat_m        AS id,
                              kategori_modul.nama_kat_m      AS nama_kategori,
                              COUNT(modul.id_modul)          AS jumlah_modul,
                              kategori_modul.created_at      AS tgl_dibuat,
                              kategori_modul.updated_at      AS tgl_diubah')
            ->join('modul', 'modul.id_kat_m = kategori_modul.id_kat_m', 'left')
            ->where('kategori_modul.id_kat_m', $id)
            ->groupBy('kategori_modul.id_kat_m')
            ->get()->getRowArray();
    }

    /**
     * Cek apakah nama kategori sudah ada.
     * $exceptId dipakai saat update — exclude baris yang sedang diedit.
     */
    public function isNamaExists(string $nama, ?int $exceptId = null): bool
    {
        $builder = $this->where('nama_kat_m', $nama);

        if ($exceptId !== null) {
            $builder = $builder->where('id_kat_m !=', $exceptId);
        }

        return $builder->countAllResults() > 0;
    }

    /**
     * Dropdown-friendly: return ['id' => 'nama'] untuk form select.
     */
    public function getDropdown(): array
    {
        $rows = $this->select('id_kat_m, nama_kat_m')
            ->orderBy('nama_kat_m', 'ASC')
            ->findAll();

        return array_column($rows, 'nama_kat_m', 'id_kat_m');
    }

    /**
     * Kategori modul untuk dashboard PKL.
     * Hanya kategori yang memiliki modul (skip kosong).
     * Tiap item dilengkapi color + icon dari palet berdasarkan index.
     *
     * Keys: id, nama, color, icon, total_modul
     * Dipakai PklDashboardController::index().
     */
    public function getForPklDashboard(): array
    {
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

        $rows = $this->db->table('kategori_modul km')
            ->select('km.id_kat_m AS id, km.nama_kat_m AS nama, COUNT(m.id_modul) AS total_modul')
            ->join('modul m', 'm.id_kat_m = km.id_kat_m', 'left')
            ->groupBy('km.id_kat_m')
            ->having('total_modul >', 0)
            ->orderBy('km.nama_kat_m', 'ASC')
            ->get()->getResultArray();

        return array_values(array_map(function ($row, $i) use ($colorPalette, $iconPalette) {
            return [
                'id'           => $row['id'],
                'nama'         => $row['nama'],
                'color'        => $colorPalette[$i % count($colorPalette)],
                'icon'         => $iconPalette[$i % count($iconPalette)],
                'progress'     => 0,
                'selesai'      => 0,
                'total_materi' => (int) $row['total_modul'],  // key sesuai view
            ];
        }, $rows, array_keys($rows)));
    }
}
