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
     * Mapping label form → DB ENUM value.
     * Form kirim 'Kuliah' / 'SMK Sederajat',
     * DB menyimpan 'kampus' / 'sekolah'.
     */
    private const KATEGORI_MAP = [
        'Kuliah'       => 'kampus',
        'SMK Sederajat' => 'sekolah',
    ];

    private const KATEGORI_MAP_REVERSE = [
        'kampus'   => 'Kuliah',
        'sekolah'  => 'SMK Sederajat',
    ];

    // ── Static Helper ───────────────────────────────────────────────

    public static function toDbValue(string $label): string
    {
        return self::KATEGORI_MAP[$label] ?? $label;
    }

    public static function toLabelValue(string $dbValue): string
    {
        return self::KATEGORI_MAP_REVERSE[$dbValue] ?? $dbValue;
    }

    // ── Custom Methods ──────────────────────────────────────────────

    /**
     * Semua instansi — format siap pakai untuk view & API.
     * kategori_instansi di-convert ke label yang ramah tampilan.
     */
    public function getAllFormatted(): array
    {
        $rows = $this->select('id_instansi AS id,
                               nama_instansi,
                               alamat_instansi  AS alamat,
                               kota_instansi    AS kota,
                               kategori_instansi,
                               created_at  AS tgl_dibuat,
                               updated_at  AS tgl_diubah')
            ->orderBy('nama_instansi', 'ASC')
            ->findAll();

        foreach ($rows as &$row) {
            $row['kategori_label'] = self::toLabelValue($row['kategori_instansi']);
        }

        return $rows;
    }

    /**
     * Satu instansi by ID — format siap pakai.
     */
    public function getOneFormatted(int $id): ?array
    {
        $row = $this->select('id_instansi AS id,
                              nama_instansi,
                              alamat_instansi  AS alamat,
                              kota_instansi    AS kota,
                              kategori_instansi,
                              created_at  AS tgl_dibuat,
                              updated_at  AS tgl_diubah')
            ->where('id_instansi', $id)
            ->first();

        if (! $row) return null;

        $row['kategori_label'] = self::toLabelValue($row['kategori_instansi']);

        return $row;
    }

    /**
     * Daftar kota unik — untuk dropdown filter kota.
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
     * Cek apakah nama instansi sudah ada.
     * $exceptId dipakai saat update.
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
     * Cek apakah instansi masih dipakai kelompok PKL.
     * Dipakai deleteInstansi() untuk cegah hapus data yang masih terpakai.
     */
    public function isUsedByKelompok(int $idInstansi): bool
    {
        return $this->db->table('kelompok_pkl')
            ->where('id_instansi', $idInstansi)
            ->countAllResults() > 0;
    }

    /**
     * Dropdown-friendly: return ['id' => 'nama'] untuk form select.
     */
    public function getDropdown(): array
    {
        $rows = $this->select('id_instansi, nama_instansi')
            ->orderBy('nama_instansi', 'ASC')
            ->findAll();

        return array_column($rows, 'nama_instansi', 'id_instansi');
    }
}
