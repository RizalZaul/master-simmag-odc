<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\AdminModel;
use App\Models\PklModel;
use App\Services\EmailService;
use CodeIgniter\Cache\CacheInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthController extends BaseController
{
    private const RESET_OTP_TTL        = 300;
    private const RESET_VERIFY_TTL     = 900;
    private const RESET_MAX_ATTEMPTS   = 3;
    private const ADMIN_LOCK_TTL       = 900;
    private const PKL_BLOCK_MESSAGE    = 'akun anda diblokir hubungi admin untuk mengaktifkan akun';
    private const AUTH_MARKER_COOKIE   = 'simmag_auth_marker';
    private const LOGOUT_MARKER_COOKIE = 'simmag_logout_marker';
    private const AUTH_MARKER_TTL      = 2592000; // 30 hari
    private const LOGOUT_MARKER_TTL    = 300; // 5 menit

    protected UserModel $userModel;
    protected AdminModel $adminModel;
    protected PklModel $pklModel;
    protected CacheInterface $cache;

    public function __construct()
    {
        $this->userModel  = new UserModel();
        $this->adminModel = new AdminModel();
        $this->pklModel   = new PklModel();
        $this->cache      = cache();
    }

    public function login()
    {
        if (session()->get('logged_in')) {
            return $this->redirectByRole(session()->get('role'));
        }

        return view('auth/login');
    }

    public function processLogin()
    {
        $isAjax = $this->request->isAJAX();

        if (session()->get('logged_in')) {
            return $isAjax
                ? $this->jsonSuccess($this->getRedirectUrl(session()->get('role')))
                : $this->redirectByRole(session()->get('role'));
        }

        $identifier = trim((string) $this->request->getPost('username'));
        $password   = (string) $this->request->getPost('password');

        $missingFields = [];
        if ($identifier === '') {
            $missingFields[] = 'Username / Email';
        }
        if (trim($password) === '') {
            $missingFields[] = 'Password';
        }

        if ($missingFields !== []) {
            $message = $this->buildMissingFieldsMessage($missingFields, 2);
            $field = '';
            if (count($missingFields) === 1) {
                $field = $missingFields[0] === 'Password' ? 'password' : 'username';
            }

            return $isAjax
                ? $this->jsonError($message, $field)
                : redirect()->back()->withInput()->with('error', $message);
        }

        $rules = [
            'username' => [
                'label'  => 'Username',
                'rules'  => 'required',
                'errors' => ['required' => 'Username atau email tidak boleh kosong.'],
            ],
            'password' => [
                'label'  => 'Password',
                'rules'  => 'required|min_length[8]',
                'errors' => [
                    'required'   => 'Password tidak boleh kosong.',
                    'min_length' => 'Password minimal 8 karakter.',
                ],
            ],
        ];

        if (! $this->validate($rules)) {
            $message = implode(' ', $this->validator->getErrors());

            return $isAjax
                ? $this->jsonError($message)
                : redirect()->back()->withInput()->with('error', $message);
        }
        $user       = $this->userModel->findByIdentifier($identifier);

        if (! $user) {
            return $isAjax
                ? $this->jsonError('Username atau email tidak ditemukan.')
                : redirect()->back()->withInput()->with('error', 'Username atau email tidak ditemukan.');
        }

        if ($user->status !== 'aktif') {
            return $isAjax
                ? $this->jsonError('Akun Anda tidak aktif. Hubungi administrator.')
                : redirect()->back()->withInput()->with('error', 'Akun Anda tidak aktif. Hubungi administrator.');
        }

        if (! password_verify($password, $user->password)) {
            $this->logActivity((int) $user->id_user, (string) $user->role, 'failed');

            return $isAjax
                ? $this->jsonError('Password yang Anda masukkan salah.', 'password')
                : redirect()->back()->withInput()->with('error', 'Password yang Anda masukkan salah.');
        }

        $profil = $this->getProfil((int) $user->id_user, (string) $user->role);

        $sessionData = [
            'user_id'    => $user->id_user,
            'username'   => $user->username,
            'email'      => $user->email,
            'role'       => $user->role,
            'nama'       => $profil['nama_lengkap']   ?? $user->username,
            'panggilan'  => $profil['nama_panggilan'] ?? null,
            'logged_in'  => true,
            'login_time' => date('Y-m-d H:i:s'),
        ];

        if ($user->role === 'pkl') {
            $sessionData['id_pkl']      = $profil['id_pkl']      ?? null;
            $sessionData['id_kelompok'] = $profil['id_kelompok'] ?? null;
        }

        if ($user->role === 'admin') {
            $sessionData['id_admin'] = $profil['id_admin'] ?? null;
        }

        session()->set($sessionData);
        session()->setFlashdata('success', 'Selamat datang, ' . $sessionData['nama'] . '!');

        $this->logActivity((int) $user->id_user, (string) $user->role, 'success');

        if ($isAjax) {
            return $this->clearLogoutMarkerCookie(
                $this->withAuthMarkerCookie(
                    $this->jsonSuccess($this->getRedirectUrl((string) $user->role), 'Selamat datang, ' . $sessionData['nama'] . '!')
                )
            );
        }

        return $this->clearLogoutMarkerCookie(
            $this->withAuthMarkerCookie($this->redirectByRole((string) $user->role))
        );
    }

    public function forgotPassword()
    {
        if (session()->get('logged_in')) {
            return $this->redirectByRole(session()->get('role'));
        }

        return view('auth/lupa_password');
    }

    public function sendForgotPasswordOtp()
    {
        if (! $this->request->isAJAX()) {
            return $this->jsonError('Forbidden', '', 403);
        }

        $email = strtolower(trim((string) $this->request->getPost('email')));

        if (! $this->validate([
            'email' => 'required|valid_email|max_length[100]',
        ])) {
            return $this->jsonError(implode(' ', $this->validator->getErrors()), 'email', 422);
        }

        $user = $this->userModel->findByEmail($email);
        if (! $user) {
            return $this->jsonError('Email akun tidak ditemukan.', 'email', 404);
        }

        if ($user->status !== 'aktif') {
            if ($user->role === 'pkl') {
                return $this->jsonError(self::PKL_BLOCK_MESSAGE, 'email', 423, [
                    'blocked' => true,
                    'role'    => 'pkl',
                ]);
            }

            return $this->jsonError('Akun Anda tidak aktif. Hubungi administrator.', 'email', 423, [
                'blocked' => true,
                'role'    => 'admin',
            ]);
        }

        $lockRemaining = $user->role === 'admin'
            ? $this->getAdminLockRemaining((int) $user->id_user)
            : 0;

        if ($lockRemaining > 0) {
            return $this->jsonError(
                'Terlalu banyak percobaan OTP. Coba lagi dalam ' . $this->formatRemainingTime($lockRemaining) . '.',
                'otp',
                423,
                [
                    'locked'        => true,
                    'role'          => 'admin',
                    'lockRemaining' => $lockRemaining,
                ]
            );
        }

        $otp      = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiryTs = time() + self::RESET_OTP_TTL;

        $this->userModel->storeOtp((int) $user->id_user, $otp, date('Y-m-d H:i:s', $expiryTs));
        $this->cache->delete($this->getForgotAttemptKey((int) $user->id_user));
        $this->rememberForgotSession($user, false, $expiryTs);

        try {
            $sent = (new EmailService())->sendOtpResetPassword($email, $otp, (string) $user->role);
        } catch (\Throwable $e) {
            log_message('error', '[AuthController::sendForgotPasswordOtp] ' . $e->getMessage());
            $this->userModel->clearOtp((int) $user->id_user);
            session()->remove('forgot_password');

            return $this->jsonError('Gagal mengirim OTP ke email. Coba beberapa saat lagi.', '', 500);
        }

        if (! $sent) {
            $this->userModel->clearOtp((int) $user->id_user);
            session()->remove('forgot_password');
            return $this->jsonError('Gagal mengirim OTP ke email. Coba beberapa saat lagi.', '', 500);
        }

        return $this->jsonSuccess('', 'Kode OTP dikirim ke ' . $email . '.', [
            'role'       => $user->role,
            'email'      => $email,
            'maskedEmail'=> $this->maskEmail($email),
            'ttl'        => self::RESET_OTP_TTL,
            'step'       => 'otp',
        ]);
    }

    public function verifyForgotPasswordOtp()
    {
        if (! $this->request->isAJAX()) {
            return $this->jsonError('Forbidden', '', 403);
        }

        $email = strtolower(trim((string) $this->request->getPost('email')));
        $otp   = trim((string) $this->request->getPost('otp'));

        if (! $this->validate([
            'email' => 'required|valid_email|max_length[100]',
            'otp'   => 'required|exact_length[6]|numeric',
        ])) {
            return $this->jsonError(implode(' ', $this->validator->getErrors()), 'otp', 422);
        }

        $user = $this->userModel->findByEmail($email);
        if (! $user) {
            return $this->jsonError('Email akun tidak ditemukan.', 'email', 404);
        }

        if ($user->status !== 'aktif') {
            if ($user->role === 'pkl') {
                return $this->jsonError(self::PKL_BLOCK_MESSAGE, 'otp', 423, [
                    'blocked' => true,
                    'role'    => 'pkl',
                ]);
            }

            return $this->jsonError('Akun Anda tidak aktif. Hubungi administrator.', 'otp', 423, [
                'blocked' => true,
                'role'    => 'admin',
            ]);
        }

        $lockRemaining = $user->role === 'admin'
            ? $this->getAdminLockRemaining((int) $user->id_user)
            : 0;

        if ($lockRemaining > 0) {
            return $this->jsonError(
                'Terlalu banyak percobaan OTP. Coba lagi dalam ' . $this->formatRemainingTime($lockRemaining) . '.',
                'otp',
                423,
                [
                    'locked'        => true,
                    'role'          => 'admin',
                    'lockRemaining' => $lockRemaining,
                ]
            );
        }

        if (! $user->kode_otp || ! $user->tenggat_otp) {
            return $this->jsonError('OTP tidak ditemukan atau sudah kadaluarsa. Silakan kirim ulang OTP.', 'otp', 422);
        }

        $expiryTs = strtotime((string) $user->tenggat_otp);
        if ($expiryTs === false || $expiryTs < time()) {
            $this->userModel->clearOtp((int) $user->id_user);
            $this->clearForgotRuntimeState((int) $user->id_user);

            return $this->jsonError('OTP sudah kadaluarsa. Silakan kirim ulang OTP.', 'otp', 422);
        }

        if (! hash_equals((string) $user->kode_otp, $otp)) {
            $attemptKey = $this->getForgotAttemptKey((int) $user->id_user);
            $attempts   = (int) ($this->cache->get($attemptKey) ?? 0) + 1;

            if ($user->role === 'pkl' && $attempts >= self::RESET_MAX_ATTEMPTS) {
                $this->cache->delete($attemptKey);
                $this->userModel->clearOtp((int) $user->id_user);
                $this->userModel->updateStatus((int) $user->id_user, 'nonaktif');
                $this->clearForgotRuntimeState((int) $user->id_user, true);

                return $this->jsonError(self::PKL_BLOCK_MESSAGE, 'otp', 423, [
                    'blocked' => true,
                    'role'    => 'pkl',
                ]);
            }

            if ($user->role === 'admin' && $attempts >= self::RESET_MAX_ATTEMPTS) {
                $lockUntil = time() + self::ADMIN_LOCK_TTL;
                $this->cache->save($this->getAdminLockKey((int) $user->id_user), $lockUntil, self::ADMIN_LOCK_TTL);
                $this->cache->delete($attemptKey);
                $this->userModel->clearOtp((int) $user->id_user);
                $this->clearForgotRuntimeState((int) $user->id_user);

                return $this->jsonError(
                    'Terlalu banyak percobaan OTP. Coba lagi dalam 15 menit.',
                    'otp',
                    423,
                    [
                        'locked'        => true,
                        'role'          => 'admin',
                        'lockRemaining' => self::ADMIN_LOCK_TTL,
                    ]
                );
            }

            $this->cache->save($attemptKey, $attempts, self::RESET_OTP_TTL);
            $remaining = max(0, self::RESET_MAX_ATTEMPTS - $attempts);

            return $this->jsonError('Kode OTP salah. Sisa percobaan: ' . $remaining . 'x.', 'otp', 422, [
                'attemptsLeft' => $remaining,
            ]);
        }

        $this->userModel->clearOtp((int) $user->id_user);
        $this->clearForgotRuntimeState((int) $user->id_user, true);
        $this->rememberForgotSession($user, true, time() + self::RESET_VERIFY_TTL);

        return $this->jsonSuccess('', 'OTP berhasil diverifikasi. Silakan masukkan password baru.', [
            'email'    => $email,
            'role'     => $user->role,
            'step'     => 'reset',
            'resetTtl' => self::RESET_VERIFY_TTL,
        ]);
    }

    public function resetForgotPassword()
    {
        if (! $this->request->isAJAX()) {
            return $this->jsonError('Forbidden', '', 403);
        }

        $email              = strtolower(trim((string) $this->request->getPost('email')));
        $passwordBaru       = (string) $this->request->getPost('password_baru');
        $konfirmasiPassword = (string) $this->request->getPost('konfirmasi_password');

        $missingFields = [];
        if ($email === '') {
            $missingFields[] = 'Email';
        }
        if (trim($passwordBaru) === '') {
            $missingFields[] = 'Password Baru';
        }
        if (trim($konfirmasiPassword) === '') {
            $missingFields[] = 'Konfirmasi Password';
        }

        if ($missingFields !== []) {
            return $this->jsonError($this->buildMissingFieldsMessage($missingFields, 3), 'password', 422);
        }

        if (! $this->validate([
            'email'               => 'required|valid_email|max_length[100]',
            'password_baru'       => 'required',
            'konfirmasi_password' => 'required',
        ])) {
            return $this->jsonError(implode(' ', $this->validator->getErrors()), 'password', 422);
        }

        $user = $this->userModel->findByEmail($email);
        if (! $user) {
            return $this->jsonError('Email akun tidak ditemukan.', 'email', 404);
        }

        if ($user->status !== 'aktif') {
            if ($user->role === 'pkl') {
                return $this->jsonError(self::PKL_BLOCK_MESSAGE, 'email', 423, [
                    'blocked' => true,
                    'role'    => 'pkl',
                ]);
            }

            return $this->jsonError('Akun Anda tidak aktif. Hubungi administrator.', 'email', 423, [
                'blocked' => true,
                'role'    => 'admin',
            ]);
        }

        $forgotSession = session()->get('forgot_password');
        $verifiedUntil = (int) ($forgotSession['expires_at'] ?? 0);

        if (
            ! is_array($forgotSession)
            || ! ($forgotSession['verified'] ?? false)
            || (int) ($forgotSession['user_id'] ?? 0) !== (int) $user->id_user
            || strtolower((string) ($forgotSession['email'] ?? '')) !== $email
            || $verifiedUntil < time()
        ) {
            session()->remove('forgot_password');
            return $this->jsonError('Verifikasi OTP tidak valid atau sudah berakhir. Silakan ulangi dari awal.', 'otp', 419, [
                'resetExpired' => true,
            ]);
        }

        $passwordError = $this->validateStrongPassword($passwordBaru, $konfirmasiPassword);
        if ($passwordError) {
            return $this->jsonError($passwordError, 'password', 422);
        }

        $this->userModel->updatePassword((int) $user->id_user, $passwordBaru);
        $this->userModel->clearOtp((int) $user->id_user);
        $this->clearForgotRuntimeState((int) $user->id_user, true);

        session()->setFlashdata('success', 'Password berhasil diperbarui. Silakan login dengan password baru.');

        return $this->jsonSuccess(base_url('auth/login'), 'Password berhasil diperbarui. Silakan login dengan password baru.');
    }

    public function logout()
    {
        if (session()->get('logged_in')) {
            $this->logActivity(
                (int) session()->get('user_id'),
                (string) session()->get('role'),
                'logout'
            );
        }

        session()->destroy();

        return $this->setLogoutMarkerCookie(
            $this->clearAuthMarkerCookie(
                redirect()
                    ->to(base_url('auth/login'))
                    ->with('success', 'Anda berhasil logout.')
            )
        );
    }

    private function getProfil(int $idUser, string $role): array
    {
        return match ($role) {
            'admin' => $this->adminModel->getProfilByIdUser($idUser),
            'pkl'   => $this->pklModel->getProfilByIdUser($idUser),
            default => [],
        };
    }

    private function redirectByRole(string $role): \CodeIgniter\HTTP\RedirectResponse
    {
        return match ($role) {
            'admin' => redirect()->to(base_url('/')),
            'pkl'   => redirect()->to(base_url('pkl/dashboard')),
            default => redirect()->to(base_url('auth/login')),
        };
    }

    private function getRedirectUrl(string $role): string
    {
        return match ($role) {
            'admin' => base_url('/'),
            'pkl'   => base_url('pkl/dashboard'),
            default => base_url('auth/login'),
        };
    }

    private function rememberForgotSession(object $user, bool $verified, int $expiresAt): void
    {
        session()->set('forgot_password', [
            'user_id'    => (int) $user->id_user,
            'email'      => strtolower((string) $user->email),
            'role'       => (string) $user->role,
            'verified'   => $verified,
            'expires_at' => $expiresAt,
        ]);
    }

    private function clearForgotRuntimeState(int $idUser, bool $clearLock = false): void
    {
        $this->cache->delete($this->getForgotAttemptKey($idUser));
        if ($clearLock) {
            $this->cache->delete($this->getAdminLockKey($idUser));
        }

        $forgotSession = session()->get('forgot_password');
        if ((int) ($forgotSession['user_id'] ?? 0) === $idUser) {
            session()->remove('forgot_password');
        }
    }

    private function getForgotAttemptKey(int $idUser): string
    {
        return 'auth_forgot_attempt_' . $idUser;
    }

    private function getAdminLockKey(int $idUser): string
    {
        return 'auth_forgot_admin_lock_' . $idUser;
    }

    private function getAdminLockRemaining(int $idUser): int
    {
        $lockUntil = (int) ($this->cache->get($this->getAdminLockKey($idUser)) ?? 0);
        if ($lockUntil <= time()) {
            $this->cache->delete($this->getAdminLockKey($idUser));
            return 0;
        }

        return $lockUntil - time();
    }

    private function validateStrongPassword(string $password, string $konfirmasi): ?string
    {
        if (strlen($password) < 8) {
            return 'Password minimal 8 karakter.';
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
        if (! preg_match('/[\W_]/', $password)) {
            return 'Password harus mengandung minimal 1 simbol (!, @, #, dst).';
        }
        if ($password !== $konfirmasi) {
            return 'Konfirmasi password tidak cocok.';
        }

        return null;
    }

    private function maskEmail(string $email): string
    {
        [$name, $domain] = array_pad(explode('@', $email, 2), 2, '');
        if ($name === '' || $domain === '') {
            return $email;
        }

        if (strlen($name) <= 2) {
            $masked = substr($name, 0, 1) . '*';
        } else {
            $masked = substr($name, 0, 2) . str_repeat('*', max(2, strlen($name) - 2));
        }

        return $masked . '@' . $domain;
    }

    private function formatRemainingTime(int $seconds): string
    {
        $seconds = max(0, $seconds);
        $minutes = intdiv($seconds, 60);
        $remain  = $seconds % 60;

        if ($minutes >= 60) {
            $hours = intdiv($minutes, 60);
            $minutes = $minutes % 60;
            return sprintf('%d jam %02d menit', $hours, $minutes);
        }

        return sprintf('%02d:%02d', $minutes, $remain);
    }

    private function jsonSuccess(string $redirect = '', string $message = '', array $extra = [], int $status = 200): ResponseInterface
    {
        return $this->response
            ->setContentType('application/json')
            ->setStatusCode($status)
            ->setJSON(array_merge([
                'success'  => true,
                'redirect' => $redirect,
                'message'  => $message,
                'csrfHash' => csrf_hash(),
            ], $extra));
    }

    private function jsonError(string $message, string $field = '', int $status = 401, array $extra = []): ResponseInterface
    {
        return $this->response
            ->setContentType('application/json')
            ->setStatusCode($status)
            ->setJSON(array_merge([
                'success'  => false,
                'message'  => $message,
                'field'    => $field,
                'csrfHash' => csrf_hash(),
            ], $extra));
    }

    private function withAuthMarkerCookie(ResponseInterface $response): ResponseInterface
    {
        return $response->setCookie([
            'name'     => self::AUTH_MARKER_COOKIE,
            'value'    => '1',
            'expire'   => self::AUTH_MARKER_TTL,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    private function clearAuthMarkerCookie(ResponseInterface $response): ResponseInterface
    {
        return $response->deleteCookie(self::AUTH_MARKER_COOKIE, '/');
    }

    private function setLogoutMarkerCookie(ResponseInterface $response): ResponseInterface
    {
        return $response->setCookie([
            'name'     => self::LOGOUT_MARKER_COOKIE,
            'value'    => '1',
            'expire'   => self::LOGOUT_MARKER_TTL,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    private function clearLogoutMarkerCookie(ResponseInterface $response): ResponseInterface
    {
        return $response->deleteCookie(self::LOGOUT_MARKER_COOKIE, '/');
    }

    private function logActivity(int $userId, string $role, string $status): void
    {
        $ip = $this->request->getIPAddress();
        log_message('info', "[Auth] user_id={$userId} | role={$role} | status={$status} | ip={$ip}");
    }
}
