<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateModulTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_modul' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'id_kat_m' => [
                'type'     => 'INT',
                'unsigned' => true,
            ],
            'nama_modul' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
            ],
            'ket_modul' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'tipe' => [
                'type'       => 'ENUM',
                'constraint' => ['link', 'file'],
            ],
            'path' => [
                'type' => 'TEXT',
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

        $this->forge->addKey('id_modul', true);
        //                             kolom      tabel            ref        ON_UPDATE   ON_DELETE
        $this->forge->addForeignKey('id_kat_m', 'kategori_modul', 'id_kat_m', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('modul');
    }

    public function down()
    {
        $this->forge->dropTable('modul');
    }
}