<?php

if (! function_exists('fmtPengumpulanDateTime')) {
    function fmtPengumpulanDateTime(?string $value): string
    {
        if (! $value) {
            return '-';
        }

        $ts = strtotime($value);
        if ($ts === false) {
            return '-';
        }

        return sprintf(
            '%02d:%02d - %02d-%02d-%04d',
            (int) date('H', $ts),
            (int) date('i', $ts),
            (int) date('d', $ts),
            (int) date('m', $ts),
            (int) date('Y', $ts)
        );
    }
}

$activeTab = $active_tab ?? 'mandiri';
?>

<div class="welcome-card">
    <h2 class="page-heading"><?= esc($welcome_heading ?? 'Pengumpulan Tugas') ?></h2>
    <p class="page-subheading"><?= esc($welcome_subheading ?? 'Pantau pengumpulan tugas berdasarkan mode mandiri, kelompok, dan tim.') ?></p>
</div>

<div class="mpkl-tab-nav mtugas-pengumpulan-tab-nav">
    <button class="mpkl-tab-btn <?= $activeTab === 'mandiri' ? 'active' : '' ?>" data-target="tab-pengumpulan-mandiri" data-tab="mandiri">
        <i class="fas fa-user"></i> Tugas Mandiri
    </button>
    <button class="mpkl-tab-btn <?= $activeTab === 'kelompok' ? 'active' : '' ?>" data-target="tab-pengumpulan-kelompok" data-tab="kelompok">
        <i class="fas fa-users"></i> Tugas Kelompok
    </button>
    <button class="mpkl-tab-btn <?= $activeTab === 'tim' ? 'active' : '' ?>" data-target="tab-pengumpulan-tim" data-tab="tim">
        <i class="fas fa-user-friends"></i> Tim Tugas
    </button>
</div>

<div class="mpkl-card mpkl-tab-content <?= $activeTab === 'mandiri' ? 'active' : '' ?>" id="tab-pengumpulan-mandiri">
    <div class="mpkl-toolbar mtugas-toolbar-between">
        <div class="mtugas-search-toolbar mtugas-pengumpulan-search">
            <div class="mtugas-search-field">
                <i class="fas fa-search mtugas-search-icon"></i>
                <input type="text" id="searchPengumpulanMandiri" class="mpkl-input mtugas-search-input" placeholder="Cari berdasarkan nama...">
            </div>
            <button type="button" class="btn-reset-filter-tim mtugas-search-reset" id="btnResetPengumpulanMandiri">
                <i class="fas fa-rotate-left"></i> Reset
            </button>
        </div>
    </div>
    <div class="mpkl-table-wrap">
        <table class="mpkl-table mtugas-full-table" id="tablePengumpulanMandiri">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Lengkap</th>
                    <th>Nama Tugas</th>
                    <th>Waktu Pengumpulan</th>
                    <th>Deadline</th>
                    <th>Status</th>
                    <th class="mtugas-text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (($mandiriRows ?? []) as $index => $row): ?>
                    <tr>
                        <td class="dt-no-col text-center"><?= $index + 1 ?></td>
                        <td><strong><?= esc($row['nama_target'] ?? '-') ?></strong></td>
                        <td><?= esc($row['nama_tugas'] ?? '-') ?></td>
                        <td data-order="<?= esc((string) ($row['waktu_pengumpulan'] ?? '')) ?>"><?= esc(fmtPengumpulanDateTime($row['waktu_pengumpulan'] ?? null)) ?></td>
                        <td data-order="<?= esc((string) ($row['deadline'] ?? '')) ?>"><?= esc(fmtPengumpulanDateTime($row['deadline'] ?? null)) ?></td>
                        <td><span class="<?= esc($row['status_class'] ?? 'badge-status-menunggu') ?>"><?= esc($row['status_label'] ?? '-') ?></span></td>
                        <td class="mtugas-text-center">
                            <a href="<?= esc($row['detail_url'] ?? '#') ?>" class="btn-tbl-view" title="Detail Pengumpulan" aria-label="Detail Pengumpulan">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="mpkl-card mpkl-tab-content <?= $activeTab === 'kelompok' ? 'active' : '' ?>" id="tab-pengumpulan-kelompok">
    <div class="mpkl-toolbar mtugas-toolbar-between">
        <div class="mtugas-search-toolbar mtugas-pengumpulan-search">
            <div class="mtugas-search-field">
                <i class="fas fa-search mtugas-search-icon"></i>
                <input type="text" id="searchPengumpulanKelompok" class="mpkl-input mtugas-search-input" placeholder="Cari berdasarkan nama kelompok...">
            </div>
            <button type="button" class="btn-reset-filter-tim mtugas-search-reset" id="btnResetPengumpulanKelompok">
                <i class="fas fa-rotate-left"></i> Reset
            </button>
        </div>
    </div>
    <div class="mpkl-table-wrap">
        <table class="mpkl-table mtugas-full-table" id="tablePengumpulanKelompok">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Kelompok PKL</th>
                    <th>Nama Tugas</th>
                    <th>Waktu Pengumpulan</th>
                    <th>Deadline</th>
                    <th>Status</th>
                    <th class="mtugas-text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (($kelompokRows ?? []) as $index => $row): ?>
                    <tr>
                        <td class="dt-no-col text-center"><?= $index + 1 ?></td>
                        <td><strong><?= esc($row['nama_target'] ?? '-') ?></strong></td>
                        <td><?= esc($row['nama_tugas'] ?? '-') ?></td>
                        <td data-order="<?= esc((string) ($row['waktu_pengumpulan'] ?? '')) ?>"><?= esc(fmtPengumpulanDateTime($row['waktu_pengumpulan'] ?? null)) ?></td>
                        <td data-order="<?= esc((string) ($row['deadline'] ?? '')) ?>"><?= esc(fmtPengumpulanDateTime($row['deadline'] ?? null)) ?></td>
                        <td><span class="<?= esc($row['status_class'] ?? 'badge-status-menunggu') ?>"><?= esc($row['status_label'] ?? '-') ?></span></td>
                        <td class="mtugas-text-center">
                            <a href="<?= esc($row['detail_url'] ?? '#') ?>" class="btn-tbl-view" title="Detail Pengumpulan" aria-label="Detail Pengumpulan">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="mpkl-card mpkl-tab-content <?= $activeTab === 'tim' ? 'active' : '' ?>" id="tab-pengumpulan-tim">
    <div class="mpkl-toolbar mtugas-toolbar-between">
        <div class="mtugas-search-toolbar mtugas-pengumpulan-search">
            <div class="mtugas-search-field">
                <i class="fas fa-search mtugas-search-icon"></i>
                <input type="text" id="searchPengumpulanTim" class="mpkl-input mtugas-search-input" placeholder="Cari berdasarkan nama tim...">
            </div>
            <button type="button" class="btn-reset-filter-tim mtugas-search-reset" id="btnResetPengumpulanTim">
                <i class="fas fa-rotate-left"></i> Reset
            </button>
        </div>
    </div>
    <div class="mpkl-table-wrap">
        <table class="mpkl-table mtugas-full-table" id="tablePengumpulanTim">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Tim</th>
                    <th>Nama Tugas</th>
                    <th>Waktu Pengumpulan</th>
                    <th>Deadline</th>
                    <th>Status</th>
                    <th class="mtugas-text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (($timRows ?? []) as $index => $row): ?>
                    <tr>
                        <td class="dt-no-col text-center"><?= $index + 1 ?></td>
                        <td><strong><?= esc($row['nama_target'] ?? '-') ?></strong></td>
                        <td><?= esc($row['nama_tugas'] ?? '-') ?></td>
                        <td data-order="<?= esc((string) ($row['waktu_pengumpulan'] ?? '')) ?>"><?= esc(fmtPengumpulanDateTime($row['waktu_pengumpulan'] ?? null)) ?></td>
                        <td data-order="<?= esc((string) ($row['deadline'] ?? '')) ?>"><?= esc(fmtPengumpulanDateTime($row['deadline'] ?? null)) ?></td>
                        <td><span class="<?= esc($row['status_class'] ?? 'badge-status-menunggu') ?>"><?= esc($row['status_label'] ?? '-') ?></span></td>
                        <td class="mtugas-text-center">
                            <a href="<?= esc($row['detail_url'] ?? '#') ?>" class="btn-tbl-view" title="Detail Pengumpulan" aria-label="Detail Pengumpulan">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
