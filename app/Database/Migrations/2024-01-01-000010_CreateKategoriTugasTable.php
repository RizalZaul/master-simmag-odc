<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateKategoriTugasTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_kat_tugas' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'nama_kat_tugas' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'mode_pengumpulan' => [
                'type'       => 'ENUM',
                'constraint' => ['individu', 'kelompok'],
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

        $this->forge->addKey('id_kat_tugas', true);
        $this->forge->createTable('kategori_tugas');
    }

    public function down()
    {
        $this->forge->dropTable('kategori_tugas');
    }
}