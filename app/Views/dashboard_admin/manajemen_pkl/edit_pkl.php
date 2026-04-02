<?php

/**
 * Views/dashboard_admin/manajemen_pkl/edit_pkl.php
 * Edit 1 anggota PKL — biodata + email + password (opsional)
 */

$isInstansi = ! empty($kelompok['id_instansi']);
$jk         = $pkl['jenis_kelamin'] ?? '';
$tglLahir   = $pkl['tgl_lahir']     ?? '';

function tglIndoShortE(?string $d): string
{
    if (!$d) return '-';
    $bln = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    $p   = explode('-', $d);
    return sprintf('%02d %s %s', (int)$p[2], $bln[(int)$p[1]], $p[0]);
}
?>

<div class="detail-pkl-wrap">

    <div class="detail-back-row">
        <a href="<?= base_url('admin/manajemen-pkl/pkl/detail/' . $pkl['id_pkl']) ?>" class="detail-back-link">
            <i class="fas fa-arrow-left"></i> Kembali ke Detail PKL
        </a>
    </div>

    <div class="detail-page-header">
        <div class="detail-page-title">
            <i class="fas fa-pen"></i>
            <h2>Edit PKL — <?= esc($pkl['nama_panggilan'] ?? $pkl['nama_lengkap']) ?></h2>
        </div>
    </div>

    <?php if (count($anggota) > 1): ?>
        <!-- ── Anggota lain (ringkas, read-only) ── -->
        <div class="detail-card">
            <div class="detail-card-header">
                <i class="fas fa-users"></i> Anggota Lain dalam Kelompok
            </div>
            <div class="anggota-ringkas-list">
                <?php foreach ($anggota as $ang):
                    if ($ang['id_pkl'] === $pkl['id_pkl']) continue;
                    $roleLabel = $ang['role_kel_pkl'] === 'ketua' ? 'Ketua' : 'Anggota';
                    $roleCls   = $ang['role_kel_pkl'] === 'ketua' ? 'badge-role-ketua' : 'badge-role-anggota';
                ?>
                    <div class="anggota-ringkas-item">
                        <i class="fas fa-user-circle"></i>
                        <span><?= esc($ang['nama_lengkap']) ?></span>
                        <span class="<?= $roleCls ?>"><?= $roleLabel ?></span>
                        <a href="<?= base_url('admin/manajemen-pkl/pkl/edit/' . $ang['id_pkl']) ?>"
                            class="btn-anggota-edit-link">
                            <i class="fas fa-pen"></i>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- ── Form Edit ── -->
    <div class="detail-card">
        <div class="detail-card-header">
            <i class="fas fa-id-card"></i>
            Edit: <?= esc($pkl['nama_lengkap']) ?>
            <?php if ($pkl['role_kel_pkl']): ?>
                <span class="<?= $pkl['role_kel_pkl'] === 'ketua' ? 'badge-role-ketua' : 'badge-role-anggota' ?>">
                    <?= $pkl['role_kel_pkl'] === 'ketua' ? 'Ketua' : 'Anggota' ?>
                </span>
            <?php endif; ?>
        </div>

        <form id="formEditPkl"
            action="<?= base_url('admin/manajemen-pkl/pkl/update/' . $pkl['id_pkl']) ?>"
            method="post">
            <?= csrf_field() ?>

            <div class="mpkl-form-body">

                <div class="mpkl-form-field">
                    <label class="mpkl-label"><i class="fas fa-user"></i> Nama Lengkap <span class="required-star">*</span></label>
                    <input type="text" name="nama_lengkap" class="mpkl-input" required
                        value="<?= esc($pkl['nama_lengkap']) ?>">
                </div>

                <div class="mpkl-form-field">
                    <label class="mpkl-label"><i class="fas fa-smile"></i> Nama Panggilan</label>
                    <input type="text" name="nama_panggilan" class="mpkl-input"
                        value="<?= esc($pkl['nama_panggilan'] ?? '') ?>">
                </div>

                <div class="mpkl-form-field">
                    <label class="mpkl-label"><i class="fas fa-map-pin"></i> Tempat Lahir</label>
                    <input type="text" name="tempat_lahir" class="mpkl-input"
                        value="<?= esc($pkl['tempat_lahir'] ?? '') ?>">
                </div>

                <div class="mpkl-form-field">
                    <label class="mpkl-label"><i class="fas fa-birthday-cake"></i> Tanggal Lahir</label>
                    <input type="text" name="tgl_lahir" id="editTglLahir" class="mpkl-input"
                        value="<?= esc($tglLahir) ?>" placeholder="Pilih tanggal lahir">
                </div>

                <div class="mpkl-form-field mpkl-form-field-full">
                    <label class="mpkl-label"><i class="fas fa-map-marker-alt"></i> Alamat</label>
                    <input type="text" name="alamat" class="mpkl-input"
                        value="<?= esc($pkl['alamat'] ?? '') ?>">
                </div>

                <div class="mpkl-form-field">
                    <label class="mpkl-label"><i class="fab fa-whatsapp"></i> No WA</label>
                    <input type="text" name="no_wa" class="mpkl-input"
                        value="<?= esc($pkl['no_wa_pkl'] ?? '') ?>">
                </div>

                <div class="mpkl-form-field">
                    <label class="mpkl-label"><i class="fas fa-venus-mars"></i> Jenis Kelamin</label>
                    <select name="jenis_kelamin" class="mpkl-select">
                        <option value="">-- Pilih --</option>
                        <option value="L" <?= $jk === 'L' ? 'selected' : '' ?>>Laki-laki</option>
                        <option value="P" <?= $jk === 'P' ? 'selected' : '' ?>>Perempuan</option>
                    </select>
                </div>

                <?php if ($isInstansi): ?>
                    <div class="mpkl-form-field">
                        <label class="mpkl-label"><i class="fas fa-graduation-cap"></i> Jurusan</label>
                        <input type="text" name="jurusan" class="mpkl-input"
                            value="<?= esc($pkl['jurusan'] ?? '') ?>">
                    </div>
                <?php endif; ?>

                <!-- Email -->
                <div class="mpkl-form-field">
                    <label class="mpkl-label"><i class="fas fa-envelope"></i> Email <span class="required-star">*</span></label>
                    <input type="email" name="email" class="mpkl-input" required
                        value="<?= esc($user->email ?? '') ?>">
                </div>

                <!-- Password (opsional) -->
                <div class="mpkl-form-field">
                    <label class="mpkl-label">
                        <i class="fas fa-key"></i> Password Baru
                        <span class="mpkl-hint" style="font-size:.75rem;color:var(--text-muted);">(kosongkan jika tidak diubah)</span>
                    </label>
                    <div style="position:relative">
                        <input type="password" name="password_baru" id="editPasswordBaru"
                            class="mpkl-input" placeholder="Isi jika ingin reset password"
                            style="padding-right:42px">
                        <button type="button" class="btn-toggle-pw" data-target="editPasswordBaru">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

            </div>

            <div class="mpkl-form-footer">
                <a href="<?= base_url('admin/manajemen-pkl/pkl/detail/' . $pkl['id_pkl']) ?>"
                    class="btn-mpkl-cancel">
                    <i class="fas fa-times"></i> Batal
                </a>
                <button type="submit" class="btn-mpkl-submit" id="btnSimpanEdit">
                    <i class="fas fa-save"></i> Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>