<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePklTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_pkl' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'id_user' => [
                'type'     => 'INT',
                'unsigned' => true,
            ],
            'id_kelompok' => [
                'type'     => 'INT',
                'unsigned' => true,
            ],
            'nama_lengkap' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'nama_panggilan' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'tempat_lahir' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'tgl_lahir' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'no_wa_pkl' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
            ],
            'jenis_kelamin' => [
                'type'       => 'ENUM',
                'constraint' => ['L', 'P'],
                'null'       => true,
            ],
            'alamat' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'role_kel_pkl' => [
                'type'       => 'ENUM',
                'constraint' => ['ketua', 'anggota'],
                'null'       => true,
            ],
            'jurusan' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
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

        $this->forge->addKey('id_pkl', true);
        $this->forge->addUniqueKey('id_user'); // Enforce relasi 1:1 dengan Users
        //                             kolom        tabel        ref           ON_UPDATE   ON_DELETE
        $this->forge->addForeignKey('id_user',    'users',       'id_user',    'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('id_kelompok', 'kelompok_pkl', 'id_kelompok', 'CASCADE', 'CASCADE');
        $this->forge->createTable('pkl');
    }

    public function down()
    {
        $this->forge->dropTable('pkl');
    }
}