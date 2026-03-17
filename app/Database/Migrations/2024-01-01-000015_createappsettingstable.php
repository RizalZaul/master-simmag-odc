<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration: app_settings
 *
 * Tabel key-value untuk menyimpan pengaturan global aplikasi.
 * Desain singleton per key — satu baris per setting.
 *
 * Key yang digunakan saat ini:
 *   form_biodata_aktif  → '1' (aktif) | '0' (nonaktif)
 *                         Mengontrol apakah siswa PKL dapat membuka form biodata.
 *
 * Cara baca:
 *   $db->table('app_settings')->where('key', 'form_biodata_aktif')->get()->getRow();
 *
 * Cara tulis (upsert manual karena CI4 tidak punya upsert native):
 *   Cek exists → update jika ada, insert jika belum.
 */
class CreateAppSettingsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_setting' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            // Nama setting — unik, dipakai sebagai identifier
            'key' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            // Nilai setting — TEXT agar fleksibel (angka, string, JSON kecil)
            'value' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            // Label deskriptif untuk keperluan tampilan di UI (opsional)
            'label' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id_setting', true);
        $this->forge->addUniqueKey('key'); // Satu baris per setting key
        $this->forge->createTable('app_settings');

        // ── Seed data default ─────────────────────────────────────────
        // Insert langsung di migration agar saat `php spark migrate`
        // tabel langsung siap pakai tanpa perlu jalankan seeder terpisah.
        $this->db->table('app_settings')->insertBatch([
            [
                'key'        => 'form_biodata_aktif',
                'value'      => '1',
                'label'      => 'Status Form Biodata PKL',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropTable('app_settings');
    }
}