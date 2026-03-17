<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($page_title ?? 'Dashboard') ?> — SIMMAG ODC</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= base_url('assets/css/core/variables.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/core/dashboard.css') ?>">
    <?= $extra_css ?? '' ?>
</head>

<body>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="dashboard-wrapper">

        <?php
        $role      = session()->get('role');
        $layoutData = [
            'active_menu' => $active_menu ?? 'dashboard',
            'page_title'  => $page_title  ?? 'Dashboard',
        ];
        echo view($role === 'admin' ? 'Layouts/sidebar_admin' : 'Layouts/sidebar_pkl', $layoutData);
        ?>

        <div class="dashboard-main" id="dashboardMain">

            <?= view('Layouts/header', $layoutData) ?>

            <main class="dashboard-content">

                <?php if (session()->getFlashdata('success')): ?>
                    <div class="flash-message success" data-timeout="4000">
                        <i class="fas fa-check-circle"></i>
                        <?= esc(session()->getFlashdata('success')) ?>
                        <button class="flash-close" type="button"><i class="fas fa-times"></i></button>
                    </div>
                <?php endif; ?>

                <?php if (session()->getFlashdata('error')): ?>
                    <div class="flash-message error" data-timeout="5000">
                        <i class="fas fa-exclamation-circle"></i>
                        <?= esc(session()->getFlashdata('error')) ?>
                        <button class="flash-close" type="button"><i class="fas fa-times"></i></button>
                    </div>
                <?php endif; ?>

                <?= $content ?>

            </main>
        </div>

    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="<?= base_url('assets/js/core/dashboard.js') ?>"></script>
    <?= $extra_js ?? '' ?>

</body>

</html>