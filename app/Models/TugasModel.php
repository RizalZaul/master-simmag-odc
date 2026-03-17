<?php

namespace App\Models;

use CodeIgniter\Model;

class TugasModel extends Model
{
    protected $table            = 'tugas';
    protected $primaryKey       = 'id_tugas';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';

    protected $allowedFields = [
        'id_user',
        'id_kat_tugas',
        'nama_tugas',
        'deskripsi',
        'target_jumlah',
        'deadline',
    ];

    // ── Custom Methods ──────────────────────────────────────────────

    /**
     * Semua tugas — format siap pakai untuk view & API listing.
     * Menyertakan nama kategori, mode pengumpulan, jumlah sasaran,
     * dan status deadline (aktif / lewat).
     * Dipakai loadTugas() dan getTugasList().
     */
    public function getFormattedList(): array
    {
        $rows = $this->db->table('tugas')
            ->select('tugas.id_tugas                              AS id,
                      tugas.id_tugas,
                      tugas.nama_tugas,
                      tugas.deskripsi,
                      tugas.target_jumlah,
                      tugas.deadline,
                      DATE_FORMAT(tugas.deadline, \'%d-%m-%Y\')   AS deadline_fmt,
                      tugas.created_at,
                      DATE_FORMAT(tugas.created_at, \'%d-%m-%Y\') AS tgl_dibuat,
                      kategori_tugas.id_kat_tugas,
                      kategori_tugas.nama_kat_tugas                AS nama_kategori,
                      kategori_tugas.nama_kat_tugas                AS kategori_tugas,
                      kategori_tugas.mode_pengumpulan,
                      COUNT(DISTINCT tugas_sasaran.id_sasaran)     AS jumlah_sasaran,
                      admin.nama_lengkap                           AS editor')
            ->join('kategori_tugas', 'kategori_tugas.id_kat_tugas = tugas.id_kat_tugas')
            ->join('tugas_sasaran',  'tugas_sasaran.id_tugas = tugas.id_tugas', 'left')
            ->join('admin',          'admin.id_user = tugas.id_user', 'left')
            ->groupBy('tugas.id_tugas')
            ->orderBy('tugas.deadline', 'ASC')
            ->get()->getResultArray();

        $now = date('Y-m-d H:i:s');
        foreach ($rows as &$row) {
            $row['is_lewat_deadline'] = $row['deadline'] && $row['deadline'] < $now;
        }
        unset($row);

        return $rows;
    }

    /**
     * Dashboard Opsi C — 5 tugas aktif terdekat deadline yang belum 100% selesai.
     *
     * Kriteria:
     *   - deadline >= hari ini (expired tidak muncul)
     *   - belum semua sasaran selesai (ada pengumpulan yang belum tgl_pengumpulan / belum diterima semua)
     *
     * Keys return:
     *   id, judul, kategori, deadline_label, sisa_hari,
     *   priority (high/medium/low), total_sasaran, sudah_kumpul, persen
     */
    public function getDashboardList(): array
    {
        $today = date('Y-m-d');

        $rows = $this->db->query("
            SELECT
                t.id_tugas                                                AS id,
                t.nama_tugas                                              AS judul,
                kt.nama_kat_tugas                                         AS kategori,
                DATE_FORMAT(t.deadline, '%d %b %Y')                       AS deadline_label,
                DATEDIFF(DATE(t.deadline), CURDATE())                     AS sisa_hari,
                COUNT(pt.id_pengumpulan_tgs)                              AS total_sasaran,
                SUM(CASE WHEN pt.tgl_pengumpulan IS NOT NULL THEN 1 ELSE 0 END)
                                                                          AS sudah_kumpul
            FROM tugas t
            JOIN kategori_tugas kt  ON kt.id_kat_tugas = t.id_kat_tugas
            LEFT JOIN pengumpulan_tugas pt ON pt.id_tugas = t.id_tugas
            WHERE DATE(t.deadline) >= ?
            GROUP BY t.id_tugas
            HAVING total_sasaran = 0
                OR sudah_kumpul < total_sasaran
            ORDER BY t.deadline ASC
            LIMIT 5
        ", [$today])->getResultArray();

        foreach ($rows as &$row) {
            $sisa = (int) $row['sisa_hari'];

            // Priority berdasarkan sisa hari
            $row['priority'] = match (true) {
                $sisa <= 3 => 'high',
                $sisa <= 7 => 'medium',
                default    => 'low',
            };

            // Persentase progress
            $total = (int) $row['total_sasaran'];
            $kumpul = (int) $row['sudah_kumpul'];
            $row['persen'] = $total > 0 ? (int) round(($kumpul / $total) * 100) : 0;

            // Label sisa hari
            $row['sisa_label'] = match (true) {
                $sisa === 0 => 'Hari ini',
                $sisa === 1 => 'Besok',
                $sisa > 1   => $sisa . ' hari lagi',
                default     => 'Terlambat', // tidak akan muncul karena sudah difilter
            };
        }
        unset($row);

        return $rows;
    }

    /**
     * Kumpulkan id_tugas yang di-assign ke PKL ini.
     * Mencakup 3 cara assign: individu, kelompok, dan tim_tugas.
     *
     * ARCH-03 FIX: Dijadikan public agar bisa dipakai langsung dari
     * PklTugasController::index() — menghilangkan duplikasi logika.
     * Sebelumnya private, sehingga controller menduplikasi kode yang sama.
     *
     * Dipakai getDashboardStatsByPkl(), getDashboardListByPkl(), dan PklTugasController.
     */
    public function getIdTugasByPkl(int $idPkl, int $idKelompok): array
    {
        $db = $this->db;

        // Ambil semua id_tim PKL ini
        $idTimList = array_column(
            $db->query('SELECT id_tim FROM anggota_tim_tugas WHERE id_pkl = ?', [$idPkl])
                ->getResultArray(),
            'id_tim'
        );

        $conditions = [];
        $bindings   = [];

        $conditions[] = "(target_tipe = 'individu' AND id_pkl = ?)";
        $bindings[]   = $idPkl;

        if ($idKelompok) {
            $conditions[] = "(target_tipe = 'kelompok' AND id_kelompok = ?)";
            $bindings[]   = $idKelompok;
        }

        if (! empty($idTimList)) {
            $ph           = implode(',', array_fill(0, count($idTimList), '?'));
            $conditions[] = "(target_tipe = 'tim_tugas' AND id_tim IN ($ph))";
            $bindings     = array_merge($bindings, $idTimList);
        }

        $where = implode(' OR ', $conditions);
        return array_column(
            $db->query("SELECT DISTINCT id_tugas FROM tugas_sasaran WHERE $where", $bindings)
                ->getResultArray(),
            'id_tugas'
        );
    }

    /**
     * Stat cards dashboard PKL.
     * Return: ['total' => int, 'selesai' => int, 'pending' => int, 'id_list' => array]
     *
     * selesai = jumlah id_tugas unik yang sudah ada tgl_pengumpulan (bukan NULL)
     *           dari pengumpulan_tugas milik PKL ini.
     */
    public function getDashboardStatsByPkl(int $idPkl, int $idKelompok): array
    {
        $idList = $this->getIdTugasByPkl($idPkl, $idKelompok);
        $total  = count($idList);
        $selesai = 0;

        if (! empty($idList)) {
            $ph  = implode(',', array_fill(0, count($idList), '?'));
            $row = $this->db->query(
                "SELECT COUNT(DISTINCT id_tugas) AS jml
                 FROM pengumpulan_tugas
                 WHERE id_pkl = ? AND tgl_pengumpulan IS NOT NULL AND id_tugas IN ($ph)",
                array_merge([$idPkl], $idList)
            )->getRow();
            $selesai = (int) ($row->jml ?? 0);
        }

        return [
            'total'   => $total,
            'selesai' => $selesai,
            'pending' => max(0, $total - $selesai),
            'id_list' => $idList,
        ];
    }

    /**
     * 5 tugas terdekat deadline untuk dashboard PKL.
     * Hanya tugas deadline >= hari ini yang belum expired.
     *
     * Keys: id, judul, deadline, kategori, mode, priority
     */
    public function getDashboardListByPkl(int $idPkl, int $idKelompok, int $limit = 5): array
    {
        $idList = $this->getIdTugasByPkl($idPkl, $idKelompok);
        if (empty($idList)) return [];

        $today = date('Y-m-d');

        $rows = $this->db->table('tugas t')
            ->select([
                't.id_tugas',
                't.nama_tugas',
                't.deadline',
                'kt.nama_kat_tugas',
                'kt.mode_pengumpulan',
            ])
            ->join('kategori_tugas kt', 'kt.id_kat_tugas = t.id_kat_tugas', 'left')
            ->whereIn('t.id_tugas', $idList)
            ->where('DATE(t.deadline) >=', $today)
            ->orderBy('t.deadline', 'ASC')
            ->limit($limit)
            ->get()->getResultArray();

        return array_map(function ($row) {
            $sisa = (int) (new \DateTime('today'))
                ->diff(new \DateTime($row['deadline'] ?? 'today'))
                ->format('%r%a');

            return [
                'id'       => $row['id_tugas'],
                'judul'    => $row['nama_tugas'],
                'deadline' => ! empty($row['deadline'])
                    ? date('d M Y', strtotime($row['deadline']))
                    : '-',
                'kategori' => $row['nama_kat_tugas'] ?? '-',
                'mode'     => match ($row['mode_pengumpulan'] ?? '') {
                    'individu' => 'Individu',
                    'kelompok' => 'Kelompok',
                    default    => '-',
                },
                'priority' => match (true) {
                    $sisa <= 3 => 'high',
                    $sisa <= 7 => 'medium',
                    default    => 'low',
                },
            ];
        }, $rows);
    }

    /**
     * Daftar tugas beserta status pengumpulan untuk satu PKL.
     *
     * ARCH-04 FIX: Memindahkan raw SQL + logika mapping yang sebelumnya
     * ada langsung di PklTugasController::index() ke dalam model ini.
     * Controller tinggal memanggil method ini.
     *
     * @param int   $idPkl       id dari tabel pkl (session id_pkl)
     * @param array $idTugasList Daftar id_tugas hasil getIdTugasByPkl()
     *
     * @return array  Siap pakai untuk view — key: id, nama, deskripsi,
     *                deadline, deadline_raw, kategori, mode, status,
     *                id_pengumpulan, sudah_dikumpulkan, is_lewat_deadline, priority
     */
    public function getTugasListForPkl(int $idPkl, int $idKelompok = 0, array $idTugasList = []): array
    {
        if (empty($idTugasList)) return [];

        $ph  = implode(',', array_fill(0, count($idTugasList), '?'));
        $now = date('Y-m-d H:i:s');

        // Kondisi JOIN untuk kelompok (aman: jika id_kelompok=0, kondisi false)
        $kelompokCond = $idKelompok
            ? "OR (pt.id_kelompok IS NOT NULL AND pt.id_kelompok = $idKelompok)"
            : '';

        $rows = $this->db->query("
            SELECT
                t.id_tugas,
                t.nama_tugas,
                t.deskripsi,
                t.deadline,
                t.target_jumlah,
                kt.nama_kat_tugas     AS kategori,
                kt.mode_pengumpulan,
                pt.id_pengumpulan_tgs,
                pt.tgl_pengumpulan,
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
                END AS status
            FROM tugas t
            JOIN kategori_tugas kt ON kt.id_kat_tugas = t.id_kat_tugas
            LEFT JOIN pengumpulan_tugas pt
                ON pt.id_tugas = t.id_tugas
                AND (
                    pt.id_pkl = ?
                    $kelompokCond
                    OR (pt.id_tim IS NOT NULL AND pt.id_tim IN (
                        SELECT id_tim FROM anggota_tim_tugas WHERE id_pkl = ?
                    ))
                )
            LEFT JOIN item_tugas it
                ON it.id_pengumpulan_tgs = pt.id_pengumpulan_tgs
            WHERE t.id_tugas IN ($ph)
            GROUP BY t.id_tugas, pt.id_pengumpulan_tgs
            ORDER BY t.deadline ASC
        ", array_merge([$idPkl, $idPkl], $idTugasList))->getResultArray();

        $result = [];
        foreach ($rows as $row) {
            $sisa = PHP_INT_MAX;
            if (! empty($row['deadline'])) {
                $sisa = (int) (new \DateTime('today'))
                    ->diff(new \DateTime($row['deadline']))
                    ->format('%r%a');
            }

            $result[] = [
                'id'                => $row['id_tugas'],
                'nama'              => $row['nama_tugas'],
                'deskripsi'         => $row['deskripsi'],
                'deadline'          => ! empty($row['deadline'])
                    ? date('d M Y', strtotime($row['deadline']))
                    : '-',
                'deadline_raw'      => $row['deadline'] ?? '',
                'kategori'          => $row['kategori'],
                'mode'              => $row['mode_pengumpulan'] === 'kelompok' ? 'Kelompok' : 'Individu',
                'status'            => $row['status'],
                'id_pengumpulan'    => $row['id_pengumpulan_tgs'],
                'sudah_dikumpulkan' => ! empty($row['tgl_pengumpulan']),
                'is_lewat_deadline' => ! empty($row['deadline']) && $row['deadline'] < $now,
                'priority'          => match (true) {
                    $sisa <= 3 => 'high',
                    $sisa <= 7 => 'medium',
                    default    => 'low',
                },
            ];
        }

        return $result;
    }

    /**
     * Detail lengkap satu tugas by ID.
     * Menyertakan nama kategori, mode pengumpulan, dan nama pembuat.
     * Dipakai detailTugas(), ubahTugas(), dan getTugas().
     */
    public function getFormattedDetail(int $id): ?array
    {
        $row = $this->db->table('tugas')
            ->select('tugas.id_tugas                              AS id,
                      tugas.id_tugas,
                      tugas.id_user,
                      tugas.nama_tugas,
                      tugas.deskripsi,
                      tugas.target_jumlah,
                      tugas.deadline,
                      DATE_FORMAT(tugas.deadline, \'%d-%m-%Y\')    AS deadline_fmt,
                      tugas.created_at,
                      DATE_FORMAT(tugas.created_at, \'%d-%m-%Y\')  AS tgl_dibuat,
                      DATE_FORMAT(tugas.updated_at, \'%d-%m-%Y\')  AS tgl_diubah,
                      kategori_tugas.id_kat_tugas,
                      kategori_tugas.nama_kat_tugas                 AS nama_kategori,
                      kategori_tugas.nama_kat_tugas                 AS kategori_tugas,
                      kategori_tugas.mode_pengumpulan,
                      admin.nama_lengkap                            AS editor,
                      admin.nama_lengkap                            AS dibuat_oleh')
            ->join('kategori_tugas', 'kategori_tugas.id_kat_tugas = tugas.id_kat_tugas')
            ->join('admin',          'admin.id_user = tugas.id_user', 'left')
            ->where('tugas.id_tugas', $id)
            ->get()->getRowArray();

        if (! $row) return null;

        $now = date('Y-m-d H:i:s');
        $row['is_lewat_deadline'] = $row['deadline'] && $row['deadline'] < $now;

        return $row;
    }
}
