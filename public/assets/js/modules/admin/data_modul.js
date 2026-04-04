/**
 * SIMMAG ODC - Data Modul Admin JS
 * Dependency:
 *   - jQuery 3.x
 *   - DataTables 1.13.x + Responsive 2.5.x
 *   - SweetAlert2
 *   - Select2 4.x
 */

$(document).ready(function () {

    var elHeaderTitle = document.querySelector('.page-title');
    var elHeaderMain = document.querySelector('.page-title .title-main');
    var elHeaderSub = document.querySelector('.page-title .title-sub');

    var pageMeta = {
        kategori: {
            main: 'Data Modul',
            sub: 'Kategori Modul',
        },
        modulList: {
            main: 'Data Modul',
            sub: 'Modul',
        },
        modulCreate: {
            main: 'Data Modul',
            sub: 'Tambah Modul',
        },
        modulDetail: {
            main: 'Data Modul',
            sub: 'Detail Modul',
        },
        modulEdit: {
            main: 'Data Modul',
            sub: 'Ubah Modul',
        },
    };

    function setUrlParam(key, value) {
        var url = new URL(window.location.href);
        url.searchParams.set(key, value);
        if (key === 'tab' && value === 'kategori') {
            url.searchParams.delete('mode');
            url.searchParams.delete('id');
            url.searchParams.delete('return');
        }
        window.history.replaceState(null, '', url.toString());
    }

    function syncPageHeading(key) {
        var meta = pageMeta[key];
        if (!meta) {
            return;
        }

        if (elHeaderMain) {
            elHeaderMain.textContent = meta.main;
        }
        if (elHeaderSub) {
            elHeaderSub.textContent = meta.sub;
        }
        if (elHeaderTitle && (!elHeaderMain || !elHeaderSub)) {
            elHeaderTitle.textContent = meta.main + ' / ' + meta.sub;
        }
        document.title = meta.main + ' / ' + meta.sub + ' - SIMMAG ODC';
    }

    function switchTab(target) {
        $('.dm-tab-btn').removeClass('active');
        $('.dm-tab-content').removeClass('active');
        $('[data-tab="' + target + '"]').addClass('active');
        $('#tab-' + target).addClass('active');

        setUrlParam('tab', target);

        if (target !== 'kategori') {
            hideKategoriForm(false);
        }

        if (target === 'kategori') {
            hideModulFilterPanel(true);
            syncModulChrome('list');
            syncPageHeading('kategori');
            return;
        }

        if (target === 'modul') {
            restoreModulState();
        }
    }

    $('.dm-tab-btn').on('click', function () {
        switchTab($(this).data('tab'));
    });

    var csrfName = document.querySelector('meta[name="csrf-token-name"]')?.content || 'csrf_test_name';

    function getCookie(name) {
        var match = document.cookie.match(new RegExp('(?:^|; )' + name.replace(/([.$?*|{}()[\]\\/+^])/g, '\\$1') + '=([^;]*)'));
        return match ? decodeURIComponent(match[1]) : '';
    }

    function getCsrfToken() {
        return getCookie(window.dmCsrfCookieName || 'csrf_cookie_name')
            || document.querySelector('meta[name="csrf-token-hash"]')?.content
            || '';
    }

    function syncAllCsrfInputs() {
        var token = getCsrfToken();
        if (!csrfName || !token) {
            return;
        }

        $('input[name="' + csrfName + '"]').val(token);
    }

    function buildCsrfData() {
        var data = {};
        data[csrfName] = getCsrfToken();
        return data;
    }

    function scrollToElement($el) {
        if (!$el || !$el.length) {
            return;
        }

        $('html, body').animate({
            scrollTop: Math.max($el.offset().top - 80, 0),
        }, 280);
    }

    function showToast(icon, title, timer) {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: icon,
            title: title,
            showConfirmButton: false,
            timer: timer || 2200,
            timerProgressBar: true,
        });
    }

    function escapeHtml(value) {
        return $('<div>').text(value == null ? '' : String(value)).html();
    }

    function buildMissingFieldsMessage(missingFields, totalRequired) {
        if (window.SimmagValidation && typeof window.SimmagValidation.buildMissingFieldsMessage === 'function') {
            return window.SimmagValidation.buildMissingFieldsMessage(missingFields, totalRequired);
        }
        var labels = Array.from(new Set((missingFields || []).filter(Boolean)));
        if (!labels.length) return 'Semua field harus diisi.';
        if (totalRequired && labels.length >= totalRequired) return 'Semua field harus diisi.';
        if (labels.length === 1) return labels[0] + ' wajib diisi.';
        return 'Field berikut wajib diisi: ' + labels.join(', ') + '.';
    }

    function formatDateTime(value) {
        if (!value) {
            return '-';
        }

        var date = new Date(String(value).replace(' ', 'T'));
        if (Number.isNaN(date.getTime())) {
            return value;
        }

        var months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        var dd = String(date.getDate()).padStart(2, '0');
        var mm = months[date.getMonth()];
        var yyyy = date.getFullYear();
        var hh = String(date.getHours()).padStart(2, '0');
        var ii = String(date.getMinutes()).padStart(2, '0');

        return hh + ':' + ii + ' - ' + dd + ' ' + mm + ' ' + yyyy;
    }

    function validateKategoriNama(value) {
        var v = window.SimmagValidation || {};
        return v.validatePatternField
            ? v.validatePatternField(
                'Nama Kategori',
                value,
                3,
                50,
                /^[\p{L}0-9\s]+$/u,
                'huruf, angka, dan spasi'
            )
            : '';
    }

    function validateModulFields() {
        var v = window.SimmagValidation || {};
        var missingFields = [];
        var nama = $inputNamaModul.val() || '';
        var kategori = $inputKategoriModul.val() || '';
        var deskripsi = $inputDeskripsiModul.val() || '';
        var tipe = $('input[name="tipe_modul"]:checked').val() || 'link';
        var url = $inputUrlModul.val() || '';
        var isEdit = $modulFormMode.val() === 'edit';
        var hasCurrentFile = !!$modulForm.data('currentFile');
        var file = $inputFileModul[0] && $inputFileModul[0].files && $inputFileModul[0].files.length
            ? $inputFileModul[0].files[0]
            : null;

        if (!$.trim(nama)) missingFields.push('Nama Modul');
        if (!$.trim(kategori)) missingFields.push('Kategori Modul');
        if (!$.trim(deskripsi)) missingFields.push('Deskripsi');
        if (tipe === 'link' && !$.trim(url)) missingFields.push('URL Modul');
        if (tipe === 'file' && !file && (!isEdit || !hasCurrentFile)) missingFields.push('File Modul');

        if (missingFields.length) {
            return buildMissingFieldsMessage(missingFields, tipe === 'file' ? 4 : 4);
        }

        return (v.validatePatternField ? v.validatePatternField(
            'Nama Modul',
            nama,
            3,
            50,
            /^[\p{L}0-9\s]+$/u,
            'huruf, angka, dan spasi'
        ) : '')
            || (v.validatePatternField ? v.validatePatternField(
                'Deskripsi',
                deskripsi,
                10,
                255,
                /^[\p{L}\p{N}\s\p{P}\p{Sc}\p{Sk}]+$/u,
                'huruf, angka, spasi, dan tanda baca'
            ) : '')
            || (tipe === 'link' && v.validateHttpsUrl ? v.validateHttpsUrl(url, 'URL Modul') : '')
            || '';
    }

    function updateModulUrl(mode, id, returnMode) {
        var url = new URL(window.location.href);
        url.searchParams.set('tab', 'modul');

        if (!mode || mode === 'list') {
            url.searchParams.delete('mode');
            url.searchParams.delete('id');
            url.searchParams.delete('return');
        } else {
            url.searchParams.set('mode', mode);
            if (id) {
                url.searchParams.set('id', id);
            } else {
                url.searchParams.delete('id');
            }

            if (mode === 'edit' && returnMode === 'detail') {
                url.searchParams.set('return', 'detail');
            } else {
                url.searchParams.delete('return');
            }
        }

        window.history.replaceState(null, '', url.toString());
    }

    function getModulUrlState() {
        var url = new URL(window.location.href);
        if (url.searchParams.get('tab') !== 'modul') {
            return { mode: 'list', id: '' };
        }

        var mode = url.searchParams.get('mode') || 'list';
        if (['list', 'create', 'detail', 'edit'].indexOf(mode) === -1) {
            mode = 'list';
        }

        return {
            mode: mode,
            id: url.searchParams.get('id') || '',
            returnMode: url.searchParams.get('return') === 'detail' ? 'detail' : 'list',
        };
    }

    // ================================================================
    // KATEGORI MODUL
    // ================================================================

    var tableKategori = $('#tabelKategori').DataTable({
        responsive: true,
        autoWidth: false,
        pageLength: 10,
        lengthMenu: [10, 25, 50, 100],
        order: [[1, 'asc']],
        dom: 'lrtip',
        columnDefs: [
            { targets: 0, orderable: false, searchable: false, className: 'text-center' },
            { targets: 1 },
            { targets: 2, className: 'min-tablet-p' },
            { targets: 3, className: 'min-tablet-p' },
            { targets: 4, orderable: false, searchable: false, className: 'text-center' },
        ],
        language: {
            lengthMenu: 'Tampilkan _MENU_ data',
            zeroRecords: 'Tidak ada kategori yang cocok',
            emptyTable: 'Belum ada data kategori',
            info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ data',
            infoEmpty: 'Menampilkan 0 sampai 0 dari 0 data',
            infoFiltered: '(difilter dari _MAX_ total data)',
            paginate: { previous: 'Sebelumnya', next: 'Selanjutnya' },
        },
        drawCallback: function () {
            var api = this.api();
            var start = api.page.info().start;
            api.rows({ page: 'current' }).every(function (rowIdx, tableLoop, rowLoop) {
                $(this.node()).find('.dt-no-col').text(start + rowLoop + 1);
            });
        },
    });

    $('#searchKategori').on('keyup input', function () {
        tableKategori.column(1).search($(this).val()).draw();
    });

    $('#btnResetKategori').on('click', function () {
        $('#searchKategori').val('');
        tableKategori.column(1).search('').draw();
        showToast('success', 'Filter direset', 1800);
    });

    var $formKategoriWrap = $('#dmFormKategoriWrap');
    var $formKategoriTitle = $('#dmFormKategoriTitle');
    var $formKategori = $('#dmFormKategori');
    var $inputNamaKategori = $('#inputNamaKategori');
    var $btnTambahKategori = $('#btnTambahKategori');
    var $btnKembaliKategori = $('#btnKembaliKategori');
    var kategoriStoreUrl = dmBaseUrl + 'admin/data-modul/kategori/store';

    function showKategoriForm() {
        $formKategoriWrap.slideDown(220);
        $btnTambahKategori.hide();
        $btnKembaliKategori.show();
        scrollToElement($formKategoriWrap);
        $inputNamaKategori.trigger('focus');
    }

    function hideKategoriForm(doReset) {
        if (doReset !== false) {
            $formKategoriWrap.slideUp(200, function () {
                if ($formKategori.length) {
                    $formKategori[0].reset();
                }
                $formKategori.attr('action', kategoriStoreUrl);
                $formKategoriTitle.text('Tambah Kategori Modul');
            });
        } else {
            $formKategoriWrap.slideUp(200);
        }

        $btnTambahKategori.show();
        $btnKembaliKategori.hide();
    }

    $btnTambahKategori.on('click', function () {
        syncAllCsrfInputs();
        if ($formKategori.length) {
            $formKategori[0].reset();
        }
        $formKategori.attr('action', kategoriStoreUrl);
        $formKategoriTitle.text('Tambah Kategori Modul');
        showKategoriForm();
    });

    $btnKembaliKategori.on('click', function () {
        hideKategoriForm();
    });

    $('#btnBatalKategori').on('click', function () {
        hideKategoriForm();
    });

    $('#dmFormKategori').on('submit', function (event) {
        var namaKategori = $inputNamaKategori.val() || '';
        var missingFields = [];

        syncAllCsrfInputs();

        if (!$.trim(namaKategori)) {
            missingFields.push('Nama Kategori');
        }

        if (missingFields.length) {
            event.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Lengkapi Data',
                text: buildMissingFieldsMessage(missingFields, 1),
                confirmButtonColor: 'var(--primary)',
            });
            return;
        }

        var kategoriError = validateKategoriNama(namaKategori);
        if (kategoriError) {
            event.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Periksa Data',
                text: kategoriError,
                confirmButtonColor: 'var(--primary)',
            });
            return;
        }

        $inputNamaKategori.val(window.SimmagValidation ? window.SimmagValidation.normalizeSpaces(namaKategori) : $.trim(namaKategori));
        syncAllCsrfInputs();
    });

    $(document).on('click', '.btn-edit-kategori', function () {
        syncAllCsrfInputs();
        var id = $(this).data('id');
        var nama = $(this).data('nama');

        $formKategori.attr('action', dmBaseUrl + 'admin/data-modul/kategori/update/' + id);
        $formKategoriTitle.text('Edit Kategori Modul');
        $inputNamaKategori.val(nama);

        showKategoriForm();
    });

    $(document).on('click', '.btn-delete-kategori', function () {
        syncAllCsrfInputs();
        var id = $(this).data('id');
        var nama = $(this).data('nama');
        var jumlah = parseInt($(this).data('jumlah'), 10) || 0;

        if (jumlah > 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Tidak Dapat Dihapus',
                html: 'Kategori <strong>' + escapeHtml(nama) + '</strong> masih memiliki '
                    + '<strong>' + jumlah + ' modul</strong> di dalamnya.<br>'
                    + 'Hapus semua modul terlebih dahulu sebelum menghapus kategori ini.',
                confirmButtonColor: 'var(--primary)',
                confirmButtonText: 'Mengerti',
            });
            return;
        }

        Swal.fire({
            icon: 'warning',
            title: 'Hapus Kategori?',
            html: 'Kategori <strong>' + escapeHtml(nama) + '</strong> akan dihapus secara permanen.<br>'
                + '<span style="font-size:0.875rem;color:var(--text-muted)">Tindakan ini tidak dapat dibatalkan.</span>',
            showCancelButton: true,
            confirmButtonText: '<i class="fas fa-trash"></i>&nbsp; Ya, Hapus',
            cancelButtonText: '<i class="fas fa-times"></i>&nbsp; Batal',
            confirmButtonColor: '#ef4444',
            cancelButtonColor: 'var(--primary)',
            reverseButtons: true,
            focusCancel: true,
        }).then(function (result) {
            if (result.isConfirmed) {
                $('#formDeleteKategori')
                    .attr('action', dmBaseUrl + 'admin/data-modul/kategori/delete/' + id)
                    .submit();
            }
        });
    });

    // ================================================================
    // DATA MODUL
    // ================================================================

    var modulMap = {};
    (Array.isArray(window.dmModulList) ? window.dmModulList : []).forEach(function (item) {
        modulMap[String(item.id)] = item;
    });

    var $modulListSection = $('#dmModulListSection');
    var $modulFormSection = $('#dmModulFormSection');
    var $modulDetailSection = $('#dmModulDetailSection');
    var $modulTopbar = $('#dmModulTopbar');
    var $tabNav = $('#dmTabNav');
    var $btnTambahModul = $('#btnTambahModul');
    var $btnToggleModulFilter = $('#btnToggleModulFilter');
    var $modulFilterPanel = $('#dmModulFilterPanel');
    var $modulForm = $('#dmFormModul');
    var $modulDetailBody = $('#dmModulDetailBody');
    var $modulFormTitle = $('#dmModulFormTitle');
    var $modulFormIcon = $('#dmModulFormIcon');
    var $modulDetailTitle = $('#dmModulDetailTitle');
    var $modulSubmitLabel = $('#dmSubmitModulLabel');
    var $modulFormMode = $('#dmFormMode');
    var $modulFormId = $('#dmFormModulId');
    var $inputNamaModul = $('#inputNamaModul');
    var $inputKategoriModul = $('#inputKategoriModul');
    var $inputDeskripsiModul = $('#inputDeskripsiModul');
    var $inputUrlModul = $('#inputUrlModul');
    var $inputFileModul = $('#inputFileModul');
    var $urlFieldWrap = $('#dmUrlFieldWrap');
    var $fileFieldWrap = $('#dmFileFieldWrap');
    var $fileDropzone = $('#dmFileDropzone');
    var $selectedFileName = $('#dmSelectedFileName');
    var $currentFileInfo = $('#dmCurrentFileInfo');
    var $detailEditButton = $('#btnDetailEdit');
    var $formBackButton = $('#btnFormKembaliModul');
    var $detailBackButton = $('#btnDetailKembali');
    var $deskripsiCounter = $('#dmDeskripsiCounter');
    if (window.SimmagValidation && typeof window.SimmagValidation.applyInputRules === 'function') {
        window.SimmagValidation.applyInputRules([
            { selector: '#inputNamaKategori', rule: 'name_code', label: 'Nama Kategori' },
            { selector: '#inputNamaModul', rule: 'name_code', label: 'Nama Modul' },
            { selector: '#inputDeskripsiModul', rule: 'loose_text', label: 'Deskripsi' },
            { selector: '#inputUrlModul', rule: 'url', label: 'URL Modul' }
        ]);
    }
    var modulStoreUrl = dmBaseUrl + 'admin/data-modul/modul/store';
    var modulUpdateBaseUrl = dmBaseUrl + 'admin/data-modul/modul/update/';
    var modulDeleteBaseUrl = dmBaseUrl + 'admin/data-modul/modul/delete/';
    var modulState = {
        editReturnMode: 'list',
    };

    var tableModul = $('#tabelModul').DataTable({
        responsive: true,
        autoWidth: false,
        pageLength: 10,
        lengthMenu: [10, 25, 50, 100],
        order: [[1, 'asc']],
        dom: 'lrtip',
        columnDefs: [
            // No — selalu tampil, tidak bisa sort/search
            { targets: 0, orderable: false, searchable: false, className: 'text-center', width: '56px' },
            // Nama Modul — selalu tampil
            { targets: 1 },
            // Kategori — masuk child row di mobile (< 768px)
            { targets: 2, className: 'min-tablet-p', width: '160px' },
            // Modul/File — masuk child row di mobile
            { targets: 3, className: 'min-tablet-p' },
            // Tanggal Diubah — masuk child row di mobile
            { targets: 4, className: 'min-tablet-p text-nowrap', width: '150px' },
            // Aksi — selalu tampil, tidak bisa sort/search
            { targets: 5, orderable: false, searchable: false, className: 'text-center col-aksi-modul', width: '96px' },
        ],
        language: {
            lengthMenu: 'Tampilkan _MENU_ data',
            zeroRecords: 'Tidak ada modul yang cocok',
            emptyTable: 'Belum ada data modul',
            info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ data',
            infoEmpty: 'Menampilkan 0 sampai 0 dari 0 data',
            infoFiltered: '(difilter dari _MAX_ total data)',
            paginate: { previous: 'Sebelumnya', next: 'Selanjutnya' },
        },
        drawCallback: function () {
            var api = this.api();
            var start = api.page.info().start;
            var isEmpty = api.rows({ search: 'applied' }).data().length === 0;

            $('#tabelModul').toggleClass('dm-modul-empty', isEmpty);

            api.rows({ page: 'current' }).every(function (rowIdx, tableLoop, rowLoop) {
                $(this.node()).find('.dt-no-col').text(start + rowLoop + 1);
            });
        },
    });

    // min-tablet-p pada columnDefs sudah menangani responsive secara otomatis
    // saat resize — tidak perlu listener manual di sini.

    function initModulFilterSelect() {
        var $select = $('#filterKategoriModul');
        if (!$select.length || typeof $select.select2 !== 'function') {
            return;
        }

        $select.select2({
            placeholder: 'Semua Kategori',
            allowClear: true,
            width: '100%',
            dropdownParent: $('#dmModulFilterPanel'),
            dropdownCssClass: 'dm-modul-select2-dropdown',
        });
    }

    function initModulFormSelect() {
        if (!$inputKategoriModul.length || typeof $inputKategoriModul.select2 !== 'function') {
            return;
        }

        if ($inputKategoriModul.hasClass('select2-hidden-accessible')) {
            return;
        }

        $inputKategoriModul.select2({
            placeholder: 'Pilih Kategori',
            allowClear: true,
            width: '100%',
            dropdownParent: $modulFormSection,
            dropdownCssClass: 'dm-modul-select2-dropdown',
        });
    }

    function isModulTabActive() {
        return $('#tab-modul').hasClass('active');
    }

    function syncModulHeading(modeKey) {
        var metaKeyMap = {
            list: 'modulList',
            create: 'modulCreate',
            detail: 'modulDetail',
            edit: 'modulEdit',
        };

        syncPageHeading(metaKeyMap[modeKey] || 'modulList');
    }

    function setModulFilterButtonState(isOpen) {
        $btnToggleModulFilter.html(
            isOpen
                ? '<i class="fas fa-times"></i><span>Tutup Filter</span>'
                : '<i class="fas fa-filter"></i><span>Filter</span>'
        );
    }

    function hideModulFilterPanel(immediate) {
        if (!$modulFilterPanel.length) {
            return;
        }

        if (immediate) {
            $modulFilterPanel.hide();
            setModulFilterButtonState(false);
            return;
        }

        $modulFilterPanel.stop(true, true).slideUp(200, function () {
            tableModul.columns.adjust().responsive.recalc();
        });
        setModulFilterButtonState(false);
    }

    function toggleModulFilterPanel() {
        if (!$modulFilterPanel.length) {
            return;
        }

        var isOpen = $modulFilterPanel.is(':visible');
        $modulFilterPanel.stop(true, true)[isOpen ? 'slideUp' : 'slideDown'](200, function () {
            tableModul.columns.adjust().responsive.recalc();
        });
        setModulFilterButtonState(!isOpen);
    }

    function syncModulChrome(modeKey) {
        var isList = modeKey === 'list';
        var hideNav = isModulTabActive() && !isList;

        $tabNav.toggle(!hideNav);
        $modulTopbar.toggle(isModulTabActive() && isList);

        if (!isList) {
            hideModulFilterPanel(true);
        }
    }

    function resolveModulModeKey(sectionName) {
        if (sectionName === 'list') {
            return 'list';
        }

        if (sectionName === 'detail') {
            return 'detail';
        }

        return $modulFormMode.val() === 'edit' ? 'edit' : 'create';
    }

    function showModulSection(sectionName) {
        var modeKey = resolveModulModeKey(sectionName);
        var isList = modeKey === 'list';

        $modulListSection.toggle(isList);
        $modulFormSection.toggle(sectionName === 'form');
        $modulDetailSection.toggle(sectionName === 'detail');
        syncModulChrome(modeKey);
        syncModulHeading(modeKey);

        if (isList) {
            tableModul.columns.adjust().responsive.recalc();
        }
    }

    function updateDeskripsiCounter() {
        var length = ($inputDeskripsiModul.val() || '').length;
        $deskripsiCounter.text(length);
    }

    function setSelectedFileLabel(file) {
        var hasFile = !!file;

        $fileDropzone.toggleClass('has-file', hasFile);
        $selectedFileName.toggleClass('has-file', hasFile);

        if (!file) {
            $selectedFileName.text('Belum ada file dipilih');
            return;
        }

        $selectedFileName.text('File dipilih: ' + file.name + ' (' + Math.ceil(file.size / 1024) + ' KB)');
    }

    function setCurrentFileInfo(htmlContent) {
        if (!htmlContent) {
            $currentFileInfo.hide().html('');
            return;
        }

        $currentFileInfo.html(htmlContent).show();
    }

    function syncModulTypeFields() {
        var selectedType = $('input[name="tipe_modul"]:checked').val() || 'link';
        var isFile = selectedType === 'file';
        var isEdit = $modulFormMode.val() === 'edit';
        var hasCurrentFile = !!$modulForm.data('currentFile');

        $urlFieldWrap.toggle(!isFile);
        $fileFieldWrap.toggle(isFile);
        $inputUrlModul.prop('required', !isFile);
        $inputFileModul.prop('required', isFile && (!isEdit || !hasCurrentFile));

        if (!isFile) {
            $inputFileModul.val('');
            setSelectedFileLabel(null);
        }
    }

    function syncUrlInputValidity() {
        if (!$inputUrlModul.length || typeof $inputUrlModul[0].setCustomValidity !== 'function') {
            return;
        }

        var selectedType = $('input[name="tipe_modul"]:checked').val() || 'link';
        var url = $.trim($inputUrlModul.val() || '');
        var isValid = selectedType !== 'link' || url === '' || /^https:\/\/.+/i.test(url);

        $inputUrlModul[0].setCustomValidity(
            isValid ? '' : 'URL modul harus diawali dengan https://'
        );
    }

    function resetModulForm() {
        syncAllCsrfInputs();

        if ($modulForm.length) {
            $modulForm[0].reset();
        }

        $modulForm.attr('action', modulStoreUrl);
        $modulFormMode.val('create');
        $modulFormId.val('');
        $modulForm.data('currentFile', '');
        modulState.editReturnMode = 'list';
        $modulFormTitle.text('Tambah Modul');
        $modulFormIcon.attr('class', 'fas fa-plus-circle');
        $modulSubmitLabel.text('Tambah');
        $('input[name="tipe_modul"][value="link"]').prop('checked', true);
        $inputKategoriModul.val('').trigger('change');
        $inputUrlModul.val('');
        $inputFileModul.val('');
        setSelectedFileLabel(null);
        setCurrentFileInfo('');
        updateDeskripsiCounter();
        syncUrlInputValidity();
        syncModulTypeFields();
    }

    function buildDetailAssetHtml(modul) {
        if (modul.tipe === 'link') {
            return ''
                + '<div class="dm-detail-item dm-detail-item-full">'
                + '  <span class="dm-detail-label"><i class="fas fa-link"></i> Modul</span>'
                + '  <div class="dm-detail-asset-card">'
                + '      <div class="dm-detail-asset-icon"><i class="' + escapeHtml(modul.icon_class) + '"></i></div>'
                + '      <div class="dm-detail-asset-name">' + escapeHtml(modul.external_url) + '</div>'
                + '      <div class="dm-detail-asset-meta">Tipe: Link Eksternal</div>'
                + '      <div class="dm-detail-asset-actions">'
                + '          <a href="' + escapeHtml(modul.asset_url || modul.external_url) + '" target="_blank" rel="noopener noreferrer" class="btn-dm-primary">'
                + '              <i class="fas fa-eye"></i> Buka Link'
                + '          </a>'
                + '      </div>'
                + '  </div>'
                + '</div>';
        }

        var assetActions;
        if (modul.file_exists) {
            assetActions = ''
                + '<a href="' + escapeHtml(modul.asset_url || modul.preview_url) + '"'
                + (modul.asset_target ? ' target="' + escapeHtml(modul.asset_target) + '" rel="noopener noreferrer"' : '')
                + ' class="btn-dm-primary">'
                + '    <i class="fas ' + (modul.is_pdf ? 'fa-eye' : 'fa-download') + '"></i> '
                + escapeHtml(modul.asset_label || (modul.is_pdf ? 'Lihat File' : 'Unduh File'))
                + '</a>';
        } else {
            assetActions = ''
                + '<button type="button" class="btn-dm-primary" disabled>'
                + '    <i class="fas fa-ban"></i> File Tidak Tersedia'
                + '</button>';
        }

        return ''
            + '<div class="dm-detail-item dm-detail-item-full">'
            + '  <span class="dm-detail-label"><i class="fas fa-folder-open"></i> Modul</span>'
            + '  <div class="dm-detail-asset-card">'
            + '      <div class="dm-detail-asset-icon"><i class="' + escapeHtml(modul.icon_class) + '"></i></div>'
            + '      <div class="dm-detail-asset-name">' + escapeHtml(modul.file_name || modul.table_asset) + '</div>'
            + '      <div class="dm-detail-asset-meta">'
            + '          Ukuran: ' + escapeHtml(modul.file_size_label || '-') + ' | Format: ' + escapeHtml(modul.file_ext || '-')
            + '      </div>'
            + (modul.file_exists ? '' : '<div class="dm-detail-asset-warning">File belum tersedia di server.</div>')
            + '      <div class="dm-detail-asset-actions">' + assetActions + '</div>'
            + '  </div>'
            + '</div>';
    }

    function renderModulDetail(modul) {
        var html = ''
            + '<div class="dm-detail-grid">'
            + '  <div class="dm-detail-item">'
            + '      <span class="dm-detail-label"><i class="fas fa-book"></i> Nama Modul</span>'
            + '      <div class="dm-detail-value">' + escapeHtml(modul.nama_modul) + '</div>'
            + '  </div>'
            + '  <div class="dm-detail-item">'
            + '      <span class="dm-detail-label"><i class="fas fa-tags"></i> Kategori</span>'
            + '      <div class="dm-detail-value">' + escapeHtml(modul.nama_kategori) + '</div>'
            + '  </div>'
            + '  <div class="dm-detail-item dm-detail-item-full">'
            + '      <span class="dm-detail-label"><i class="fas fa-align-left"></i> Deskripsi</span>'
            + '      <div class="dm-detail-value">' + escapeHtml(modul.ket_modul || '-') + '</div>'
            + '  </div>'
            + buildDetailAssetHtml(modul)
            + '  <div class="dm-detail-item">'
            + '      <span class="dm-detail-label"><i class="fas fa-calendar-check"></i> Terakhir Diubah</span>'
            + '      <div class="dm-detail-value">' + escapeHtml(formatDateTime(modul.tgl_diubah)) + '</div>'
            + '  </div>'
            + '  <div class="dm-detail-item">'
            + '      <span class="dm-detail-label"><i class="fas fa-calendar-plus"></i> Tanggal Ditambahkan</span>'
            + '      <div class="dm-detail-value">' + escapeHtml(formatDateTime(modul.tgl_dibuat)) + '</div>'
            + '  </div>'
            + '</div>';

        $modulDetailBody.html(html);
        $detailEditButton.data('id', modul.id);
    }

    function showModulList() {
        modulState.editReturnMode = 'list';
        showModulSection('list');
        updateModulUrl('list');
        scrollToElement($modulListSection);
    }

    function showModulCreate() {
        resetModulForm();
        $modulFormTitle.text('Tambah Modul');
        $modulDetailTitle.text('Detail Modul');
        showModulSection('form');
        updateModulUrl('create');
        scrollToElement($modulFormSection);
        $inputNamaModul.trigger('focus');
    }

    function showModulDetail(id) {
        var modul = modulMap[String(id)];
        if (!modul) {
            showModulList();
            return;
        }

        renderModulDetail(modul);
        $modulDetailTitle.text('Detail Modul');
        modulState.editReturnMode = 'detail';
        showModulSection('detail');
        updateModulUrl('detail', modul.id);
        scrollToElement($modulDetailSection);
    }

    function showModulEdit(id, returnMode) {
        var modul = modulMap[String(id)];
        if (!modul) {
            showModulList();
            return;
        }

        resetModulForm();
        modulState.editReturnMode = returnMode || 'list';
        $modulForm.attr('action', modulUpdateBaseUrl + modul.id);
        $modulFormMode.val('edit');
        $modulFormId.val(modul.id);
        $modulFormTitle.text('Ubah Modul');
        $modulFormIcon.attr('class', 'fas fa-pen-to-square');
        $modulSubmitLabel.text('Simpan Perubahan');

        $inputNamaModul.val(modul.nama_modul);
        $inputKategoriModul.val(modul.id_kat_m).trigger('change');
        $inputDeskripsiModul.val(modul.ket_modul || '');

        $('input[name="tipe_modul"][value="' + modul.tipe + '"]').prop('checked', true);

        if (modul.tipe === 'link') {
            $inputUrlModul.val(modul.external_url || modul.path || '');
            $modulForm.data('currentFile', '');
            setCurrentFileInfo('');
        } else {
            $inputUrlModul.val('');
            $modulForm.data('currentFile', modul.file_name || modul.path || '');
            setCurrentFileInfo(
                '<strong>File saat ini:</strong> ' + escapeHtml(modul.file_name || modul.table_asset || '-')
                + '<br><span>Biarkan kosong jika tidak ingin mengganti file.</span>'
            );
        }

        updateDeskripsiCounter();
        syncUrlInputValidity();
        syncModulTypeFields();
        showModulSection('form');
        updateModulUrl('edit', modul.id, modulState.editReturnMode);
        scrollToElement($modulFormSection);
        $inputNamaModul.trigger('focus');
    }

    function restoreModulState() {
        var currentUrl = new URL(window.location.href);
        var isModulActive = currentUrl.searchParams.get('tab') === 'modul' || window.dmActiveTab === 'modul';

        if (!isModulActive) {
            $modulListSection.show();
            $modulFormSection.hide();
            $modulDetailSection.hide();
            syncModulChrome('list');
            syncPageHeading('kategori');
            return;
        }

        var state = getModulUrlState();
        if (state.mode === 'detail' && state.id) {
            showModulDetail(state.id);
            return;
        }
        if (state.mode === 'edit' && state.id) {
            showModulEdit(state.id, state.returnMode);
            return;
        }
        if (state.mode === 'create') {
            showModulCreate();
            return;
        }

        showModulList();
    }

    $('#searchModul').on('keyup input', function () {
        tableModul.column(1).search($(this).val()).draw();
    });

    $('#filterKategoriModul').on('change', function () {
        var value = $(this).val();
        var search = value
            ? '^' + $.fn.dataTable.util.escapeRegex(value) + '$'
            : '';

        tableModul.column(2).search(search, true, false).draw();
    });

    $('#btnResetModulFilter').on('click', function () {
        $('#searchModul').val('');
        $('#filterKategoriModul').val('').trigger('change');
        tableModul.column(1).search('').column(2).search('', true, false).draw();
        showToast('info', 'Filter direset', 1800);
    });

    $btnTambahModul.on('click', function () {
        showModulCreate();
    });

    $btnToggleModulFilter.on('click', function () {
        toggleModulFilterPanel();
    });

    $detailBackButton.on('click', function () {
        showModulList();
    });

    $formBackButton.on('click', function () {
        if (modulState.editReturnMode === 'detail' && $modulFormId.val()) {
            showModulDetail($modulFormId.val());
            return;
        }

        showModulList();
    });

    $(document).on('click', '.btn-view-modul', function () {
        showModulDetail($(this).data('id'));
    });

    $('#btnDetailEdit').on('click', function () {
        var id = $(this).data('id');
        if (id) {
            showModulEdit(id, 'detail');
        }
    });

    $('input[name="tipe_modul"]').on('change', function () {
        syncUrlInputValidity();
        syncModulTypeFields();
    });

    $inputDeskripsiModul.on('input', function () {
        updateDeskripsiCounter();
    });

    $inputUrlModul.on('input blur', function () {
        syncUrlInputValidity();
    });

    $fileDropzone.on('click', function () {
        $inputFileModul.trigger('click');
    });

    $fileDropzone.on('dragover', function (e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).addClass('dragover');
    });

    $fileDropzone.on('dragleave dragend', function (e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).removeClass('dragover');
    });

    $fileDropzone.on('drop', function (e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).removeClass('dragover');

        var files = e.originalEvent && e.originalEvent.dataTransfer
            ? e.originalEvent.dataTransfer.files
            : null;

        if (!files || !files.length) {
            return;
        }

        try {
            $inputFileModul[0].files = files;
        } catch (err) {
            // noop: fallback ke dialog manual jika browser menolak assignment
        }

        setSelectedFileLabel(files[0]);
    });

    $inputFileModul.on('change', function () {
        var file = this.files && this.files.length ? this.files[0] : null;
        setSelectedFileLabel(file);
    });

    $(document).on('click', '.btn-delete-modul', function () {
        var id = $(this).data('id');
        var nama = $(this).data('nama');

        Swal.fire({
            icon: 'warning',
            title: 'Hapus Modul?',
            html: 'Modul <strong>' + escapeHtml(nama) + '</strong> akan dihapus secara permanen.<br>'
                + '<span style="font-size:0.875rem;color:var(--text-muted)">Tindakan ini tidak dapat dibatalkan.</span>',
            showCancelButton: true,
            confirmButtonText: '<i class="fas fa-trash"></i>&nbsp; Ya, Hapus',
            cancelButtonText: '<i class="fas fa-times"></i>&nbsp; Batal',
            confirmButtonColor: '#ef4444',
            cancelButtonColor: 'var(--primary)',
            reverseButtons: true,
            focusCancel: true,
        }).then(function (result) {
            if (!result.isConfirmed) {
                return;
            }

            $.ajax({
                url: modulDeleteBaseUrl + id,
                method: 'POST',
                data: buildCsrfData(),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': getCsrfToken(),
                },
                success: function (res) {
                    if (!res || !res.success) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal!',
                            text: res && res.message ? res.message : 'Gagal menghapus modul.',
                            confirmButtonColor: 'var(--primary)',
                        });
                        return;
                    }

                    showToast('success', res.message || 'Modul berhasil dihapus.', 2200);
                    setTimeout(function () {
                        window.location.href = res.redirect_url || (dmBaseUrl + 'admin/data-modul?tab=modul');
                    }, 900);
                },
                error: function (xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal!',
                        text: xhr.responseJSON?.message || 'Terjadi kesalahan.',
                        confirmButtonColor: 'var(--primary)',
                    });
                },
            });
        });
    });

    $modulForm.on('submit', function (e) {
        e.preventDefault();
        syncAllCsrfInputs();
        syncUrlInputValidity();

        var validationError = validateModulFields();
        if (validationError) {
            Swal.fire({
                icon: 'warning',
                title: 'Periksa Data',
                text: validationError,
                confirmButtonColor: 'var(--primary)',
            });
            return;
        }

        $inputNamaModul.val(window.SimmagValidation ? window.SimmagValidation.normalizeSpaces($inputNamaModul.val()) : $.trim($inputNamaModul.val()));
        $inputDeskripsiModul.val(window.SimmagValidation ? window.SimmagValidation.normalizeSpaces($inputDeskripsiModul.val()) : $.trim($inputDeskripsiModul.val()));
        if ($('input[name="tipe_modul"]:checked').val() === 'link') {
            $inputUrlModul.val($.trim($inputUrlModul.val() || ''));
        }

        var isEdit = $modulFormMode.val() === 'edit';
        var id = $modulFormId.val();
        var actionUrl = isEdit ? modulUpdateBaseUrl + id : modulStoreUrl;
        var token = getCsrfToken();
        var formData = new FormData(this);

        if (csrfName && token) {
            formData.set(csrfName, token);
        }

        var $submitBtn = $('#btnSubmitModulForm');
        var originalHtml = $submitBtn.html();
        $submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Menyimpan...');

        $.ajax({
            url: actionUrl,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': token,
            },
            success: function (res) {
                if (!res || !res.success) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal!',
                        text: res && res.message ? res.message : 'Terjadi kesalahan.',
                        confirmButtonColor: 'var(--primary)',
                    });
                    return;
                }

                showToast('success', res.message || 'Modul berhasil disimpan.', 2200);
                setTimeout(function () {
                    window.location.href = res.redirect_url || (dmBaseUrl + 'admin/data-modul?tab=modul');
                }, 900);
            },
            error: function (xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal!',
                    text: xhr.responseJSON?.message || 'Terjadi kesalahan.',
                    confirmButtonColor: 'var(--primary)',
                });
            },
            complete: function () {
                $submitBtn.prop('disabled', false).html(originalHtml);
            },
        });
    });

    initModulFilterSelect();
    initModulFormSelect();
    setModulFilterButtonState(false);
    restoreModulState();
    syncAllCsrfInputs();
});
