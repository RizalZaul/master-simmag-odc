<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * AuthFilter
 *
 * Filter tunggal yang menangani empat kondisi:
 *   1. Belum login                                        -> redirect ke auth/login
 *   2. Login tapi akun dinonaktifkan/dihapus admin        -> force logout + redirect ke auth/login
 *   3. Login tapi akses route 'admin' padahal role 'pkl'  -> redirect ke pkl/dashboard
 *   4. Login tapi akses route 'pkl' padahal role 'admin'  -> redirect ke /
 *
 * Cara pakai di Routes.php:
 *   $routes->group('/',    ['filter' => 'auth:admin'], ...);
 *   $routes->group('pkl',  ['filter' => 'auth:pkl'],   ...);
 *   $routes->get('auth/login', ..., ['filter' => 'auth:guest']);
 *
 * ============================================================
 * CHANGELOG / BUG FIX:
 * ============================================================
 *
 * [FIX 1] after() mengembalikan $response padahal seharusnya null
 *
 * [FIX 2] Request AJAX yang tidak terautentikasi mendapat redirect (HTML)
 *         bukan JSON 401.
 *
 * [FIX 3] Route POST auth/login tidak diproteksi filter guest.
 *         Ditangani langsung di AuthController::processLogin().
 *
 * [FIX 4] isAJAX() tidak ada di RequestInterface (base interface).
 *         FilterInterface::before() menerima RequestInterface, bukan
 *         IncomingRequest. Method isAJAX() hanya ada di IncomingRequest.
 *         Fix: cek header X-Requested-With secara langsung.
 * ============================================================
 */
class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session  = session();
        $loggedIn = $session->get('logged_in');
        $role     = $session->get('role');
        $required = $arguments[0] ?? null; // 'admin' | 'pkl' | 'guest'

        // [FIX 4] Gunakan getHeaderLine() — tersedia di RequestInterface
        // isAJAX() hanya ada di IncomingRequest, tidak di RequestInterface
        $isAjax = $request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest';

        // ── Kasus guest (halaman login) ──────────────────────────────────
        if ($required === 'guest') {
            if ($loggedIn) {
                if ($isAjax) {
                    return $this->jsonRedirect(
                        $this->getRedirectUrl($role),
                        'Anda sudah login.'
                    );
                }
                return $this->redirectByRole($role);
            }
            return null;
        }

        // ── Belum login ──────────────────────────────────────────────────
        if (! $loggedIn) {
            $sessionConfig = config('Session');
            $cookieName    = $sessionConfig->cookieName ?? 'ci_session';
            $hasSessionCookie = false;

            if ($request instanceof \CodeIgniter\HTTP\IncomingRequest) {
                $hasSessionCookie = $request->getCookie($cookieName) !== null;
            }

            $message = $hasSessionCookie
                ? 'Sesi Anda telah berakhir karena tidak aktif. Silakan login kembali.'
                : 'Silakan login terlebih dahulu.';

            if ($isAjax) {
                return $this->jsonUnauthorized($message);
            }
            return redirect()
                ->to(base_url('auth/login'))
                ->with('error', $message);
        }

        // ── Cek status akun secara real-time ke DB ───────────────────────
        $userId = $session->get('user_id');
        if ($userId) {
            $user = \Config\Database::connect()
                ->table('users')
                ->select('status')
                ->where('id_user', $userId)
                ->get()
                ->getRowArray();

            if (! $user || $user['status'] !== 'aktif') {
                $session->destroy();

                if ($isAjax) {
                    return $this->jsonUnauthorized('Akun Anda telah dinonaktifkan. Hubungi administrator.');
                }
                return redirect()
                    ->to(base_url('auth/login'))
                    ->with('error', 'Akun Anda telah dinonaktifkan. Hubungi administrator.');
            }
        }

        // ── Role mismatch ────────────────────────────────────────────────
        if ($required && $role !== $required) {
            if ($isAjax) {
                return $this->jsonRedirect(
                    $this->getRedirectUrl($role),
                    'Akses ditolak. Anda akan diarahkan ke dashboard.'
                );
            }
            return $this->redirectByRole($role);
        }

        return null;
    }

    /**
     * [FIX 1] Return null, bukan $response.
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    private function redirectByRole(?string $role): \CodeIgniter\HTTP\RedirectResponse
    {
        return match ($role) {
            'admin' => redirect()->to(base_url('/')),
            'pkl'   => redirect()->to(base_url('pkl/dashboard')),
            default => redirect()->to(base_url('auth/login')),
        };
    }

    private function getRedirectUrl(?string $role): string
    {
        return match ($role) {
            'admin' => base_url('/'),
            'pkl'   => base_url('pkl/dashboard'),
            default => base_url('auth/login'),
        };
    }

    private function jsonUnauthorized(string $message): \CodeIgniter\HTTP\ResponseInterface
    {
        return \Config\Services::response()
            ->setStatusCode(401)
            ->setContentType('application/json')
            ->setJSON([
                'success'  => false,
                'message'  => $message,
                'redirect' => base_url('auth/login'),
            ]);
    }

    private function jsonRedirect(string $url, string $message = ''): \CodeIgniter\HTTP\ResponseInterface
    {
        return \Config\Services::response()
            ->setStatusCode(403)
            ->setContentType('application/json')
            ->setJSON([
                'success'  => false,
                'message'  => $message,
                'redirect' => $url,
            ]);
    }
}
