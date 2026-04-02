<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password — SIMMAG ODC</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <link rel="stylesheet" href="<?= base_url('assets/css/core/variables.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/modules/auth.css') ?>">
</head>

<body>

    <div class="auth-wrapper">

        <div class="auth-left">
            <div class="auth-brand">
                <div class="auth-brand-logo">
                    <img src="<?= base_url('assets/images/logo_2.png') ?>"
                        alt="OurWeb.id"
                        onerror="this.style.display='none'; this.nextElementSibling.style.display='flex'">
                    <i class="fas fa-globe auth-brand-logo-icon" style="display:none"></i>
                </div>
                <span class="auth-brand-name">OurWeb.id</span>
                <span class="auth-brand-sub">Our Digital Solution</span>
            </div>

            <div class="auth-left-content">
                <h1 class="auth-system-name">Reset Password</h1>
                <p class="auth-system-desc">
                    Pulihkan akses akun Anda melalui verifikasi email OTP.<br>
                    Untuk keamanan, kode hanya berlaku singkat dan setiap percobaan dipantau sistem.
                </p>

                <div class="auth-features">
                    <div class="auth-feature-item">
                        <div class="auth-feature-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <span class="auth-feature-text">OTP dikirim ke email akun terdaftar</span>
                    </div>
                    <div class="auth-feature-item">
                        <div class="auth-feature-icon">
                            <i class="fas fa-shield-halved"></i>
                        </div>
                        <span class="auth-feature-text">Percobaan salah dibatasi untuk menjaga keamanan</span>
                    </div>
                    <div class="auth-feature-item">
                        <div class="auth-feature-icon">
                            <i class="fas fa-key"></i>
                        </div>
                        <span class="auth-feature-text">Password baru harus kuat dan aman</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="auth-right">
            <div class="auth-right-inner">

                <div class="auth-mobile-logo">
                    <img src="<?= base_url('assets/images/logo_hijau.png') ?>"
                        alt="OurWeb.id"
                        onerror="this.style.display='none'">
                </div>

                <div class="auth-card auth-card-wide">
                    <div class="auth-card-header">
                        <h2 class="auth-greeting">Lupa Password</h2>
                        <p class="auth-subtitle">Masukkan email akun, verifikasi OTP, lalu buat password baru.</p>
                    </div>

                    <div class="auth-alert" id="authAlert">
                        <i class="fas fa-exclamation-circle"></i>
                        <span class="alert-text"></span>
                    </div>

                    <div class="auth-stepper">
                        <div class="auth-step is-active" data-step="1">
                            <span class="auth-step-dot">1</span>
                            <span class="auth-step-label">Email</span>
                        </div>
                        <div class="auth-step-line"></div>
                        <div class="auth-step" data-step="2">
                            <span class="auth-step-dot">2</span>
                            <span class="auth-step-label">OTP</span>
                        </div>
                        <div class="auth-step-line"></div>
                        <div class="auth-step" data-step="3">
                            <span class="auth-step-dot">3</span>
                            <span class="auth-step-label">Password</span>
                        </div>
                    </div>

                    <div class="auth-reset-section">
                        <?= csrf_field() ?>

                        <div class="form-group">
                            <label class="form-label" for="inputResetEmail">
                                <i class="fas fa-envelope"></i> EMAIL AKUN
                            </label>
                            <div class="input-wrap">
                                <i class="fas fa-envelope input-icon"></i>
                                <input
                                    type="email"
                                    id="inputResetEmail"
                                    class="form-control"
                                    placeholder="Masukkan email akun"
                                    autocomplete="email">
                            </div>
                            <span class="form-error"><i class="fas fa-exclamation-circle"></i> <span></span></span>
                        </div>

                        <div class="auth-inline-row">
                            <button type="button" id="btnSendResetOtp" class="btn-login auth-btn-half">
                                <span class="spinner"></span>
                                <span>Kirim OTP</span>
                                <i class="fas fa-paper-plane btn-arrow"></i>
                            </button>
                            <button type="button" id="btnChangeResetEmail" class="btn-auth-secondary auth-btn-half" style="display:none">
                                <i class="fas fa-pen"></i>
                                <span>Ubah Email</span>
                            </button>
                        </div>

                        <div class="auth-meta-text">
                            OTP hanya dikirim ke email yang terdaftar pada akun Admin atau PKL.
                        </div>

                        <div class="auth-reset-lock" id="lockNotice" style="display:none">
                            <i class="fas fa-hourglass-half"></i>
                            <div>
                                <strong>Terlalu banyak percobaan OTP.</strong>
                                <div id="lockCountdownText">Coba lagi dalam 15:00.</div>
                            </div>
                        </div>
                    </div>

                    <div class="auth-reset-section auth-step-panel" id="otpPanel" style="display:none">
                        <div class="auth-section-title">
                            <i class="fas fa-shield-halved"></i>
                            <span>Verifikasi OTP</span>
                        </div>

                        <div class="auth-otp-notice">
                            OTP dikirim ke <strong id="otpEmailTarget">-</strong>.
                            <span id="otpCountdownText">Berlaku 05:00</span>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="inputResetOtp">
                                <i class="fas fa-key"></i> KODE OTP
                            </label>
                            <div class="input-wrap">
                                <i class="fas fa-key input-icon"></i>
                                <input
                                    type="text"
                                    id="inputResetOtp"
                                    class="form-control auth-otp-input"
                                    placeholder="Masukkan 6 digit OTP"
                                    inputmode="numeric"
                                    maxlength="6"
                                    autocomplete="one-time-code">
                            </div>
                            <span class="form-error"><i class="fas fa-exclamation-circle"></i> <span></span></span>
                        </div>
                        <div class="auth-meta-text">
                            OTP akan diverifikasi otomatis setelah Anda memasukkan 6 digit angka.
                        </div>
                    </div>

                    <div class="auth-reset-section auth-step-panel" id="resetPanel" style="display:none">
                        <div class="auth-section-title">
                            <i class="fas fa-lock"></i>
                            <span>Password Baru</span>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="inputPasswordBaru">
                                <i class="fas fa-lock"></i> PASSWORD BARU
                            </label>
                            <div class="input-wrap">
                                <i class="fas fa-lock input-icon"></i>
                                <input
                                    type="password"
                                    id="inputPasswordBaru"
                                    class="form-control"
                                    placeholder="Min. 8 karakter, huruf besar/kecil, angka & simbol"
                                    autocomplete="new-password">
                                <button type="button" class="btn-toggle-pw" data-target="#inputPasswordBaru" tabindex="-1" title="Tampilkan/sembunyikan password">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <span class="form-error"><i class="fas fa-exclamation-circle"></i> <span></span></span>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="inputKonfirmasiPassword">
                                <i class="fas fa-lock"></i> KONFIRMASI PASSWORD
                            </label>
                            <div class="input-wrap">
                                <i class="fas fa-lock input-icon"></i>
                                <input
                                    type="password"
                                    id="inputKonfirmasiPassword"
                                    class="form-control"
                                    placeholder="Ulangi password baru"
                                    autocomplete="new-password">
                                <button type="button" class="btn-toggle-pw" data-target="#inputKonfirmasiPassword" tabindex="-1" title="Tampilkan/sembunyikan password">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <span class="form-error"><i class="fas fa-exclamation-circle"></i> <span></span></span>
                        </div>

                        <div class="auth-password-hint">
                            Password harus terdiri dari minimal 8 karakter, huruf kapital, huruf kecil, angka, dan simbol.
                        </div>

                        <button type="button" id="btnSubmitResetPassword" class="btn-login">
                            <span class="spinner"></span>
                            <span>Simpan Password Baru</span>
                            <i class="fas fa-arrow-right btn-arrow"></i>
                        </button>
                    </div>

                    <div class="auth-card-footer">
                        <a href="<?= base_url('auth/login') ?>" class="auth-back-login">
                            <i class="fas fa-arrow-left"></i>
                            <span>Kembali ke Login</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script>
        window.AUTH_FORGOT_CFG = {
            sendOtpUrl: '<?= base_url('auth/lupa-password/send-otp') ?>',
            verifyOtpUrl: '<?= base_url('auth/lupa-password/verify-otp') ?>',
            resetUrl: '<?= base_url('auth/lupa-password/reset') ?>',
            loginUrl: '<?= base_url('auth/login') ?>',
            csrfName: '<?= csrf_token() ?>',
            csrfHash: '<?= csrf_hash() ?>'
        };
    </script>
    <script src="<?= base_url('assets/js/modules/auth_forgot_password.js') ?>?v=20260402-1"></script>

</body>

</html>
