<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendaftaran Berhasil — SIMMAG ODC</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= base_url('assets/css/core/variables.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/modules/biodata_pkl.css') ?>">
</head>

<body class="biodata-public-body">
    <header class="biodata-header">
        <div class="biodata-header-inner">
            <div class="biodata-brand">
                <img src="<?= base_url('assets/images/logo.png') ?>"
                    alt="SIMMAG ODC"
                    class="biodata-brand-logo">
                <span>PT Our Digital Creative</span>
            </div>
        </div>
    </header>

    <main class="biodata-main biodata-center-main">
        <div class="biodata-info-card">
            <div class="biodata-info-icon biodata-icon-success">
                <i class="fas fa-check-circle"></i>
            </div>
            <h2>Pendaftaran Berhasil!</h2>
            <p>Data PKL Anda telah berhasil disimpan ke sistem SIMMAG ODC.</p>

            <div class="sukses-steps">
                <div class="sukses-step">
                    <i class="fas fa-envelope"></i>
                    <span>Cek email Anda untuk mendapatkan <strong>username dan password</strong> login.</span>
                </div>
                <?php if (($kategori ?? 'mandiri') === 'instansi'): ?>
                    <div class="sukses-step">
                        <i class="fas fa-users"></i>
                        <span>Ketua kelompok mendapatkan <strong>rekapan data seluruh anggota</strong> via email.</span>
                    </div>
                <?php endif; ?>
                <div class="sukses-step">
                    <i class="fas fa-key"></i>
                    <span>Segera <strong>ganti password</strong> setelah login pertama.</span>
                </div>
            </div>

            <div class="biodata-info-contact">
                <i class="fas fa-info-circle"></i>
                <span>Jika email tidak masuk, periksa folder spam atau hubungi admin.</span>
            </div>
        </div>
    </main>

    <footer class="biodata-public-footer">
        <span>© SIMMAG ODC — PT Our Digital Creative</span>
    </footer>
</body>

</html>