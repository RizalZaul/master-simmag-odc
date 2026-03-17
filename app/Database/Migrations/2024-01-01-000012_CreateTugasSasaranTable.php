<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTugasSasaranTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_sasaran' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'id_tugas' => [
                'type'     => 'INT',
                'unsigned' => true,
            ],
            'target_tipe' => [
                'type'       => 'ENUM',
                'constraint' => ['individu', 'kelompok', 'tim_tugas'],
            ],
            'id_pkl' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true, // NULL jika bukan individu
            ],
            'id_kelompok' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true, // NULL jika bukan kelompok
            ],
            'id_tim' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true, // NULL jika bukan tim
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

        $this->forge->addKey('id_sasaran', true);
        //                             kolom          tabel          ref             ON_UPDATE   ON_DELETE
        $this->forge->addForeignKey('id_tugas',    'tugas',        'id_tugas',    'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('id_pkl',      'pkl',          'id_pkl',      'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('id_kelompok', 'kelompok_pkl', 'id_kelompok', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('id_tim',      'tim_tugas',    'id_tim',      'CASCADE', 'CASCADE');
        $this->forge->createTable('tugas_sasaran');
    }

    public function down()
    {
        $this->forge->dropTable('tugas_sasaran');
    }
}