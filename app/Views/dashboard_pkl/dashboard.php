<?php

/**
 * dashboard_pkl/dashboard.php
 *
 * Variables dari DashboardPklController::index():
 *   $statsT    → ['total' => int, 'selesai' => int, 'pending' => int, 'belum_dikirim' => int]
 *   $modulList → keys: id, nama, total_modul, color, icon
 *   $tugasList → keys: id_tugas, nama_tugas, deadline, nama_kat_tugas,
 *                      sudah_kumpul, ada_revisi, semua_diterima
 */

$namaUser = session()->get('panggilan') ?: session()->get('nama') ?: 'PKL';

function tglFormatPkl(string $date): string
{
    return date('d M Y', strtotime($date));
}

function deadlineStatusPkl(string $deadline, $sudahKumpul, int $adaRevisi): array
{
    if ($sudahKumpul !== null) {
        if ($adaRevisi) {
            return ['label' => 'Revisi',          'class' => 'revisi',  'icon' => 'fa-redo'];
        }
        return     ['label' => 'Menunggu Review', 'class' => 'pending', 'icon' => 'fa-hourglass-half'];
    }

    $today = strtotime(date('Y-m-d'));
    $due   = strtotime(date('Y-m-d', strtotime($deadline)));
    $diff  = (int) round(($due - $today) / 86400);

    if ($diff < 0)   return ['label' => 'Terlambat',   'class' => 'terlambat', 'icon' => 'fa-exclamation-circle'];
    if ($diff === 0) return ['label' => 'Hari ini',    'class' => 'hari-ini',  'icon' => 'fa-clock'];
    if ($diff <= 3)  return ['label' => 'Mendesak',    'class' => 'mendesak',  'icon' => 'fa-hourglass-half'];
    return             ['label' => 'Belum Dikirim', 'class' => 'pending',   'icon' => 'fa-clock'];
}
?>

<!-- ── Welcome Card ── -->
<div class="welcome-card">
    <h2 class="page-heading">Dashboard</h2>
    <p class="page-subheading">Selamat datang, <?= esc($namaUser) ?>!</p>
</div>

<!-- ══ STAT CARDS TUGAS (4 cards) ══ -->
<div class="stat-cards-row stat-cards-4">

    <!-- Total Tugas -->
    <div class="stat-card">
        <div class="stat-icon-box">
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
        <div class="stat-icon-box blue">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-info">
            <span class="stat-label">Tugas Selesai</span>
            <span class="stat-value"><?= $statsT['selesai'] ?? 0 ?></span>
            <span class="stat-desc">Semua item diterima</span>
        </div>
    </div>

    <!-- Pending (sudah kirim, menunggu review atau revisi) -->
    <div class="stat-card card-pending">
        <div class="stat-icon-box orange">
            <i class="fas fa-hourglass-half"></i>
        </div>
        <div class="stat-info">
            <span class="stat-label">Menunggu / Revisi</span>
            <span class="stat-value"><?= $statsT['pending'] ?? 0 ?></span>
            <span class="stat-desc">Sudah dikirim</span>
        </div>
    </div>

    <!-- Belum Dikirim -->
    <div class="stat-card card-inactive">
        <div class="stat-icon-box red">
            <i class="fas fa-paper-plane"></i>
        </div>
        <div class="stat-info">
            <span class="stat-label">Belum Dikirim</span>
            <span class="stat-value"><?= $statsT['belum_dikirim'] ?? 0 ?></span>
            <span class="stat-desc">Belum dikerjakan</span>
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
        <a href="<?= base_url('pkl/modul') ?>" class="section-link">
            Lihat Semua <i class="fas fa-arrow-right"></i>
        </a>
    </div>

    <?php if (!empty($modulList)): ?>
        <div class="modul-grid-pkl">
            <?php foreach ($modulList as $kat): ?>
                <div class="modul-card-pkl color-<?= esc($kat['color']) ?>">
                    <div class="modul-card-cover">
                        <i class="fas <?= esc($kat['icon']) ?>"></i>
                    </div>
                    <div class="modul-card-body">
                        <h4 class="modul-nama"><?= esc($kat['nama']) ?></h4>
                        <span class="modul-count-badge">
                            <i class="fas fa-layer-group"></i>
                            <?= (int) $kat['total_modul'] ?> Modul
                        </span>
                        <a href="<?= base_url('pkl/modul/kategori/' . $kat['id']) ?>"
                            class="btn-modul">
                            Buka Modul
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

<!-- ══ TUGAS SAYA ══ -->
<div class="dashboard-section">
    <div class="section-header">
        <div class="section-title">
            <i class="fas fa-list-check"></i>
            <span>Tugas Saya</span>
        </div>
        <a href="<?= base_url('pkl/tugas') ?>" class="section-link">
            Lihat Semua <i class="fas fa-arrow-right"></i>
        </a>
    </div>

    <?php if (!empty($tugasList)): ?>
        <div class="tugas-list">
            <?php foreach ($tugasList as $tugas): ?>
                <?php
                $adaRevisi = (int) ($tugas['ada_revisi'] ?? 0);
                $status    = deadlineStatusPkl(
                    $tugas['deadline'],
                    $tugas['sudah_kumpul'],
                    $adaRevisi
                );
                ?>
                <div class="tugas-item tugas-<?= $status['class'] ?>">
                    <div class="tugas-item-icon">
                        <i class="fas <?= $status['icon'] ?>"></i>
                    </div>
                    <div class="tugas-item-body">
                        <div class="tugas-item-top">
                            <span class="tugas-nama"><?= esc($tugas['nama_tugas']) ?></span>
                            <div class="tugas-actions">
                                <span class="badge-status <?= $status['class'] ?>">
                                    <?= $status['label'] ?>
                                </span>
                                <a href="<?= base_url('pkl/tugas/detail/' . $tugas['id_tugas']) ?>"
                                    class="btn-tugas-view" title="Lihat Detail">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </div>
                        </div>
                        <div class="tugas-meta">
                            <span class="meta-chip">
                                <i class="far fa-calendar-alt"></i>
                                <?= tglFormatPkl($tugas['deadline']) ?>
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
            <p>Tidak ada tugas yang perlu dikerjakan</p>
        </div>
    <?php endif; ?>
</div>