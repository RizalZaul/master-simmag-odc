<?php

/**
 * dashboard_admin/dashboard.php
 *
 * Variables dari DashboardAdminController::index():
 *   $stats     → ['aktif' => int, 'selesai' => int, 'nonaktif' => int]
 *   $modulList → array of ['id', 'nama', 'total_modul', 'color', 'icon']
 *   $tugasList → array of ['id_tugas', 'nama_tugas', 'deadline',
 *                           'nama_kat_tugas', 'total_penerima', 'sudah_kumpul']
 */

// ── Helper: selisih hari dari hari ini ke deadline ───────────────────────────
function deadlineDiff(string $deadline): int
{
    $today = strtotime(date('Y-m-d'));
    $due   = strtotime(substr($deadline, 0, 10)); // handle DATETIME maupun DATE
    return (int) round(($due - $today) / 86400);
}

// ── Helper: label relatif deadline ──────────────────────────────────────────
function deadlineLabel(string $deadline): string
{
    $diff = deadlineDiff($deadline);
    if ($diff < 0)   return 'Terlambat';
    if ($diff === 0) return 'Hari ini';
    if ($diff === 1) return 'Besok';
    return 'Tersisa ' . $diff . ' hari';
}

// ── Helper: CSS class untuk badge deadline ───────────────────────────────────
function deadlineBadgeClass(string $deadline): string
{
    $diff = deadlineDiff($deadline);
    if ($diff < 0)   return 'terlambat';
    if ($diff === 0) return 'hari-ini';
    if ($diff <= 3)  return 'mendesak';
    return 'normal';
}

// ── Helper: CSS class untuk border-left & icon tugas item ───────────────────
function tugasItemClass(string $deadline): string
{
    $diff = deadlineDiff($deadline);
    if ($diff < 0)   return 'tugas-status-terlambat';
    if ($diff === 0) return 'tugas-status-hari-ini';
    if ($diff <= 3)  return 'tugas-status-mendesak';
    return 'tugas-status-normal';
}

// ── Helper: format tanggal "d M Y" ──────────────────────────────────────────
function fmtTgl(string $date): string
{
    return date('d M Y', strtotime($date));
}
?>

<!-- ── Page Header ── -->
<div class="page-header">
    <h2 class="page-heading">Dashboard</h2>
    <p class="page-subheading">Ringkasan data sistem PKL</p>
</div>

<!-- ══ STAT CARDS ══ -->
<div class="stat-cards-row">

    <!-- PKL Aktif -->
    <div class="stat-card">
        <div class="stat-icon-box">
            <i class="fas fa-briefcase"></i>
        </div>
        <div class="stat-info">
            <span class="stat-label">PKL Aktif</span>
            <span class="stat-value"><?= $stats['aktif'] ?? 0 ?></span>
            <span class="stat-desc">Kelompok berjalan</span>
        </div>
    </div>

    <!-- PKL Selesai -->
    <div class="stat-card card-done">
        <div class="stat-icon-box blue">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-info">
            <span class="stat-label">PKL Selesai</span>
            <span class="stat-value"><?= $stats['selesai'] ?? 0 ?></span>
            <span class="stat-desc">Kelompok selesai</span>
        </div>
    </div>

    <!-- PKL Non-Aktif -->
    <div class="stat-card card-inactive">
        <div class="stat-icon-box orange">
            <i class="fas fa-pause-circle"></i>
        </div>
        <div class="stat-info">
            <span class="stat-label">PKL Non-Aktif</span>
            <span class="stat-value"><?= $stats['nonaktif'] ?? 0 ?></span>
            <span class="stat-desc">Peserta dibekukan</span>
        </div>
    </div>

</div>

<!-- ══ MODUL PEMBELAJARAN ══ -->
<div class="dashboard-section">
    <div class="section-header">
        <div class="section-title">
            <i class="fas fa-book-open"></i>
            <span>Modul Pembelajaran</span>
        </div>
        <a href="<?= base_url('admin/data-modul') ?>" class="section-link">
            Lihat Semua <i class="fas fa-arrow-right"></i>
        </a>
    </div>

    <?php if (!empty($modulList)): ?>
        <div class="modul-grid-admin">
            <?php foreach ($modulList as $modul): ?>
                <div class="modul-card-admin color-<?= esc($modul['color']) ?>">

                    <div class="modul-card-cover">
                        <i class="fas fa-book-open"></i>
                    </div>

                    <div class="modul-card-body">
                        <!-- Badge jumlah modul -->
                        <span class="modul-count-badge">
                            <i class="fas fa-layer-group"></i>
                            <?= (int) $modul['total_modul'] ?> modul
                        </span>

                        <h4 class="modul-nama"><?= esc($modul['nama']) ?></h4>

                        <a href="<?= base_url('admin/data-modul') ?>"
                            class="btn-modul">
                            Lihat Modul
                        </a>
                    </div>

                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-book"></i>
            <p>Belum ada kategori modul tersedia</p>
        </div>
    <?php endif; ?>
</div>

<!-- ══ TUGAS AKTIF — PERLU PERHATIAN ══ -->
<div class="dashboard-section">
    <div class="section-header">
        <div class="section-title">
            <i class="fas fa-list-check"></i>
            <span>Tugas Aktif &mdash; Perlu Perhatian</span>
        </div>
        <a href="<?= base_url('admin/manajemen-tugas/penugasan?tab=tugas') ?>" class="section-link">
            Lihat Semua <i class="fas fa-arrow-right"></i>
        </a>
    </div>

    <?php if (!empty($tugasList)): ?>
        <div class="tugas-list">
            <?php foreach ($tugasList as $tugas): ?>
                <?php
                $totalPenerima  = (int) ($tugas['total_penerima']  ?? 0);
                $sudahKumpul    = (int) ($tugas['sudah_kumpul']    ?? 0);
                $menungguReview = (int) ($tugas['menunggu_review'] ?? 0);
                $perluRevisi    = (int) ($tugas['perlu_revisi']    ?? 0);
                $belumKumpul    = max(0, $totalPenerima - $sudahKumpul);
                $persen         = $totalPenerima > 0
                    ? round($sudahKumpul / $totalPenerima * 100)
                    : 0;
                $label          = deadlineLabel($tugas['deadline']);
                $badgeClass     = deadlineBadgeClass($tugas['deadline']);
                $itemClass      = tugasItemClass($tugas['deadline']);
                ?>
                <div class="tugas-item <?= $itemClass ?>">

                    <div class="tugas-item-icon">
                        <i class="fas fa-file-alt"></i>
                    </div>

                    <div class="tugas-item-body">

                        <div class="tugas-item-top">
                            <span class="tugas-nama"><?= esc($tugas['nama_tugas']) ?></span>
                            <div class="tugas-actions">
                                <span class="badge-deadline <?= $badgeClass ?>">
                                    <?= $label ?>
                                </span>
                                <a href="<?= base_url('admin/manajemen-tugas/tugas/detail/' . $tugas['id_tugas']) ?>"
                                    class="btn-tugas-view" title="Lihat Detail">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </div>
                        </div>

                        <!-- ── Status chips: belum kumpul / menunggu review / perlu revisi ── -->
                        <div class="tugas-status-chips">
                            <?php if ($belumKumpul > 0): ?>
                                <span class="status-chip chip-belum">
                                    <i class="fas fa-clock"></i>
                                    <?= $belumKumpul ?> belum kumpul
                                </span>
                            <?php endif; ?>
                            <?php if ($menungguReview > 0): ?>
                                <span class="status-chip chip-review">
                                    <i class="fas fa-hourglass-half"></i>
                                    <?= $menungguReview ?> menunggu review
                                </span>
                            <?php endif; ?>
                            <?php if ($perluRevisi > 0): ?>
                                <span class="status-chip chip-revisi">
                                    <i class="fas fa-redo"></i>
                                    <?= $perluRevisi ?> perlu revisi
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="tugas-meta">
                            <span class="meta-chip">
                                <i class="far fa-calendar-alt"></i>
                                <?= fmtTgl($tugas['deadline']) ?>
                            </span>
                            <?php if (!empty($tugas['nama_kat_tugas'])): ?>
                                <span class="meta-chip tag">
                                    <i class="fas fa-tag"></i>
                                    <?= esc($tugas['nama_kat_tugas']) ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <!-- Progress pengumpulan -->
                        <div class="tugas-progress-wrap">
                            <div class="tugas-progress-info">
                                <span><?= $sudahKumpul ?>/<?= $totalPenerima ?> sudah mengumpulkan</span>
                                <span><?= $persen ?>%</span>
                            </div>
                            <div class="progress-bar-wrap">
                                <div class="progress-bar-fill" style="width: <?= $persen ?>%"></div>
                            </div>
                        </div>

                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-inbox"></i>
            <p>Tidak ada tugas aktif yang perlu perhatian</p>
        </div>
    <?php endif; ?>
</div>
