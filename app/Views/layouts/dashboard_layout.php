<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($page_title ?? 'Dashboard') ?> — SIMMAG ODC</title>

    <!-- CSRF untuk AJAX request -->
    <meta name="csrf-token-name" content="<?= csrf_token() ?>">
    <meta name="csrf-token-hash" content="<?= csrf_hash() ?>">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- DataTables + Responsive extension -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">

    <!-- Select2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">

    <!-- Flatpickr -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/material_green.css">

    <link rel="stylesheet" href="<?= base_url('assets/css/core/variables.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/core/dashboard.css') ?>">

    <!-- Breadcrumb responsive: sembunyikan bagian utama di mobile -->
    <style>
        @media (max-width: 640px) {

            .page-title .title-main,
            .page-title .title-sep {
                display: none;
            }
        }
    </style>

    <?= $extra_css ?? '' ?>
</head>

<body>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="dashboard-wrapper">

        <?php
        $role       = session()->get('role');
        $layoutData = [
            'active_menu'    => $active_menu    ?? 'dashboard',
            'page_title'     => $page_title     ?? 'Dashboard',
            'page_title_sub' => $page_title_sub ?? null,
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

    <!-- DataTables + Responsive extension (setelah jQuery) -->
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>

    <!-- Select2 (setelah jQuery) -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Flatpickr -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    <script src="<?= base_url('assets/js/core/dashboard.js') ?>?v=20260402-3"></script>
    <script src="<?= base_url('assets/js/core/simmag_validation.js') ?>?v=20260406-3"></script>
    <?= $extra_js ?? '' ?>

</body>

</html>
