<?php

namespace App\Models;

use CodeIgniter\Model;

class AdminModel extends Model
{
    protected $table            = 'admin';
    protected $primaryKey       = 'id_admin';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';

    protected $allowedFields = [
        'id_user',
        'nama_lengkap',
        'nama_panggilan',
        'no_wa_admin',
        'alamat',
    ];

    // ── Custom Methods ──────────────────────────────────────────────

    /**
     * Ambil profil singkat admin berdasarkan id_user.
     * Dipakai Auth Controller saat set session setelah login.
     */
    public function getProfilByIdUser(int $idUser): array
    {
        $row = $this->where('id_user', $idUser)->first();

        if (! $row) return [];

        return [
            'id_admin'       => $row['id_admin'],
            'nama_lengkap'   => $row['nama_lengkap'],
            'nama_panggilan' => $row['nama_panggilan'] ?? null,
        ];
    }

    /**
     * Ambil satu baris penuh admin berdasarkan id_user.
     * Dipakai halaman edit profil admin.
     */
    public function findByIdUser(int $idUser): ?array
    {
        return $this->where('id_user', $idUser)->first();
    }

    /**
     * Ambil data admin beserta data user-nya.
     * Dipakai halaman detail / profil admin.
     */
    public function getWithUser(int $idAdmin): ?array
    {
        return $this->select('admin.*, users.email, users.username, users.status')
            ->join('users', 'users.id_user = admin.id_user')
            ->where('admin.id_admin', $idAdmin)
            ->first();
    }

    /**
     * Ambil field profil admin dari DB untuk halaman profil.
     * Dipakai Dashboard::profile().
     *
     * FIX: Sebelumnya hanya SELECT no_wa_admin + alamat.
     * Sekarang juga ambil nama_lengkap + nama_panggilan agar
     * perubahan langsung di DB langsung terlihat tanpa re-login.
     * (nama di session hanya untuk navbar/greeting, bukan source of truth profil)
     *
     * Keys: nama_lengkap, nama_panggilan, no_wa_admin, alamat
     */
    public function getExtraProfil(int $idAdmin): array
    {
        $row = $this->select('nama_lengkap, nama_panggilan, no_wa_admin, alamat')
            ->where('id_admin', $idAdmin)
            ->first();

        return $row ?? [
            'nama_lengkap'   => '',
            'nama_panggilan' => '',
            'no_wa_admin'    => '-',
            'alamat'         => '-',
        ];
    }

    /**
     * Update profil admin (nama, panggilan, no_wa, alamat).
     * Dipakai Dashboard::updateProfile().
     */
    public function updateProfil(int $idAdmin, array $data): void
    {
        $this->update($idAdmin, array_merge($data, [
            'updated_at' => date('Y-m-d H:i:s'),
        ]));
    }
}
