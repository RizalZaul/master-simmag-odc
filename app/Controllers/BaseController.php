<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 *
 * Extend this class in any new controllers:
 * ```
 *     class Home extends BaseController
 * ```
 *
 * For security, be sure to declare any new methods as protected or private.
 */
abstract class BaseController extends Controller
{
    /**
     * Be sure to declare properties for any property fetch you initialized.
     * The creation of dynamic property is deprecated in PHP 8.2.
     */

    // protected $session;

    /**
     * @return void
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        // Load here all helpers you want to be available in your controllers that extend BaseController.
        // Caution: Do not put the this below the parent::initController() call below.
        // $this->helpers = ['form', 'url'];

        // Caution: Do not edit this line.
        parent::initController($request, $response, $logger);

        // Preload any models, libraries, etc, here.
        // $this->session = service('session');
    }

    /**
     * Bangun pesan validasi field wajib yang konsisten.
     *
     * Aturan:
     * - semua field wajib kosong -> "Semua field harus diisi."
     * - satu field kosong        -> "<Field> wajib diisi."
     * - beberapa field kosong    -> "Field berikut wajib diisi: A, B, C."
     */
    protected function buildMissingFieldsMessage(array $missingFields, ?int $totalRequired = null): string
    {
        $missingFields = array_values(array_unique(array_filter(array_map(
            static fn($label) => trim((string) $label),
            $missingFields
        ))));

        if ($missingFields === []) {
            return 'Semua field harus diisi.';
        }

        if ($totalRequired !== null && $totalRequired > 0 && count($missingFields) >= $totalRequired) {
            return 'Semua field harus diisi.';
        }

        if (count($missingFields) === 1) {
            return $missingFields[0] . ' wajib diisi.';
        }

        return 'Field berikut wajib diisi: ' . implode(', ', $missingFields) . '.';
    }

    /**
     * Tambahkan pesan grouped fields seperti "Anggota 1: ...".
     */
    protected function appendMissingFieldGroup(array &$messages, string $groupLabel, array $missingFields, ?int $totalRequired = null): void
    {
        $missingFields = array_values(array_unique(array_filter(array_map(
            static fn($label) => trim((string) $label),
            $missingFields
        ))));

        if ($missingFields === []) {
            return;
        }

        $message = $this->buildMissingFieldsMessage($missingFields, $totalRequired);
        if ($message === 'Semua field harus diisi.') {
            $messages[] = $groupLabel . ': ' . $message;
            return;
        }

        $messages[] = $groupLabel . ': ' . $message;
    }

    protected function normalizeSingleSpaces(?string $value): string
    {
        $value = (string) $value;
        $trimmed = trim($value);
        return preg_replace('/\s{2,}/u', ' ', $trimmed) ?? $trimmed;
    }

    protected function normalizeLineEndings(?string $value): string
    {
        $value = (string) $value;
        return preg_replace("/\r\n?/", "\n", $value) ?? $value;
    }

    protected function normalizeMultilineText(?string $value): string
    {
        $value = $this->normalizeLineEndings($value);
        $value = str_replace("\t", ' ', $value);
        $value = preg_replace('/[^\S\n]{2,}/u', ' ', $value) ?? $value;
        $value = preg_replace('/^[^\S\n]+|[^\S\n]+$/um', '', $value) ?? $value;
        return $value;
    }

    protected function hasInvalidSpacing(?string $value): bool
    {
        $value = (string) $value;
        return $value !== trim($value) || preg_match('/\s{2,}/u', $value) === 1;
    }

    protected function buildSpacingError(string $label): string
    {
        return $label . ' tidak boleh diawali/diakhiri dengan spasi dan tidak boleh mengandung spasi ganda.';
    }

    protected function validatePatternField(
        string $label,
        ?string $value,
        int $min,
        int $max,
        string $pattern,
        string $allowedText,
        bool $checkSpacing = true
    ): ?string {
        $value = (string) $value;

        if ($value === '') {
            return $label . ' wajib diisi.';
        }

        if ($checkSpacing && $this->hasInvalidSpacing($value)) {
            return $this->buildSpacingError($label);
        }

        $normalized = $this->normalizeSingleSpaces($value);
        $length = mb_strlen($normalized);

        if ($length < $min) {
            return $label . ' minimal ' . $min . ' karakter.';
        }

        if ($length > $max) {
            return $label . ' maksimal ' . $max . ' karakter.';
        }

        if (! preg_match($pattern, $normalized)) {
            return $label . ' hanya boleh berisi ' . $allowedText . '.';
        }

        return null;
    }

    protected function validateLooseTextField(string $label, ?string $value, int $min, int $max): ?string
    {
        $value = (string) $value;

        if ($value === '') {
            return $label . ' wajib diisi.';
        }

        if ($this->hasInvalidSpacing($value)) {
            return $this->buildSpacingError($label);
        }

        $normalized = $this->normalizeSingleSpaces($value);
        $length = mb_strlen($normalized);

        if ($length < $min) {
            return $label . ' minimal ' . $min . ' karakter.';
        }

        if ($length > $max) {
            return $label . ' maksimal ' . $max . ' karakter.';
        }

        if (! preg_match('/^[^\r\n\t]+$/u', $normalized)) {
            return $label . ' mengandung karakter yang tidak valid.';
        }

        return null;
    }

    protected function validateMultilinePatternField(
        string $label,
        ?string $value,
        int $min,
        int $max,
        string $pattern,
        string $allowedText
    ): ?string {
        $normalized = $this->normalizeMultilineText($value);

        if (trim($normalized) === '') {
            return $label . ' wajib diisi.';
        }

        $length = mb_strlen($normalized);

        if ($length < $min) {
            return $label . ' minimal ' . $min . ' karakter.';
        }

        if ($length > $max) {
            return $label . ' maksimal ' . $max . ' karakter.';
        }

        if (! preg_match($pattern, $normalized)) {
            return $label . ' hanya boleh berisi ' . $allowedText . '.';
        }

        return null;
    }

    protected function validateEmailAddress(?string $email, string $label = 'Email'): ?string
    {
        $email = (string) $email;

        if ($email === '') {
            return $label . ' wajib diisi.';
        }

        if ($this->hasInvalidSpacing($email)) {
            return $this->buildSpacingError($label);
        }

        if (mb_strlen($email) > 100) {
            return $label . ' maksimal 100 karakter.';
        }

        $pattern = '/^[A-Za-z0-9](?:[A-Za-z0-9._%+\-]{0,62}[A-Za-z0-9])?@'
            . '[A-Za-z0-9](?:[A-Za-z0-9\-]{0,61}[A-Za-z0-9])?'
            . '(?:\.[A-Za-z0-9](?:[A-Za-z0-9\-]{0,61}[A-Za-z0-9])?)+$/';

        if (! preg_match($pattern, $email)) {
            return $label . ' tidak valid.';
        }

        $domain = (string) substr(strrchr($email, '@') ?: '', 1);
        if ($domain === '' || str_ends_with($domain, '.')) {
            return $label . ' tidak valid.';
        }

        return null;
    }

    protected function validateWhatsappNumber(?string $value, string $label = 'No WA'): ?string
    {
        $value = (string) $value;

        if ($value === '') {
            return $label . ' wajib diisi.';
        }

        if ($this->hasInvalidSpacing($value)) {
            return $this->buildSpacingError($label);
        }

        if (! preg_match('/^(?:\+\d{6,19}|\d{7,20})$/', $value)) {
            return $label . ' hanya boleh berisi angka dan tanda tambah (+), dengan panjang total 7 sampai 20 karakter.';
        }

        return null;
    }

    protected function validateStandardPassword(string $password, ?string $confirmation = null): ?string
    {
        if ($password === '') {
            return 'Password wajib diisi.';
        }

        if (strlen($password) < 8) {
            return 'Password minimal 8 karakter.';
        }

        if (strlen($password) > 24) {
            return 'Password maksimal 24 karakter.';
        }

        if (! preg_match('/[A-Z]/', $password)) {
            return 'Password harus mengandung minimal 1 huruf kapital (A-Z).';
        }

        if (! preg_match('/[a-z]/', $password)) {
            return 'Password harus mengandung minimal 1 huruf kecil (a-z).';
        }

        if (! preg_match('/[0-9]/', $password)) {
            return 'Password harus mengandung minimal 1 angka (0-9).';
        }

        if (! preg_match('/[^A-Za-z0-9]/', $password)) {
            return 'Password harus mengandung minimal 1 simbol.';
        }

        if ($confirmation !== null && $password !== $confirmation) {
            return 'Konfirmasi password tidak cocok.';
        }

        return null;
    }

    protected function validateNumberRange(string $label, mixed $value, int $min, ?int $max = null): ?string
    {
        $raw = trim((string) $value);

        if ($raw === '') {
            return $label . ' wajib diisi.';
        }

        if (! preg_match('/^\d+$/', $raw)) {
            return $label . ' hanya boleh berisi angka.';
        }

        $number = (int) $raw;
        if ($number < $min) {
            return $label . ' minimal ' . $min . '.';
        }

        if ($max !== null && $number > $max) {
            return $label . ' maksimal ' . $max . '.';
        }

        return null;
    }

    protected function validateDateOnlyValue(string $label, ?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return $label . ' wajib diisi.';
        }

        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return $label . ' harus dipilih dari pemilih tanggal yang disediakan.';
        }

        $timestamp = strtotime($value);
        if ($timestamp === false) {
            return $label . ' tidak valid.';
        }

        return null;
    }

    protected function validateDateTimeValue(string $label, ?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return $label . ' wajib diisi.';
        }

        if (! preg_match('/^\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}$/', $value) && ! preg_match('/^\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2}$/', $value)) {
            return $label . ' harus dipilih dari pemilih tanggal yang disediakan.';
        }

        if (strtotime($value) === false) {
            return $label . ' tidak valid.';
        }

        return null;
    }

    protected function validatePklStartDate(?string $value): ?string
    {
        $error = $this->validateDateOnlyValue('Tanggal Mulai PKL', $value);
        if ($error !== null) {
            return $error;
        }

        $timestamp = strtotime((string) $value);
        $today = strtotime(date('Y-m-d'));
        $min = strtotime('-14 days', $today);
        $max = strtotime('+3 months', $today);

        if ($timestamp < $min) {
            return 'Tanggal Mulai PKL tidak boleh lebih awal dari 2 minggu sebelum hari ini.';
        }

        if ($timestamp > $max) {
            return 'Tanggal Mulai PKL maksimal 3 bulan dari hari ini.';
        }

        return null;
    }

    protected function validatePklEndDate(?string $startDate, ?string $endDate): ?string
    {
        $error = $this->validateDateOnlyValue('Tanggal Akhir PKL', $endDate);
        if ($error !== null) {
            return $error;
        }

        $startError = $this->validateDateOnlyValue('Tanggal Mulai PKL', $startDate);
        if ($startError !== null) {
            return $startError;
        }

        $startTs = strtotime((string) $startDate);
        $endTs = strtotime((string) $endDate);
        $minEnd = strtotime('+2 months', $startTs);

        if ($endTs < $minEnd) {
            return 'Tanggal Akhir PKL minimal 2 bulan dari Tanggal Mulai PKL.';
        }

        return null;
    }

    protected function validateDeadlineValue(?string $deadline, int $minimumTimestamp): ?string
    {
        $error = $this->validateDateTimeValue('Tenggat Waktu (Deadline)', $deadline);
        if ($error !== null) {
            return $error;
        }

        $timestamp = strtotime((string) $deadline);
        if ($timestamp === false) {
            return 'Tenggat Waktu (Deadline) tidak valid.';
        }

        if ($timestamp < $minimumTimestamp) {
            return 'Deadline minimal 30 menit setelah tugas dibuat.';
        }

        return null;
    }

    protected function validateHttpsUrlValue(?string $url, string $label = 'URL'): ?string
    {
        $url = trim((string) $url);
        if ($url === '') {
            return $label . ' wajib diisi.';
        }

        if (! filter_var($url, FILTER_VALIDATE_URL) || ! str_starts_with(strtolower($url), 'https://')) {
            return $label . ' harus diawali dengan https://';
        }

        return null;
    }
}
