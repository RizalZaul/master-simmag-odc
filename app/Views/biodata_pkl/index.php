<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Pengisian Biodata PKL — SIMMAG ODC</title>

    <!-- Google Fonts: Montserrat -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Flatpickr -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/material_green.css">
    <!-- Select2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- CSS Variables + Form CSS -->
    <link rel="stylesheet" href="<?= base_url('assets/css/core/variables.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/modules/biodata_pkl.css') ?>">

    <?= csrf_meta() ?>
</head>

<body class="biodata-public-body">

    <!-- ── Header ── -->
    <header class="biodata-header">
        <div class="biodata-header-inner">
            <div class="biodata-brand">
                <img src="<?= base_url('assets/images/logo.png') ?>"
                    alt="SIMMAG ODC"
                    class="biodata-brand-logo">
                <span>PT Our Digital Creative</span>
            </div>
            <div class="biodata-header-title">Form Pengisian Biodata PKL</div>
        </div>
    </header>

    <!-- ── Main Content ── -->
    <main class="biodata-main">
        <div class="biodata-container">

            <!-- Step Indicator -->
            <div class="biodata-steps" id="biodataSteps">
                <div class="biodata-step active" id="stepInd1">
                    <div class="step-circle">1</div>
                    <span class="step-label">Data PKL</span>
                </div>
                <div class="step-line"></div>
                <div class="biodata-step" id="stepInd2">
                    <div class="step-circle">2</div>
                    <span class="step-label">Biodata</span>
                </div>
                <div class="step-line"></div>
                <div class="biodata-step" id="stepInd3">
                    <div class="step-circle">3</div>
                    <span class="step-label">Konfirmasi</span>
                </div>
            </div>

            <!-- ══ STEP 1: Data PKL ══ -->
            <div class="biodata-panel active" id="panel1">
                <div class="biodata-card">
                    <div class="biodata-card-header">
                        <i class="fas fa-users"></i>
                        <div>
                            <h3>Data PKL</h3>
                            <p>Informasi PKL</p>
                        </div>
                    </div>
                    <div class="biodata-card-divider"></div>

                    <div class="biodata-form-body">

                        <!-- Kategori PKL -->
                        <div class="biodata-field biodata-field-full">
                            <label class="biodata-label">
                                <i class="fas fa-tags"></i> Kategori PKL <span class="required-star">*</span>
                            </label>
                            <div class="biodata-radio-group">
                                <label class="biodata-radio-option">
                                    <input type="radio" name="b_kategori" value="mandiri" checked id="radioMandiri">
                                    <span class="biodata-radio-custom"></span>
                                    <i class="fas fa-user"></i>
                                    <span>Mandiri</span>
                                </label>
                                <label class="biodata-radio-option">
                                    <input type="radio" name="b_kategori" value="instansi" id="radioInstansi">
                                    <span class="biodata-radio-custom"></span>
                                    <i class="fas fa-building"></i>
                                    <span>Instansi</span>
                                </label>
                            </div>
                        </div>

                        <!-- Field Instansi (muncul jika pilih instansi) -->
                        <div id="bFieldInstansiGroup" style="display:none" class="biodata-instansi-group">

                            <div class="biodata-field">
                                <label class="biodata-label">
                                    <i class="fas fa-building"></i> Kategori Instansi <span class="required-star">*</span>
                                </label>
                                <select id="bKategoriInstansi" class="biodata-select">
                                    <option value="" selected disabled>-- Pilih Kategori --</option>
                                    <option value="Kuliah">Kuliah</option>
                                    <option value="SMK Sederajat">SMK Sederajat</option>
                                </select>
                            </div>

                            <div class="biodata-field">
                                <label class="biodata-label">
                                    <i class="fas fa-university"></i> Nama Instansi <span class="required-star">*</span>
                                </label>
                                <select id="bNamaInstansi" class="biodata-select-instansi">
                                    <option value=""></option>
                                </select>
                                <span class="biodata-hint">
                                    <i class="fas fa-info-circle"></i>
                                    Ketik nama instansi baru jika tidak ada dalam pilihan
                                </span>
                            </div>

                            <!-- Instansi baru -->
                            <div id="bFieldAlamatBaru" style="display:none" class="biodata-field biodata-field-full">
                                <label class="biodata-label">
                                    <i class="fas fa-map-marker-alt"></i> Alamat Instansi Baru <span class="required-star">*</span>
                                </label>
                                <textarea id="bAlamatInstansi" class="biodata-textarea"
                                    placeholder="Masukkan alamat instansi baru" maxlength="100" rows="3"></textarea>
                            </div>
                            <div id="bFieldKotaBaru" style="display:none" class="biodata-field">
                                <label class="biodata-label">
                                    <i class="fas fa-city"></i> Kota Instansi Baru <span class="required-star">*</span>
                                </label>
                                <select id="bKotaInstansi" class="biodata-select-kota">
                                    <option value=""></option>
                                    <?php foreach ($kotaList ?? [] as $kota): ?>
                                        <option value="<?= esc($kota) ?>"><?= esc($kota) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="biodata-field">
                                <label class="biodata-label">
                                    <i class="fas fa-chalkboard-teacher"></i> Nama Pembimbing <span class="required-star">*</span>
                                </label>
                                <input type="text" id="bNamaPembimbing" class="biodata-input"
                                    placeholder="Nama pembimbing instansi" maxlength="100">
                            </div>

                            <div class="biodata-field">
                                <label class="biodata-label">
                                    <i class="fas fa-phone"></i> No WA Pembimbing <span class="required-star">*</span>
                                </label>
                                <input type="text" id="bWaPembimbing" class="biodata-input"
                                    placeholder="08xxxxxxxxxx" maxlength="20">
                            </div>

                            <div class="biodata-field">
                                <label class="biodata-label">
                                    <i class="fas fa-users"></i> Jumlah Anggota <span class="required-star">*</span>
                                </label>
                                <input type="number" id="bJumlahAnggota" class="biodata-input"
                                    value="1" min="1" max="10">
                                <span class="biodata-hint">
                                    <i class="fas fa-info-circle"></i> Termasuk ketua kelompok
                                </span>
                            </div>

                            <div class="biodata-field">
                                <label class="biodata-label">
                                    <i class="fas fa-flag"></i> Nama Kelompok <span class="required-star">*</span>
                                </label>
                                <input type="text" id="bNamaKelompok" class="biodata-input"
                                    placeholder="Contoh: Tim ODC 2026" maxlength="20">
                            </div>

                        </div><!-- end instansi group -->

                        <!-- Tanggal -->
                        <div class="biodata-field">
                            <label class="biodata-label">
                                <i class="fas fa-calendar-alt"></i> Tanggal Mulai PKL <span class="required-star">*</span>
                            </label>
                            <input type="text" id="bTglMulai" class="biodata-input biodata-datepicker"
                                placeholder="Pilih tanggal"
                                data-min="<?= $minMulai ?>"
                                data-max="<?= $maxMulai ?>">
                        </div>

                        <div class="biodata-field">
                            <label class="biodata-label">
                                <i class="fas fa-calendar-check"></i> Tanggal Akhir PKL <span class="required-star">*</span>
                            </label>
                            <input type="text" id="bTglAkhir" class="biodata-input biodata-datepicker"
                                placeholder="Pilih tanggal">
                        </div>

                    </div><!-- end form-body -->
                </div>

                <div class="biodata-footer">
                    <div></div>
                    <button type="button" class="btn-biodata-next" id="btnStep1Next">
                        Lanjut ke Biodata <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </div><!-- end panel1 -->

            <!-- ══ STEP 2: Biodata Anggota ══ -->
            <div class="biodata-panel" id="panel2" style="display:none">
                <div class="biodata-card">
                    <div class="biodata-card-header">
                        <i class="fas fa-id-card-alt"></i>
                        <div>
                            <h3>Biodata</h3>
                            <p>Informasi biodata</p>
                        </div>
                    </div>
                    <div class="biodata-card-divider"></div>
                    <!-- Accordion anggota di-generate oleh JS -->
                    <div id="biodataAccordion"></div>
                </div>

                <div class="biodata-footer">
                    <button type="button" class="btn-biodata-back" id="btnStep2Back">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </button>
                    <button type="button" class="btn-biodata-next" id="btnStep2Next">
                        Lanjut ke Konfirmasi <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </div><!-- end panel2 -->

            <!-- ══ STEP 3: Konfirmasi + OTP ══ -->
            <div class="biodata-panel" id="panel3" style="display:none">

                <!-- Preview data -->
                <div class="biodata-card">
                    <div class="biodata-card-header">
                        <i class="fas fa-clipboard-check" style="color:var(--status-success)"></i>
                        <div>
                            <h3>Konfirmasi Data PKL</h3>
                            <p>Periksa kembali data sebelum menyimpan</p>
                        </div>
                    </div>
                    <div class="biodata-card-divider"></div>
                    <div id="konfirmasiContent"></div>
                </div>

                <!-- OTP Verification Card -->
                <div class="biodata-card biodata-otp-card" id="otpCard">
                    <div class="biodata-card-header">
                        <i class="fas fa-shield-alt" style="color:var(--status-info)"></i>
                        <div>
                            <h3>Verifikasi Email</h3>
                            <p id="otpCardSubtitle">Konfirmasi identitas via kode OTP</p>
                        </div>
                    </div>
                    <div class="biodata-card-divider"></div>

                    <div class="otp-body">
                        <div class="otp-email-display">
                            <i class="fas fa-envelope"></i>
                            <div>
                                <span class="otp-email-label">OTP akan dikirim ke:</span>
                                <strong id="otpEmailDisplay" class="otp-email-value">-</strong>
                            </div>
                        </div>

                        <!-- Step A: Send OTP -->
                        <div id="otpStepSend" class="otp-step">
                            <button type="button" class="btn-otp-send" id="btnKirimOtp">
                                <i class="fas fa-paper-plane"></i> Kirim OTP ke Email
                            </button>
                        </div>

                        <!-- Step B: Verify OTP -->
                        <div id="otpStepVerify" class="otp-step" style="display:none">
                            <div class="otp-sent-notice">
                                <i class="fas fa-check-circle"></i>
                                <span>OTP dikirim! Periksa inbox/spam email Anda.</span>
                            </div>
                            <div class="otp-input-group">
                                <input type="text"
                                    id="inputOtp"
                                    class="otp-input"
                                    maxlength="6"
                                    placeholder="000000"
                                    inputmode="numeric"
                                    autocomplete="one-time-code">
                                <button type="button" class="btn-otp-verify" id="btnVerifikasiOtp">
                                    <i class="fas fa-check"></i> Verifikasi
                                </button>
                            </div>
                            <div class="otp-meta">
                                <span id="otpCountdown" class="otp-countdown"></span>
                                <button type="button" class="btn-otp-resend" id="btnResendOtp" style="display:none">
                                    <i class="fas fa-redo"></i> Kirim Ulang OTP
                                </button>
                            </div>
                        </div>

                        <!-- Step C: Verified -->
                        <div id="otpStepDone" class="otp-step otp-verified" style="display:none">
                            <i class="fas fa-check-circle"></i>
                            <span>Email berhasil diverifikasi!</span>
                        </div>
                    </div>
                </div><!-- end otp card -->

                <div class="biodata-footer">
                    <button type="button" class="btn-biodata-back" id="btnStep3Back">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </button>
                    <button type="button" class="btn-biodata-submit" id="btnSimpanBiodata" disabled>
                        <i class="fas fa-save"></i> Simpan Pendaftaran
                    </button>
                </div>
            </div><!-- end panel3 -->

        </div><!-- end container -->
    </main>

    <!-- ── Footer ── -->
    <footer class="biodata-public-footer">
        <span>© SIMMAG ODC — PT Our Digital Creative</span>
    </footer>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        window.BiodataPKL = {
            token: '<?= esc($token) ?>',
            baseUrl: '<?= rtrim(base_url('/'), '/') . '/' ?>',
            instansiList: <?= $instansiJson ?? '[]' ?>,
            kotaList: <?= $kotaListJson ?? '[]' ?>,
            minMulai: '<?= $minMulai ?>',
            maxMulai: '<?= $maxMulai ?>',
            urlCheckEmail: '<?= base_url('biodata-pkl/check-email') ?>',
            urlSendOtp: '<?= base_url('biodata-pkl/send-otp') ?>',
            urlVerifyOtp: '<?= base_url('biodata-pkl/verify-otp') ?>',
            urlStore: '<?= base_url('biodata-pkl/store') ?>',
            csrfName: document.querySelector('meta[name="csrf-token-name"]')?.content ?? '',
            csrfHash: document.querySelector('meta[name="csrf-token-hash"]')?.content ?? '',
        };
    </script>
    <script src="<?= base_url('assets/js/core/simmag_validation.js') ?>?v=20260406-3"></script>
    <script src="<?= base_url('assets/js/modules/biodata_pkl.js') ?>"></script>
</body>

</html>
