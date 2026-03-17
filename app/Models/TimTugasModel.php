<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * TimTugasModel
 * Model untuk tabel `tim_tugas`.
 * Tim adalah pengelompokan PKL secara khusus di luar kelompok reguler,
 * dipakai sebagai salah satu sasaran penugasan.
 */
class TimTugasModel extends Model
{
    protected $table            = 'tim_tugas';
    protected $primaryKey       = 'id_tim';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';

    protected $allowedFields = [
        'nama_tim',
        'deskripsi',
    ];

    // ── Custom Methods ──────────────────────────────────────────────

    /**
     * Semua tim beserta jumlah anggotanya.
     */
    public function getAllWithCount(): array
    {
        return $this->db->table('tim_tugas')
            ->select('tim_tugas.id_tim, tim_tugas.nama_tim, tim_tugas.deskripsi,
                      COUNT(att.id_pkl) AS jumlah_anggota,
                      tim_tugas.created_at AS tgl_dibuat')
            ->join('anggota_tim_tugas att', 'att.id_tim = tim_tugas.id_tim', 'left')
            ->groupBy('tim_tugas.id_tim')
            ->orderBy('tim_tugas.nama_tim', 'ASC')
            ->get()->getResultArray();
    }

    /**
     * Dropdown-friendly untuk form select.
     */
    public function getDropdown(): array
    {
        $rows = $this->select('id_tim, nama_tim')
            ->orderBy('nama_tim', 'ASC')
            ->findAll();

        return array_column($rows, 'nama_tim', 'id_tim');
    }
}
