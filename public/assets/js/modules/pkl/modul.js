/**
 * SIMMAG ODC — Modul PKL JS
 * public/assets/js/modules/pkl/modul.js
 *
 * Digunakan oleh dua halaman:
 *   1. dashboard_pkl/modul/index.php    → card search + count badge
 *   2. dashboard_pkl/modul/kategori.php → DataTable modul
 *
 * Dependency: jQuery 3.x, DataTables 1.13.x + Responsive 2.5.x, SweetAlert2
 */

$(document).ready(function () {

    /* ══════════════════════════════════════════════════════════ */
    /* 1. INDEX PAGE — CARD SEARCH & COUNT BADGE                 */
    /* ══════════════════════════════════════════════════════════ */

    var $searchKategori = $('#pklModulSearchKategori');

    if ($searchKategori.length) {
        var $cardList = $('#pklModulCardList');
        var $cards = $cardList.find('.pkl-kat-card');
        var $emptySearch = $('#pklModulEmptySearch');
        var $countNum = $('#pklModulKategoriCountNum');
        var totalKategori = $cards.length;

        // ── Search live ──────────────────────────────────────────
        $searchKategori.on('input keyup', function () {
            var keyword = $(this).val().toLowerCase().trim();
            var visible = 0;

            $cards.each(function () {
                // data-nama berisi nama kategori lowercase (di-set di PHP)
                var nama = $(this).data('nama') || '';
                var match = !keyword || nama.indexOf(keyword) !== -1;
                $(this).toggle(match);
                if (match) visible++;
            });

            // Update count badge
            $countNum.text(visible);

            // Tampilkan empty search state jika tidak ada hasil
            var isEmpty = visible === 0 && keyword !== '';
            $emptySearch.toggle(isEmpty);
            $cardList.toggle(!isEmpty || keyword === '');

            // Jika keyword kosong, tampilkan semua
            if (!keyword) {
                $cards.show();
                $countNum.text(totalKategori);
                $emptySearch.hide();
                $cardList.show();
            }
        });

        $('#pklModulResetKategori').on('click', function () {
            $('#pklModulSearchKategori').val('').trigger('input'); // trigger input agar count badge ikut terupdate

            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'info',
                title: 'Pencarian direset',
                showConfirmButton: false,
                timer: 1800,
                timerProgressBar: true,
            });
        });
    }

    /* ══════════════════════════════════════════════════════════ */
    /* 2. KATEGORI PAGE — DATATABLES                             */
    /* ══════════════════════════════════════════════════════════ */

    if ($('#tabelModulPkl').length) {
        var tableModul = $('#tabelModulPkl').DataTable({
            responsive: true,
            autoWidth: false,
            pageLength: 10,
            lengthMenu: [10, 25, 50, 100],
            order: [[1, 'asc']],

            // 'f' dihapus — pakai custom search #pklModulSearchModul
            dom: 'lrtip',

            columnDefs: [
                // No — selalu tampil, tidak bisa sort/search
                { targets: 0, orderable: false, searchable: false, className: 'text-center' },
                // Nama Modul — selalu tampil
                { targets: 1 },
                // Modul (link/file) — masuk child row di mobile (< 768px)
                { targets: 2, className: 'min-tablet-p' },
                // Tanggal Ditambah — masuk child row di mobile
                { targets: 3, className: 'min-tablet-p' },
                // Tanggal Diubah — masuk child row di mobile
                { targets: 4, className: 'min-tablet-p' },
            ],

            language: {
                lengthMenu: 'Tampilkan _MENU_ data',
                zeroRecords: 'Tidak ada modul yang cocok',
                emptyTable: 'Belum ada modul dalam kategori ini',
                info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ modul',
                infoEmpty: 'Menampilkan 0 sampai 0 dari 0 modul',
                infoFiltered: '(difilter dari _MAX_ total modul)',
                paginate: { previous: 'Sebelumnya', next: 'Selanjutnya' },
            },

            // Renumber kolom No setiap draw (sort, filter, paginate)
            drawCallback: function () {
                var api = this.api();
                var start = api.page.info().start;
                api.rows({ page: 'current' }).every(function (rowIdx, tl, rowLoop) {
                    $(this.node()).find('.dt-no-col').text(start + rowLoop + 1);
                });
            },
        });

        // ── Custom Search → DataTables global search ─────────────
        $('#pklModulSearchModul').on('keyup input', function () {
            tableModul.column(1).search($(this).val()).draw();
        });

        // ── Tombol Reset ─────────────────────────────────────────
        $('#pklModulResetModul').on('click', function () {
            $('#pklModulSearchModul').val('');
            tableModul.column(1).search('').draw();

            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'info',
                title: 'Pencarian direset',
                showConfirmButton: false,
                timer: 1800,
                timerProgressBar: true,
            });
        });

    }

});
