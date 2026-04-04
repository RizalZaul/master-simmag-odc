$(document).ready(function () {
    var taskUploadDefaults = {
        allowedExtensions: ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'zip', 'rar'],
        maxSizeKb: 307200
    };

    if (window.SimmagValidation && typeof window.SimmagValidation.applyInputRules === 'function') {
        window.SimmagValidation.applyInputRules([
            { selector: '[data-task-modal] input[type="url"]', rule: 'url', label: 'Link Tugas' }
        ]);
    }

    function showTaskToast(icon, title, html) {
        if (!window.Swal) {
            return;
        }

        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: icon,
            title: title,
            html: html || undefined,
            customClass: {
                container: 'pkl-task-swal-container'
            },
            showConfirmButton: false,
            timer: html ? 3200 : 1800,
            timerProgressBar: true
        });
    }

    function showTaskAlert(message, useHtml) {
        showTaskToast('warning', 'Perhatian', useHtml ? message : String(message || ''));
    }

    function updateTaskHeader(tab) {
        var labels = {
            individu: 'Tugas Individu',
            kelompok: 'Tugas Kelompok'
        };

        var $headerSub = $('.page-title .title-sub');
        if ($headerSub.length && labels[tab]) {
            $headerSub.text(labels[tab]);
        }

        if (labels[tab]) {
            document.title = 'Manajemen Tugas / ' + labels[tab] + ' - SIMMAG ODC';
        }
    }

    function syncTaskUrl(tab) {
        var url = new URL(window.location.href);
        url.searchParams.set('tab', tab);
        window.history.replaceState(null, '', url.toString());
    }

    function filterActiveTasks() {
        var query = ($('#pklTaskSearchInput').val() || '').toLowerCase().trim();
        var $activePanel = $('.pkl-task-tab-panel.active');
        if (!$activePanel.length) {
            return;
        }

        var $cards = $activePanel.find('[data-task-card]');
        var visibleCount = 0;

        $cards.each(function () {
            var taskTitle = $(this).find('.pkl-task-card-title').text().toLowerCase().trim();
            var matched = query === '' || taskTitle.indexOf(query) !== -1;
            $(this).toggleClass('is-hidden', !matched);
            if (matched) {
                visibleCount += 1;
            }
        });

        var hasCards = $cards.length > 0;
        $activePanel.find('[data-empty-default]').toggleClass('is-hidden', hasCards || query !== '');
        $activePanel.find('[data-empty-filtered]').toggleClass('is-hidden', !hasCards || query === '' || visibleCount > 0);
    }

    function switchTaskTab(tab) {
        $('.pkl-task-tab-btn').removeClass('active');
        $('.pkl-task-tab-panel').removeClass('active');
        $('.pkl-task-tab-btn[data-tab="' + tab + '"]').addClass('active');
        $('.pkl-task-tab-panel[data-tab-panel="' + tab + '"]').addClass('active');
        syncTaskUrl(tab);
        updateTaskHeader(tab);
        filterActiveTasks();
    }

    $('.pkl-task-tab-btn').on('click', function () {
        switchTaskTab($(this).data('tab'));
    });

    $('#pklTaskSearchInput').on('input', filterActiveTasks);
    $('#pklTaskSearchReset').on('click', function () {
        $('#pklTaskSearchInput').val('');
        filterActiveTasks();
        showTaskToast('info', 'Pencarian tugas direset.');
    });

    if ($('.pkl-task-tab-btn').length) {
        switchTaskTab((window.PKL_TUGAS && window.PKL_TUGAS.activeTab) || 'individu');
    }

    function setBodyScrollLocked(locked) {
        $('body').toggleClass('pkl-task-modal-open', locked);
    }

    function openTaskModal() {
        var $modal = $('[data-task-modal]');
        if (!$modal.length) {
            return;
        }

        $modal.addClass('is-open');
        setBodyScrollLocked(true);
    }

    function closeTaskModal() {
        var $modal = $('[data-task-modal]');
        if (!$modal.length) {
            return;
        }

        $modal.removeClass('is-open');
        setBodyScrollLocked(false);
    }

    $(document).on('click', '[data-open-task-modal]', function () {
        openTaskModal();
    });

    $(document).on('click', '[data-close-task-modal]', function () {
        closeTaskModal();
    });

    $(document).on('keydown', function (event) {
        if (event.key === 'Escape') {
            closeTaskModal();
        }
    });

    function syncAnswerType($card) {
        var currentType = $card.find('[data-answer-type-select]').val() || 'link';
        $card.find('[data-answer-type-panel]').each(function () {
            var isMatch = $(this).data('answer-type-panel') === currentType;
            $(this).toggleClass('is-hidden', !isMatch);
        });
    }

    function getTaskFileDropDefaultLabel($drop) {
        return ($drop.attr('data-default-label') || '').trim() || 'Drag & Drop file di sini';
    }

    function getTaskFileAllowedExtensions($drop) {
        var raw = ($drop.attr('data-allowed-ext') || '').trim();
        if (raw === '') {
            return taskUploadDefaults.allowedExtensions.slice();
        }

        return raw.split(',').map(function (item) {
            return $.trim(item).replace(/^\./, '').toLowerCase();
        }).filter(Boolean);
    }

    function getTaskFileMaxSizeKb($drop) {
        var parsed = parseInt($drop.attr('data-max-size-kb'), 10);
        return Number.isFinite(parsed) && parsed > 0 ? parsed : taskUploadDefaults.maxSizeKb;
    }

    function getTaskFileAcceptAttr($drop) {
        return getTaskFileAllowedExtensions($drop).map(function (extension) {
            return '.' + extension;
        }).join(',');
    }

    function setTaskFileLabel($input, file) {
        var $drop = $input.closest('.pkl-task-file-drop');
        if (!$drop.length) {
            return;
        }

        $drop.removeClass('is-dragover').toggleClass('has-file', !!file);
        $drop.find('[data-file-label]').text(file ? file.name : getTaskFileDropDefaultLabel($drop));
    }

    function clearTaskFileInput(input) {
        if (!input) {
            return;
        }

        try {
            input.value = '';
        } catch (error) {
            // noop
        }

        setTaskFileLabel($(input), null);
    }

    function getTaskFileValidationMessage(file, $drop) {
        if (!file) {
            return 'File wajib dipilih.';
        }

        var allowedExtensions = getTaskFileAllowedExtensions($drop);
        var extension = '';
        var fileName = String(file.name || '');
        var lastDotIndex = fileName.lastIndexOf('.');

        if (lastDotIndex !== -1) {
            extension = fileName.substring(lastDotIndex + 1).toLowerCase();
        }

        if (!extension || $.inArray(extension, allowedExtensions) === -1) {
            return 'Format file harus PDF, DOC/DOCX, PPT/PPTX, XLS/XLSX, ZIP, atau RAR.';
        }

        if (Math.ceil((file.size || 0) / 1024) > getTaskFileMaxSizeKb($drop)) {
            return 'Ukuran file maksimal 300 MB.';
        }

        return '';
    }

    function validateTaskFile(file, $drop) {
        var message = getTaskFileValidationMessage(file, $drop);
        if (message) {
            showTaskToast('error', message);
            return false;
        }

        return true;
    }

    function extractDraggedFiles(event) {
        var originalEvent = event.originalEvent || event;
        var transfer = originalEvent.dataTransfer;
        if (!transfer || !transfer.files || !transfer.files.length) {
            return [];
        }

        return Array.prototype.slice.call(transfer.files);
    }

    function isFileDragEvent(event) {
        var originalEvent = event.originalEvent || event;
        var transfer = originalEvent.dataTransfer;
        if (!transfer) {
            return false;
        }

        if (transfer.items && transfer.items.length) {
            return Array.prototype.some.call(transfer.items, function (item) {
                return item.kind === 'file';
            });
        }

        if (transfer.types && transfer.types.length) {
            return Array.prototype.indexOf.call(transfer.types, 'Files') !== -1;
        }

        return !!(transfer.files && transfer.files.length);
    }

    function assignDroppedFileToInput(input, file) {
        if (!input || !file || typeof window.DataTransfer === 'undefined') {
            return false;
        }

        try {
            var transfer = new window.DataTransfer();
            transfer.items.add(file);
            input.files = transfer.files;
            return true;
        } catch (error) {
            return false;
        }
    }

    function enhanceTaskFileDropzones() {
        $('.pkl-task-file-drop').each(function () {
            var $drop = $(this);
            var $input = $drop.find('[data-file-input]');
            var $label = $drop.find('[data-file-label]');
            var defaultLabel = getTaskFileDropDefaultLabel($drop);

            if (!$input.length || !$label.length) {
                return;
            }

            if ($drop.attr('data-default-label') !== defaultLabel) {
                $drop.attr('data-default-label', defaultLabel);
            }

            if (!$input.attr('accept')) {
                $input.attr('accept', getTaskFileAcceptAttr($drop));
            }

            if (!$drop.find('.pkl-task-file-helper').length) {
                $('<span class="pkl-task-file-helper">atau klik untuk memilih file</span>').insertAfter($label);
            }

            if (!($input[0].files && $input[0].files.length)) {
                setTaskFileLabel($input, null);
            }
        });
    }

    $('[data-answer-card]').each(function () {
        syncAnswerType($(this));
    });

    enhanceTaskFileDropzones();

    $(document).on('change', '[data-answer-type-select]', function () {
        syncAnswerType($(this).closest('[data-answer-card]'));
    });

    $(document).on('change', '[data-file-input]', function () {
        var files = this.files || [];
        var $input = $(this);
        var $drop = $input.closest('.pkl-task-file-drop');
        var file = files.length ? files[0] : null;

        if (file && !validateTaskFile(file, $drop)) {
            clearTaskFileInput(this);
            return;
        }

        setTaskFileLabel($input, file);
    });

    $(document).on('dragover', '.pkl-task-file-drop', function (event) {
        if (!isFileDragEvent(event)) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();
        $(this).addClass('is-dragover');
    });

    $(document).on('dragleave dragend drop', '.pkl-task-file-drop', function (event) {
        if (event.type === 'dragleave') {
            var originalEvent = event.originalEvent || event;
            var relatedTarget = originalEvent.relatedTarget || event.relatedTarget;
            if (relatedTarget && this.contains(relatedTarget)) {
                return;
            }
        }

        $(this).removeClass('is-dragover');
    });

    $(document).on('drop', '.pkl-task-file-drop', function (event) {
        var files = extractDraggedFiles(event);
        var $drop = $(this);
        var $input = $drop.find('[data-file-input]');

        if (!files.length || !$input.length) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();

        if (files.length > 1) {
            showTaskToast('info', 'Hanya file pertama yang digunakan.');
        }

        if (!validateTaskFile(files[0], $drop)) {
            clearTaskFileInput($input[0]);
            return;
        }

        if (!assignDroppedFileToInput($input[0], files[0])) {
            showTaskToast('warning', 'Browser ini belum mendukung drag & drop file secara penuh. Silakan pilih file manual.');
            return;
        }

        setTaskFileLabel($input, files[0]);
    });

    $(document).on('dragover drop', function (event) {
        if (!$('[data-task-modal].is-open').length || !isFileDragEvent(event)) {
            return;
        }

        event.preventDefault();
    });

    $(document).on('submit', '[data-task-modal] form', function (event) {
        var $form = $(this);
        var errorMessages = [];
        var editableCardCount = 0;
        var emptyFieldErrorCount = 0;

        $form.find('[data-answer-card]').each(function () {
            var $card = $(this);
            if ($card.hasClass('is-locked')) {
                return;
            }

            editableCardCount++;

            var answerNumber = $.trim($card.find('.pkl-task-answer-form-head h4').text() || 'Jawaban');
            var answerType = ($card.find('[data-answer-type-select]').val() || 'link').toLowerCase();

            if (answerType === 'link') {
                var url = $.trim($card.find('input[type="url"]').val() || '');
                if (url === '') {
                    errorMessages.push(answerNumber + ' harus diisi dengan link.');
                    emptyFieldErrorCount++;
                    return;
                }

                if (!/^https:\/\//i.test(url)) {
                    errorMessages.push(answerNumber + ' harus menggunakan link yang diawali https://.');
                    return;
                }

                if (url.length > 2048) {
                    errorMessages.push(answerNumber + ' terlalu panjang.');
                    return;
                }

                return;
            }

            var $fileInput = $card.find('[data-file-input]').first();
            var file = $fileInput[0] && $fileInput[0].files && $fileInput[0].files.length
                ? $fileInput[0].files[0]
                : null;
            var fileValidationMessage = getTaskFileValidationMessage(file, $card.find('.pkl-task-file-drop').first());
            if (fileValidationMessage) {
                errorMessages.push(answerNumber + ': ' + fileValidationMessage);
                if (/wajib dipilih\.$/i.test(fileValidationMessage)) {
                    emptyFieldErrorCount++;
                }
            }
        });

        if (errorMessages.length) {
            event.preventDefault();
            if (editableCardCount > 0 && errorMessages.length === editableCardCount && emptyFieldErrorCount === editableCardCount) {
                showTaskAlert('Semua jawaban harus diisi.');
                return;
            }

            showTaskAlert(errorMessages.join('<br>'), true);
        }
    });

    if ((window.PKL_TUGAS_DETAIL && window.PKL_TUGAS_DETAIL.autoOpenUpload) || $('[data-task-modal]').data('auto-open') === 1 || $('[data-task-modal]').data('auto-open') === '1') {
        openTaskModal();
    }

    if (window.PKL_TUGAS_DETAIL) {
        if (window.PKL_TUGAS_DETAIL.flashSuccess) {
            showTaskToast('success', window.PKL_TUGAS_DETAIL.flashSuccess);
        }

        if (window.PKL_TUGAS_DETAIL.flashError) {
            showTaskToast('error', window.PKL_TUGAS_DETAIL.flashError);
        }

        if (Array.isArray(window.PKL_TUGAS_DETAIL.uploadErrors) && window.PKL_TUGAS_DETAIL.uploadErrors.length) {
            showTaskToast('warning', 'Periksa Jawaban', window.PKL_TUGAS_DETAIL.uploadErrors.join('<br>'));
        }
    }
});
