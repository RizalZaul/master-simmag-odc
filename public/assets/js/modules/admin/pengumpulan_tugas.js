/**
 * SIMMAG ODC - Pengumpulan Tugas Admin JS
 */

$(document).ready(function () {
    var tabMeta = {
        mandiri: 'Tugas Mandiri',
        kelompok: 'Tugas Kelompok',
        tim: 'Tim Tugas'
    };

    var $headerSub = $('.page-title .title-sub');

    function swalToast(icon, title) {
        if (!window.Swal) {
            return;
        }

        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: icon,
            title: title,
            showConfirmButton: false,
            timer: 1800,
            timerProgressBar: true
        });
    }

    function updateHeader(tab) {
        if ($headerSub.length && tabMeta[tab]) {
            $headerSub.text(tabMeta[tab]);
        }
        if (tabMeta[tab]) {
            document.title = 'Manajemen Tugas / Pengumpulan / ' + tabMeta[tab] + ' - SIMMAG ODC';
        }
    }

    function syncUrl(tab) {
        var url = new URL(window.location.href);
        url.searchParams.set('tab', tab);
        window.history.replaceState(null, '', url.toString());
    }

    function refreshTables() {
        if (!$.fn.DataTable) {
            return;
        }

        $.fn.dataTable
            .tables({ visible: true, api: true })
            .columns.adjust()
            .responsive.recalc();
    }

    function switchTab(tab) {
        $('.mpkl-tab-btn').removeClass('active');
        $('.mpkl-tab-content').removeClass('active');
        $('[data-tab="' + tab + '"]').addClass('active');
        $('#tab-pengumpulan-' + tab).addClass('active');
        syncUrl(tab);
        updateHeader(tab);
        refreshTables();
    }

    $('.mpkl-tab-btn').on('click', function (e) {
        e.preventDefault();
        var tab = $(this).data('tab');
        if (!tabMeta[tab]) {
            return;
        }
        switchTab(tab);
    });

    function dtLang() {
        return {
            lengthMenu: 'Tampilkan _MENU_ data',
            zeroRecords: 'Tidak ada data yang tersedia',
            emptyTable: 'Tidak ada data yang tersedia',
            info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ data',
            infoEmpty: 'Menampilkan 0 sampai 0 dari 0 data',
            infoFiltered: '(difilter dari _MAX_ total data)',
            paginate: { previous: 'Sebelumnya', next: 'Selanjutnya' },
        };
    }

    function initTable(selector) {
        return $(selector).DataTable({
            responsive: {
                details: {
                    type: 'inline',
                    target: 'tr'
                }
            },
            autoWidth: false,
            pageLength: 10,
            lengthMenu: [10, 25, 50, 100],
            order: [[4, 'asc']],
            dom: 'lrtip',
            language: dtLang(),
            columnDefs: [
                { targets: 0, orderable: false, searchable: false, className: 'all dtr-control text-center', width: '56px' },
                { targets: 1, className: 'all' },
                { targets: 2, className: 'min-tablet-p' },
                { targets: 3, className: 'min-tablet-p' },
                { targets: 4, className: 'min-tablet-p' },
                { targets: 5, className: 'min-tablet-p' },
                { targets: 6, orderable: false, searchable: false, className: 'all text-center', width: '78px' },
            ],
            drawCallback: function () {
                var api = this.api();
                var start = api.page.info().start;
                api.rows({ page: 'current' }).every(function (rowIdx, tableLoop, rowLoop) {
                    $(this.node()).find('.dt-no-col').text(start + rowLoop + 1);
                });
            }
        });
    }

    var dtMandiri = $('#tablePengumpulanMandiri').length ? initTable('#tablePengumpulanMandiri') : null;
    var dtKelompok = $('#tablePengumpulanKelompok').length ? initTable('#tablePengumpulanKelompok') : null;
    var dtTim = $('#tablePengumpulanTim').length ? initTable('#tablePengumpulanTim') : null;

    $('#searchPengumpulanMandiri').on('input', function () {
        if (dtMandiri) {
            dtMandiri.column(1).search($(this).val()).draw();
        }
    });

    $('#btnResetPengumpulanMandiri').on('click', function () {
        $('#searchPengumpulanMandiri').val('');
        if (dtMandiri) {
            dtMandiri.column(1).search('').draw();
        }
        swalToast('info', 'Pencarian tugas mandiri direset.');
    });

    $('#searchPengumpulanKelompok').on('input', function () {
        if (dtKelompok) {
            dtKelompok.column(1).search($(this).val()).draw();
        }
    });

    $('#btnResetPengumpulanKelompok').on('click', function () {
        $('#searchPengumpulanKelompok').val('');
        if (dtKelompok) {
            dtKelompok.column(1).search('').draw();
        }
        swalToast('info', 'Pencarian tugas kelompok direset.');
    });

    $('#searchPengumpulanTim').on('input', function () {
        if (dtTim) {
            dtTim.column(1).search($(this).val()).draw();
        }
    });

    $('#btnResetPengumpulanTim').on('click', function () {
        $('#searchPengumpulanTim').val('');
        if (dtTim) {
            dtTim.column(1).search('').draw();
        }
        swalToast('info', 'Pencarian tim tugas direset.');
    });

    switchTab((window.MTUGAS_PENGUMPULAN && window.MTUGAS_PENGUMPULAN.activeTab) || 'mandiri');
});
