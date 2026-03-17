<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateKelompokPklTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_kelompok' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'id_instansi' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true, // NULL = mandiri
            ],
            'nama_kelompok' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true, // NULL = mandiri
            ],
            'nama_pembimbing' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true, // NULL = mandiri
            ],
            'no_wa_pembimbing' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
            ],
            'tgl_mulai' => [
                'type' => 'DATE',
            ],
            'tgl_akhir' => [
                'type' => 'DATE',
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['aktif', 'selesai'],
                'default'    => 'aktif',
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

        $this->forge->addKey('id_kelompok', true);
        $this->forge->addUniqueKey('nama_kelompok');
        //                             kolom         tabel      ref          ON_UPDATE   ON_DELETE
        $this->forge->addForeignKey('id_instansi', 'instansi', 'id_instansi', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('kelompok_pkl');
    }

    public function down()
    {
        $this->forge->dropTable('kelompok_pkl');
    }
}