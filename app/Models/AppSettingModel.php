<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * AppSettingModel
 *
 * Model untuk tabel `app_settings`.
 * Menyimpan konfigurasi global aplikasi sebagai key-value.
 *
 * Cara pakai:
 *   $settingModel = new AppSettingModel();
 *   $val = $settingModel->get('form_biodata_aktif'); // return '1' atau '0'
 *   $settingModel->set('form_biodata_aktif', '0');
 */
class AppSettingModel extends Model
{
    protected $table            = 'app_settings';
    protected $primaryKey       = 'id_setting';
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

    // Cache in-memory agar tidak query ulang dalam satu request
    private static array $cache = [];

    // ── Custom Methods ──────────────────────────────────────────────

    /**
     * Ambil nilai setting berdasarkan key.
     * Return null jika key tidak ditemukan.
     */
    public function get(string $key): ?string
    {
        if (array_key_exists($key, self::$cache)) {
            return self::$cache[$key];
        }

        $row = $this->where('key', $key)->first();
        self::$cache[$key] = $row['value'] ?? null;

        return self::$cache[$key];
    }

    /**
     * Set nilai setting (upsert: update jika ada, insert jika belum).
     */
    public function setSetting(string $key, string $value): void
    {
        $existing = $this->where('key', $key)->first();

        if ($existing) {
            $this->update($existing['id_setting'], ['value' => $value]);
        } else {
            $this->insert(['key' => $key, 'value' => $value]);
        }

        self::$cache[$key] = $value;
    }

    /**
     * Ambil semua settings sebagai array asosiatif [key => value].
     * Dipakai halaman pengaturan admin.
     */
    public function getAll(): array
    {
        $rows = $this->orderBy('key', 'ASC')->findAll();
        return array_column($rows, 'value', 'key');
    }

    /**
     * Ambil semua settings lengkap dengan label.
     * Dipakai untuk tampilan tabel setting admin.
     */
    public function getAllWithLabel(): array
    {
        return $this->orderBy('key', 'ASC')->findAll();
    }

    /**
     * Helper: cek apakah form biodata PKL sedang aktif.
     */
    public function isFormBiodataAktif(): bool
    {
        return $this->get('form_biodata_aktif') === '1';
    }
}
