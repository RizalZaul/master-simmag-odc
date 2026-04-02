<?php

/**
 * app/Views/dashboard_admin/data_modul/index.php
 *
 * Variables:
 *   $kategoriList    → array kategori siap tabel
 *   $kategoriOptions → array [['id', 'nama']]
 *   $modulList       → array data modul siap view
 *   $active_tab      → 'kategori' | 'modul'
 *   $welcome_heading
 *   $welcome_subheading
 */

function fmtDt(?string $dt): string
{
    if (! $dt) {
        return '-';
    }

    static $bln = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    $ts = strtotime($dt);
    if ($ts === false) {
        return '-';
    }

    return sprintf(
        '%02d %s %s %02d:%02d',
        (int) date('d', $ts),
        $bln[(int) date('n', $ts)],
        date('Y', $ts),
        (int) date('H', $ts),
        (int) date('i', $ts)
    );
}

$swalSuccess = session()->getFlashdata('swal_success');
$swalError   = session()->getFlashdata('swal_error');
$modulJson   = json_encode($modulList ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>

<?php if ($swalSuccess): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: '<?= addslashes($swalSuccess) ?>',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
            });
        });
    </script>
<?php endif; ?>

<?php if ($swalError): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'error',
                title: 'Gagal!',
                text: '<?= addslashes($swalError) ?>',
                confirmButtonColor: 'var(--primary)',
            });
        });
    </script>
<?php endif; ?>

<?php
$modulMode = (($active_tab ?? 'kategori') === 'modul')
    ? (string) service('request')->getGet('mode')
    : 'list';
$isModulChildMode = in_array($modulMode, ['create', 'detail', 'edit'], true);
?>

<div class="welcome-card">
    <h2 class="page-heading"><?= esc($welcome_heading ?? 'Data Modul') ?></h2>
    <p class="page-subheading"><?= esc($welcome_subheading ?? 'Kelola kategori modul dan materi pembelajaran dalam satu tempat.') ?></p>
</div>

<div class="dm-tab-nav" id="dmTabNav" <?= $isModulChildMode ? 'style="display:none"' : '' ?>>
    <button class="dm-tab-btn <?= ($active_tab ?? 'kategori') === 'kategori' ? 'active' : '' ?>"
        data-tab="kategori" type="button">
        <i class="fas fa-tags"></i>
        <span>Kategori Modul</span>
    </button>
    <button class="dm-tab-btn <?= ($active_tab ?? 'kategori') === 'modul' ? 'active' : '' ?>"
        data-tab="modul" type="button">
        <i class="fas fa-file-alt"></i>
        <span>Modul</span>
    </button>
</div>

<div class="dm-tab-content <?= ($active_tab ?? 'kategori') === 'kategori' ? 'active' : '' ?>"
    id="tab-kategori">

    <div class="dm-toolbar">
        <div class="dm-search-wrap">
            <i class="fas fa-search dm-search-icon"></i>
            <input type="text" id="searchKategori" class="dm-search-input"
                placeholder="Cari nama kategori...">
        </div>
        <button type="button" id="btnResetKategori" class="btn-dm-secondary">
            <i class="fas fa-redo"></i>
            <span>Reset</span>
        </button>
        <button type="button" id="btnTambahKategori" class="btn-dm-primary">
            <i class="fas fa-plus"></i>
            <span>Tambah</span>
        </button>
        <button type="button" id="btnKembaliKategori" class="btn-dm-primary" style="display:none">
            <i class="fas fa-arrow-left"></i>
            <span>Kembali</span>
        </button>
    </div>

    <div id="dmFormKategoriWrap" style="display:none">
        <div class="dm-form-card">

            <div class="dm-form-header">
                <i class="fas fa-pen"></i>
                <span id="dmFormKategoriTitle">Tambah Kategori Modul</span>
            </div>

            <form id="dmFormKategori" method="post"
                action="<?= base_url('admin/data-modul/kategori/store') ?>">
                <?= csrf_field() ?>

                <div class="dm-form-field">
                    <label class="dm-form-label" for="inputNamaKategori">
                        <i class="fas fa-tag"></i>
                        Nama Kategori
                        <span class="dm-required">*</span>
                    </label>
                    <input type="text"
                        name="nama_kategori"
                        id="inputNamaKategori"
                        class="dm-form-input"
                        placeholder="Masukkan nama kategori"
                        maxlength="100"
                        autocomplete="off"
                        required>
                </div>

                <div class="dm-form-footer">
                    <button type="button" id="btnBatalKategori" class="btn-dm-batal">
                        <i class="fas fa-times"></i> Batal
                    </button>
                    <button type="submit" class="btn-dm-simpan">
                        <i class="fas fa-save"></i> Simpan
                    </button>
                </div>

            </form>
        </div>
    </div>

    <div class="dm-table-card">
        <div class="dm-table-wrap">
            <table id="tabelKategori" class="dm-table">
                <thead>
                    <tr>
                        <th class="col-no">No</th>
                        <th>Nama Kategori</th>
                        <th>Tanggal Dibuat</th>
                        <th>Tanggal Diubah</th>
                        <th class="col-aksi">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (! empty($kategoriList)): ?>
                        <?php foreach ($kategoriList as $i => $row): ?>
                            <tr>
                                <td class="dt-no-col text-center"><?= $i + 1 ?></td>
                                <td><?= esc($row['nama_kategori']) ?></td>
                                <td class="text-nowrap"><?= fmtDt($row['tgl_dibuat']) ?></td>
                                <td class="text-nowrap"><?= fmtDt($row['tgl_diubah']) ?></td>
                                <td class="text-center">
                                    <button type="button"
                                        class="btn-tbl-edit btn-edit-kategori"
                                        data-id="<?= (int) $row['id'] ?>"
                                        data-nama="<?= esc($row['nama_kategori']) ?>"
                                        title="Edit Kategori">
                                        <i class="fas fa-pen"></i>
                                    </button>
                                    <button type="button"
                                        class="btn-tbl-delete btn-delete-kategori"
                                        data-id="<?= (int) $row['id'] ?>"
                                        data-nama="<?= esc($row['nama_kategori']) ?>"
                                        data-jumlah="<?= (int) ($row['jumlah_modul'] ?? 0) ?>"
                                        title="Hapus Kategori">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<div class="dm-tab-content <?= ($active_tab ?? 'kategori') === 'modul' ? 'active' : '' ?>"
    id="tab-modul">

    <div class="dm-modul-topbar" id="dmModulTopbar" <?= $isModulChildMode ? 'style="display:none"' : '' ?>>
        <button type="button" id="btnTambahModul" class="btn-dm-primary">
            <i class="fas fa-plus"></i>
            <span>Tambah</span>
        </button>
        <button type="button" id="btnToggleModulFilter" class="btn-dm-dark">
            <i class="fas fa-filter"></i>
            <span>Filter</span>
        </button>
    </div>

    <div id="dmModulListSection">
        <div class="dm-filter-panel" id="dmModulFilterPanel" style="display:none">
            <div class="dm-filter-header">
                <div class="dm-filter-title">
                    <i class="fas fa-filter"></i>
                    <span>Filter Data Modul</span>
                </div>
                <button type="button" id="btnResetModulFilter" class="btn-filter-reset">
                    <i class="fas fa-redo"></i> Reset
                </button>
            </div>

            <div class="dm-filter-grid">
                <div class="dm-filter-field">
                    <label class="dm-filter-label" for="searchModul">
                        <i class="fas fa-search"></i> Cari Nama Modul
                    </label>
                    <input type="text" id="searchModul" class="dm-search-input dm-filter-input"
                        placeholder="Ketik nama modul...">
                </div>

                <div class="dm-filter-field">
                    <label class="dm-filter-label" for="filterKategoriModul">
                        <i class="fas fa-tags"></i> Kategori
                    </label>
                    <select id="filterKategoriModul" class="dm-filter-select">
                        <option value="">Semua Kategori</option>
                        <?php foreach (($kategoriOptions ?? []) as $kategori): ?>
                            <option value="<?= esc($kategori['nama']) ?>"><?= esc($kategori['nama']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="dm-table-card">
            <div class="dm-table-wrap">
                <table id="tabelModul" class="dm-table dm-modul-table">
                    <thead>
                        <tr>
                            <th class="col-no">No</th>
                            <th>Nama Modul</th>
                            <th>Kategori</th>
                            <th>Modul</th>
                            <th>Tanggal Diubah</th>
                            <th class="col-aksi">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (! empty($modulList)): ?>
                            <?php foreach ($modulList as $i => $row): ?>
                                <tr>
                                    <td class="dt-no-col text-center"><?= $i + 1 ?></td>
                                    <td>
                                        <strong><?= esc($row['nama_modul']) ?></strong>
                                    </td>
                                    <td><?= esc($row['nama_kategori']) ?></td>
                                    <td>
                                        <?php
                                        $assetUrl = $row['asset_url'] ?? null;
                                        $assetTarget = $row['asset_target'] ?? null;
                                        $assetRel = $assetTarget === '_blank' ? 'noopener noreferrer' : null;
                                        $assetClickable = ! empty($assetUrl);
                                        ?>
                                        <?php if ($assetClickable): ?>
                                            <a href="<?= esc($assetUrl) ?>"
                                                class="dm-modul-asset-link"
                                                title="<?= esc($row['asset_label'] ?? $row['table_asset']) ?>"
                                                <?= $assetTarget ? 'target="' . esc($assetTarget) . '"' : '' ?>
                                                <?= $assetRel ? 'rel="' . esc($assetRel) . '"' : '' ?>>
                                                <span class="dm-modul-asset-cell">
                                                    <i class="<?= esc($row['icon_class']) ?>"></i>
                                                    <span class="dm-modul-asset-text">
                                                        <?= esc($row['table_label']) ?>
                                                    </span>
                                                </span>
                                            </a>
                                        <?php else: ?>
                                            <span class="dm-modul-asset-link is-disabled" title="<?= esc($row['table_asset']) ?>">
                                                <span class="dm-modul-asset-cell">
                                                    <i class="<?= esc($row['icon_class']) ?>"></i>
                                                    <span class="dm-modul-asset-text">
                                                        <?= esc($row['table_label']) ?>
                                                    </span>
                                                </span>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-nowrap"><?= fmtDt($row['tgl_diubah']) ?></td>
                                    <td class="text-center col-aksi-modul">
                                        <button type="button"
                                            class="btn-tbl-view btn-view-modul"
                                            data-id="<?= (int) $row['id'] ?>"
                                            title="Lihat Detail">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button type="button"
                                            class="btn-tbl-delete btn-delete-modul"
                                            data-id="<?= (int) $row['id'] ?>"
                                            data-nama="<?= esc($row['nama_modul']) ?>"
                                            title="Hapus Modul">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="dmModulFormSection" style="display:none">
        <div class="dm-form-card dm-modul-form-card">
            <div class="dm-form-header dm-modul-form-header">
                <i class="fas fa-plus-circle" id="dmModulFormIcon"></i>
                <span id="dmModulFormTitle">Tambah Modul</span>
            </div>

            <div class="dm-form-divider"></div>

            <form id="dmFormModul"
                action="<?= base_url('admin/data-modul/modul/store') ?>"
                method="post"
                enctype="multipart/form-data">
                <?= csrf_field() ?>
                <input type="hidden" id="dmFormMode" value="create">
                <input type="hidden" id="dmFormModulId" value="">

                <div class="dm-modul-form-grid">
                    <div class="dm-form-field dm-form-field-full">
                        <label class="dm-form-label" for="inputNamaModul">
                            <i class="fas fa-book"></i>
                            Nama Modul
                            <span class="dm-required">*</span>
                        </label>
                        <input type="text"
                            name="nama_modul"
                            id="inputNamaModul"
                            class="dm-form-input"
                            placeholder="Masukkan nama modul"
                            maxlength="150"
                            autocomplete="off"
                            required>
                    </div>

                    <div class="dm-form-field dm-form-field-full">
                        <label class="dm-form-label" for="inputKategoriModul">
                            <i class="fas fa-tags"></i>
                            Kategori Modul
                            <span class="dm-required">*</span>
                        </label>
                        <select name="id_kat_m" id="inputKategoriModul" class="dm-form-input dm-form-select" required>
                            <option value="">Pilih Kategori</option>
                            <?php foreach (($kategoriOptions ?? []) as $kategori): ?>
                                <option value="<?= (int) $kategori['id'] ?>"><?= esc($kategori['nama']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="dm-form-field dm-form-field-full">
                        <label class="dm-form-label" for="inputDeskripsiModul">
                            <i class="fas fa-align-left"></i>
                            Deskripsi
                            <span class="dm-required">*</span>
                        </label>
                        <textarea
                            name="ket_modul"
                            id="inputDeskripsiModul"
                            class="dm-form-textarea"
                            rows="5"
                            maxlength="500"
                            placeholder="Masukkan deskripsi modul (maksimal 500 karakter)"
                            required></textarea>
                        <div class="dm-text-counter">
                            <span id="dmDeskripsiCounter">0</span> / 500 karakter
                        </div>
                    </div>

                    <div class="dm-form-field dm-form-field-full">
                        <label class="dm-form-label">
                            <i class="fas fa-list"></i>
                            Tipe Modul
                            <span class="dm-required">*</span>
                        </label>
                        <div class="dm-radio-group">
                            <label class="dm-radio-option">
                                <input type="radio" name="tipe_modul" value="link" checked>
                                <span class="dm-radio-custom"></span>
                                <i class="fas fa-link"></i>
                                <span>Link</span>
                            </label>
                            <label class="dm-radio-option">
                                <input type="radio" name="tipe_modul" value="file">
                                <span class="dm-radio-custom"></span>
                                <i class="fas fa-file"></i>
                                <span>File</span>
                            </label>
                        </div>
                    </div>

                    <div class="dm-form-field dm-form-field-full" id="dmUrlFieldWrap">
                        <label class="dm-form-label" for="inputUrlModul">
                            <i class="fas fa-link"></i>
                            URL Link
                            <span class="dm-required">*</span>
                        </label>
                        <input type="url"
                            name="url_modul"
                            id="inputUrlModul"
                            class="dm-form-input"
                            pattern="https://.*"
                            placeholder="https://example.com/modul">
                    </div>

                    <div class="dm-form-field dm-form-field-full" id="dmFileFieldWrap" style="display:none">
                        <label class="dm-form-label" for="inputFileModul">
                            <i class="fas fa-file-upload"></i>
                            Upload File
                            <span class="dm-required">*</span>
                        </label>

                        <input type="file"
                            name="file_modul"
                            id="inputFileModul"
                            class="dm-file-input"
                            accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.zip,.rar">

                        <div class="dm-file-dropzone" id="dmFileDropzone">
                            <div class="dm-file-dropzone-icon">
                                <i class="fas fa-cloud-upload-alt"></i>
                            </div>
                            <p class="dm-file-dropzone-title">Drag &amp; Drop file di sini</p>
                            <p class="dm-file-dropzone-subtitle">atau klik untuk memilih file</p>
                            <div class="dm-file-dropzone-info">
                                Maksimal: 300 MB<br>
                                Format: PDF, DOC, DOCX, PPT, PPTX, XLS, XLSX, ZIP, RAR
                            </div>
                            <div class="dm-file-selected" id="dmSelectedFileName">Belum ada file dipilih</div>
                        </div>

                        <div class="dm-current-file" id="dmCurrentFileInfo" style="display:none"></div>
                    </div>
                </div>

                <div class="dm-form-footer dm-modul-form-footer">
                    <button type="button" class="btn-dm-secondary" id="btnFormKembaliModul">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </button>
                    <button type="submit" class="btn-dm-primary" id="btnSubmitModulForm">
                        <i class="fas fa-save"></i>
                        <span id="dmSubmitModulLabel">Tambah</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="dmModulDetailSection" style="display:none">
        <div class="dm-detail-card">
            <div class="dm-detail-header">
                <i class="fas fa-info-circle"></i>
                <span id="dmModulDetailTitle">Detail Modul</span>
            </div>

            <div class="dm-form-divider"></div>

            <div class="dm-detail-body" id="dmModulDetailBody"></div>

            <div class="dm-detail-footer">
                <button type="button" class="btn-dm-secondary" id="btnDetailKembali">
                    <i class="fas fa-arrow-left"></i> Kembali
                </button>
                <button type="button" class="btn-dm-edit" id="btnDetailEdit">
                    <i class="fas fa-pen-square"></i> Ubah
                </button>
            </div>
        </div>
    </div>

</div>

<form id="formDeleteKategori" method="post" style="display:none">
    <?= csrf_field() ?>
</form>

<script>
    var dmBaseUrl = '<?= rtrim(base_url('/'), '/') . '/' ?>';
    var dmActiveTab = '<?= $active_tab ?? 'kategori' ?>';
    var dmModulList = <?= $modulJson ?: '[]' ?>;
    var dmCsrfCookieName = 'csrf_cookie_name';
</script>
