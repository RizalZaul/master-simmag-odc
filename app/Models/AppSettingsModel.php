<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * AppSettingsModel
 *
 * Model untuk tabel `app_settings`.
 * Struktur tabel:
 *   id_setting, key, value, label, created_at, updated_at
 *
 * NOTE: Method dinamai getValue/setValue (bukan get/set) karena
 *       CI4 base Model sudah punya get() dan set() untuk query builder.
 *       Menggunakan nama yang sama akan menyebabkan error "not compatible".
 */
class AppSettingsModel extends Model
{
    protected $table         = 'app_settings';
    protected $primaryKey    = 'id_setting';
    protected $returnType    = 'array';
    protected $protectFields = true;

    /*
     * $useTimestamps = true → CI4 otomatis mengisi created_at saat insert
     * dan updated_at saat update. Tidak perlu inject manual.
     */
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $allowedFields = [
        'key',
        'value',
        'label',
        // created_at & updated_at: dikelola CI4 via $useTimestamps, bukan di sini.
    ];

    /**
     * Ambil value setting berdasarkan key.
     * Return null jika key tidak ditemukan.
     *
     * NOTE: Tidak menggunakan nama 'get' — bentrok dengan CI4 Model::get().
     */
    public function getValue(string $key): ?string
    {
        $row = $this->where('key', $key)->first();
        return $row['value'] ?? null;
    }

    /**
     * Simpan/update value setting berdasarkan key.
     * Jika key sudah ada → update (updated_at otomatis via $useTimestamps).
     * Jika belum ada    → insert (created_at & updated_at otomatis).
     *
     * NOTE: Tidak menggunakan nama 'set' — bentrok dengan CI4 Model::set().
     */
    public function setValue(string $key, string $value): void
    {
        $existing = $this->where('key', $key)->first();

        if ($existing) {
            $this->update($existing['id_setting'], ['value' => $value]);
        } else {
            $this->insert(['key' => $key, 'value' => $value]);
        }
    }
}
