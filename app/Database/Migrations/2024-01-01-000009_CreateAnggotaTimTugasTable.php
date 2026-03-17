<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAnggotaTimTugasTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_anggota_tim' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'id_tim' => [
                'type'     => 'INT',
                'unsigned' => true,
            ],
            'id_pkl' => [
                'type'     => 'INT',
                'unsigned' => true,
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

        $this->forge->addKey('id_anggota_tim', true);
        //                             kolom     tabel       ref       ON_UPDATE   ON_DELETE
        $this->forge->addForeignKey('id_tim', 'tim_tugas', 'id_tim', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('id_pkl', 'pkl',       'id_pkl', 'CASCADE', 'CASCADE');
        $this->forge->createTable('anggota_tim_tugas');
    }

    public function down()
    {
        $this->forge->dropTable('anggota_tim_tugas');
    }
}