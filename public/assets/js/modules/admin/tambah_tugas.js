/**
 * SIMMAG ODC — Tambah Tugas (Step 1) JS
 * public/assets/js/modules/admin/tambah_tugas.js
 */

$(document).ready(function () {

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
        var todayStart = new Date();
        todayStart.setHours(0, 0, 0, 0);

        if (!idKategori) return showError('Pilih Kategori Tugas terlebih dahulu.');
        if (!nama) return showError('Nama Tugas tidak boleh kosong.');
        if (!deskripsi) return showError('Deskripsi Tugas tidak boleh kosong.');
        if (isNaN(target) || target < 1) return showError('Target jumlah minimal 1.');
        if (!deadline) return showError('Tenggat waktu (deadline) wajib diisi.');
        if (!deadlineDate || isNaN(deadlineDate.getTime())) return showError('Format deadline tidak valid.');
        if (deadlineDate < todayStart) return showError('Deadline tugas minimal tanggal hari ini.');

        var payloadKetentuan = {
            kategori_id: idKategori,
            kategori_mode: mode,   // 'individu' atau 'kelompok'
            nama: nama,
            deskripsi: deskripsi,
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
