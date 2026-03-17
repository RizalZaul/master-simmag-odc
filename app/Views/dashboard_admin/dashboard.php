<?php

/**
 * dashboard_admin/dashboard.php
 * Variables dari DashboardAdminController::index():
 *   $stats       → ['aktif' => int, 'selesai' => int, 'nonaktif' => int]
 *   $modulTerbaru → array of ['id', 'nama', 'kategori']
 *   $tugasAktif  → array of tugas rows
 */

/**
 * Helper: format tanggal relatif dari deadline
 */
function deadlineRelative(string $deadline): string
{
    $today = strtotime(date('Y-m-d'));
    $due   = strtotime($deadline);
    $diff  = (int) round(($due - $today) / 86400);

    if ($diff < 0)  return 'Terlambat';
    if ($diff === 0) return 'Hari ini';
    if ($diff === 1) return 'Besok';
    return 'Tersisa ' . $diff . ' hari';
}

/**
 * Helper: deadline ke format tampilan "d M Y"
 */
function formatTgl(string $date): string
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
        <div class="stat-icon-box teal">
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
        <div class="stat-icon-box teal">
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

    <?php if (!empty($modulTerbaru)): ?>
        <div class="modul-grid-admin">
            <?php foreach ($modulTerbaru as $modul): ?>
                <div class="modul-card-admin">
                    <div class="modul-card-cover">
                        <i class="fas fa-book-open"></i>
                    </div>
                    <div class="modul-card-body">
                        <span class="modul-kategori-tag">
                            <?= esc($modul['kategori'] ?? 'Umum') ?>
                        </span>
                        <h4 class="modul-nama"><?= esc($modul['nama']) ?></h4>
                        <a href="<?= base_url('admin/data-modul/detail/' . $modul['id']) ?>"
                            class="btn-modul">
                            Lihat Detail
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-book"></i>
            <p>Belum ada modul tersedia</p>
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
        <a href="<?= base_url('admin/penugasan') ?>" class="section-link">
            Lihat Semua <i class="fas fa-arrow-right"></i>
        </a>
    </div>

    <?php if (!empty($tugasAktif)): ?>
        <div class="tugas-list">
            <?php foreach ($tugasAktif as $tugas): ?>
                <?php
                $totalPkl   = (int) ($tugas['total_pkl']   ?? 0);
                $sudahKumpul = (int) ($tugas['sudah_kumpul'] ?? 0);
                $persen     = $totalPkl > 0 ? round($sudahKumpul / $totalPkl * 100) : 0;
                $relative   = deadlineRelative($tugas['deadline']);
                $urgency    = strtolower($tugas['urgensi'] ?? 'normal');
                ?>
                <div class="tugas-item">
                    <div class="tugas-item-icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <div class="tugas-item-body">
                        <div class="tugas-item-top">
                            <span class="tugas-nama"><?= esc($tugas['nama_tugas']) ?></span>
                            <div class="tugas-actions">
                                <span class="badge-urgency <?= esc($urgency) ?>">
                                    <?= esc(ucfirst($tugas['urgensi'] ?? 'Normal')) ?>
                                </span>
                                <a href="<?= base_url('admin/penugasan/detail/' . $tugas['id_tugas']) ?>"
                                    class="btn-tugas-view" title="Lihat Detail">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </div>
                        </div>

                        <div class="tugas-meta">
                            <span class="meta-chip">
                                <i class="far fa-clock"></i>
                                <?= formatTgl($tugas['deadline']) ?>
                            </span>
                            <span class="meta-chip">
                                <i class="fas fa-hourglass-half"></i>
                                <?= $relative ?>
                            </span>
                            <?php if (!empty($tugas['nama_kat_tugas'])): ?>
                                <span class="meta-chip tag">
                                    <i class="fas fa-tag"></i>
                                    <?= esc($tugas['nama_kat_tugas']) ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="tugas-progress-wrap">
                            <div class="tugas-progress-info">
                                <span><?= $sudahKumpul ?>/<?= $totalPkl ?> sudah mengumpulkan</span>
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