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
}
