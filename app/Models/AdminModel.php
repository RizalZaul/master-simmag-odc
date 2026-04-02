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

    /*
     * $useTimestamps = true → CI4 otomatis mengisi created_at saat insert
     * dan updated_at saat update, TANPA perlu ditambahkan manual ke $data.
     * Menambahkan 'updated_at' secara manual ke array data adalah anti-pattern:
     * field tersebut bukan bagian dari $allowedFields sehingga akan di-strip
     * oleh $protectFields, lalu CI4 menambahkannya kembali via timestamp handler
     * — double-handling yang tidak perlu dan berpotensi race condition.
     */
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $allowedFields = [
        'id_user',
        'nama_lengkap',
        'nama_panggilan',
        'no_wa_admin',
        'alamat',
        // Catatan: created_at & updated_at SENGAJA tidak di sini.
        // CI4 mengelolanya sendiri via $useTimestamps — bukan lewat $allowedFields.
    ];

    // ── Custom Methods ──────────────────────────────────────────────

    /**
     * Ambil profil singkat admin berdasarkan id_user.
     * Dipakai AuthController saat set session setelah login.
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
     * Dipakai halaman profil admin.
     */
    public function findByIdUser(int $idUser): ?array
    {
        return $this->where('id_user', $idUser)->first();
    }

    /**
     * Ambil data admin beserta data user-nya (join).
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
     * Selalu baca dari DB, bukan session — supaya perubahan langsung
     * terlihat tanpa perlu re-login.
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
     *
     * FIX: Tidak lagi meng-inject 'updated_at' manual.
     * $useTimestamps = true sudah otomatis mengisi updated_at
     * di setiap pemanggilan update() — ini cara yang benar di CI4.
     */
    public function updateProfil(int $idAdmin, array $data): void
    {
        $this->update($idAdmin, $data);
    }
}
