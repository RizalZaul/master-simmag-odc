/**
 * SIMMAG ODC - Detail / Ubah Tugas Admin JS
 */

$(document).ready(function () {
    if ($('#editTugasKategori').length && typeof $.fn.select2 === 'function') {
        $('#editTugasKategori').select2({
            placeholder: '-- Pilih Kategori --',
            allowClear: false,
            width: '100%'
        });
    }

    if ($('#editTugasDeadline').length) {
        flatpickr('#editTugasDeadline', {
            enableTime: true,
            time_24hr: true,
            dateFormat: 'Y-m-d H:i',
            altInput: true,
            minDate: 'today',
            altFormat: 'H:i \\— d M Y',
            locale: {
                months: {
                    shorthand: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
                    longhand: ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember']
                },
                weekdays: {
                    shorthand: ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'],
                    longhand: ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu']
                }
            }
        });
    }

    $('#formEditTugas').on('submit', function (e) {
        e.preventDefault();

        var $form = $(this);
        var $btn = $('#btnSimpanEditTugas');
        var originalHtml = $btn.html();

        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Menyimpan...');

        $.ajax({
            url: $form.attr('action'),
            method: 'POST',
            data: $form.serialize(),
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function (res) {
                if (!res || !res.success) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal!',
                        text: res && res.message ? res.message : 'Gagal memperbarui tugas.',
                        confirmButtonColor: 'var(--primary)'
                    });
                    return;
                }

                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: res.message || 'Tugas berhasil diperbarui.',
                    showConfirmButton: false,
                    timer: 2200,
                    timerProgressBar: true
                });

                setTimeout(function () {
                    window.location.href = res.redirect_url || window.location.href;
                }, 700);
            },
            error: function (xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal!',
                    text: xhr.responseJSON?.message || 'Terjadi kesalahan jaringan.',
                    confirmButtonColor: 'var(--primary)'
                });
            },
            complete: function () {
                $btn.prop('disabled', false).html(originalHtml);
            }
        });
    });
});
