<?php

/**
 * Views/dashboard_admin/manajemen_pkl/_tab_instansi.php
 * Variables: $isFormMode, $currentMode, $currentEditId, $editRow, $kotaList, $instansiList
 */
?>

<!-- ── Section: Table ── -->
<div class="mpkl-section" id="sectionTableInstansi" <?= $isFormMode ? 'style="display:none"' : '' ?>>
    <div class="mpkl-card">
        <div class="mpkl-toolbar">
            <button class="btn-mpkl-add" id="btnTambahInstansi" type="button">
                <i class="fas fa-plus"></i> Tambah
            </button>
            <button class="btn-mpkl-filter" id="btnFilterInstansi" type="button">
                <i class="fas fa-filter"></i> Filter
            </button>
        </div>

        <div class="mpkl-filter-panel" id="filterPanelInstansi" style="display:none">
            <div class="filter-panel-header">
                <span><i class="fas fa-filter"></i> Filter Data Instansi</span>
                <button class="btn-filter-reset" id="btnResetFilterInstansi" type="button">
                    <i class="fas fa-redo"></i> Reset
                </button>
            </div>
            <div class="filter-panel-body filter-pkl-grid">
                <div class="filter-row-full">
                    <label class="filter-label"><i class="fas fa-search"></i> Cari Nama Instansi</label>
                    <input type="text" id="filterNamaInstansi" class="filter-input" placeholder="Ketik nama instansi...">
                </div>
                <div class="filter-row-half">
                    <label class="filter-label"><i class="fas fa-tags"></i> Kategori</label>
                    <select id="filterKategoriInstansi" class="filter-select">
                        <option value="">Semua Kategori</option>
                        <option value="Kuliah">Kuliah</option>
                        <option value="SMK Sederajat">SMK Sederajat</option>
                    </select>
                </div>
                <div class="filter-row-half">
                    <label class="filter-label"><i class="fas fa-map-marker-alt"></i> Kota</label>
                    <select id="filterKotaInstansi" class="filter-select">
                        <option value="">Semua Kota</option>
                        <?php foreach ($kotaList as $kota): ?>
                            <option value="<?= esc($kota) ?>"><?= esc($kota) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="mpkl-table-wrap">
            <table id="tabelInstansi" class="mpkl-table" width="100%">
                <thead>
                    <tr>
                        <th class="col-no">No</th>
                        <th>Nama Instansi</th>
                        <th>Kategori Instansi</th>
                        <th class="col-alamat">Alamat</th>
                        <th class="col-aksi">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($instansiList as $i => $row): ?>
                        <tr>
                            <td class="text-center"><?= $i + 1 ?></td>
                            <td><strong><?= esc($row['nama_instansi']) ?></strong></td>
                            <td>
                                <span class="badge-kategori-instansi <?= esc($row['kategori_instansi']) ?>">
                                    <?= esc($row['kategori_label']) ?>
                                </span>
                            </td>
                            <td class="text-secondary-col"><?= esc($row['alamat_kota']) ?></td>
                            <td class="text-center">
                                <button class="btn-tbl-edit btn-edit-instansi" type="button" title="Edit"
                                    data-id="<?= $row['id_instansi'] ?>"
                                    data-nama="<?= esc($row['nama_instansi']) ?>"
                                    data-kategori="<?= esc($row['kategori_label']) ?>"
                                    data-alamat="<?= esc($row['alamat_instansi'] ?? '') ?>"
                                    data-kota="<?= esc($row['kota_instansi']) ?>">
                                    <i class="fas fa-pen"></i>
                                </button>
                                <button class="btn-tbl-delete btn-delete-instansi" type="button" title="Hapus"
                                    data-id="<?= $row['id_instansi'] ?>"
                                    data-nama="<?= esc($row['nama_instansi']) ?>">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ── Section: Form Tambah / Edit ── -->
<div class="mpkl-section" id="sectionFormInstansi" <?= $isFormMode ? '' : 'style="display:none"' ?>>
    <div class="mpkl-card">
        <div class="mpkl-form-header">
            <span id="formInstansiTitle">
                <?php if ($currentMode === 'edit'): ?>
                    <i class="fas fa-pen"></i> Ubah Instansi
                <?php else: ?>
                    <i class="fas fa-plus-circle"></i> Tambah Instansi Baru
                <?php endif; ?>
            </span>
        </div>
        <form id="formInstansi" novalidate>
            <input type="hidden" id="instansiEditId" value="<?= $currentEditId > 0 ? $currentEditId : '' ?>">
            <div class="mpkl-form-body">
                <div class="mpkl-form-field">
                    <label class="mpkl-label">
                        <i class="fas fa-tags"></i> Kategori Instansi <span class="required-star">*</span>
                    </label>
                    <select id="inputKategoriInstansi" name="kategori_instansi" class="mpkl-select" required>
                        <option value="" <?= empty($editRow['kategori_label']) ? 'selected' : '' ?> disabled>Pilih Kategori</option>
                        <option value="Kuliah" <?= ($editRow['kategori_label'] ?? '') === 'Kuliah' ? 'selected' : '' ?>>Kuliah</option>
                        <option value="SMK Sederajat" <?= ($editRow['kategori_label'] ?? '') === 'SMK Sederajat' ? 'selected' : '' ?>>SMK Sederajat</option>
                    </select>
                </div>
                <div class="mpkl-form-field">
                    <label class="mpkl-label">
                        <i class="fas fa-building"></i> Nama Instansi <span class="required-star">*</span>
                    </label>
                    <input type="text" id="inputNamaInstansi" name="nama_instansi" class="mpkl-input"
                        placeholder="Masukkan nama instansi" required
                        value="<?= esc($editRow['nama_instansi'] ?? '') ?>">
                </div>
                <div class="mpkl-form-field mpkl-form-field-full">
                    <label class="mpkl-label">
                        <i class="fas fa-map-marker-alt"></i> Alamat <span class="required-star">*</span>
                    </label>
                    <textarea id="inputAlamatInstansi" name="alamat_instansi" class="mpkl-textarea" rows="3"
                        placeholder="Masukkan alamat lengkap instansi" required><?= esc($editRow['alamat_instansi'] ?? '') ?></textarea>
                </div>
                <div class="mpkl-form-field">
                    <label class="mpkl-label">
                        <i class="fas fa-city"></i> Kota <span class="required-star">*</span>
                    </label>
                    <select id="inputKotaInstansi" name="kota_instansi" class="mpkl-select-kota" required>
                        <option value=""></option>
                        <?php foreach ($kotaList as $kota): ?>
                            <option value="<?= esc($kota) ?>" <?= ($editRow['kota_instansi'] ?? '') === $kota ? 'selected' : '' ?>>
                                <?= esc($kota) ?>
                            </option>
                        <?php endforeach; ?>
                        <?php
                        $editKota = $editRow['kota_instansi'] ?? '';
                        if ($editKota && ! in_array($editKota, $kotaList)): ?>
                            <option value="<?= esc($editKota) ?>" selected><?= esc($editKota) ?></option>
                        <?php endif; ?>
                    </select>
                    <span class="mpkl-hint"><i class="fas fa-info-circle"></i> Ketik nama kota baru jika tidak ada dalam pilihan</span>
                </div>
            </div>
            <div class="mpkl-form-footer">
                <button type="button" class="btn-mpkl-cancel" id="btnBatalInstansi">
                    <i class="fas fa-arrow-left"></i> Batal
                </button>
                <button type="submit" class="btn-mpkl-submit" id="btnSubmitInstansi">
                    <i class="fas fa-save"></i>
                    <span id="submitInstansiLabel"><?= $currentMode === 'edit' ? 'Simpan' : 'Tambah' ?></span>
                </button>
            </div>
        </form>
    </div>
</div>
