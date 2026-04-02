<?php

namespace App\Models;

use CodeIgniter\Model;

class TimTugasModel extends Model
{
    protected $table            = 'tim_tugas';
    protected $primaryKey       = 'id_tim';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';

    protected $allowedFields = [
        'nama_tim',
        'deskripsi',
    ];

    /**
     * Ambil semua tim beserta:
     *   - jumlah_anggota : COUNT dari anggota_tim_tugas
     *   - jumlah_tugas   : COUNT berapa tugas yang menggunakan tim ini (via tugas_sasaran)
     *
     * Dipakai di tab Tim Tugas halaman pilih-sasaran.
     */
    public function getAllWithStats(): array
    {
        return $this->db->table('tim_tugas t')
            ->select([
                't.id_tim',
                't.nama_tim',
                't.deskripsi',
                't.created_at',
            ])
            ->select('COUNT(DISTINCT att.id_pkl) AS jumlah_anggota', false)
            ->select('COUNT(DISTINCT ts.id_tugas) AS jumlah_tugas', false)
            ->join('anggota_tim_tugas att', 'att.id_tim = t.id_tim', 'left')
            ->join('tugas_sasaran ts',      'ts.id_tim  = t.id_tim AND ts.target_tipe = \'tim_tugas\'', 'left')
            ->groupBy('t.id_tim, t.nama_tim, t.deskripsi, t.created_at')
            ->orderBy('t.created_at', 'DESC')
            ->get()->getResultArray();
    }

    public function getPengumpulanRowsForAdmin(): array
    {
        return $this->db->table('tugas_sasaran ts')
            ->select('ts.id_tugas, ts.id_tim, t.nama_tugas, t.deadline')
            ->select('kt.nama_kat_tugas, kt.mode_pengumpulan')
            ->select('tt.nama_tim AS nama_target')
            ->select("GROUP_CONCAT(DISTINCT pt.id_pengumpulan_tgs ORDER BY pt.id_pengumpulan_tgs ASC SEPARATOR ',') AS pengumpulan_ids", false)
            ->select('MAX(pt.tgl_pengumpulan) AS waktu_pengumpulan', false)
            ->join('tugas t', 't.id_tugas = ts.id_tugas')
            ->join('kategori_tugas kt', 'kt.id_kat_tugas = t.id_kat_tugas')
            ->join('tim_tugas tt', 'tt.id_tim = ts.id_tim', 'left')
            ->join('pengumpulan_tugas pt', 'pt.id_tugas = ts.id_tugas AND pt.id_tim = ts.id_tim', 'left')
            ->where('kt.mode_pengumpulan', 'kelompok')
            ->where('ts.target_tipe', 'tim_tugas')
            ->groupBy('ts.id_tugas, ts.id_tim, t.nama_tugas, t.deadline, kt.nama_kat_tugas, kt.mode_pengumpulan, tt.nama_tim')
            ->orderBy('t.deadline', 'ASC')
            ->orderBy('tt.nama_tim', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getPengumpulanDetailRow(int $idTugas, int $idTim): ?array
    {
        return $this->db->table('tugas_sasaran ts')
            ->select('ts.id_tugas, ts.id_tim, t.nama_tugas, t.deskripsi, t.deadline')
            ->select('kt.nama_kat_tugas, kt.mode_pengumpulan')
            ->select('tt.nama_tim AS nama_target')
            ->select("GROUP_CONCAT(DISTINCT pt.id_pengumpulan_tgs ORDER BY pt.id_pengumpulan_tgs ASC SEPARATOR ',') AS pengumpulan_ids", false)
            ->select('MAX(pt.tgl_pengumpulan) AS waktu_pengumpulan', false)
            ->join('tugas t', 't.id_tugas = ts.id_tugas')
            ->join('kategori_tugas kt', 'kt.id_kat_tugas = t.id_kat_tugas')
            ->join('tim_tugas tt', 'tt.id_tim = ts.id_tim', 'left')
            ->join('pengumpulan_tugas pt', 'pt.id_tugas = ts.id_tugas AND pt.id_tim = ts.id_tim', 'left')
            ->where('ts.id_tugas', $idTugas)
            ->where('ts.id_tim', $idTim)
            ->where('ts.target_tipe', 'tim_tugas')
            ->where('kt.mode_pengumpulan', 'kelompok')
            ->groupBy('ts.id_tugas, ts.id_tim, t.nama_tugas, t.deskripsi, t.deadline, kt.nama_kat_tugas, kt.mode_pengumpulan, tt.nama_tim')
            ->get()
            ->getRowArray();
    }

    public function getTugasTargetDetail(int $idTim): ?array
    {
        return $this->db->table('tim_tugas t')
            ->select('t.nama_tim, t.deskripsi')
            ->select('COUNT(DISTINCT att.id_pkl) AS jumlah_anggota', false)
            ->join('anggota_tim_tugas att', 'att.id_tim = t.id_tim', 'left')
            ->where('t.id_tim', $idTim)
            ->groupBy('t.id_tim, t.nama_tim, t.deskripsi')
            ->get()
            ->getRowArray();
    }
}
