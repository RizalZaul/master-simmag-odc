<?php

$detail = $detail ?? [];
$answers = $detail['answers'] ?? [];
$slots = $detail['submission_slots'] ?? [];
$uploadErrors = $uploadErrors ?? [];
$autoOpenUpload = ! empty($autoOpenUpload);
?>

<div class="pkl-task-detail-wrap">
    <div class="pkl-task-back-row">
        <a href="<?= esc($detail['back_url'] ?? base_url('pkl/tugas')) ?>" class="pkl-task-back-link">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="pkl-task-detail-card">
        <div class="pkl-task-detail-hero">
            <div class="pkl-task-detail-icon">
                <i class="fas fa-file-upload"></i>
            </div>
            <div class="pkl-task-detail-content">
                <h2><?= esc($detail['nama_tugas'] ?? '-') ?></h2>
                <div class="pkl-task-detail-badges">
                    <span class="pkl-task-chip"><i class="fas fa-tag"></i> <?= esc($detail['nama_kategori'] ?? '-') ?></span>
                    <span class="pkl-task-chip"><i class="fas fa-user-friends"></i> <?= esc($detail['source_badge'] ?? '-') ?></span>
                    <span class="pkl-task-status-badge is-<?= esc($detail['status_class'] ?? 'warning') ?>">
                        <?= esc($detail['status_label'] ?? 'Belum Dikirim') ?>
                    </span>
                </div>
            </div>
        </div>

        <div class="pkl-task-meta-grid">
            <div class="pkl-task-meta-item">
                <span class="pkl-task-meta-label"><i class="far fa-calendar-plus"></i> Ditambahkan</span>
                <strong><?= esc($detail['created_display'] ?? '-') ?></strong>
            </div>
            <div class="pkl-task-meta-item">
                <span class="pkl-task-meta-label"><i class="far fa-calendar-alt"></i> Deadline</span>
                <strong><?= esc($detail['deadline_display'] ?? '-') ?></strong>
            </div>
            <div class="pkl-task-meta-item">
                <span class="pkl-task-meta-label"><i class="fas fa-users"></i> Sasaran</span>
                <strong><?= esc($detail['source_name'] ?? '-') ?></strong>
            </div>
            <div class="pkl-task-meta-item">
                <span class="pkl-task-meta-label"><i class="fas fa-bullseye"></i> Target Jawaban</span>
                <strong><?= esc($detail['target_label'] ?? '-') ?></strong>
            </div>
        </div>

        <div class="pkl-task-description-box">
            <?= nl2br(esc($detail['deskripsi'] ?? '-')) ?>
        </div>

        <div class="pkl-task-detail-actions">
            <?php if (! empty($detail['can_submit'])): ?>
                <button type="button" class="pkl-task-primary-btn" data-open-task-modal>
                    <i class="fas fa-plus-circle"></i> <?= esc($detail['submit_label'] ?? 'Kumpulkan Tugas') ?>
                </button>
            <?php else: ?>
                <div class="pkl-task-submit-note">
                    Pengumpulan tugas saat ini sedang diproses atau sudah selesai.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="pkl-task-detail-card">
        <div class="pkl-task-section-title">Status Pengumpulan</div>
        <div class="pkl-task-status-table">
            <div class="pkl-task-status-row">
                <span>Status</span>
                <strong>
                    <span class="pkl-task-status-badge is-<?= esc($detail['status_class'] ?? 'warning') ?>">
                        <?= esc($detail['status_label'] ?? 'Belum Dikirim') ?>
                    </span>
                </strong>
            </div>
            <div class="pkl-task-status-row">
                <span>Waktu Pengumpulan</span>
                <strong><?= esc($detail['submitted_display'] ?? 'Belum dikumpulkan') ?></strong>
            </div>
            <div class="pkl-task-status-row">
                <span>Jumlah Jawaban</span>
                <strong><?= esc($detail['progress_label'] ?? '0 / 0 item') ?></strong>
            </div>
            <div class="pkl-task-status-row">
                <span>Sisa Waktu</span>
                <strong><?= esc($detail['remaining_time'] ?? '-') ?></strong>
            </div>
            <div class="pkl-task-status-row">
                <span>Terakhir Diubah</span>
                <strong><?= esc($detail['last_updated_display'] ?? '-') ?></strong>
            </div>
        </div>
    </div>

    <div class="pkl-task-detail-card">
        <div class="pkl-task-section-title">Jawaban Terkirim</div>
        <?php if (! empty($answers)): ?>
            <div class="pkl-task-answer-list">
                <?php foreach ($answers as $index => $answer): ?>
                    <div class="pkl-task-answer-item">
                        <div class="pkl-task-answer-top">
                            <div>
                                <span class="pkl-task-answer-number">Jawaban <?= $index + 1 ?></span>
                                <div class="pkl-task-answer-type"><?= esc($answer['tipe_label'] ?? '-') ?></div>
                            </div>
                            <span class="pkl-task-status-badge is-<?= esc($answer['status_class'] ?? 'warning') ?>">
                                <?= esc($answer['status_label'] ?? 'Belum Dikirim') ?>
                            </span>
                        </div>
                        <div class="pkl-task-answer-value">
                            <?php if (($answer['tipe'] ?? '') === 'link'): ?>
                                <a href="<?= esc($answer['action_url'] ?? '#') ?>" target="_blank" rel="noopener noreferrer">
                                    <?= esc($answer['display_value'] ?? '-') ?>
                                </a>
                            <?php else: ?>
                                <a href="<?= esc($answer['action_url'] ?? '#') ?>">
                                    <i class="fas fa-download"></i> <?= esc($answer['display_value'] ?? '-') ?>
                                </a>
                            <?php endif; ?>
                        </div>
                        <?php if (! empty($answer['komentar'])): ?>
                            <div class="pkl-task-answer-comment">
                                <i class="fas fa-circle-info"></i> <?= nl2br(esc($answer['komentar'])) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="pkl-task-empty small">
                <i class="fas fa-inbox"></i>
                <p>Belum ada jawaban yang dikirim.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if (! empty($detail['can_submit'])): ?>
    <div class="pkl-task-modal" id="taskUploadModal" data-task-modal data-auto-open="<?= $autoOpenUpload ? '1' : '0' ?>">
        <div class="pkl-task-modal-backdrop" data-close-task-modal></div>
        <div class="pkl-task-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="taskUploadModalTitle">
            <div class="pkl-task-modal-header">
                <div class="pkl-task-modal-title" id="taskUploadModalTitle">
                    <div class="pkl-task-modal-title-icon">
                        <i class="fas fa-upload"></i>
                    </div>
                    <span>Kumpulkan Tugas</span>
                </div>
                <button type="button" class="pkl-task-modal-close" data-close-task-modal>
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="pkl-task-modal-body">
                <form action="<?= esc(base_url('pkl/tugas/kumpulkan/' . (int) ($detail['id_tugas'] ?? 0))) ?>" method="post" enctype="multipart/form-data">
                    <?= csrf_field() ?>

                    <div class="pkl-task-form-grid">
                        <div class="pkl-task-form-field">
                            <label>Judul Tugas</label>
                            <input type="text" class="pkl-task-input" value="<?= esc($detail['nama_tugas'] ?? '-') ?>" readonly>
                        </div>
                        <div class="pkl-task-form-field">
                            <label>Deadline</label>
                            <input type="text" class="pkl-task-input" value="<?= esc($detail['deadline_display'] ?? '-') ?>" readonly>
                        </div>
                    </div>

                    <div class="pkl-task-file-note">
                        <i class="fas fa-circle-info"></i>
                        <span><?= esc($detail['allowed_file_text'] ?? 'Format file: pdf, docx, doc, pptx, ppt, xlsx, xls, zip, rar - maks 300 MB') ?></span>
                    </div>

                    <div class="pkl-task-answer-form-list">
                        <?php foreach ($slots as $slot): ?>
                            <?php
                            $slotIndex = (int) ($slot['index'] ?? 0);
                            $existing = $slot['existing'] ?? null;
                            $existingType = (string) ($existing['tipe'] ?? 'link');
                            $selectedType = old('jawaban.' . $slotIndex . '.tipe');
                            $selectedType = in_array($selectedType, ['link', 'file'], true) ? $selectedType : $existingType;
                            $selectedType = $selectedType !== '' ? $selectedType : 'link';
                            $oldUrl = old('jawaban.' . $slotIndex . '.url');
                            $urlValue = $oldUrl !== null ? (string) $oldUrl : (($selectedType === 'link' && $existing) ? (string) ($existing['data'] ?? '') : '');
                            $isLocked = (bool) ($slot['is_locked'] ?? false);
                            ?>
                            <div class="pkl-task-answer-form-card <?= $isLocked ? 'is-locked' : '' ?>" data-answer-card>
                                <div class="pkl-task-answer-form-head">
                                    <h4>Jawaban <?= esc((string) ($slot['number'] ?? ($slotIndex + 1))) ?></h4>
                                    <?php if ($isLocked): ?>
                                        <span class="pkl-task-status-badge is-success">Diterima</span>
                                    <?php else: ?>
                                        <select name="jawaban[<?= $slotIndex ?>][tipe]" class="pkl-task-select" data-answer-type-select>
                                            <option value="link" <?= $selectedType === 'link' ? 'selected' : '' ?>>Link URL</option>
                                            <option value="file" <?= $selectedType === 'file' ? 'selected' : '' ?>>File</option>
                                        </select>
                                    <?php endif; ?>
                                </div>

                                <?php if ($isLocked && $existing): ?>
                                    <div class="pkl-task-locked-answer">
                                        <div class="pkl-task-answer-type"><?= esc($existing['tipe_label'] ?? '-') ?></div>
                                        <div class="pkl-task-answer-value">
                                            <?php if (($existing['tipe'] ?? '') === 'link'): ?>
                                                <a href="<?= esc($existing['action_url'] ?? '#') ?>" target="_blank" rel="noopener noreferrer">
                                                    <?= esc($existing['display_value'] ?? '-') ?>
                                                </a>
                                            <?php else: ?>
                                                <a href="<?= esc($existing['action_url'] ?? '#') ?>">
                                                    <i class="fas fa-download"></i> <?= esc($existing['display_value'] ?? '-') ?>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="pkl-task-answer-input-group <?= $selectedType === 'link' ? '' : 'is-hidden' ?>" data-answer-type-panel="link">
                                        <input
                                            type="url"
                                            name="jawaban[<?= $slotIndex ?>][url]"
                                            class="pkl-task-input"
                                            placeholder="https://..."
                                            value="<?= esc($urlValue) ?>">
                                    </div>

                                    <div class="pkl-task-answer-input-group <?= $selectedType === 'file' ? '' : 'is-hidden' ?>" data-answer-type-panel="file">
                                        <label class="pkl-task-file-drop"
                                            data-default-label="Klik untuk memilih file"
                                            data-allowed-ext="<?= esc(implode(',', $detail['upload_allowed_extensions'] ?? [])) ?>"
                                            data-max-size-kb="<?= (int) ($detail['upload_max_size_kb'] ?? 307200) ?>">
                                            <input type="file"
                                                name="jawaban_file_<?= $slotIndex ?>"
                                                class="pkl-task-file-input"
                                                data-file-input
                                                accept="<?= esc(implode(',', array_map(fn($ext) => '.' . $ext, $detail['upload_allowed_extensions'] ?? []))) ?>">
                                            <span class="pkl-task-file-icon"><i class="fas fa-cloud-upload-alt"></i></span>
                                            <span class="pkl-task-file-title" data-file-label>Klik untuk memilih file</span>
                                            <span class="pkl-task-file-subtitle"><?= esc($detail['allowed_file_text'] ?? '') ?></span>
                                        </label>
                                    </div>

                                    <?php if ($existing): ?>
                                        <div class="pkl-task-current-answer">
                                            <span class="pkl-task-current-answer-label">Jawaban saat ini:</span>
                                            <?php if (($existing['tipe'] ?? '') === 'link'): ?>
                                                <a href="<?= esc($existing['action_url'] ?? '#') ?>" target="_blank" rel="noopener noreferrer">
                                                    <?= esc($existing['display_value'] ?? '-') ?>
                                                </a>
                                            <?php else: ?>
                                                <a href="<?= esc($existing['action_url'] ?? '#') ?>">
                                                    <i class="fas fa-download"></i> <?= esc($existing['display_value'] ?? '-') ?>
                                                </a>
                                            <?php endif; ?>
                                            <?php if (! empty($existing['komentar'])): ?>
                                                <span class="pkl-task-current-comment">
                                                    Catatan admin: <?= nl2br(esc($existing['komentar'])) ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="pkl-task-modal-actions">
                        <button type="button" class="pkl-task-secondary-btn" data-close-task-modal>
                            Batal
                        </button>
                        <button type="submit" class="pkl-task-primary-btn">
                            <i class="fas fa-paper-plane"></i> Kirim Jawaban
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>
