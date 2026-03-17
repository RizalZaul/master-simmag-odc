<?php

namespace App\Models;

use CodeIgniter\Model;

class TimTugasModel extends Model
{
    protected $table            = 'tim_tugas';             // ← FIX: was 'timtugas'
    protected $primaryKey       = 'id_tim';                // ← FIX: was 'id'
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

    // ── Validation ─────────────────────────────────────────────────
    protected $validationRules      = [];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // ── Custom Methods ──────────────────────────────────────────────

    /**
     * Semua tim beserta jumlah anggota, preview nama, dan jumlah tugas.
     * Dipakai getTimList() di controller.
     *
     * Keys: id, nama, jumlah, anggota_preview, tgl_dibuat, dipakai
     */
    public function getFormattedList(): array
    {
        $rows = $this->db->query("
            SELECT
                t.id_tim,
                t.nama_tim,
                t.deskripsi,
                DATE_FORMAT(t.created_at, '%d-%m-%Y') AS tgl_dibuat,
                COUNT(DISTINCT at.id_pkl)              AS jumlah,
                GROUP_CONCAT(
                    p.nama_lengkap
                    ORDER BY p.nama_lengkap
                    SEPARATOR ', '
                )                                      AS anggota_str,
                COUNT(DISTINCT ts.id_tugas)            AS dipakai
            FROM tim_tugas t
            LEFT JOIN anggota_tim_tugas at ON at.id_tim = t.id_tim
            LEFT JOIN pkl p               ON p.id_pkl   = at.id_pkl
            LEFT JOIN tugas_sasaran ts    ON ts.id_tim  = t.id_tim
            GROUP BY t.id_tim
            ORDER BY t.id_tim ASC
        ")->getResultArray();

        return array_map(function ($row) {
            $anggotaArr     = $row['anggota_str'] ? explode(', ', $row['anggota_str']) : [];
            $anggotaPreview = implode(', ', array_slice($anggotaArr, 0, 3));
            if (count($anggotaArr) > 3) {
                $anggotaPreview .= '...';
            }

            return [
                'id'              => $row['id_tim'],
                'nama'            => $row['nama_tim'],
                'jumlah'          => (int) $row['jumlah'],
                'anggota_preview' => $anggotaPreview,
                'tgl_dibuat'      => $row['tgl_dibuat'] ?? '-',
                'dipakai'         => (int) $row['dipakai'],
            ];
        }, $rows);
    }

    /**
     * Detail satu tim beserta daftar anggotanya.
     * Dipakai getTim() di controller.
     *
     * Keys: id_tim, nama_tim, deskripsi, tgl_dibuat, anggota[]
     */
    public function getFormattedDetail(int $id): ?array
    {
        $row = $this->db->query("
            SELECT
                t.id_tim, t.nama_tim, t.deskripsi,
                DATE_FORMAT(t.created_at, '%d-%m-%Y') AS tgl_dibuat
            FROM tim_tugas t
            WHERE t.id_tim = ?
        ", [$id])->getRowArray();

        if (! $row) return null;

        $row['anggota'] = $this->db->query("
            SELECT p.id_pkl, p.nama_lengkap
            FROM anggota_tim_tugas at
            JOIN pkl p ON p.id_pkl = at.id_pkl
            WHERE at.id_tim = ?
            ORDER BY p.nama_lengkap
        ", [$id])->getResultArray();

        return $row;
    }

    /**
     * Buat tim baru + insert anggota dalam satu transaksi.
     * Return: array data tim baru siap untuk response API,
     *         atau null jika transaksi gagal.
     */
    public function storeWithAnggota(string $namaTim, ?string $deskripsi, array $anggotaIds): ?array
    {
        $db  = $this->db;
        $now = date('Y-m-d H:i:s');

        // BUG-03 FIX: Ganti transStart/transComplete (pola lama) ke transBegin/transCommit/transRollback.
        // Pola lama tidak men-catch PHP Exception: jika insertBatch melempar exception,
        // baris tim_tugas sudah masuk DB tanpa anggota, dan rollback tidak terjadi.
        try {
            $db->transBegin();

            $db->table('tim_tugas')->insert([
                'nama_tim'   => $namaTim,
                'deskripsi'  => $deskripsi,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $idTim = $db->insertID();

            $batch = array_map(fn($idPkl) => [
                'id_tim'     => $idTim,
                'id_pkl'     => (int) $idPkl,
                'created_at' => $now,
                'updated_at' => $now,
            ], $anggotaIds);

            $db->table('anggota_tim_tugas')->insertBatch($batch);
            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'TimTugasModel::storeWithAnggota gagal: ' . $e->getMessage());
            return null;
        }

        // Preview nama anggota untuk response
        $namaRows = $db->query("
            SELECT p.nama_lengkap FROM anggota_tim_tugas at
            JOIN pkl p ON p.id_pkl = at.id_pkl
            WHERE at.id_tim = ?
            ORDER BY p.nama_lengkap
        ", [$idTim])->getResultArray();

        $namaList       = array_column($namaRows, 'nama_lengkap');
        $anggotaPreview = implode(', ', array_slice($namaList, 0, 3))
            . (count($namaList) > 3 ? '...' : '');

        return [
            'id'              => $idTim,
            'nama'            => $namaTim,
            'jumlah'          => count($anggotaIds),
            'anggota_preview' => $anggotaPreview,
            'tgl_dibuat'      => date('d-m-Y'),
            'dipakai'         => 0,
        ];
    }

    /**
     * Update nama/deskripsi tim. Return false jika tim tidak ditemukan.
     */
    public function updateTim(int $id, string $namaTim, ?string $deskripsi): bool
    {
        $tim = $this->db->table('tim_tugas')->where('id_tim', $id)->get()->getRowArray();
        if (! $tim) return false;

        $this->db->table('tim_tugas')->where('id_tim', $id)->update([
            'nama_tim'   => $namaTim,
            'deskripsi'  => $deskripsi ?? $tim['deskripsi'],
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return true;
    }

    /**
     * Hapus tim beserta anggotanya, dengan cek apakah sedang dipakai tugas.
     *
     * Return:
     *   ['ok' => true]                     — berhasil dihapus
     *   ['ok' => false, 'not_found' => true]
     *   ['ok' => false, 'dipakai' => int]  — jumlah tugas yang memakai tim ini
     */
    public function deleteWithCheck(int $id): array
    {
        $db  = $this->db;
        $tim = $db->table('tim_tugas')->where('id_tim', $id)->get()->getRowArray();

        if (! $tim) return ['ok' => false, 'not_found' => true];

        $dipakai = $db->table('tugas_sasaran')->where('id_tim', $id)->countAllResults();
        if ($dipakai > 0) return ['ok' => false, 'dipakai' => $dipakai];

        $db->table('anggota_tim_tugas')->where('id_tim', $id)->delete();
        $db->table('tim_tugas')->where('id_tim', $id)->delete();

        return ['ok' => true];
    }
}
