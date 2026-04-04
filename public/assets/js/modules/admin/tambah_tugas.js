/**
 * SIMMAG ODC — Tambah Tugas (Step 1) JS
 * public/assets/js/modules/admin/tambah_tugas.js
 */

$(document).ready(function () {
    if (window.SimmagValidation && typeof window.SimmagValidation.applyInputRules === 'function') {
        window.SimmagValidation.applyInputRules([
            { selector: '#tugasNama', rule: 'name_code', label: 'Nama Tugas' },
            { selector: '#tugasDeskripsi', rule: 'loose_text', label: 'Deskripsi / Instruksi' },
            { selector: '#tugasTarget', rule: 'numeric', label: 'Target Jumlah Item' }
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

    if ($('#tugasKategori').length && typeof $.fn.select2 === 'function') {
        $('#tugasKategori').select2({
            placeholder: '-- Pilih Kategori --',
            allowClear: false,
            width: '100%'
        });
    }

    // ── 1. Inisialisasi Flatpickr ────────────────────────────────
    // dateFormat (value internal) : Y-m-d H:i  → dipakai strtotime() di server
    // altInput + altFormat         : H:i — d M Y → tampilan ke user
    if ($('#tugasDeadline').length) {
        flatpickr('#tugasDeadline', {
            enableTime: true,
            time_24hr: true,
            dateFormat: 'Y-m-d H:i',
            altInput: true,
            altFormat: 'H:i \\— d M Y',   // contoh: "14:00 — 31 Mar 2026"
            minDate: 'today',
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

    // ── 2. Validasi & Lanjut ke Pilih Sasaran ───────────────────
    $('#btnNextSasaran').on('click', function () {
        var idKategori = $('#tugasKategori').val();
        var mode = $('#tugasKategori').find(':selected').data('mode');
        var nama = $.trim($('#tugasNama').val());
        var deskripsi = $.trim($('#tugasDeskripsi').val());
        var target = parseInt($('#tugasTarget').val(), 10);
        // Ambil nilai internal (Y-m-d H:i) dari elemen asli (bukan alt-input)
        var deadline = $('#tugasDeadline').val();
        var deadlineDate = deadline ? new Date(deadline.replace(' ', 'T')) : null;
        var minimumDeadline = new Date(Date.now() + (30 * 60 * 1000));
        var missingFields = [];
        var v = window.SimmagValidation || {};

        if (!idKategori) missingFields.push('Kategori Tugas');
        if (!nama) missingFields.push('Nama Tugas');
        if (!deskripsi) missingFields.push('Deskripsi / Instruksi');
        if (isNaN(target) || target < 1) missingFields.push('Target Jumlah Item');
        if (!deadline) missingFields.push('Tenggat Waktu (Deadline)');
        if (missingFields.length) return showError(buildMissingFieldsMessage(missingFields, 5));
        if ((v.validatePatternField && v.validatePatternField('Nama Tugas', $('#tugasNama').val(), 3, 50, /^[\p{L}0-9\s]+$/u, 'huruf, angka, dan spasi'))
            || (v.validatePatternField && v.validatePatternField('Deskripsi / Instruksi', $('#tugasDeskripsi').val(), 10, 255, /^[\p{L}\p{N}\s\p{P}\p{Sc}\p{Sk}]+$/u, 'huruf, angka, spasi, dan tanda baca'))
            || (v.validateNumberRange && v.validateNumberRange(target, 'Target Jumlah Item', 1))
            || (v.validateDateTime && v.validateDateTime(deadline, 'Tenggat Waktu (Deadline)'))) {
            return showError(
                (v.validatePatternField && v.validatePatternField('Nama Tugas', $('#tugasNama').val(), 3, 50, /^[\p{L}0-9\s]+$/u, 'huruf, angka, dan spasi'))
                || (v.validatePatternField && v.validatePatternField('Deskripsi / Instruksi', $('#tugasDeskripsi').val(), 10, 255, /^[\p{L}\p{N}\s\p{P}\p{Sc}\p{Sk}]+$/u, 'huruf, angka, spasi, dan tanda baca'))
                || (v.validateNumberRange && v.validateNumberRange(target, 'Target Jumlah Item', 1))
                || (v.validateDateTime && v.validateDateTime(deadline, 'Tenggat Waktu (Deadline)'))
            );
        }
        if (!deadlineDate || isNaN(deadlineDate.getTime())) return showError('Format deadline tidak valid.');
        if (deadlineDate.getTime() < minimumDeadline.getTime()) return showError('Deadline minimal 30 menit setelah tugas dibuat.');

        var payloadKetentuan = {
            kategori_id: idKategori,
            kategori_mode: mode,   // 'individu' atau 'kelompok'
            nama: v.normalizeSpaces ? v.normalizeSpaces(nama) : nama,
            deskripsi: v.normalizeSpaces ? v.normalizeSpaces(deskripsi) : deskripsi,
            target: target,
            deadline: deadline   // format Y-m-d H:i
        };

        sessionStorage.setItem('tugasFormData', JSON.stringify(payloadKetentuan));
        window.location.href = window.BASE_URL_TUGAS + '/tugas/pilih-sasaran';
    });

    // ── 3. Restore data jika user kembali dari Step 2 ───────────
    var savedData = sessionStorage.getItem('tugasFormData');
    if (savedData) {
        try {
            var data = JSON.parse(savedData);
            $('#tugasKategori').val(data.kategori_id).trigger('change');
            $('#tugasNama').val(data.nama);
            $('#tugasDeskripsi').val(data.deskripsi);
            $('#tugasTarget').val(data.target);
            var fp = document.querySelector('#tugasDeadline')._flatpickr;
            if (fp && data.deadline) fp.setDate(data.deadline, false, 'Y-m-d H:i');
        } catch (e) {
            console.warn('Gagal restore form dari sessionStorage:', e);
        }
    }

    function showError(msg) {
        Swal.fire({ icon: 'warning', title: 'Perhatian', text: msg, confirmButtonColor: 'var(--primary)' });
    }
});
