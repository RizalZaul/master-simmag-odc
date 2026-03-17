<?php

/**
 * dashboard_pkl/dashboard.php
 * Variables dari DashboardPklController::index():
 *   $statsT   → ['total' => int, 'selesai' => int, 'pending' => int]
 *   $modulList → array dari KategoriModulModel::getForPklDashboard()
 *               keys: id, nama, color, icon, progress, selesai, total_materi
 *   $tugasTerbaru → array of tugas rows (5 terbaru)
 *   $namaUser → nama panggilan dari session
 */
$namaUser = session()->get('panggilan') ?: session()->get('nama') ?: 'PKL';
?>

<!-- ── Welcome Card ── -->
<div class="welcome-card">
    <h2 class="page-heading">Dashboard</h2>
    <p class="page-subheading">Selamat datang, <?= esc($namaUser) ?>!</p>
</div>

<!-- ══ STAT CARDS ══ -->
<div class="stat-cards-row">

    <!-- Total Tugas -->
    <div class="stat-card">
        <div class="stat-icon-box teal">
            <i class="fas fa-clipboard-list"></i>
        </div>
        <div class="stat-info">
            <span class="stat-label">Total Tugas</span>
            <span class="stat-value"><?= $statsT['total'] ?? 0 ?></span>
            <span class="stat-desc">Tugas yang diberikan</span>
        </div>
    </div>

    <!-- Tugas Selesai -->
    <div class="stat-card card-done">
        <div class="stat-icon-box teal">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-info">
            <span class="stat-label">Tugas Selesai</span>
            <span class="stat-value"><?= $statsT['selesai'] ?? 0 ?></span>
            <span class="stat-desc">Telah dikumpulkan</span>
        </div>
    </div>

    <!-- Tugas Pending -->
    <div class="stat-card card-inactive">
        <div class="stat-icon-box orange">
            <i class="fas fa-hourglass-half"></i>
        </div>
        <div class="stat-info">
            <span class="stat-label">Tugas Pending</span>
            <span class="stat-value"><?= $statsT['pending'] ?? 0 ?></span>
            <span class="stat-desc">Belum dikerjakan</span>
        </div>
    </div>

</div>

<!-- ══ MODUL PEMBELAJARAN SAYA ══ -->
<div class="dashboard-section">
    <div class="section-header">
        <div class="section-title">
            <i class="fas fa-book-open"></i>
            <span>Modul Pembelajaran Saya</span>
        </div>
        <a href="<?= base_url('pkl/modul') ?>" class="section-link">
            Lihat Semua <i class="fas fa-arrow-right"></i>
        </a>
    </div>

    <?php if (!empty($modulList)): ?>
        <div class="modul-grid-pkl">
            <?php foreach ($modulList as $modul): ?>
                <?php
                $colorClass = 'color-' . ($modul['color'] ?? 'teal');
                $icon       = $modul['icon'] ?? 'fa-book-open';
                $persen     = $modul['total_materi'] > 0
                    ? round(($modul['selesai'] / $modul['total_materi']) * 100)
                    : 0;
                ?>
                <div class="modul-card-pkl <?= esc($colorClass) ?>">
                    <div class="modul-card-cover">
                        <i class="fas <?= esc($icon) ?>"></i>
                    </div>
                    <div class="modul-card-body">
                        <h4 class="modul-nama"><?= esc($modul['nama']) ?></h4>

                        <div class="modul-progress-wrap">
                            <div class="modul-progress-label">
                                <span><?= $persen ?>% Selesai (<?= $modul['selesai'] ?>/<?= $modul['total_materi'] ?> Materi)</span>
                            </div>
                            <div class="modul-progress-bar">
                                <div class="modul-progress-fill" style="width: <?= $persen ?>%"></div>
                            </div>
                        </div>

                        <a href="<?= base_url('pkl/modul/kategori/' . $modul['id']) ?>"
                            class="btn-modul">
                            Lanjutkan Belajar
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-book"></i>
            <p>Belum ada modul pembelajaran</p>
        </div>
    <?php endif; ?>
</div>

<!-- ══ TUGAS TERBARU ══ -->
<div class="dashboard-section">
    <div class="section-header">
        <div class="section-title">
            <i class="fas fa-list-check"></i>
            <span>Tugas Terbaru</span>
        </div>
        <a href="<?= base_url('pkl/tugas') ?>" class="section-link">
            Lihat Semua <i class="fas fa-arrow-right"></i>
        </a>
    </div>

    <?php if (!empty($tugasTerbaru)): ?>
        <div class="tugas-list">
            <?php foreach ($tugasTerbaru as $tugas): ?>
                <?php
                $sudahKumpul = (bool) ($tugas['sudah_kumpul'] ?? false);
                $today = strtotime(date('Y-m-d'));
                $due   = strtotime($tugas['deadline'] ?? date('Y-m-d'));
                $terlambat = $due < $today && !$sudahKumpul;

                if ($sudahKumpul) {
                    $statusClass = 'tugas-selesai';
                    $statusBadge = 'selesai';
                    $statusLabel = 'Selesai';
                } elseif ($terlambat) {
                    $statusClass = '';
                    $statusBadge = 'terlambat';
                    $statusLabel = 'Terlambat';
                } else {
                    $statusClass = 'tugas-pending';
                    $statusBadge = 'pending';
                    $statusLabel = 'Pending';
                }
                ?>
                <div class="tugas-item <?= $statusClass ?>">
                    <div class="tugas-item-icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <div class="tugas-item-body">
                        <div class="tugas-item-top">
                            <span class="tugas-nama"><?= esc($tugas['nama_tugas']) ?></span>
                            <div class="tugas-actions">
                                <span class="badge-status <?= $statusBadge ?>">
                                    <i class="fas fa-<?= $sudahKumpul ? 'check' : ($terlambat ? 'exclamation' : 'clock') ?>"></i>
                                    <?= $statusLabel ?>
                                </span>
                            </div>
                        </div>

                        <div class="tugas-meta">
                            <span class="meta-chip">
                                <i class="far fa-calendar-alt"></i>
                                <?= date('d M Y', strtotime($tugas['deadline'])) ?>
                            </span>
                            <?php if (!empty($tugas['nama_kat_tugas'])): ?>
                                <span class="meta-chip tag">
                                    <i class="fas fa-tag"></i>
                                    <?= esc($tugas['nama_kat_tugas']) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-inbox"></i>
            <p>Belum ada tugas</p>
        </div>
    <?php endif; ?>
</div>