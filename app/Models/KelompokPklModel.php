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

    public function countAktif(): int
    {
        return $this->db->table('pkl p')
            ->join('kelompok_pkl k', 'k.id_kelompok = p.id_kelompok')
            ->join('users u', 'u.id_user = p.id_user')
            ->where('k.tgl_akhir >=', date('Y-m-d'))
            ->where('u.status', 'aktif')
            ->countAllResults();
    }

    public function countSelesai(): int
    {
        return $this->db->table('pkl p')
            ->join('kelompok_pkl k', 'k.id_kelompok = p.id_kelompok')
            ->join('users u', 'u.id_user = p.id_user')
            ->where('k.tgl_akhir <', date('Y-m-d'))
            ->where('u.status', 'aktif')
            ->countAllResults();
    }

    public function countNonAktif(): int
    {
        return $this->db->table('pkl p')
            ->join('users u', 'u.id_user = p.id_user')
            ->where('u.status', 'nonaktif')
            ->countAllResults();
    }

    public function getDashboardStats(): array
    {
        return [
            'aktif'    => $this->countAktif(),
            'selesai'  => $this->countSelesai(),
            'nonaktif' => $this->countNonAktif(),
        ];
    }

    private function baseQueryBuilder()
    {
        $cols = implode(', ', [
            'p.id_pkl',
            'p.nama_lengkap',
            'p.nama_panggilan',
            'p.role_kel_pkl',
            'k.id_kelompok',
            'k.tgl_mulai',
            'k.tgl_akhir',
            'k.status AS status_kelompok',
            'u.id_user',
            'u.status AS status_user',
            'u.username',
            'i.nama_instansi',
        ]);
        $caseExpr = "CASE WHEN k.id_instansi IS NULL THEN 'mandiri' ELSE i.kategori_instansi END AS kategori_pkl";

        return $this->db->table('pkl p')
            ->select($cols)
            ->select($caseExpr, false)
            ->join('kelompok_pkl k', 'k.id_kelompok = p.id_kelompok')
            ->join('users u', 'u.id_user = p.id_user')
            ->join('instansi i', 'i.id_instansi = k.id_instansi', 'left');
    }

    public function getAktif(): array
    {
        return $this->baseQueryBuilder()
            ->where('k.tgl_akhir >=', date('Y-m-d'))
            ->where('u.status', 'aktif')
            ->orderBy('p.nama_lengkap', 'ASC')
            ->get()->getResultArray();
    }

    public function getSelesai(): array
    {
        return $this->baseQueryBuilder()
            ->where('k.tgl_akhir <', date('Y-m-d'))
            ->where('u.status', 'aktif')
            ->orderBy('p.nama_lengkap', 'ASC')
            ->get()->getResultArray();
    }

    public function getNonAktif(): array
    {
        return $this->baseQueryBuilder()
            ->where('u.status', 'nonaktif')
            ->orderBy('p.nama_lengkap', 'ASC')
            ->get()->getResultArray();
    }

    public function getDetailKelompok(int $idKelompok): ?array
    {
        $cols      = implode(', ', ['k.*', 'i.nama_instansi', 'i.alamat_instansi', 'i.kota_instansi', 'i.kategori_instansi']);
        $caseExpr  = "CASE WHEN k.id_instansi IS NULL THEN 'mandiri' ELSE i.kategori_instansi END AS kategori_pkl";

        return $this->db->table('kelompok_pkl k')
            ->select($cols)->select($caseExpr, false)
            ->join('instansi i', 'i.id_instansi = k.id_instansi', 'left')
            ->where('k.id_kelompok', $idKelompok)
            ->get()->getRowArray() ?? null;
    }

    public function getAnggotaByKelompok(int $idKelompok): array
    {
        return $this->db->table('pkl p')
            ->select('p.*, u.username, u.email, u.status AS status_user')
            ->join('users u', 'u.id_user = p.id_user')
            ->where('p.id_kelompok', $idKelompok)
            ->orderBy("FIELD(p.role_kel_pkl,'ketua','anggota')", '', false)
            ->get()->getResultArray();
    }

    public function masihAdaAnggotaLain(int $idKelompok, int $exceptIdPkl): bool
    {
        return $this->db->table('pkl')->where('id_kelompok', $idKelompok)->where('id_pkl !=', $exceptIdPkl)->countAllResults() > 0;
    }

    public function jumlahAnggota(int $idKelompok): int
    {
        return $this->db->table('pkl')->where('id_kelompok', $idKelompok)->countAllResults();
    }

    // ── Untuk Modul Tugas ─────────────────────────────────────────

    /** PKL aktif untuk tab Individu di pilih-sasaran tugas. */
    public function getPklAktifForTugas(): array
    {
        $namaInstansi = "COALESCE(NULLIF(i.nama_instansi, ''), 'Mandiri') AS nama_instansi";
        $namaKelompok = "COALESCE(NULLIF(k.nama_kelompok, ''), CASE WHEN k.id_instansi IS NULL THEN 'Mandiri' ELSE CONCAT('Kelompok #', k.id_kelompok) END) AS nama_kelompok";

        return $this->db->table('pkl p')
            ->select('p.id_pkl, p.nama_lengkap')
            ->select($namaInstansi, false)
            ->select($namaKelompok, false)
            ->join('kelompok_pkl k', 'k.id_kelompok = p.id_kelompok')
            ->join('users u', 'u.id_user = p.id_user')
            ->join('instansi i', 'i.id_instansi = k.id_instansi', 'left')
            ->where('k.status', 'aktif')
            ->where('k.tgl_akhir >=', date('Y-m-d'))
            ->where('u.status', 'aktif')
            ->orderBy('p.nama_lengkap', 'ASC')
            ->get()->getResultArray();
    }

    /** Kelompok aktif untuk tab Kelompok di pilih-sasaran tugas. */
    public function getKelompokAktifForTugas(): array
    {
        $namaKelompok = "COALESCE(NULLIF(k.nama_kelompok, ''), CONCAT('Kelompok #', k.id_kelompok)) AS nama_kelompok";

        return $this->db->table('kelompok_pkl k')
            ->select('k.id_kelompok, i.nama_instansi')
            ->select($namaKelompok, false)
            ->select('COUNT(DISTINCT p.id_pkl) AS jumlah_anggota', false)
            ->join('instansi i', 'i.id_instansi = k.id_instansi', 'left')
            ->join('pkl p', 'p.id_kelompok = k.id_kelompok', 'left')
            ->join('users u', 'u.id_user = p.id_user', 'left')
            ->where('k.id_instansi IS NOT NULL', null, false)
            ->where('k.tgl_akhir >=', date('Y-m-d'))
            ->where('k.status', 'aktif')
            ->groupStart()
                ->where('u.id_user IS NULL', null, false)
                ->orWhere('u.status', 'aktif')
            ->groupEnd()
            ->groupBy('k.id_kelompok, i.nama_instansi')
            ->orderBy('k.nama_kelompok', 'ASC')
            ->get()->getResultArray();
    }

    /**
     * PKL aktif dengan info kategori PKL (instansi/mandiri) dan nama kelompok.
     * Dipakai form "Buat Tim Tugas Baru" — tab Tim Tugas di pilih-sasaran.
     *
     * Return keys: id_pkl, nama_lengkap, kategori_pkl, kelompok_nama
     */
    public function getPklAktifWithKategori(): array
    {
        $caseKategori = "CASE WHEN k.id_instansi IS NULL THEN 'mandiri' ELSE 'instansi' END AS kategori_pkl";
        $caseKelompok = "COALESCE(NULLIF(k.nama_kelompok, ''), CASE WHEN k.id_instansi IS NULL THEN 'Mandiri' ELSE CONCAT('Kelompok #', k.id_kelompok) END) AS kelompok_nama";

        return $this->db->table('pkl p')
            ->select('p.id_pkl, p.nama_lengkap')
            ->select($caseKategori, false)
            ->select($caseKelompok, false)
            ->join('kelompok_pkl k', 'k.id_kelompok = p.id_kelompok')
            ->join('users u', 'u.id_user = p.id_user')
            ->where('k.status', 'aktif')
            ->where('k.tgl_akhir >=', date('Y-m-d'))
            ->where('u.status', 'aktif')
            ->orderBy('p.nama_lengkap', 'ASC')
            ->get()->getResultArray();
    }

    public function getPengumpulanRowsForAdmin(): array
    {
        return $this->db->table('tugas_sasaran ts')
            ->select('ts.id_tugas, ts.id_kelompok, t.nama_tugas, t.deadline')
            ->select('kt.nama_kat_tugas, kt.mode_pengumpulan')
            ->select("COALESCE(NULLIF(k.nama_kelompok, ''), CONCAT('Kelompok #', k.id_kelompok)) AS nama_target", false)
            ->select("GROUP_CONCAT(DISTINCT pt.id_pengumpulan_tgs ORDER BY pt.id_pengumpulan_tgs ASC SEPARATOR ',') AS pengumpulan_ids", false)
            ->select('MAX(pt.tgl_pengumpulan) AS waktu_pengumpulan', false)
            ->join('tugas t', 't.id_tugas = ts.id_tugas')
            ->join('kategori_tugas kt', 'kt.id_kat_tugas = t.id_kat_tugas')
            ->join('kelompok_pkl k', 'k.id_kelompok = ts.id_kelompok', 'left')
            ->join('pengumpulan_tugas pt', 'pt.id_tugas = ts.id_tugas AND pt.id_kelompok = ts.id_kelompok', 'left')
            ->where('kt.mode_pengumpulan', 'kelompok')
            ->where('ts.target_tipe', 'kelompok')
            ->groupBy('ts.id_tugas, ts.id_kelompok, t.nama_tugas, t.deadline, kt.nama_kat_tugas, kt.mode_pengumpulan, k.nama_kelompok, k.id_kelompok')
            ->orderBy('t.deadline', 'ASC')
            ->orderBy('nama_target', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getPengumpulanDetailRow(int $idTugas, int $idKelompok): ?array
    {
        return $this->db->table('tugas_sasaran ts')
            ->select('ts.id_tugas, ts.id_kelompok, t.nama_tugas, t.deskripsi, t.deadline')
            ->select('kt.nama_kat_tugas, kt.mode_pengumpulan')
            ->select("COALESCE(NULLIF(k.nama_kelompok, ''), CONCAT('Kelompok #', k.id_kelompok)) AS nama_target", false)
            ->select("GROUP_CONCAT(DISTINCT pt.id_pengumpulan_tgs ORDER BY pt.id_pengumpulan_tgs ASC SEPARATOR ',') AS pengumpulan_ids", false)
            ->select('MAX(pt.tgl_pengumpulan) AS waktu_pengumpulan', false)
            ->join('tugas t', 't.id_tugas = ts.id_tugas')
            ->join('kategori_tugas kt', 'kt.id_kat_tugas = t.id_kat_tugas')
            ->join('kelompok_pkl k', 'k.id_kelompok = ts.id_kelompok', 'left')
            ->join('pengumpulan_tugas pt', 'pt.id_tugas = ts.id_tugas AND pt.id_kelompok = ts.id_kelompok', 'left')
            ->where('ts.id_tugas', $idTugas)
            ->where('ts.id_kelompok', $idKelompok)
            ->where('ts.target_tipe', 'kelompok')
            ->where('kt.mode_pengumpulan', 'kelompok')
            ->groupBy('ts.id_tugas, ts.id_kelompok, t.nama_tugas, t.deskripsi, t.deadline, kt.nama_kat_tugas, kt.mode_pengumpulan, k.nama_kelompok, k.id_kelompok')
            ->get()
            ->getRowArray();
    }

    public function getActiveMemberNames(int $idKelompok): array
    {
        return $this->db->table('pkl p')
            ->select('p.nama_lengkap')
            ->join('users u', 'u.id_user = p.id_user', 'left')
            ->where('p.id_kelompok', $idKelompok)
            ->groupStart()
                ->where('u.id_user IS NULL', null, false)
                ->orWhere('u.status', 'aktif')
            ->groupEnd()
            ->orderBy("FIELD(p.role_kel_pkl, 'ketua', 'anggota')", '', false)
            ->orderBy('p.nama_lengkap', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getTugasTargetDetail(int $idKelompok): ?array
    {
        return $this->db->table('kelompok_pkl k')
            ->select("COALESCE(NULLIF(k.nama_kelompok, ''), CONCAT('Kelompok #', k.id_kelompok)) AS nama_kelompok", false)
            ->select("COALESCE(NULLIF(i.nama_instansi, ''), 'Instansi Tidak Diketahui') AS nama_instansi", false)
            ->select('COUNT(DISTINCT p.id_pkl) AS jumlah_anggota', false)
            ->join('instansi i', 'i.id_instansi = k.id_instansi', 'left')
            ->join('pkl p', 'p.id_kelompok = k.id_kelompok', 'left')
            ->where('k.id_kelompok', $idKelompok)
            ->groupBy('k.id_kelompok, i.nama_instansi')
            ->get()
            ->getRowArray();
    }
}
