<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * RegistrasiModel
 *
 * Menangani penyimpanan data registrasi PKL ke database.
 * Dipanggil oleh RegistrasiController::verifyOtp() setelah OTP berhasil diverifikasi.
 *
 * Alur:
 *   1. Baca session data (step1 + step2) yang disimpan saat sendOtp()
 *   2. Insert instansi (jika baru) → kelompok_pkl → users + pkl per anggota
 *   3. Return array ['anggota_login' => [...]] berisi kredensial untuk pengiriman email
 *
 * Seluruh operasi DB dijalankan dalam satu transaksi eksplisit.
 * Jika salah satu INSERT gagal, semua perubahan di-rollback.
 */
class RegistrasiModel extends Model
{
    protected $table      = 'users'; // tabel utama (tidak dipakai langsung, hanya untuk CI4 Model)
    protected $returnType = 'array';

    // ==========================================
    // SIMPAN REGISTRASI
    // ==========================================

    /**
     * Simpan semua data registrasi ke database dalam satu transaksi.
     *
     * @param array $sessionData  Data dari session reg_data:
     *                            [
     *                              'step1' => [...],
     *                              'step2' => ['anggota' => [...]]
     *                            ]
     *
     * @return array  ['anggota_login' => [['nama_lengkap', 'email', 'username', 'password_plain'], ...]]
     *
     * @throws \Throwable  Jika transaksi gagal — controller yang handle
     */
    public function simpanRegistrasi(array $sessionData): array
    {
        $step1   = $sessionData['step1']            ?? [];
        $anggota = $sessionData['step2']['anggota'] ?? [];
        $tipePkl = $step1['tipe_pkl']               ?? 'mandiri'; // 'mandiri' | 'instansi'
        $now     = date('Y-m-d H:i:s');

        $userModel = new UserModel();
        $db        = $this->db;

        $db->transBegin();

        try {
            // ── 1. Handle instansi (hanya untuk tipe instansi) ───────────
            $idInstansi = null;

            if ($tipePkl === 'instansi') {
                if (! empty($step1['id_instansi'])) {
                    // Instansi sudah ada — pakai ID yang dipilih
                    $idInstansi = (int) $step1['id_instansi'];
                } else {
                    // Instansi baru — insert terlebih dahulu
                    // Nilai kategori_instansi sudah dalam format DB ENUM ('kampus' / 'sekolah')
                    // sesuai value pada select di form HTML — tidak perlu konversi.
                    $db->table('instansi')->insert([
                        'kategori_instansi' => $step1['kategori_instansi'] ?? '',
                        'nama_instansi'     => trim($step1['nama_instansi'] ?? ''),
                        'alamat_instansi'   => trim($step1['alamat_instansi'] ?? ''),
                        'kota_instansi'     => trim($step1['kota_instansi'] ?? ''),
                        'created_at'        => $now,
                        'updated_at'        => $now,
                    ]);
                    $idInstansi = $db->insertID();
                }
            }

            // ── 2. Insert kelompok_pkl ────────────────────────────────────
            $tglMulai = $this->_parseDate($step1['tgl_mulai'] ?? '');
            $tglAkhir = $this->_parseDate($step1['tgl_akhir'] ?? '');

            if ($tipePkl === 'instansi') {
                $db->table('kelompok_pkl')->insert([
                    'id_instansi'      => $idInstansi,
                    'nama_kelompok'    => trim($step1['nama_kelompok']    ?? ''),
                    'nama_pembimbing'  => trim($step1['nama_pembimbing']  ?? ''),
                    'no_wa_pembimbing' => trim($step1['no_wa_pembimbing'] ?? ''),
                    'tgl_mulai'        => $tglMulai,
                    'tgl_akhir'        => $tglAkhir,
                    'status'           => 'aktif',
                    'created_at'       => $now,
                    'updated_at'       => $now,
                ]);
            } else {
                // Mandiri — semua kolom instansi/pembimbing NULL
                $db->table('kelompok_pkl')->insert([
                    'id_instansi'      => null,
                    'nama_kelompok'    => null,
                    'nama_pembimbing'  => null,
                    'no_wa_pembimbing' => null,
                    'tgl_mulai'        => $tglMulai,
                    'tgl_akhir'        => $tglAkhir,
                    'status'           => 'aktif',
                    'created_at'       => $now,
                    'updated_at'       => $now,
                ]);
            }

            $idKelompok   = $db->insertID();
            $anggotaLogin = [];

            // ── 3. Insert users + pkl per anggota ─────────────────────────
            foreach ($anggota as $i => $a) {
                $email         = strtolower(trim($a['email'] ?? ''));
                $base          = explode('@', $email)[0];
                $username      = $userModel->generateUniqueUsername($base);
                $passwordPlain = 'Pkl@' . random_int(100000, 999999);

                // ARCH-05 FIX: Gunakan $userModel->insert() — bukan $db->table('users')->insert() —
                // agar beforeInsert callback hashPassword() berjalan otomatis.
                // Password plain dikirim; callback yang akan mem-bcrypt-nya.
                $userModel->insert([
                    'email'    => $email,
                    'username' => $username,
                    'password' => $passwordPlain,
                    'role'     => 'pkl',
                    'status'   => 'aktif',
                ]);
                $idUser = $userModel->getInsertID();

                // Tentukan role dalam kelompok
                // Fallback: anggota pertama (index 0) = ketua jika field 'role' tidak ada
                $roleKelompok = null;
                if ($tipePkl === 'instansi') {
                    $roleKelompok = $a['role'] ?? ($i === 0 ? 'ketua' : 'anggota');
                }

                // Insert ke tabel pkl
                $db->table('pkl')->insert([
                    'id_user'        => $idUser,
                    'id_kelompok'    => $idKelompok,
                    'nama_lengkap'   => trim($a['nama_lengkap']   ?? ''),
                    'nama_panggilan' => trim($a['nama_panggilan'] ?? ''),
                    'tempat_lahir'   => trim($a['tempat_lahir']   ?? '') ?: null,
                    'tgl_lahir'      => $this->_parseDate($a['tgl_lahir'] ?? ''),
                    'no_wa_pkl'      => trim($a['no_wa']          ?? '') ?: null,
                    'jenis_kelamin'  => ($a['jenis_kelamin'] ?? '') === 'Laki-laki' ? 'L' : 'P',
                    'alamat'         => trim($a['alamat']         ?? '') ?: null,
                    'role_kel_pkl'   => $roleKelompok,
                    // jurusan hanya untuk instansi
                    'jurusan'        => $tipePkl === 'instansi'
                        ? (trim($a['jurusan'] ?? '') ?: null)
                        : null,
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ]);

                $anggotaLogin[] = [
                    'nama_lengkap'   => trim($a['nama_lengkap']),
                    'email'          => $email,
                    'username'       => $username,
                    'password_plain' => $passwordPlain,
                    'role'           => $roleKelompok,
                ];
            }

            $db->transCommit();

            return ['anggota_login' => $anggotaLogin];
        } catch (\Throwable $e) {
            $db->transRollback();
            throw $e; // Re-throw — RegistrasiController yang handle dan log
        }
    }


    // ==========================================
    // PRIVATE HELPERS
    // ==========================================

    /**
     * Parse berbagai format tanggal ke Y-m-d.
     *
     * Mendukung:
     *   - Y-m-d  (HTML date input default)
     *   - d-m-Y  (datepicker format Indonesia)
     *   - d/m/Y  (format slash)
     */
    private function _parseDate(string $dateStr): ?string
    {
        if (! $dateStr) return null;

        // Sudah dalam format Y-m-d
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStr)) {
            return $dateStr;
        }

        // Format d-m-Y
        $date = \DateTime::createFromFormat('d-m-Y', $dateStr);
        if ($date && $date->format('d-m-Y') === $dateStr) {
            return $date->format('Y-m-d');
        }

        // Format d/m/Y
        $date = \DateTime::createFromFormat('d/m/Y', $dateStr);
        if ($date && $date->format('d/m/Y') === $dateStr) {
            return $date->format('Y-m-d');
        }

        return null;
    }
}
