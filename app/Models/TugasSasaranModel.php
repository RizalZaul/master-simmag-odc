<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * TugasSasaranModel
 *
 * Model untuk tabel `tugas_sasaran`.
 * Menentukan siapa saja penerima tugas.
 *
 * target_tipe:
 *   'individu'  → id_pkl diisi, id_kelompok & id_tim NULL
 *   'kelompok'  → id_kelompok diisi, id_pkl & id_tim NULL
 *   'tim_tugas' → id_tim diisi, id_pkl & id_kelompok NULL
 */
class TugasSasaranModel extends Model
{
    protected $table            = 'tugas_sasaran';
    protected $primaryKey       = 'id_sasaran';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';

    protected $allowedFields = [
        'id_tugas',
        'target_tipe',
        'id_pkl',
        'id_kelompok',
        'id_tim',
    ];

    // ── Custom Methods ──────────────────────────────────────────────

    /**
     * Semua sasaran untuk satu tugas beserta detail nama.
     *
     * Keys: id_sasaran, target_tipe,
     *       id_pkl, nama_pkl (nullable),
     *       id_kelompok, nama_kelompok (nullable),
     *       id_tim, nama_tim (nullable)
     */
    public function getByTugas(int $idTugas): array
    {
        return $this->select('tugas_sasaran.*,
                              pkl.nama_lengkap   AS nama_pkl,
                              kelompok_pkl.nama_kelompok,
                              tim_tugas.nama_tim')
            ->join('pkl',          'pkl.id_pkl             = tugas_sasaran.id_pkl',      'left')
            ->join('kelompok_pkl', 'kelompok_pkl.id_kelompok = tugas_sasaran.id_kelompok', 'left')
            ->join('tim_tugas',    'tim_tugas.id_tim         = tugas_sasaran.id_tim',      'left')
            ->where('tugas_sasaran.id_tugas', $idTugas)
            ->findAll();
    }

    /**
     * Hapus semua sasaran untuk satu tugas.
     * Dipakai saat update tugas (reset sasaran lama, insert baru).
     */
    public function deleteByTugas(int $idTugas): void
    {
        $this->where('id_tugas', $idTugas)->delete();
    }

    /**
     * Cek apakah PKL tertentu adalah penerima tugas ini
     * (dari jalur individu, kelompok, atau tim).
     */
    public function isPklSasaran(int $idTugas, int $idPkl, int $idKelompok): bool
    {
        // Cek jalur individu dan kelompok
        $langsung = $this->groupStart()
            ->where('target_tipe', 'individu')->where('id_pkl', $idPkl)
            ->orGroupStart()
            ->where('target_tipe', 'kelompok')->where('id_kelompok', $idKelompok)
            ->groupEnd()
            ->groupEnd()
            ->where('id_tugas', $idTugas)
            ->countAllResults();

        if ($langsung > 0) return true;

        // Cek jalur tim
        $timIds = $this->db->table('anggota_tim_tugas')
            ->where('id_pkl', $idPkl)
            ->get()->getResultArray();

        if (empty($timIds)) return false;

        $ids = array_column($timIds, 'id_tim');

        return $this->where('id_tugas', $idTugas)
            ->where('target_tipe', 'tim_tugas')
            ->whereIn('id_tim', $ids)
            ->countAllResults() > 0;
    }
}
