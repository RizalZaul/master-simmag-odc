<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table            = 'users';
    protected $primaryKey       = 'id_user';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';

    protected $allowedFields = [
        'email',
        'username',
        'password',
        'role',
        'status',
        'kode_otp',
        'tenggat_otp',
    ];

    protected $allowCallbacks = true;
    protected $beforeInsert   = ['hashPassword'];
    protected $beforeUpdate   = ['hashPassword'];

    // ── Callbacks ──────────────────────────────────────────────────

    /**
     * Auto-hash password sebelum insert/update.
     * Guard: skip jika password sudah berupa bcrypt hash.
     */
    protected function hashPassword(array $data): array
    {
        if (isset($data['data']['password'])) {
            $pwd = $data['data']['password'];
            if (! str_starts_with($pwd, '$2y$')) {
                $data['data']['password'] = password_hash($pwd, PASSWORD_BCRYPT);
            }
        }
        return $data;
    }

    // ── Custom Methods ──────────────────────────────────────────────

    /**
     * Cari user by username ATAU email.
     * Status tidak difilter — Controller yang handle pesan errornya.
     */
    public function findByIdentifier(string $identifier): ?object
    {
        return $this->groupStart()
            ->where('username', $identifier)
            ->orWhere('email', $identifier)
            ->groupEnd()
            ->first();
    }

    /**
     * Cek apakah email sudah terdaftar di tabel users.
     * Dipakai storePkl() di ManajemenPklController.
     */
    public function isEmailExists(string $email): bool
    {
        return $this->where('email', strtolower(trim($email)))->countAllResults() > 0;
    }

    /**
     * Generate username unik berdasarkan string base.
     * Jika 'pkl123' sudah ada → coba 'pkl1231', 'pkl1232', dst.
     * Dipakai storePkl() di ManajemenPklController.
     */
    public function generateUniqueUsername(string $base): string
    {
        $base     = preg_replace('/[^a-z0-9_]/', '', strtolower($base)) ?: 'pkl';
        $username = $base;
        $counter  = 1;

        while ($this->where('username', $username)->countAllResults() > 0) {
            $username = $base . $counter++;
        }

        return $username;
    }

    /**
     * Update password user by id_user.
     * Password di-hash via beforeUpdate callback (hashPassword).
     * Dipakai updatePassword() di Dashboard.
     */
    public function updatePassword(int $idUser, string $passwordPlain): void
    {
        $this->update($idUser, ['password' => $passwordPlain]);
    }
}
