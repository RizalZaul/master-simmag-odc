<?php

namespace App\Models;

use CodeIgniter\Model;

class KategoriTugasModel extends Model
{
    protected $table            = 'kategori_tugas';
    protected $primaryKey       = 'id_kat_tugas';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';

    protected $allowedFields = [
        'nama_kat_tugas',
        'mode_pengumpulan',
    ];

    // ── Custom Methods ──────────────────────────────────────────────

    /**
     * Semua kategori tugas — format siap pakai untuk view & API.
     * Menyertakan jumlah tugas yang menggunakan kategori ini.
     * Dipakai loadKategoriTugas() dan getKategoriList().
     *
     * Keys: id, nama_kategori, mode_pengumpulan, jumlah_tugas, tgl_dibuat, tgl_diubah
     */
    public function getFormattedList(): array
    {
        return $this->db->table('kategori_tugas')
            ->select("kategori_tugas.id_kat_tugas                               AS id,
                      kategori_tugas.nama_kat_tugas                             AS nama_kategori,
                      kategori_tugas.mode_pengumpulan,
                      COUNT(tugas.id_tugas)                                     AS jumlah_tugas,
                      DATE_FORMAT(kategori_tugas.created_at, '%d-%m-%Y')        AS tgl_dibuat,
                      DATE_FORMAT(kategori_tugas.updated_at, '%d-%m-%Y')        AS tgl_diubah")
            ->join('tugas', 'tugas.id_kat_tugas = kategori_tugas.id_kat_tugas', 'left')
            ->groupBy('kategori_tugas.id_kat_tugas')
            ->orderBy('kategori_tugas.nama_kat_tugas', 'ASC')
            ->get()->getResultArray();
    }

    /**
     * Satu kategori by ID — format siap pakai untuk API response.
     * Dipakai getKategori().
     *
     * Keys: id, nama_kategori, mode_pengumpulan, jumlah_tugas, tgl_dibuat, tgl_diubah
     */
    public function getFormatted(int $id): ?array
    {
        return $this->db->table('kategori_tugas')
            ->select("kategori_tugas.id_kat_tugas                               AS id,
                      kategori_tugas.nama_kat_tugas                             AS nama_kategori,
                      kategori_tugas.mode_pengumpulan,
                      COUNT(tugas.id_tugas)                                     AS jumlah_tugas,
                      DATE_FORMAT(kategori_tugas.created_at, '%d-%m-%Y')        AS tgl_dibuat,
                      DATE_FORMAT(kategori_tugas.updated_at, '%d-%m-%Y')        AS tgl_diubah")
            ->join('tugas', 'tugas.id_kat_tugas = kategori_tugas.id_kat_tugas', 'left')
            ->where('kategori_tugas.id_kat_tugas', $id)
            ->groupBy('kategori_tugas.id_kat_tugas')
            ->get()->getRowArray() ?: null;
    }

    /**
     * Dropdown-friendly untuk form pilih kategori di tambah/ubah tugas.
     * Return format: [['id' => ..., 'nama_kategori' => ..., 'mode' => ...], ...]
     * Dipakai tambahTugas() dan ubahTugas().
     */
    public function getOptionList(): array
    {
        return $this->select('id_kat_tugas AS id, nama_kat_tugas AS nama_kategori, mode_pengumpulan AS mode')
            ->orderBy('nama_kat_tugas', 'ASC')
            ->findAll();
    }

    /**
     * Cek apakah kategori masih dipakai oleh satu atau lebih tugas.
     * Dipakai deleteKategori() di controller.
     *
     * Return: jumlah tugas yang memakai kategori ini (0 = aman dihapus)
     */
    public function countUsedByTugas(int $id): int
    {
        return (int) $this->db->table('tugas')
            ->where('id_kat_tugas', $id)
            ->countAllResults();
    }
}
