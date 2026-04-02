<?php

/**
 * Layouts/sidebar_admin.php
 * Variables: $active_menu
 */

$activeMenu     = $active_menu ?? 'dashboard';
$tugasActive    = in_array($activeMenu, ['penugasan', 'pengumpulan']);
$panggilan      = session()->get('panggilan') ?: session()->get('nama') ?: 'Admin';
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
        <a href="<?= base_url('/') ?>">
          <i class="fas fa-chart-line icon"></i>
          <span class="text">Dashboard</span>
        </a>
      </li>

      <li class="menu-item <?= $activeMenu === 'manajemen_pkl' ? 'active' : '' ?>">
        <a href="<?= base_url('admin/manajemen-pkl') ?>">
          <i class="fas fa-users icon"></i>
          <span class="text">Manajemen PKL</span>
        </a>
      </li>

      <li class="menu-item <?= $activeMenu === 'data_modul' ? 'active' : '' ?>">
        <a href="<?= base_url('admin/data-modul') ?>">
          <i class="fas fa-book icon"></i>
          <span class="text">Data Modul</span>
        </a>
      </li>

      <!-- Manajemen Tugas (submenu) -->
      <li class="menu-item has-submenu <?= $tugasActive ? 'active open' : '' ?>">
        <a href="javascript:void(0)">
          <i class="fas fa-clipboard-list icon"></i>
          <span class="text">Manajemen Tugas</span>
          <i class="fas fa-chevron-down submenu-arrow"></i>
        </a>
        <ul class="submenu">
          <li class="submenu-item <?= $activeMenu === 'penugasan' ? 'active' : '' ?>">
            <a href="<?= base_url('admin/manajemen-tugas/penugasan') ?>">
              <i class="fas fa-tasks icon"></i>
              <span class="text">Penugasan</span>
            </a>
          </li>
          <li class="submenu-item <?= $activeMenu === 'pengumpulan' ? 'active' : '' ?>">
            <a href="<?= base_url('admin/manajemen-tugas/pengumpulan') ?>">
              <i class="fas fa-inbox icon"></i>
              <span class="text">Pengumpulan</span>
            </a>
          </li>
        </ul>
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
        <span class="role">Administrator</span>
      </div>
      <i class="fas fa-chevron-up chevron"></i>
    </div>

    <div class="profile-dropdown" id="profileDropdown">
      <a href="<?= base_url('admin/profil') ?>" class="dropdown-item">
        <i class="fas fa-user"></i>
        <span>Profil</span>
      </a>
      <a href="<?= base_url('auth/logout') ?>" class="dropdown-item danger" data-logout-link>
        <i class="fas fa-sign-out-alt"></i>
        <span>Logout</span>
      </a>
    </div>

  </div>

</aside>
