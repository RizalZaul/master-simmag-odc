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

        $this->forge->addKey('id_tugas', true);
        //                             kolom          tabel            ref             ON_UPDATE   ON_DELETE
        $this->forge->addForeignKey('id_user',      'users',          'id_user',      'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('id_kat_tugas', 'kategori_tugas', 'id_kat_tugas', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('tugas');
    }

    public function down()
    {
        $this->forge->dropTable('tugas');
    }
}