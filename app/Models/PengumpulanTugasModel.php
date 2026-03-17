<?php

namespace App\Models;

use CodeIgniter\Model;

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

    // ──────────────────────────────────────────────────────────────
    // Status diturunkan dari item_tugas.status_item (tidak disimpan):
    //   ada revisi        → Revisi
    //   ada dikirim       → Belum Diperiksa
    //   semua diterima    → Done
    //   tidak ada / semua belum_dikirim → Belum Dikirim
    // ──────────────────────────────────────────────────────────────

    /** Fragment SQL computed status — dipakai di SELECT getFormattedList* dan TugasModel. */
    public const SQL_STATUS = "
        CASE
            WHEN COUNT(it.id_item) = 0
                THEN 'Belum Dikirim'
            WHEN SUM(CASE WHEN it.status_item = 'revisi'   THEN 1 ELSE 0 END) > 0
                THEN 'Revisi'
            WHEN SUM(CASE WHEN it.status_item = 'dikirim'  THEN 1 ELSE 0 END) > 0
                THEN 'Belum Diperiksa'
            WHEN SUM(CASE WHEN it.status_item = 'diterima' THEN 1 ELSE 0 END) = COUNT(it.id_item)
                 AND COUNT(it.id_item) > 0
                THEN 'Done'
            ELSE 'Belum Dikirim'
        END
    ";

    /**
     * Ambil record pengumpulan_tugas milik PKL ini + computed status.
     *
     * [FIX BUG 3] Sebelumnya hanya mencari by id_pkl. Sekarang mencari
     * secara bertahap: individu (id_pkl) → kelompok (id_kelompok) → tim (id_tim).
     * Ini menyelesaikan bug "Tugas ini tidak ditugaskan kepada Anda" pada
     * tugas kelompok/tim karena pengumpulan_tugas menyimpan id_kelompok/id_tim,
     * bukan id_pkl.
     *
     * @param int $idTugas     id dari tabel tugas
     * @param int $idPkl       id dari tabel pkl (session id_pkl)
     * @param int $idKelompok  id kelompok PKL (session id_kelompok), 0 jika tidak ada
     */
    public function getWithStatusForPkl(int $idTugas, int $idPkl, int $idKelompok = 0): ?array
    {
        // ── 1. Coba individu (id_pkl) ─────────────────────────────────
        $result = $this->db->query("
            SELECT
                pt.*,
                " . self::SQL_STATUS . " AS status
            FROM pengumpulan_tugas pt
            LEFT JOIN item_tugas it ON it.id_pengumpulan_tgs = pt.id_pengumpulan_tgs
            WHERE pt.id_tugas = ? AND pt.id_pkl = ?
            GROUP BY pt.id_pengumpulan_tgs
        ", [$idTugas, $idPkl])->getRowArray() ?: null;

        if ($result) return $result;

        // ── 2. Coba kelompok (id_kelompok) ───────────────────────────
        if ($idKelompok) {
            $result = $this->db->query("
                SELECT
                    pt.*,
                    " . self::SQL_STATUS . " AS status
                FROM pengumpulan_tugas pt
                LEFT JOIN item_tugas it ON it.id_pengumpulan_tgs = pt.id_pengumpulan_tgs
                WHERE pt.id_tugas = ? AND pt.id_kelompok = ?
                GROUP BY pt.id_pengumpulan_tgs
            ", [$idTugas, $idKelompok])->getRowArray() ?: null;

            if ($result) return $result;
        }

        // ── 3. Coba tim tugas (id_tim) ────────────────────────────────
        $idTimList = array_column(
            $this->db->query(
                'SELECT id_tim FROM anggota_tim_tugas WHERE id_pkl = ?',
                [$idPkl]
            )->getResultArray(),
            'id_tim'
        );

        if (! empty($idTimList)) {
            $ph     = implode(',', array_fill(0, count($idTimList), '?'));
            $result = $this->db->query("
                SELECT
                    pt.*,
                    " . self::SQL_STATUS . " AS status
                FROM pengumpulan_tugas pt
                LEFT JOIN item_tugas it ON it.id_pengumpulan_tgs = pt.id_pengumpulan_tgs
                WHERE pt.id_tugas = ? AND pt.id_tim IN ($ph)
                GROUP BY pt.id_pengumpulan_tgs
                LIMIT 1
            ", array_merge([$idTugas], $idTimList))->getRowArray() ?: null;

            if ($result) return $result;
        }

        return null;
    }

    /**
     * Map status_item ENUM DB → label tampilan (untuk item list di detail).
     * belum_dikirim → Belum Dikirim
     * dikirim       → Submit
     * revisi        → Revisi
     * diterima      → Done
     */
    public static function mapItemStatus(string $statusDb): string
    {
        return match ($statusDb) {
            'dikirim'  => 'Submit',
            'revisi'   => 'Revisi',
            'diterima' => 'Done',
            default    => 'Belum Dikirim',
        };
    }

    // ==========================================
    // AUTO-CREATE — dipanggil dari storeTugas()
    // ==========================================

    /**
     * Auto-create record pengumpulan_tugas saat tugas dikirim.
     *
     * Logika berdasarkan kombinasi mode_pengumpulan × target_tipe:
     *
     *  mode=individu + target=individu  → 1 record per PKL           (id_pkl)
     *  mode=individu + target=kelompok  → expand: 1 record per anggota kelompok (id_pkl)
     *  mode=individu + target=tim_tugas → expand: 1 record per anggota tim      (id_pkl)
     *  mode=kelompok + target=kelompok  → 1 record per kelompok      (id_kelompok)
     *  mode=kelompok + target=tim_tugas → 1 record per tim           (id_tim)
     *  mode=kelompok + target=individu  → TIDAK BOLEH (diblokir di storeTugas)
     *
     * @param int    $idTugas          ID tugas yang baru dibuat
     * @param string $targetTipe       'individu' | 'kelompok' | 'tim_tugas'
     * @param array  $sasaranItems     Array of ID sasaran
     * @param string $modePengumpulan  'individu' | 'kelompok' (dari kategori_tugas)
     */
    public function autoCreateForTugas(
        int    $idTugas,
        string $targetTipe,
        array  $sasaranItems,
        string $modePengumpulan = 'individu'
    ): void {
        $now   = date('Y-m-d H:i:s');
        $batch = [];

        // ── CASE 1: mode individu + target kelompok ──────────────────
        // Expand: setiap anggota kelompok dapat tugas sendiri-sendiri
        if ($modePengumpulan === 'individu' && $targetTipe === 'kelompok') {
            foreach ($sasaranItems as $idKelompok) {
                $anggota = $this->db->query(
                    'SELECT id_pkl FROM pkl WHERE id_kelompok = ?',
                    [(int) $idKelompok]
                )->getResultArray();

                foreach ($anggota as $pkls) {
                    $batch[] = [
                        'id_tugas'        => $idTugas,
                        'id_pkl'          => (int) $pkls['id_pkl'],
                        'id_kelompok'     => null,
                        'id_tim'          => null,
                        'tgl_pengumpulan' => null,
                        'created_at'      => $now,
                        'updated_at'      => $now,
                    ];
                }
            }

            // ── CASE 2: mode individu + target tim_tugas ─────────────────
            // Expand: setiap anggota tim dapat tugas sendiri-sendiri
        } elseif ($modePengumpulan === 'individu' && $targetTipe === 'tim_tugas') {
            foreach ($sasaranItems as $idTim) {
                $anggota = $this->db->query(
                    'SELECT id_pkl FROM anggota_tim_tugas WHERE id_tim = ?',
                    [(int) $idTim]
                )->getResultArray();

                foreach ($anggota as $pkls) {
                    $batch[] = [
                        'id_tugas'        => $idTugas,
                        'id_pkl'          => (int) $pkls['id_pkl'],
                        'id_kelompok'     => null,
                        'id_tim'          => null,
                        'tgl_pengumpulan' => null,
                        'created_at'      => $now,
                        'updated_at'      => $now,
                    ];
                }
            }

            // ── CASE 3: semua kombinasi lain (tanpa expand) ───────────────
            // mode=individu+individu → id_pkl
            // mode=kelompok+kelompok → id_kelompok
            // mode=kelompok+tim_tugas → id_tim
        } else {
            foreach ($sasaranItems as $itemId) {
                $row = [
                    'id_tugas'        => $idTugas,
                    'id_pkl'          => null,
                    'id_kelompok'     => null,
                    'id_tim'          => null,
                    'tgl_pengumpulan' => null,
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ];

                match ($targetTipe) {
                    'individu'  => $row['id_pkl']      = (int) $itemId,
                    'kelompok'  => $row['id_kelompok'] = (int) $itemId,
                    'tim_tugas' => $row['id_tim']      = (int) $itemId,
                    default     => null,
                };

                $batch[] = $row;
            }
        }

        if (! empty($batch)) {
            $this->db->table('pengumpulan_tugas')->insertBatch($batch);
        }
    }

    // ==========================================
    // LIST QUERIES — untuk loadPengumpulan*
    // ==========================================

    /**
     * Daftar pengumpulan Mandiri.
     * Keys: id, type, id_tugas, nama_tugas, kategori_tugas,
     *       nama_pengirim, waktu_pengumpulan, deadline, status
     */
    public function getFormattedListMandiri(): array
    {
        return $this->db->query("
            SELECT
                pt.id_pengumpulan_tgs                              AS id,
                'mandiri'                                          AS type,
                pt.id_tugas,
                t.nama_tugas,
                kt.nama_kat_tugas                                  AS kategori_tugas,
                p.nama_lengkap                                     AS nama_pengirim,
                DATE_FORMAT(pt.tgl_pengumpulan, '%d-%m-%Y %H:%i') AS waktu_pengumpulan,
                DATE_FORMAT(t.deadline, '%H:%i · %d-%m-%Y')       AS deadline,
                " . self::SQL_STATUS . "                           AS status
            FROM pengumpulan_tugas pt
            JOIN tugas          t   ON t.id_tugas      = pt.id_tugas
            JOIN kategori_tugas kt  ON kt.id_kat_tugas = t.id_kat_tugas
            JOIN pkl            p   ON p.id_pkl        = pt.id_pkl
            LEFT JOIN item_tugas it ON it.id_pengumpulan_tgs = pt.id_pengumpulan_tgs
            WHERE pt.id_pkl      IS NOT NULL
              AND pt.id_kelompok IS NULL
              AND pt.id_tim      IS NULL
            GROUP BY pt.id_pengumpulan_tgs
            ORDER BY t.deadline ASC, p.nama_lengkap ASC
        ")->getResultArray();
    }

    /**
     * Daftar pengumpulan Kelompok PKL.
     * Keys: id, type, id_tugas, nama_tugas, kategori_tugas,
     *       nama_kelompok, waktu_pengumpulan, deadline, status
     */
    public function getFormattedListKelompok(): array
    {
        return $this->db->query("
            SELECT
                pt.id_pengumpulan_tgs                              AS id,
                'kelompok'                                         AS type,
                pt.id_tugas,
                t.nama_tugas,
                kt.nama_kat_tugas                                  AS kategori_tugas,
                k.nama_kelompok,
                DATE_FORMAT(pt.tgl_pengumpulan, '%d-%m-%Y %H:%i') AS waktu_pengumpulan,
                DATE_FORMAT(t.deadline, '%H:%i · %d-%m-%Y')       AS deadline,
                " . self::SQL_STATUS . "                           AS status
            FROM pengumpulan_tugas pt
            JOIN tugas          t   ON t.id_tugas      = pt.id_tugas
            JOIN kategori_tugas kt  ON kt.id_kat_tugas = t.id_kat_tugas
            JOIN kelompok_pkl   k   ON k.id_kelompok   = pt.id_kelompok
            LEFT JOIN item_tugas it ON it.id_pengumpulan_tgs = pt.id_pengumpulan_tgs
            WHERE pt.id_kelompok IS NOT NULL
              AND pt.id_tim      IS NULL
            GROUP BY pt.id_pengumpulan_tgs
            ORDER BY t.deadline ASC, k.nama_kelompok ASC
        ")->getResultArray();
    }

    /**
     * Daftar pengumpulan Tim Tugas.
     * Keys: id, type, id_tugas, nama_tugas, kategori_tugas,
     *       nama_tim, waktu_pengumpulan, deadline, status
     */
    public function getFormattedListTim(): array
    {
        return $this->db->query("
            SELECT
                pt.id_pengumpulan_tgs                              AS id,
                'tim'                                              AS type,
                pt.id_tugas,
                t.nama_tugas,
                kt.nama_kat_tugas                                  AS kategori_tugas,
                tt.nama_tim,
                DATE_FORMAT(pt.tgl_pengumpulan, '%d-%m-%Y %H:%i') AS waktu_pengumpulan,
                DATE_FORMAT(t.deadline, '%H:%i · %d-%m-%Y')       AS deadline,
                " . self::SQL_STATUS . "                           AS status
            FROM pengumpulan_tugas pt
            JOIN tugas          t   ON t.id_tugas      = pt.id_tugas
            JOIN kategori_tugas kt  ON kt.id_kat_tugas = t.id_kat_tugas
            JOIN tim_tugas      tt  ON tt.id_tim       = pt.id_tim
            LEFT JOIN item_tugas it ON it.id_pengumpulan_tgs = pt.id_pengumpulan_tgs
            WHERE pt.id_tim IS NOT NULL
            GROUP BY pt.id_pengumpulan_tgs
            ORDER BY t.deadline ASC, tt.nama_tim ASC
        ")->getResultArray();
    }

    // ==========================================
    // DETAIL QUERY — untuk detailPengumpulan()
    // ==========================================

    /**
     * Detail lengkap satu pengumpulan by ID.
     * Return array siap pakai untuk view pengumpulan_detail.php.
     *
     * Keys: id, type, id_tugas, nama_tugas, kategori_tugas,
     *       mode_pengumpulan, deskripsi_tugas,
     *       nama_pengirim | nama_kelompok | nama_tim,
     *       waktu_pengumpulan, deadline, status,
     *       anggota[]   (hanya kelompok/tim),
     *       items[]     [id, tipe, path, status, komentar]
     */
    public function getFormattedDetail(int $id): ?array
    {
        $db = $this->db;

        // ── 1. Data pengumpulan + tugas ──────────────────────────────
        $pt = $db->query("
            SELECT
                pt.id_pengumpulan_tgs,
                pt.id_tugas,
                pt.id_pkl,
                pt.id_kelompok,
                pt.id_tim,
                t.nama_tugas,
                t.deskripsi                                                AS deskripsi_tugas,
                kt.nama_kat_tugas                                          AS kategori_tugas,
                kt.mode_pengumpulan,
                DATE_FORMAT(t.deadline,        '%H:%i · %d-%m-%Y')        AS deadline,
                DATE_FORMAT(pt.tgl_pengumpulan,'%d-%m-%Y %H:%i')          AS waktu_pengumpulan
            FROM pengumpulan_tugas pt
            JOIN tugas          t  ON t.id_tugas      = pt.id_tugas
            JOIN kategori_tugas kt ON kt.id_kat_tugas = t.id_kat_tugas
            WHERE pt.id_pengumpulan_tgs = ?
        ", [$id])->getRowArray();

        if (! $pt) return null;

        // ── 2. Tentukan type + identitas + anggota ───────────────────
        if ($pt['id_pkl'] !== null && $pt['id_kelompok'] === null && $pt['id_tim'] === null) {
            $pt['type'] = 'mandiri';
            $row = $db->query(
                'SELECT nama_lengkap FROM pkl WHERE id_pkl = ?',
                [$pt['id_pkl']]
            )->getRowArray();
            $pt['nama_pengirim'] = $row['nama_lengkap'] ?? '-';
        } elseif ($pt['id_kelompok'] !== null) {
            $pt['type'] = 'kelompok';
            $row = $db->query(
                'SELECT nama_kelompok FROM kelompok_pkl WHERE id_kelompok = ?',
                [$pt['id_kelompok']]
            )->getRowArray();
            $pt['nama_kelompok'] = $row['nama_kelompok'] ?? '-';

            $anggota = $db->query("
                SELECT nama_lengkap FROM pkl
                WHERE id_kelompok = ?
                ORDER BY nama_lengkap ASC
            ", [$pt['id_kelompok']])->getResultArray();
            $pt['anggota'] = array_column($anggota, 'nama_lengkap');
        } else {
            $pt['type'] = 'tim';
            $row = $db->query(
                'SELECT nama_tim FROM tim_tugas WHERE id_tim = ?',
                [$pt['id_tim']]
            )->getRowArray();
            $pt['nama_tim'] = $row['nama_tim'] ?? '-';

            $anggota = $db->query("
                SELECT p.nama_lengkap
                FROM anggota_tim_tugas att
                JOIN pkl p ON p.id_pkl = att.id_pkl
                WHERE att.id_tim = ?
                ORDER BY p.nama_lengkap ASC
            ", [$pt['id_tim']])->getResultArray();
            $pt['anggota'] = array_column($anggota, 'nama_lengkap');
        }

        // ── 3. Items dari item_tugas ─────────────────────────────────
        $itemRows = $db->query("
            SELECT
                it.id_item    AS id,
                it.tipe_item  AS tipe,
                it.data_item  AS path,
                it.status_item,
                it.komentar
            FROM item_tugas it
            WHERE it.id_pengumpulan_tgs = ?
            ORDER BY it.id_item ASC
        ", [$id])->getResultArray();

        $pt['items'] = array_map(function ($item) {
            $item['status'] = self::mapItemStatus($item['status_item']);
            unset($item['status_item']);
            return $item;
        }, $itemRows);

        // ── 4. Computed status keseluruhan ───────────────────────────
        $statusRow = $db->query("
            SELECT " . self::SQL_STATUS . " AS status
            FROM item_tugas it
            WHERE it.id_pengumpulan_tgs = ?
        ", [$id])->getRowArray();
        $pt['status'] = $statusRow['status'] ?? 'Belum Dikirim';

        // ── 5. Rename PK + bersihkan kolom internal ──────────────────
        $pt['id'] = $pt['id_pengumpulan_tgs'];
        unset($pt['id_pengumpulan_tgs'], $pt['id_pkl'], $pt['id_kelompok'], $pt['id_tim']);

        return $pt;
    }
}
