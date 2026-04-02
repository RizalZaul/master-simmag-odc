<?php

/**
 * app/Helpers/tgl_helper.php
 *
 * Helper tanggal — tersedia global setelah di-autoload di app/Config/Autoload.php
 * atau dipanggil manual dengan: helper('tgl');
 *
 * Autoload: tambahkan 'tgl' ke $helpers di app/Config/Autoload.php
 * Contoh:
 *   public $helpers = ['tgl'];
 */

if (! function_exists('tglShortIndo')) {
    /**
     * Format tanggal ke format Indonesia singkat.
     * Input : 'Y-m-d' (mis. '2026-03-06')
     * Output: 'd M Y' (mis. '06 Mar 2026')
     */
    function tglShortIndo(?string $date): string
    {
        if (! $date) return '-';
        $bulan = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        $parts = explode('-', $date);
        if (count($parts) < 3) return '-';
        return sprintf('%02d %s %s', (int) $parts[2], $bulan[(int) $parts[1]], $parts[0]);
    }
}

if (! function_exists('hitungDurasi')) {
    /**
     * Hitung durasi antara dua tanggal.
     * Return: '2 Bulan 5 Hari' atau '45 Hari'
     */
    function hitungDurasi(?string $mulai, ?string $akhir): string
    {
        if (! $mulai || ! $akhir) return '-';
        try {
            $diff  = (new DateTime($mulai))->diff(new DateTime($akhir));
            $bulan = $diff->m + ((int) $diff->y * 12);
            if ($bulan >= 1) {
                return $bulan . ' Bulan' . ($diff->d > 0 ? ' ' . $diff->d . ' Hari' : '');
            }
            return $diff->days . ' Hari';
        } catch (\Throwable $e) {
            return '-';
        }
    }
}
