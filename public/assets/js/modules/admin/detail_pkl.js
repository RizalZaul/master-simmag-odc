/**
 * public/assets/js/modules/admin/detail_pkl.js
 *
 * Digunakan oleh DUA halaman:
 *   1. detail_pkl.php → toggleAnggota accordion
 *   2. edit_pkl.php   → AJAX submit, flatpickr tgl lahir, toggle pw, validasi pw
 *
 * A1-FIX: Handler AJAX #formEditPkl dipindahkan ke sini (bukan tambah_pkl.js)
 * agar edit page tidak memuat tambah_pkl.js yang men-crash karena Select2
 * dipanggil pada elemen yang tidak ada di edit page.
 *
 * NEW-BUG-FIX: Validasi password sebelum AJAX — min 8 karakter,
 * huruf besar, huruf kecil, angka, simbol (sinkron dengan profil & login).
 */

/* ── Accordion Anggota (Detail Page) ─────────────────────────────────── */

window.toggleAnggota = function (header) {
    var body = header.nextElementSibling;
    var icon = header.querySelector('.toggle-icon');
    // display === '' (belum ada inline style) → dianggap tampil (open)
    var open = body.style.display !== 'none';
    body.style.display = open ? 'none' : 'block';
    if (icon) {
        icon.className = 'fas fa-chevron-' + (open ? 'down' : 'up') + ' toggle-icon';
    }
};

/* ── Inisialisasi Edit Page ───────────────────────────────────────────── */

$(document).ready(function () {

    // Hanya jalankan blok edit jika form edit ada di halaman
    if (!document.getElementById('formEditPkl')) return;

    if (window.SimmagValidation && typeof window.SimmagValidation.applyInputRules === 'function') {
        window.SimmagValidation.applyInputRules([
            { selector: '#formEditPkl [name="nama_lengkap"]', rule: 'person_name', label: 'Nama Lengkap' },
            { selector: '#formEditPkl [name="nama_panggilan"]', rule: 'nickname', label: 'Nama Panggilan' },
            { selector: '#formEditPkl [name="tempat_lahir"]', rule: 'city', label: 'Tempat Lahir' },
            { selector: '#formEditPkl [name="alamat"]', rule: 'address', label: 'Alamat' },
            { selector: '#formEditPkl [name="no_wa"]', rule: 'phone', label: 'No WA' },
            { selector: '#formEditPkl [name="email"]', rule: 'email', label: 'Email' },
            { selector: '#formEditPkl [name="jurusan"]', rule: 'jurusan', label: 'Jurusan' }
        ]);
    }

    /* ── Flatpickr — Tanggal Lahir ─────────────────────────────────── */
    if (document.getElementById('editTglLahir')) {
        flatpickr('#editTglLahir', {
            dateFormat: 'Y-m-d',
            altInput: true,
            altFormat: 'd M Y',
            maxDate: 'today',
            locale: {
                firstDayOfWeek: 1,
                months: {
                    shorthand: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
                    longhand: ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
                },
                weekdays: {
                    shorthand: ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'],
                    longhand: ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'],
                },
            },
        });
    }

    /* ── Toggle Show/Hide Password ─────────────────────────────────── */
    $(document).on('click', '.btn-toggle-pw', function () {
        var targetId = $(this).data('target');
        var input = document.getElementById(targetId);
        if (!input) return;
        input.type = input.type === 'password' ? 'text' : 'password';
        $(this).find('i').toggleClass('fa-eye fa-eye-slash');
    });

    /* ── Validasi Password ─────────────────────────────────────────── */
    // Kosong = tidak diubah → lolos. Terisi → wajib memenuhi semua aturan.
    // Aturan sinkron dengan profil admin & halaman login:
    //   min 8 karakter | huruf kapital | huruf kecil | angka | simbol
    function validatePassword(pw) {
        if (!pw || pw.length === 0) return null; // skip, tidak diubah

        var errors = [];
        if (pw.length < 8) errors.push('minimal 8 karakter');
        if (!/[A-Z]/.test(pw)) errors.push('minimal 1 huruf kapital (A-Z)');
        if (!/[a-z]/.test(pw)) errors.push('minimal 1 huruf kecil (a-z)');
        if (!/[0-9]/.test(pw)) errors.push('minimal 1 angka (0-9)');
        if (!/[^A-Za-z0-9]/.test(pw)) errors.push('minimal 1 simbol (mis. @, #, !, _)');

        return errors.length > 0 ? errors : null;
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

    function getSelectedJenisKelamin() {
        return $.trim($('#formEditPkl input[name="jenis_kelamin"]:checked').val() || '');
    }

    /* ── AJAX Submit Form Edit PKL ─────────────────────────────────── */
    $('#formEditPkl').on('submit', function (e) {
        e.preventDefault();

        var $form = $(this);
        var $jurusanField = $form.find('[name="jurusan"]');
        var missingFields = [];
        var v = window.SimmagValidation || {};

        if (!$.trim($form.find('[name="nama_lengkap"]').val())) missingFields.push('Nama Lengkap');
        if (!$.trim($form.find('[name="nama_panggilan"]').val())) missingFields.push('Nama Panggilan');
        if (!$.trim($form.find('[name="tempat_lahir"]').val())) missingFields.push('Tempat Lahir');
        if (!$.trim($form.find('[name="tgl_lahir"]').val())) missingFields.push('Tanggal Lahir');
        if (!$.trim($form.find('[name="alamat"]').val())) missingFields.push('Alamat');
        if (!$.trim($form.find('[name="no_wa"]').val())) missingFields.push('No WA');
        if (!getSelectedJenisKelamin()) missingFields.push('Jenis Kelamin');
        if (!$.trim($form.find('[name="email"]').val())) missingFields.push('Email');
        if ($jurusanField.length && !$.trim($jurusanField.val())) missingFields.push('Jurusan');

        if (missingFields.length) {
            Swal.fire({
                icon: 'warning',
                title: 'Lengkapi Data',
                text: buildMissingFieldsMessage(missingFields, $jurusanField.length ? 9 : 8),
                confirmButtonColor: 'var(--primary)',
            });
            return;
        }

        var fieldError = (v.validatePatternField ? v.validatePatternField('Nama Lengkap', $form.find('[name="nama_lengkap"]').val(), 1, 100, /^[\p{L}\s.,'-]+$/u, 'huruf, spasi, titik, koma, apostrof, dan tanda hubung') : '')
            || (v.validateLooseField ? v.validateLooseField('Nama Panggilan', $form.find('[name="nama_panggilan"]').val(), 1, 10) : '')
            || (v.validatePatternField ? v.validatePatternField('Tempat Lahir', $form.find('[name="tempat_lahir"]').val(), 1, 50, /^[\p{L}\s]+$/u, 'huruf dan spasi') : '')
            || (v.validateDateOnly ? v.validateDateOnly($form.find('[name="tgl_lahir"]').val(), 'Tanggal Lahir') : '')
            || (v.validatePatternField ? v.validatePatternField('Alamat', $form.find('[name="alamat"]').val(), 5, 100, /^[\p{L}0-9\s'.,\-\/#+]+$/u, 'huruf, angka, spasi, apostrof, tanda hubung, titik, koma, garis miring, dan tanda angka (#)') : '')
            || (v.validatePhone ? v.validatePhone($form.find('[name="no_wa"]').val(), 'No WA') : '')
            || (!getSelectedJenisKelamin() ? 'Jenis Kelamin wajib diisi.' : '')
            || ($jurusanField.length && v.validatePatternField ? v.validatePatternField('Jurusan', $jurusanField.val(), 2, 100, /^[\p{L}\s.()\-]+$/u, 'huruf, spasi, titik, tanda hubung, dan tanda kurung') : '')
            || (v.validateEmail ? v.validateEmail($form.find('[name="email"]').val(), 'Email') : '');

        if (fieldError) {
            Swal.fire({
                icon: 'warning',
                title: 'Periksa Data',
                text: fieldError,
                confirmButtonColor: 'var(--primary)',
            });
            return;
        }

        // Validasi password client-side sebelum kirim ke server
        var pw = $('#editPasswordBaru').val();
        var passwordError = pw.trim()
            ? ((window.SimmagValidation && window.SimmagValidation.validatePassword)
                ? window.SimmagValidation.validatePassword(pw.trim())
                : null)
            : null;
        if (passwordError) {
            Swal.fire({
                icon: 'warning',
                title: 'Password Tidak Valid',
                text: passwordError,
                confirmButtonColor: 'var(--primary)',
            });
            return;
        }

        if (v.normalizeSpaces) {
            ['nama_lengkap', 'nama_panggilan', 'tempat_lahir', 'alamat', 'jurusan'].forEach(function (fieldName) {
                var $field = $form.find('[name="' + fieldName + '"]');
                if ($field.length) {
                    $field.val(v.normalizeSpaces($field.val()));
                }
            });
        }

        var $btn = $('#btnSimpanEdit');
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Menyimpan...');

        $.ajax({
            url: $form.attr('action'),
            method: 'POST',
            data: $form.serialize(),
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function (res) {
                if (!res.success) {
                    Swal.fire({
                        icon: 'error', title: 'Gagal!',
                        text: res.message, confirmButtonColor: 'var(--primary)',
                    });
                    return;
                }
                Swal.fire({
                    toast: true, position: 'top-end', icon: 'success',
                    title: res.message, showConfirmButton: false,
                    timer: 2500, timerProgressBar: true,
                });
                setTimeout(function () { history.back(); }, 1500);
            },
            error: function (xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message)
                    ? xhr.responseJSON.message
                    : 'Terjadi kesalahan. Coba lagi.';
                Swal.fire({ icon: 'error', title: 'Gagal!', text: msg, confirmButtonColor: 'var(--primary)' });
            },
            complete: function () {
                $btn.prop('disabled', false).html('<i class="fas fa-save"></i> Simpan Perubahan');
            },
        });
    });

});
