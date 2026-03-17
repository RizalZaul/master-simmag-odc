<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * PengumpulanTugasModel
 *
 * Model untuk tabel `pengumpulan_tugas`.
 * Satu baris = satu sesi pengumpulan dari satu PKL/kelompok/tim untuk satu tugas.
 * Detail file/link-nya ada di tabel `item_tugas`.
 */
class PengumpulanTugasModel extends Model
{
    protected $table            = 'pengumpulan_tugas';
    protected $primaryKey       = 'id_pengumpulan_tgs';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';

    protected $allowedFields = [
        'id_tugas',
        'id_pkl',
        'id_kelompok',
        'id_tim',
        'tgl_pengumpulan',
    ];

    // ── Custom Methods ──────────────────────────────────────────────

    /**
     * Cek apakah PKL sudah mengumpulkan tugas ini.
     * Return: row pengumpulan atau null.
     */
    public function getPengumpulanPkl(int $idTugas, int $idPkl): ?array
    {
        return $this->where('id_tugas', $idTugas)
            ->where('id_pkl', $idPkl)
            ->first();
    }

    /**
     * Semua pengumpulan untuk satu tugas beserta nama PKL.
     * Dipakai halaman detail tugas admin.
     */
    public function getByTugas(int $idTugas): array
    {
        return $this->select('pengumpulan_tugas.*, pkl.nama_lengkap AS nama_pkl,
                              pkl.nama_panggilan, kelompok_pkl.nama_kelompok')
            ->join('pkl',          'pkl.id_pkl              = pengumpulan_tugas.id_pkl',       'left')
            ->join('kelompok_pkl', 'kelompok_pkl.id_kelompok = pengumpulan_tugas.id_kelompok',  'left')
            ->where('pengumpulan_tugas.id_tugas', $idTugas)
            ->orderBy('pengumpulan_tugas.tgl_pengumpulan', 'ASC')
            ->findAll();
    }

    /**
     * Semua pengumpulan milik satu PKL beserta nama tugas.
     * Dipakai halaman riwayat PKL.
     */
    public function getByPkl(int $idPkl): array
    {
        return $this->select('pengumpulan_tugas.*, tugas.nama_tugas, tugas.deadline,
                              kategori_tugas.nama_kat_tugas')
            ->join('tugas',          'tugas.id_tugas            = pengumpulan_tugas.id_tugas',    'left')
            ->join('kategori_tugas', 'kategori_tugas.id_kat_tugas = tugas.id_kat_tugas',          'left')
            ->where('pengumpulan_tugas.id_pkl', $idPkl)
            ->orderBy('pengumpulan_tugas.tgl_pengumpulan', 'DESC')
            ->findAll();
    }

    /**
     * Hitung jumlah PKL yang sudah mengumpulkan untuk satu tugas.
     */
    public function countByTugas(int $idTugas): int
    {
        return $this->where('id_tugas', $idTugas)
            ->where('id_pkl IS NOT NULL', null, false)
            ->countAllResults();
    }
}
