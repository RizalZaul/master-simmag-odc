<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\AdminModel;
use App\Models\PklModel;

/**
 * AuthController
 * Menangani login, logout, dan redirect berdasarkan role.
 *
 * Perubahan dari Auth.php:
 *   - Nama class: Auth → AuthController
 *   - Nama file  : Auth.php → AuthController.php
 *   - Tidak ada perubahan logika
 */
class AuthController extends BaseController
{
    protected UserModel  $userModel;
    protected AdminModel $adminModel;
    protected PklModel   $pklModel;

    public function __construct()
    {
        $this->userModel  = new UserModel();
        $this->adminModel = new AdminModel();
        $this->pklModel   = new PklModel();
    }


    // ==========================================
    // LOGIN PAGE
    // ==========================================

    public function login()
    {
        if (session()->get('logged_in')) {
            return $this->redirectByRole(session()->get('role'));
        }

        return view('auth/login');
    }


    // ==========================================
    // PROCESS LOGIN
    // ==========================================

    public function processLogin()
    {
        $isAjax = $this->request->isAJAX();

        // ── Jika sudah login, langsung redirect (FIX 3) ────────────────
        // Melindungi POST /auth/login dari duplikasi session jika user
        // yang sudah login mengirim ulang form (tidak ada filter di POST route).
        if (session()->get('logged_in')) {
            return $isAjax
                ? $this->jsonSuccess($this->getRedirectUrl(session()->get('role')))
                : $this->redirectByRole(session()->get('role'));
        }

        // ── Validasi input ──────────────────────────────────────────────
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

        $identifier = trim($this->request->getPost('username'));
        $password   = $this->request->getPost('password');

        // ── Cari user via Model ─────────────────────────────────────────
        $user = $this->userModel->findByIdentifier($identifier);

        if (! $user) {
            return $isAjax
                ? $this->jsonError('Username atau email tidak ditemukan.')
                : redirect()->back()->withInput()->with('error', 'Username atau email tidak ditemukan.');
        }

        // ── Cek status akun ─────────────────────────────────────────────
        if ($user->status !== 'aktif') {
            return $isAjax
                ? $this->jsonError('Akun Anda tidak aktif. Hubungi administrator.')
                : redirect()->back()->withInput()->with('error', 'Akun Anda tidak aktif. Hubungi administrator.');
        }

        // ── Verifikasi password ─────────────────────────────────────────
        if (! password_verify($password, $user->password)) {
            $this->logActivity($user->id_user, $user->role, 'failed');

            return $isAjax
                ? $this->jsonError('Password yang Anda masukkan salah.', 'password')
                : redirect()->back()->withInput()->with('error', 'Password yang Anda masukkan salah.');
        }

        // ── Ambil profil berdasarkan role ───────────────────────────────
        $profil = $this->getProfil($user->id_user, $user->role);

        // ── Set session ─────────────────────────────────────────────────
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

        $this->logActivity($user->id_user, $user->role, 'success');

        return $isAjax
            ? $this->jsonSuccess($this->getRedirectUrl($user->role), 'Selamat datang, ' . $sessionData['nama'] . '!')
            : $this->redirectByRole($user->role);
    }


    // ==========================================
    // LOGOUT
    // ==========================================

    public function logout()
    {
        if (session()->get('logged_in')) {
            $this->logActivity(
                session()->get('user_id'),
                session()->get('role'),
                'logout'
            );
        }

        session()->destroy();

        return redirect()->to(base_url('auth/login'))
            ->with('success', 'Anda berhasil logout.');
    }


    // ==========================================
    // PRIVATE HELPERS
    // ==========================================

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

    private function jsonSuccess(string $redirect, string $message = ''): \CodeIgniter\HTTP\ResponseInterface
    {
        return $this->response
            ->setContentType('application/json')
            ->setJSON([
                'success'  => true,
                'redirect' => $redirect,
                'message'  => $message,
            ]);
    }

    private function jsonError(string $message, string $field = ''): \CodeIgniter\HTTP\ResponseInterface
    {
        return $this->response
            ->setContentType('application/json')
            ->setStatusCode(401)
            ->setJSON([
                'success' => false,
                'message' => $message,
                'field'   => $field,
            ]);
    }

    private function logActivity(int $userId, string $role, string $status): void
    {
        $ip = $this->request->getIPAddress();
        log_message('info', "[Auth] user_id={$userId} | role={$role} | status={$status} | ip={$ip}");
    }
}
