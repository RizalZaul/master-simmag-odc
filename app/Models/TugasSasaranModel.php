<?php

namespace App\Models;

use CodeIgniter\Model;

class TugasSasaranModel extends Model
{
    protected $table            = 'tugas_sasaran';
    protected $primaryKey       = 'id_sasaran';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';

    protected $allowedFields = [
        'id_tugas',
        'target_tipe',   // enum: individu, kelompok, tim_tugas
        'id_pkl',        // Terisi jika target_tipe = individu
        'id_kelompok',   // Terisi jika target_tipe = kelompok
        'id_tim',        // Terisi jika target_tipe = tim_tugas
    ];
}
