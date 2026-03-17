<?php

/**
 * Layouts/header.php
 * Variables: $page_title
 */
?>
<header class="dashboard-header">
    <div class="header-left">
        <button class="menu-toggle" id="menuToggle" type="button" title="Toggle Sidebar">
            <i class="fas fa-bars"></i>
        </button>
        <h1 class="page-title"><?= esc($page_title ?? 'Dashboard') ?></h1>
    </div>
    <div class="header-right">
        <img src="<?= base_url('assets/images/logo_hor.png') ?>"
            alt="OurWeb.id"
            class="header-logo"
            onerror="this.style.display='none'">
    </div>
</header>