<?php

/**
 * Layouts/sidebar_pkl.php
 * Variables: $active_menu
 */

$activeMenu = $active_menu ?? 'dashboard';
$panggilan  = session()->get('panggilan') ?: session()->get('nama') ?: 'PKL';
?>

<aside class="dashboard-sidebar" id="dashboardSidebar">

    <!-- ══ TOP ══ -->
    <div class="sidebar-top">

        <!-- Logo -->
        <div class="sidebar-logo">
            <img src="<?= base_url('assets/images/logo.png') ?>"
                alt="OurWeb.id"
                class="logo-image logo-large"
                data-logo-large="<?= base_url('assets/images/logo.png') ?>"
                data-logo-small="<?= base_url('assets/images/logo_2.png') ?>">
        </div>

        <!-- Navigation -->
        <ul class="sidebar-menu">

            <li class="menu-item <?= $activeMenu === 'dashboard' ? 'active' : '' ?>">
                <a href="<?= base_url('pkl/dashboard') ?>">
                    <i class="fas fa-chart-line icon"></i>
                    <span class="text">Dashboard</span>
                </a>
            </li>

            <li class="menu-item <?= $activeMenu === 'data_modul' ? 'active' : '' ?>">
                <a href="<?= base_url('pkl/modul') ?>">
                    <i class="fas fa-book icon"></i>
                    <span class="text">Data Modul</span>
                </a>
            </li>

            <li class="menu-item <?= $activeMenu === 'manajemen_tugas' ? 'active' : '' ?>">
                <a href="<?= base_url('pkl/tugas') ?>">
                    <i class="fas fa-clipboard-list icon"></i>
                    <span class="text">Manajemen Tugas</span>
                </a>
            </li>

        </ul>
    </div>

    <!-- ══ BOTTOM / PROFILE ══ -->
    <div class="sidebar-bottom">

        <div class="sidebar-profile" id="profileToggle">
            <div class="avatar">
                <i class="fas fa-user-circle"></i>
            </div>
            <div class="profile-info">
                <span class="name"><?= esc($panggilan) ?></span>
                <span class="role">PKL</span>
            </div>
            <i class="fas fa-chevron-up chevron"></i>
        </div>

        <div class="profile-dropdown" id="profileDropdown">
            <a href="<?= base_url('pkl/profil') ?>" class="dropdown-item">
                <i class="fas fa-user"></i>
                <span>Profil</span>
            </a>
            <a href="<?= base_url('auth/logout') ?>" class="dropdown-item danger">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>

    </div>

</aside>