/**
 * SIMMAG ODC — Penugasan Admin JS
 * public/assets/js/modules/admin/penugasan.js
 */

$(document).ready(function () {
    var cfg = window.MTUGAS || {};
    var tabMeta = {
        kategori: 'Kategori Tugas',
        tugas: 'Tugas'
    };
    var $headerMain = $('.page-title .title-main');
    var $headerSub = $('.page-title .title-sub');

    if (window.SimmagValidation && typeof window.SimmagValidation.applyInputRules === 'function') {
        window.SimmagValidation.applyInputRules([
            { selector: '#namaKategori', rule: 'name_code', label: 'Nama Kategori Tugas' }
        ]);
    }

    function syncHeading(tab) {
        var subTitle = tabMeta[tab] || tabMeta.kategori;
        var mainTitle = 'Manajemen Tugas / Penugasan';

        if ($headerMain.length) {
            $headerMain.text(mainTitle);
        }
        if ($headerSub.length) {
            $headerSub.text(subTitle);
        }

        document.title = mainTitle + ' / ' + subTitle + ' - SIMMAG ODC';
    }

    function syncTabUrl(tab) {
        var url = new URL(window.location.href);
        url.searchParams.set('tab', tab);
        window.history.replaceState(null, '', url.toString());
    }

    // ── 1. Tab Navigation ────────────────────────────────────────
    $('.mpkl-tab-btn').on('click', function (e) {
        e.preventDefault();
        var target = $(this).data('target');
        var tab = $(this).data('tab') || (target === 'tab-tugas' ? 'tugas' : 'kategori');
        $('.mpkl-tab-btn').removeClass('active');
        $(this).addClass('active');
        $('.mpkl-tab-content').removeClass('active');
        $('#' + target).addClass('active');
        syncTabUrl(tab);
        syncHeading(tab);
    });

    // ── 2. DataTables — KATEGORI (tanpa built-in search) ─────────
    var dtKategori = $('#tableKategori').DataTable({
        ajax: { url: cfg.urlKategoriList, dataSrc: 'data' },
        columns: [
            {
                data: null, className: 'text-center', orderable: false,
                render: function (d, t, r, meta) { return meta.row + 1; }
            },
            { data: 'nama_kat_tugas' },
            {
                // Plain text — no badge per permintaan
                data: 'mode_pengumpulan',
                render: function (d) { return d === 'individu' ? 'Individu' : 'Kelompok'; }
            },
            { data: 'created_at', render: function (d) { return d ? tglIndo(d) : '-'; } },
            { data: 'updated_at', render: function (d) { return d ? tglIndo(d) : '-'; } },
            {
                data: null, className: 'text-center', orderable: false,
                render: function (d, t, row) {
                    return '<button class="btn-tbl-edit btn-edit-kat" type="button" title="Edit"' +
                        ' data-id="' + row.id_kat_tugas + '"' +
                        ' data-nama="' + escAttr(row.nama_kat_tugas) + '"' +
                        ' data-mode="' + row.mode_pengumpulan + '">' +
                        '<i class="fas fa-pen"></i></button>' +
                        '<button class="btn-tbl-delete btn-delete-kat" type="button" title="Hapus"' +
                        ' data-id="' + row.id_kat_tugas + '"' +
                        ' data-nama="' + escAttr(row.nama_kat_tugas) + '">' +
                        '<i class="fas fa-trash"></i></button>';
                }
            }
        ],
        language: dtLang(),
        order: [[1, 'asc']],
        pageLength: 10,
        responsive: {
            details: {
                type: 'inline',
                target: 'tr'
            }
        },
        // Hapus "f" (built-in search) — pakai custom search di toolbar
        dom: 'l<"dt-spacer">rt<"dataTables_footer"ip>',
        columnDefs: [
            { targets: 0, orderable: false, className: 'all dtr-control text-center' },
            { targets: 1, className: 'all' },
            { targets: 2, className: 'min-tablet-p' },
            { targets: 3, className: 'min-tablet-p' },
            { targets: 4, className: 'min-tablet-p' },
            { targets: 5, orderable: false, className: 'all text-center' }
        ],
        drawCallback: function () {
            var api = this.api();
            var start = api.page.info().start;
            api.column(0, { page: 'current' }).nodes().each(function (cell, i) {
                cell.innerHTML = start + i + 1;
            });
        }
    });

    // ── Custom search Kategori (input di toolbar _tab_kategori.php) ──
    $('#searchKategori').on('input', function () {
        dtKategori.column(1).search($(this).val()).draw();
    });

    $('#btnResetSearchKategori').on('click', function () {
        $('#searchKategori').val('');
        dtKategori.column(1).search('').draw();
        swalToast('success', 'Pencarian kategori berhasil direset.');
    });

    // ── 3. DataTables — TUGAS (tanpa built-in search) ────────────
    var dtTugas = $('#tableTugas').DataTable({
        ajax: { url: cfg.urlTugasList, dataSrc: 'data' },
        columns: [
            {
                data: null, className: 'text-center', orderable: false,
                render: function (d, t, r, meta) { return meta.row + 1; }
            },
            { data: 'nama_tugas' },
            { data: 'nama_kat_tugas' },
            {
                data: 'mode_pengumpulan',
                render: function (d) {
                    var cls = d === 'individu' ? 'badge-mode-individu' : 'badge-mode-kelompok';
                    var text = d === 'individu' ? 'Individu' : 'Kelompok';
                    return '<span class="badge-mode ' + cls + '">' + text + '</span>';
                }
            },
            {
                // Format: "H:i — d M Y"  Merah jika sudah lewat
                data: 'deadline',
                render: function (d) {
                    if (!d) return '-';
                    var formatted = formatDeadline(d);
                    var lewat = new Date(d.replace(' ', 'T')) < new Date();
                    return lewat
                        ? '<span style="color:var(--accent-red);font-weight:600;">' + formatted + '</span>'
                        : formatted;
                }
            },
            {
                data: null, className: 'text-center', orderable: false,
                render: function (d, t, row) {
                    return '<a href="' + (cfg.urlTugasDetail || '#') + '/' + row.id_tugas + '" class="btn-tbl-view" title="Detail">' +
                        '<i class="fas fa-eye"></i></a>' +
                        '<button class="btn-tbl-delete btn-delete-tugas" type="button" title="Hapus"' +
                        ' data-id="' + row.id_tugas + '" data-nama="' + escAttr(row.nama_tugas) + '">' +
                        '<i class="fas fa-trash"></i></button>';
                }
            }
        ],
        language: dtLang(),
        order: [[4, 'asc']],
        pageLength: 10,
        responsive: {
            details: {
                type: 'inline',
                target: 'tr'
            }
        },
        dom: 'l<"dt-spacer">rt<"dataTables_footer"ip>',
        columnDefs: [
            { targets: 0, orderable: false, className: 'all dtr-control text-center' },
            { targets: 1, className: 'all' },
            { targets: 2, className: 'min-tablet-p' },
            { targets: 3, className: 'min-tablet-p' },
            { targets: 4, className: 'min-tablet-p' },
            { targets: 5, orderable: false, className: 'all text-center' }
        ],
        drawCallback: function () {
            var api = this.api();
            var start = api.page.info().start;
            api.column(0, { page: 'current' }).nodes().each(function (cell, i) {
                cell.innerHTML = start + i + 1;
            });
        }
    });

    // ── 4. Filter Tugas ──────────────────────────────────────────

    $('#btnFilterTugas').on('click', function () {
        $('#filterPanelTugas').slideToggle(200);
        $(this).toggleClass('active');
    });

    // Select2 untuk dropdown kategori filter
    if ($('.filter-select-kategori-tugas').length) {
        $('.filter-select-kategori-tugas').select2({
            placeholder: 'Semua Kategori', allowClear: true, width: '100%'
        });
    }

    // Cari nama tugas
    $('#fNamaTugas').on('input', function () { dtTugas.column(1).search($(this).val()).draw(); });

    // Filter kategori — cari berdasarkan teks nama kategori di kolom 2
    $('#fKategoriTugas').on('change', function () {
        var text = $(this).find('option:selected').text().trim();
        var search = $(this).val()
            ? '^' + $.fn.dataTable.util.escapeRegex(text) + '$'
            : '';

        dtTugas.column(2).search(search, true, false).draw();
    });

    // Reset filter
    $('#btnResetFilterTugas').on('click', function () {
        $('#fNamaTugas').val('');
        $('#fKategoriTugas').val('').trigger('change');
        dtTugas.column(1).search('').column(2).search('', true, false).draw();
        swalToast('success', 'Filter tugas berhasil direset.');
    });

    // ── 5. Form Panel Kategori — Show/Hide ───────────────────────

    $('#btnTambahKategori').on('click', function () {
        resetFormKategori();
        $('#formKategoriPanelTitle').html('<i class="fas fa-plus-circle"></i> Tambah Kategori Tugas');
        $('#panelFormKategori').slideDown(200);
        $('html,body').animate({ scrollTop: $('#panelFormKategori').offset().top - 80 }, 300);
    });

    $('#btnBatalKategori').on('click', function () {
        $('#panelFormKategori').slideUp(200);
        resetFormKategori();
    });

    // ── 6. CRUD Kategori — Store & Update ────────────────────────

    $('#formKategori').on('submit', function (e) {
        e.preventDefault();

        var id = $('#kategoriId').val();
        var nama = $('#namaKategori').val();
        var mode = $('input[name="mode_pengumpulan"]:checked').val();
        var v = window.SimmagValidation || {};
        var missingFields = [];

        if (!$.trim(nama)) missingFields.push('Nama Kategori');
        if (!mode) missingFields.push('Mode Pengumpulan');
        if (missingFields.length) return swalWarn((v.buildMissingFieldsMessage || buildMissingFieldsMessage)(missingFields, 2));

        var namaError = v.validatePatternField
            ? v.validatePatternField(
                'Nama Kategori',
                nama,
                3,
                50,
                /^[\p{L}0-9\s]+$/u,
                'huruf, angka, dan spasi'
            )
            : '';
        if (namaError) return swalWarn(namaError);

        nama = v.normalizeSpaces ? v.normalizeSpaces(nama) : $.trim(nama);

        var url = id ? cfg.urlKategoriUpdate + '/' + id : cfg.urlKategoriStore;
        var $btn = $('#btnSimpanKategori');
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Menyimpan...');

        $.ajax({
            url: url, method: 'POST',
            data: { nama_kategori: nama, mode_pengumpulan: mode, [cfg.csrfName]: getCsrf() },
            success: function (res) {
                if (res.success) {
                    $('#panelFormKategori').slideUp(200);
                    resetFormKategori();
                    dtKategori.ajax.reload(null, false);
                    swalToast('success', res.message || 'Kategori berhasil disimpan.');
                } else {
                    swalWarn(res.message || 'Gagal menyimpan.');
                }
            },
            error: function (xhr) { swalError(xhr); },
            complete: function () { $btn.prop('disabled', false).html('<i class="fas fa-save"></i> Simpan'); }
        });
    });

    // ── 7. Edit Kategori ─────────────────────────────────────────

    $(document).on('click', '.btn-edit-kat', function () {
        var id = $(this).data('id');
        var nama = $(this).data('nama');
        var mode = $(this).data('mode');

        $('#kategoriId').val(id);
        $('#namaKategori').val(nama);
        $('input[name="mode_pengumpulan"][value="' + mode + '"]').prop('checked', true);
        $('#formKategoriPanelTitle').html('<i class="fas fa-pen"></i> Ubah Kategori Tugas');
        $('#panelFormKategori').slideDown(200);
        $('html,body').animate({ scrollTop: $('#panelFormKategori').offset().top - 80 }, 300);
    });

    // ── 8. Delete Kategori ───────────────────────────────────────

    $(document).on('click', '.btn-delete-kat', function () {
        var id = $(this).data('id'), nama = $(this).data('nama');
        Swal.fire({
            icon: 'warning', title: 'Hapus Kategori?',
            html: 'Kategori <strong>"' + escHtml(nama) + '"</strong> akan dihapus.',
            showCancelButton: true,
            confirmButtonColor: 'var(--accent-red)', cancelButtonColor: 'var(--secondary)',
            confirmButtonText: '<i class="fas fa-trash"></i> Ya, Hapus', cancelButtonText: 'Batal',
        }).then(function (r) {
            if (!r.isConfirmed) return;
            $.ajax({
                url: cfg.urlKategoriDelete + '/' + id, method: 'POST',
                data: { [cfg.csrfName]: getCsrf() },
                success: function (res) {
                    if (res.success) { dtKategori.ajax.reload(null, false); swalToast('success', res.message); }
                    else swalWarn(res.message);
                },
                error: function (xhr) { swalError(xhr); }
            });
        });
    });

    // ── 9. Delete Tugas ──────────────────────────────────────────

    $(document).on('click', '.btn-delete-tugas', function () {
        var id = $(this).data('id'), nama = $(this).data('nama');
        Swal.fire({
            icon: 'warning', title: 'Hapus Tugas?',
            html: 'Tugas <strong>"' + escHtml(nama) + '"</strong> dan semua sasarannya akan dihapus.',
            showCancelButton: true,
            confirmButtonColor: 'var(--accent-red)', cancelButtonColor: 'var(--secondary)',
            confirmButtonText: '<i class="fas fa-trash"></i> Ya, Hapus', cancelButtonText: 'Batal',
        }).then(function (r) {
            if (!r.isConfirmed) return;
            $.ajax({
                url: cfg.urlTugasDelete + '/' + id, method: 'POST',
                data: { [cfg.csrfName]: getCsrf() },
                success: function (res) {
                    if (res.success) { dtTugas.ajax.reload(null, false); swalToast('success', res.message); }
                    else swalWarn(res.message);
                },
                error: function (xhr) { swalError(xhr); }
            });
        });
    });

    // ── HELPERS ──────────────────────────────────────────────────

    function resetFormKategori() {
        $('#formKategori')[0].reset();
        $('#kategoriId').val('');
        $('input[name="mode_pengumpulan"][value="individu"]').prop('checked', true);
    }

    function getCsrf() {
        return document.querySelector('meta[name="csrf-token-hash"]')?.content ?? '';
    }

    /**
     * Format deadline: "H:i — d M Y"
     * Input: "2026-03-31 14:00:00" → Output: "14:00 — 31 Mar 2026"
     */
    function formatDeadline(str) {
        if (!str) return '-';
        var bln = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        var d = str.substring(0, 10).split('-');
        var time = str.length >= 16 ? str.substring(11, 16) : '00:00';
        return time + ' \u2014 ' + parseInt(d[2]) + ' ' + bln[parseInt(d[1])] + ' ' + d[0];
    }

    function tglIndo(str) {
        if (!str) return '-';
        var bln = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        var d = str.substring(0, 10).split('-');
        return parseInt(d[2]) + ' ' + bln[parseInt(d[1])] + ' ' + d[0];
    }

    function dtLang() {
        return {
            search: 'Cari:', lengthMenu: 'Tampilkan _MENU_ data',
            info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ data',
            infoEmpty: 'Tidak ada data', infoFiltered: '(difilter dari _MAX_ total data)',
            paginate: { first: '&laquo;', last: '&raquo;', next: '&rsaquo;', previous: '&lsaquo;' },
            zeroRecords: 'Data tidak ditemukan', emptyTable: 'Belum ada data',
            loadingRecords: 'Memuat data...', processing: '<i class="fas fa-spinner fa-spin"></i> Memuat...',
        };
    }

    function escHtml(str) { return $('<div>').text(str == null ? '' : String(str)).html(); }
    function escAttr(str) { return String(str ?? '').replace(/"/g, '&quot;').replace(/'/g, '&#39;'); }

    function swalToast(icon, msg) {
        Swal.fire({
            toast: true, position: 'top-end', icon: icon,
            title: msg, showConfirmButton: false, timer: 2500, timerProgressBar: true
        });
    }
    function swalWarn(msg) {
        Swal.fire({ icon: 'warning', title: 'Perhatian', text: msg, confirmButtonColor: 'var(--primary)' });
    }
    function swalError(xhr) {
        var msg = xhr.responseJSON?.message || 'Terjadi kesalahan jaringan.';
        Swal.fire({ icon: 'error', title: 'Gagal!', text: msg, confirmButtonColor: 'var(--primary)' });
    }

    syncHeading(cfg.activeTab || new URL(window.location.href).searchParams.get('tab') || 'kategori');
});
