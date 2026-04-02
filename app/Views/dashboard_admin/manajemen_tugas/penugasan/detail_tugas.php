<?php

function fmtMtugasDateTime(?string $value): string
{
    if (! $value) {
        return '-';
    }

    $ts = strtotime($value);
    if ($ts === false) {
        return '-';
    }

    static $months = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

    return sprintf(
        '%02d %s %s %02d:%02d',
        (int) date('d', $ts),
        $months[(int) date('n', $ts)],
        date('Y', $ts),
        (int) date('H', $ts),
        (int) date('i', $ts)
    );
}

$backUrl = base_url('admin/manajemen-tugas/penugasan?tab=tugas');
$modeLabel = ucfirst((string) ($tugas['mode_pengumpulan'] ?? 'individu'));
?>

<div class="mtugas-detail-wrap">
    <div class="mtugas-back-row">
        <a href="<?= $backUrl ?>" class="mtugas-back-link">
            <i class="fas fa-arrow-left"></i> Kembali ke Data Tugas
        </a>
    </div>

    <div class="mtugas-detail-header">
        <div class="mtugas-detail-title">
            <i class="fas fa-clipboard-check"></i>
            <div>
                <h2>Detail Tugas</h2>
                <p><?= esc($tugas['nama_tugas'] ?? '-') ?></p>
            </div>
        </div>
        <a href="<?= base_url('admin/manajemen-tugas/tugas/ubah/' . (int) ($tugas['id_tugas'] ?? 0)) ?>" class="btn-mpkl-submit">
            <i class="fas fa-pen"></i> Edit Tugas
        </a>
    </div>

    <div class="mtugas-detail-card">
        <div class="mtugas-detail-card-header">
            <i class="fas fa-info-circle"></i> Informasi Tugas
        </div>
        <div class="mtugas-detail-grid">
            <div class="mtugas-detail-item">
                <span class="mtugas-detail-label">Kategori</span>
                <div class="mtugas-detail-value"><?= esc($tugas['nama_kat_tugas'] ?? '-') ?></div>
            </div>
            <div class="mtugas-detail-item">
                <span class="mtugas-detail-label">Mode Pengumpulan</span>
                <div class="mtugas-detail-value">
                    <span class="badge-mode <?= ($tugas['mode_pengumpulan'] ?? 'individu') === 'kelompok' ? 'badge-mode-kelompok' : 'badge-mode-individu' ?>">
                        <?= esc($modeLabel) ?>
                    </span>
                </div>
            </div>
            <div class="mtugas-detail-item">
                <span class="mtugas-detail-label">Editor</span>
                <div class="mtugas-detail-value"><?= esc($tugas['editor_username'] ?? 'Admin') ?></div>
            </div>
            <div class="mtugas-detail-item">
                <span class="mtugas-detail-label">Target Jumlah Item</span>
                <div class="mtugas-detail-value"><?= (int) ($tugas['target_jumlah'] ?? 0) ?> item</div>
            </div>
            <div class="mtugas-detail-item">
                <span class="mtugas-detail-label">Deadline</span>
                <div class="mtugas-detail-value"><?= esc(fmtMtugasDateTime($tugas['deadline'] ?? null)) ?></div>
            </div>
            <div class="mtugas-detail-item">
                <span class="mtugas-detail-label">Terakhir Diubah</span>
                <div class="mtugas-detail-value"><?= esc(fmtMtugasDateTime($tugas['updated_at'] ?? null)) ?></div>
            </div>
            <div class="mtugas-detail-item mtugas-detail-item-full">
                <span class="mtugas-detail-label">Deskripsi / Instruksi</span>
                <div class="mtugas-detail-value"><?= nl2br(esc($tugas['deskripsi'] ?? '-')) ?></div>
            </div>
        </div>
    </div>

    <div class="mtugas-detail-card">
        <div class="mtugas-detail-card-header">
            <i class="fas fa-bullseye"></i> Sasaran Tugas
        </div>

        <?php if (! empty($sasaranList)): ?>
            <div class="mtugas-sasaran-list">
                <?php foreach ($sasaranList as $sasaran): ?>
                    <div class="mtugas-sasaran-item">
                        <div class="mtugas-sasaran-head">
                            <span class="mtugas-sasaran-type"><?= esc(ucwords(str_replace('_', ' ', (string) ($sasaran['target_tipe'] ?? '-')))) ?></span>
                            <strong><?= esc($sasaran['label'] ?? '-') ?></strong>
                        </div>
                        <div class="mtugas-sasaran-meta"><?= esc($sasaran['meta'] ?? '-') ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="mtugas-empty-state">Belum ada sasaran yang tersimpan.</div>
        <?php endif; ?>
    </div>

    <div class="mtugas-detail-card">
        <div class="mtugas-detail-card-header">
            <i class="fas fa-inbox"></i> Pengumpulan Tugas
        </div>

        <div class="mtugas-recap-grid">
            <div class="mtugas-recap-card">
                <span class="mtugas-recap-label">Total Penerima</span>
                <strong><?= (int) ($totalPenerima ?? 0) ?></strong>
            </div>
            <div class="mtugas-recap-card">
                <span class="mtugas-recap-label">Sudah Mengumpulkan</span>
                <strong><?= (int) ($totalTerkumpul ?? 0) ?></strong>
            </div>
            <div class="mtugas-recap-card">
                <span class="mtugas-recap-label">Belum Mengumpulkan</span>
                <strong><?= (int) ($totalBelumKumpul ?? 0) ?></strong>
            </div>
        </div>

        <div class="mtugas-table-shell">
            <table class="mpkl-table mtugas-full-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama PKL</th>
                        <th>Kelompok</th>
                        <th>Status</th>
                        <th>Tgl Pengumpulan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (! empty($pengumpulanList)): ?>
                        <?php foreach ($pengumpulanList as $index => $row): ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td><strong><?= esc($row['nama_pkl'] ?? '-') ?></strong></td>
                                <td><?= esc($row['nama_kelompok'] ?? 'Mandiri') ?></td>
                                <td>
                                    <span class="<?= esc($row['status_class'] ?? 'badge-status-menunggu') ?>">
                                        <?= esc($row['status_label'] ?? '-') ?>
                                    </span>
                                </td>
                                <td><?= esc(fmtMtugasDateTime($row['tgl_pengumpulan'] ?? null)) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="mtugas-empty-cell">Belum ada data penerima tugas.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="mpkl-form-footer mtugas-form-footer-end">
        <a href="<?= $backUrl ?>" class="btn-mpkl-cancel">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
        <a href="<?= base_url('admin/manajemen-tugas/tugas/ubah/' . (int) ($tugas['id_tugas'] ?? 0)) ?>" class="btn-mpkl-submit">
            <i class="fas fa-pen"></i> Edit Tugas
        </a>
    </div>
</div>
