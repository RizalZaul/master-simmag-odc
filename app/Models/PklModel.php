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
     * Dipakai Auth Controller saat set session setelah login.
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
     * Dipakai halaman profil / detail PKL.
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
     * Dipakai halaman edit profil PKL.
     */
    public function findByIdUser(int $idUser): ?array
    {
        return $this->where('id_user', $idUser)->first();
    }

    /**
     * Ambil semua PKL dalam satu kelompok.
     * Dipakai halaman detail kelompok.
     */
    public function getByKelompok(int $idKelompok): array
    {
        return $this->where('id_kelompok', $idKelompok)->findAll();
    }

    // ── PKL Self-Service (dipakai PklProfilController) ──────────────

    /**
     * Data lengkap satu PKL untuk halaman Data Diri.
     * Satu query utama: pkl JOIN kelompok_pkl LEFT JOIN instansi.
     *
     * Keys: semua kolom pkl, id_instansi, tgl_mulai, tgl_akhir,
     *       nama_pembimbing, no_wa_pembimbing, nama_instansi, alamat_instansi
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
                'k.tgl_mulai',
                'k.tgl_akhir',
                'k.nama_pembimbing',
                'k.no_wa_pembimbing',
                'i.nama_instansi',
                'i.alamat_instansi',
            ])
            ->join('kelompok_pkl k', 'k.id_kelompok = p.id_kelompok', 'left')
            ->join('instansi i',     'i.id_instansi  = k.id_instansi',  'left')
            ->where('p.id_pkl', $idPkl)
            ->get()->getRowArray();

        return $row ?? [];
    }

    /**
     * Nama ketua dari satu kelompok.
     * Return null jika tidak ada ketua atau id_kelompok = 0.
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
     * Semua anggota kelompok beserta role-nya.
     * Urutan: ketua dulu, lalu anggota.
     * Return array of string nama_lengkap (untuk view).
     */
    public function getAnggotaKelompok(int $idKelompok): array
    {
        if (! $idKelompok) return [];

        $rows = $this->db->table('pkl')
            ->select('nama_lengkap, role_kel_pkl')
            ->where('id_kelompok', $idKelompok)
            ->orderBy("FIELD(role_kel_pkl, 'ketua', 'anggota')", '', false)
            ->get()->getResultArray();

        return array_map(fn($a) => $a['nama_lengkap'], $rows);
    }

    /**
     * Update data pribadi PKL (field yang boleh diubah sendiri).
     * Dipakai updateProfil() di PklProfilController.
     */
    public function updateDataDiri(int $idPkl, array $data): void
    {
        $this->update($idPkl, array_merge($data, [
            'updated_at' => date('Y-m-d H:i:s'),
        ]));
    }
}
