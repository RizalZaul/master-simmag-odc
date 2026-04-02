<?php

/**
 * Views/dashboard_admin/manajemen_tugas/penugasan/_tab_tugas.php
 */
?>

<div class="mpkl-card">

    <div class="mpkl-toolbar">
        <a href="<?= base_url('admin/manajemen-tugas/tugas/tambah') ?>" class="btn-mpkl-add mtugas-link-reset">
            <i class="fas fa-plus"></i> Tambah Tugas
        </a>
        <button class="btn-mpkl-filter" id="btnFilterTugas" type="button">
            <i class="fas fa-filter"></i> Filter
        </button>
    </div>

    <!-- ── Filter Panel ── -->
    <div class="mpkl-filter-panel" id="filterPanelTugas" style="display:none">
        <div class="filter-panel-header">
            <span><i class="fas fa-filter"></i> Filter Data Tugas</span>
            <button class="btn-filter-reset" id="btnResetFilterTugas" type="button">
                <i class="fas fa-redo"></i> Reset
            </button>
        </div>
        <div class="filter-panel-body filter-pkl-grid">
            <div class="filter-row-full">
                <label class="filter-label"><i class="fas fa-search"></i> Cari Nama Tugas</label>
                <input type="text" id="fNamaTugas" class="filter-input" placeholder="Ketik nama tugas...">
            </div>
            <div class="filter-row-full">
                <label class="filter-label"><i class="fas fa-tags"></i> Kategori Tugas</label>
                <select id="fKategoriTugas" class="filter-input filter-select-kategori-tugas">
                    <option value="">Semua Kategori</option>
                    <?php foreach (($kategoriList ?? []) as $kat): ?>
                        <option value="<?= esc($kat['id_kat_tugas']) ?>">
                            <?= esc($kat['nama_kat_tugas']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>

    <!-- ── Table (tanpa DT built-in search — pakai filter panel di atas) ── -->
    <div class="mpkl-table-wrap">
        <table class="mpkl-table tugas-table mtugas-full-table" id="tableTugas">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Tugas</th>
                    <th>Kategori</th>
                    <th>Mode</th>
                    <th>Deadline</th>
                    <th class="mtugas-text-center">Aksi</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>

</div>
