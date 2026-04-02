<?php

/**
 * Views/dashboard_admin/profil/profil.php
 *
 * FIX: Section link biodata token dipindah ke DALAM .profil-section
 *      agar mendapat styling card yang benar.
 */

$namaLengkap   = esc($admin['nama_lengkap']   ?? '');
$namaPanggilan = esc($admin['nama_panggilan'] ?? '');
$noWa          = esc($admin['no_wa_admin']    ?? '');
$alamat        = esc($admin['alamat']          ?? '');
$email         = esc($user->email             ?? '');
$username      = esc($user->username          ?? '');

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
                timerProgressBar: true,
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
                confirmButtonColor: 'var(--primary)',
            });
        });
    </script>
<?php endif; ?>

<!-- ── Welcome Card ── -->
<div class="welcome-card">
    <h2 class="page-heading"><?= esc($page_title ?? 'Profil Saya') ?></h2>
    <p class="page-subheading"><?= esc($page_subheading ?? 'Data diri dan informasi akun') ?></p>
</div>

<!-- ══ TAB NAVIGATION ══ -->
<div class="profil-tab-nav">
    <button class="profil-tab-btn <?= $active_tab === 'biodata'  ? 'active' : '' ?>"
        data-tab="biodata">
        <i class="fas fa-user"></i>
        Bio Pribadi
    </button>
    <button class="profil-tab-btn <?= $active_tab === 'setting' ? 'active' : '' ?>"
        data-tab="setting">
        <i class="fas fa-gear"></i>
        Pengaturan Form Biodata PKL
    </button>
</div>

<!-- ══════════════════════════════════════════════════════════════ -->
<!-- TAB: BIO PRIBADI                                              -->
<!-- ══════════════════════════════════════════════════════════════ -->
<div class="profil-tab-content <?= $active_tab === 'biodata' ? 'active' : '' ?>"
    id="tab-biodata">

    <!-- Avatar Card -->
    <div class="profil-avatar-card">
        <div class="profil-avatar-circle">
            <i class="fas fa-user-tie"></i>
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

    <!-- ── SECTION: Informasi Pribadi ── -->
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

        <form action="<?= base_url('admin/profil/biodata') ?>" method="post" id="formBiodata">
            <?= csrf_field() ?>

            <div class="profil-field-grid">

                <!-- Nama Lengkap -->
                <div class="profil-field">
                    <label><i class="fas fa-user"></i> Nama Lengkap</label>
                    <div class="profil-field-display" id="displayNamaLengkap"><?= $namaLengkap ?></div>
                    <input type="text" name="nama_lengkap" class="profil-input"
                        value="<?= $namaLengkap ?>" id="inputNamaLengkap" style="display:none">
                </div>

                <!-- Nama Panggilan -->
                <div class="profil-field">
                    <label><i class="fas fa-smile"></i> Nama Panggilan</label>
                    <div class="profil-field-display" id="displayNamaPanggilan"><?= $namaPanggilan ?: '-' ?></div>
                    <input type="text" name="nama_panggilan" class="profil-input"
                        value="<?= $namaPanggilan ?>" id="inputNamaPanggilan" style="display:none">
                </div>

                <!-- Username (locked) -->
                <div class="profil-field">
                    <label>
                        <i class="fas fa-at"></i> Username
                        <i class="fas fa-lock field-lock-icon" title="Username tidak dapat diubah"></i>
                    </label>
                    <div class="profil-field-display field-locked"><?= $username ?></div>
                </div>

                <!-- Email -->
                <div class="profil-field">
                    <label><i class="fas fa-envelope"></i> Email</label>
                    <div class="profil-field-display" id="displayEmail"><?= $email ?></div>
                    <input type="email" name="email" class="profil-input"
                        value="<?= $email ?>" id="inputEmail" style="display:none">
                </div>

                <!-- No. WhatsApp -->
                <div class="profil-field">
                    <label><i class="fab fa-whatsapp"></i> No. WhatsApp</label>
                    <div class="profil-field-display" id="displayNoWa"><?= $noWa ?: '-' ?></div>
                    <input type="text" name="no_wa" class="profil-input"
                        value="<?= $noWa ?>" id="inputNoWa" style="display:none">
                </div>

                <!-- Alamat (full width) -->
                <div class="profil-field profil-field-full">
                    <label><i class="fas fa-map-marker-alt"></i> Alamat</label>
                    <div class="profil-field-display" id="displayAlamat"><?= $alamat ?: '-' ?></div>
                    <input type="text" name="alamat" class="profil-input"
                        value="<?= $alamat ?>" id="inputAlamat" style="display:none">
                </div>

            </div>

            <!-- Action buttons -->
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

    <!-- ── SECTION: Ubah Password ── -->
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

        <form action="<?= base_url('admin/profil/password') ?>" method="post" id="formPassword">
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
                        <button type="button" class="btn-toggle-pw" data-target="inputPasswordBaru"
                            title="Tampilkan/Sembunyikan password">
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
                        <button type="button" class="btn-toggle-pw" data-target="inputKonfirmasi"
                            title="Tampilkan/Sembunyikan password">
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

</div><!-- end #tab-biodata -->

<!-- ══════════════════════════════════════════════════════════════ -->
<!-- TAB: PENGATURAN FORM BIODATA PKL                              -->
<!-- ══════════════════════════════════════════════════════════════ -->
<div class="profil-tab-content <?= $active_tab === 'setting' ? 'active' : '' ?>"
    id="tab-setting">

    <!-- ══ SATU section card memuat toggle + link sekaligus ══ -->
    <div class="profil-section">
        <div class="profil-section-header">
            <div class="profil-section-title">
                <i class="fas fa-gear"></i>
                <span>Pengaturan Form Biodata PKL</span>
            </div>
        </div>

        <!-- Toggle aktif/nonaktif -->
        <div class="setting-toggle-card">
            <div class="setting-toggle-info">
                <div class="setting-toggle-icon">
                    <i class="fas fa-file-alt"></i>
                </div>
                <div class="setting-toggle-text">
                    <strong>Form Biodata PKL</strong>
                    <p>Ketika diaktifkan, siswa PKL dapat membuka form biodata. Ketika dinonaktifkan, form biodata tidak dapat diakses.</p>
                </div>
            </div>
            <div class="setting-toggle-control">
                <label class="toggle-switch">
                    <input type="checkbox" id="toggleBiodataPkl"
                        <?= $form_biodata_aktif ? 'checked' : '' ?>
                        data-url="<?= base_url('admin/profil/toggle-biodata-pkl') ?>">
                    <span class="toggle-slider"></span>
                </label>
                <span class="toggle-label" id="toggleLabel">
                    <?= $form_biodata_aktif ? 'AKTIF' : 'NONAKTIF' ?>
                </span>
            </div>
        </div>

        <!-- Status info -->
        <div class="setting-status-info <?= $form_biodata_aktif ? 'info-aktif' : 'info-nonaktif' ?>"
            id="settingStatusInfo">
            <i class="fas <?= $form_biodata_aktif ? 'fa-check-circle' : 'fa-times-circle' ?>"></i>
            <span id="settingStatusText">
                <?php if ($form_biodata_aktif): ?>
                    Form biodata PKL sedang <strong>terbuka</strong>. Siswa dapat mengisi dan mengubah data mereka.
                <?php else: ?>
                    Form biodata PKL sedang <strong>ditutup</strong>. Siswa tidak dapat mengakses form biodata.
                <?php endif; ?>
            </span>
        </div>

        <!-- ══ Divider & Link Form Biodata ══ -->
        <!-- FIX: section ini sekarang di DALAM .profil-section agar tampil dalam card -->
        <div class="setting-link-divider">
            <i class="fas fa-link"></i>
            <span>Link Form Pendaftaran PKL</span>
        </div>

        <?php if ($biodata_link ?? null): ?>
            <div class="setting-token-wrap">
                <p class="setting-token-hint">
                    <i class="fas fa-share-alt"></i>
                    Bagikan link berikut kepada calon PKL untuk mengisi form pendaftaran
                </p>
                <div class="setting-token-row">
                    <input type="text" class="setting-token-input" id="biodataLinkInput"
                        value="<?= esc($biodata_link) ?>" readonly onclick="this.select()">
                    <button type="button" class="btn-setting-copy" id="btnCopyBiodataLink">
                        <i class="fas fa-copy"></i> Salin
                    </button>
                </div>
                <p class="setting-token-note">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span>
                        Generate token baru akan <strong>menonaktifkan link lama</strong> yang sudah dibagikan.
                    </span>
                </p>
                <button type="button" class="btn-setting-generate" id="btnGenerateToken"
                    data-url="<?= base_url('admin/profil/generate-token') ?>">
                    <i class="fas fa-rotate"></i> Generate Token Baru
                </button>
            </div>
        <?php else: ?>
            <div class="setting-no-token">
                <i class="fas fa-exclamation-triangle"></i>
                <span>Link form belum dibuat. Klik tombol di bawah untuk membuat link pertama kali.</span>
            </div>
            <div style="margin-top: 12px;">
                <button type="button" class="btn-setting-generate" id="btnGenerateToken"
                    data-url="<?= base_url('admin/profil/generate-token') ?>">
                    <i class="fas fa-plus-circle"></i> Buat Link Sekarang
                </button>
            </div>
        <?php endif; ?>

    </div><!-- end .profil-section -->

</div><!-- end #tab-setting -->
