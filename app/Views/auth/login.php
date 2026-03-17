<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — SIMMAG ODC</title>

    <!-- Google Fonts: Montserrat -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- CSS -->
    <link rel="stylesheet" href="<?= base_url('assets/css/core/variables.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/modules/auth.css') ?>">
</head>

<body>

    <div class="auth-wrapper">

        <!-- ══ LEFT PANEL ══ -->
        <div class="auth-left">
            <!-- Brand -->
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

            <!-- Content -->
            <div class="auth-left-content">
                <h1 class="auth-system-name">SIMMAG ODC</h1>
                <p class="auth-system-desc">
                    Sistem Informasi Manajemen Magang Our Digital Creative<br>
                    Platform digital untuk mengelola dan memantau kegiatan Praktik Kerja Lapangan
                </p>

                <div class="auth-features">
                    <div class="auth-feature-item">
                        <div class="auth-feature-icon">
                            <i class="fas fa-check"></i>
                        </div>
                        <span class="auth-feature-text">Manajemen Tugas Terintegrasi</span>
                    </div>
                    <div class="auth-feature-item">
                        <div class="auth-feature-icon">
                            <i class="fas fa-check"></i>
                        </div>
                        <span class="auth-feature-text">Monitoring Progress Real-time</span>
                    </div>
                    <div class="auth-feature-item">
                        <div class="auth-feature-icon">
                            <i class="fas fa-check"></i>
                        </div>
                        <span class="auth-feature-text">Laporan Digital Otomatis</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ══ RIGHT PANEL ══ -->
        <div class="auth-right">
            <div class="auth-right-inner">

                <!-- Logo mobile (hanya muncul di ≤899px) -->
                <div class="auth-mobile-logo">
                    <img src="<?= base_url('assets/images/logo_hijau.png') ?>"
                        alt="OurWeb.id"
                        onerror="this.style.display='none'">
                    <!-- <span class="auth-mobile-logo-name">OurWeb.id</span> -->
                </div>

                <div class="auth-card">

                    <!-- Header -->
                    <div class="auth-card-header">
                        <h2 class="auth-greeting">Selamat Datang! 👋</h2>
                        <p class="auth-subtitle">Silakan login untuk melanjutkan ke sistem</p>
                    </div>

                    <!-- Flash messages dari session (non-AJAX) -->
                    <?php if (session()->getFlashdata('error')): ?>
                        <div class="auth-alert error visible" id="authAlert">
                            <i class="fas fa-exclamation-circle"></i>
                            <span class="alert-text"><?= esc(session()->getFlashdata('error')) ?></span>
                        </div>
                    <?php elseif (session()->getFlashdata('success')): ?>
                        <div class="auth-alert success visible" id="authAlert">
                            <i class="fas fa-check-circle"></i>
                            <span class="alert-text"><?= esc(session()->getFlashdata('success')) ?></span>
                        </div>
                    <?php else: ?>
                        <div class="auth-alert" id="authAlert">
                            <i class="fas fa-exclamation-circle"></i>
                            <span class="alert-text"></span>
                        </div>
                    <?php endif; ?>

                    <!-- Form -->
                    <form id="loginForm" action="<?= base_url('auth/login') ?>" method="POST" novalidate>
                        <?= csrf_field() ?>

                        <div class="auth-form">

                            <!-- Username / Email -->
                            <div class="form-group">
                                <label class="form-label" for="inputUsername">
                                    <i class="fas fa-user"></i> USERNAME / EMAIL
                                </label>
                                <div class="input-wrap">
                                    <i class="fas fa-user input-icon"></i>
                                    <input
                                        type="text"
                                        id="inputUsername"
                                        name="username"
                                        class="form-control"
                                        placeholder="Masukkan username atau email"
                                        value="<?= old('username') ?>"
                                        autocomplete="username"
                                        autofocus>
                                </div>
                                <span class="form-error"><i class="fas fa-exclamation-circle"></i> <span></span></span>
                            </div>

                            <!-- Password -->
                            <div class="form-group">
                                <label class="form-label" for="inputPassword">
                                    <i class="fas fa-lock"></i> PASSWORD
                                </label>
                                <div class="input-wrap">
                                    <i class="fas fa-lock input-icon"></i>
                                    <input
                                        type="password"
                                        id="inputPassword"
                                        name="password"
                                        class="form-control"
                                        placeholder="Masukkan password"
                                        autocomplete="current-password">
                                    <button type="button" id="btnTogglePw" class="btn-toggle-pw" tabindex="-1" title="Tampilkan/sembunyikan password">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <span class="form-error"><i class="fas fa-exclamation-circle"></i> <span></span></span>
                            </div>

                            <!-- Lupa Password -->
                            <div class="auth-forgot">
                                <a href="<?= base_url('auth/lupa-password') ?>">Lupa Password?</a>
                            </div>

                            <!-- Submit -->
                            <button type="submit" id="btnLogin" class="btn-login">
                                <span class="spinner"></span>
                                <span>Masuk</span>
                                <i class="fas fa-arrow-right btn-arrow"></i>
                            </button>

                        </div>
                    </form>

                    <!-- Footer -->
                    <div class="auth-card-footer">
                        &copy; <?= date('Y') ?> <a href="#" target="_blank">Our Digital Creative</a> &mdash; All rights reserved
                    </div>

                </div><!-- /.auth-card -->
            </div><!-- /.auth-right-inner -->
        </div><!-- /.auth-right -->

    </div><!-- /.auth-wrapper -->

    <!-- jQuery -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <!-- Auth JS -->
    <script src="<?= base_url('assets/js/modules/auth.js') ?>"></script>

</body>

</html>