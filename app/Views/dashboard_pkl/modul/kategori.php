<?php

/**
 * Views/dashboard_pkl/modul/kategori.php
 *
 * Halaman daftar modul dalam satu kategori untuk role PKL.
 * Variables:
 *   $kategori   → array ['id_kat_m', 'nama_kat_m', ...]
 *   $modulList  → array of formatted modul rows
 */

// ── Helper format datetime ──────────────────────────────────────────
function fmtDtPklModul(?string $dt): string
{
    if (! $dt) return '-';
    static $bln = [
        '',
        'Jan',
        'Feb',
        'Mar',
        'Apr',
        'Mei',
        'Jun',
        'Jul',
        'Agu',
        'Sep',
        'Okt',
        'Nov',
        'Des'
    ];
    $ts = strtotime($dt);
    if ($ts === false) return '-';
    return sprintf(
        '%02d %s %s %02d:%02d',
        (int) date('d', $ts),
        $bln[(int) date('n', $ts)],
        date('Y', $ts),
        (int) date('H', $ts),
        (int) date('i', $ts)
    );
}

$namaKategori = esc($kategori['nama_kat_m'] ?? 'Kategori');
?>

<!-- ── Back + Heading ── -->
<div class="welcome-card">
    <div class="pkl-modul-back-row">
        <a href="<?= base_url('pkl/modul') ?>" class="pkl-modul-back-link">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
        <h2 class="pkl-modul-kat-heading"><?= $namaKategori ?></h2>
        <p class="pkl-modul-kat-subheading">Daftar modul dalam kategori ini</p>
    </div>
</div>

<!-- ── Search + Reset ── -->
<div class="pkl-modul-kat-search-bar">
    <div class="pkl-modul-search-input-wrap">
        <i class="fas fa-search pkl-modul-search-icon"></i>
        <input type="text"
            id="pklModulSearchModul"
            class="pkl-modul-search-input"
            placeholder="Cari modul...">
    </div>
    <button type="button" id="pklModulResetModul" class="pkl-modul-btn-reset">
        <i class="fas fa-redo"></i>
        <span>Reset</span>
    </button>
</div>

<!-- ── Tabel Modul ── -->
<div class="pkl-modul-table-card">
    <div class="pkl-modul-table-wrap">
        <table id="tabelModulPkl" class="pkl-modul-table" width="100%">
            <thead>
                <tr>
                    <th class="col-no">No</th>
                    <th>Nama Modul</th>
                    <th>Modul</th>
                    <th>Tanggal Ditambah</th>
                    <th>Tanggal Diubah</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($modulList as $i => $row): ?>
                    <tr>
                        <td class="dt-no-col text-center"><?= $i + 1 ?></td>
                        <td>
                            <strong><?= esc($row['nama_modul']) ?></strong>
                            <?php if ($row['ket_modul']): ?>
                                <div class="pkl-modul-deskripsi"><?= esc($row['ket_modul']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($row['asset_url']): ?>
                                <a href="<?= esc($row['asset_url']) ?>"
                                    <?= $row['asset_target'] === '_blank' ? 'target="_blank" rel="noopener noreferrer"' : '' ?>
                                    class="pkl-modul-asset-link pkl-modul-asset-<?= $row['tipe'] ?>">
                                    <i class="<?= esc($row['icon_class']) ?>"></i>
                                    <span><?= esc($row['asset_label'] ?? '-') ?></span>
                                </a>
                            <?php elseif ($row['tipe'] === 'file' && ! $row['file_exists']): ?>
                                <span class="pkl-modul-missing">
                                    <i class="fas fa-exclamation-circle"></i>
                                    File tidak tersedia
                                </span>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-nowrap"><?= fmtDtPklModul($row['tgl_dibuat']) ?></td>
                        <td class="text-nowrap"><?= fmtDtPklModul($row['tgl_diubah']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    var pklModulBaseUrl = '<?= rtrim(base_url('/'), '/') . '/' ?>';
    var pklModulPage = 'kategori';
</script>