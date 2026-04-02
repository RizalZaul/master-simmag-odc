<?php

namespace App\Models;

use CodeIgniter\Model;

class AnggotaTimTugasModel extends Model
{
    protected $table            = 'anggota_tim_tugas';
    protected $primaryKey       = 'id_anggota_tim';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';

    protected $allowedFields = [
        'id_tim',
        'id_pkl',
    ];

    public function getActiveRecipientRowsByTimIds(array $targetIds): array
    {
        $targetIds = array_values(array_unique(array_filter(array_map('intval', $targetIds))));
        if ($targetIds === []) {
            return [];
        }

        return $this->db->table('anggota_tim_tugas att')
            ->select('att.id_tim, p.id_pkl, p.id_kelompok')
            ->join('pkl p', 'p.id_pkl = att.id_pkl')
            ->join('users u', 'u.id_user = p.id_user')
            ->join('kelompok_pkl k', 'k.id_kelompok = p.id_kelompok')
            ->where('u.status', 'aktif')
            ->where('k.status', 'aktif')
            ->where('k.tgl_akhir >=', date('Y-m-d'))
            ->whereIn('att.id_tim', $targetIds)
            ->get()
            ->getResultArray();
    }

    public function getActiveMemberNamesByTim(int $idTim): array
    {
        return $this->db->table('anggota_tim_tugas att')
            ->select('p.nama_lengkap')
            ->join('pkl p', 'p.id_pkl = att.id_pkl')
            ->join('users u', 'u.id_user = p.id_user', 'left')
            ->where('att.id_tim', $idTim)
            ->groupStart()
                ->where('u.id_user IS NULL', null, false)
                ->orWhere('u.status', 'aktif')
            ->groupEnd()
            ->orderBy('p.nama_lengkap', 'ASC')
            ->get()
            ->getResultArray();
    }
}
