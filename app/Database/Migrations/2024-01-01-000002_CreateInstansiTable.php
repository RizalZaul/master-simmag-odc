<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateInstansiTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_instansi' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'kategori_instansi' => [
                'type'       => 'ENUM',
                'constraint' => ['kampus', 'sekolah'],
            ],
            'nama_instansi' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
            ],
            'alamat_instansi' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'kota_instansi' => [
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

        $this->forge->addKey('id_instansi', true);
        $this->forge->createTable('instansi');
    }

    public function down()
    {
        $this->forge->dropTable('instansi');
    }
}