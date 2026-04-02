<?php

/**
 * Views/dashboard_pkl/modul/index.php
 *
 * Halaman daftar kategori modul untuk role PKL.
 * Variables:
 *   $kategoriList  → array of ['id', 'nama_kategori', 'jumlah_modul',
 *                              'tgl_dibuat', 'tgl_diubah', 'color', 'icon']
 *   $swal_error    → flash error message
 */
?>

<?php if ($swal_error ?? null): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'error',
                title: 'Gagal!',
                text: '<?= addslashes($swal_error) ?>',
                confirmButtonColor: 'var(--primary)',
            });
        });
    </script>
<?php endif; ?>

<!-- ── Welcome Card ── -->
<div class="welcome-card">
    <h2 class="page-heading"><?= esc($page_title ?? 'Data Modul') ?></h2>
    <p class="page-subheading"><?= esc($page_subheading ?? 'Modul pembelajaran PKL') ?></p>
</div>

<!-- ── Search + Count + Reset ── -->
<div class="pkl-modul-search-bar">
    <div class="pkl-modul-search-input-wrap">
        <i class="fas fa-search pkl-modul-search-icon"></i>
        <input type="text"
            id="pklModulSearchKategori"
            class="pkl-modul-search-input"
            placeholder="Cari kategori...">
    </div>
    <div class="pkl-modul-search-actions">
        <span class="pkl-modul-count-badge" id="pklModulKategoriCount">
            <i class="fas fa-layer-group"></i>
            <span id="pklModulKategoriCountNum"><?= count($kategoriList ?? []) ?></span> Kategori
        </span>
        <button type="button" id="pklModulResetKategori" class="pkl-modul-btn-reset">
            <i class="fas fa-redo"></i>
            <span>Reset</span>
        </button>
    </div>
</div>

<!-- ── Section Title ── -->
<div class="pkl-modul-section-title">
    <i class="fas fa-th-large"></i>
    <span>SEMUA KATEGORI</span>
</div>

<!-- ── Kategori Cards ── -->
<div class="pkl-modul-card-list" id="pklModulCardList">

    <?php if (empty($kategoriList)): ?>
        <div class="pkl-modul-empty-state">
            <i class="fas fa-inbox"></i>
            <p>Belum ada kategori modul yang tersedia.</p>
        </div>
    <?php else: ?>

        <?php foreach ($kategoriList as $kat): ?>
            <?php
            $jumlah  = (int) ($kat['jumlah_modul'] ?? 0);
            $isAktif = $jumlah > 0;
            $color   = esc($kat['color'] ?? 'teal');
            $icon    = esc($kat['icon']  ?? 'fa-book-open');
            ?>
            <a href="<?= base_url('pkl/modul/kategori/' . (int) $kat['id']) ?>"
                class="pkl-kat-card <?= $isAktif ? '' : 'pkl-kat-card--kosong' ?>"
                data-nama="<?= esc(strtolower($kat['nama_kategori'])) ?>">

                <div class="pkl-kat-card-icon pkl-kat-icon-color-<?= $color ?>">
                    <i class="fas <?= $icon ?>"></i>
                </div>

                <div class="pkl-kat-card-info">
                    <span class="pkl-kat-card-nama"><?= esc($kat['nama_kategori']) ?></span>
                    <div class="pkl-kat-card-meta">
                        <span class="pkl-kat-badge-modul">
                            <i class="fas fa-file-alt"></i>
                            <?= $jumlah ?> Modul
                        </span>
                        <?php if ($isAktif): ?>
                            <span class="pkl-kat-badge-status pkl-kat-badge-aktif">
                                <i class="fas fa-check-circle"></i> Aktif
                            </span>
                        <?php else: ?>
                            <span class="pkl-kat-badge-status pkl-kat-badge-kosong">
                                <i class="fas fa-minus-circle"></i> Kosong
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="pkl-kat-card-arrow">
                    <i class="fas fa-chevron-right"></i>
                </div>

            </a>
        <?php endforeach; ?>

    <?php endif; ?>

</div>

<!-- ── Empty Search State (tersembunyi, muncul via JS) ── -->
<div class="pkl-modul-empty-state pkl-modul-empty-search" id="pklModulEmptySearch" style="display:none">
    <i class="fas fa-search"></i>
    <p>Tidak ada kategori yang cocok dengan pencarian.</p>
</div>

<script>
    var pklModulBaseUrl = '<?= rtrim(base_url('/'), '/') . '/' ?>';
</script>