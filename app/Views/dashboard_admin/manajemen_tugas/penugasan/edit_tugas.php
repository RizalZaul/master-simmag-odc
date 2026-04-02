<?php

$detailUrl = base_url('admin/manajemen-tugas/tugas/detail/' . (int) ($tugas['id_tugas'] ?? 0));
$modeLabel = ucfirst((string) ($tugas['mode_pengumpulan'] ?? 'individu'));
?>

<div class="mtugas-detail-wrap">
    <div class="mtugas-back-row">
        <a href="<?= $detailUrl ?>" class="mtugas-back-link">
            <i class="fas fa-arrow-left"></i> Kembali ke Detail Tugas
        </a>
    </div>

    <div class="mtugas-detail-header">
        <div class="mtugas-detail-title">
            <i class="fas fa-pen"></i>
            <div>
                <h2>Ubah Tugas</h2>
                <p><?= esc($tugas['nama_tugas'] ?? '-') ?></p>
            </div>
        </div>
    </div>

    <div class="mpkl-card">
        <div class="mpkl-card-header">
            <i class="fas fa-edit"></i>
            <div>
                <h3>Perbarui Detail Tugas</h3>
                <p>Sasaran tidak dapat diubah dari halaman ini. Kategori hanya bisa dipilih dari mode pengumpulan yang sama.</p>
            </div>
        </div>

        <form id="formEditTugas" action="<?= base_url('admin/manajemen-tugas/tugas/update/' . (int) ($tugas['id_tugas'] ?? 0)) ?>" method="post">
            <?= csrf_field() ?>

            <div class="mpkl-form-body filter-pkl-grid">
                <div class="mpkl-form-field filter-row-half">
                    <label class="mpkl-label"><i class="fas fa-user-edit"></i> Editor</label>
                    <input type="text" class="mpkl-input mtugas-readonly-input" value="<?= esc($tugas['editor_username'] ?? 'Admin') ?>" readonly>
                    <span class="mpkl-hint"><i class="fas fa-info-circle"></i> Mengikuti akun pembuat awal tugas.</span>
                </div>

                <div class="mpkl-form-field filter-row-half">
                    <label class="mpkl-label"><i class="fas fa-layer-group"></i> Mode Pengumpulan</label>
                    <input type="text" class="mpkl-input mtugas-readonly-input" value="<?= esc($modeLabel) ?>" readonly>
                    <span class="mpkl-hint"><i class="fas fa-lock"></i> Mode pengumpulan tidak bisa diubah dari halaman ini.</span>
                </div>

                <div class="mpkl-form-field filter-row-half">
                    <label class="mpkl-label"><i class="fas fa-tags"></i> Kategori Tugas <span class="required-star">*</span></label>
                    <select id="editTugasKategori" name="id_kat_tugas" class="mpkl-select mtugas-select2-field" required>
                        <option value="">-- Pilih Kategori --</option>
                        <?php foreach (($kategoriList ?? []) as $kat): ?>
                            <option value="<?= esc((string) $kat['id_kat_tugas']) ?>" <?= (int) ($kat['id_kat_tugas'] ?? 0) === (int) ($tugas['id_kat_tugas'] ?? 0) ? 'selected' : '' ?>>
                                <?= esc((string) $kat['nama_kat_tugas']) ?> (<?= esc(ucfirst((string) $kat['mode_pengumpulan'])) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mpkl-form-field filter-row-half">
                    <label class="mpkl-label"><i class="fas fa-heading"></i> Nama Tugas <span class="required-star">*</span></label>
                    <input type="text" name="nama_tugas" class="mpkl-input" value="<?= esc($tugas['nama_tugas'] ?? '') ?>" required>
                </div>

                <div class="mpkl-form-field filter-row-half">
                    <label class="mpkl-label"><i class="fas fa-bullseye"></i> Target Jumlah Item <span class="required-star">*</span></label>
                    <input type="number" name="target_jumlah" class="mpkl-input" min="1" value="<?= (int) ($tugas['target_jumlah'] ?? 1) ?>" required>
                </div>

                <div class="mpkl-form-field filter-row-half">
                    <label class="mpkl-label"><i class="fas fa-clock"></i> Tenggat Waktu (Deadline) <span class="required-star">*</span></label>
                    <input type="text" name="deadline" id="editTugasDeadline" class="mpkl-input" value="<?= esc(substr((string) ($tugas['deadline'] ?? ''), 0, 16)) ?>" required>
                </div>

                <div class="mpkl-form-field filter-row-full">
                    <label class="mpkl-label"><i class="fas fa-align-left"></i> Deskripsi / Instruksi <span class="required-star">*</span></label>
                    <textarea name="deskripsi" class="mpkl-input" rows="5" required><?= esc($tugas['deskripsi'] ?? '') ?></textarea>
                </div>

                <div class="mpkl-form-field filter-row-full">
                    <label class="mpkl-label"><i class="fas fa-bullseye"></i> Sasaran Tugas</label>
                    <div class="mtugas-readonly-panel">
                        <?php if (! empty($sasaranList)): ?>
                            <div class="mtugas-readonly-list">
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
                            <div class="mtugas-readonly-empty">Belum ada sasaran yang tersimpan.</div>
                        <?php endif; ?>
                    </div>
                    <span class="mpkl-hint"><i class="fas fa-info-circle"></i> Sasaran hanya dapat diubah melalui alur penugasan baru.</span>
                </div>
            </div>

            <div class="mpkl-form-footer">
                <a href="<?= $detailUrl ?>" class="btn-mpkl-cancel">
                    <i class="fas fa-times"></i> Batal
                </a>
                <button type="submit" class="btn-mpkl-submit" id="btnSimpanEditTugas">
                    <i class="fas fa-save"></i> Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>
