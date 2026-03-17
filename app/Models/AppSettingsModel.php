<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * AppSettingsModel
 *
 * Mengelola tabel app_settings — key-value store untuk konfigurasi aplikasi.
 * Sebelumnya: query raw tersebar di Dashboard controller.
 */
class AppSettingsModel extends Model
{
    protected $table            = 'app_settings';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';

    protected $allowedFields = [
        'key',
        'value',
        'label',
    ];

    // ── Custom Methods ──────────────────────────────────────────────

    /**
     * Ambil nilai setting by key.
     * Return null jika key belum ada di DB.
     */
    public function getValue(string $key): ?string
    {
        $row = $this->where('key', $key)->first();
        return $row ? $row['value'] : null;
    }

    /**
     * Upsert — update jika key sudah ada, insert jika belum.
     * Dipakai updateBiodataSetting() di Dashboard.
     */
    public function upsert(string $key, string $value, string $label = ''): void
    {
        $now      = date('Y-m-d H:i:s');
        $existing = $this->where('key', $key)->first();

        if ($existing) {
            $this->where('key', $key)->set([
                'value'      => $value,
                'updated_at' => $now,
            ])->update();
        } else {
            $this->insert([
                'key'        => $key,
                'value'      => $value,
                'label'      => $label,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    /**
     * Baca status form_biodata_aktif.
     * Default: true (aktif) jika belum ada di DB — aman untuk fresh install.
     */
    public function getBiodataState(): bool
    {
        $value = $this->getValue('form_biodata_aktif');
        return $value !== null ? (bool)(int)$value : true;
    }
}
