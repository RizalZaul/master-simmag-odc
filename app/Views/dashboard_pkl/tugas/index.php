<?php

if (! function_exists('fmtPklTaskCardDate')) {
    function fmtPklTaskCardDate(?string $value): string
    {
        if (! $value) {
            return '-';
        }

        $timestamp = strtotime($value);
        if ($timestamp === false) {
            return '-';
        }

        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        $month = $months[((int) date('n', $timestamp)) - 1] ?? date('M', $timestamp);

        return date('d', $timestamp) . ' ' . $month . ' ' . date('Y', $timestamp);
    }
}

$activeTab = $active_tab ?? 'individu';
$individuTasks = $individuTasks ?? [];
$kelompokTasks = $kelompokTasks ?? [];
?>

<div class="welcome-card">
    <h2 class="page-heading"><?= esc($welcome_heading ?? 'Manajemen Tugas') ?></h2>
    <p class="page-subheading"><?= esc($welcome_subheading ?? 'Kelola tugas individu dan kelompok Anda') ?></p>
</div>

<div class="pkl-task-page-card">
    <div class="pkl-task-tab-nav">
        <button type="button" class="pkl-task-tab-btn <?= $activeTab === 'individu' ? 'active' : '' ?>" data-tab="individu">
            <i class="fas fa-user"></i>
            <span>Tugas Individu</span>
            <span class="pkl-task-tab-count"><?= count($individuTasks) ?></span>
        </button>
        <button type="button" class="pkl-task-tab-btn <?= $activeTab === 'kelompok' ? 'active' : '' ?>" data-tab="kelompok">
            <i class="fas fa-users"></i>
            <span>Tugas Kelompok</span>
            <span class="pkl-task-tab-count"><?= count($kelompokTasks) ?></span>
        </button>
    </div>

    <div class="pkl-task-search-bar">
        <div class="pkl-task-search-actions">
            <div class="pkl-task-search-wrap">
                <i class="fas fa-search pkl-task-search-icon"></i>
                <input type="text" class="pkl-task-search-input" id="pklTaskSearchInput" placeholder="Cari tugas...">
            </div>
            <button type="button" class="pkl-task-search-reset" id="pklTaskSearchReset">
                <i class="fas fa-rotate-left"></i>
                <span>Reset</span>
            </button>
        </div>
    </div>

    <div class="pkl-task-tab-panel <?= $activeTab === 'individu' ? 'active' : '' ?>" id="pkl-task-tab-individu" data-tab-panel="individu">
        <div class="pkl-task-card-list">
            <?php foreach ($individuTasks as $task): ?>
                <a href="<?= esc($task['detail_url'] ?? '#') ?>" class="pkl-task-card" data-task-card data-search="<?= esc($task['search_blob'] ?? '') ?>">
                    <div class="pkl-task-card-icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <div class="pkl-task-card-body">
                        <div class="pkl-task-card-head">
                            <h3 class="pkl-task-card-title"><?= esc($task['nama_tugas'] ?? '-') ?></h3>
                            <span class="pkl-task-status-badge is-<?= esc($task['status_class'] ?? 'warning') ?>">
                                <?= esc($task['status_short'] ?? 'Belum') ?>
                            </span>
                        </div>
                        <div class="pkl-task-card-meta">
                            <span><i class="far fa-clock"></i> <?= esc($task['deadline_display'] ?? fmtPklTaskCardDate($task['deadline'] ?? null)) ?></span>
                            <span><i class="fas fa-tag"></i> <?= esc($task['nama_kategori'] ?? '-') ?></span>
                        </div>
                    </div>
                    <div class="pkl-task-card-arrow">
                        <i class="fas fa-chevron-right"></i>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="pkl-task-empty <?= ! empty($individuTasks) ? 'is-hidden' : '' ?>" data-empty-default>
            <i class="fas fa-user-clock"></i>
            <p>Belum ada tugas individu</p>
        </div>

        <div class="pkl-task-empty is-hidden" data-empty-filtered>
            <i class="fas fa-search"></i>
            <p>Tidak ada tugas yang cocok dengan pencarian.</p>
        </div>
    </div>

    <div class="pkl-task-tab-panel <?= $activeTab === 'kelompok' ? 'active' : '' ?>" id="pkl-task-tab-kelompok" data-tab-panel="kelompok">
        <div class="pkl-task-card-list">
            <?php foreach ($kelompokTasks as $task): ?>
                <a href="<?= esc($task['detail_url'] ?? '#') ?>" class="pkl-task-card" data-task-card data-search="<?= esc($task['search_blob'] ?? '') ?>">
                    <div class="pkl-task-card-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="pkl-task-card-body">
                        <div class="pkl-task-card-head">
                            <h3 class="pkl-task-card-title"><?= esc($task['nama_tugas'] ?? '-') ?></h3>
                            <span class="pkl-task-status-badge is-<?= esc($task['status_class'] ?? 'warning') ?>">
                                <?= esc($task['status_short'] ?? 'Belum') ?>
                            </span>
                        </div>
                        <div class="pkl-task-card-meta">
                            <span><i class="far fa-clock"></i> <?= esc($task['deadline_display'] ?? fmtPklTaskCardDate($task['deadline'] ?? null)) ?></span>
                            <span><i class="fas fa-tag"></i> <?= esc($task['nama_kategori'] ?? '-') ?></span>
                            <span><i class="fas fa-users"></i> <?= esc($task['source_name'] ?? '-') ?></span>
                        </div>
                    </div>
                    <div class="pkl-task-card-arrow">
                        <i class="fas fa-chevron-right"></i>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="pkl-task-empty <?= ! empty($kelompokTasks) ? 'is-hidden' : '' ?>" data-empty-default>
            <i class="fas fa-users"></i>
            <p>Belum ada tugas kelompok</p>
        </div>

        <div class="pkl-task-empty is-hidden" data-empty-filtered>
            <i class="fas fa-search"></i>
            <p>Tidak ada tugas yang cocok dengan pencarian.</p>
        </div>
    </div>
</div>

