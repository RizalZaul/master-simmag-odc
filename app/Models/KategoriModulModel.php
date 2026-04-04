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

    // Palet warna dan ikon untuk card kategori
    private const COLOR_PALETTE = ['teal', 'blue', 'purple', 'orange', 'red', 'green', 'indigo', 'pink'];
    private const ICON_PALETTE  = ['fa-book-open'];

    // ── Custom Methods ──────────────────────────────────────────────

    /**
     * Kategori modul untuk dashboard (Admin & PKL).
     * Hanya kategori yang memiliki minimal 1 modul.
     * Tiap item dilengkapi color + icon dari palet berdasarkan index.
     *
     * Keys: id, nama, total_modul, color, icon
     *
     * Dipakai DashboardAdminController dan DashboardPklController.
     */
    public function getForDashboard(): array
    {
        $rows = $this->db->table('kategori_modul km')
            ->select('km.id_kat_m AS id, km.nama_kat_m AS nama, COUNT(m.id_modul) AS total_modul')
            ->join('modul m', 'm.id_kat_m = km.id_kat_m', 'left')
            ->groupBy('km.id_kat_m')
            ->having('total_modul >', 0)
            ->orderBy('km.nama_kat_m', 'ASC')
            ->get()->getResultArray();

        return array_values(array_map(function ($row, $i) {
            return [
                'id'          => (int) $row['id'],
                'nama'        => $row['nama'],
                'total_modul' => (int) $row['total_modul'],
                'color'       => self::COLOR_PALETTE[$i % count(self::COLOR_PALETTE)],
                'icon'        => self::ICON_PALETTE[$i  % count(self::ICON_PALETTE)],
            ];
        }, $rows, array_keys($rows)));
    }

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
     * @deprecated Gunakan getForDashboard() — method ini dihapus
     *             karena progress/selesai sudah tidak ditampilkan di dashboard PKL.
     *             Dipertahankan untuk backward-compatibility jika ada kode lain
     *             yang masih memanggilnya, tapi akan mengembalikan data tanpa
     *             field progress dan selesai.
     */
    public function getForPklDashboard(): array
    {
        return $this->getForDashboard();
    }
}
