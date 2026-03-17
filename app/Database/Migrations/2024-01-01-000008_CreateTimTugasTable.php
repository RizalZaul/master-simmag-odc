<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTimTugasTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_tim' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'nama_tim' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'deskripsi' => [
                'type' => 'TEXT',
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

        $this->forge->addKey('id_tim', true);
        $this->forge->createTable('tim_tugas');
    }

    public function down()
    {
        $this->forge->dropTable('tim_tugas');
    }
}