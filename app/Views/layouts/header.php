<?php

/**
 * Layouts/header.php
 * Variables: $page_title, $page_title_sub (optional)
 *
 * Breadcrumb mode (jika $page_title_sub diset):
 *   Desktop : "Manajemen PKL / Data Instansi"
 *   Mobile  : "Data Instansi" (title-main + title-sep disembunyikan via CSS)
 *
 * Normal mode (tanpa $page_title_sub):
 *   Semua ukuran: "Dashboard" / "Profil Saya" / dll
 */
?>
<header class="dashboard-header">
    <div class="header-left">
        <button class="menu-toggle" id="menuToggle" type="button" title="Toggle Sidebar">
            <i class="fas fa-bars"></i>
        </button>
        <h1 class="page-title">
            <?php if (! empty($page_title_sub)): ?>
                <span class="title-main"><?= esc($page_title ?? 'Dashboard') ?></span>
                <span class="title-sep">&thinsp;/&thinsp;</span>
                <span class="title-sub"><?= esc($page_title_sub) ?></span>
            <?php else: ?>
                <?= esc($page_title ?? 'Dashboard') ?>
            <?php endif; ?>
        </h1>
    </div>
    <div class="header-right">
        <img src="<?= base_url('assets/images/logo_hor.png') ?>"
            alt="OurWeb.id"
            class="header-logo"
            onerror="this.style.display='none'">
    </div>
</header>