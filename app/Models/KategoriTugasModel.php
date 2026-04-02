<?php

namespace App\Models;

use CodeIgniter\Model;

class KategoriTugasModel extends Model
{
    protected $table            = 'kategori_tugas';
    protected $primaryKey       = 'id_kat_tugas';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';

    protected $allowedFields = [
        'nama_kat_tugas',
        'mode_pengumpulan',
    ];

    /**
     * Ambil semua kategori untuk dropdown atau list
     */
    public function getAllKategori()
    {
        return $this->orderBy('nama_kat_tugas', 'ASC')->findAll();
    }
}
