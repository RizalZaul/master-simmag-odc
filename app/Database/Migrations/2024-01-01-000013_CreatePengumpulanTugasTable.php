<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePengumpulanTugasTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_pengumpulan_tgs' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'id_tugas' => [
                'type'     => 'INT',
                'unsigned' => true,
            ],
            'id_pkl' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
            ],
            'id_kelompok' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
            ],
            'id_tim' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
            ],
            'tgl_pengumpulan' => [
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

        $this->forge->addKey('id_pengumpulan_tgs', true);

        //                              kolom          tabel          ref            ON_UPDATE   ON_DELETE
        $this->forge->addForeignKey('id_tugas',    'tugas',        'id_tugas',    'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('id_pkl',      'pkl',          'id_pkl',      'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('id_kelompok', 'kelompok_pkl', 'id_kelompok', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('id_tim',      'tim_tugas',    'id_tim',      'CASCADE', 'CASCADE');
        $this->forge->createTable('pengumpulan_tugas');
    }

    public function down()
    {
        $this->forge->dropTable('pengumpulan_tugas');
    }
}
