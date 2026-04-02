/**
 * SIMMAG ODC — Profil PKL JS
 * public/assets/js/modules/pkl/profil.js
 *
 * Fitur:
 *   1. URL Param Helpers (setUrlParam, removeUrlParam, getUrlParam)
 *   2. Track currentEditMode + konfirmasi konflik antar section (SweetAlert)
 *   3. Inline edit: Informasi Pribadi (termasuk Flatpickr untuk tgl_lahir)
 *      — enter/exit sync URL param ?mode=edit_biodata
 *   4. Inline edit: Ubah Password + password strength indicator
 *      — enter/exit sync URL param ?mode=edit_password
 *   5. Auto-enter edit mode saat page load berdasarkan URL param ?mode=
 *      (refresh halaman tetap di mode edit)
 *   6. Toggle show/hide password
 *
 * Catatan Flatpickr:
 *   - Diinit LAZY saat pertama kali masuk edit mode
 *   - Input tgl_lahir dibungkus #wrapTglLahir agar show/hide bersih
 *   - altInput: true  → Flatpickr buat input tampilan "d M Y" di dalam wrapper
 *   - dateFormat: "Y-m-d" → value dikirim ke server tetap format Y-m-d
 */

document.addEventListener('DOMContentLoaded', function () {

    /* ══════════════════════════════════════════════════════════ */
    /* 1. URL PARAM HELPERS                                       */
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

    /* ══════════════════════════════════════════════════════════ */
    /* 2. TRACK MODE EDIT AKTIF                                   */
    /*                                                            */
    /* currentEditMode menyimpan section mana yang sedang edit:  */
    /*   null | 'biodata' | 'password'                           */
    /* Jika user klik Edit saat section lain terbuka → konfirmasi */
    /* SweetAlert sebelum berpindah.                              */
    /* ══════════════════════════════════════════════════════════ */

    var currentEditMode = null;

    /**
     * Cek apakah ada section lain yang sedang edit.
     * resolve(true) = lanjut berpindah, resolve(false) = batal.
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
    /* 3. INLINE EDIT: INFORMASI PRIBADI                          */
    /* ══════════════════════════════════════════════════════════ */

    var btnEditBiodata = document.getElementById('btnEditBiodata');
    var btnCancelBiodata = document.getElementById('btnCancelBiodata');
    var actionsBiodata = document.getElementById('actionsBiodata');

    // Field standar (display <-> input toggle)
    // tgl_lahir DIKECUALIKAN — dihandle via Flatpickr + #wrapTglLahir
    var biodataFields = [
        { displayId: 'displayNamaLengkap', inputId: 'inputNamaLengkap' },
        { displayId: 'displayNamaPanggilan', inputId: 'inputNamaPanggilan' },
        { displayId: 'displayJenisKelamin', inputId: 'inputJenisKelamin' },
        { displayId: 'displayNoWa', inputId: 'inputNoWa' },
        { displayId: 'displayTempatLahir', inputId: 'inputTempatLahir' },
        { displayId: 'displayAlamat', inputId: 'inputAlamat' },
        { displayId: 'displayJurusan', inputId: 'inputJurusan' },
    ].map(function (pair) {
        return {
            display: document.getElementById(pair.displayId),
            input: document.getElementById(pair.inputId),
        };
    }).filter(function (pair) {
        return pair.display !== null && pair.input !== null;
    });

    // Elemen khusus tgl_lahir (Flatpickr)
    var displayTglLahir = document.getElementById('displayTglLahir');
    var wrapTglLahir = document.getElementById('wrapTglLahir');
    var inputTglLahir = document.getElementById('inputTglLahir');

    // Snapshot nilai awal untuk cancel
    var biodataOriginal = {};
    var tglLahirOriginal = '';

    function snapshotBiodata() {
        biodataFields.forEach(function (pair) {
            if (pair.input) biodataOriginal[pair.input.name] = pair.input.value;
        });
        tglLahirOriginal = inputTglLahir ? (inputTglLahir.value || '') : '';
    }

    // Flatpickr instance (lazy — hanya dibuat saat edit mode pertama kali)
    var fpInstance = null;

    function initFlatpickr() {
        if (!inputTglLahir || fpInstance) return;

        fpInstance = flatpickr(inputTglLahir, {
            dateFormat: 'Y-m-d',
            altInput: true,
            altFormat: 'd M Y',
            defaultDate: inputTglLahir.value || null,
            allowInput: false,
            disableMobile: false,
            locale: {
                firstDayOfWeek: 1,
                weekdays: {
                    shorthand: ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'],
                    longhand: ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'],
                },
                months: {
                    shorthand: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                    longhand: ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
                },
            },
        });
    }

    function enterBiodataEdit() {
        // Snapshot nilai saat ini sebagai backup cancel
        snapshotBiodata();

        // Toggle field standar
        biodataFields.forEach(function (pair) {
            if (pair.display) pair.display.style.display = 'none';
            if (pair.input) pair.input.style.display = 'block';
        });

        // Toggle tgl_lahir + init Flatpickr (lazy)
        if (displayTglLahir) displayTglLahir.style.display = 'none';
        if (wrapTglLahir) wrapTglLahir.style.display = 'block';
        initFlatpickr();

        if (actionsBiodata) actionsBiodata.style.display = 'flex';
        if (btnEditBiodata) btnEditBiodata.style.display = 'none';

        var first = biodataFields.find(function (p) { return p.input; });
        if (first) first.input.focus();

        currentEditMode = 'biodata';
        setUrlParam('mode', 'edit_biodata');
    }

    function exitBiodataEdit(restore) {
        if (restore) {
            biodataFields.forEach(function (pair) {
                if (pair.input) pair.input.value = biodataOriginal[pair.input.name] || '';
            });
            if (fpInstance) {
                fpInstance.setDate(tglLahirOriginal || null, false);
            } else if (inputTglLahir) {
                inputTglLahir.value = tglLahirOriginal;
            }
        }

        biodataFields.forEach(function (pair) {
            if (pair.display) pair.display.style.display = 'flex';
            if (pair.input) pair.input.style.display = 'none';
        });

        if (displayTglLahir) displayTglLahir.style.display = 'flex';
        if (wrapTglLahir) wrapTglLahir.style.display = 'none';

        if (actionsBiodata) actionsBiodata.style.display = 'none';
        if (btnEditBiodata) btnEditBiodata.style.display = '';

        currentEditMode = null;
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
    /* 4. INLINE EDIT: PASSWORD                                   */
    /* ══════════════════════════════════════════════════════════ */

    var btnEditPassword = document.getElementById('btnEditPassword');
    var btnCancelPassword = document.getElementById('btnCancelPassword');
    var actionsPassword = document.getElementById('actionsPassword');
    var strengthEl = document.getElementById('passwordStrength');

    var pwDisplays = document.querySelectorAll('#formPassword .profil-field-display');
    var pwWraps = document.querySelectorAll('.profil-input-password-wrap');

    function enterPasswordEdit() {
        pwDisplays.forEach(function (el) { el.style.display = 'none'; });
        pwWraps.forEach(function (el) { el.style.display = 'flex'; });
        if (strengthEl) strengthEl.style.display = 'block';
        if (actionsPassword) actionsPassword.style.display = 'flex';
        if (btnEditPassword) btnEditPassword.style.display = 'none';

        var first = document.getElementById('inputPasswordBaru');
        if (first) first.focus();

        currentEditMode = 'password';
        setUrlParam('mode', 'edit_password');
    }

    function exitPasswordEdit() {
        pwDisplays.forEach(function (el) { el.style.display = 'flex'; });
        pwWraps.forEach(function (el) { el.style.display = 'none'; });
        if (strengthEl) strengthEl.style.display = 'none';
        if (actionsPassword) actionsPassword.style.display = 'none';
        if (btnEditPassword) btnEditPassword.style.display = '';

        var pb = document.getElementById('inputPasswordBaru');
        var pk = document.getElementById('inputKonfirmasi');
        if (pb) pb.value = '';
        if (pk) pk.value = '';
        resetStrength();

        currentEditMode = null;
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
    /* 5. AUTO-ENTER EDIT MODE SAAT PAGE LOAD                     */
    /*                                                            */
    /* Baca URL param ?mode= dan langsung masuk edit mode yang    */
    /* sesuai. Membuat refresh tetap di mode edit.                */
    /* ══════════════════════════════════════════════════════════ */

    var initMode = getUrlParam('mode');
    if (initMode === 'edit_biodata') {
        enterBiodataEdit();
    } else if (initMode === 'edit_password') {
        enterPasswordEdit();
    }

    /* ══════════════════════════════════════════════════════════ */
    /* 6. SHOW/HIDE PASSWORD                                      */
    /* ══════════════════════════════════════════════════════════ */

    document.querySelectorAll('.btn-toggle-pw').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var input = document.getElementById(btn.dataset.target);
            if (!input) return;
            var isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            var icon = btn.querySelector('i');
            if (icon) icon.className = isPassword ? 'fas fa-eye-slash' : 'fas fa-eye';
        });
    });

    /* ══════════════════════════════════════════════════════════ */
    /* 7. PASSWORD STRENGTH INDICATOR                             */
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
        if (strengthFill) strengthFill.className = 'strength-fill';
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

});