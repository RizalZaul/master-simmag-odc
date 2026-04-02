<!-- ============================================================ -->
<!-- app/Views/biodata_pkl/ditutup.php                          -->
<!-- ============================================================ -->
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Tidak Tersedia — SIMMAG ODC</title>
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
            <?php $alasan = $alasan ?? 'form_nonaktif'; ?>

            <?php if ($alasan === 'form_nonaktif'): ?>
                <div class="biodata-info-icon biodata-icon-warning">
                    <i class="fas fa-lock"></i>
                </div>
                <h2>Form Sedang Ditutup</h2>
                <p>Form pendaftaran PKL saat ini <strong>tidak aktif</strong>.<br>
                    Silakan hubungi admin untuk informasi lebih lanjut.</p>

            <?php elseif ($alasan === 'token_invalid'): ?>
                <div class="biodata-info-icon biodata-icon-danger">
                    <i class="fas fa-link-slash"></i>
                </div>
                <h2>Link Tidak Valid</h2>
                <p>Link yang Anda gunakan <strong>tidak valid</strong> atau sudah kadaluarsa.<br>
                    Pastikan Anda menggunakan link terbaru dari admin.</p>

            <?php else: ?>
                <div class="biodata-info-icon biodata-icon-warning">
                    <i class="fas fa-clock"></i>
                </div>
                <h2>Form Belum Tersedia</h2>
                <p>Form pendaftaran PKL belum dibuka oleh admin.<br>
                    Silakan tunggu informasi selanjutnya.</p>
            <?php endif; ?>

            <div class="biodata-info-contact">
                <i class="fas fa-headset"></i>
                <span>Butuh bantuan? Hubungi admin PT Our Digital Creative.</span>
            </div>
        </div>
    </main>

    <footer class="biodata-public-footer">
        <span>© SIMMAG ODC — PT Our Digital Creative</span>
    </footer>
</body>

</html>