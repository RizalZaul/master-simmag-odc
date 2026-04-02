<?php

/**
 * Views/dashboard_admin/manajemen_tugas/penugasan/main_penugasan.php
 * Wrapper utama Manajemen Tugas -> Penugasan
 */

$activeTab = $active_tab ?? 'kategori';
?>

<div class="welcome-card">
    <h2 class="page-heading">Manajemen Tugas</h2>
    <p class="page-subheading">Kelola kategori, ketentuan tugas, dan target sasaran (Individu, Kelompok, atau Tim)</p>
</div>

<div class="mpkl-tab-nav">
    <button class="mpkl-tab-btn <?= $activeTab === 'kategori' ? 'active' : '' ?>" data-target="tab-kategori" data-tab="kategori">
        <i class="fas fa-tags"></i> Kategori Tugas
    </button>
    <button class="mpkl-tab-btn <?= $activeTab === 'tugas' ? 'active' : '' ?>" data-target="tab-tugas" data-tab="tugas">
        <i class="fas fa-tasks"></i> Data Tugas
    </button>
</div>

<div class="mpkl-tab-content <?= $activeTab === 'kategori' ? 'active' : '' ?>" id="tab-kategori">
    <?= view('dashboard_admin/manajemen_tugas/penugasan/_tab_kategori') ?>
</div>

<div class="mpkl-tab-content <?= $activeTab === 'tugas' ? 'active' : '' ?>" id="tab-tugas">
    <?= view('dashboard_admin/manajemen_tugas/penugasan/_tab_tugas', [
        'kategoriList' => $kategoriList ?? []
    ]) ?>
</div>
