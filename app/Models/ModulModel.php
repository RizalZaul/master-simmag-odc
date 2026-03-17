<?php

namespace App\Models;

use CodeIgniter\Model;

class ModulModel extends Model
{
    protected $table            = 'modul';
    protected $primaryKey       = 'id_modul';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';

    protected $allowedFields = [
        'id_kat_m',
        'nama_modul',
        'ket_modul',
        'tipe',
        'path',
    ];

    // ── Custom Methods ──────────────────────────────────────────────

    /**
     * 6 modul terbaru untuk dashboard admin.
     * Keys: id, nama, kategori
     */
    public function getDashboardRecent(int $limit = 6): array
    {
        return $this->select('modul.id_modul       AS id,
                              modul.nama_modul     AS nama,
                              kategori_modul.nama_kat_m AS kategori')
            ->join('kategori_modul', 'kategori_modul.id_kat_m = modul.id_kat_m', 'left')
            ->orderBy('modul.updated_at', 'DESC')
            ->limit($limit)
            ->findAll();
    }

    /**
     * Semua modul beserta nama kategorinya.
     * Dipakai loadDataModul() dan halaman listing.
     */
    public function getAllWithKategori(): array
    {
        return $this->select('modul.id_modul                AS id,
                          modul.id_kat_m,
                          modul.nama_modul,
                          modul.ket_modul,
                          modul.tipe,
                          modul.path,
                          modul.created_at                  AS tgl_dibuat,
                          modul.updated_at                  AS tgl_diubah,
                          kategori_modul.nama_kat_m         AS nama_kategori')
            ->join('kategori_modul', 'kategori_modul.id_kat_m = modul.id_kat_m')
            ->orderBy('kategori_modul.nama_kat_m', 'ASC')
            ->orderBy('modul.nama_modul', 'ASC')
            ->findAll();
    }

    /**
     * Satu modul by ID beserta nama kategorinya.
     * Dipakai detailModul() dan ubahModul().
     */
    public function getOneWithKategori(int $id): ?array
    {
        return $this->select('modul.id_modul            AS id,
                          modul.id_kat_m,
                          modul.nama_modul,
                          modul.ket_modul,
                          modul.tipe,
                          modul.path,
                          modul.created_at              AS tgl_dibuat,
                          modul.updated_at              AS tgl_diubah,
                          kategori_modul.nama_kat_m     AS nama_kategori')
            ->join('kategori_modul', 'kategori_modul.id_kat_m = modul.id_kat_m')
            ->where('modul.id_modul', $id)
            ->first();
    }

    /**
     * Semua modul dalam satu kategori — format siap pakai untuk view PKL.
     * Dipakai kategoriModul() di PklModulController.
     *
     * Keys: id, nama, tipe, path, tanggal_diubah
     */
    public function getFormattedByKategori(int $idKategori): array
    {
        $rows = $this->where('id_kat_m', $idKategori)
            ->orderBy('updated_at', 'DESC')
            ->findAll();

        return array_map(fn($m) => [
            'id'             => $m['id_modul'],
            'nama'           => $m['nama_modul'],
            'tipe'           => $m['tipe'],
            'path'           => $m['path'],
            'tanggal_diubah' => ! empty($m['updated_at'])
                ? date('d-m-Y', strtotime($m['updated_at']))
                : '-',
        ], $rows);
    }

    /**
     * Hitung jumlah modul dalam satu kategori.
     * Dipakai deleteKategori() untuk cek apakah kategori aman dihapus.
     */
    public function countByKategori(int $idKatM): int
    {
        return $this->where('id_kat_m', $idKatM)->countAllResults();
    }

    /**
     * Ambil semua modul dalam satu kategori.
     * Dipakai jika butuh listing modul per kategori.
     */
    public function getByKategori(int $idKatM): array
    {
        return $this->where('id_kat_m', $idKatM)
            ->orderBy('nama_modul', 'ASC')
            ->findAll();
    }
}
