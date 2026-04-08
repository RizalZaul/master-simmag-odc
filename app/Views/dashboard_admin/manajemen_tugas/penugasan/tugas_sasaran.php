<?php

/**
 * Views/dashboard_admin/manajemen_tugas/penugasan/tugas_sasaran.php
 * Wizard Step 2: Pilih Sasaran Tugas
 */
?>

<div class="welcome-card mtugas-page-hero">
    <div>
        <h2 class="page-heading">Pilih Sasaran Tugas</h2>
        <p class="page-subheading">Langkah 2: Tentukan siapa yang harus mengerjakan tugas ini</p>
    </div>
    <a href="<?= base_url('admin/manajemen-tugas/tugas/tambah') ?>" class="btn-mpkl-cancel mtugas-link-reset">
        <i class="fas fa-arrow-left"></i> Kembali ke Ketentuan
    </a>
</div>

<div class="step-indicator">
    <div class="step-item">
        <div class="step-circle done"><i class="fas fa-check"></i></div>
        <span class="step-label">Ketentuan Tugas</span>
    </div>
    <div class="step-line done"></div>
    <div class="step-item">
        <div class="step-circle active">2</div>
        <span class="step-label active">Pilih Sasaran</span>
    </div>
</div>

<div class="mpkl-card mtugas-sasaran-card">

    <!-- ── Tab Nav ── -->
    <div class="mpkl-tab-nav mtugas-sasaran-tab-nav">
        <button class="mpkl-tab-btn active" data-target="tab-mandiri" id="tabBtnMandiri">
            <i class="fas fa-user"></i> Individu
        </button>
        <button class="mpkl-tab-btn" data-target="tab-kelompok">
            <i class="fas fa-users"></i> Kelompok
        </button>
        <button class="mpkl-tab-btn" data-target="tab-tim">
            <i class="fas fa-user-friends"></i> Tim Tugas
        </button>
    </div>

    <!-- ════ TAB: INDIVIDU (MANDIRI) ════ -->
    <div class="mpkl-tab-content active" id="tab-mandiri">
        <div class="mtugas-search-strip">
            <div class="mtugas-filter-toolbar">
                <div class="mtugas-filter-field">
                    <input type="text" id="cariMandiri" class="mpkl-input"
                        placeholder="Cari nama PKL...">
                </div>
                <button type="button" class="btn-reset-filter-tim mtugas-search-reset" id="btnResetMandiriSearch">
                    <i class="fas fa-redo"></i> Reset
                </button>
            </div>
        </div>
        <div class="mtugas-mobile-list-wrap" id="mobileMandiriWrap">
            <div class="mtugas-mobile-list-head">
                <label class="mtugas-mobile-list-check">
                    <input type="checkbox" id="checkAllMandiriMobile">
                </label>
                <div class="mtugas-mobile-list-name">Nama</div>
                <div class="mtugas-mobile-list-action">
                    <i class="fas fa-chevron-down"></i>
                </div>
            </div>
            <div class="mtugas-mobile-list-body" id="mobileMandiriList">
                <div class="mtugas-mobile-list-empty">Memuat data...</div>
            </div>
        </div>

        <div class="mtugas-table-shell mtugas-desktop-table">
            <table class="mpkl-table mtugas-full-table" id="tableMandiri">
                <thead>
                    <tr>
                        <th class="mtugas-check-col"><input type="checkbox" id="checkAllMandiri"></th>
                        <th>Nama Lengkap</th>
                        <th>Instansi</th>
                        <th>Kelompok</th>
                    </tr>
                </thead>
                <tbody id="tbodyMandiri">
                    <tr>
                        <td colspan="4" class="mtugas-empty-cell">Memuat data...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ════ TAB: KELOMPOK ════ -->
    <div class="mpkl-tab-content" id="tab-kelompok">
        <div class="mtugas-search-strip">
            <div class="mtugas-filter-toolbar">
                <div class="mtugas-filter-field">
                    <input type="text" id="cariKelompok" class="mpkl-input"
                        placeholder="Cari nama kelompok...">
                </div>
                <button type="button" class="btn-reset-filter-tim mtugas-search-reset" id="btnResetKelompokSearch">
                    <i class="fas fa-redo"></i> Reset
                </button>
            </div>
        </div>
        <div class="mtugas-table-shell">
            <table class="mpkl-table mtugas-full-table" id="tableKelompok">
                <thead>
                    <tr>
                        <th class="mtugas-check-col"><input type="checkbox" id="checkAllKelompok"></th>
                        <th>Nama Kelompok</th>
                        <th>Instansi</th>
                    </tr>
                </thead>
                <tbody id="tbodyKelompok">
                    <tr>
                        <td colspan="3" class="mtugas-empty-cell">Memuat data...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ════ TAB: TIM TUGAS ════ -->
    <div class="mpkl-tab-content" id="tab-tim">

        <!-- Card Tim List -->
        <div class="tim-main-card mtugas-tim-card">

            <div class="tim-card-header">
                <span class="tim-card-header-title">
                    <i class="fas fa-user-friends"></i> Tim Tugas
                </span>
            </div>

            <div class="tim-filter-body">
                <div class="mtugas-filter-toolbar">
                    <div class="mtugas-filter-field">
                        <label class="buat-tim-label"><i class="fas fa-search"></i> Cari Nama Tim</label>
                        <input type="text" id="cariNamaTim" class="tim-filter-input" placeholder="Ketik nama...">
                    </div>
                    <div class="tim-filter-actions">
                        <button type="button" class="btn-reset-filter-tim" id="btnResetFilterTim">
                            <i class="fas fa-redo"></i> Reset Filter
                        </button>
                    </div>
                </div>
            </div>

            <!-- Toolbar -->
            <div class="tim-toolbar">
                <button type="button" class="btn-mpkl-add" id="btnBuatTimBaru">
                    <i class="fas fa-plus"></i> Buat Tim Tugas Baru
                </button>
            </div>

            <!-- Tim Table -->
            <div class="mtugas-mobile-list-wrap" id="mobileTimWrap">
                <div class="mtugas-mobile-list-head mtugas-mobile-list-head-tim">
                    <label class="mtugas-mobile-list-check">
                        <input type="checkbox" id="checkAllTimMobile">
                    </label>
                    <div class="mtugas-mobile-list-no">No</div>
                    <div class="mtugas-mobile-list-name">Tim</div>
                    <div class="mtugas-mobile-list-action">
                        <i class="fas fa-chevron-down"></i>
                    </div>
                </div>
                <div class="mtugas-mobile-list-body" id="mobileTimList">
                    <div class="mtugas-mobile-list-empty">Memuat data tim...</div>
                </div>
            </div>

            <div class="mtugas-table-shell mtugas-desktop-table">
                <table class="mpkl-table mtugas-full-table" id="tableTimTugas">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="checkAllTim"></th>
                            <th>No</th>
                            <th>Nama Tim</th>
                            <th>Jumlah Anggota</th>
                            <th>Tgl Dibuat</th>
                            <th>Dipakai di</th>
                            <th class="mtugas-expand-col mtugas-text-center">Detail</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyTimTugas">
                        <tr class="tim-empty-row">
                            <td colspan="7" class="mtugas-empty-cell">Memuat data tim...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div><!-- /.tim-main-card -->

        <!-- Form Buat Tim Baru (hidden default) -->
        <div class="buat-tim-card mtugas-buat-tim-card" id="sectionBuatTim" style="display:none;">
            <div class="buat-tim-header">
                <i class="fas fa-plus-circle"></i> Buat Tim Tugas Baru
            </div>
            <div class="buat-tim-body">

                <label class="buat-tim-label">
                    <i class="fas fa-signature"></i> Nama Tim <span class="required-star">*</span>
                </label>
                <input type="text" id="inputNamaTim" class="buat-tim-input"
                    placeholder="Contoh: Team Backend PKL ITS...">

                <label class="buat-tim-label">
                    <i class="fas fa-align-left"></i> Deskripsi Tim
                </label>
                <textarea id="inputDeskripsiTim" class="buat-tim-input buat-tim-textarea"
                    rows="3" maxlength="255"
                    placeholder="Tambahkan deskripsi tim jika diperlukan..."></textarea>

                <div class="buat-tim-filter-row">
                    <div>
                        <label class="buat-tim-label"><i class="fas fa-search"></i> Cari Nama Anggota</label>
                        <input type="text" id="cariAnggotaTim" class="buat-tim-input" placeholder="Ketik nama...">
                    </div>
                    <div>
                        <label class="buat-tim-label"><i class="fas fa-filter"></i> Kategori PKL</label>
                        <select id="filterKategoriAnggota" class="buat-tim-select">
                            <option value="">Semua</option>
                            <option value="instansi">Dari Instansi</option>
                            <option value="mandiri">Mandiri</option>
                        </select>
                    </div>
                </div>

                <div class="mtugas-filter-actions-row">
                    <button type="button" class="btn-reset-filter-tim" id="btnResetAnggotaFilter">
                        <i class="fas fa-redo"></i> Reset Filter Anggota
                    </button>
                </div>

                <div class="mtugas-table-shell mtugas-table-shell-mobile-collapse">
                    <table class="anggota-table" id="tableAnggotaCalon">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="checkAllAnggota"></th>
                                <th class="mtugas-no-col">
                                    <span class="mtugas-mobile-label-full">NO</span>
                                    <span class="mtugas-mobile-label-short">No</span>
                                </th>
                                <th class="mtugas-name-col">
                                    <span class="mtugas-mobile-label-full">Nama Lengkap</span>
                                    <span class="mtugas-mobile-label-short">Nama</span>
                                </th>
                                <th class="mtugas-mobile-hide">Kategori PKL</th>
                                <th class="mtugas-mobile-hide">Kelompok/Mandiri</th>
                                <th class="mtugas-expand-col mtugas-text-center mtugas-detail-head">
                                    <span class="mtugas-mobile-label-full">Detail</span>
                                    <span class="mtugas-mobile-label-short"><i class="fas fa-chevron-down"></i></span>
                                </th>
                            </tr>
                        </thead>
                        <tbody id="tbodyAnggotaCalon">
                            <tr>
                                <td colspan="6" class="mtugas-empty-cell">Memuat data...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="anggota-counter" id="anggotaCounter">0 anggota dipilih</div>

                <div class="buat-tim-footer">
                    <div></div>
                    <div class="mtugas-inline-actions">
                        <button type="button" class="btn-mpkl-cancel" id="btnBatalBuatTim">
                            Batal
                        </button>
                        <button type="button" class="btn-mpkl-submit" id="btnSimpanTim">
                            <i class="fas fa-save"></i> Simpan Tim
                        </button>
                    </div>
                </div>

            </div>
        </div><!-- /.buat-tim-card -->

    </div><!-- /#tab-tim -->

    <!-- ── Sticky Footer ── -->
    <div class="sasaran-footer">
        <div>
            <span class="mtugas-footer-label">Total Sasaran Terpilih:</span>
            <strong id="totalTerpilih" class="mtugas-footer-value">0</strong>
        </div>
        <button type="button" class="btn-mpkl-submit" id="btnSimpanTugasFinal">
            <i class="fas fa-paper-plane"></i> Simpan & Tugaskan
        </button>
    </div>

</div><!-- /.mpkl-card -->
