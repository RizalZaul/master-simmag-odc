/**
 * SIMMAG ODC — Form Biodata PKL Publik
 * public/assets/js/modules/biodata_pkl.js
 *
 * Dependency: jQuery 3.x, flatpickr, Select2 4.x, SweetAlert2
 *
 * FIX: flatpickr locale — tambah weekdays.longhand (wajib ada, tanpa ini
 *      flatpickr crash dengan TypeError: Cannot read properties of undefined
 *      (reading 'join') karena flatpickr memanggil .join() pada array yang
 *      tidak ada.
 */

(function ($) {
    'use strict';

    var cfg = window.BiodataPKL || {};

    // ── State ───────────────────────────────────────────────────────

    var state = {
        step: 1,
        kategori: 'mandiri',
        jumlahAnggota: 1,
        tglMulai: null,
        tglAkhir: null,
        fpMulai: null,
        fpAkhir: null,
        instansiMode: 'existing', // 'existing' | 'new'
        instansiData: {},
        anggotaData: [],
        otpVerified: false,
        otpCountdownTimer: null,
    };

    // ── FIX: Locale flatpickr lengkap ───────────────────────────────
    // weekdays.longhand WAJIB ada — flatpickr memanggil .join() pada
    // kedua array (longhand & shorthand) untuk render kalender.

    var fpLocale = {
        months: {
            longhand: ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
            shorthand: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
        },
        weekdays: {
            longhand: ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'],
            shorthand: ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'],
        },
    };

    // ── CSRF ────────────────────────────────────────────────────────

    function getCsrfBody(extra) {
        var data = {};
        data[cfg.csrfName] = cfg.csrfHash;
        return $.extend(data, extra || {});
    }

    function refreshCsrf(res) {
        if (res && res.csrf_hash) {
            cfg.csrfHash = res.csrf_hash;
            $('meta[name="csrf-token-hash"]').attr('content', res.csrf_hash);
        }
    }

    // ── Toast & Alert ───────────────────────────────────────────────

    function toast(icon, title, timer) {
        Swal.fire({
            toast: true, position: 'top-end',
            icon: icon, title: title,
            showConfirmButton: false,
            timer: timer || 2200, timerProgressBar: true,
        });
    }

    // ── Step Navigation ─────────────────────────────────────────────

    function goToStep(n) {
        state.step = n;
        $('.biodata-panel').hide();
        $('#panel' + n).show();

        $('.biodata-step').removeClass('active done');
        for (var i = 1; i < n; i++) {
            $('#stepInd' + i).addClass('done');
        }
        $('#stepInd' + n).addClass('active');

        $('html, body').animate({ scrollTop: 0 }, 250);
    }

    // ── Kategori Toggle ─────────────────────────────────────────────

    $('input[name="b_kategori"]').on('change', function () {
        state.kategori = $(this).val();
        var isInstansi = state.kategori === 'instansi';
        $('#bFieldInstansiGroup').toggle(isInstansi);
        if (!isInstansi) {
            state.jumlahAnggota = 1;
        } else {
            state.jumlahAnggota = parseInt($('#bJumlahAnggota').val(), 10) || 2;
        }
    });

    $('#bJumlahAnggota').on('change', function () {
        var val = parseInt($(this).val(), 10);
        if (isNaN(val) || val < 2) { $(this).val(2); val = 2; }
        if (val > 20) { $(this).val(20); val = 20; }
        state.jumlahAnggota = val;
    });

    // ── Flatpickr ───────────────────────────────────────────────────

    function initDatepickers() {
        var minMulai = $('#bTglMulai').data('min');
        var maxMulai = $('#bTglMulai').data('max');

        state.fpMulai = flatpickr('#bTglMulai', {
            dateFormat: 'Y-m-d',
            altInput: true,
            altFormat: 'd M Y',
            minDate: minMulai,
            maxDate: maxMulai,
            allowInput: false,
            locale: fpLocale,
            onChange: function (dates, dateStr) {
                state.tglMulai = dateStr;
                if (state.fpAkhir) {
                    state.fpAkhir.set('minDate', dateStr || null);
                    if (state.tglAkhir && state.tglAkhir <= dateStr) {
                        state.fpAkhir.clear();
                        state.tglAkhir = null;
                    }
                }
            },
        });

        state.fpAkhir = flatpickr('#bTglAkhir', {
            dateFormat: 'Y-m-d',
            altInput: true,
            altFormat: 'd M Y',
            minDate: state.tglMulai || 'today',
            allowInput: false,
            locale: fpLocale,
            onChange: function (dates, dateStr) {
                state.tglAkhir = dateStr;
            },
        });
    }

    // ── Select2: Instansi & Kota ─────────────────────────────────────

    function initSelect2() {
        // Instansi (dengan opsi tambah baru)
        $('#bNamaInstansi').select2({
            placeholder: 'Ketik atau pilih instansi...',
            allowClear: true,
            width: '100%',
            tags: true,
            createTag: function (params) {
                var term = $.trim(params.term);
                if (!term) return null;
                return { id: 'new:' + term, text: term + ' (Tambah Baru)', newTag: true, nama: term };
            },
        }).on('select2:select', function (e) {
            var val = e.params.data.id || '';
            if (String(val).startsWith('new:')) {
                state.instansiMode = 'new';
                state.instansiData.nama = e.params.data.nama || String(val).replace('new:', '');
                $('#bFieldAlamatBaru, #bFieldKotaBaru').show();
            } else if (String(val).startsWith('existing:')) {
                state.instansiMode = 'existing';
                state.instansiData.id = parseInt(String(val).replace('existing:', ''), 10);
                state.instansiData.nama = e.params.data.text;
                $('#bFieldAlamatBaru, #bFieldKotaBaru').hide();
            }
        }).on('select2:clear', function () {
            state.instansiMode = 'existing';
            state.instansiData = {};
            $('#bFieldAlamatBaru, #bFieldKotaBaru').hide();
        });

        // Kota
        $('#bKotaInstansi').select2({
            placeholder: 'Pilih atau ketik kota...',
            allowClear: true,
            tags: true,
            width: '100%',
        });

        // ── Filter Nama Instansi berdasarkan Kategori ────────────────────
        $('#bKategoriInstansi').on('change', function () {
            var selectedKategori = $(this).val(); // Nilainya 'Kuliah' atau 'SMK Sederajat'
            var $selectInstansi = $('#bNamaInstansi');

            // 1. Kosongkan opsi yang ada (kembalikan hanya placeholder)
            $selectInstansi.empty().append('<option value=""></option>');

            // 2. Jika ada kategori yang dipilih, filter data dari variabel global
            if (selectedKategori) {
                var filteredData = cfg.instansiList.filter(function (item) {
                    // Pastikan mencocokkan dengan kategori_label
                    return item.kategori_label === selectedKategori;
                });

                // 3. Masukkan data yang sudah difilter ke dalam combo box
                filteredData.forEach(function (item) {
                    var option = $('<option></option>')
                        .val('existing:' + item.id_instansi)
                        .text(item.nama_instansi)
                        .attr('data-nama', item.nama_instansi);
                    $selectInstansi.append(option);
                });
            }

            // 4. Beri tahu Select2 bahwa opsi telah diperbarui
            $selectInstansi.trigger('change');

            // 5. Sembunyikan form alamat & kota baru jika sebelumnya terbuka
            $('#bFieldAlamatBaru, #bFieldKotaBaru').hide();
            state.instansiMode = 'existing';
            state.instansiData = {};
        });
    }

    // ── Accordion Anggota ────────────────────────────────────────────

    function renderAccordion() {
        var $wrap = $('#biodataAccordion').empty();
        var n = state.kategori === 'mandiri' ? 1 : state.jumlahAnggota;

        for (var i = 0; i < n; i++) {
            var isKetua = (state.kategori === 'instansi' && i === 0);
            var badgeText = state.kategori === 'mandiri'
                ? 'Data Diri'
                : (isKetua
                    ? 'Anggota ' + (i + 1) + ' <span class="badge-ketua">Ketua</span>'
                    : 'Anggota ' + (i + 1));

            var prev = state.anggotaData[i] || {};
            var html = '<div class="biodata-acc-item" data-idx="' + i + '">'
                + '<div class="biodata-acc-header" data-idx="' + i + '">'
                + '  <span class="acc-badge">' + badgeText + '</span>'
                + '  <span class="acc-name-preview" id="accNamePrev' + i + '">'
                + (prev.nama_lengkap ? esc(prev.nama_lengkap) : '<em>Belum diisi</em>') + '</span>'
                + '  <i class="fas fa-chevron-down acc-toggle-icon"></i>'
                + '</div>'
                + '<div class="biodata-acc-body" id="accBody' + i + '">'
                + buildAnggotaFields(i, prev, isKetua)
                + '</div>'
                + '</div>';

            $wrap.append(html);
        }

        // Init flatpickr untuk tanggal lahir — gunakan fpLocale yang sudah benar
        $wrap.find('.biodata-dob-picker').each(function () {
            flatpickr(this, {
                dateFormat: 'Y-m-d',
                altInput: true,
                altFormat: 'd M Y',
                maxDate: 'today',
                allowInput: false,
                locale: fpLocale,
            });
        });

        // Buka accordion pertama by default
        $('#accBody0').show();
        $('[data-idx="0"].biodata-acc-header .acc-toggle-icon').addClass('open');

        // Live name preview
        $wrap.on('input', '.acc-nama-input', function () {
            var idx = $(this).closest('.biodata-acc-item').data('idx');
            $('#accNamePrev' + idx).html($(this).val() || '<em>Belum diisi</em>');
        });
    }

    // ── Radio Uncheck Logic ──────────────────────────────────────────
    $(document).on('click', 'input[type="radio"][data-field="jenis_kelamin"]', function () {
        var previousValue = $(this).attr('previousValue');
        var name = $(this).attr('name');

        if (previousValue === 'checked') {
            $(this).prop('checked', false);
            $(this).attr('previousValue', false);
        } else {
            $('input[name="' + name + '"]').attr('previousValue', false);
            $(this).attr('previousValue', 'checked');
        }
    });

    function buildAnggotaFields(i, prev, isKetua) {
        var emailNote = isKetua
            ? '<span class="biodata-hint"><i class="fas fa-key"></i> Email ketua dipakai untuk verifikasi OTP</span>'
            : '';

        var jurusanHtml = '';
        if (state.kategori !== 'mandiri') {
            jurusanHtml = '<div class="biodata-field">'
                + '<label class="biodata-label"><i class="fas fa-graduation-cap"></i> Jurusan</label>'
                + '<input type="text" class="biodata-input" data-field="jurusan" data-idx="' + i + '"'
                + ' value="' + esc(prev.jurusan || '') + '" placeholder="Jurusan / program studi" maxlength="100">'
                + '</div>';
        }

        return '<div class="biodata-anggota-grid">'

            + '<div class="biodata-field biodata-field-full">'
            + '<label class="biodata-label"><i class="fas fa-user"></i> Nama Lengkap <span class="required-star">*</span></label>'
            + '<input type="text" class="biodata-input acc-nama-input" data-field="nama_lengkap" data-idx="' + i + '"'
            + ' value="' + esc(prev.nama_lengkap || '') + '" placeholder="Nama lengkap sesuai KTP" maxlength="100">'
            + '</div>'

            + '<div class="biodata-field">'
            + '<label class="biodata-label"><i class="fas fa-smile"></i> Nama Panggilan</label>'
            + '<input type="text" class="biodata-input" data-field="nama_panggilan" data-idx="' + i + '"'
            + ' value="' + esc(prev.nama_panggilan || '') + '" placeholder="Nama panggilan" maxlength="50">'
            + '</div>'

            + '<div class="biodata-field">'
            + '<label class="biodata-label"><i class="fas fa-map-pin"></i> Tempat Lahir</label>'
            + '<input type="text" class="biodata-input" data-field="tempat_lahir" data-idx="' + i + '"'
            + ' value="' + esc(prev.tempat_lahir || '') + '" placeholder="Kota tempat lahir" maxlength="100">'
            + '</div>'

            + '<div class="biodata-field">'
            + '<label class="biodata-label"><i class="fas fa-birthday-cake"></i> Tanggal Lahir</label>'
            + '<input type="text" class="biodata-input biodata-dob-picker" data-field="tgl_lahir" data-idx="' + i + '"'
            + ' value="' + esc(prev.tgl_lahir || '') + '" placeholder="Pilih tanggal lahir">'
            + '</div>'

            + '<div class="biodata-field">'
            + '<label class="biodata-label"><i class="fas fa-venus-mars"></i> Jenis Kelamin <span class="required-star">*</span></label>'
            + '<div class="biodata-radio-group biodata-radio-sm">'
            // Tambahkan name="jk_' + i + '"
            + '<label class="biodata-radio-option"><input type="radio" name="jk_' + i + '" data-field="jenis_kelamin" data-idx="' + i + '" value="L"'
            + (prev.jenis_kelamin === 'L' ? ' checked' : '') + '><span class="biodata-radio-custom"></span><i class="fas fa-mars"></i> Laki-laki</label>'
            + '<label class="biodata-radio-option"><input type="radio" name="jk_' + i + '" data-field="jenis_kelamin" data-idx="' + i + '" value="P"'
            + (prev.jenis_kelamin === 'P' ? ' checked' : '') + '><span class="biodata-radio-custom"></span><i class="fas fa-venus"></i> Perempuan</label>'
            + '</div></div>'

            + jurusanHtml

            + '<div class="biodata-field">'
            + '<label class="biodata-label"><i class="fab fa-whatsapp"></i> No. WhatsApp <span class="required-star">*</span></label>'
            + '<input type="text" class="biodata-input" data-field="no_wa" data-idx="' + i + '"'
            + ' value="' + esc(prev.no_wa || '') + '" placeholder="08xxxxxxxxxx" maxlength="20">'
            + '</div>'

            + '<div class="biodata-field">'
            + '<label class="biodata-label"><i class="fas fa-envelope"></i> Email <span class="required-star">*</span></label>'
            + '<input type="email" class="biodata-input" data-field="email" data-idx="' + i + '"'
            + ' value="' + esc(prev.email || '') + '" placeholder="email@contoh.com" maxlength="100">'
            + emailNote
            + '</div>'

            + '<div class="biodata-field biodata-field-full">'
            + '<label class="biodata-label"><i class="fas fa-map-marker-alt"></i> Alamat</label>'
            + '<textarea class="biodata-textarea" data-field="alamat" data-idx="' + i + '"'
            + ' placeholder="Alamat lengkap tinggal saat ini" rows="3" maxlength="300">'
            + esc(prev.alamat || '') + '</textarea>'
            + '</div>'

            + '</div>'; // end grid
    }

    // ── Accordion Toggle ─────────────────────────────────────────────

    $(document).on('click', '.biodata-acc-header', function () {
        var idx = $(this).data('idx');
        var $body = $('#accBody' + idx);
        var $icon = $(this).find('.acc-toggle-icon');
        var isOpen = $body.is(':visible');

        // Tutup semua
        $('.biodata-acc-body').slideUp(180);
        $('.acc-toggle-icon').removeClass('open');

        if (!isOpen) {
            $body.slideDown(200);
            $icon.addClass('open');
        }
    });

    // ── Collect Anggota Data from Inputs ────────────────────────────

    function collectAnggotaData() {
        var n = state.kategori === 'mandiri' ? 1 : state.jumlahAnggota;
        var result = [];

        for (var i = 0; i < n; i++) {
            var obj = {};
            $('[data-idx="' + i + '"]').each(function () {
                var field = $(this).data('field');
                if (!field) return;
                var el = $(this);
                if (el.is(':radio')) {
                    if (el.is(':checked')) obj[field] = el.val();
                } else {
                    obj[field] = el.val();
                }
            });
            result.push(obj);
        }

        state.anggotaData = result;
        return result;
    }

    // ── Validation ───────────────────────────────────────────────────

    function validateStep1() {
        if (!state.tglMulai) { return 'Tanggal mulai PKL wajib diisi.'; }
        if (!state.tglAkhir) { return 'Tanggal akhir PKL wajib diisi.'; }
        if (state.tglAkhir <= state.tglMulai) { return 'Tanggal akhir harus setelah tanggal mulai.'; }

        if (state.kategori === 'instansi') {
            if (!$('#bKategoriInstansi').val()) return 'Kategori instansi wajib dipilih.';
            if (!$('#bNamaInstansi').val()) return 'Nama instansi wajib diisi.';
            if (state.instansiMode === 'new') {
                if (!$.trim($('#bAlamatInstansi').val())) return 'Alamat instansi baru wajib diisi.';
                if (!$('#bKotaInstansi').val()) return 'Kota instansi baru wajib dipilih.';
            }
            if (!$.trim($('#bNamaPembimbing').val())) return 'Nama pembimbing wajib diisi.';
            if (!$.trim($('#bWaPembimbing').val())) return 'No WA pembimbing wajib diisi.';
            var jumlah = parseInt($('#bJumlahAnggota').val(), 10);
            if (isNaN(jumlah) || jumlah < 2) return 'Jumlah anggota minimal 2 (termasuk ketua).';
            if (!$.trim($('#bNamaKelompok').val())) return 'Nama kelompok wajib diisi.';
        }
        return null;
    }

    function validateStep2(anggotaArr) {
        for (var i = 0; i < anggotaArr.length; i++) {
            var a = anggotaArr[i];
            var no = i + 1;
            var who = state.kategori === 'mandiri' ? 'Data Diri' : 'Anggota ' + no;

            if (!$.trim(a.nama_lengkap || '')) return who + ': Nama lengkap wajib diisi.';
            if (!a.jenis_kelamin) return who + ': Jenis kelamin wajib dipilih.';
            if (!$.trim(a.no_wa || '')) return who + ': No. WhatsApp wajib diisi.';
            if (!$.trim(a.email || '') || !isValidEmail(a.email)) return who + ': Email tidak valid.';
        }

        // Cek duplikat email antar anggota
        var emails = anggotaArr.map(function (a) { return (a.email || '').toLowerCase().trim(); });
        var unique = [...new Set(emails)];
        if (unique.length < emails.length) {
            return 'Setiap anggota harus menggunakan email yang berbeda.';
        }
        return null;
    }

    function isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test($.trim(email || ''));
    }

    // ── Check Email Unik via AJAX ────────────────────────────────────

    function checkEmailsUnique(anggotaArr, callback) {
        var emails = anggotaArr.map(function (a) { return (a.email || '').toLowerCase().trim(); });

        $.ajax({
            url: cfg.urlCheckEmail,
            method: 'POST',
            data: getCsrfBody({ emails: emails }),
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function (res) {
                refreshCsrf(res);
                var conflicts = [];
                if (res.success && res.data) {
                    $.each(res.data, function (email, exists) {
                        if (exists) conflicts.push(email);
                    });
                }
                callback(conflicts);
            },
            error: function () { callback([]); },
        });
    }

    // ── Build Payload ────────────────────────────────────────────────

    function buildPayload() {
        var p = {
            kategori: state.kategori,
            tgl_mulai: state.tglMulai,
            tgl_akhir: state.tglAkhir,
            anggota: state.anggotaData,
        };

        if (state.kategori === 'instansi') {
            p.nama_kelompok = $.trim($('#bNamaKelompok').val());
            p.nama_pembimbing = $.trim($('#bNamaPembimbing').val());
            p.no_wa_pembimbing = $.trim($('#bWaPembimbing').val());

            if (state.instansiMode === 'new') {
                p.instansi = {
                    is_new: true,
                    nama: state.instansiData.nama || '',
                    kategori_label: $('#bKategoriInstansi').val(),
                    alamat: $.trim($('#bAlamatInstansi').val()),
                    kota: $('#bKotaInstansi').val(),
                };
            } else {
                p.instansi = {
                    is_new: false,
                    id: state.instansiData.id || 0,
                    nama: state.instansiData.nama || '',
                };
            }
        }

        return p;
    }

    // ── Render Konfirmasi ────────────────────────────────────────────

    function renderKonfirmasi(payload) {
        var html = '';

        html += '<div class="konfirmasi-section">'
            + '<div class="konfirmasi-section-title"><i class="fas fa-users"></i> Data Kelompok</div>'
            + '<div class="konfirmasi-grid">'
            + konfRow('Kategori PKL', payload.kategori === 'instansi' ? 'Instansi' : 'Mandiri')
            + konfRow('Periode', fmtDate(payload.tgl_mulai) + ' s/d ' + fmtDate(payload.tgl_akhir));

        if (payload.kategori === 'instansi') {
            html += konfRow('Instansi', esc(payload.instansi.nama))
                + konfRow('Nama Kelompok', esc(payload.nama_kelompok))
                + konfRow('Pembimbing', esc(payload.nama_pembimbing))
                + konfRow('WA Pembimbing', esc(payload.no_wa_pembimbing));
        }
        html += '</div></div>';

        payload.anggota.forEach(function (a, i) {
            var isKetua = payload.kategori === 'instansi' && i === 0;
            var roleText = payload.kategori === 'mandiri' ? ''
                : (isKetua
                    ? ' <span class="badge-ketua">Ketua</span>'
                    : ' <span class="badge-anggota">Anggota</span>');
            var title = payload.kategori === 'mandiri' ? 'Data Diri' : ('Anggota ' + (i + 1) + roleText);

            var jurusanRow = payload.kategori !== 'mandiri'
                ? konfRow('Jurusan', esc(a.jurusan) || '-')
                : '';

            html += '<div class="konfirmasi-section">'
                + '<div class="konfirmasi-section-title"><i class="fas fa-user"></i> ' + title + '</div>'
                + '<div class="konfirmasi-grid">'
                + konfRow('Nama Lengkap', esc(a.nama_lengkap))
                + konfRow('Nama Panggilan', esc(a.nama_panggilan) || '-')
                + konfRow('Tempat, Tgl Lahir', (esc(a.tempat_lahir) || '-') + ', ' + (fmtDate(a.tgl_lahir) || '-'))
                + konfRow('Jenis Kelamin', a.jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan')
                + jurusanRow
                + konfRow('No. WhatsApp', esc(a.no_wa))
                + konfRow('Email', esc(a.email))
                + konfRow('Alamat', esc(a.alamat) || '-')
                + '</div></div>';
        });

        $('#konfirmasiContent').html(html);
    }

    function konfRow(label, value) {
        return '<div class="konfirmasi-row">'
            + '<span class="konfirmasi-label">' + label + '</span>'
            + '<span class="konfirmasi-value">' + value + '</span>'
            + '</div>';
    }

    function fmtDate(str) {
        if (!str) return '-';
        var bln = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        var parts = String(str).split('-');
        if (parts.length < 3) return str;
        return parts[2] + ' ' + (bln[parseInt(parts[1], 10)] || parts[1]) + ' ' + parts[0];
    }

    function esc(str) {
        return $('<div>').text(str == null ? '' : String(str)).html();
    }

    // ── OTP Flow ─────────────────────────────────────────────────────

    function getKetuaEmail() {
        return ((state.anggotaData[0] || {}).email || '').toLowerCase().trim();
    }

    function initOtpSection(payload) {
        var email = getKetuaEmail();
        var subtitle = payload.kategori === 'instansi'
            ? 'OTP dikirim ke email ketua kelompok untuk verifikasi'
            : 'OTP dikirim ke email Anda untuk verifikasi';

        $('#otpCardSubtitle').text(subtitle);
        $('#otpEmailDisplay').text(email || '-');

        // Reset UI
        state.otpVerified = false;
        $('#btnSimpanBiodata').prop('disabled', true);
        $('#otpStepSend').show();
        $('#otpStepVerify').hide();
        $('#otpStepDone').hide();
        $('#inputOtp').val('');
        clearCountdown();
    }

    // Kirim OTP
    $('#btnKirimOtp').on('click', function () {
        var email = getKetuaEmail();
        if (!email) {
            Swal.fire({ icon: 'error', title: 'Error', text: 'Email tidak ditemukan. Kembali dan periksa biodata.', confirmButtonColor: 'var(--primary)' });
            return;
        }

        var $btn = $(this);
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Mengirim...');

        $.ajax({
            url: cfg.urlSendOtp,
            method: 'POST',
            data: getCsrfBody({ email: email, token: cfg.token }),
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function (res) {
                refreshCsrf(res);
                $btn.prop('disabled', false).html('<i class="fas fa-paper-plane"></i> Kirim OTP ke Email');

                if (!res.success) {
                    Swal.fire({ icon: 'error', title: 'Gagal!', text: res.message, confirmButtonColor: 'var(--primary)' });
                    return;
                }

                toast('success', 'OTP dikirim ke ' + email, 3000);
                $('#otpStepSend').hide();
                $('#otpStepVerify').show();
                $('#inputOtp').trigger('focus');
                startCountdown(res.ttl || 300);
            },
            error: function (xhr) {
                $btn.prop('disabled', false).html('<i class="fas fa-paper-plane"></i> Kirim OTP ke Email');

                var msg = (xhr.responseJSON && xhr.responseJSON.message)
                    ? xhr.responseJSON.message
                    : 'Tidak dapat terhubung ke server.';

                Swal.fire({ icon: 'error', title: 'Gagal!', text: msg, confirmButtonColor: 'var(--primary)' });
            },
        });
    });

    // Verifikasi OTP
    $('#btnVerifikasiOtp').on('click', function () {
        var otp = $.trim($('#inputOtp').val());
        if (otp.length !== 6) {
            toast('warning', 'Masukkan kode OTP 6 digit.', 2000);
            return;
        }

        var $btn = $(this);
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

        $.ajax({
            url: cfg.urlVerifyOtp,
            method: 'POST',
            data: getCsrfBody({ otp: otp, token: cfg.token }),
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function (res) {
                refreshCsrf(res);
                $btn.prop('disabled', false).html('<i class="fas fa-check"></i> Verifikasi');

                if (!res.success) {
                    Swal.fire({ icon: 'error', title: 'OTP Salah!', text: res.message, confirmButtonColor: 'var(--primary)' });
                    return;
                }

                state.otpVerified = true;
                clearCountdown();
                $('#otpStepVerify').hide();
                $('#otpStepDone').show();
                $('#btnSimpanBiodata').prop('disabled', false);
                toast('success', 'Email berhasil diverifikasi!', 2500);
            },
            error: function (xhr) {
                $btn.prop('disabled', false).html('<i class="fas fa-check"></i> Verifikasi');

                var msg = (xhr.responseJSON && xhr.responseJSON.message)
                    ? xhr.responseJSON.message
                    : 'Tidak dapat terhubung ke server.';

                Swal.fire({ icon: 'error', title: 'Gagal!', text: msg, confirmButtonColor: 'var(--primary)' });

                // Deteksi jika sesi OTP sudah dihapus server (karena kadaluarsa atau max percobaan)
                if (msg.toLowerCase().includes('kirim ulang') || msg.toLowerCase().includes('kadaluarsa')) {
                    // Reset UI kembali ke tampilan awal OTP
                    clearCountdown();
                    $('#inputOtp').val('');
                    $('#otpStepVerify').hide();
                    $('#otpStepSend').show(); // Memunculkan tombol Kirim OTP lagi
                }
            },
        });
    });

    // Input OTP: hanya angka, auto-verify saat 6 digit
    $('#inputOtp').on('input', function () {
        this.value = this.value.replace(/\D/g, '').slice(0, 6);
        if (this.value.length === 6) {
            $('#btnVerifikasiOtp').trigger('click');
        }
    });

    // Kirim ulang OTP
    $('#btnResendOtp').on('click', function () {
        $('#otpStepSend').show();
        $('#otpStepVerify').hide();
        clearCountdown();
        $('#btnKirimOtp').trigger('click');
    });

    // ── Countdown Timer ──────────────────────────────────────────────

    function startCountdown(ttl) {
        clearCountdown();
        var remaining = ttl;
        updateCountdownUI(remaining);
        $('#btnResendOtp').hide();
        $('#btnVerifikasiOtp').prop('disabled', false);

        state.otpCountdownTimer = setInterval(function () {
            remaining--;
            updateCountdownUI(remaining);

            if (remaining <= 0) {
                clearCountdown();
                $('#otpCountdown').html('<span class="otp-expired">OTP kadaluarsa.</span>');
                $('#btnResendOtp').show();
                $('#btnVerifikasiOtp').prop('disabled', true);
            }
        }, 1000);
    }

    function updateCountdownUI(secs) {
        var m = Math.floor(secs / 60);
        var s = secs % 60;
        var fmt = (m < 10 ? '0' + m : m) + ':' + (s < 10 ? '0' + s : s);
        $('#otpCountdown').html('<i class="fas fa-clock"></i> Berlaku: <strong>' + fmt + '</strong>');
    }

    function clearCountdown() {
        if (state.otpCountdownTimer) {
            clearInterval(state.otpCountdownTimer);
            state.otpCountdownTimer = null;
        }
    }

    // ── Final Submit ─────────────────────────────────────────────────

    $('#btnSimpanBiodata').on('click', function () {
        if (!state.otpVerified) {
            toast('warning', 'Verifikasi email terlebih dahulu.', 2000);
            return;
        }

        Swal.fire({
            icon: 'question',
            title: 'Simpan Pendaftaran?',
            text: 'Pastikan semua data sudah benar. Data tidak dapat diubah setelah disimpan.',
            showCancelButton: true,
            confirmButtonText: '<i class="fas fa-save"></i> Ya, Simpan',
            cancelButtonText: 'Periksa Lagi',
            confirmButtonColor: 'var(--primary)',
            cancelButtonColor: 'var(--secondary-light)',
            reverseButtons: true,
        }).then(function (result) {
            if (!result.isConfirmed) return;

            var payload = buildPayload();
            var $btn = $('#btnSimpanBiodata');
            var origHtml = $btn.html();
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Menyimpan...');

            $.ajax({
                url: cfg.urlStore,
                method: 'POST',
                data: getCsrfBody({ payload: JSON.stringify(payload), token: cfg.token }),
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                success: function (res) {
                    refreshCsrf(res);
                    $btn.prop('disabled', false).html(origHtml);

                    if (!res.success) {
                        Swal.fire({ icon: 'error', title: 'Gagal!', text: res.message, confirmButtonColor: 'var(--primary)' });
                        return;
                    }

                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        html: res.message + '<br><small style="color:#64748b">Anda bisa melanjutkan dengan membuka WhatsApp rekap pendaftaran.</small>',
                        confirmButtonText: '<i class="fab fa-whatsapp"></i> Buka WhatsApp',
                        showCancelButton: true,
                        cancelButtonText: 'Lihat Halaman Sukses',
                        confirmButtonColor: '#25d366',
                        cancelButtonColor: 'var(--primary)',
                    }).then(function (result) {
                        if (result.isConfirmed && res.wa_url) {
                            window.open(res.wa_url, '_blank');
                        }

                        window.location.href = res.redirect || (cfg.baseUrl + 'biodata-pkl/sukses');
                    });
                },
                error: function (xhr) {
                    $btn.prop('disabled', false).html(origHtml);
                    var msg = (xhr.responseJSON && xhr.responseJSON.message)
                        ? xhr.responseJSON.message
                        : 'Terjadi kesalahan server.';
                    Swal.fire({ icon: 'error', title: 'Gagal!', text: msg, confirmButtonColor: 'var(--primary)' });
                },
            });
        });
    });

    // ── Step Button Handlers ─────────────────────────────────────────

    // Step 1 → Step 2
    $('#btnStep1Next').on('click', function () {
        var err = validateStep1();
        if (err) {
            Swal.fire({ icon: 'warning', title: 'Perhatian', text: err, confirmButtonColor: 'var(--primary)' });
            return;
        }

        if (state.kategori === 'instansi') {
            state.jumlahAnggota = parseInt($('#bJumlahAnggota').val(), 10) || 2;
        }

        renderAccordion();
        goToStep(2);
    });

    // Step 2 → Step 1
    $('#btnStep2Back').on('click', function () {
        collectAnggotaData();
        goToStep(1);
    });

    // Step 2 → Step 3
    $('#btnStep2Next').on('click', function () {
        var anggota = collectAnggotaData();
        var err = validateStep2(anggota);

        if (err) {
            Swal.fire({ icon: 'warning', title: 'Perhatian', text: err, confirmButtonColor: 'var(--primary)' });
            return;
        }

        var $btn = $(this);
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Memeriksa...');

        checkEmailsUnique(anggota, function (conflicts) {
            $btn.prop('disabled', false).html('Lanjut ke Konfirmasi <i class="fas fa-arrow-right"></i>');

            if (conflicts.length > 0) {
                Swal.fire({
                    icon: 'error',
                    title: 'Email Sudah Terdaftar',
                    html: 'Email berikut sudah terdaftar di sistem:<br><strong>'
                        + conflicts.map(function (e) { return esc(e); }).join('<br>')
                        + '</strong><br><small>Gunakan email lain atau hubungi admin.</small>',
                    confirmButtonColor: 'var(--primary)',
                });
                return;
            }

            var payload = buildPayload();
            renderKonfirmasi(payload);
            initOtpSection(payload);
            goToStep(3);
        });
    });

    // Step 3 → Step 2
    $('#btnStep3Back').on('click', function () {
        clearCountdown();
        state.otpVerified = false;
        goToStep(2);
    });

    // ── Init ─────────────────────────────────────────────────────────

    function init() {
        initDatepickers();
        initSelect2();
        goToStep(1);
    }

    $(document).ready(init);

}(jQuery));
