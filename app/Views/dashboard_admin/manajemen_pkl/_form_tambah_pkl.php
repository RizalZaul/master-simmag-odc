<?php

/**
 * Views/dashboard_admin/manajemen_pkl/_form_tambah_pkl.php
 * Form Tambah PKL — 3 Step Wizard
 */
$kotaListJson    = json_encode($kotaList ?? [], JSON_UNESCAPED_UNICODE);
$instansiJson    = json_encode(array_values($instansiList ?? []), JSON_UNESCAPED_UNICODE);
$urlCheckEmail   = base_url('admin/manajemen-pkl/pkl/check-email');
$urlStore        = base_url('admin/manajemen-pkl/pkl/store');
$urlKembali      = base_url('admin/manajemen-pkl?tab=pkl');

// Tanggal batas
$today    = date('Y-m-d');
$minMulai = date('Y-m-d', strtotime('-14 days'));  // H-14
$maxMulai = date('Y-m-d', strtotime('+180 days')); // H+180
?>

<div class="wizard-wrap">

    <!-- ── Step Indicator ── -->
    <div class="wizard-steps">
        <div class="wizard-step active" id="step-ind-1">
            <div class="step-circle">1</div>
            <span>Data Kelompok</span>
        </div>
        <div class="wizard-line"></div>
        <div class="wizard-step" id="step-ind-2">
            <div class="step-circle">2</div>
            <span>Biodata Anggota</span>
        </div>
        <div class="wizard-line"></div>
        <div class="wizard-step" id="step-ind-3">
            <div class="step-circle">3</div>
            <span>Konfirmasi</span>
        </div>
    </div>

    <!-- ══ STEP 1: Data Kelompok ══ -->
    <div class="wizard-panel active" id="panel-1">
        <div class="wizard-card">
            <div class="wizard-card-header">
                <i class="fas fa-users"></i>
                <div>
                    <h3>Data Kelompok PKL</h3>
                    <p>Isi informasi kelompok PKL dengan lengkap</p>
                </div>
            </div>
            <div class="wizard-card-divider"></div>
            <div class="wizard-form-body">

                <!-- Kategori PKL -->
                <div class="wizard-field wizard-field-full">
                    <label class="wizard-label">
                        <i class="fas fa-tags"></i> Kategori PKL <span class="required-star">*</span>
                    </label>
                    <div class="radio-group">
                        <label class="radio-option">
                            <input type="radio" name="kategori" value="mandiri" checked id="radioMandiri">
                            <span class="radio-custom"></span>
                            Mandiri
                        </label>
                        <label class="radio-option">
                            <input type="radio" name="kategori" value="instansi" id="radioInstansi">
                            <span class="radio-custom"></span>
                            Instansi
                        </label>
                    </div>
                </div>

                <!-- Field Instansi (hidden jika mandiri) -->
                <div id="fieldInstansiGroup" style="display:none" class="wizard-field-group">

                    <div class="wizard-field">
                        <label class="wizard-label">
                            <i class="fas fa-building"></i> Kategori Instansi <span class="required-star">*</span>
                        </label>
                        <select id="s1KategoriInstansi" class="wizard-select">
                            <option value="">-- Pilih Kategori --</option>
                            <option value="Kuliah">Kuliah</option>
                            <option value="SMK Sederajat">SMK Sederajat</option>
                        </select>
                    </div>

                    <div class="wizard-field">
                        <label class="wizard-label">
                            <i class="fas fa-university"></i> Nama Instansi <span class="required-star">*</span>
                        </label>
                        <select id="s1NamaInstansi" class="wizard-select-instansi">
                            <option value=""></option>
                        </select>
                        <span class="mpkl-hint"><i class="fas fa-info-circle"></i> Ketik nama instansi baru jika tidak ada dalam pilihan</span>
                    </div>

                    <!-- Field tambahan jika instansi baru -->
                    <div id="fieldInstansiBaru" style="display:none" class="wizard-field wizard-field-full">
                        <label class="wizard-label"><i class="fas fa-map-marker-alt"></i> Alamat Instansi Baru</label>
                        <input type="text" id="s1AlamatInstansi" class="wizard-input" placeholder="Masukkan alamat instansi baru">
                    </div>
                    <div id="fieldKotaBaru" style="display:none" class="wizard-field">
                        <label class="wizard-label"><i class="fas fa-city"></i> Kota Instansi Baru</label>
                        <select id="s1KotaInstansi" class="wizard-select-kota">
                            <option value=""></option>
                            <?php foreach ($kotaList as $kota): ?>
                                <option value="<?= esc($kota) ?>"><?= esc($kota) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="wizard-field">
                        <label class="wizard-label">
                            <i class="fas fa-chalkboard-teacher"></i> Nama Pembimbing <span class="required-star">*</span>
                        </label>
                        <input type="text" id="s1NamaPembimbing" class="wizard-input" placeholder="Nama pembimbing instansi">
                    </div>

                    <div class="wizard-field">
                        <label class="wizard-label">
                            <i class="fas fa-phone"></i> No WA Pembimbing <span class="required-star">*</span>
                        </label>
                        <input type="text" id="s1WaPembimbing" class="wizard-input" placeholder="08xxxxxxxxxx">
                    </div>

                    <div class="wizard-field">
                        <label class="wizard-label">
                            <i class="fas fa-users"></i> Jumlah Anggota <span class="required-star">*</span>
                        </label>
                        <input type="number" id="s1JumlahAnggota" class="wizard-input" value="1" min="1" max="20">
                    </div>

                    <div class="wizard-field">
                        <label class="wizard-label">
                            <i class="fas fa-flag"></i> Nama Kelompok <span class="required-star">*</span>
                        </label>
                        <input type="text" id="s1NamaKelompok" class="wizard-input" placeholder="Contoh: Tim ITM">
                    </div>

                </div>

                <!-- Tanggal -->
                <div class="wizard-field">
                    <label class="wizard-label">
                        <i class="fas fa-calendar-alt"></i> Tanggal Mulai PKL <span class="required-star">*</span>
                    </label>
                    <input type="text" id="s1TglMulai" class="wizard-input" placeholder="Pilih tanggal"
                        data-min="<?= $minMulai ?>" data-max="<?= $maxMulai ?>">
                </div>

                <div class="wizard-field">
                    <label class="wizard-label">
                        <i class="fas fa-calendar-check"></i> Tanggal Akhir PKL <span class="required-star">*</span>
                    </label>
                    <input type="text" id="s1TglAkhir" class="wizard-input" placeholder="Pilih tanggal">
                </div>

            </div>
        </div>

        <div class="wizard-footer">
            <a href="<?= $urlKembali ?>" class="btn-wizard-cancel">
                <i class="fas fa-times"></i> Batal
            </a>
            <button type="button" class="btn-wizard-next" id="btnStep1Next">
                Lanjut ke Biodata <i class="fas fa-arrow-right"></i>
            </button>
        </div>
    </div>

    <!-- ══ STEP 2: Biodata Anggota ══ -->
    <div class="wizard-panel" id="panel-2" style="display:none">
        <div class="wizard-card">
            <div class="wizard-card-header">
                <i class="fas fa-id-card-alt"></i>
                <div>
                    <h3>Biodata Anggota PKL</h3>
                    <p>Isi biodata setiap anggota kelompok PKL</p>
                </div>
            </div>
            <div class="wizard-card-divider"></div>
            <!-- Accordion anggota di-generate oleh JS berdasarkan jumlah anggota -->
            <div id="accordionAnggota"></div>
        </div>

        <div class="wizard-footer">
            <button type="button" class="btn-wizard-back" id="btnStep2Back">
                <i class="fas fa-arrow-left"></i> Kembali
            </button>
            <button type="button" class="btn-wizard-next" id="btnStep2Next">
                Lanjut ke Konfirmasi <i class="fas fa-arrow-right"></i>
            </button>
        </div>
    </div>

    <!-- ══ STEP 3: Konfirmasi ══ -->
    <div class="wizard-panel" id="panel-3" style="display:none">
        <div class="wizard-card">
            <div class="wizard-card-header">
                <i class="fas fa-check-circle" style="color:var(--status-success)"></i>
                <div>
                    <h3>Konfirmasi Data PKL</h3>
                    <p>Periksa kembali data sebelum disimpan</p>
                </div>
            </div>
            <div class="wizard-card-divider"></div>
            <!-- Di-render oleh JS -->
            <div id="konfirmasiContent"></div>
        </div>

        <div class="wizard-footer">
            <button type="button" class="btn-wizard-back" id="btnStep3Back">
                <i class="fas fa-arrow-left"></i> Kembali
            </button>
            <button type="button" class="btn-wizard-submit" id="btnSimpanPkl">
                <i class="fas fa-save"></i> Simpan Data PKL
            </button>
        </div>
    </div>

</div>

<script>
    window.TambahPKL = {
        urlCheckEmail: '<?= $urlCheckEmail ?>',
        urlStore: '<?= $urlStore ?>',
        urlKembali: '<?= $urlKembali ?>',
        instansiList: <?= $instansiJson ?>,
        kotaList: <?= $kotaListJson ?>,
        minMulai: '<?= $minMulai ?>',
        maxMulai: '<?= $maxMulai ?>',
        csrfName: document.querySelector('meta[name="csrf-token-name"]')?.content ?? '',
        csrfHash: document.querySelector('meta[name="csrf-token-hash"]')?.content ?? '',
    };
</script>