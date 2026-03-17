<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTugasTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_tugas' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'id_user' => [
                'type'     => 'INT',
                'unsigned' => true,
            ],
            'id_kat_tugas' => [
                'type'     => 'INT',
                'unsigned' => true,
            ],
            'nama_tugas' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
            ],
            'deskripsi' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'target_jumlah' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
            ],
            'deadline' => [
                'type' => 'DATETIME',
                'null' => true,
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

        // Primary key
        $this->forge->addKey('id_tugas', true);

        // ── Index untuk performa query dashboard ──────────────────────
        // Dipakai filter HAVING deadline >= CURDATE() / deadline < CURDATE()
        $this->forge->addKey('deadline');

        // Dipakai filter WHERE id_user = ? (query getAllWithDetail)
        $this->forge->addKey('id_user');

        // Dipakai filter WHERE id_kat_tugas = ? (JOIN ke kategori_tugas)
        $this->forge->addKey('id_kat_tugas');

        // Foreign keys
        $this->forge->addForeignKey('id_user',      'users',          'id_user',      'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('id_kat_tugas', 'kategori_tugas', 'id_kat_tugas', 'CASCADE', 'RESTRICT');

        $this->forge->createTable('tugas');
    }

    public function down()
    {
        $this->forge->dropTable('tugas');
    }
}
