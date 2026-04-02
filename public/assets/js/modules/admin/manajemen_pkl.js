/**
 * SIMMAG ODC — Manajemen PKL Admin JS
 * public/assets/js/modules/admin/manajemen_pkl.js
 *
 * Fitur:
 *   1. Tab switching (Data Instansi | Data PKL) + update breadcrumb H1
 *   2. Section switching (tabel ↔ filter ↔ form)
 *   3. DataTables dengan Responsive extension + custom filter
 *   4. Select2 + tags untuk field kota
 *   5. AJAX: store, update, delete instansi
 *   6. SweetAlert konfirmasi delete
 */

var mpklPklTableIds = ['tabel-aktif', 'tabel-aktif-selesai', 'tabel-aktif-nonaktif'];

function mpklEscapeRegex(value) {
    return $.fn.dataTable.util.escapeRegex(String(value || ''));
}

$(document).ready(function () {

    /* ══════════════════════════════════════════════════════════ */
    /* CONFIG                                                      */
    /* ══════════════════════════════════════════════════════════ */

    var cfg = window.MPKL || {};
    var urlStore = cfg.urlStore || '';
    var urlUpdate = cfg.urlUpdate || '';
    var urlDelete = cfg.urlDelete || '';
    var urlBase = cfg.urlBase || '';
    var kotaList = cfg.kotaList || [];
    var activeTab = cfg.activeTab || 'instansi';
    var initMode = cfg.initMode || 'list';
    // CSRF — diperbarui setiap respons AJAX karena CI4 regenerate
    function getCsrf() {
        return {
            name: document.querySelector('meta[name="csrf-token-name"]')?.content ?? '',
            hash: document.querySelector('meta[name="csrf-token-hash"]')?.content ?? '',
        };
    }

    function buildCsrfData(extra) {
        var csrf = getCsrf();
        var obj = {};
        obj[csrf.name] = csrf.hash;
        return Object.assign(obj, extra || {});
    }

    /* ══════════════════════════════════════════════════════════ */
    /* 1. TAB SWITCHING                                           */
    /* ══════════════════════════════════════════════════════════ */

    var elHeaderH1 = document.querySelector('.page-title');
    var elTitleMain = elHeaderH1 ? elHeaderH1.querySelector('.title-main') : null;
    var elTitleSub = elHeaderH1 ? elHeaderH1.querySelector('.title-sub') : null;

    $('.mpkl-tab-btn').on('click', function () {
        var target = $(this).data('tab'); // 'instansi' | 'pkl'

        // Navigasi ke controller yang menangani tab tersebut:
        //   tab instansi → InstansiAdminController (/admin/manajemen-pkl)
        //   tab pkl      → MPklAdminController    (/admin/manajemen-pkl/pkl)
        // replaceState tidak dipakai karena tidak trigger server render,
        // sehingga data PKL / instansi tidak ter-load sesuai tab yang dipilih.
        if (target === 'pkl') {
            window.location.href = cfg.urlPklBase || (urlBase + '/pkl');
        } else {
            window.location.href = urlBase;
        }
    });

    /* ══════════════════════════════════════════════════════════ */
    /* 2. DATATABLES — INSTANSI                                   */
    /* ══════════════════════════════════════════════════════════ */

    var dtInstansi = $('#tabelInstansi').DataTable({
        responsive: true,
        autoWidth: false,
        // dom: hilangkan 'f' (filter/search bawaan DT) karena sudah pakai custom filter panel
        // l=length, r=processing, t=table, i=info, p=pagination
        dom: 'lrtip',
        language: {
            lengthMenu: 'Tampilkan _MENU_ data',
            zeroRecords: 'Tidak ada data yang ditemukan',
            info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ data',
            infoEmpty: 'Menampilkan 0 sampai 0 dari 0 data',
            infoFiltered: '(difilter dari _MAX_ total data)',
            search: 'Cari:',
            paginate: {
                first: 'Pertama',
                last: 'Terakhir',
                next: 'Selanjutnya',
                previous: 'Sebelumnya',
            },
        },
        columnDefs: [
            { targets: 0, orderable: false }, // No       — selalu tampil
            { targets: 1 }, // Nama     — selalu tampil
            { targets: 2, className: 'min-tablet-p' }, // Kategori — tampil >= 768px, child row di mobile
            { targets: 3, className: 'min-tablet-p' }, // Alamat   — tampil >= 768px, child row di mobile
            { targets: 4, orderable: false }, // Aksi     — selalu tampil
        ],
        // min-tablet-p = "minimum tablet portrait" (768px ke atas).
        // Di atas 768px: kolom tampil normal di tabel.
        // Di bawah 768px (mobile): kolom masuk child row expand (+).
        // Ini berbeda dengan className:'none' yang hide di SEMUA ukuran layar.
        pageLength: 10,
        order: [[1, 'asc']],
        drawCallback: function () {
            var api = this.api();
            var start = api.page.info().start;
            api.column(0, { page: 'current' }).nodes().each(function (cell, i) {
                cell.innerHTML = start + i + 1;
            });
        }
    });

    /* ── Custom Filter (filter panel → DataTables search) ── */

    // Nama instansi → search global DataTables
    $('#filterNamaInstansi').on('keyup', function () {
        dtInstansi.column(1).search($(this).val()).draw();
    });

    // Kategori → search kolom kategori
    $('#filterKategoriInstansi').on('change', function () {
        var value = $(this).val();
        var search = value ? '^' + mpklEscapeRegex(value) + '$' : '';
        dtInstansi.column(2).search(search, true, false).draw();
    });

    // Kota → search kolom alamat (kolom 3 berisi "alamat, kota")
    $('#filterKotaInstansi').on('change', function () {
        dtInstansi.column(3).search($(this).val()).draw();
    });

    // Reset filter + toast notifikasi
    $('#btnResetFilterInstansi').on('click', function () {
        $('#filterNamaInstansi').val('');
        $('#filterKategoriInstansi').val('');
        $('#filterKotaInstansi').val('');
        dtInstansi.search('').columns().search('').draw();

        Swal.fire({
            toast: true, position: 'top-end', icon: 'info',
            title: 'Filter direset', showConfirmButton: false,
            timer: 1800, timerProgressBar: true,
        });
    });

    /* ══════════════════════════════════════════════════════════ */
    /* 3. SELECT2 — KOTA (form instansi)                          */
    /* ══════════════════════════════════════════════════════════ */

    function initSelect2Kota() {
        var $kotaSelect = $('#inputKotaInstansi');
        if (!$kotaSelect.length) return;

        // Tambahkan kota dari PHP sebagai option jika belum ada
        kotaList.forEach(function (kota) {
            if (!$kotaSelect.find('option[value="' + kota + '"]').length) {
                $kotaSelect.append(new Option(kota, kota));
            }
        });

        $kotaSelect.select2({
            tags: true,
            placeholder: 'Pilih atau Ketik Kota Baru',
            allowClear: true,
            width: '100%',
            dropdownParent: $('#sectionFormInstansi'),
            createTag: function (params) {
                var term = $.trim(params.term);
                if (term === '') return null;
                return { id: term, text: term, newTag: true };
            },
            language: {
                noResults: function () { return 'Tidak ada kota yang cocok'; },
                inputTooShort: function () { return 'Ketik untuk mencari atau menambah kota baru'; },
            },
        });
    }

    // Set nilai Select2 (termasuk opsi baru yang belum ada di list)
    function setSelect2Kota(value) {
        var $sel = $('#inputKotaInstansi');
        if (!value) {
            $sel.val(null).trigger('change');
            return;
        }
        if (!$sel.find('option[value="' + value + '"]').length) {
            $sel.append(new Option(value, value, true, true));
        }
        $sel.val(value).trigger('change');
    }

    /* ══════════════════════════════════════════════════════════ */
    /* 4. SECTION SWITCHING — INSTANSI                            */
    /* ══════════════════════════════════════════════════════════ */

    function showTableInstansi() {
        // Opsi A: navigasi ke URL list — server render state yang benar
        // Ini memastikan refresh pun tetap di mode yang sama
        var url = urlBase + '?tab=' + activeTab;
        window.location.href = url;
    }

    function showFormInstansi(mode, editId) {
        // Opsi A: navigasi ke URL mode — server render section + pre-fill data
        var url = urlBase + '?tab=' + activeTab + '&mode=' + mode;
        if (mode === 'edit' && editId) {
            url += '&id=' + editId;
        }
        window.location.href = url;
    }

    function resetFormInstansi() {
        $('#instansiEditId').val('');
        $('#formInstansi')[0].reset();
        setSelect2Kota(null);
    }

    // Tambah (dari toolbar table)
    $('#btnTambahInstansi').on('click', function () {
        showFormInstansi('tambah');
    });

    // Batal → kembali ke list (satu-satunya tombol navigasi di form)
    $('#btnBatalInstansi').on('click', function () {
        showTableInstansi();
    });

    // Init Select2 jika halaman load di mode form (tambah/edit)
    if (initMode === 'tambah' || initMode === 'edit') {
        initSelect2Kota();
    }

    // Filter toggle
    $('#btnFilterInstansi').on('click', function () {
        var $panel = $('#filterPanelInstansi');
        if ($panel.is(':visible')) {
            $panel.slideUp(200);
            $(this).html('<i class="fas fa-filter"></i> Filter');
        } else {
            $panel.slideDown(200);
            $(this).html('<i class="fas fa-times"></i> Kembali');
        }
    });

    /* ══════════════════════════════════════════════════════════ */
    /* 5. EDIT — PRE-FILL FORM                                    */
    /* ══════════════════════════════════════════════════════════ */

    $(document).on('click', '.btn-edit-instansi', function () {
        var btn = $(this);
        var editId = btn.data('id');
        // Opsi A: navigasi ke URL edit — server pre-fill data dari DB
        showFormInstansi('edit', editId);
    });

    /* ══════════════════════════════════════════════════════════ */
    /* 6. AJAX — STORE & UPDATE                                   */
    /* ══════════════════════════════════════════════════════════ */

    $('#formInstansi').on('submit', function (e) {
        e.preventDefault();

        var editId = $('#instansiEditId').val();
        var isEdit = editId !== '' && parseInt(editId, 10) > 0;
        var url = isEdit ? (urlUpdate + '/' + editId) : urlStore;

        var payload = buildCsrfData({
            kategori_instansi: $('#inputKategoriInstansi').val(),
            nama_instansi: $('#inputNamaInstansi').val(),
            alamat_instansi: $('#inputAlamatInstansi').val(),
            kota_instansi: $('#inputKotaInstansi').val(),
        });

        var $btn = $('#btnSubmitInstansi');
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Menyimpan...');

        $.ajax({
            url: url,
            method: 'POST',
            data: payload,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function (res) {
                if (!res.success) {
                    Swal.fire({ icon: 'error', title: 'Gagal!', text: res.message, confirmButtonColor: 'var(--primary)' });
                    return;
                }

                if (isEdit) {
                    updateDtRow(editId, res.data);
                } else {
                    addDtRow(res.data);
                }

                Swal.fire({
                    toast: true, position: 'top-end', icon: 'success',
                    title: res.message, showConfirmButton: false,
                    timer: 2500, timerProgressBar: true,
                });
                setTimeout(function () {
                    showTableInstansi();
                }, 700);
            },
            error: function (xhr) {
                var msg = xhr.responseJSON?.message ?? 'Terjadi kesalahan. Coba lagi.';
                Swal.fire({ icon: 'error', title: 'Gagal!', text: msg, confirmButtonColor: 'var(--primary)' });
            },
            complete: function () {
                $btn.prop('disabled', false)
                    .html('<i class="fas fa-save"></i> <span id="submitInstansiLabel">'
                        + (isEdit ? 'Simpan' : 'Tambah') + '</span>');
            },
        });
    });

    /* ══════════════════════════════════════════════════════════ */
    /* 7. AJAX — DELETE (SweetAlert konfirmasi)                   */
    /* ══════════════════════════════════════════════════════════ */

    $(document).on('click', '.btn-delete-instansi', function () {
        var btn = $(this);
        var id = btn.data('id');
        var nama = btn.data('nama');

        Swal.fire({
            title: 'Hapus Instansi?',
            html: 'Data <strong>' + nama + '</strong> akan dihapus permanen.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: '<i class="fas fa-trash"></i> Ya, Hapus',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            reverseButtons: true,
        }).then(function (result) {
            if (!result.isConfirmed) return;

            $.ajax({
                url: urlDelete + '/' + id,
                method: 'POST',
                data: buildCsrfData(),
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                success: function (res) {
                    if (!res.success) {
                        Swal.fire({ icon: 'error', title: 'Gagal!', text: res.message, confirmButtonColor: 'var(--primary)' });
                        return;
                    }
                    // Hapus baris dari DataTable
                    dtInstansi.row(btn.closest('tr')).remove().draw();
                    // Nomor urut otomatis tidak perlu diupdate — DataTable handle via index

                    Swal.fire({
                        toast: true, position: 'top-end', icon: 'success',
                        title: res.message, showConfirmButton: false,
                        timer: 2500, timerProgressBar: true,
                    });
                },
                error: function (xhr) {
                    var msg = xhr.responseJSON?.message ?? 'Terjadi kesalahan.';
                    Swal.fire({ icon: 'error', title: 'Gagal!', text: msg, confirmButtonColor: 'var(--primary)' });
                },
            });
        });
    });

    /* ══════════════════════════════════════════════════════════ */
    /* 8. HELPER: UPDATE / ADD ROW DI DATATABLE                   */
    /* ══════════════════════════════════════════════════════════ */

    function buildBadge(kategori) {
        return '<span class="badge-kategori-instansi ' + kategori + '">'
            + (kategori === 'kampus' ? 'Kuliah' : 'SMK Sederajat') + '</span>';
    }

    function buildActions(row) {
        return '<button class="btn-tbl-edit btn-edit-instansi" type="button" title="Edit"'
            + ' data-id="' + row.id_instansi + '"'
            + ' data-nama="' + escHtml(row.nama_instansi) + '"'
            + ' data-kategori="' + escHtml(row.kategori_label ?? (row.kategori_instansi === 'kampus' ? 'Kuliah' : 'SMK Sederajat')) + '"'
            + ' data-alamat="' + escHtml(row.alamat_instansi ?? '') + '"'
            + ' data-kota="' + escHtml(row.kota_instansi) + '">'
            + '<i class="fas fa-pen"></i></button>'
            + '<button class="btn-tbl-delete btn-delete-instansi" type="button" title="Hapus"'
            + ' data-id="' + row.id_instansi + '"'
            + ' data-nama="' + escHtml(row.nama_instansi) + '">'
            + '<i class="fas fa-trash"></i></button>';
    }

    function addDtRow(row) {
        var totalRows = dtInstansi.data().count() + 1;
        dtInstansi.row.add([
            totalRows,
            '<strong>' + escHtml(row.nama_instansi) + '</strong>',
            buildBadge(row.kategori_instansi),
            '<span class="text-muted">' + escHtml(row.alamat_kota ?? '') + '</span>',
            '<span class="text-center">' + buildActions(row) + '</span>',
        ]).draw();
    }

    function updateDtRow(id, row) {
        var $btn = $('.btn-edit-instansi[data-id="' + id + '"]');
        if (!$btn.length) return;
        var dtRow = dtInstansi.row($btn.closest('tr'));
        var data = dtRow.data();
        data[1] = '<strong>' + escHtml(row.nama_instansi) + '</strong>';
        data[2] = buildBadge(row.kategori_instansi);
        data[3] = '<span class="text-muted">' + escHtml(row.alamat_kota ?? '') + '</span>';
        data[4] = '<span class="text-center">' + buildActions(row) + '</span>';
        dtRow.data(data).draw();
    }

    function escHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

});
/* ══════════════════════════════════════════════════════════ 
TAMBAHAN: Tab Data PKL — Sub - tab, Filter, Toggle, Hapus
============================================================ */

// ── Sub-Tab Switching ─────────────────────────────────────────
window.switchSubTab = function (tab, cardEl) {
    // A2-FIX: Perbarui activeSubTab (global var dari _tab_pkl.php) agar handler
    // toggle/delete membaca sub-tab yang sedang aktif, bukan nilai awal dari server.
    activeSubTab = tab;

    // Update stat cards
    document.querySelectorAll('.pkl-stat-card').forEach(function (c) { c.classList.remove('selected'); });
    if (cardEl) cardEl.classList.add('selected');
    else {
        var map = { aktif: 0, selesai: 1, nonaktif: 2 };
        document.querySelectorAll('.pkl-stat-card')[map[tab] ?? 0]?.classList.add('selected');
    }
    // Update sub-tab buttons
    document.querySelectorAll('.pkl-subtab-btn').forEach(function (b) { b.classList.remove('active'); });
    document.querySelectorAll('.pkl-subtab-btn')[{ aktif: 0, selesai: 1, nonaktif: 2 }[tab] ?? 0]?.classList.add('active');
    // Update content
    document.querySelectorAll('.pkl-subtab-section').forEach(function (s) { s.classList.remove('active'); });
    document.getElementById('subtab-' + tab)?.classList.add('active');

    // DataTables adjust — wajib setelah tab visible, jika tidak kolom akan 0-width
    // karena DataTables menghitung lebar saat elemen masih hidden
    var dtMap = { aktif: window.dtAktif, selesai: window.dtSelesai, nonaktif: window.dtNonaktif };
    var dt = dtMap[tab];
    if (dt) {
        setTimeout(function () {
            dt.columns.adjust().responsive.recalc();
        }, 50);
    }
};

// ── Toggle Accordion Detail ───────────────────────────────────
window.toggleAnggota = function (header) {
    var body = header.nextElementSibling;
    var icon = header.querySelector('.toggle-icon');
    var open = body.style.display !== 'none';
    body.style.display = open ? 'none' : 'block';
    if (icon) icon.className = 'fas fa-chevron-' + (open ? 'down' : 'up') + ' toggle-icon';
};

$(document).ready(function () {

    var urlPklDelete = window.urlPklDelete || '';
    var urlPklToggle = window.urlPklToggle || '';

    function getCsrfPkl() {
        return {
            name: document.querySelector('meta[name="csrf-token-name"]')?.content ?? '',
            hash: document.querySelector('meta[name="csrf-token-hash"]')?.content ?? '',
        };
    }
    function buildCsrfPkl(extra) {
        var csrf = getCsrfPkl(), obj = {};
        obj[csrf.name] = csrf.hash;
        return Object.assign(obj, extra || {});
    }

    // ── Init DataTables PKL ──────────────────────────────────────
    // Declare di window scope agar accessible dari switchSubTab() yang ada di luar document.ready
    window.dtAktif = null;
    window.dtSelesai = null;
    window.dtNonaktif = null;
    var dtAktif = null, dtSelesai = null, dtNonaktif = null;
    function initDtPkl(id) {
        var $tbl = $('#' + id);
        if (!$tbl.length || $.fn.DataTable.isDataTable('#' + id)) return $.fn.DataTable.isDataTable('#' + id) ? $('#' + id).DataTable() : null;
        return $tbl.DataTable({
            responsive: true, autoWidth: false,
            dom: 'lrtip',
            language: {
                lengthMenu: 'Tampilkan _MENU_ data',
                zeroRecords: 'Tidak ada data',
                info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ data',
                infoEmpty: 'Tidak ada data',
                paginate: { next: 'Selanjutnya', previous: 'Sebelumnya' },
            },
            columnDefs: [
                { targets: 0, orderable: false },
                { targets: [2, 3, 4, 5], className: 'min-tablet-p' },
                { targets: -1, orderable: false },
            ],
            pageLength: 10,
            drawCallback: function () {
                var api = this.api();
                var start = api.page.info().start;
                api.column(0, { page: 'current' }).nodes().each(function (cell, i) {
                    cell.innerHTML = start + i + 1;
                });
            }
        });
    }

    // Init saat tab pkl aktif
    if (typeof activeSubTab !== 'undefined') {
        dtAktif = window.dtAktif = initDtPkl('tabel-aktif');
        dtSelesai = window.dtSelesai = initDtPkl('tabel-aktif-selesai');
        dtNonaktif = window.dtNonaktif = initDtPkl('tabel-aktif-nonaktif');

        // Init Select2 pada filter instansi PKL
        var instansiOpts = typeof instansiListPkl !== 'undefined' ? instansiListPkl : [];
        $('.pkl-filter-instansi').each(function () {
            var $sel = $(this);
            instansiOpts.forEach(function (n) {
                if (!$sel.find('option[value="' + n + '"]').length) {
                    $sel.append(new Option(n, n));
                }
            });
            $sel.select2({
                placeholder: 'Pilih Instansi',
                allowClear: true,
                width: '100%',
                dropdownParent: $sel.closest('.mpkl-filter-panel'),
            });
        });

        // Init Flatpickr untuk filter tanggal (guard: pastikan library sudah load)
        if (typeof flatpickr !== 'undefined') {
            var fpOpts = {
                dateFormat: 'Y-m-d', altInput: true, altFormat: 'd M Y',
                // Flatpickr butuh KEDUA shorthand DAN longhand — jika salah satu
                // undefined maka akan crash dengan error "Cannot read properties of
                // undefined (reading 'join')"
                locale: {
                    firstDayOfWeek: 1,
                    months: {
                        shorthand: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
                        longhand: ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
                    },
                    weekdays: {
                        shorthand: ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'],
                        longhand: ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'],
                    },
                },
            };
            document.querySelectorAll('.flatpickr-date').forEach(function (el) {
                if (!el._flatpickr) flatpickr(el, fpOpts);
            });
        }
    }

    // ── Filter PKL — delegated, pakai data-col + data-table attribute ──────
    // Semua filter field pakai class pkl-filter-nama / pkl-filter-field / pkl-filter-instansi
    // dengan data-col (index kolom DataTables) dan data-table (id tabel)

    function getDtById(tableId) {
        var map = {
            'tabel-aktif': window.dtAktif,
            'tabel-aktif-selesai': window.dtSelesai,
            'tabel-aktif-nonaktif': window.dtNonaktif,
        };
        return map[tableId] || null;
    }

    $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
        var tableId = settings && settings.nTable ? settings.nTable.id : '';
        if (mpklPklTableIds.indexOf(tableId) === -1) {
            return true;
        }

        var rowNode = settings.aoData && settings.aoData[dataIndex]
            ? settings.aoData[dataIndex].nTr
            : null;

        if (!rowNode) {
            return true;
        }

        var $row = $(rowNode);
        var mulaiFilter = $('.pkl-filter-date[data-table="' + tableId + '"][data-date-type="mulai"]').val() || '';
        var akhirFilter = $('.pkl-filter-date[data-table="' + tableId + '"][data-date-type="akhir"]').val() || '';
        var statusFilter = $('.pkl-filter-field[data-table="' + tableId + '"][data-col="6"]').val() || '';
        var rowMulai = ($row.attr('data-tgl-mulai') || '').trim();
        var rowAkhir = ($row.attr('data-tgl-akhir') || '').trim();
        var rowStatus = ($row.attr('data-status-kelompok') || '').trim().toLowerCase();

        if (mulaiFilter && rowMulai !== mulaiFilter) {
            return false;
        }

        if (akhirFilter && rowAkhir !== akhirFilter) {
            return false;
        }

        if (statusFilter && rowStatus !== String(statusFilter).trim().toLowerCase()) {
            return false;
        }

        return true;
    });

    // Nama PKL (keyup)
    $(document).on('keyup', '.pkl-filter-nama', function () {
        var dt = getDtById($(this).data('table'));
        if (dt) dt.column(1).search($(this).val()).draw();
    });

    // Kategori / Instansi / Status Kelompok (change)
    $(document).on('change', '.pkl-filter-field, .pkl-filter-instansi', function () {
        var dt = getDtById($(this).data('table'));
        var col = parseInt($(this).data('col'), 10);
        if (!dt || isNaN(col)) {
            return;
        }

        if (col === 6) {
            dt.draw();
            return;
        }

        var value = $(this).val();
        var search = value ? '^' + mpklEscapeRegex(value) + '$' : '';
        dt.column(col).search(search, true, false).draw();
    });

    $(document).on('change input', '.pkl-filter-date', function () {
        var dt = getDtById($(this).data('table'));
        if (dt) {
            dt.draw();
        }
    });

    // Filter toggle
    $(document).on('click', '.btn-filter-pkl', function () {
        var target = $(this).data('target');
        var $panel = $('#' + target);
        var open = $panel.is(':visible');
        $panel[open ? 'slideUp' : 'slideDown'](200);
        $(this).html(open ? '<i class="fas fa-filter"></i> Filter' : '<i class="fas fa-times"></i> Tutup Filter');
    });

    // Reset filter
    $(document).on('click', '.btn-reset-pkl', function () {
        var tableId = $(this).data('table');
        var $panel = $(this).closest('.pkl-filter-panel');
        $panel.find('input[type="text"]').val('');
        $panel.find('select').val('').trigger('change');
        $panel.find('.pkl-filter-date').each(function () {
            if (this._flatpickr) {
                this._flatpickr.clear();
            }
        });
        var dt = $('#' + tableId).DataTable();
        if (dt) dt.search('').columns().search('').draw();
        Swal.fire({ toast: true, position: 'top-end', icon: 'info', title: 'Filter direset', showConfirmButton: false, timer: 1800, timerProgressBar: true });
    });

    // ── Toggle Status PKL (AJAX) ─────────────────────────────────
    $(document).on('click', '.btn-tbl-toggle', function () {
        var btn = $(this);
        var id = btn.data('id');
        var nama = btn.data('nama');
        var status = btn.data('status');
        var aksi = status === 'aktif' ? 'nonaktifkan' : 'aktifkan';

        Swal.fire({
            title: 'Konfirmasi', icon: 'question',
            html: 'Yakin ingin <strong>' + aksi + '</strong> akun <strong>' + nama + '</strong>?',
            showCancelButton: true,
            confirmButtonText: aksi.charAt(0).toUpperCase() + aksi.slice(1),
            cancelButtonText: 'Batal',
            confirmButtonColor: status === 'aktif' ? '#ef4444' : '#10b981',
            cancelButtonColor: '#64748b',
        }).then(function (r) {
            if (!r.isConfirmed) return;
            $.ajax({
                url: urlPklToggle + '/' + id, method: 'POST',
                data: buildCsrfPkl(),
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                success: function (res) {
                    if (!res.success) { Swal.fire({ icon: 'error', title: 'Gagal!', text: res.message }); return; }
                    Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: res.message, showConfirmButton: false, timer: 2500, timerProgressBar: true });
                    // BUG A2 FIX: location.reload() menyebabkan server default ke sub=aktif.
                    // Arahkan ke URL eksplisit dengan sub-tab yang sedang aktif.
                    setTimeout(function () {
                        var sub = (typeof activeSubTab !== 'undefined') ? activeSubTab : 'aktif';
                        var base = (window.MPKL && window.MPKL.urlPklBase) ? window.MPKL.urlPklBase : window.location.pathname;
                        window.location.href = base + '?sub=' + sub;
                    }, 1500);
                },
                error: function (xhr) {
                    Swal.fire({ icon: 'error', title: 'Gagal!', text: xhr.responseJSON?.message ?? 'Terjadi kesalahan.' });
                }
            });
        });
    });

    // ── Hapus PKL (AJAX) ─────────────────────────────────────────
    $(document).on('click', '.btn-delete-pkl', function () {
        var btn = $(this);
        var id = btn.data('id');
        var nama = btn.data('nama');
        var role = btn.data('role');

        var msg = role === 'ketua'
            ? 'Kamu akan menghapus <strong>' + nama + ' (Ketua)</strong>. <br><span class="text-danger">Semua anggota kelompok juga akan dihapus!</span>'
            : 'Data PKL <strong>' + nama + '</strong> akan dihapus permanen.';

        Swal.fire({
            title: 'Hapus PKL?', html: msg, icon: 'warning',
            showCancelButton: true,
            confirmButtonText: '<i class="fas fa-trash"></i> Ya, Hapus',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#ef4444', cancelButtonColor: '#64748b',
            reverseButtons: true,
        }).then(function (r) {
            if (!r.isConfirmed) return;
            $.ajax({
                url: urlPklDelete + '/' + id, method: 'POST',
                data: buildCsrfPkl(),
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                success: function (res) {
                    if (!res.success) { Swal.fire({ icon: 'error', title: 'Gagal!', text: res.message }); return; }
                    Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: res.message, showConfirmButton: false, timer: 2500, timerProgressBar: true });
                    // BUG A2 FIX: sama seperti toggle — preserve sub-tab aktif
                    setTimeout(function () {
                        var sub = (typeof activeSubTab !== 'undefined') ? activeSubTab : 'aktif';
                        var base = (window.MPKL && window.MPKL.urlPklBase) ? window.MPKL.urlPklBase : window.location.pathname;
                        window.location.href = base + '?sub=' + sub;
                    }, 1500);
                },
                error: function (xhr) {
                    Swal.fire({ icon: 'error', title: 'Gagal!', text: xhr.responseJSON?.message ?? 'Terjadi kesalahan.' });
                }
            });
        });
    });

});
