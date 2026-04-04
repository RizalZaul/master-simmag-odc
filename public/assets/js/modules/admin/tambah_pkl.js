/**
 * SIMMAG ODC — Tambah PKL Wizard JS
 * public/assets/js/modules/admin/tambah_pkl.js
 */

$(document).ready(function () {

    var cfg = window.TambahPKL || {};
    var urlCheckEmail = cfg.urlCheckEmail || '';
    var urlStore = cfg.urlStore || '';
    var urlKembali = cfg.urlKembali || '';
    var instansiList = cfg.instansiList || [];
    var kotaList = cfg.kotaList || [];
    var v = window.SimmagValidation || {};

    if (window.SimmagValidation && typeof window.SimmagValidation.applyInputRules === 'function') {
        window.SimmagValidation.applyInputRules([
            { selector: '#s1NamaPembimbing', rule: 'person_name', label: 'Nama Pembimbing' },
            { selector: '#s1WaPembimbing', rule: 'phone', label: 'No WA Pembimbing' },
            { selector: '#s1JumlahAnggota', rule: 'numeric', label: 'Jumlah Anggota PKL' },
            { selector: '#s1NamaKelompok', rule: 'group_name', label: 'Nama Kelompok' },
            { selector: '#s1AlamatInstansi', rule: 'address', label: 'Alamat Instansi Baru' }
        ]);
    }

    function csrf() {
        var name = document.querySelector('meta[name="csrf-token-name"]')?.content ?? '';
        var hash = document.querySelector('meta[name="csrf-token-hash"]')?.content ?? '';
        var obj = {}; obj[name] = hash; return obj;
    }

    // ── Flatpickr Locale ID ──────────────────────────────────────
    var fpLocale = {
        firstDayOfWeek: 1,
        weekdays: { shorthand: ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'], longhand: ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'] },
        months: {
            shorthand: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
            longhand: ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
        },
    };

    // ── Step State ───────────────────────────────────────────────
    var currentStep = 1;
    var formData = {};

    function buildMissingFieldsMessage(missingFields, totalRequired) {
        if (v.buildMissingFieldsMessage) {
            return v.buildMissingFieldsMessage(missingFields, totalRequired);
        }
        var labels = Array.from(new Set((missingFields || []).filter(Boolean)));
        if (!labels.length) return 'Semua field harus diisi.';
        if (totalRequired && labels.length >= totalRequired) return 'Semua field harus diisi.';
        if (labels.length === 1) return labels[0] + ' wajib diisi.';
        return 'Field berikut wajib diisi: ' + labels.join(', ') + '.';
    }

    function appendGroupMessage(messages, groupLabel, missingFields, totalRequired) {
        if (!missingFields.length) return;
        messages.push(groupLabel + ': ' + buildMissingFieldsMessage(missingFields, totalRequired));
    }

    function normalizeText(value) {
        return v.normalizeSpaces ? v.normalizeSpaces(value) : $.trim(value || '');
    }

    function normalizeJumlahAnggota($field) {
        var raw = $.trim($field.val() || '');
        var value = parseInt(raw, 10);

        if (!raw || isNaN(value) || value < 1) value = 1;
        if (value > 10) value = 10;

        $field.val(value);
        return value;
    }

    function goToStep(step) {
        // Update indicator
        for (var i = 1; i <= 3; i++) {
            var ind = document.getElementById('step-ind-' + i);
            ind.className = 'wizard-step' + (i < step ? ' done' : (i === step ? ' active' : ''));
        }
        // Update lines
        document.querySelectorAll('.wizard-line').forEach(function (l, idx) {
            l.className = 'wizard-line' + (idx < step - 1 ? ' done' : '');
        });
        // Show panel
        document.querySelectorAll('.wizard-panel').forEach(function (p) { p.style.display = 'none'; });
        document.getElementById('panel-' + step).style.display = 'block';
        currentStep = step;
        window.scrollTo(0, 0);
    }

    // ══ STEP 1 ═══════════════════════════════════════════════════

    // Kategori toggle
    $('input[name="kategori"]').on('change', function () {
        var isMandiri = $(this).val() === 'mandiri';
        $('#fieldInstansiGroup').toggle(!isMandiri);
        // Set required
        var instansiFields = ['#s1KategoriInstansi', '#s1NamaPembimbing', '#s1WaPembimbing', '#s1JumlahAnggota', '#s1NamaKelompok'];
        instansiFields.forEach(function (f) {
            $(f).prop('required', !isMandiri);
        });
    });

    $('#s1JumlahAnggota').on('change blur', function () {
        normalizeJumlahAnggota($(this));
    });

    // Flatpickr tanggal mulai
    var fpMulai = flatpickr('#s1TglMulai', {
        dateFormat: 'Y-m-d', altInput: true, altFormat: 'd M Y',
        locale: fpLocale,
        minDate: cfg.minMulai,
        maxDate: cfg.maxMulai,
        allowInput: false,
        onChange: function (sel) {
            if (sel[0] && fpAkhir) {
                var minAkhir = new Date(sel[0]);
                minAkhir.setMonth(minAkhir.getMonth() + 2);
                fpAkhir.set('minDate', minAkhir);
                var akhirDipilih = $('#s1TglAkhir').val();
                if (akhirDipilih && v.validatePklEndDate && v.validatePklEndDate($('#s1TglMulai').val(), akhirDipilih)) {
                    fpAkhir.clear();
                }
            }
        },
    });

    var fpAkhir = flatpickr('#s1TglAkhir', {
        dateFormat: 'Y-m-d', altInput: true, altFormat: 'd M Y',
        locale: fpLocale,
        allowInput: false,
    });

    // Select2 Instansi (pilih atau tambah baru)
    var $s1Instansi = $('#s1NamaInstansi');
    $s1Instansi.select2({
        tags: true, placeholder: 'Pilih atau Ketik Instansi Baru',
        allowClear: true, width: '100%',
        dropdownParent: $('#panel-1'),
        createTag: function (p) {
            var t = $.trim(p.term);
            if (!t) return null;
            return { id: 'new:' + t, text: t + ' (Baru)', newTag: true };
        },
    });
    $s1Instansi.on('select2:open', function () {
        var field = document.querySelector('.select2-container--open .select2-search__field');
        if (field) {
            field.dataset.svRule = 'instansi_name';
            field.dataset.svLabel = 'Nama Instansi';
        }
    });

    // ── Filter Nama Instansi berdasarkan Kategori ────────────────────
    $('#s1KategoriInstansi').on('change', function () {
        var selectedKategori = $(this).val(); // Nilainya 'Kuliah' atau 'SMK Sederajat'
        var $selectInstansi = $('#s1NamaInstansi');

        // 1. Kosongkan opsi yang ada (kembalikan hanya placeholder)
        $selectInstansi.empty().append('<option value=""></option>');

        // 2. Jika ada kategori yang dipilih, filter data dari variabel global (instansiList)
        if (selectedKategori) {
            var filteredData = cfg.instansiList.filter(function (item) {
                // Pastikan variabel kategori_label cocok
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
        $('#fieldInstansiBaru, #fieldKotaBaru').hide();
    });

    $s1Instansi.on('change', function () {
        var val = $(this).val() || '';
        var isNew = val.startsWith('new:');
        $('#fieldInstansiBaru, #fieldKotaBaru').toggle(isNew);
        if (isNew) {
            var namaInstansiBaru = val.replace('new:', '');
            // Init Select2 kota jika belum
            if (!$('#s1KotaInstansi').data('select2')) {
                $('#s1KotaInstansi').select2({
                    tags: true, placeholder: 'Pilih atau Ketik Kota',
                    allowClear: true, width: '100%',
                    dropdownParent: $('#panel-1'),
                });
                $('#s1KotaInstansi').on('select2:open', function () {
                    var field = document.querySelector('.select2-container--open .select2-search__field');
                    if (field) {
                        field.dataset.svRule = 'city';
                        field.dataset.svLabel = 'Kota Instansi Baru';
                    }
                });
            }
        }
    });

    // Tombol Next Step 1
    $('#btnStep1Next').on('click', function () {
        var kategori = $('input[name="kategori"]:checked').val();
        var mulai = $('#s1TglMulai').val();
        var akhir = $('#s1TglAkhir').val();
        var missingFields = [];
        var totalRequired = 2;

        if (!mulai) missingFields.push('Tanggal Mulai PKL');
        if (!akhir) missingFields.push('Tanggal Akhir PKL');

        if (kategori === 'instansi') {
            var kat = $('#s1KategoriInstansi').val();
            var instansi = $('#s1NamaInstansi').val();
            var pembimbing = $('#s1NamaPembimbing').val();
            var waPemb = $('#s1WaPembimbing').val();
            var jumlah = normalizeJumlahAnggota($('#s1JumlahAnggota'));
            var namaKel = $('#s1NamaKelompok').val();
            var isNewInstansi = (instansi || '').startsWith('new:');
            totalRequired += 6 + (isNewInstansi ? 2 : 0);

            if (!kat) missingFields.push('Kategori Instansi');
            if (!instansi) missingFields.push('Nama Instansi');
            if (!pembimbing) missingFields.push('Nama Pembimbing');
            if (!waPemb) missingFields.push('No WA Pembimbing');
            if (!namaKel) missingFields.push('Nama Kelompok');

            if (isNewInstansi) {
                if (!$('#s1AlamatInstansi').val().trim()) missingFields.push('Alamat Instansi Baru');
                if (!$('#s1KotaInstansi').val()) missingFields.push('Kota Instansi Baru');
            }

            if (missingFields.length) {
                Swal.fire({ icon: 'warning', title: 'Lengkapi Data', text: buildMissingFieldsMessage(missingFields, totalRequired), confirmButtonColor: 'var(--primary)' });
                return;
            }

            var namaInstansi = isNewInstansi
                ? String(instansi).replace('new:', '').split(' (Baru)')[0]
                : ((instansiList.find(function (i) { return String(i.id_instansi) === String(String(instansi).replace('existing:', '')); }) || {}).nama_instansi || instansi);
            var fieldError = (v.validatePklStartDate ? v.validatePklStartDate(mulai) : '')
                || (v.validatePklEndDate ? v.validatePklEndDate(mulai, akhir) : '')
                || (v.validatePatternField ? v.validatePatternField('Nama Instansi', namaInstansi, 2, 100, /^[\p{L}0-9\s'.()\-]+$/u, 'huruf, angka, spasi, apostrof, tanda hubung, tanda kurung, dan titik') : '')
                || (isNewInstansi && v.validatePatternField ? v.validatePatternField('Alamat Instansi Baru', $('#s1AlamatInstansi').val(), 5, 100, /^[\p{L}0-9\s'.,\-\/#+]+$/u, 'huruf, angka, spasi, apostrof, tanda hubung, titik, koma, garis miring, dan tanda angka (#)') : '')
                || (isNewInstansi && v.validatePatternField ? v.validatePatternField('Kota Instansi Baru', $('#s1KotaInstansi').val(), 1, 50, /^[\p{L}\s]+$/u, 'huruf dan spasi') : '')
                || (v.validatePatternField ? v.validatePatternField('Nama Pembimbing', pembimbing, 1, 100, /^[\p{L}\s.,'-]+$/u, 'huruf, spasi, titik, koma, apostrof, dan tanda hubung') : '')
                || (v.validatePhone ? v.validatePhone(waPemb, 'No WA Pembimbing') : '')
                || (isNaN(jumlah) || jumlah < 1
                    ? 'Jumlah Anggota PKL minimal 1.'
                    : (jumlah > 10 ? 'Jumlah Anggota PKL maksimal 10.' : ''))
                || (v.validateLooseField ? v.validateLooseField('Nama Kelompok', namaKel, 5, 20) : '');

            if (fieldError) {
                Swal.fire({ icon: 'warning', title: 'Periksa Data', text: fieldError, confirmButtonColor: 'var(--primary)' });
                return;
            }

            var instansiData = {};
            if (isNewInstansi) {
                instansiData = {
                    is_new: true,
                    nama: normalizeText(namaInstansi),
                    kategori_label: kat,
                    alamat: normalizeText($('#s1AlamatInstansi').val()),
                    kota: normalizeText($('#s1KotaInstansi').val() || ''),
                };
            } else {
                var instansiId = instansi.replace('existing:', '');
                var found = instansiList.find(function (i) { return i.id_instansi == instansiId; });
                instansiData = {
                    is_new: false,
                    id: instansiId,
                    nama: normalizeText(found ? found.nama_instansi : instansi),
                    kategori_label: kat,
                };
            }

            formData.kategori = 'instansi';
            formData.instansi = instansiData;
            formData.nama_pembimbing = normalizeText(pembimbing);
            formData.no_wa_pembimbing = $.trim(waPemb);
            formData.jumlah_anggota = jumlah;
            formData.nama_kelompok = normalizeText(namaKel);
        } else {
            var mandiriError = (v.validatePklStartDate ? v.validatePklStartDate(mulai) : '')
                || (v.validatePklEndDate ? v.validatePklEndDate(mulai, akhir) : '');
            if (mandiriError) {
                Swal.fire({ icon: 'warning', title: 'Periksa Data', text: mandiriError, confirmButtonColor: 'var(--primary)' });
                return;
            }
            formData.kategori = 'mandiri';
            formData.jumlah_anggota = 1;
        }

        if (missingFields.length) {
            Swal.fire({ icon: 'warning', title: 'Lengkapi Data', text: buildMissingFieldsMessage(missingFields, totalRequired), confirmButtonColor: 'var(--primary)' });
            return;
        }

        formData.tgl_mulai = mulai;
        formData.tgl_akhir = akhir;

        buildAccordionAnggota(formData.jumlah_anggota, formData.kategori === 'instansi');
        goToStep(2);
    });

    // ══ STEP 2 ═══════════════════════════════════════════════════

    function buildAccordionAnggota(jumlah, isInstansi) {
        var html = '';
        for (var i = 1; i <= jumlah; i++) {
            var isKetua = i === 1;
            var roleLabel = isInstansi ? (isKetua ? ' (Ketua)' : ' (Anggota)') : '';
            var expanded = i === 1;
            html += buildAnggotaAccordion(i, roleLabel, isInstansi, expanded);
        }
        $('#accordionAnggota').html(html);

        // Init Flatpickr untuk setiap tgl lahir
        for (var i = 1; i <= jumlah; i++) {
            flatpickr('#ang' + i + 'TglLahir', {
                dateFormat: 'Y-m-d', altInput: true, altFormat: 'd M Y',
                locale: fpLocale, maxDate: 'today', allowInput: false,
            });
        }

        if (window.SimmagValidation && typeof window.SimmagValidation.applyInputRules === 'function') {
            window.SimmagValidation.applyInputRules([
                { selector: 'input[id$="NamaLengkap"]', rule: 'person_name', label: 'Nama Lengkap' },
                { selector: 'input[id$="NamaPanggilan"]', rule: 'nickname', label: 'Nama Panggilan' },
                { selector: 'input[id$="TempatLahir"]', rule: 'city', label: 'Tempat Lahir' },
                { selector: 'input[id$="Alamat"]', rule: 'address', label: 'Alamat' },
                { selector: 'input[id$="NoWa"]', rule: 'phone', label: 'No WA' },
                { selector: 'input[id$="Email"]', rule: 'email', label: 'Email' },
                { selector: 'input[id$="Jurusan"]', rule: 'jurusan', label: 'Jurusan' }
            ], document.getElementById('accordionAnggota'));
        }
    }

    function buildAnggotaAccordion(no, roleLabel, isInstansi, expanded) {
        var jurusanField = isInstansi ? '<div class="anggota-form-full"><label class="wizard-label"><i class="fas fa-graduation-cap"></i> Jurusan / Program Studi <span class="required-star">*</span></label><input type="text" id="ang' + no + 'Jurusan" class="wizard-input" placeholder="Jurusan/Program Studi" maxlength="100"></div>' : '';
        var roleDisplay = isInstansi ? '<div><label class="wizard-label"><i class="fas fa-user-tag"></i> Role dalam Kelompok</label><div class="wizard-input" style="background:var(--bg-gray);color:var(--text-muted);">' + (no === 1 ? 'Ketua' : 'Anggota') + '</div></div>' : '';

        return '<div class="anggota-accordion" id="acc-anggota-' + no + '">' +
            '<div class="anggota-acc-header" onclick="toggleAccAnggota(this)">' +
            '<div class="anggota-acc-title">' +
            '<i class="fas fa-user-circle"></i>' +
            'Anggota ' + no + roleLabel +
            (no === 1 && isInstansi ? '<span class="badge-role-ketua" style="font-size:.65rem">Ketua</span>' : '') +
            '</div>' +
            '<i class="fas fa-chevron-' + (expanded ? 'up' : 'down') + ' toggle-icon"></i>' +
            '</div>' +
            '<div class="anggota-acc-body"' + (expanded ? '' : ' style="display:none"') + '>' +
            '<div class="anggota-form-grid">' +
            '<div><label class="wizard-label"><i class="fas fa-user"></i> Nama Lengkap <span class="required-star">*</span></label><input type="text" id="ang' + no + 'NamaLengkap" class="wizard-input" placeholder="Nama lengkap sesuai KTP" maxlength="100"></div>' +
            '<div><label class="wizard-label"><i class="fas fa-smile"></i> Nama Panggilan <span class="required-star">*</span></label><input type="text" id="ang' + no + 'NamaPanggilan" class="wizard-input" placeholder="Nama panggilan" maxlength="10"></div>' +
            '<div><label class="wizard-label"><i class="fas fa-map-pin"></i> Tempat Lahir <span class="required-star">*</span></label><input type="text" id="ang' + no + 'TempatLahir" class="wizard-input" placeholder="Kota tempat lahir" maxlength="50"></div>' +
            '<div><label class="wizard-label"><i class="fas fa-birthday-cake"></i> Tanggal Lahir <span class="required-star">*</span></label><input type="text" id="ang' + no + 'TglLahir" class="wizard-input" placeholder="Pilih tanggal"></div>' +
            '<div class="anggota-form-full"><label class="wizard-label"><i class="fas fa-home"></i> Alamat <span class="required-star">*</span></label><input type="text" id="ang' + no + 'Alamat" class="wizard-input" placeholder="Alamat lengkap" maxlength="100"></div>' +
            '<div><label class="wizard-label"><i class="fab fa-whatsapp"></i> No WA <span class="required-star">*</span></label><input type="text" id="ang' + no + 'NoWa" class="wizard-input" placeholder="08xxxxxxxxxx" maxlength="20"></div>' +
            '<div><label class="wizard-label"><i class="fas fa-envelope"></i> Email <span class="required-star">*</span></label><input type="email" id="ang' + no + 'Email" class="wizard-input" placeholder="email@example.com" maxlength="100"></div>' +
            '<div><label class="wizard-label"><i class="fas fa-venus-mars"></i> Jenis Kelamin <span class="required-star">*</span></label>' +
            '<div class="radio-group">' +
            '<label class="radio-option"><input type="radio" name="ang' + no + 'JenisKelamin" value="L"><span class="radio-custom"></span> Laki-laki</label>' +
            '<label class="radio-option"><input type="radio" name="ang' + no + 'JenisKelamin" value="P"><span class="radio-custom"></span> Perempuan</label>' +
            '</div></div>' +
            jurusanField +
            roleDisplay +
            '</div>' +
            '</div>' +
            '</div>';
    }

    window.toggleAccAnggota = function (header) {
        var body = header.nextElementSibling;
        var icon = header.querySelector('.toggle-icon');
        var open = body.style.display !== 'none';
        body.style.display = open ? 'none' : 'block';
        if (icon) icon.className = 'fas fa-chevron-' + (open ? 'down' : 'up') + ' toggle-icon';
    };

    // Tombol Next Step 2
    $('#btnStep2Next').on('click', function () {
        var jumlah = formData.jumlah_anggota || 1;
        var isInstansi = formData.kategori === 'instansi';
        var anggota = [];
        var errors = [];
        var emails = [];

        for (var i = 1; i <= jumlah; i++) {
            var namaL = $('#ang' + i + 'NamaLengkap').val();
            var namaP = $('#ang' + i + 'NamaPanggilan').val();
            var tmpLhr = $('#ang' + i + 'TempatLahir').val();
            var tglLhr = $('#ang' + i + 'TglLahir').val();
            var alamat = $('#ang' + i + 'Alamat').val();
            var noWa = $('#ang' + i + 'NoWa').val();
            var email = $('#ang' + i + 'Email').val();
            var jk = $('input[name="ang' + i + 'JenisKelamin"]:checked').val() || '';
            var jurusan = isInstansi ? ($('#ang' + i + 'Jurusan').val() || '') : '';
            var missingAnggota = [];

            if (!$.trim(namaL || '')) missingAnggota.push('Nama Lengkap');
            if (!$.trim(namaP || '')) missingAnggota.push('Nama Panggilan');
            if (!$.trim(tmpLhr || '')) missingAnggota.push('Tempat Lahir');
            if (!tglLhr) missingAnggota.push('Tanggal Lahir');
            if (!$.trim(alamat || '')) missingAnggota.push('Alamat');
            if (!$.trim(noWa || '')) missingAnggota.push('No WA');
            if (!$.trim(email || '')) missingAnggota.push('Email');
            if (!jk) missingAnggota.push('Jenis Kelamin');
            if (isInstansi && !$.trim(jurusan || '')) missingAnggota.push('Jurusan');

            appendGroupMessage(errors, 'Anggota ' + i, missingAnggota, isInstansi ? 9 : 8);
            if (missingAnggota.length) {
                continue;
            }

            var fieldError = (v.validatePatternField ? v.validatePatternField('Nama Lengkap', namaL, 1, 100, /^[\p{L}\s.,'-]+$/u, 'huruf, spasi, titik, koma, apostrof, dan tanda hubung') : '')
                || (v.validateLooseField ? v.validateLooseField('Nama Panggilan', namaP, 1, 10) : '')
                || (v.validatePatternField ? v.validatePatternField('Tempat Lahir', tmpLhr, 1, 50, /^[\p{L}\s]+$/u, 'huruf dan spasi') : '')
                || (v.validateDateOnly ? v.validateDateOnly(tglLhr, 'Tanggal Lahir') : '')
                || (v.validatePatternField ? v.validatePatternField('Alamat', alamat, 5, 100, /^[\p{L}0-9\s'.,\-\/#+]+$/u, 'huruf, angka, spasi, apostrof, tanda hubung, titik, koma, garis miring, dan tanda angka (#)') : '')
                || (v.validatePhone ? v.validatePhone(noWa, 'No WA') : '')
                || (v.validateEmail ? v.validateEmail(email, 'Email') : '')
                || (isInstansi && v.validatePatternField ? v.validatePatternField('Jurusan', jurusan, 2, 100, /^[\p{L}\s.()\-]+$/u, 'huruf, spasi, titik, tanda hubung, dan tanda kurung') : '');
            if (fieldError) {
                errors.push('Anggota ' + i + ': ' + fieldError);
            }
            if ($.trim(email || '')) emails.push(normalizeText(email).toLowerCase());

            anggota.push({
                nama_lengkap: normalizeText(namaL), nama_panggilan: normalizeText(namaP), tempat_lahir: normalizeText(tmpLhr),
                tgl_lahir: tglLhr, alamat: normalizeText(alamat), no_wa: $.trim(noWa), email: $.trim(email),
                jenis_kelamin: jk, jurusan: isInstansi ? normalizeText(jurusan) : ''
            });
        }

        if (errors.length) {
            Swal.fire({ icon: 'warning', title: 'Lengkapi Data', html: errors.join('<br>'), confirmButtonColor: 'var(--primary)' });
            return;
        }

        // ── Cek email duplikat ANTAR anggota (sebelum AJAX ke server) ──
        // Bug: sebelumnya hanya cek ke DB, tidak cek sesama form —
        // email kembar antar anggota lolos sampai server lalu error.
        var emailLower = emails.map(function (e) { return e.toLowerCase(); });
        var emailSeen = {};
        var internalDup = [];
        emailLower.forEach(function (em) {
            if (emailSeen[em]) {
                if (internalDup.indexOf(em) === -1) internalDup.push(em);
            } else {
                emailSeen[em] = true;
            }
        });
        if (internalDup.length) {
            Swal.fire({
                icon: 'error',
                title: 'Email Ganda Antar Anggota',
                html: 'Email berikut digunakan lebih dari satu anggota:<br>'
                    + '<strong>' + internalDup.join(', ') + '</strong>'
                    + '<br><small style="color:#64748b">Setiap anggota harus memiliki email yang berbeda.</small>',
                confirmButtonColor: 'var(--primary)',
            });
            return;
        }
        var $btn = $('#btnStep2Next');
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Memeriksa email...');

        $.ajax({
            url: urlCheckEmail, method: 'POST',
            data: Object.assign(csrf(), { emails: emails }),
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function (res) {
                var dupEmails = [];
                if (res.data) {
                    Object.keys(res.data).forEach(function (em) {
                        if (res.data[em]) dupEmails.push(em);
                    });
                }
                if (dupEmails.length) {
                    Swal.fire({
                        icon: 'error', title: 'Email Sudah Terdaftar',
                        html: 'Email berikut sudah digunakan:<br><strong>' + dupEmails.join(', ') + '</strong>',
                        confirmButtonColor: 'var(--primary)'
                    });
                    return;
                }
                formData.anggota = anggota;
                buildKonfirmasi();
                goToStep(3);
            },
            complete: function () {
                $btn.prop('disabled', false).html('Lanjut ke Konfirmasi <i class="fas fa-arrow-right"></i>');
            },
        });
    });

    $('#btnStep2Back').on('click', function () { goToStep(1); });

    // ══ STEP 3 ═══════════════════════════════════════════════════

    function tglFmt(d) {
        if (!d) return '-';
        var bln = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        var p = d.split('-');
        return parseInt(p[2]) + ' ' + bln[parseInt(p[1])] + ' ' + p[0];
    }

    function buildKonfirmasi() {
        var html = '';
        var jumlahAnggotaLabel = (formData.anggota || []).length > 1
            ? ' (' + formData.anggota.length + ' Orang)'
            : '';

        // Data PKL
        html += '<div class="konfirmasi-section">';
        html += '<div class="konfirmasi-section-header"><i class="fas fa-users"></i> Data PKL</div>';
        html += '<div class="konfirmasi-section-body">';
        html += '<div class="konfirmasi-row"><span class="conf-label">Kategori PKL:</span><span class="conf-value">' + (formData.kategori === 'instansi' ? 'Instansi' : 'Mandiri') + '</span></div>';
        if (formData.instansi) {
            html += '<div class="konfirmasi-row"><span class="conf-label">Nama Instansi:</span><span class="conf-value">' + (formData.instansi.nama || '-') + '</span></div>';
            html += '<div class="konfirmasi-row"><span class="conf-label">Nama Kelompok:</span><span class="conf-value">' + (formData.nama_kelompok || '-') + '</span></div>';
            html += '<div class="konfirmasi-row"><span class="conf-label">Pembimbing:</span><span class="conf-value">' + (formData.nama_pembimbing || '-') + '</span></div>';
        }
        html += '<div class="konfirmasi-row"><span class="conf-label">Tanggal Mulai:</span><span class="conf-value">' + tglFmt(formData.tgl_mulai) + '</span></div>';
        html += '<div class="konfirmasi-row"><span class="conf-label">Tanggal Akhir:</span><span class="conf-value">' + tglFmt(formData.tgl_akhir) + '</span></div>';
        html += '</div></div>';

        // Biodata
        html += '<div class="konfirmasi-section">';
        html += '<div class="konfirmasi-section-header"><i class="fas fa-id-card"></i> Biodata' + jumlahAnggotaLabel + '</div>';
        html += '<div class="konfirmasi-section-body" style="padding:10px 18px">';

        formData.anggota.forEach(function (ang, idx) {
            var isKetua = idx === 0 && formData.kategori === 'instansi';
            var role = isKetua ? 'Ketua' : (formData.kategori === 'instansi' ? 'Anggota' : '');
            var jkLabel = ang.jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan';
            html += '<div class="konfirmasi-anggota-card">';
            html += '<div class="konfirmasi-anggota-header" onclick="toggleAccKonfirmasi(this)">' +
                '<i class="fas fa-user-circle" style="color:var(--primary)"></i>' +
                ang.nama_lengkap + (role ? ' <span class="badge-role-' + (isKetua ? 'ketua' : 'anggota') + '" style="font-size:.65rem">' + role + '</span>' : '') +
                '</div>';
            html += '<div class="konfirmasi-anggota-body" ' + (idx > 0 ? 'style="display:none"' : '') + '>';
            html += '<div class="konfirmasi-row"><span class="conf-label">Nama Panggilan:</span><span class="conf-value">' + ang.nama_panggilan + '</span></div>';
            html += '<div class="konfirmasi-row"><span class="conf-label">Tempat, Tgl Lahir:</span><span class="conf-value">' + ang.tempat_lahir + ', ' + tglFmt(ang.tgl_lahir) + '</span></div>';
            html += '<div class="konfirmasi-row"><span class="conf-label">Alamat:</span><span class="conf-value">' + ang.alamat + '</span></div>';
            html += '<div class="konfirmasi-row"><span class="conf-label">No WA:</span><span class="conf-value">' + ang.no_wa + '</span></div>';
            html += '<div class="konfirmasi-row"><span class="conf-label">Email:</span><span class="conf-value">' + ang.email + '</span></div>';
            html += '<div class="konfirmasi-row"><span class="conf-label">Jenis Kelamin:</span><span class="conf-value">' + jkLabel + '</span></div>';
            if (ang.jurusan) html += '<div class="konfirmasi-row"><span class="conf-label">Jurusan:</span><span class="conf-value">' + ang.jurusan + '</span></div>';
            html += '</div></div>';
        });

        html += '</div></div>';
        $('#konfirmasiContent').html(html);
    }

    window.toggleAccKonfirmasi = function (header) {
        var body = header.nextElementSibling;
        var open = body.style.display !== 'none';
        body.style.display = open ? 'none' : 'block';
    };

    // Simpan
    $('#btnSimpanPkl').on('click', function () {
        var $btn = $(this);
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Menyimpan...');

        $.ajax({
            url: urlStore, method: 'POST',
            data: Object.assign(csrf(), { payload: JSON.stringify(formData) }),
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function (res) {
                if (!res.success) {
                    Swal.fire({ icon: 'error', title: 'Gagal!', text: res.message, confirmButtonColor: 'var(--primary)' });
                    return;
                }
                Swal.fire({
                    icon: 'success', title: 'Berhasil!',
                    html: res.message + '<br><small style="color:#64748b">Email info login telah dikirim ke semua anggota.</small>',
                    confirmButtonText: '<i class="fab fa-whatsapp"></i> Buka WhatsApp',
                    showCancelButton: true, cancelButtonText: 'Ke Data PKL',
                    confirmButtonColor: '#25d366', cancelButtonColor: 'var(--primary)',
                }).then(function (r) {
                    if (r.isConfirmed && res.wa_url) {
                        window.open(res.wa_url, '_blank');
                    }
                    window.location.href = urlKembali;
                });
            },
            error: function (xhr) {
                Swal.fire({ icon: 'error', title: 'Gagal!', text: xhr.responseJSON?.message ?? 'Terjadi kesalahan.', confirmButtonColor: 'var(--primary)' });
            },
            complete: function () {
                $btn.prop('disabled', false).html('<i class="fas fa-save"></i> Simpan Data PKL');
            },
        });
    });

    $('#btnStep3Back').on('click', function () { goToStep(2); });

    // ── Edit PKL: Flatpickr tgl lahir ────────────────────────────
    if (document.getElementById('editTglLahir')) {
        flatpickr('#editTglLahir', {
            dateFormat: 'Y-m-d', altInput: true, altFormat: 'd M Y',
            locale: fpLocale, maxDate: 'today',
        });
    }

    // ── Edit PKL: Toggle show/hide password ──────────────────────
    $(document).on('click', '.btn-toggle-pw', function () {
        var input = document.getElementById($(this).data('target'));
        if (!input) return;
        var isPw = input.type === 'password';
        input.type = isPw ? 'text' : 'password';
        $(this).find('i').toggleClass('fa-eye fa-eye-slash');
    });

    // ── Edit PKL: AJAX submit ────────────────────────────────────
    $('#formEditPkl').on('submit', function (e) {
        e.preventDefault();
        var $btn = $('#btnSimpanEdit');
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Menyimpan...');

        $.ajax({
            url: $(this).attr('action'), method: 'POST',
            data: $(this).serialize(),
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function (res) {
                if (!res.success) {
                    Swal.fire({ icon: 'error', title: 'Gagal!', text: res.message, confirmButtonColor: 'var(--primary)' });
                    return;
                }
                Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: res.message, showConfirmButton: false, timer: 2500, timerProgressBar: true });
                setTimeout(function () { history.back(); }, 1500);
            },
            error: function (xhr) {
                Swal.fire({ icon: 'error', title: 'Gagal!', text: xhr.responseJSON?.message ?? 'Terjadi kesalahan.', confirmButtonColor: 'var(--primary)' });
            },
            complete: function () {
                $btn.prop('disabled', false).html('<i class="fas fa-save"></i> Simpan Perubahan');
            },
        });
    });

});
