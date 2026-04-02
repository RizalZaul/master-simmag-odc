<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * TugasModel
 *
 * Model untuk tabel `tugas`.
 *
 * Relasi penting:
 *   tugas       → tugas_sasaran   (target penerima: individu / kelompok / tim_tugas)
 *   tugas       → pengumpulan_tugas (hasil kumpul PKL)
 *   tugas_sasaran + anggota_tim_tugas → menentukan siapa saja penerima tugas
 *
 * Catatan query complex:
 *   Karena penerima tugas bisa dari 3 jalur berbeda (individu, kelompok, tim),
 *   method getDashboardAdmin() dan getDashboardPkl() menggunakan raw SQL
 *   dengan subquery untuk menghitung total_penerima dan sudah_kumpul secara akurat.
 */
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

    // ── Dashboard Admin ─────────────────────────────────────────────

    /**
     * Semua tugas untuk dashboard admin.
     *
     * Menampilkan:
     *   - Tugas aktif (deadline >= hari ini)
     *   - Tugas overdue (deadline < hari ini) yang belum semua penerima mengumpulkan
     *
     * total_penerima dihitung berdasarkan tugas_sasaran dengan mempertimbangkan
     * 3 jalur sasaran: individu (1 PKL), kelompok (semua PKL kelompok), tim (semua anggota tim).
     *
     * sudah_kumpul = jumlah PKL distinct yang sudah ada di pengumpulan_tugas.
     *
     * Diurut berdasarkan deadline ASC (deadline terdekat = paling mendesak).
     *
     * Return keys: id_tugas, nama_tugas, deadline, nama_kat_tugas,
     *              total_penerima, sudah_kumpul, menunggu_review, perlu_revisi
     */
    public function getDashboardAdmin(): array
    {
        $sql = "
            SELECT
                t.id_tugas,
                t.nama_tugas,
                t.deadline,
                kt.nama_kat_tugas,

                -- ── Total PKL penerima (semua jalur: individu / kelompok / tim) ──
                (
                    SELECT COUNT(DISTINCT p2.id_pkl)
                    FROM tugas_sasaran ts2
                    LEFT JOIN pkl p2 ON (
                        (ts2.target_tipe = 'individu'  AND p2.id_pkl      = ts2.id_pkl)
                        OR (ts2.target_tipe = 'kelompok'  AND p2.id_kelompok = ts2.id_kelompok)
                        OR (ts2.target_tipe = 'tim_tugas' AND p2.id_pkl IN (
                            SELECT att.id_pkl FROM anggota_tim_tugas att
                            WHERE att.id_tim = ts2.id_tim
                        ))
                    )
                    WHERE ts2.id_tugas = t.id_tugas
                ) AS total_penerima,

                -- ── PKL yang sudah submit (tgl_pengumpulan IS NOT NULL) ──
                -- Baris pre-populated saat tugas dibuat memiliki tgl_pengumpulan = NULL
                (
                    SELECT COUNT(DISTINCT pt.id_pkl)
                    FROM pengumpulan_tugas pt
                    WHERE pt.id_tugas        = t.id_tugas
                      AND pt.id_pkl          IS NOT NULL
                      AND pt.tgl_pengumpulan IS NOT NULL
                ) AS sudah_kumpul,

                -- ── PKL yang menunggu review admin (ada item berstatus 'dikirim') ──
                (
                    SELECT COUNT(DISTINCT pt2.id_pkl)
                    FROM pengumpulan_tugas pt2
                    JOIN item_tugas it2 ON it2.id_pengumpulan_tgs = pt2.id_pengumpulan_tgs
                    WHERE pt2.id_tugas        = t.id_tugas
                      AND pt2.tgl_pengumpulan IS NOT NULL
                      AND it2.status_item     = 'dikirim'
                ) AS menunggu_review,

                -- ── PKL yang perlu revisi (ada item berstatus 'revisi') ──
                (
                    SELECT COUNT(DISTINCT pt3.id_pkl)
                    FROM pengumpulan_tugas pt3
                    JOIN item_tugas it3 ON it3.id_pengumpulan_tgs = pt3.id_pengumpulan_tgs
                    WHERE pt3.id_tugas        = t.id_tugas
                      AND pt3.tgl_pengumpulan IS NOT NULL
                      AND it3.status_item     = 'revisi'
                ) AS perlu_revisi

            FROM tugas t
            LEFT JOIN kategori_tugas kt ON kt.id_kat_tugas = t.id_kat_tugas

            HAVING
                -- Tampil selama SALAH SATU dari kondisi ini terpenuhi:
                -- 1. Masih ada PKL yang belum mengumpulkan sama sekali
                (sudah_kumpul < total_penerima)
                -- 2. Ada PKL yang sudah kumpul tapi menunggu di-review admin
                OR (menunggu_review > 0)
                -- 3. Ada PKL yang diminta revisi oleh admin
                OR (perlu_revisi > 0)
                --
                -- Tugas HILANG dari dashboard hanya jika KETIGA kondisi = false:
                -- semua sudah kumpul AND tidak ada menunggu review AND tidak ada revisi
                -- (= semua item sudah berstatus 'diterima' atau tidak ada item sama sekali)

            ORDER BY t.deadline ASC
        ";

        try {
            return $this->db->query($sql)->getResultArray();
        } catch (\Exception $e) {
            log_message('warning', '[TugasModel::getDashboardAdmin] ' . $e->getMessage());
            return [];
        }
    }

    public function getAdminDetailById(int $idTugas): ?array
    {
        return $this->select('tugas.*, kategori_tugas.nama_kat_tugas, kategori_tugas.mode_pengumpulan, users.username AS editor_username')
            ->join('kategori_tugas', 'kategori_tugas.id_kat_tugas = tugas.id_kat_tugas', 'left')
            ->join('users', 'users.id_user = tugas.id_user', 'left')
            ->where('tugas.id_tugas', $idTugas)
            ->first();
    }

    // ── Dashboard PKL ───────────────────────────────────────────────

    /**
     * Tugas PKL yang belum selesai (tampil di dashboard "Tugas Saya").
     *
     * Tugas TAMPIL jika:
     *   - Belum dikirim sama sekali (tgl_pengumpulan IS NULL)
     *   - Sudah dikirim tapi ada item 'revisi'
     *   - Sudah dikirim tapi ada item 'dikirim' (menunggu review admin)
     *
     * Tugas HILANG hanya jika semua_diterima = 1:
     *   sudah submit + ada item + semua item berstatus 'diterima'
     *
     * Return keys:
     *   id_tugas, nama_tugas, deadline, nama_kat_tugas,
     *   sudah_kumpul   (id_pkl | NULL),
     *   ada_revisi     (1 | 0),
     *   semua_diterima (1 | 0)
     */
    public function getDashboardPkl(int $idPkl, int $idKelompok): array
    {
        if (! $idPkl) return [];

        // FIX: Ganti SELECT DISTINCT + correlated EXISTS subqueries dengan
        // GROUP BY + LEFT JOIN item_tugas langsung + SUM/COUNT aggregates.
        //
        // Pola lama (SELECT DISTINCT + CASE...EXISTS) bermasalah di MySQL
        // karena optimizer bisa salah melakukan predicate-pushdown pada
        // correlated subquery di dalam CASE, terutama saat pt (LEFT JOIN)
        // bernilai NULL — akibatnya nilai semua_diterima / ada_revisi keliru
        // dan tugas yang seharusnya tampil malah tersaring.
        //
        // Pendekatan baru dengan GROUP BY + SUM/COUNT bersifat deterministik
        // dan bekerja benar di semua versi MySQL.
        /*
         * CATATAN: Tidak ada komentar SQL (--) di dalam string query.
         * CI4 MySQLi driver mem-parsing sendiri placeholder (?) sebelum
         * dikirim ke MySQLi prepared statement. Komentar -- di dalam
         * string query memutus proses parsing tersebut sehingga MySQL
         * menerima literal '?' dan melempar syntax error.
         */
        $sql = "
            SELECT
                t.id_tugas,
                t.nama_tugas,
                t.deadline,
                kt.nama_kat_tugas,
                pt.id_pkl AS sudah_kumpul,
                IF(
                    pt.id_pkl IS NOT NULL
                    AND SUM(it.status_item = 'revisi') > 0,
                    1, 0
                ) AS ada_revisi,
                IF(
                    pt.id_pkl              IS NOT NULL
                    AND pt.tgl_pengumpulan IS NOT NULL
                    AND COUNT(it.id_item)  > 0
                    AND SUM(it.status_item != 'diterima') = 0,
                    1, 0
                ) AS semua_diterima
            FROM tugas t
            JOIN tugas_sasaran ts
                ON ts.id_tugas = t.id_tugas
            LEFT JOIN kategori_tugas kt
                ON kt.id_kat_tugas = t.id_kat_tugas
            LEFT JOIN pengumpulan_tugas pt
                ON pt.id_tugas        = t.id_tugas
               AND pt.id_pkl          = ?
               AND pt.tgl_pengumpulan IS NOT NULL
            LEFT JOIN item_tugas it
                ON it.id_pengumpulan_tgs = pt.id_pengumpulan_tgs
            WHERE (
                (ts.target_tipe = 'individu'  AND ts.id_pkl      = ?)
                OR (ts.target_tipe = 'kelompok'  AND ts.id_kelompok = ?)
                OR (ts.target_tipe = 'tim_tugas' AND ts.id_tim IN (
                    SELECT att.id_tim FROM anggota_tim_tugas att
                    WHERE att.id_pkl = ?
                ))
            )
            GROUP BY
                t.id_tugas,
                t.nama_tugas,
                t.deadline,
                kt.nama_kat_tugas,
                pt.id_pkl,
                pt.id_pengumpulan_tgs,
                pt.tgl_pengumpulan
            HAVING semua_diterima = 0
            ORDER BY t.deadline ASC
        ";

        try {
            return $this->db->query($sql, [
                $idPkl,       // LEFT JOIN pt.id_pkl = ?
                $idPkl,       // WHERE individu
                $idKelompok,  // WHERE kelompok
                $idPkl,       // WHERE tim_tugas subquery
            ])->getResultArray();
        } catch (\Exception $e) {
            log_message('warning', '[TugasModel::getDashboardPkl] ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Statistik tugas untuk PKL tertentu — 4 kategori.
     *
     * total         = semua tugas yang diberikan ke PKL ini
     * selesai       = tugas yang semua item-nya 'diterima'
     * pending       = sudah submit tapi belum selesai (ada 'dikirim' atau 'revisi')
     * belum_dikirim = belum submit sama sekali (pre-populated, tgl_pengumpulan = NULL)
     *
     * Invariant: total = selesai + pending + belum_dikirim
     *
     * Return: ['total' => int, 'selesai' => int, 'pending' => int, 'belum_dikirim' => int]
     */
    public function getStatsPkl(int $idPkl, int $idKelompok): array
    {
        $default = ['total' => 0, 'selesai' => 0, 'pending' => 0, 'belum_dikirim' => 0];
        if (! $idPkl) return $default;

        // WHERE sasaran — dipakai ulang di 3 query
        $whereSasaran = "
            (ts.target_tipe = 'individu'  AND ts.id_pkl      = ?)
            OR (ts.target_tipe = 'kelompok'  AND ts.id_kelompok = ?)
            OR (ts.target_tipe = 'tim_tugas' AND ts.id_tim IN (
                SELECT att.id_tim FROM anggota_tim_tugas att WHERE att.id_pkl = ?
            ))
        ";

        // ── 1. Total tugas yang diberikan ke PKL ini ─────────────────
        $sqlTotal = "
            SELECT COUNT(DISTINCT t.id_tugas) AS total
            FROM tugas t
            JOIN tugas_sasaran ts ON ts.id_tugas = t.id_tugas
            WHERE ($whereSasaran)
        ";

        // ── 2. Selesai: semua item berstatus 'diterima' ───────────────
        $sqlSelesai = "
            SELECT COUNT(DISTINCT t.id_tugas) AS selesai
            FROM tugas t
            JOIN tugas_sasaran ts ON ts.id_tugas = t.id_tugas
            JOIN pengumpulan_tugas pt
              ON pt.id_tugas        = t.id_tugas
             AND pt.id_pkl          = ?
             AND pt.tgl_pengumpulan IS NOT NULL
            WHERE ($whereSasaran)
              AND EXISTS (
                  SELECT 1 FROM item_tugas it
                  WHERE it.id_pengumpulan_tgs = pt.id_pengumpulan_tgs
              )
              AND NOT EXISTS (
                  SELECT 1 FROM item_tugas it
                  WHERE it.id_pengumpulan_tgs = pt.id_pengumpulan_tgs
                    AND it.status_item != 'diterima'
              )
        ";

        // ── 3. Pending: sudah submit tapi belum selesai ───────────────
        //    (ada item 'dikirim' menunggu review, atau ada item 'revisi')
        $sqlPending = "
            SELECT COUNT(DISTINCT t.id_tugas) AS pending
            FROM tugas t
            JOIN tugas_sasaran ts ON ts.id_tugas = t.id_tugas
            JOIN pengumpulan_tugas pt
              ON pt.id_tugas        = t.id_tugas
             AND pt.id_pkl          = ?
             AND pt.tgl_pengumpulan IS NOT NULL
            WHERE ($whereSasaran)
              AND (
                  -- Ada item yang belum final (dikirim/revisi)
                  EXISTS (
                      SELECT 1 FROM item_tugas it
                      WHERE it.id_pengumpulan_tgs = pt.id_pengumpulan_tgs
                        AND it.status_item IN ('dikirim', 'revisi')
                  )
                  -- Atau sudah submit tapi belum ada item sama sekali
                  OR NOT EXISTS (
                      SELECT 1 FROM item_tugas it
                      WHERE it.id_pengumpulan_tgs = pt.id_pengumpulan_tgs
                  )
              )
        ";

        // ── 4. Belum dikirim: pre-populated row, tgl_pengumpulan NULL ─
        $sqlBelumDikirim = "
            SELECT COUNT(DISTINCT t.id_tugas) AS belum_dikirim
            FROM tugas t
            JOIN tugas_sasaran ts ON ts.id_tugas = t.id_tugas
            LEFT JOIN pengumpulan_tugas pt
              ON pt.id_tugas = t.id_tugas
             AND pt.id_pkl   = ?
            WHERE ($whereSasaran)
              AND (pt.id_pkl IS NULL OR pt.tgl_pengumpulan IS NULL)
        ";

        try {
            $total = (int) ($this->db->query($sqlTotal, [
                $idPkl,
                $idKelompok,
                $idPkl,
            ])->getRow()->total ?? 0);

            $selesai = (int) ($this->db->query($sqlSelesai, [
                $idPkl,                        // JOIN pt.id_pkl = ?
                $idPkl,
                $idKelompok,
                $idPkl,   // WHERE sasaran
            ])->getRow()->selesai ?? 0);

            $pending = (int) ($this->db->query($sqlPending, [
                $idPkl,                        // JOIN pt.id_pkl = ?
                $idPkl,
                $idKelompok,
                $idPkl,   // WHERE sasaran
            ])->getRow()->pending ?? 0);

            $belumDikirim = (int) ($this->db->query($sqlBelumDikirim, [
                $idPkl,                        // LEFT JOIN pt.id_pkl = ?
                $idPkl,
                $idKelompok,
                $idPkl,   // WHERE sasaran
            ])->getRow()->belum_dikirim ?? 0);

            return [
                'total'        => $total,
                'selesai'      => $selesai,
                'pending'      => $pending,
                'belum_dikirim' => $belumDikirim,
            ];
        } catch (\Exception $e) {
            log_message('warning', '[TugasModel::getStatsPkl] ' . $e->getMessage());
            return $default;
        }
    }

    // ── General Methods ─────────────────────────────────────────────

    /**
     * Satu tugas beserta nama kategori tugasnya.
     */
    public function getOneWithKategori(int $id): ?array
    {
        return $this->select('tugas.*, kategori_tugas.nama_kat_tugas, kategori_tugas.mode_pengumpulan')
            ->join('kategori_tugas', 'kategori_tugas.id_kat_tugas = tugas.id_kat_tugas', 'left')
            ->where('tugas.id_tugas', $id)
            ->first();
    }

    /**
     * Semua tugas beserta nama kategori dan nama pembuat (dari users).
     * Dipakai halaman listing tugas admin.
     */
    public function getAllWithDetail(): array
    {
        return $this->select('tugas.*, kategori_tugas.nama_kat_tugas, users.username AS dibuat_oleh')
            ->join('kategori_tugas', 'kategori_tugas.id_kat_tugas = tugas.id_kat_tugas', 'left')
            ->join('users', 'users.id_user = tugas.id_user', 'left')
            ->orderBy('tugas.deadline', 'ASC')
            ->findAll();
    }

    /**
     * Ambil list tugas beserta nama kategorinya (untuk Datatables / List View)
     */
    public function getListTugas()
    {
        return $this->select('tugas.*, kategori_tugas.nama_kat_tugas, kategori_tugas.mode_pengumpulan')
            ->join('kategori_tugas', 'kategori_tugas.id_kat_tugas = tugas.id_kat_tugas')
            ->orderBy('tugas.created_at', 'DESC')
            ->findAll();
    }
}
