<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run()
    {
        $now = date('Y-m-d H:i:s');

        // ── Insert tabel users ──
        $this->db->table('users')->insert([
            'email'      => 'admin@pkl.test',
            'username'   => 'admin',
            'password'   => password_hash('Admin@1234', PASSWORD_BCRYPT),
            'role'       => 'admin',
            'status'     => 'aktif',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $idUser = $this->db->insertID();

        // ── Insert tabel admin ──
        $this->db->table('admin')->insert([
            'id_user'        => $idUser,
            'nama_lengkap'   => 'Administrator Utama',
            'nama_panggilan' => 'Admin',
            'no_wa_admin'    => '08200000001',
            'alamat'         => 'Jl. Admin No. 1, Surabaya',
            'created_at'     => $now,
            'updated_at'     => $now,
        ]);

        echo "  [AdminSeeder] 1 admin inserted. (id_user: {$idUser})\n";
    }
}