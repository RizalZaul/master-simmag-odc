/**
 * SIMMAG ODC — Profil Admin JS
 * public/assets/js/modules/profil.js
 */

document.addEventListener('DOMContentLoaded', function () {
    if (window.SimmagValidation && typeof window.SimmagValidation.applyInputRules === 'function') {
        window.SimmagValidation.applyInputRules([
            { selector: '#inputNamaLengkap', rule: 'person_name', label: 'Nama Lengkap' },
            { selector: '#inputNamaPanggilan', rule: 'nickname', label: 'Nama Panggilan' },
            { selector: '#inputEmail', rule: 'email', label: 'Email' },
            { selector: '#inputNoWa', rule: 'phone', label: 'No WA' },
            { selector: '#inputAlamat', rule: 'multiline_address', label: 'Alamat' }
        ]);
    }

    /* ══════════════════════════════════════════════════════════ */
    /* 1. TAB SWITCHING + UPDATE HEADING                          */
    /* ══════════════════════════════════════════════════════════ */

    const tabBtns = document.querySelectorAll('.profil-tab-btn');
    const tabContents = document.querySelectorAll('.profil-tab-content');
    const elHeading = document.querySelector('.page-heading');
    const elSubheading = document.querySelector('.page-subheading');
    const elHeaderH1 = document.querySelector('.page-title');

    const tabMeta = {
        'biodata': { heading: 'Profil Saya', subheading: 'Data diri dan informasi akun' },
        'setting': { heading: 'Pengaturan', subheading: 'Pengaturan form biodata siswa PKL' },
    };

    tabBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            var target = btn.dataset.tab;

            tabBtns.forEach(function (b) { b.classList.remove('active'); });
            tabContents.forEach(function (c) { c.classList.remove('active'); });
            btn.classList.add('active');

            var targetEl = document.getElementById('tab-' + target);
            if (targetEl) targetEl.classList.add('active');

            var meta = tabMeta[target];
            if (meta) {
                if (elHeading) elHeading.textContent = meta.heading;
                if (elSubheading) elSubheading.textContent = meta.subheading;
                if (elHeaderH1) elHeaderH1.textContent = meta.heading;
                document.title = meta.heading + ' — SIMMAG ODC';
            }

            // Sinkron URL tanpa reload
            setUrlParam('tab', target);
            // Saat pindah tab, hapus mode edit dari URL agar tidak membingungkan
            removeUrlParam('mode');
        });
    });

    /* ══════════════════════════════════════════════════════════ */
    /* 2. URL PARAM HELPERS                                       */
    /* ══════════════════════════════════════════════════════════ */

    function setUrlParam(key, value) {
        var url = new URL(window.location.href);
        url.searchParams.set(key, value);
        window.history.replaceState(null, '', url.toString());
    }

    function removeUrlParam(key) {
        var url = new URL(window.location.href);
        url.searchParams.delete(key);
        window.history.replaceState(null, '', url.toString());
    }

    function getUrlParam(key) {
        return new URL(window.location.href).searchParams.get(key);
    }

    function buildMissingFieldsMessage(missingFields, totalRequired) {
        var labels = Array.from(new Set((missingFields || []).filter(Boolean)));
        if (!labels.length) return 'Semua field harus diisi.';
        if (totalRequired && labels.length >= totalRequired) return 'Semua field harus diisi.';
        if (labels.length === 1) return labels[0] + ' wajib diisi.';
        return 'Field berikut wajib diisi: ' + labels.join(', ') + '.';
    }

    function isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(String(email || '').trim());
    }

    /* ══════════════════════════════════════════════════════════ */
    /* 3. TRACK MODE EDIT AKTIF                                   */
    /*                                                            */
    /* A6-FIX & konflik dua tombol Edit:                          */
    /* - currentEditMode menyimpan section mana yang sedang edit  */
    /* - Setiap enter/exit edit selalu sync URL param 'mode'      */
    /*   sehingga refresh tetap di mode yang sama                 */
    /* - Jika user klik Edit saat section lain sudah terbuka,     */
    /*   muncul konfirmasi SweetAlert sebelum berpindah           */
    /* ══════════════════════════════════════════════════════════ */

    var currentEditMode = null; // null | 'biodata' | 'password'

    /**
     * Cek apakah ada section lain yang sedang edit.
     * Jika ada → tampilkan konfirmasi, resolve(true) = lanjut, resolve(false) = batal.
     */
    function confirmSwitchEdit(targetMode) {
        return new Promise(function (resolve) {
            if (!currentEditMode || currentEditMode === targetMode) {
                resolve(true);
                return;
            }

            var sectionLabel = currentEditMode === 'biodata' ? 'Informasi Pribadi' : 'Ubah Password';
            Swal.fire({
                icon: 'question',
                title: 'Batalkan perubahan?',
                html: 'Form <strong>' + sectionLabel + '</strong> sedang terbuka dan belum disimpan.'
                    + '<br>Batalkan dan buka form yang lain?',
                showCancelButton: true,
                confirmButtonText: '<i class="fas fa-times"></i> Ya, Batalkan',
                cancelButtonText: 'Tidak, Tetap Di Sini',
                confirmButtonColor: '#ef4444',
                cancelButtonColor: 'var(--primary)',
                reverseButtons: true,
            }).then(function (result) {
                resolve(result.isConfirmed);
            });
        });
    }

    /* ══════════════════════════════════════════════════════════ */
    /* 4. INLINE EDIT: BIODATA                                    */
    /* ══════════════════════════════════════════════════════════ */

    var btnEditBiodata = document.getElementById('btnEditBiodata');
    var btnCancelBiodata = document.getElementById('btnCancelBiodata');
    var actionsBiodata = document.getElementById('actionsBiodata');

    var biodataFields = [
        { display: document.getElementById('displayNamaLengkap'), input: document.getElementById('inputNamaLengkap') },
        { display: document.getElementById('displayNamaPanggilan'), input: document.getElementById('inputNamaPanggilan') },
        { display: document.getElementById('displayEmail'), input: document.getElementById('inputEmail') },
        { display: document.getElementById('displayNoWa'), input: document.getElementById('inputNoWa') },
        { display: document.getElementById('displayAlamat'), input: document.getElementById('inputAlamat') },
    ];

    var biodataOriginal = {};

    function enterBiodataEdit() {
        // Snapshot nilai saat ini sebagai backup cancel
        biodataFields.forEach(function (pair) {
            if (pair.input) biodataOriginal[pair.input.name] = pair.input.value;
        });
        biodataFields.forEach(function (pair) {
            if (pair.display) pair.display.style.display = 'none';
            if (pair.input) pair.input.style.display = 'block';
        });
        actionsBiodata.style.display = 'flex';
        btnEditBiodata.style.display = 'none';
        if (biodataFields[0].input) biodataFields[0].input.focus();

        currentEditMode = 'biodata';
        // A6-FIX: persist mode ke URL agar refresh tetap di edit
        setUrlParam('mode', 'edit_biodata');
    }

    function exitBiodataEdit(restore) {
        if (restore) {
            biodataFields.forEach(function (pair) {
                if (pair.input) pair.input.value = biodataOriginal[pair.input.name] || '';
            });
        }
        biodataFields.forEach(function (pair) {
            if (pair.display) pair.display.style.display = 'flex';
            if (pair.input) pair.input.style.display = 'none';
        });
        actionsBiodata.style.display = 'none';
        btnEditBiodata.style.display = '';

        currentEditMode = null;
        // A6-FIX: hapus mode dari URL saat keluar edit
        removeUrlParam('mode');
    }

    if (btnEditBiodata) {
        btnEditBiodata.addEventListener('click', function () {
            confirmSwitchEdit('biodata').then(function (confirmed) {
                if (!confirmed) return;
                // Jika ada password edit yang terbuka, tutup dulu tanpa save
                if (currentEditMode === 'password') exitPasswordEdit();
                enterBiodataEdit();
            });
        });
    }

    if (btnCancelBiodata) {
        btnCancelBiodata.addEventListener('click', function () { exitBiodataEdit(true); });
    }

    /* ══════════════════════════════════════════════════════════ */
    /* 5. INLINE EDIT: PASSWORD                                   */
    /* ══════════════════════════════════════════════════════════ */

    var btnEditPassword = document.getElementById('btnEditPassword');
    var btnCancelPassword = document.getElementById('btnCancelPassword');
    var actionsPassword = document.getElementById('actionsPassword');

    var pwDisplays = document.querySelectorAll('#formPassword .profil-field-display');
    var pwWraps = document.querySelectorAll('.profil-input-password-wrap');
    var strengthEl = document.getElementById('passwordStrength');

    function enterPasswordEdit() {
        pwDisplays.forEach(function (el) { el.style.display = 'none'; });
        pwWraps.forEach(function (el) { el.style.display = 'flex'; });
        if (strengthEl) strengthEl.style.display = 'block';
        actionsPassword.style.display = 'flex';
        btnEditPassword.style.display = 'none';
        var firstInput = document.getElementById('inputPasswordBaru');
        if (firstInput) firstInput.focus();

        currentEditMode = 'password';
        // A6-FIX: persist mode ke URL
        setUrlParam('mode', 'edit_password');
    }

    function exitPasswordEdit() {
        pwDisplays.forEach(function (el) { el.style.display = 'flex'; });
        pwWraps.forEach(function (el) { el.style.display = 'none'; });
        if (strengthEl) strengthEl.style.display = 'none';
        actionsPassword.style.display = 'none';
        btnEditPassword.style.display = '';
        var pb = document.getElementById('inputPasswordBaru');
        var pk = document.getElementById('inputKonfirmasi');
        if (pb) pb.value = '';
        if (pk) pk.value = '';
        resetStrength();

        currentEditMode = null;
        // A6-FIX: hapus mode dari URL
        removeUrlParam('mode');
    }

    if (btnEditPassword) {
        btnEditPassword.addEventListener('click', function () {
            confirmSwitchEdit('password').then(function (confirmed) {
                if (!confirmed) return;
                // Jika ada biodata edit yang terbuka, tutup dulu tanpa save
                if (currentEditMode === 'biodata') exitBiodataEdit(true);
                enterPasswordEdit();
            });
        });
    }

    if (btnCancelPassword) {
        btnCancelPassword.addEventListener('click', function () { exitPasswordEdit(); });
    }

    /* ══════════════════════════════════════════════════════════ */
    /* 6. A6-FIX: AUTO-ENTER EDIT MODE SAAT PAGE LOAD            */
    /*                                                            */
    /* Baca URL param ?mode= dan langsung masuk edit mode yang    */
    /* sesuai. Ini membuat refresh tetap di mode edit.            */
    /* ══════════════════════════════════════════════════════════ */

    var initMode = getUrlParam('mode');
    if (initMode === 'edit_biodata') {
        enterBiodataEdit();
    } else if (initMode === 'edit_password') {
        enterPasswordEdit();
    }

    /* ══════════════════════════════════════════════════════════ */
    /* 7. SHOW/HIDE PASSWORD                                      */
    /* ══════════════════════════════════════════════════════════ */

    document.querySelectorAll('.btn-toggle-pw').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var targetId = btn.dataset.target;
            var input = document.getElementById(targetId);
            if (!input) return;
            var isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            var icon = btn.querySelector('i');
            if (icon) icon.className = isPassword ? 'fas fa-eye-slash' : 'fas fa-eye';
        });
    });

    /* ══════════════════════════════════════════════════════════ */
    /* 8. PASSWORD STRENGTH INDICATOR                             */
    /* ══════════════════════════════════════════════════════════ */

    var inputPasswordBaru = document.getElementById('inputPasswordBaru');
    var strengthFill = document.getElementById('strengthFill');

    var rules = {
        'rule-length': function (v) { return v.length >= 8; },
        'rule-upper': function (v) { return /[A-Z]/.test(v); },
        'rule-lower': function (v) { return /[a-z]/.test(v); },
        'rule-number': function (v) { return /[0-9]/.test(v); },
        'rule-symbol': function (v) { return /[\W_]/.test(v); },
    };

    function resetStrength() {
        if (!strengthFill) return;
        strengthFill.className = 'strength-fill';
        Object.keys(rules).forEach(function (id) {
            var li = document.getElementById(id);
            if (li) li.classList.remove('passed');
        });
    }

    if (inputPasswordBaru) {
        inputPasswordBaru.addEventListener('input', function () {
            var val = inputPasswordBaru.value;
            var score = 0;
            Object.keys(rules).forEach(function (id) {
                var li = document.getElementById(id);
                var passes = rules[id](val);
                if (li) passes ? li.classList.add('passed') : li.classList.remove('passed');
                if (passes) score++;
            });
            if (!strengthFill) return;
            strengthFill.className = 'strength-fill';
            if (score <= 1) strengthFill.classList.add('weak');
            else if (score === 2) strengthFill.classList.add('fair');
            else if (score <= 4) strengthFill.classList.add('good');
            else strengthFill.classList.add('strong');
        });
    }

    var formBiodata = document.getElementById('formBiodata');
    if (formBiodata) {
        formBiodata.addEventListener('submit', function (event) {
            var v = window.SimmagValidation || {};
            var missingFields = [];
            var namaLengkap = document.getElementById('inputNamaLengkap')?.value || '';
            var namaPanggilan = document.getElementById('inputNamaPanggilan')?.value || '';
            var email = document.getElementById('inputEmail')?.value || '';
            var noWa = document.getElementById('inputNoWa')?.value || '';
            var alamat = document.getElementById('inputAlamat')?.value || '';

            if (!namaLengkap.trim()) missingFields.push('Nama Lengkap');
            if (!namaPanggilan.trim()) missingFields.push('Nama Panggilan');
            if (!email.trim()) missingFields.push('Email');
            if (!noWa.trim()) missingFields.push('No WA');
            if (!alamat.trim()) missingFields.push('Alamat');

            if (missingFields.length) {
                event.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: 'Lengkapi Data',
                    text: buildMissingFieldsMessage(missingFields, 5),
                    confirmButtonColor: 'var(--primary)',
                });
                return;
            }

            var fieldError = (v.validatePatternField ? v.validatePatternField('Nama Lengkap', namaLengkap, 1, 100, /^[\p{L}\s.,'-]+$/u, 'huruf, spasi, titik, koma, apostrof, dan tanda hubung') : '')
                || (v.validateLooseField ? v.validateLooseField('Nama Panggilan', namaPanggilan, 1, 10) : '')
                || (v.validateEmail ? v.validateEmail(email, 'Email') : '')
                || (v.validatePhone ? v.validatePhone(noWa, 'No WA') : '')
                || (v.validateMultilinePatternField ? v.validateMultilinePatternField('Alamat', alamat, 5, 100, /^[\p{L}0-9\s'.,\-\/#+]+$/u, 'huruf, angka, spasi, apostrof, tanda hubung, titik, koma, garis miring, tanda angka (#), dan baris baru') : '');

            if (fieldError) {
                event.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: 'Periksa Data',
                    text: fieldError,
                    confirmButtonColor: 'var(--primary)',
                });
                return;
            }

            if (v.normalizeSpaces) {
                document.getElementById('inputNamaLengkap').value = v.normalizeSpaces(namaLengkap);
                document.getElementById('inputNamaPanggilan').value = v.normalizeSpaces(namaPanggilan);
                document.getElementById('inputAlamat').value = v.normalizeMultilineValue ? v.normalizeMultilineValue(alamat) : $.trim(alamat);
            }
        });
    }

    var formPassword = document.getElementById('formPassword');
    if (formPassword) {
        formPassword.addEventListener('submit', function (event) {
            var passwordBaru = document.getElementById('inputPasswordBaru')?.value || '';
            var konfirmasi = document.getElementById('inputKonfirmasi')?.value || '';
            var missingFields = [];

            if (!passwordBaru.trim()) missingFields.push('Password Baru');
            if (!konfirmasi.trim()) missingFields.push('Konfirmasi Password');

            if (missingFields.length) {
                event.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: 'Lengkapi Data',
                    text: buildMissingFieldsMessage(missingFields, 2),
                    confirmButtonColor: 'var(--primary)',
                });
                return;
            }

            var passwordError = window.SimmagValidation && window.SimmagValidation.validatePassword
                ? window.SimmagValidation.validatePassword(passwordBaru, konfirmasi)
                : '';
            if (passwordError) {
                event.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: 'Periksa Data',
                    text: passwordError,
                    confirmButtonColor: 'var(--primary)',
                });
            }
        });
    }

    /* ══════════════════════════════════════════════════════════ */
    /* 9. TOGGLE FORM BIODATA PKL (AJAX)                          */
    /* ══════════════════════════════════════════════════════════ */

    var toggleBiodata = document.getElementById('toggleBiodataPkl');
    var toggleLabel = document.getElementById('toggleLabel');
    var settingStatusInfo = document.getElementById('settingStatusInfo');
    var settingStatusText = document.getElementById('settingStatusText');

    if (toggleBiodata) {
        toggleBiodata.addEventListener('change', function () {
            var url = toggleBiodata.dataset.url;

            fetch(url, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: getCsrfBody(),
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (!data.success) {
                        toggleBiodata.checked = !toggleBiodata.checked;
                        Swal.fire({ icon: 'error', title: 'Gagal!', text: 'Terjadi kesalahan, coba lagi.', confirmButtonColor: 'var(--primary)' });
                        return;
                    }
                    if (toggleLabel) toggleLabel.textContent = data.label;

                    if (settingStatusInfo) {
                        settingStatusInfo.className = 'setting-status-info ' + (data.aktif ? 'info-aktif' : 'info-nonaktif');
                        var icon = settingStatusInfo.querySelector('i');
                        if (icon) icon.className = 'fas ' + (data.aktif ? 'fa-check-circle' : 'fa-times-circle');
                    }
                    if (settingStatusText) {
                        settingStatusText.innerHTML = data.aktif
                            ? 'Form biodata PKL sedang <strong>terbuka</strong>. Siswa dapat mengisi dan mengubah data mereka.'
                            : 'Form biodata PKL sedang <strong>ditutup</strong>. Siswa tidak dapat mengakses form biodata.';
                    }

                    Swal.fire({
                        toast: true, position: 'top-end', icon: 'success',
                        title: data.aktif ? 'Form biodata diaktifkan' : 'Form biodata dinonaktifkan',
                        showConfirmButton: false, timer: 2500, timerProgressBar: true,
                    });
                })
                .catch(function () {
                    toggleBiodata.checked = !toggleBiodata.checked;
                    Swal.fire({ icon: 'error', title: 'Gagal!', text: 'Tidak dapat terhubung ke server.', confirmButtonColor: 'var(--primary)' });
                });
        });
    }

    function getCsrfBody() {
        var body = '';
        document.querySelectorAll('input[type="hidden"]').forEach(function (inp) {
            if (inp.name && inp.name.indexOf('csrf') !== -1) {
                body = encodeURIComponent(inp.name) + '=' + encodeURIComponent(inp.value);
            }
        });
        return body;
    }

    /* ══════════════════════════════════════════════════════════ */
    /* 10. GENERATE TOKEN BIODATA PKL                             */
    /* ══════════════════════════════════════════════════════════ */

    var btnGenToken = document.getElementById('btnGenerateToken');
    if (btnGenToken) {
        btnGenToken.addEventListener('click', function () {
            var url = btnGenToken.dataset.url;

            Swal.fire({
                icon: 'warning',
                title: 'Generate Token Baru?',
                html: 'Link lama yang sudah dibagikan <strong>tidak akan bisa diakses lagi</strong>.<br>Yakin ingin membuat link baru?',
                showCancelButton: true,
                confirmButtonText: '<i class="fas fa-rotate"></i> Ya, Generate',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#ef4444',
                cancelButtonColor: 'var(--primary)',
                reverseButtons: true,
            }).then(function (result) {
                if (!result.isConfirmed) return;

                fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: getCsrfBody(),
                })
                    .then(function (res) { return res.json(); })
                    .then(function (data) {
                        if (!data.success) {
                            Swal.fire({ icon: 'error', title: 'Gagal!', text: 'Gagal generate token.', confirmButtonColor: 'var(--primary)' });
                            return;
                        }

                        // Update input link tanpa reload
                        var linkInput = document.getElementById('biodataLinkInput');
                        if (linkInput) {
                            linkInput.value = data.link;
                        }

                        Swal.fire({
                            toast: true, position: 'top-end', icon: 'success',
                            title: 'Token baru berhasil dibuat!',
                            showConfirmButton: false, timer: 2500, timerProgressBar: true,
                        });
                    })
                    .catch(function () {
                        Swal.fire({ icon: 'error', title: 'Gagal!', text: 'Tidak dapat terhubung ke server.', confirmButtonColor: 'var(--primary)' });
                    });
            });
        });
    }

    // Copy link biodata
    var btnCopyLink = document.getElementById('btnCopyBiodataLink');
    if (btnCopyLink) {
        btnCopyLink.addEventListener('click', function () {
            var input = document.getElementById('biodataLinkInput');
            if (!input) return;
            input.select();
            navigator.clipboard.writeText(input.value).then(function () {
                Swal.fire({
                    toast: true, position: 'top-end', icon: 'success',
                    title: 'Link disalin ke clipboard!',
                    showConfirmButton: false, timer: 1800, timerProgressBar: true,
                });
            }).catch(function () {
                document.execCommand('copy');
                Swal.fire({
                    toast: true, position: 'top-end', icon: 'success',
                    title: 'Link disalin!',
                    showConfirmButton: false, timer: 1800, timerProgressBar: true,
                });
            });
        });
    }

});
