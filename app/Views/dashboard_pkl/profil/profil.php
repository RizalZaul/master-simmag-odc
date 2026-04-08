<?php

/**
 * Views/dashboard_pkl/profil/profil.php
 *
 * Variables dari ProfilPklController::index():
 *   $pkl     → array dari PklModel::getDataDiri()
 *              keys: id_pkl, nama_lengkap, nama_panggilan, tempat_lahir,
 *                    tgl_lahir, no_wa_pkl, jenis_kelamin, alamat, jurusan,
 *                    role_kel_pkl, id_instansi, nama_kelompok, tgl_mulai,
 *                    tgl_akhir, status_kelompok, nama_pembimbing,
 *                    no_wa_pembimbing, nama_instansi, alamat_instansi,
 *                    kategori_instansi, kota_instansi
 *   $user    → object dari UserModel
 *   $anggota → array [['nama_lengkap'=>..., 'role_kel_pkl'=>...], ...]
 *
 * BUG-FIX: Hapus deklarasi tglFmt() & hitungDurasi() — sudah ada di
 *           tgl_helper.php yang di-autoload global (Autoload.php $helpers).
 *           tglFmt() diganti tglShortIndo(), hitungDurasi() nama sama.
 */

// ── REMOVED: tglFmt() & hitungDurasi() — pakai tgl_helper.php ────────────
// Fungsi-fungsi ini sudah tersedia global via autoload, mendefinisikan ulang
// di sini menyebabkan "Cannot redeclare" ErrorException.

$adaInstansi   = ! empty($pkl['id_instansi']);
$namaLengkap   = esc($pkl['nama_lengkap']   ?? '');
$namaPanggilan = esc($pkl['nama_panggilan'] ?? '');
$jenisKelamin  = $pkl['jenis_kelamin'] ?? null;
$jkLabel       = $jenisKelamin === 'L' ? 'Laki-laki' : ($jenisKelamin === 'P' ? 'Perempuan' : '-');
$tempat        = esc($pkl['tempat_lahir'] ?? '');
$tglLahir      = esc($pkl['tgl_lahir']    ?? '');
$tglLahirFmt   = tglShortIndo($pkl['tgl_lahir'] ?? null);   // ← was: tglFmt()
$noWa          = esc($pkl['no_wa_pkl']    ?? '');
$alamat        = esc($pkl['alamat']       ?? '');
$jurusan       = esc($pkl['jurusan']      ?? '');
$username      = esc($user->username      ?? '');
$email         = esc($user->email         ?? '');
$roleKel       = $pkl['role_kel_pkl'] ?? null;
$roleBadge     = $roleKel === 'ketua' ? 'Ketua Kelompok' : ($roleKel === 'anggota' ? 'Anggota' : '-');

$tglMulai      = $pkl['tgl_mulai']  ?? null;
$tglAkhir      = $pkl['tgl_akhir']  ?? null;
$durasi        = ($tglMulai && $tglAkhir) ? hitungDurasi($tglMulai, $tglAkhir) : '-';
$statusKel     = $pkl['status_kelompok'] ?? 'aktif';

$kategoriLabel = 'Mandiri';
if ($adaInstansi) {
    $kategoriLabel = match ($pkl['kategori_instansi'] ?? '') {
        'kampus'  => 'Kampus',
        'sekolah' => 'Sekolah',
        default   => 'Instansi',
    };
}

$swalSuccess = session()->getFlashdata('swal_success');
$swalError   = session()->getFlashdata('swal_error');
?>

<?php if ($swalSuccess): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: '<?= addslashes($swalSuccess) ?>',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
        });
    </script>
<?php endif; ?>

<?php if ($swalError): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'error',
                title: 'Gagal!',
                text: '<?= addslashes($swalError) ?>',
                confirmButtonColor: 'var(--primary)'
            });
        });
    </script>
<?php endif; ?>

<!-- ── Welcome Card ── -->
<div class="welcome-card">
    <h2 class="page-heading">Profil Saya</h2>
    <p class="page-subheading">Data diri dan informasi akun</p>
</div>

<!-- ══ HERO ROW: Avatar + Durasi PKL ══ -->
<div class="profil-hero-row">

    <!-- Avatar Card -->
    <div class="profil-avatar-card">
        <div class="profil-avatar-circle">
            <i class="fas fa-user-graduate"></i>
        </div>
        <div class="profil-avatar-info">
            <h3 class="profil-nama"><?= $namaLengkap ?></h3>
            <span class="profil-username-chip">
                <i class="fas fa-at"></i><?= $username ?>
            </span>
            <span class="profil-email-chip">
                <i class="fas fa-envelope"></i><?= $email ?>
            </span>
        </div>
    </div>

    <!-- Durasi PKL Card -->
    <div class="profil-durasi-card">
        <div class="profil-durasi-header">
            <i class="fas fa-calendar-alt"></i>
            <span>Informasi PKL</span>
            <span class="badge-status-kel <?= $statusKel === 'aktif' ? 'aktif' : 'selesai' ?>">
                <?= $statusKel === 'aktif' ? 'Aktif' : 'Selesai' ?>
            </span>
        </div>
        <div class="profil-durasi-body">
            <div class="durasi-item">
                <span class="durasi-label">Periode</span>
                <span class="durasi-value">
                    <?= tglShortIndo($tglMulai) ?> — <?= tglShortIndo($tglAkhir) ?>
                </span>
            </div>
            <div class="durasi-item">
                <span class="durasi-label">Durasi</span>
                <span class="durasi-value"><?= $durasi ?></span>
            </div>
            <div class="durasi-item">
                <span class="durasi-label">Kategori</span>
                <span class="durasi-value">
                    <span class="badge-kategori <?= strtolower($kategoriLabel) ?>">
                        <?= $kategoriLabel ?>
                    </span>
                </span>
            </div>
        </div>
    </div>

</div><!-- end .profil-hero-row -->

<!-- ══ SECTION: Informasi Pribadi ══ -->
<div class="profil-section">
    <div class="profil-section-header">
        <div class="profil-section-title">
            <i class="fas fa-id-card"></i>
            <span>Informasi Pribadi</span>
        </div>
        <button class="btn-profil-edit" id="btnEditBiodata" type="button">
            <i class="fas fa-pen"></i> Edit
        </button>
    </div>

    <form action="<?= base_url('pkl/profil/biodata') ?>" method="post" id="formBiodata" novalidate>
        <?= csrf_field() ?>

        <div class="profil-field-grid">

            <!-- Nama Lengkap -->
            <div class="profil-field">
                <label><i class="fas fa-user"></i> Nama Lengkap <span class="required-star">*</span></label>
                <div class="profil-field-display" id="displayNamaLengkap"><?= $namaLengkap ?: '-' ?></div>
                <input type="text" name="nama_lengkap" class="profil-input"
                    value="<?= $namaLengkap ?>" id="inputNamaLengkap" style="display:none" required>
            </div>

            <!-- Nama Panggilan -->
            <div class="profil-field">
                <label><i class="fas fa-smile"></i> Nama Panggilan <span class="required-star">*</span></label>
                <div class="profil-field-display" id="displayNamaPanggilan"><?= $namaPanggilan ?: '-' ?></div>
                <input type="text" name="nama_panggilan" class="profil-input"
                    value="<?= $namaPanggilan ?>" id="inputNamaPanggilan" style="display:none" required>
            </div>

            <!-- Username (locked) -->
            <div class="profil-field">
                <label>
                    <i class="fas fa-at"></i> Username
                    <i class="fas fa-lock field-lock-icon" title="Username tidak dapat diubah"></i>
                </label>
                <div class="profil-field-display field-locked"><?= $username ?></div>
            </div>

            <!-- Email (locked) -->
            <div class="profil-field">
                <label>
                    <i class="fas fa-envelope"></i> Email
                    <i class="fas fa-lock field-lock-icon" title="Email tidak dapat diubah sendiri"></i>
                </label>
                <div class="profil-field-display field-locked"><?= $email ?></div>
            </div>

            <!-- Jenis Kelamin -->
            <div class="profil-field">
                <label><i class="fas fa-venus-mars"></i> Jenis Kelamin <span class="required-star">*</span></label>
                <div class="profil-field-display" id="displayJenisKelamin"><?= $jkLabel ?></div>
                <select name="jenis_kelamin" class="profil-input" id="inputJenisKelamin" style="display:none" required>
                    <option value="" <?= $jenisKelamin === '' ? 'selected' : '' ?> disabled>-- Pilih --</option>
                    <option value="L" <?= $jenisKelamin === 'L' ? 'selected' : '' ?>>Laki-laki</option>
                    <option value="P" <?= $jenisKelamin === 'P' ? 'selected' : '' ?>>Perempuan</option>
                </select>
            </div>

            <!-- No. WhatsApp -->
            <div class="profil-field">
                <label><i class="fab fa-whatsapp"></i> No. WhatsApp <span class="required-star">*</span></label>
                <div class="profil-field-display" id="displayNoWa"><?= $noWa ?: '-' ?></div>
                <input type="text" name="no_wa" class="profil-input"
                    value="<?= $noWa ?>" id="inputNoWa" style="display:none" required>
            </div>

            <!-- Tempat Lahir -->
            <div class="profil-field">
                <label><i class="fas fa-map-pin"></i> Tempat Lahir <span class="required-star">*</span></label>
                <div class="profil-field-display" id="displayTempatLahir"><?= $tempat ?: '-' ?></div>
                <input type="text" name="tempat_lahir" class="profil-input"
                    value="<?= $tempat ?>" id="inputTempatLahir" style="display:none" required>
            </div>

            <!-- Tanggal Lahir (Flatpickr — altInput untuk tampil "d M Y") -->
            <div class="profil-field">
                <label><i class="fas fa-birthday-cake"></i> Tanggal Lahir <span class="required-star">*</span></label>
                <div class="profil-field-display" id="displayTglLahir"><?= $tglLahirFmt ?></div>
                <div id="wrapTglLahir" style="display:none">
                    <input type="text" name="tgl_lahir" class="profil-input"
                        id="inputTglLahir" value="<?= $tglLahir ?>"
                        placeholder="Pilih tanggal lahir" required>
                </div>
            </div>

            <!-- Alamat (full width) -->
            <div class="profil-field profil-field-full">
                <label><i class="fas fa-map-marker-alt"></i> Alamat <span class="required-star">*</span></label>
                <div class="profil-field-display multiline" id="displayAlamat"><?= $alamat !== '' ? nl2br($alamat) : '-' ?></div>
                <textarea name="alamat" class="profil-input profil-textarea"
                    id="inputAlamat" style="display:none" rows="3" maxlength="100" required><?= $alamat ?></textarea>
            </div>

            <?php if ($adaInstansi): ?>
                <!-- Jurusan (hanya tampil jika ada instansi, editable) -->
                <div class="profil-field profil-field-full">
                    <label><i class="fas fa-graduation-cap"></i> Jurusan <span class="required-star">*</span></label>
                    <div class="profil-field-display" id="displayJurusan"><?= $jurusan ?: '-' ?></div>
                    <input type="text" name="jurusan" class="profil-input"
                        value="<?= $jurusan ?>" id="inputJurusan" style="display:none" required>
                </div>
            <?php else: ?>
                <!-- Hidden field agar jurusan tetap terkirim (preserve value) -->
                <input type="hidden" name="jurusan" value="<?= $jurusan ?>">
            <?php endif; ?>

        </div>

        <div class="profil-edit-actions" id="actionsBiodata" style="display:none">
            <button type="button" class="btn-profil-cancel" id="btnCancelBiodata">
                <i class="fas fa-times"></i> Batal
            </button>
            <button type="submit" class="btn-profil-save">
                <i class="fas fa-check"></i> Simpan
            </button>
        </div>
    </form>
</div>

<!-- ══ SECTION: Informasi Instansi & Kelompok (hanya jika ada instansi) ══ -->
<?php if ($adaInstansi): ?>
    <div class="profil-section">
        <div class="profil-section-header">
            <div class="profil-section-title">
                <i class="fas fa-building"></i>
                <span>Informasi Instansi &amp; Kelompok</span>
            </div>
            <span class="section-readonly-badge">
                <i class="fas fa-lock"></i> Dikelola Admin
            </span>
        </div>

        <div class="profil-field-grid">

            <!-- Nama Instansi -->
            <div class="profil-field">
                <label><i class="fas fa-university"></i> Nama Instansi</label>
                <div class="profil-field-display field-locked">
                    <?= esc($pkl['nama_instansi'] ?? '-') ?>
                </div>
            </div>

            <!-- Kota Instansi -->
            <div class="profil-field">
                <label><i class="fas fa-city"></i> Kota</label>
                <div class="profil-field-display field-locked">
                    <?= esc($pkl['kota_instansi'] ?? '-') ?>
                </div>
            </div>

            <!-- Alamat Instansi (full width) -->
            <div class="profil-field profil-field-full">
                <label><i class="fas fa-map-marker-alt"></i> Alamat Instansi</label>
                <div class="profil-field-display field-locked multiline">
                    <?= ! empty($pkl['alamat_instansi']) ? nl2br(esc($pkl['alamat_instansi'])) : '-' ?>
                </div>
            </div>

            <!-- Nama Kelompok -->
            <div class="profil-field">
                <label><i class="fas fa-users"></i> Nama Kelompok</label>
                <div class="profil-field-display field-locked">
                    <?= esc($pkl['nama_kelompok'] ?? '-') ?>
                </div>
            </div>

            <!-- Role dalam Kelompok -->
            <div class="profil-field">
                <label>
                    <i class="fas fa-user-tag"></i> Role dalam Kelompok
                    <i class="fas fa-lock field-lock-icon" title="Ditentukan oleh admin"></i>
                </label>
                <div class="profil-field-display field-locked">
                    <span class="badge-role <?= $roleKel ?? 'none' ?>">
                        <?= $roleBadge ?>
                    </span>
                </div>
            </div>

            <!-- Nama Pembimbing -->
            <div class="profil-field">
                <label><i class="fas fa-chalkboard-teacher"></i> Nama Pembimbing</label>
                <div class="profil-field-display field-locked">
                    <?= esc($pkl['nama_pembimbing'] ?? '-') ?>
                </div>
            </div>

            <!-- No. WA Pembimbing -->
            <div class="profil-field">
                <label><i class="fab fa-whatsapp"></i> No. WA Pembimbing</label>
                <div class="profil-field-display field-locked">
                    <?= esc($pkl['no_wa_pembimbing'] ?? '-') ?>
                </div>
            </div>

            <!-- Anggota Kelompok (full width) -->
            <?php if (! empty($anggota)): ?>
                <div class="profil-field profil-field-full">
                    <label><i class="fas fa-user-friends"></i> Anggota Kelompok</label>
                    <div class="profil-field-display field-locked anggota-list">
                        <?php foreach ($anggota as $a): ?>
                            <span class="anggota-chip">
                                <i class="fas fa-user-circle"></i>
                                <?= esc($a['nama_lengkap']) ?>
                                <?php if ($a['role_kel_pkl'] === 'ketua'): ?>
                                    <span class="chip-badge-ketua">Ketua</span>
                                <?php endif; ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>
<?php endif; ?>

<!-- ══ SECTION: Ubah Password ══ -->
<div class="profil-section">
    <div class="profil-section-header">
        <div class="profil-section-title">
            <i class="fas fa-lock"></i>
            <span>Ubah Password</span>
        </div>
        <button class="btn-profil-edit" id="btnEditPassword" type="button">
            <i class="fas fa-pen"></i> Edit
        </button>
    </div>

    <form action="<?= base_url('pkl/profil/password') ?>" method="post" id="formPassword" novalidate>
        <?= csrf_field() ?>

        <div class="profil-field-grid">

            <!-- Password Baru -->
            <div class="profil-field">
                <label><i class="fas fa-key"></i> Password Baru</label>
                <div class="profil-field-display pw-display-placeholder">
                    <span class="pw-dots">••••••••</span>
                    <i class="fas fa-eye pw-eye-hint" title="Klik Edit untuk mengubah password"></i>
                </div>
                <div class="profil-input-password-wrap" style="display:none">
                    <input type="password" name="password_baru" class="profil-input"
                        id="inputPasswordBaru"
                        placeholder="Min. 8 karakter, huruf besar/kecil, angka & simbol">
                    <button type="button" class="btn-toggle-pw" data-target="inputPasswordBaru">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <div class="password-strength" id="passwordStrength" style="display:none">
                    <div class="strength-bar">
                        <div class="strength-fill" id="strengthFill"></div>
                    </div>
                    <ul class="strength-rules" id="strengthRules">
                        <li id="rule-length"><i class="fas fa-circle"></i> Min. 8 karakter</li>
                        <li id="rule-upper"><i class="fas fa-circle"></i> Huruf kapital (A-Z)</li>
                        <li id="rule-lower"><i class="fas fa-circle"></i> Huruf kecil (a-z)</li>
                        <li id="rule-number"><i class="fas fa-circle"></i> Angka (0-9)</li>
                        <li id="rule-symbol"><i class="fas fa-circle"></i> Simbol (!@#…)</li>
                    </ul>
                </div>
            </div>

            <!-- Konfirmasi Password -->
            <div class="profil-field">
                <label><i class="fas fa-key"></i> Konfirmasi Password</label>
                <div class="profil-field-display pw-display-placeholder">
                    <span class="pw-dots">••••••••</span>
                    <i class="fas fa-eye pw-eye-hint" title="Klik Edit untuk mengubah password"></i>
                </div>
                <div class="profil-input-password-wrap" style="display:none">
                    <input type="password" name="konfirmasi_password" class="profil-input"
                        id="inputKonfirmasi"
                        placeholder="Ulangi password baru">
                    <button type="button" class="btn-toggle-pw" data-target="inputKonfirmasi">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>

        </div>

        <div class="profil-edit-actions" id="actionsPassword" style="display:none">
            <button type="button" class="btn-profil-cancel" id="btnCancelPassword">
                <i class="fas fa-times"></i> Batal
            </button>
            <button type="submit" class="btn-profil-save">
                <i class="fas fa-check"></i> Simpan Password
            </button>
        </div>
    </form>
</div>
