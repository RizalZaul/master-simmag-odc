<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateItemTugasTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_item' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'id_pengumpulan_tgs' => [
                'type'     => 'INT',
                'unsigned' => true,
            ],
            'tipe_item' => [
                'type'       => 'ENUM',
                'constraint' => ['file', 'link'],
            ],
            'data_item' => [
                'type' => 'TEXT',
            ],
            'komentar' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'status_item' => [
                'type'       => 'ENUM',
                'constraint' => ['belum_dikirim', 'dikirim', 'revisi', 'diterima'],
                'default'    => 'belum_dikirim',
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

        $this->forge->addKey('id_item', true);
        //                             kolom                  tabel               ref                    ON_UPDATE   ON_DELETE
        $this->forge->addForeignKey('id_pengumpulan_tgs', 'pengumpulan_tugas', 'id_pengumpulan_tgs', 'CASCADE', 'CASCADE');
        $this->forge->createTable('item_tugas');
    }

    public function down()
    {
        $this->forge->dropTable('item_tugas');
    }
}