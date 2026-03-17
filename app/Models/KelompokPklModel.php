<?php

namespace App\Models;

use CodeIgniter\Model;

class KelompokPklModel extends Model
{
    protected $table            = 'kelompok_pkl';
    protected $primaryKey       = 'id_kelompok';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';

    protected $allowedFields = [
        'id_instansi',
        'nama_kelompok',
        'nama_pembimbing',
        'no_wa_pembimbing',
        'tgl_mulai',
        'tgl_akhir',
        'status',
    ];

    // ── Custom Methods ──────────────────────────────────────────────

    /**
     * Sinkronisasi kolom status kelompok_pkl berdasarkan tgl_akhir.
     *
     * FIX BUG-06 (sentralisasi):
     * Sebelumnya, logika UPDATE ini ada di DUA tempat secara terpisah:
     *   1. Inline di getDashboardStats() (KelompokPklModel)
     *   2. Di _syncKelompokStatus() (PklAdminModel)
     * Akibatnya UPDATE bisa berjalan dua kali dalam satu HTTP request.
     *
     * Solusi: pindah ke satu static method di sini dengan static $synced flag.
     * PklAdminModel::_syncKelompokStatus() sekarang hanya memanggil method ini.
     * Dengan begitu, sync hanya berjalan sekali per request, tidak peduli
     * berapa banyak model yang memanggilnya.
     */
    public static function syncStatus(): void
    {
        static $synced = false;
        if ($synced) return;
        $synced = true;

        $db    = \Config\Database::connect();
        $today = date('Y-m-d');

        $db->table('kelompok_pkl')
            ->where('tgl_akhir <', $today)
            ->where('status', 'aktif')
            ->update(['status' => 'selesai']);

        $db->table('kelompok_pkl')
            ->where('tgl_akhir >=', $today)
            ->where('status', 'selesai')
            ->update(['status' => 'aktif']);
    }

    /**
     * Stat cards untuk dashboard admin.
     * Return: ['aktif' => int, 'selesai' => int, 'nonaktif' => int]
     *
     * Aktif   : kelompok dengan tgl_akhir >= hari ini
     * Selesai : kelompok dengan tgl_akhir < hari ini
     * NonAktif: user PKL dengan status = 'nonaktif' (dibekukan manual)
     */
    public function getDashboardStats(): array
    {
        // Gunakan static method terpusat — tidak akan double-run jika
        // PklAdminModel::_syncKelompokStatus() juga dipanggil di request yang sama.
        self::syncStatus();

        $db    = $this->db;
        $today = date('Y-m-d');

        return [
            'aktif' => $db->table('kelompok_pkl')
                ->where('tgl_akhir >=', $today)
                ->countAllResults(),

            'selesai' => $db->table('kelompok_pkl')
                ->where('tgl_akhir <', $today)
                ->countAllResults(),

            'nonaktif' => $db->table('users')
                ->join('pkl', 'pkl.id_user = users.id_user', 'inner')
                ->where('users.role', 'pkl')
                ->where('users.status', 'nonaktif')
                ->countAllResults(),
        ];
    }

    /**
     * Semua kelompok beserta nama instansinya.
     */
    public function getAllWithInstansi(): array
    {
        return $this->select('kelompok_pkl.id_kelompok,
                              kelompok_pkl.nama_kelompok,
                              kelompok_pkl.nama_pembimbing,
                              kelompok_pkl.no_wa_pembimbing,
                              kelompok_pkl.tgl_mulai,
                              kelompok_pkl.tgl_akhir,
                              kelompok_pkl.status,
                              kelompok_pkl.created_at  AS tgl_dibuat,
                              kelompok_pkl.updated_at  AS tgl_diubah,
                              instansi.nama_instansi,
                              instansi.kota_instansi,
                              instansi.kategori_instansi')
            ->join('instansi', 'instansi.id_instansi = kelompok_pkl.id_instansi', 'left')
            ->orderBy('kelompok_pkl.nama_kelompok', 'ASC')
            ->findAll();
    }

    /**
     * Satu kelompok by ID beserta nama instansinya.
     */
    public function getOneWithInstansi(int $id): ?array
    {
        return $this->select('kelompok_pkl.id_kelompok,
                              kelompok_pkl.nama_kelompok,
                              kelompok_pkl.nama_pembimbing,
                              kelompok_pkl.no_wa_pembimbing,
                              kelompok_pkl.tgl_mulai,
                              kelompok_pkl.tgl_akhir,
                              kelompok_pkl.status,
                              kelompok_pkl.created_at  AS tgl_dibuat,
                              kelompok_pkl.updated_at  AS tgl_diubah,
                              instansi.id_instansi,
                              instansi.nama_instansi,
                              instansi.kota_instansi,
                              instansi.kategori_instansi')
            ->join('instansi', 'instansi.id_instansi = kelompok_pkl.id_instansi', 'left')
            ->where('kelompok_pkl.id_kelompok', $id)
            ->first();
    }

    /**
     * Hanya kelompok berstatus aktif.
     *
     * BUG-06 FIX: Sebelumnya hanya memanggil getAllWithInstansi() tanpa filter,
     * sehingga semua kelompok (aktif, selesai, nonaktif) dikembalikan.
     * Sekarang filter eksplisit WHERE status = 'aktif' diterapkan.
     * syncStatus() dipanggil agar status kelompok selalu up-to-date sebelum query.
     */
    public function getAktif(): array
    {
        self::syncStatus();

        return $this->select('kelompok_pkl.id_kelompok,
                              kelompok_pkl.nama_kelompok,
                              kelompok_pkl.nama_pembimbing,
                              kelompok_pkl.no_wa_pembimbing,
                              kelompok_pkl.tgl_mulai,
                              kelompok_pkl.tgl_akhir,
                              kelompok_pkl.status,
                              kelompok_pkl.created_at  AS tgl_dibuat,
                              kelompok_pkl.updated_at  AS tgl_diubah,
                              instansi.nama_instansi,
                              instansi.kota_instansi,
                              instansi.kategori_instansi')
            ->join('instansi', 'instansi.id_instansi = kelompok_pkl.id_instansi', 'left')
            ->where('kelompok_pkl.status', 'aktif')
            ->orderBy('kelompok_pkl.nama_kelompok', 'ASC')
            ->findAll();
    }

    /**
     * Dropdown-friendly untuk form select.
     */
    public function getDropdown(): array
    {
        $rows = $this->select('id_kelompok, nama_kelompok')
            ->orderBy('nama_kelompok', 'ASC')
            ->findAll();

        return array_column($rows, 'nama_kelompok', 'id_kelompok');
    }

    /**
     * Cek apakah nama kelompok sudah ada.
     */
    public function isNamaExists(string $nama, ?int $exceptId = null): bool
    {
        $builder = $this->where('nama_kelompok', $nama);

        if ($exceptId !== null) {
            $builder = $builder->where('id_kelompok !=', $exceptId);
        }

        return $builder->countAllResults() > 0;
    }
}
