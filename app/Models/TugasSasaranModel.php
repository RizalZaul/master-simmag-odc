<?php

namespace App\Models;

use CodeIgniter\Model;

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

    // ── Type Mapping ────────────────────────────────────────────────

    /**
     * Mapping tipe sasaran dari frontend → nilai ENUM di DB.
     *
     * Frontend kirim : 'mandiri'  | 'kelompok'  | 'tim'
     * DB ENUM simpan : 'individu' | 'kelompok'  | 'tim_tugas'
     */
    private const TYPE_MAP = [
        'mandiri'  => 'individu',
        'kelompok' => 'kelompok',
        'tim'      => 'tim_tugas',
    ];

    /**
     * Validasi apakah sasaran_type dari frontend dikenal.
     * Dipakai storeTugas() di Controller.
     */
    public static function isValidType(string $type): bool
    {
        return array_key_exists(strtolower($type), self::TYPE_MAP);
    }

    /**
     * Konversi tipe frontend ke nilai ENUM DB.
     * Dipakai storeTugas() di Controller.
     */
    public static function mapType(string $type): string
    {
        return self::TYPE_MAP[strtolower($type)] ?? $type;
    }

    // ── Custom Methods ──────────────────────────────────────────────

    /**
     * Insert banyak sasaran sekaligus dalam satu transaksi.
     *
     * @param int    $idTugas     ID tugas yang baru dibuat.
     * @param string $targetTipe  Nilai ENUM DB: 'individu' | 'kelompok' | 'tim_tugas'.
     * @param array  $items       Array of ID sasaran (id_pkl / id_kelompok / id_tim).
     *
     * Dipakai storeTugas() di Controller setelah insert tugas berhasil.
     */
    public function insertBulk(int $idTugas, string $targetTipe, array $items): void
    {
        $now   = date('Y-m-d H:i:s');
        $batch = [];

        foreach ($items as $itemId) {
            $row = [
                'id_tugas'    => $idTugas,
                'target_tipe' => $targetTipe,
                'id_pkl'      => null,
                'id_kelompok' => null,
                'id_tim'      => null,
                'created_at'  => $now,
                'updated_at'  => $now,
            ];

            // Isi kolom FK yang sesuai berdasarkan tipe sasaran
            match ($targetTipe) {
                'individu'  => $row['id_pkl']      = (int) $itemId,
                'kelompok'  => $row['id_kelompok'] = (int) $itemId,
                'tim_tugas' => $row['id_tim']      = (int) $itemId,
                default     => null,
            };

            $batch[] = $row;
        }

        if (! empty($batch)) {
            $this->db->table('tugas_sasaran')->insertBatch($batch);
        }
    }

    /**
     * Hapus semua sasaran milik sebuah tugas.
     * Berguna jika ingin replace sasaran saat update tugas.
     */
    public function deleteByTugas(int $idTugas): void
    {
        $this->where('id_tugas', $idTugas)->delete();
    }

    /**
     * Daftar sasaran satu tugas beserta nama & keterangan lengkapnya.
     * Dipakai getSasaranByTugas() di controller.
     *
     * Keys: id_sasaran, target_tipe, nama_sasaran, keterangan
     */
    public function getFormattedByTugas(int $idTugas): array
    {
        return $this->db->query("
            SELECT
                ts.id_sasaran,
                ts.target_tipe,
                CASE ts.target_tipe
                    WHEN 'individu'  THEN p.nama_lengkap
                    WHEN 'kelompok'  THEN k.nama_kelompok
                    WHEN 'tim_tugas' THEN t.nama_tim
                END AS nama_sasaran,
                CASE ts.target_tipe
                    WHEN 'individu'  THEN COALESCE(kp.nama_kelompok, 'Mandiri')
                    WHEN 'kelompok'  THEN CONCAT(
                        COALESCE(ca.jumlah, 0), ' anggota · ', COALESCE(i.nama_instansi, '-')
                    )
                    WHEN 'tim_tugas' THEN CONCAT(COALESCE(ct.jumlah, 0), ' anggota tim')
                END AS keterangan
            FROM tugas_sasaran ts
            LEFT JOIN pkl          p   ON p.id_pkl       = ts.id_pkl
            LEFT JOIN kelompok_pkl kp  ON kp.id_kelompok = p.id_kelompok
            LEFT JOIN kelompok_pkl k   ON k.id_kelompok  = ts.id_kelompok
            LEFT JOIN instansi     i   ON i.id_instansi  = k.id_instansi
            LEFT JOIN tim_tugas    t   ON t.id_tim        = ts.id_tim
            LEFT JOIN (
                SELECT id_kelompok, COUNT(id_pkl) AS jumlah
                FROM pkl GROUP BY id_kelompok
            ) ca  ON ca.id_kelompok = ts.id_kelompok
            LEFT JOIN (
                SELECT id_tim, COUNT(id_pkl) AS jumlah
                FROM anggota_tim_tugas GROUP BY id_tim
            ) ct  ON ct.id_tim = ts.id_tim
            WHERE ts.id_tugas = ?
            ORDER BY ts.target_tipe, nama_sasaran
        ", [$idTugas])->getResultArray();
    }
}
