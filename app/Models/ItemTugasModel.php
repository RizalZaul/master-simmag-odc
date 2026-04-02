<?php

namespace App\Models;

use CodeIgniter\Model;

class ItemTugasModel extends Model
{
    protected $table            = 'item_tugas';            // ← FIX: was 'itemtugas'
    protected $primaryKey       = 'id_item';               // ← FIX: was 'id'
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';

    protected $allowedFields = [
        'id_pengumpulan_tgs',
        'tipe_item',
        'data_item',
        'komentar',
        'status_item',
    ];

    // ── Validation ─────────────────────────────────────────────────
    protected $validationRules      = [];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;


    // ==========================================
    // QUERY METHODS
    // ==========================================

    /**
     * BUG-06 FIX: Ambil semua item tugas untuk satu record pengumpulan.
     *
     * Menggantikan raw SQL inline di PklTugasController::detail() agar
     * query terpusat di model dan controller tetap tipis.
     *
     * Setiap item di-map statusnya via PengumpulanTugasModel::mapItemStatus()
     * sehingga view menerima key 'status' (human-readable) bukan 'status_item' (raw).
     *
     * @param int $idPengumpulan  id_pengumpulan_tgs
     * @return array  Array of items: [id, tipe, data, status, komentar, created_at]
     */
    public function getByPengumpulan(int $idPengumpulan): array
    {
        $rows = $this->db
            ->table('item_tugas it')
            ->select('it.id_item AS id, it.tipe_item AS tipe, it.data_item AS data, it.status_item, it.komentar, it.created_at')
            ->where('it.id_pengumpulan_tgs', $idPengumpulan)
            ->orderBy('it.id_item', 'ASC')
            ->get()
            ->getResultArray();

        return array_map(static function (array $item): array {
            $item['status'] = \App\Models\PengumpulanTugasModel::mapItemStatus($item['status_item']);
            unset($item['status_item']);
            return $item;
        }, $rows);
    }

    public function getAdminItemsByPengumpulanIds(array $pengumpulanIds): array
    {
        $pengumpulanIds = array_values(array_unique(array_filter(array_map('intval', $pengumpulanIds), static fn($id) => $id > 0)));
        if ($pengumpulanIds === []) {
            return [];
        }

        return $this->db->table('item_tugas it')
            ->select('it.id_item, it.id_pengumpulan_tgs, it.tipe_item, it.data_item, it.status_item, it.komentar, it.created_at')
            ->select('p.nama_lengkap AS nama_pengirim')
            ->join('pengumpulan_tugas pt', 'pt.id_pengumpulan_tgs = it.id_pengumpulan_tgs')
            ->join('pkl p', 'p.id_pkl = pt.id_pkl', 'left')
            ->whereIn('it.id_pengumpulan_tgs', $pengumpulanIds)
            ->orderBy('it.created_at', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getStatsMapByPengumpulanIds(array $pengumpulanIds): array
    {
        $pengumpulanIds = array_values(array_unique(array_filter(array_map('intval', $pengumpulanIds), static fn($id) => $id > 0)));
        if ($pengumpulanIds === []) {
            return [];
        }

        $rows = $this->db->table('item_tugas')
            ->select('id_pengumpulan_tgs')
            ->select('COUNT(*) AS total_item', false)
            ->select("SUM(CASE WHEN status_item = 'dikirim' THEN 1 ELSE 0 END) AS total_dikirim", false)
            ->select("SUM(CASE WHEN status_item = 'revisi' THEN 1 ELSE 0 END) AS total_revisi", false)
            ->select("SUM(CASE WHEN status_item = 'diterima' THEN 1 ELSE 0 END) AS total_diterima", false)
            ->whereIn('id_pengumpulan_tgs', $pengumpulanIds)
            ->groupBy('id_pengumpulan_tgs')
            ->get()
            ->getResultArray();

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row['id_pengumpulan_tgs']] = [
                'total_item'     => (int) ($row['total_item'] ?? 0),
                'total_dikirim'  => (int) ($row['total_dikirim'] ?? 0),
                'total_revisi'   => (int) ($row['total_revisi'] ?? 0),
                'total_diterima' => (int) ($row['total_diterima'] ?? 0),
            ];
        }

        return $map;
    }

    public function findAdminItemById(int $idItem): ?array
    {
        if ($idItem < 1) {
            return null;
        }

        $row = $this->db->table('item_tugas it')
            ->select('it.id_item, it.id_pengumpulan_tgs, it.tipe_item, it.data_item, it.status_item, it.komentar, it.created_at, it.updated_at')
            ->select('pt.id_tugas, pt.id_pkl, pt.id_kelompok, pt.id_tim, pt.tgl_pengumpulan')
            ->select('p.nama_lengkap AS nama_pengirim')
            ->join('pengumpulan_tugas pt', 'pt.id_pengumpulan_tgs = it.id_pengumpulan_tgs')
            ->join('pkl p', 'p.id_pkl = pt.id_pkl', 'left')
            ->where('it.id_item', $idItem)
            ->get()
            ->getRowArray();

        return $row ?: null;
    }
}
