/**
 * SIMMAG ODC — Pilih Sasaran Tugas (Step 2) JS
 * public/assets/js/modules/admin/sasaran_tugas.js
 */

$(document).ready(function () {

    // ── 1. Validasi Data Step 1 ──────────────────────────────────
    var rawKetentuan = sessionStorage.getItem('tugasFormData');
    if (!rawKetentuan) {
        Swal.fire({
            icon: 'error', title: 'Akses Ditolak',
            text: 'Data ketentuan tugas tidak ditemukan. Silakan isi kembali.',
            confirmButtonColor: 'var(--primary)'
        }).then(function () { window.location.href = window.BASE_URL_API.replace('/api', '') + '/tugas/tambah'; });
        return;
    }
    var dataKetentuan;
    try {
        dataKetentuan = JSON.parse(rawKetentuan);
    } catch (err) {
        Swal.fire({
            icon: 'error', title: 'Data Tidak Valid',
            text: 'Data ketentuan tugas rusak atau tidak lengkap. Silakan isi ulang.',
            confirmButtonColor: 'var(--primary)'
        }).then(function () {
            sessionStorage.removeItem('tugasFormData');
            window.location.href = window.BASE_URL_API.replace('/api', '') + '/tugas/tambah';
        });
        return;
    }
    var kategoriMode = dataKetentuan.kategori_mode || 'individu'; // 'individu' | 'kelompok'
    var state = {
        activeTab: (kategoriMode === 'kelompok' ? 'kelompok' : 'mandiri'),
        mandiriRows: [],
        selectedIds: [],
        selectedAnggotaIds: [],
        timRows: []
    };

    function updateCounter() { $('#totalTerpilih').text(state.selectedIds.length); }

    function pushSelected(list, id, checked) {
        id = parseInt(id, 10);
        if (!id) return;

        var idx = list.indexOf(id);
        if (checked && idx === -1) {
            list.push(id);
        }
        if (!checked && idx !== -1) {
            list.splice(idx, 1);
        }
    }

    function syncMasterCheckbox(masterSelector, itemSelector) {
        var $items = $(itemSelector).filter(':visible');
        var allChecked = $items.length > 0 && $items.filter(':checked').length === $items.length;
        $(masterSelector).prop('checked', allChecked);
    }

    // ── 2. Auto-hide tab Mandiri jika mode Kelompok ──────────────
    if (kategoriMode === 'kelompok') {
        $('#tabBtnMandiri').prop('disabled', true).hide();
        switchTab('tab-kelompok', 'kelompok', false);
    } else {
        switchTab('tab-mandiri', 'mandiri', false);
    }

    // ── 3. State ─────────────────────────────────────────────────
    // ── 4. Tab Switching ─────────────────────────────────────────
    $('.mpkl-tab-btn').on('click', function () {
        var targetId = $(this).data('target');
        var tipe = targetId.replace('tab-', '');

        if (kategoriMode === 'individu' && (tipe === 'kelompok' || tipe === 'tim')) {
            Swal.fire({
                icon: 'info', title: 'Perhatian',
                text: tipe === 'tim'
                    ? 'Kategori ini untuk Individu. Tugas ke tim tugas akan membuat semua anggota menerima tugas individu.'
                    : 'Kategori ini untuk Individu. Tugas ke kelompok akan membuat semua anggota menerima tugas individu.',
                toast: true, position: 'top-end', timer: 3500, showConfirmButton: false
            });
        } else if (kategoriMode === 'kelompok' && tipe === 'mandiri') {
            Swal.fire({
                icon: 'warning', title: 'Tidak Disarankan',
                text: 'Kategori ini adalah tugas Kelompok. Sebaiknya pilih tab Kelompok.',
                confirmButtonColor: 'var(--primary)'
            });
        }

        switchTab(targetId, tipe, true);
    });

    function switchTab(targetId, tipe, reset) {
        $('.mpkl-tab-btn').removeClass('active');
        $('[data-target="' + targetId + '"]').addClass('active');
        $('.mpkl-tab-content').removeClass('active');
        $('#' + targetId).addClass('active');
        state.activeTab = tipe;
        if (reset) {
            state.selectedIds = [];
            $('.check-item, .check-mandiri, .check-mandiri-mobile, .check-tim, .check-tim-mobile, #checkAllMandiri, #checkAllMandiriMobile, #checkAllKelompok, #checkAllTim, #checkAllTimMobile').prop('checked', false);
            $('.mtugas-mobile-detail-panel, .mtugas-mobile-detail-row').removeClass('is-open').hide();
            $('.mtugas-row-toggle').removeClass('is-open').attr('aria-expanded', 'false').find('i').removeClass('fa-chevron-up').addClass('fa-chevron-down');
        }
        updateCounter();
        updateMandiriMasterCheckboxes();
        syncMasterCheckbox('#checkAllKelompok', '.check-kelompok');
        updateTimMasterCheckboxes();
    }

    // ── 5. Load Data AJAX ────────────────────────────────────────

    // Tab Mandiri — PKL aktif
    function renderBodyState(selector, colspan, message) {
        $(selector).html('<tr><td colspan="' + colspan + '" class="mtugas-empty-cell">' + esc(message) + '</td></tr>');
    }

    function ajaxErrorMessage(xhr, fallback) {
        return xhr.responseJSON && xhr.responseJSON.message
            ? xhr.responseJSON.message
            : fallback;
    }

    function normalizeSasaranLabel(value) {
        var raw = String(value || '').trim();
        if (!raw || raw.toLowerCase() === 'mandiri') {
            return '-';
        }

        return raw;
    }

    function updateMandiriMasterCheckboxes() {
        syncMasterCheckbox('#checkAllMandiri', '.check-mandiri');
        syncMasterCheckbox('#checkAllMandiriMobile', '.check-mandiri-mobile');
    }

    function updateTimMasterCheckboxes() {
        syncMasterCheckbox('#checkAllTim', '.check-tim');
        syncMasterCheckbox('#checkAllTimMobile', '.check-tim-mobile');
    }

    function syncCheckboxMirror(selector, id, checked) {
        $(selector + '[value="' + id + '"]').prop('checked', checked);
    }

    function renderMandiriTable(rows) {
        var keyword = ($('#cariMandiri').val() || '').toLowerCase();
        var filtered = rows.filter(function (pkl) {
            return !keyword || String(pkl.nama_lengkap || '').toLowerCase().includes(keyword);
        });
        var desktopHtml = '';
        var mobileHtml = '';

        if (!filtered.length) {
            desktopHtml = '<tr><td colspan="4" class="mtugas-empty-cell">' +
                (rows.length ? 'Tidak ada PKL yang cocok dengan pencarian.' : 'Tidak ada PKL aktif.') +
                '</td></tr>';
            mobileHtml = '<div class="mtugas-mobile-list-empty">' +
                (rows.length ? 'Tidak ada PKL yang cocok dengan pencarian.' : 'Tidak ada PKL aktif.') +
                '</div>';
        } else {
            filtered.forEach(function (pkl) {
                var checked = state.selectedIds.indexOf(parseInt(pkl.id_pkl, 10)) > -1;
                var detailId = 'mandiri-detail-' + pkl.id_pkl;
                var instansi = esc(normalizeSasaranLabel(pkl.nama_instansi));
                var kelompok = esc(normalizeSasaranLabel(pkl.nama_kelompok));
                var checkedAttr = checked ? ' checked' : '';

                desktopHtml += '<tr class="row-mandiri">' +
                    '<td><input type="checkbox" class="check-mandiri" value="' + pkl.id_pkl + '"' + checkedAttr + '></td>' +
                    '<td class="td-nama"><strong>' + esc(pkl.nama_lengkap) + '</strong></td>' +
                    '<td>' + instansi + '</td>' +
                    '<td>' + kelompok + '</td>' +
                    '</tr>';

                mobileHtml += '<div class="mtugas-mobile-item">' +
                    '<div class="mtugas-mobile-item-main">' +
                    '<label class="mtugas-mobile-item-check">' +
                    '<input type="checkbox" class="check-mandiri-mobile" value="' + pkl.id_pkl + '"' + checkedAttr + '>' +
                    '</label>' +
                    '<div class="mtugas-mobile-item-name"><strong>' + esc(pkl.nama_lengkap) + '</strong></div>' +
                    '<button type="button" class="mtugas-row-toggle mtugas-mobile-item-toggle" data-detail-id="' + detailId + '" aria-expanded="false" aria-label="Lihat detail sasaran">' +
                    '<i class="fas fa-chevron-down"></i></button>' +
                    '</div>' +
                    '<div class="mtugas-mobile-detail-panel" id="' + detailId + '">' +
                    '<div class="mtugas-mobile-detail-grid">' +
                    '<div class="mtugas-mobile-detail-item"><span class="mtugas-mobile-detail-label">Instansi</span><strong>' + instansi + '</strong></div>' +
                    '<div class="mtugas-mobile-detail-item"><span class="mtugas-mobile-detail-label">Kelompok</span><strong>' + kelompok + '</strong></div>' +
                    '</div></div></div>';
            });
        }

        $('#tbodyMandiri').html(desktopHtml);
        $('#mobileMandiriList').html(mobileHtml);
        updateMandiriMasterCheckboxes();
    }

    function loadMandiriList() {
        $.ajax({
            url: window.BASE_URL_API + '/pkl-aktif',
            method: 'GET',
            success: function (res) {
                state.mandiriRows = res.data || [];
                renderMandiriTable(state.mandiriRows);
            },
            error: function (xhr) {
                var message = ajaxErrorMessage(xhr, 'Gagal memuat data PKL aktif.');
                renderBodyState('#tbodyMandiri', 4, message);
                $('#mobileMandiriList').html('<div class="mtugas-mobile-list-empty">' + esc(message) + '</div>');
            }
        });
    }

    loadMandiriList();

    // Tab Kelompok — Kelompok aktif
    function loadKelompokList() {
        $.ajax({
            url: window.BASE_URL_API + '/kelompok-aktif',
            method: 'GET',
            success: function (res) {
                if (!res.data || !res.data.length) {
                    renderBodyState('#tbodyKelompok', 3, 'Tidak ada kelompok instansi aktif.');
                    return;
                }

                var html = '';
                res.data.forEach(function (kel) {
                    var checked = state.selectedIds.indexOf(parseInt(kel.id_kelompok, 10)) > -1;
                    html += '<tr class="row-kelompok">' +
                        '<td><input type="checkbox" class="check-item check-kelompok" value="' + kel.id_kelompok + '"' + (checked ? ' checked' : '') + '></td>' +
                        '<td class="td-nama"><strong>' + esc(kel.nama_kelompok) + '</strong></td>' +
                        '<td>' + esc(kel.nama_instansi || 'Mandiri') + '</td>' +
                        '</tr>';
                });
                $('#tbodyKelompok').html(html);
                syncMasterCheckbox('#checkAllKelompok', '.check-kelompok');
            },
            error: function (xhr) {
                renderBodyState('#tbodyKelompok', 3, ajaxErrorMessage(xhr, 'Gagal memuat data kelompok aktif.'));
            }
        });
    }

    loadKelompokList();

    // Tab Tim — load tim list
    function loadTimList() {
        $.ajax({
            url: window.BASE_URL_API + '/tim-tugas',
            method: 'GET',
            success: function (res) {
                state.timRows = res.data || [];
                renderTimTable(state.timRows);
            },
            error: function (xhr) {
                state.timRows = [];
                $('#tbodyTimTugas').html(
                    '<tr class="tim-empty-row"><td colspan="6">' +
                    esc(ajaxErrorMessage(xhr, 'Gagal memuat data tim tugas.')) +
                    '</td></tr>'
                );
                $('#mobileTimList').html('<div class="mtugas-mobile-list-empty">' +
                    esc(ajaxErrorMessage(xhr, 'Gagal memuat data tim tugas.')) +
                    '</div>');
                updateTimMasterCheckboxes();
            }
        });
    }

    loadTimList();

    function renderTimTable(rows) {
        var keyword = $('#cariNamaTim').val().toLowerCase();
        var desktopHtml = '';
        var mobileHtml = '';
        var filtered = rows.filter(function (t) {
            return !keyword || t.nama_tim.toLowerCase().includes(keyword);
        });

        if (!filtered.length) {
            desktopHtml = '<tr class="tim-empty-row"><td colspan="6">' +
                (rows.length
                    ? 'Tidak ada tim tugas yang cocok dengan pencarian.'
                    : 'Belum ada tim tugas. Buat tim baru di atas.') +
                '</td></tr>';
            mobileHtml = '<div class="mtugas-mobile-list-empty">' +
                (rows.length
                    ? 'Tidak ada tim tugas yang cocok dengan pencarian.'
                    : 'Belum ada tim tugas. Buat tim baru di atas.') +
                '</div>';
        } else {
            filtered.forEach(function (tim, i) {
                var checked = state.selectedIds.indexOf(parseInt(tim.id_tim)) > -1;
                var detailId = 'tim-detail-' + tim.id_tim;
                var jumlahAnggota = (tim.jumlah_anggota || 0) + ' orang';
                var tanggalDibuat = tglIndo(tim.created_at);
                var dipakaiDi = (tim.jumlah_tugas || 0) + ' tugas';
                var checkedAttr = checked ? ' checked' : '';

                desktopHtml += '<tr class="row-tim">' +
                    '<td><input type="checkbox" class="check-tim" value="' + tim.id_tim + '"' + checkedAttr + '></td>' +
                    '<td>' + (i + 1) + '</td>' +
                    '<td><strong>' + esc(tim.nama_tim) + '</strong></td>' +
                    '<td>' + jumlahAnggota + '</td>' +
                    '<td>' + tanggalDibuat + '</td>' +
                    '<td>' + dipakaiDi + '</td>' +
                    '</tr>';

                mobileHtml += '<div class="mtugas-mobile-item mtugas-mobile-item-tim">' +
                    '<div class="mtugas-mobile-item-main">' +
                    '<label class="mtugas-mobile-item-check">' +
                    '<input type="checkbox" class="check-tim-mobile" value="' + tim.id_tim + '"' + checkedAttr + '>' +
                    '</label>' +
                    '<div class="mtugas-mobile-item-no">' + (i + 1) + '</div>' +
                    '<div class="mtugas-mobile-item-name"><strong>' + esc(tim.nama_tim) + '</strong></div>' +
                    '<button type="button" class="mtugas-row-toggle mtugas-mobile-item-toggle" data-detail-id="' + detailId + '" aria-expanded="false" aria-label="Lihat detail tim">' +
                    '<i class="fas fa-chevron-down"></i></button>' +
                    '</div>' +
                    '<div class="mtugas-mobile-detail-panel" id="' + detailId + '">' +
                    '<div class="mtugas-mobile-detail-grid">' +
                    (tim.deskripsi ? '<div class="mtugas-mobile-detail-item"><span class="mtugas-mobile-detail-label">Deskripsi</span><strong>' + esc(tim.deskripsi) + '</strong></div>' : '') +
                    '<div class="mtugas-mobile-detail-item"><span class="mtugas-mobile-detail-label">Jumlah Anggota</span><strong>' + jumlahAnggota + '</strong></div>' +
                    '<div class="mtugas-mobile-detail-item"><span class="mtugas-mobile-detail-label">Tgl Dibuat</span><strong>' + tanggalDibuat + '</strong></div>' +
                    '<div class="mtugas-mobile-detail-item"><span class="mtugas-mobile-detail-label">Dipakai di</span><strong>' + dipakaiDi + '</strong></div>' +
                    '</div></div></div>';
            });
        }
        $('#tbodyTimTugas').html(desktopHtml);
        $('#mobileTimList').html(mobileHtml);
        updateTimMasterCheckboxes();
    }

    // ── 6. Checkbox Logic ────────────────────────────────────────

    // Check All Mandiri
    $('#checkAllMandiri').on('change', function () {
        var checked = this.checked;
        $('#checkAllMandiriMobile').prop('checked', checked);
        $('.check-mandiri:visible').each(function () {
            $(this).prop('checked', checked);
            pushSelected(state.selectedIds, $(this).val(), checked);
            syncCheckboxMirror('.check-mandiri-mobile', $(this).val(), checked);
        });
        updateCounter();
        updateMandiriMasterCheckboxes();
    });

    $('#checkAllMandiriMobile').on('change', function () {
        var checked = this.checked;
        $('#checkAllMandiri').prop('checked', checked);
        $('.check-mandiri-mobile:visible').each(function () {
            $(this).prop('checked', checked);
            pushSelected(state.selectedIds, $(this).val(), checked);
            syncCheckboxMirror('.check-mandiri', $(this).val(), checked);
        });
        updateCounter();
        updateMandiriMasterCheckboxes();
    });

    // Check All Kelompok
    $('#checkAllKelompok').on('change', function () {
        $('.check-kelompok:visible').prop('checked', this.checked);
        recalculate('.check-kelompok');
        syncMasterCheckbox('#checkAllKelompok', '.check-kelompok');
    });

    // Check All Tim
    $('#checkAllTim').on('change', function () {
        var checked = $('#checkAllTim').is(':checked');
        $('#checkAllTimMobile').prop('checked', checked);
        $('.check-tim:visible').each(function () {
            $(this).prop('checked', checked);
            pushSelected(state.selectedIds, $(this).val(), checked);
            syncCheckboxMirror('.check-tim-mobile', $(this).val(), checked);
        });
        updateCounter();
        updateTimMasterCheckboxes();
    });

    $('#checkAllTimMobile').on('change', function () {
        var checked = $('#checkAllTimMobile').is(':checked');
        $('#checkAllTim').prop('checked', checked);
        $('.check-tim-mobile:visible').each(function () {
            $(this).prop('checked', checked);
            pushSelected(state.selectedIds, $(this).val(), checked);
            syncCheckboxMirror('.check-tim', $(this).val(), checked);
        });
        updateCounter();
        updateTimMasterCheckboxes();
    });

    $(document).on('change', '.check-mandiri, .check-mandiri-mobile', function () {
        pushSelected(state.selectedIds, $(this).val(), this.checked);
        syncCheckboxMirror('.check-mandiri', $(this).val(), this.checked);
        syncCheckboxMirror('.check-mandiri-mobile', $(this).val(), this.checked);
        updateCounter();
        updateMandiriMasterCheckboxes();
    });

    $(document).on('change', '.check-kelompok', function () {
        recalculate('.check-kelompok');
        syncMasterCheckbox('#checkAllKelompok', '.check-kelompok');
    });

    $(document).on('change', '.check-tim, .check-tim-mobile', function () {
        pushSelected(state.selectedIds, $(this).val(), this.checked);
        syncCheckboxMirror('.check-tim', $(this).val(), this.checked);
        syncCheckboxMirror('.check-tim-mobile', $(this).val(), this.checked);
        updateCounter();
        updateTimMasterCheckboxes();
    });

    function recalculate(sel) {
        state.selectedIds = [];
        $(sel + ':checked').each(function () {
            state.selectedIds.push(parseInt($(this).val(), 10));
        });
        updateCounter();
    }

    // ── 7. Search Filter ─────────────────────────────────────────

    $('#cariMandiri').on('input', function () {
        renderMandiriTable(state.mandiriRows);
    });

    $('#cariKelompok').on('input', function () {
        var kw = $(this).val().toLowerCase();
        $('.row-kelompok').each(function () {
            $(this).toggle($(this).find('.td-nama').text().toLowerCase().includes(kw));
        });
        syncMasterCheckbox('#checkAllKelompok', '.check-kelompok');
    });

    $('#btnResetMandiriSearch').on('click', function () {
        $('#cariMandiri').val('').trigger('input');
        swalToast('success', 'Filter individu berhasil direset.');
    });

    $('#btnResetKelompokSearch').on('click', function () {
        $('#cariKelompok').val('').trigger('input');
        swalToast('success', 'Filter kelompok berhasil direset.');
    });

    // ── 8. Tim Filter ────────────────────────────────────────────

    $('#cariNamaTim').on('input', function () { renderTimTable(state.timRows); });

    $(document).on('click', '.mtugas-row-toggle', function () {
        var detailId = $(this).data('detail-id');
        var $detailRow = $('#' + detailId);
        var isOpen = $detailRow.hasClass('is-open');

        if (isOpen) {
            $detailRow.removeClass('is-open').hide();
        } else {
            $detailRow.addClass('is-open').show();
        }

        $(this)
            .attr('aria-expanded', !isOpen ? 'true' : 'false')
            .toggleClass('is-open', !isOpen)
            .find('i')
            .toggleClass('fa-chevron-down', isOpen)
            .toggleClass('fa-chevron-up', !isOpen);
    });

    $('#btnResetFilterTim').on('click', function () {
        $('#cariNamaTim').val('');
        renderTimTable(state.timRows);
        swalToast('success', 'Filter tim berhasil direset.');
    });

    // ── 9. Buat Tim Baru ─────────────────────────────────────────

    var allPklData = []; // cache PKL aktif untuk tim form

    $('#btnBuatTimBaru').on('click', function () {
        $('#sectionBuatTim').slideDown(200);
        $('html,body').animate({ scrollTop: $('#sectionBuatTim').offset().top - 80 }, 300);

        // Load PKL jika belum
        if (!allPklData.length) {
            $.ajax({
                url: window.BASE_URL_API + '/pkl-aktif-with-kategori', method: 'GET',
                success: function (res) {
                    allPklData = res.data || [];
                    renderAnggotaTable(allPklData);
                },
                error: function (xhr) {
                    $('#tbodyAnggotaCalon').html(
                        '<tr><td colspan="6" class="mtugas-empty-cell">' +
                        esc(ajaxErrorMessage(xhr, 'Gagal memuat anggota aktif.')) +
                        '</td></tr>'
                    );
                }
            });
        } else {
            renderAnggotaTable(allPklData);
        }
    });

    $('#btnBatalBuatTim').on('click', function () {
        $('#sectionBuatTim').slideUp(200);
        resetBuatTim();
    });

    function renderAnggotaTable(data) {
        var kw = $('#cariAnggotaTim').val().toLowerCase();
        var kat = $('#filterKategoriAnggota').val();

        var filtered = data.filter(function (p) {
            var namaOk = !kw || p.nama_lengkap.toLowerCase().includes(kw);
            var katOk = !kat || p.kategori_pkl === kat;
            return namaOk && katOk;
        });

        if (!filtered.length) {
            $('#tbodyAnggotaCalon').html('<tr><td colspan="6" class="mtugas-empty-cell">Tidak ada PKL aktif.</td></tr>');
            updateAnggotaCounter();
            return;
        }

        var html = '';
        filtered.forEach(function (pkl, i) {
            var badge = pkl.kategori_pkl === 'instansi'
                ? '<span class="badge-pkl-instansi">Instansi</span>'
                : '<span class="badge-pkl-mandiri">Mandiri</span>';
            var checked = state.selectedAnggotaIds.indexOf(parseInt(pkl.id_pkl, 10)) > -1;
            var detailId = 'anggota-detail-' + pkl.id_pkl;
            var kelompokLabel = esc(pkl.kelompok_nama || '-');
            html += '<tr class="row-anggota">' +
                '<td><input type="checkbox" class="check-anggota" value="' + pkl.id_pkl + '"' + (checked ? ' checked' : '') + '></td>' +
                '<td class="mtugas-no-cell">' + (i + 1) + '</td>' +
                '<td class="anggota-nama td-nama mtugas-name-cell"><strong>' + esc(pkl.nama_lengkap) + '</strong></td>' +
                '<td class="mtugas-mobile-hide">' + badge + '</td>' +
                '<td class="mtugas-mobile-hide">' + kelompokLabel + '</td>' +
                '<td class="mtugas-expand-cell mtugas-text-center">' +
                '<button type="button" class="mtugas-row-toggle" data-detail-id="' + detailId + '" aria-expanded="false" aria-label="Lihat detail anggota">' +
                '<i class="fas fa-chevron-down"></i></button></td>' +
                '</tr>' +
                '<tr class="mtugas-mobile-detail-row" id="' + detailId + '">' +
                '<td colspan="6"><div class="mtugas-mobile-detail-grid">' +
                '<div class="mtugas-mobile-detail-item"><span class="mtugas-mobile-detail-label">Kategori PKL</span><strong>' + (pkl.kategori_pkl === 'instansi' ? 'Instansi' : 'Mandiri') + '</strong></div>' +
                '<div class="mtugas-mobile-detail-item"><span class="mtugas-mobile-detail-label">Kelompok / Mandiri</span><strong>' + kelompokLabel + '</strong></div>' +
                '</div></td></tr>';
        });
        $('#tbodyAnggotaCalon').html(html);
        updateAnggotaCounter();
    }

    // Filter anggota realtime
    $('#cariAnggotaTim, #filterKategoriAnggota').on('input change', function () {
        renderAnggotaTable(allPklData);
    });

    $('#btnResetAnggotaFilter').on('click', function () {
        $('#cariAnggotaTim').val('');
        $('#filterKategoriAnggota').val('');
        renderAnggotaTable(allPklData);
        swalToast('success', 'Filter anggota berhasil direset.');
    });

    // Check all anggota
    $('#checkAllAnggota').on('change', function () {
        $('.check-anggota:visible').each(function () {
            $(this).prop('checked', $('#checkAllAnggota').is(':checked'));
            pushSelected(state.selectedAnggotaIds, $(this).val(), $('#checkAllAnggota').is(':checked'));
        });
        updateAnggotaCounter();
    });

    $(document).on('change', '.check-anggota', function () {
        pushSelected(state.selectedAnggotaIds, $(this).val(), this.checked);
        updateAnggotaCounter();
    });

    function updateAnggotaCounter() {
        var n = state.selectedAnggotaIds.length;
        $('#anggotaCounter').text(n + ' anggota dipilih');
        syncMasterCheckbox('#checkAllAnggota', '.check-anggota');
    }

    // Simpan Tim
    $('#btnSimpanTim').on('click', function () {
        var namaTim = $.trim($('#inputNamaTim').val());
        if (!namaTim) {
            return Swal.fire({ icon: 'warning', title: 'Perhatian', text: 'Nama tim tidak boleh kosong.', confirmButtonColor: 'var(--primary)' });
        }

        var anggotaIds = state.selectedAnggotaIds.slice();

        if (!anggotaIds.length) {
            return Swal.fire({ icon: 'warning', title: 'Perhatian', text: 'Pilih minimal 1 anggota untuk tim ini.', confirmButtonColor: 'var(--primary)' });
        }

        var $btn = $(this);
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Menyimpan...');

        $.ajax({
            url: window.BASE_URL_API + '/tim-tugas/store',
            method: 'POST',
            data: JSON.stringify({ nama_tim: namaTim, deskripsi: '', anggota_ids: anggotaIds }),
            contentType: 'application/json',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                [document.querySelector('meta[name="csrf-token-name"]').content]:
                    document.querySelector('meta[name="csrf-token-hash"]').content
            },
            success: function (res) {
                if (res.success) {
                    $('#sectionBuatTim').slideUp(200);
                    resetBuatTim();
                    state.timRows = res.data || [];
                    renderTimTable(state.timRows);
                    Swal.fire({
                        toast: true, position: 'top-end', icon: 'success',
                        title: res.message, showConfirmButton: false, timer: 2500, timerProgressBar: true
                    });
                } else {
                    Swal.fire({ icon: 'error', title: 'Gagal', text: res.message, confirmButtonColor: 'var(--primary)' });
                }
            },
            error: function (xhr) {
                var msg = xhr.responseJSON?.message || 'Terjadi kesalahan jaringan.';
                Swal.fire({ icon: 'error', title: 'Gagal', text: msg, confirmButtonColor: 'var(--primary)' });
            },
            complete: function () {
                $btn.prop('disabled', false).html('<i class="fas fa-save"></i> Simpan Tim');
            }
        });
    });

    function resetBuatTim() {
        state.selectedAnggotaIds = [];
        $('#inputNamaTim').val('');
        $('#cariAnggotaTim').val('');
        $('#filterKategoriAnggota').val('');
        $('#checkAllAnggota').prop('checked', false);
        $('#tbodyAnggotaCalon').html('<tr><td colspan="6" class="mtugas-empty-cell">...</td></tr>');
        $('#anggotaCounter').text('0 anggota dipilih');
    }

    // ── 10. Submit Final ─────────────────────────────────────────

    $('#btnSimpanTugasFinal').on('click', function () {
        if (state.selectedIds.length === 0) {
            return Swal.fire({
                icon: 'warning', title: 'Pilih Sasaran',
                text: 'Anda belum memilih siapa yang akan menerima tugas ini.',
                confirmButtonColor: 'var(--primary)'
            });
        }

        // Map JS tab name → server tipe
        var tipeMap = { mandiri: 'individu', kelompok: 'kelompok', tim: 'tim_tugas' };
        var tipe = tipeMap[state.activeTab] || state.activeTab;

        var payload = {
            ketentuan: dataKetentuan,
            sasaran: { tipe: tipe, target_ids: state.selectedIds }
        };

        var $btn = $(this);
        var origHtml = $btn.html();
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Menyimpan...');

        $.ajax({
            url: window.URL_STORE_TUGAS,
            method: 'POST',
            data: JSON.stringify(payload),
            contentType: 'application/json',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                [document.querySelector('meta[name="csrf-token-name"]').content]:
                    document.querySelector('meta[name="csrf-token-hash"]').content
            },
            success: function (res) {
                if (res.success) {
                    sessionStorage.removeItem('tugasFormData');
                    swalToast('success', res.message || 'Tugas berhasil disimpan.');
                    setTimeout(function () {
                        window.location.href = window.URL_REDIRECT;
                    }, 700);
                } else {
                    $btn.prop('disabled', false).html(origHtml);
                    Swal.fire({ icon: 'error', title: 'Gagal', text: res.message, confirmButtonColor: 'var(--primary)' });
                }
            },
            error: function (xhr) {
                $btn.prop('disabled', false).html(origHtml);
                var msg = xhr.responseJSON?.message || 'Terjadi kesalahan jaringan.';
                Swal.fire({ icon: 'error', title: 'Gagal', text: msg, confirmButtonColor: 'var(--primary)' });
            }
        });
    });

    // ── HELPERS ──────────────────────────────────────────────────

    function tglIndo(str) {
        if (!str) return '-';
        var bln = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        var p = str.substring(0, 10).split('-');
        return parseInt(p[2]) + ' ' + bln[parseInt(p[1])] + ' ' + p[0];
    }

    function swalToast(icon, msg) {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: icon,
            title: msg,
            showConfirmButton: false,
            timer: 2200,
            timerProgressBar: true
        });
    }

    function esc(str) {
        return $('<div>').text(str == null ? '' : String(str)).html();
    }
});
