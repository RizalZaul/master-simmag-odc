<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * AnggotaTimTugasModel
 * Model untuk tabel `anggota_tim_tugas`.
 * Pivot table antara tim_tugas dan pkl.
 */
class AnggotaTimTugasModel extends Model
{
    protected $table            = 'anggota_tim_tugas';
    protected $primaryKey       = 'id_anggota_tim';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';

    protected $allowedFields = [
        'id_tim',
        'id_pkl',
    ];

    // ── Custom Methods ──────────────────────────────────────────────

    /**
     * Semua anggota satu tim beserta nama PKL.
     */
    public function getByTim(int $idTim): array
    {
        return $this->select('anggota_tim_tugas.*, pkl.nama_lengkap, pkl.nama_panggilan,
                              kelompok_pkl.nama_kelompok')
            ->join('pkl',          'pkl.id_pkl              = anggota_tim_tugas.id_pkl',      'left')
            ->join('kelompok_pkl', 'kelompok_pkl.id_kelompok = pkl.id_kelompok',               'left')
            ->where('anggota_tim_tugas.id_tim', $idTim)
            ->orderBy('pkl.nama_lengkap', 'ASC')
            ->findAll();
    }

    /**
     * Semua tim yang diikuti PKL tertentu.
     * Return: array of id_tim.
     */
    public function getTimByPkl(int $idPkl): array
    {
        $rows = $this->select('id_tim')
            ->where('id_pkl', $idPkl)
            ->findAll();

        return array_column($rows, 'id_tim');
    }

    /**
     * Cek apakah PKL sudah menjadi anggota tim ini.
     */
    public function isAnggota(int $idTim, int $idPkl): bool
    {
        return $this->where('id_tim', $idTim)
            ->where('id_pkl', $idPkl)
            ->countAllResults() > 0;
    }

    /**
     * Hapus semua anggota dari satu tim.
     * Dipakai saat reset anggota tim.
     */
    public function deleteByTim(int $idTim): void
    {
        $this->where('id_tim', $idTim)->delete();
    }
}
