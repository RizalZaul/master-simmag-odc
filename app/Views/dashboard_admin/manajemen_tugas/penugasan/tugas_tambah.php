<?php

/**
 * Views/dashboard_admin/manajemen_tugas/penugasan/tugas_tambah.php
 * Wizard Step 1: Ketentuan Tugas
 */
?>

<div class="welcome-card mtugas-page-hero">
    <div>
        <h2 class="page-heading">Tambah Tugas Baru</h2>
        <p class="page-subheading">Langkah 1: Isi ketentuan dan detail tugas</p>
    </div>
    <a href="<?= base_url('admin/manajemen-tugas/penugasan?tab=tugas') ?>" class="btn-mpkl-cancel mtugas-link-reset">
        <i class="fas fa-arrow-left"></i> Kembali ke List
    </a>
</div>

<div class="step-indicator">
    <div class="step-item">
        <div class="step-circle active">1</div>
        <span class="step-label active">Ketentuan Tugas</span>
    </div>
    <div class="step-line"></div>
    <div class="step-item">
        <div class="step-circle">2</div>
        <span class="step-label">Pilih Sasaran</span>
    </div>
</div>

<div class="mpkl-card">
    <div class="mpkl-card-header">
        <i class="fas fa-clipboard-list"></i>
        <div>
            <h3>Detail Ketentuan Tugas</h3>
            <p>Pastikan semua field bertanda bintang (*) diisi.</p>
        </div>
    </div>

    <form id="formKetentuanTugas" novalidate>
        <div class="mpkl-form-body filter-pkl-grid">

            <div class="mpkl-form-field filter-row-half">
                <label class="mpkl-label"><i class="fas fa-user-edit"></i> Editor</label>
                <input type="text" class="mpkl-input mtugas-readonly-input"
                    value="<?= esc($editor_nama ?? 'Admin') ?>"
                    readonly>
                <span class="mpkl-hint"><i class="fas fa-info-circle"></i> Otomatis diisi dari akun yang sedang login.</span>
            </div>

            <div class="mpkl-form-field filter-row-half">
                <label class="mpkl-label"><i class="fas fa-tags"></i> Kategori Tugas <span class="required-star">*</span></label>
                <select id="tugasKategori" class="mpkl-select mtugas-select2-field" required>
                    <option value="" selected disabled>-- Pilih Kategori --</option>
                    <?php foreach (($kategoriList ?? []) as $kat): ?>
                        <option value="<?= esc((string) $kat['id_kat_tugas']) ?>"
                            data-mode="<?= esc((string) $kat['mode_pengumpulan']) ?>">
                            <?= esc((string) $kat['nama_kat_tugas']) ?> (<?= ucfirst(esc((string) $kat['mode_pengumpulan'])) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mpkl-form-field filter-row-half">
                <label class="mpkl-label"><i class="fas fa-heading"></i> Nama Tugas <span class="required-star">*</span></label>
                <input type="text" id="tugasNama" class="mpkl-input" placeholder="Contoh: Membuat Artikel SEO" maxlength="50" required>
            </div>

            <div class="mpkl-form-field filter-row-full">
                <label class="mpkl-label"><i class="fas fa-align-left"></i> Deskripsi / Instruksi <span class="required-star">*</span></label>
                <textarea id="tugasDeskripsi" class="mpkl-input" rows="4" maxlength="255"
                    placeholder="Jelaskan detail tugas yang harus dikerjakan..." required></textarea>
            </div>

            <div class="mpkl-form-field filter-row-half">
                <label class="mpkl-label"><i class="fas fa-bullseye"></i> Target Jumlah Item <span class="required-star">*</span></label>
                <input type="number" id="tugasTarget" class="mpkl-input" min="1" value="1" required>
                <span class="mpkl-hint"><i class="fas fa-info-circle"></i> Jumlah file/link yang harus dikumpulkan siswa.</span>
            </div>

            <div class="mpkl-form-field filter-row-half">
                <label class="mpkl-label"><i class="fas fa-clock"></i> Tenggat Waktu (Deadline) <span class="required-star">*</span></label>
                <!-- altInput aktif: nilai internal Y-m-d H:i, tampilan H:i — d M Y -->
                <input type="text" id="tugasDeadline" class="mpkl-input" required>
            </div>

        </div>

        <div class="mpkl-form-footer mtugas-form-footer-end">
            <button type="button" class="btn-mpkl-submit" id="btnNextSasaran">
                Lanjut ke Pilih Sasaran <i class="fas fa-arrow-right"></i>
            </button>
        </div>
    </form>
</div>
