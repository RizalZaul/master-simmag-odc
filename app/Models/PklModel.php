<?php

namespace App\Models;

use CodeIgniter\Model;

class PklModel extends Model
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

    // ── Custom Methods ──────────────────────────────────────────────

    /**
     * Ambil profil singkat PKL berdasarkan id_user.
     * Dipakai AuthController saat set session setelah login.
     */
    public function getProfilByIdUser(int $idUser): array
    {
        $row = $this->where('id_user', $idUser)->first();

        if (! $row) return [];

        return [
            'id_pkl'         => $row['id_pkl'],
            'id_kelompok'    => $row['id_kelompok'],
            'nama_lengkap'   => $row['nama_lengkap'],
            'nama_panggilan' => $row['nama_panggilan'] ?? null,
        ];
    }

    /**
     * Ambil profil lengkap PKL beserta data user dan kelompoknya.
     * Dipakai halaman detail PKL.
     */
    public function getWithDetail(int $idPkl): ?array
    {
        return $this->select('pkl.*, users.email, users.username, users.status,
                              kelompok_pkl.nama_kelompok, kelompok_pkl.tgl_mulai, kelompok_pkl.tgl_akhir')
            ->join('users', 'users.id_user = pkl.id_user')
            ->join('kelompok_pkl', 'kelompok_pkl.id_kelompok = pkl.id_kelompok')
            ->where('pkl.id_pkl', $idPkl)
            ->first();
    }

    /**
     * Ambil satu baris penuh PKL berdasarkan id_user.
     */
    public function findByIdUser(int $idUser): ?array
    {
        return $this->where('id_user', $idUser)->first();
    }

    /**
     * Ambil semua PKL dalam satu kelompok.
     */
    public function getByKelompok(int $idKelompok): array
    {
        return $this->where('id_kelompok', $idKelompok)->findAll();
    }

    // ── PKL Self-Service (dipakai ProfilPklController) ──────────────

    /**
     * Data lengkap satu PKL untuk halaman profil.
     *
     * FIX: Menambahkan field yang sebelumnya tidak di-select:
     *   - k.nama_kelompok    → ditampilkan di section instansi
     *   - k.status           → untuk badge aktif/selesai di durasi card
     *   - i.kategori_instansi → penentu label Kampus/Sekolah
     *   - i.kota_instansi    → ditampilkan di section instansi
     *
     * Keys: semua kolom pkl + id_instansi, tgl_mulai, tgl_akhir,
     *       status, nama_kelompok, nama_pembimbing, no_wa_pembimbing,
     *       nama_instansi, alamat_instansi, kategori_instansi, kota_instansi
     */
    public function getDataDiri(int $idPkl): array
    {
        $row = $this->db->table('pkl p')
            ->select([
                'p.id_pkl',
                'p.nama_lengkap',
                'p.nama_panggilan',
                'p.tempat_lahir',
                'p.tgl_lahir',
                'p.no_wa_pkl',
                'p.jenis_kelamin',
                'p.alamat',
                'p.jurusan',
                'p.role_kel_pkl',
                'k.id_kelompok',
                'k.id_instansi',
                'k.nama_kelompok',
                'k.tgl_mulai',
                'k.tgl_akhir',
                'k.status AS status_kelompok',
                'k.nama_pembimbing',
                'k.no_wa_pembimbing',
                'i.nama_instansi',
                'i.alamat_instansi',
                'i.kategori_instansi',
                'i.kota_instansi',
            ])
            ->join('kelompok_pkl k', 'k.id_kelompok = p.id_kelompok', 'left')
            ->join('instansi i',     'i.id_instansi  = k.id_instansi',  'left')
            ->where('p.id_pkl', $idPkl)
            ->get()->getRowArray();

        return $row ?? [];
    }

    /**
     * Nama ketua dari satu kelompok.
     */
    public function getKetuaKelompok(int $idKelompok): ?string
    {
        if (! $idKelompok) return null;

        $row = $this->select('nama_lengkap')
            ->where('id_kelompok', $idKelompok)
            ->where('role_kel_pkl', 'ketua')
            ->first();

        return $row['nama_lengkap'] ?? null;
    }

    /**
     * Semua anggota kelompok (ketua dulu, lalu anggota).
     * Return array of ['nama_lengkap' => ..., 'role_kel_pkl' => ...]
     * untuk keperluan tampil badge role di view.
     */
    public function getAnggotaKelompok(int $idKelompok): array
    {
        if (! $idKelompok) return [];

        return $this->db->table('pkl')
            ->select('nama_lengkap, role_kel_pkl')
            ->where('id_kelompok', $idKelompok)
            ->orderBy("FIELD(role_kel_pkl, 'ketua', 'anggota')", '', false)
            ->get()->getResultArray();
    }

    /**
     * Update data pribadi PKL (field yang boleh diubah sendiri).
     *
     * FIX: Hapus manual 'updated_at' — serahkan ke $useTimestamps = true.
     * Sama dengan fix di AdminModel::updateProfil().
     */
    public function updateDataDiri(int $idPkl, array $data): void
    {
        $this->update($idPkl, $data);
    }

    public function getActiveRecipientRowsByPklIds(array $targetIds): array
    {
        $targetIds = array_values(array_unique(array_filter(array_map('intval', $targetIds))));
        if ($targetIds === []) {
            return [];
        }

        return $this->db->table('pkl p')
            ->select('p.id_pkl, p.id_kelompok')
            ->join('users u', 'u.id_user = p.id_user')
            ->join('kelompok_pkl k', 'k.id_kelompok = p.id_kelompok')
            ->where('u.status', 'aktif')
            ->where('k.status', 'aktif')
            ->where('k.tgl_akhir >=', date('Y-m-d'))
            ->whereIn('p.id_pkl', $targetIds)
            ->get()
            ->getResultArray();
    }

    public function getActiveRecipientRowsByKelompokIds(array $targetIds): array
    {
        $targetIds = array_values(array_unique(array_filter(array_map('intval', $targetIds))));
        if ($targetIds === []) {
            return [];
        }

        return $this->db->table('pkl p')
            ->select('p.id_pkl, p.id_kelompok')
            ->join('users u', 'u.id_user = p.id_user')
            ->join('kelompok_pkl k', 'k.id_kelompok = p.id_kelompok')
            ->where('u.status', 'aktif')
            ->where('k.status', 'aktif')
            ->where('k.tgl_akhir >=', date('Y-m-d'))
            ->whereIn('p.id_kelompok', $targetIds)
            ->get()
            ->getResultArray();
    }

    public function getTugasTargetDetail(int $idPkl): ?array
    {
        return $this->db->table('pkl p')
            ->select('p.nama_lengkap')
            ->select("COALESCE(NULLIF(i.nama_instansi, ''), 'Mandiri') AS nama_instansi", false)
            ->select("COALESCE(NULLIF(k.nama_kelompok, ''), CASE WHEN k.id_instansi IS NULL THEN 'Mandiri' ELSE CONCAT('Kelompok #', k.id_kelompok) END) AS nama_kelompok", false)
            ->join('kelompok_pkl k', 'k.id_kelompok = p.id_kelompok')
            ->join('instansi i', 'i.id_instansi = k.id_instansi', 'left')
            ->where('p.id_pkl', $idPkl)
            ->get()
            ->getRowArray();
    }
}
