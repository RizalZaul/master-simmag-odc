<?php

namespace App\Models;

use CodeIgniter\Model;

class InstansiModel extends Model
{
    protected $table            = 'instansi';
    protected $primaryKey       = 'id_instansi';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';

    protected $allowedFields = [
        'kategori_instansi',
        'nama_instansi',
        'alamat_instansi',
        'kota_instansi',
    ];

    /**
     * Mapping label form → DB ENUM value dan sebaliknya.
     * Form kirim 'Kuliah' / 'SMK Sederajat',
     * DB menyimpan 'kampus' / 'sekolah'.
     */
    private const KATEGORI_MAP = [
        'Kuliah'        => 'kampus',
        'SMK Sederajat' => 'sekolah',
    ];

    private const KATEGORI_MAP_REVERSE = [
        'kampus'  => 'Kuliah',
        'sekolah' => 'SMK Sederajat',
    ];

    // ── Static Helpers ──────────────────────────────────────────────

    public static function toDbValue(string $label): string
    {
        return self::KATEGORI_MAP[$label] ?? $label;
    }

    public static function toLabelValue(string $dbValue): string
    {
        return self::KATEGORI_MAP_REVERSE[$dbValue] ?? $dbValue;
    }

    // ── Read Methods ────────────────────────────────────────────────

    /**
     * Semua instansi — format siap view + DataTables.
     * Alamat ditampilkan sebagai "alamat, kota" untuk kolom tabel.
     */
    public function getAllFormatted(): array
    {
        $rows = $this->select('id_instansi,
                               nama_instansi,
                               alamat_instansi,
                               kota_instansi,
                               kategori_instansi')
            ->orderBy('nama_instansi', 'ASC')
            ->findAll();

        foreach ($rows as &$row) {
            $row['kategori_label'] = self::toLabelValue($row['kategori_instansi']);
            $row['alamat_kota']    = trim(
                ($row['alamat_instansi'] ?? '') . ', ' . ($row['kota_instansi'] ?? ''),
                ', '
            );
        }

        return $rows;
    }

    /**
     * Satu instansi by ID — format siap view.
     */
    public function getOneFormatted(int $id): ?array
    {
        $row = $this->where('id_instansi', $id)->first();
        if (! $row) return null;

        $row['kategori_label'] = self::toLabelValue($row['kategori_instansi']);
        $row['alamat_kota']    = trim(
            ($row['alamat_instansi'] ?? '') . ', ' . ($row['kota_instansi'] ?? ''),
            ', '
        );

        return $row;
    }

    /**
     * Daftar kota unik — untuk Select2 dropdown filter & form.
     */
    public function getKotaList(): array
    {
        $rows = $this->select('kota_instansi')
            ->distinct()
            ->orderBy('kota_instansi', 'ASC')
            ->findAll();

        return array_column($rows, 'kota_instansi');
    }

    /**
     * Cek apakah nama instansi sudah ada (untuk validasi unik).
     * $exceptId dipakai saat update agar nama instansi sendiri tidak dihitung.
     */
    public function isNamaExists(string $nama, ?int $exceptId = null): bool
    {
        $builder = $this->where('nama_instansi', $nama);
        if ($exceptId !== null) {
            $builder = $builder->where('id_instansi !=', $exceptId);
        }
        return $builder->countAllResults() > 0;
    }

    /**
     * Cek apakah instansi masih dipakai oleh kelompok PKL.
     * Dipakai sebelum delete untuk mencegah orphan data.
     */
    public function isUsedByKelompok(int $idInstansi): bool
    {
        return $this->db->table('kelompok_pkl')
            ->where('id_instansi', $idInstansi)
            ->countAllResults() > 0;
    }

    /**
     * Dropdown-friendly: ['id_instansi' => 'nama_instansi'].
     * Dipakai form select pilih instansi (mis. tambah kelompok).
     */
    public function getDropdown(): array
    {
        $rows = $this->select('id_instansi, nama_instansi')
            ->orderBy('nama_instansi', 'ASC')
            ->findAll();

        return array_column($rows, 'nama_instansi', 'id_instansi');
    }
}
