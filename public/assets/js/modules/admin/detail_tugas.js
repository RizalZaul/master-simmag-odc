/**
 * SIMMAG ODC - Detail / Ubah Tugas Admin JS
 */

$(document).ready(function () {
    if (window.SimmagValidation && typeof window.SimmagValidation.applyInputRules === 'function') {
        window.SimmagValidation.applyInputRules([
            { selector: '#formEditTugas [name="nama_tugas"]', rule: 'name_code', label: 'Nama Tugas' },
            { selector: '#formEditTugas [name="deskripsi"]', rule: 'loose_text', label: 'Deskripsi / Instruksi' },
            { selector: '#formEditTugas [name="target_jumlah"]', rule: 'numeric', label: 'Target Jumlah Item' }
        ]);
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
        var missingFields = [];
        var kategori = $.trim(String($('#editTugasKategori').val() || ''));
        var nama = $.trim(String($form.find('[name="nama_tugas"]').val() || ''));
        var target = $.trim(String($form.find('[name="target_jumlah"]').val() || ''));
        var deadline = $.trim(String($form.find('[name="deadline"]').val() || ''));
        var deskripsi = $.trim(String($form.find('[name="deskripsi"]').val() || ''));
        var createdAt = String($form.attr('data-created-at') || '');
        var v = window.SimmagValidation || {};

        if (!kategori) missingFields.push('Kategori Tugas');
        if (!nama) missingFields.push('Nama Tugas');
        if (!target || parseInt(target, 10) < 1) missingFields.push('Target Jumlah Item');
        if (!deadline) missingFields.push('Tenggat Waktu (Deadline)');
        if (!deskripsi) missingFields.push('Deskripsi / Instruksi');

        if (missingFields.length) {
            Swal.fire({
                icon: 'warning',
                title: 'Lengkapi Data',
                text: buildMissingFieldsMessage(missingFields, 5),
                confirmButtonColor: 'var(--primary)'
            });
            return;
        }

        var fieldError = (v.validatePatternField ? v.validatePatternField('Nama Tugas', $form.find('[name="nama_tugas"]').val(), 3, 50, /^[\p{L}0-9\s]+$/u, 'huruf, angka, dan spasi') : '')
            || (v.validateNumberRange ? v.validateNumberRange(target, 'Target Jumlah Item', 1) : '')
            || (v.validateDateTime ? v.validateDateTime(deadline, 'Tenggat Waktu (Deadline)') : '')
            || (v.validatePatternField ? v.validatePatternField('Deskripsi / Instruksi', $form.find('[name="deskripsi"]').val(), 10, 255, /^[\p{L}\p{N}\s\p{P}\p{Sc}\p{Sk}]+$/u, 'huruf, angka, spasi, dan tanda baca') : '');

        if (fieldError) {
            Swal.fire({
                icon: 'warning',
                title: 'Periksa Data',
                text: fieldError,
                confirmButtonColor: 'var(--primary)'
            });
            return;
        }

        var minBase = createdAt ? new Date(createdAt.replace(' ', 'T')) : new Date();
        if (isNaN(minBase.getTime())) {
            minBase = new Date();
        }
        var minimumDeadline = Math.max(Date.now(), minBase.getTime() + (30 * 60 * 1000));
        var deadlineDate = new Date(deadline.replace(' ', 'T'));
        if (isNaN(deadlineDate.getTime()) || deadlineDate.getTime() < minimumDeadline) {
            Swal.fire({
                icon: 'warning',
                title: 'Periksa Data',
                text: 'Deadline minimal 30 menit setelah tugas dibuat.',
                confirmButtonColor: 'var(--primary)'
            });
            return;
        }

        $form.find('[name="nama_tugas"]').val(v.normalizeSpaces ? v.normalizeSpaces($form.find('[name="nama_tugas"]').val()) : nama);
        $form.find('[name="deskripsi"]').val(v.normalizeSpaces ? v.normalizeSpaces($form.find('[name="deskripsi"]').val()) : deskripsi);

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
