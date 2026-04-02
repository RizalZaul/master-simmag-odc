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

    /* ── AJAX Submit Form Edit PKL ─────────────────────────────────── */
    $('#formEditPkl').on('submit', function (e) {
        e.preventDefault();

        // Validasi password client-side sebelum kirim ke server
        var pw = $('#editPasswordBaru').val();
        var pwErrors = validatePassword(pw.trim());
        if (pwErrors) {
            Swal.fire({
                icon: 'warning',
                title: 'Password Tidak Valid',
                html: 'Password baru harus memenuhi:<br>'
                    + '<ul style="text-align:left;margin:10px 0 0;padding-left:20px">'
                    + pwErrors.map(function (err) { return '<li>' + err + '</li>'; }).join('')
                    + '</ul>',
                confirmButtonColor: 'var(--primary)',
            });
            return;
        }

        var $form = $(this);
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