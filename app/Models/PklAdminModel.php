<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * PklAdminModel
 *
 * Khusus kebutuhan admin — query kompleks lintas tabel
 * (pkl + users + kelompok_pkl + instansi).
 *
 * Berbeda dari PklModel yang fokus pada kebutuhan
 * PKL itu sendiri (profil, session, edit biodata).
 */
class PklAdminModel extends Model
{
    protected $table            = 'pkl';
    protected $primaryKey       = 'id_pkl';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';

    protected $allowedFields = [
        'id_user',
        'id_kelompok',
        'nama_lengkap',
        'nama_panggilan',
        'tempat_lahir',
        'tgl_lahir',
        'no_wa_pkl',
        'jenis_kelamin',
        'alamat',
        'role_kel_pkl',
        'jurusan',
    ];

    // ── Sasaran Tugas — Data API ─────────────────────────────────────

    /**
     * PKL mandiri (kelompok tanpa instansi) yang aktif.
     * Dipakai getSasaranMandiriList() di controller.
     *
     * Keys: id, nama, jk, tgl_mulai, tgl_akhir
     */
    public function getSasaranMandiri(): array
    {
        $rows = $this->db->query("
            SELECT
                p.id_pkl,
                p.nama_lengkap,
                CASE p.jenis_kelamin
                    WHEN 'L' THEN 'Laki-laki'
                    WHEN 'P' THEN 'Perempuan'
                    ELSE '-'
                END AS jenis_kelamin,
                DATE_FORMAT(k.tgl_mulai, '%d-%m-%Y') AS tgl_mulai,
                DATE_FORMAT(k.tgl_akhir, '%d-%m-%Y') AS tgl_akhir
            FROM pkl p
            JOIN kelompok_pkl k ON k.id_kelompok = p.id_kelompok
            JOIN users u        ON u.id_user      = p.id_user
            WHERE k.id_instansi IS NULL
              AND k.status = 'aktif'
              AND u.status = 'aktif'
            ORDER BY p.nama_lengkap ASC
        ")->getResultArray();

        return array_map(fn($r) => [
            'id'        => $r['id_pkl'],
            'nama'      => $r['nama_lengkap'],
            'jk'        => $r['jenis_kelamin'],
            'tgl_mulai' => $r['tgl_mulai'],
            'tgl_akhir' => $r['tgl_akhir'],
        ], $rows);
    }

    /**
     * Kelompok PKL aktif untuk tab Kelompok sasaran.
     * Dipakai getSasaranKelompokList() di controller.
     *
     * Keys: id, nama, instansi, jumlah, tgl_mulai, tgl_akhir
     */
    public function getSasaranKelompok(): array
    {
        $rows = $this->db->query("
            SELECT
                k.id_kelompok,
                k.nama_kelompok,
                i.nama_instansi                       AS instansi,
                COUNT(p.id_pkl)                       AS jumlah,
                DATE_FORMAT(k.tgl_mulai, '%d-%m-%Y')  AS tgl_mulai,
                DATE_FORMAT(k.tgl_akhir, '%d-%m-%Y')  AS tgl_akhir
            FROM kelompok_pkl k
            LEFT JOIN instansi i ON i.id_instansi = k.id_instansi
            LEFT JOIN pkl p      ON p.id_kelompok = k.id_kelompok
            WHERE k.id_instansi IS NOT NULL
              AND k.status = 'aktif'
            GROUP BY k.id_kelompok
            ORDER BY k.nama_kelompok ASC
        ")->getResultArray();

        return array_map(fn($r) => [
            'id'        => $r['id_kelompok'],
            'nama'      => $r['nama_kelompok'],
            'instansi'  => $r['instansi'] ?? '-',
            'jumlah'    => (int) $r['jumlah'],
            'tgl_mulai' => $r['tgl_mulai'],
            'tgl_akhir' => $r['tgl_akhir'],
        ], $rows);
    }

    /**
     * Semua PKL aktif untuk form Buat Tim.
     * Dipakai getPklMemberList() di controller.
     *
     * Keys: id, nama, kategori, kelompok
     */
    public function getPklMemberList(): array
    {
        $rows = $this->db->query("
            SELECT
                p.id_pkl,
                p.nama_lengkap,
                CASE WHEN k.id_instansi IS NULL THEN 'Mandiri' ELSE 'Instansi' END AS kategori,
                COALESCE(k.nama_kelompok, '-')                                     AS kelompok
            FROM pkl p
            JOIN kelompok_pkl k ON k.id_kelompok = p.id_kelompok
            JOIN users u        ON u.id_user      = p.id_user
            WHERE u.status = 'aktif'
              AND k.status = 'aktif'
            ORDER BY p.nama_lengkap ASC
        ")->getResultArray();

        return array_map(fn($r) => [
            'id'       => $r['id_pkl'],
            'nama'     => $r['nama_lengkap'],
            'kategori' => $r['kategori'],
            'kelompok' => $r['kelompok'],
        ], $rows);
    }

    // ── Base Query ──────────────────────────────────────────────────
    /**
     * Base query dengan semua join yang dibutuhkan.
     * Dipakai sebagai fondasi getAktif(), getSelesai(), getNonaktif().
     */
    private function baseQuery(): \CodeIgniter\Database\BaseBuilder
    {
        return $this->db->table('pkl')
            ->select('pkl.id_pkl,
                              pkl.nama_lengkap,
                              pkl.nama_panggilan,
                              pkl.tempat_lahir,
                              pkl.tgl_lahir,
                              pkl.alamat,
                              pkl.jurusan,
                              pkl.role_kel_pkl,
                              pkl.no_wa_pkl,
                              pkl.jenis_kelamin,
                              users.id_user,
                              users.email,
                              users.username,
                              users.status           AS status_user,
                              kelompok_pkl.id_kelompok,
                              kelompok_pkl.nama_kelompok,
                              kelompok_pkl.tgl_mulai,
                              kelompok_pkl.tgl_akhir,
                              kelompok_pkl.status    AS status_kelompok,
                              instansi.nama_instansi,
                              instansi.kota_instansi,
                              CASE WHEN kelompok_pkl.id_instansi IS NULL
                                   THEN \'Mandiri\'
                                   ELSE \'Instansi\'
                              END                    AS kategori_pkl')
            ->join('users',        'users.id_user = pkl.id_user')
            ->join('kelompok_pkl', 'kelompok_pkl.id_kelompok = pkl.id_kelompok')
            ->join('instansi',     'instansi.id_instansi = kelompok_pkl.id_instansi', 'left');
    }

    // ── List Methods ────────────────────────────────────────────────

    /**
     * PKL aktif = user aktif + kelompok aktif.
     */
    public function getAktif(): array
    {
        $this->_syncKelompokStatus();

        return $this->baseQuery()
            ->where('users.status', 'aktif')
            ->where('kelompok_pkl.tgl_akhir >=', date('Y-m-d'))
            ->get()->getResultArray();
    }

    /**
     * PKL selesai = kelompok sudah selesai (regardless status user).
     */
    public function getSelesai(): array
    {
        $this->_syncKelompokStatus();

        return $this->baseQuery()
            ->where('kelompok_pkl.tgl_akhir <', date('Y-m-d'))
            ->get()->getResultArray();
    }

    /**
     * PKL nonaktif = user dinonaktifkan admin.
     */
    public function getNonaktif(): array
    {
        return $this->baseQuery()
            ->where('users.status', 'nonaktif')
            ->orderBy('kelompok_pkl.nama_kelompok', 'ASC')
            ->orderBy('pkl.nama_lengkap', 'ASC')
            ->get()->getResultArray();
    }

    // ── Stats ───────────────────────────────────────────────────────

    /**
     * Statistik ringkas untuk halaman utama PKL.
     */
    public function getStats(): array
    {
        $this->_syncKelompokStatus();

        $today = date('Y-m-d');

        return [
            'total'    => $this->db->table('pkl')->countAllResults(),
            'aktif'    => $this->db->table('pkl')
                ->join('users',        'users.id_user = pkl.id_user')
                ->join('kelompok_pkl', 'kelompok_pkl.id_kelompok = pkl.id_kelompok')
                ->where('users.status', 'aktif')
                ->where('kelompok_pkl.tgl_akhir >=', $today)
                ->countAllResults(),
            'selesai'  => $this->db->table('pkl')
                ->join('kelompok_pkl', 'kelompok_pkl.id_kelompok = pkl.id_kelompok')
                ->where('kelompok_pkl.tgl_akhir <', $today)
                ->countAllResults(),
            'nonaktif' => $this->db->table('pkl')
                ->join('users', 'users.id_user = pkl.id_user')
                ->where('users.status', 'nonaktif')
                ->countAllResults(),
        ];
    }

    /**
     * Sync kolom status kelompok_pkl berdasarkan tgl_akhir.
     * Dipanggil otomatis sebelum setiap query read.
     *
     * FIX BUG-06 (full): Delegasikan ke KelompokPklModel::syncStatus() yang
     * memegang static $synced flag. Dengan begitu tidak ada duplikasi logika
     * UPDATE di dua tempat, dan sync benar-benar hanya berjalan sekali per
     * HTTP request meskipun kedua model dipanggil dalam request yang sama.
     */
    private function _syncKelompokStatus(): void
    {
        \App\Models\KelompokPklModel::syncStatus();
    }

    // ── Detail ──────────────────────────────────────────────────────

    /**
     * Detail lengkap satu kelompok beserta semua anggotanya.
     * Dipakai detailPkl() dan ubahPkl() di Controller.
     *
     * @return array|null ['kelompok' => [...], 'anggota' => [...]]
     */
    public function getDetailKelompok(int $idKelompok): ?array
    {
        $kelompok = $this->db->table('kelompok_pkl')
            ->select('kelompok_pkl.*,
                                       instansi.nama_instansi,
                                       instansi.alamat_instansi,
                                       instansi.kota_instansi,
                                       instansi.kategori_instansi')
            ->join('instansi', 'instansi.id_instansi = kelompok_pkl.id_instansi', 'left')
            ->where('kelompok_pkl.id_kelompok', $idKelompok)
            ->get()->getRowArray();

        if (! $kelompok) return null;

        $anggota = $this->baseQuery()
            ->where('pkl.id_kelompok', $idKelompok)
            ->orderBy('pkl.role_kel_pkl', 'ASC') // ketua dulu
            ->orderBy('pkl.nama_lengkap', 'ASC')
            ->get()->getResultArray();

        return [
            'kelompok' => $kelompok,
            'anggota'  => $anggota,
        ];
    }

    // ── Status Management ───────────────────────────────────────────

    /**
     * Ubah status user PKL (aktif/nonaktif).
     * Dipakai nonaktifkanPkl() dan aktifkanPkl() di Controller.
     */
    public function ubahStatusUser(int $idPkl, string $status): bool
    {
        $pkl = $this->find($idPkl);

        if (! $pkl) return false;

        return $this->db->table('users')
            ->where('id_user', $pkl['id_user'])
            ->update(['status' => $status, 'updated_at' => date('Y-m-d H:i:s')]);
    }

    // ── Delete ──────────────────────────────────────────────────────

    /**
     * Hapus PKL beserta user-nya sekaligus.
     * FK ON DELETE CASCADE di users sudah handle ini,
     * tapi kita hapus user dulu agar lebih eksplisit.
     */
    public function deletePkl(int $idPkl): array
    {
        // Ambil id_user dan id_kelompok sebelum dihapus
        $pkl = $this->db->table('pkl')
            ->select('id_user, id_kelompok')
            ->where('id_pkl', $idPkl)
            ->get()->getRowArray();

        if (! $pkl) {
            return ['found' => false, 'kelompok_dihapus' => false];
        }

        // Hapus user → CASCADE otomatis hapus baris pkl
        $this->db->table('users')
            ->where('id_user', $pkl['id_user'])
            ->delete();

        // Cek sisa anggota di kelompok
        $sisaAnggota = $this->db->table('pkl')
            ->where('id_kelompok', $pkl['id_kelompok'])
            ->countAllResults();

        $kelompokDihapus = false;
        if ($sisaAnggota === 0) {
            $this->db->table('kelompok_pkl')
                ->where('id_kelompok', $pkl['id_kelompok'])
                ->delete();
            $kelompokDihapus = true;
        }

        return ['found' => true, 'kelompok_dihapus' => $kelompokDihapus];
    }
}
