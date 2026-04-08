<?php

/**
 * Views/dashboard_admin/manajemen_pkl/detail_pkl.php
 */

function tglIndoShort(?string $d): string
{
    if (!$d) return '-';
    $bln = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    $p   = explode('-', $d);
    return sprintf('%02d %s %s', (int)$p[2], $bln[(int)$p[1]], $p[0]);
}

function hitungDurasiHari(string $mulai, string $akhir): string
{
    $diff = (new DateTime($mulai))->diff(new DateTime($akhir));
    $bulan = $diff->m + ($diff->y * 12);
    return $bulan >= 1
        ? $bulan . ' Bulan' . ($diff->d > 0 ? ' ' . $diff->d . ' Hari' : '')
        : $diff->days . ' Hari';
}

$today    = date('Y-m-d');
$tglAkhir = $kelompok['tgl_akhir'] ?? $today;
// UserModel returnType=object → pakai object notation
$statusKel = $user->status === 'nonaktif' ? 'nonaktif'
    : ($tglAkhir < $today ? 'selesai' : 'aktif');

$statusBadge = match ($statusKel) {
    'aktif'    => ['label' => 'Aktif',     'class' => 'badge-status-aktif'],
    'selesai'  => ['label' => 'Selesai',   'class' => 'badge-status-selesai'],
    default    => ['label' => 'Non-Aktif', 'class' => 'badge-status-nonaktif'],
};

$kategoriPkl = $kelompok['kategori_pkl'] ?? 'mandiri';
$isInstansi  = $kategoriPkl !== 'mandiri';
$jumlahAnggota = count($anggota);
?>

<div class="detail-pkl-wrap">

    <!-- ── Back + Header ── -->
    <div class="detail-back-row">
        <?php
        // BUG A2 FIX: kembali ke sub-tab yang digunakan untuk membuka halaman ini.
        // $from_sub dikirim dari MPklAdminController::detail() via ?sub= query string.
        $backUrl = base_url('admin/manajemen-pkl?tab=pkl&sub=' . ($from_sub ?? 'aktif'));
        ?>
        <a href="<?= $backUrl ?>" class="detail-back-link">
            <i class="fas fa-arrow-left"></i> Kembali ke Data PKL
        </a>
    </div>

    <div class="detail-page-header">
        <div class="detail-page-title">
            <i class="fas fa-id-badge"></i>
            <h2>Detail PKL — <?= esc($pkl['nama_panggilan'] ?? $pkl['nama_lengkap']) ?></h2>
        </div>
        <span class="<?= $statusBadge['class'] ?>"><?= $statusBadge['label'] ?></span>
    </div>

    <!-- ── Informasi Umum ── -->
    <div class="detail-card">
        <div class="detail-card-header">
            <i class="fas fa-users"></i> Informasi Umum
        </div>
        <div class="detail-info-grid">
            <div class="detail-info-item">
                <span class="detail-info-label">KATEGORI PKL</span>
                <span class="detail-info-value">
                    <?= $isInstansi ? esc($kelompok['nama_instansi'] ?? '-') : 'Mandiri' ?>
                    <span class="badge-kategori-pkl <?= $isInstansi ? 'instansi' : 'mandiri' ?>">
                        <?= $isInstansi ? 'Instansi' : 'Mandiri' ?>
                    </span>
                </span>
            </div>
            <div class="detail-info-item">
                <span class="detail-info-label">STATUS KELOMPOK</span>
                <span class="detail-info-value">
                    <span class="<?= $statusBadge['class'] ?>"><?= $statusBadge['label'] ?></span>
                </span>
            </div>
            <div class="detail-info-item">
                <span class="detail-info-label">TANGGAL MULAI</span>
                <span class="detail-info-value">
                    <i class="fas fa-calendar-alt" style="color:var(--primary)"></i>
                    <?= tglIndoShort($kelompok['tgl_mulai'] ?? null) ?>
                </span>
            </div>
            <div class="detail-info-item">
                <span class="detail-info-label">TANGGAL AKHIR</span>
                <span class="detail-info-value">
                    <i class="fas fa-calendar-check" style="color:var(--primary)"></i>
                    <?= tglIndoShort($kelompok['tgl_akhir'] ?? null) ?>
                </span>
            </div>
            <div class="detail-info-item">
                <span class="detail-info-label">DURASI PKL</span>
                <span class="detail-info-value">
                    <i class="fas fa-hourglass-half" style="color:var(--primary)"></i>
                    <?= ($kelompok['tgl_mulai'] && $kelompok['tgl_akhir'])
                        ? hitungDurasiHari($kelompok['tgl_mulai'], $kelompok['tgl_akhir'])
                        : '-' ?>
                </span>
            </div>
            <div class="detail-info-item">
                <span class="detail-info-label">JUMLAH ANGGOTA</span>
                <span class="detail-info-value">
                    <i class="fas fa-users" style="color:var(--primary)"></i>
                    <?= $jumlahAnggota ?> Orang
                </span>
            </div>
            <?php if ($isInstansi): ?>
                <div class="detail-info-item">
                    <span class="detail-info-label">NAMA KELOMPOK</span>
                    <span class="detail-info-value"><?= esc($kelompok['nama_kelompok'] ?? '-') ?></span>
                </div>
                <div class="detail-info-item">
                    <span class="detail-info-label">NAMA PEMBIMBING</span>
                    <span class="detail-info-value"><?= esc($kelompok['nama_pembimbing'] ?? '-') ?></span>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ── Daftar Anggota ── -->
    <div class="detail-card">
        <div class="detail-card-header">
            <i class="fas fa-users"></i> Daftar Anggota (<?= $jumlahAnggota ?> Orang)
        </div>

        <?php foreach ($anggota as $idx => $ang): ?>
            <?php
            $no        = $idx + 1;
            $roleLabel = $ang['role_kel_pkl'] === 'ketua' ? 'Ketua' : 'Anggota';
            $roleCls   = $ang['role_kel_pkl'] === 'ketua' ? 'badge-role-ketua' : 'badge-role-anggota';
            $jk        = match ($ang['jenis_kelamin'] ?? '') {
                'L' => 'Laki-laki',
                'P' => 'Perempuan',
                default => '-'
            };
            $statusAnggota = $ang['status_user'] === 'aktif'
                ? '<span class="badge-status-aktif">Aktif</span>'
                : '<span class="badge-status-nonaktif">Non-Aktif</span>';
            ?>
            <div class="detail-anggota-item">
                <div class="detail-anggota-header" onclick="toggleAnggota(this)">
                    <div class="detail-anggota-title">
                        <i class="fas fa-user-circle"></i>
                        Anggota <?= $no ?> — <?= esc($ang['nama_lengkap']) ?>
                        <span class="<?= $roleCls ?>"><?= $roleLabel ?></span>
                    </div>
                    <i class="fas fa-chevron-<?= $idx === 0 ? 'up' : 'down' ?> toggle-icon"></i>
                </div>
                <div class="detail-anggota-body" <?= $idx > 0 ? 'style="display:none"' : '' ?>>
                    <div class="detail-info-grid">
                        <div class="detail-info-item">
                            <span class="detail-info-label">NAMA LENGKAP</span>
                            <span class="detail-info-value"><?= esc($ang['nama_lengkap']) ?></span>
                        </div>
                        <div class="detail-info-item">
                            <span class="detail-info-label">NAMA PANGGILAN</span>
                            <span class="detail-info-value"><?= esc($ang['nama_panggilan'] ?? '-') ?></span>
                        </div>
                        <div class="detail-info-item">
                            <span class="detail-info-label">TEMPAT LAHIR</span>
                            <span class="detail-info-value"><?= esc($ang['tempat_lahir'] ?? '-') ?></span>
                        </div>
                        <div class="detail-info-item">
                            <span class="detail-info-label">TANGGAL LAHIR</span>
                            <span class="detail-info-value">
                                <i class="fas fa-birthday-cake" style="color:var(--primary)"></i>
                                <?= tglIndoShort($ang['tgl_lahir'] ?? null) ?>
                            </span>
                        </div>
                        <div class="detail-info-item detail-info-full">
                            <span class="detail-info-label">ALAMAT</span>
                            <span class="detail-info-value"><?= ! empty($ang['alamat']) ? nl2br(esc($ang['alamat'])) : '-' ?></span>
                        </div>
                        <div class="detail-info-item">
                            <span class="detail-info-label">NO WA</span>
                            <span class="detail-info-value">
                                <i class="fab fa-whatsapp" style="color:#25d366"></i>
                                <?= esc($ang['no_wa_pkl'] ?? '-') ?>
                            </span>
                        </div>
                        <div class="detail-info-item">
                            <span class="detail-info-label">EMAIL</span>
                            <span class="detail-info-value">
                                <i class="fas fa-envelope" style="color:var(--primary)"></i>
                                <?= esc($ang['email'] ?? '-') ?>
                            </span>
                        </div>
                        <div class="detail-info-item">
                            <span class="detail-info-label">JENIS KELAMIN</span>
                            <span class="detail-info-value"><?= $jk ?></span>
                        </div>
                        <?php if ($isInstansi): ?>
                            <div class="detail-info-item">
                                <span class="detail-info-label">JURUSAN</span>
                                <span class="detail-info-value"><?= esc($ang['jurusan'] ?? '-') ?></span>
                            </div>
                        <?php endif; ?>
                        <div class="detail-info-item">
                            <span class="detail-info-label">USERNAME</span>
                            <span class="detail-info-value">
                                <i class="fas fa-at" style="color:var(--primary)"></i>
                                <?= esc($ang['username'] ?? '-') ?>
                            </span>
                        </div>
                        <div class="detail-info-item">
                            <span class="detail-info-label">STATUS</span>
                            <span class="detail-info-value"><?= $statusAnggota ?></span>
                        </div>
                    </div>
                    <div class="detail-anggota-actions">
                        <a href="<?= base_url('admin/manajemen-pkl/pkl/edit/' . $ang['id_pkl']) ?>"
                            class="btn-detail-edit">
                            <i class="fas fa-pen"></i> Edit
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

</div>

<!-- ── Sticky Footer ── -->
<div class="detail-sticky-footer">
    <a href="<?= $backUrl ?>" class="btn-detail-back">
        <i class="fas fa-arrow-left"></i> Kembali
    </a>
</div>
