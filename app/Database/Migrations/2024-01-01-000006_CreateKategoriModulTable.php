<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateKategoriModulTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_kat_m' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'nama_kat_m' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
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

        $this->forge->addKey('id_kat_m', true);
        $this->forge->createTable('kategori_modul');
    }

    public function down()
    {
        $this->forge->dropTable('kategori_modul');
    }
}