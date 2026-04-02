<?php

if (! function_exists('fmtPengumpulanDetailDateTime')) {
    function fmtPengumpulanDetailDateTime(?string $value): string
    {
        if (! $value) {
            return 'Belum dikirim';
        }

        $ts = strtotime($value);
        if ($ts === false) {
            return 'Belum dikirim';
        }

        static $months = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

        return sprintf(
            '%02d:%02d - %02d %s %04d',
            (int) date('H', $ts),
            (int) date('i', $ts),
            (int) date('d', $ts),
            $months[(int) date('n', $ts)],
            (int) date('Y', $ts)
        );
    }
}

$detail = $detail ?? [];
$anggota = $detail['anggota'] ?? [];
$items = $detail['items'] ?? [];
$detailTitle = str_replace(['â€”', '—'], '-', (string) ($detail['title'] ?? 'Informasi Pengumpulan'));
$jenis = (string) ($detail['jenis'] ?? 'mandiri');
$infoIcon = $jenis === 'mandiri' ? 'fas fa-user' : 'fas fa-users';
?>

<div class="mtugas-detail-wrap">
    <div class="mtugas-back-row">
        <a href="<?= esc($detail['back_url'] ?? base_url('admin/manajemen-tugas/pengumpulan')) ?>" class="btn-mpkl-cancel">
            <i class="fas fa-arrow-left"></i> Kembali ke Pengumpulan
        </a>
    </div>

    <div class="mtugas-detail-card">
        <div class="mtugas-detail-card-header mtugas-detail-card-header-between">
            <span><i class="<?= esc($infoIcon) ?>"></i> <?= esc($detailTitle) ?></span>
            <span class="<?= esc($detail['badge_class'] ?? 'badge-status-menunggu') ?>"><?= esc($detail['badge_label'] ?? 'Belum Dikirim') ?></span>
        </div>
        <div class="mtugas-detail-grid">
            <div class="mtugas-detail-item">
                <span class="mtugas-detail-label"><?= esc($detail['target_label'] ?? 'Target') ?></span>
                <div class="mtugas-detail-value"><?= esc($detail['target_value'] ?? '-') ?></div>
            </div>
            <div class="mtugas-detail-item">
                <span class="mtugas-detail-label">Kategori Tugas</span>
                <div class="mtugas-detail-value"><?= esc($detail['kategori_tugas'] ?? '-') ?></div>
            </div>
            <div class="mtugas-detail-item">
                <span class="mtugas-detail-label">Nama Tugas</span>
                <div class="mtugas-detail-value"><?= esc($detail['nama_tugas'] ?? '-') ?></div>
            </div>
            <div class="mtugas-detail-item">
                <span class="mtugas-detail-label">Deadline</span>
                <div class="mtugas-detail-value"><?= esc(fmtPengumpulanDetailDateTime($detail['deadline'] ?? null)) ?></div>
            </div>
            <div class="mtugas-detail-item">
                <span class="mtugas-detail-label">Tanggal Dikirim</span>
                <div class="mtugas-detail-value"><?= esc(fmtPengumpulanDetailDateTime($detail['tanggal_dikirim'] ?? null)) ?></div>
            </div>
            <div class="mtugas-detail-item mtugas-detail-item-full">
                <span class="mtugas-detail-label">Deskripsi Tugas</span>
                <div class="mtugas-detail-value"><?= nl2br(esc($detail['deskripsi'] ?? '-')) ?></div>
            </div>
        </div>
    </div>

    <div class="mtugas-detail-card">
        <div class="mtugas-detail-card-header mtugas-detail-card-header-between">
            <span><i class="fas fa-users"></i> <?= esc($detail['anggota_title'] ?? 'Anggota') ?></span>
            <span class="mtugas-chip-count"><?= count($anggota) ?> Orang</span>
        </div>
        <div class="mtugas-member-list">
            <?php if (! empty($anggota)): ?>
                <?php foreach ($anggota as $index => $anggotaRow): ?>
                    <div class="mtugas-member-chip">
                        <span class="mtugas-member-number"><?= $index + 1 ?></span>
                        <i class="fas fa-user-circle"></i>
                        <span><?= esc($anggotaRow['nama_lengkap'] ?? $anggotaRow['nama'] ?? '-') ?></span>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="mtugas-empty-state">Tidak ada anggota yang terhubung.</div>
            <?php endif; ?>
        </div>
    </div>

    <div class="mtugas-detail-card">
        <div class="mtugas-detail-card-header mtugas-detail-card-header-between">
            <span><i class="fas fa-folder-open"></i> Hasil Tugas</span>
            <span class="mtugas-chip-count"><?= count($items) ?> Item</span>
        </div>

        <?php if (! empty($items)): ?>
            <div class="mtugas-result-list">
                <?php foreach ($items as $item): ?>
                    <div class="mtugas-result-item">
                        <div class="mtugas-result-top">
                            <div class="mtugas-result-head">
                                <span class="mtugas-sasaran-type"><?= esc($item['tipe_label'] ?? 'Link') ?></span>
                                <strong>
                                    <?php if (($item['tipe_label'] ?? '') === 'Link' && ! empty($item['data_item'])): ?>
                                        <a href="<?= esc($item['data_item']) ?>" target="_blank" rel="noopener noreferrer"><?= esc($item['data_item']) ?></a>
                                    <?php else: ?>
                                        <?= esc($item['data_item'] ?? '-') ?>
                                    <?php endif; ?>
                                </strong>
                            </div>
                            <span class="<?= esc($item['status_class'] ?? 'badge-status-menunggu') ?>"><?= esc($item['status_label'] ?? '-') ?></span>
                        </div>
                        <div class="mtugas-result-meta">
                            <span>Pengirim: <?= esc($item['nama_pengirim'] ?? '-') ?></span>
                            <?php if (! empty($item['komentar'])): ?>
                                <span>Komentar: <?= esc($item['komentar']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="mtugas-empty-state">Belum ada hasil tugas yang dikumpulkan.</div>
        <?php endif; ?>
    </div>
</div>
