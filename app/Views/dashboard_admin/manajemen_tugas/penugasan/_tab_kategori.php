<?php

/**
 * Views/dashboard_admin/manajemen_tugas/penugasan/_tab_kategori.php
 */
?>

<div class="mpkl-card">

    <!-- ── Toolbar: Custom Search + Reset + Tambah ── -->
    <div class="mpkl-toolbar mtugas-toolbar-between">
        <div class="mtugas-search-toolbar mtugas-search-toolbar-wide">
            <div class="mtugas-search-field">
                <i class="fas fa-search mtugas-search-icon"></i>
                <input type="text" id="searchKategori" class="mpkl-input mtugas-search-input"
                    placeholder="Cari nama kategori...">
            </div>
            <button type="button" class="btn-mpkl-filter" id="btnResetSearchKategori">
                <i class="fas fa-redo"></i> Reset
            </button>
        </div>
        <button class="btn-mpkl-add" id="btnTambahKategori" type="button">
            <i class="fas fa-plus"></i> Tambah
        </button>
    </div>

    <!-- ── Panel Form Tambah / Edit (hidden by default) ── -->
    <div id="panelFormKategori" style="display:none">
        <div class="mpkl-filter-panel">
            <div class="filter-panel-header">
                <span id="formKategoriPanelTitle"><i class="fas fa-tag"></i> Tambah Kategori Tugas</span>
                <button type="button" class="btn-filter-reset" id="btnBatalKategori">
                    <i class="fas fa-times"></i> Batal
                </button>
            </div>

            <form id="formKategori" novalidate>
                <input type="hidden" id="kategoriId">
                <div class="mtugas-kategori-form-grid">

                    <div class="filter-row-full">
                        <label class="filter-label"><i class="fas fa-tag"></i> Nama Kategori <span class="required-star">*</span></label>
                        <input type="text" id="namaKategori" class="filter-input"
                            maxlength="50"
                            placeholder="Contoh: Pemrograman Web" required>
                    </div>

                    <div class="filter-row-full">
                        <label class="filter-label"><i class="fas fa-layer-group"></i> Mode Pengumpulan <span class="required-star">*</span></label>
                        <div class="radio-group mtugas-radio-group">
                            <label class="radio-option radio-white">
                                <input type="radio" name="mode_pengumpulan" value="individu" checked>
                                <span class="radio-custom"></span> Individu
                            </label>
                            <label class="radio-option radio-white">
                                <input type="radio" name="mode_pengumpulan" value="kelompok">
                                <span class="radio-custom"></span> Kelompok
                            </label>
                        </div>
                    </div>

                </div>

                <div class="mtugas-form-actions">
                    <button type="submit" class="btn-mpkl-submit" id="btnSimpanKategori">
                        <i class="fas fa-save"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ── Table ── -->
    <div class="mpkl-table-wrap">
        <table class="mpkl-table kat-table mtugas-full-table" id="tableKategori">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Kategori</th>
                    <th>Mode Pengumpulan</th>
                    <th>Tgl Dibuat</th>
                    <th>Tgl Diubah</th>
                    <th class="mtugas-text-center">Aksi</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>

</div>
