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
 *   SEBELUM : return $response;
 *   SESUDAH : return null;
 *   ALASAN  : CI4 FilterInterface::after() mengembalikan
 *             ResponseInterface|null. Mengembalikan $response
 *             berarti filter "mengklaim" sudah memodifikasi
 *             response, yang bisa menyebabkan pipeline filter
 *             berikutnya melewati response yang salah.
 *             Return null = "tidak ada modifikasi, lanjut".
 *
 * [FIX 2] Request AJAX yang tidak terautentikasi mendapat redirect (HTML)
 *         bukan JSON 401 → menyebabkan AJAX handler error karena
 *         mengharapkan JSON.
 *   SEBELUM : redirect()->to(...) pada semua kondisi
 *   SESUDAH : Jika request adalah AJAX, kembalikan JSON 401
 *             { success: false, message: '...', redirect: '...' }
 *   ALASAN  : jQuery/fetch akan mengikuti redirect dan menerima HTML.
 *             auth.js mengharapkan JSON → JSON.parse error / UI rusak.
 *
 * [FIX 3] Route POST auth/login tidak diproteksi filter guest
 *         → Jika user sudah login bisa kirim POST ulang dan
 *           memproses login lagi (duplikasi session).
 *   SEBELUM : hanya GET auth/login yang pakai filter 'auth:guest'
 *   SESUDAH : tambahkan pengecekan $loggedIn pada processLogin
 *             di AuthController, ATAU tangani di filter ini
 *             dengan menambah proteksi pada method POST.
 *   CATATAN : Fix ini diterapkan langsung di AuthController::processLogin()
 *             (sudah ada pengecekan session di awal method),
 *             bukan di filter — karena filter tidak bisa membedakan
 *             POST login dari POST lain pada route yang sama.
 *             Lihat AuthController.php untuk implementasinya.
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
        $isAjax   = $request->isAJAX();   // [FIX 2]

        // ── Kasus guest (halaman login) ──────────────────────────────────
        // Jika sudah login dan mencoba akses /auth/login, redirect ke dashboard.
        if ($required === 'guest') {
            if ($loggedIn) {
                // [FIX 2] AJAX tidak perlu redirect — return JSON
                if ($isAjax) {
                    return $this->jsonRedirect(
                        $this->getRedirectUrl($role),
                        'Anda sudah login.'
                    );
                }
                return $this->redirectByRole($role);
            }
            return null; // belum login → lanjut ke halaman login
        }

        // ── Kasus protected route ────────────────────────────────────────
        // Belum login → ke halaman login.
        if (! $loggedIn) {
            // [FIX 2] AJAX → JSON 401, bukan redirect HTML
            if ($isAjax) {
                return $this->jsonUnauthorized('Sesi Anda telah berakhir. Silakan login kembali.');
            }

            return redirect()
                ->to(base_url('auth/login'))
                ->with('error', 'Silakan login terlebih dahulu.');
        }

        // ── Cek status akun secara real-time ke DB ───────────────────────
        // Session hanya menyimpan snapshot saat login. Tanpa pengecekan ini,
        // user yang dinonaktifkan oleh admin akan tetap bisa menggunakan sistem
        // selama session mereka belum expire.
        $userId = $session->get('user_id');
        if ($userId) {
            $user = \Config\Database::connect()
                ->table('users')
                ->select('status')
                ->where('id_user', $userId)
                ->get()
                ->getRowArray();

            // Akun tidak ditemukan (dihapus) ATAU statusnya bukan 'aktif'
            if (! $user || $user['status'] !== 'aktif') {
                $session->destroy();

                // [FIX 2] AJAX → JSON 401
                if ($isAjax) {
                    return $this->jsonUnauthorized('Akun Anda telah dinonaktifkan. Hubungi administrator.');
                }

                return redirect()
                    ->to(base_url('auth/login'))
                    ->with('error', 'Akun Anda telah dinonaktifkan. Hubungi administrator.');
            }
        }

        // ── Role mismatch → redirect ke dashboard yang benar ────────────
        if ($required && $role !== $required) {
            // [FIX 2] AJAX → JSON dengan redirect URL
            if ($isAjax) {
                return $this->jsonRedirect(
                    $this->getRedirectUrl($role),
                    'Akses ditolak. Anda akan diarahkan ke dashboard.'
                );
            }
            return $this->redirectByRole($role);
        }

        // Lanjut normal
        return null;
    }

    /**
     * [FIX 1] Kembalikan null, bukan $response.
     * Return null = "tidak memodifikasi response, lanjutkan pipeline".
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null; // [FIX 1] was: return $response
    }


    // ── Helpers ─────────────────────────────────────────────────────────

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

    /**
     * [FIX 2] Response JSON 401 untuk AJAX yang tidak terautentikasi.
     */
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

    /**
     * [FIX 2] Response JSON untuk AJAX yang sudah login tapi salah role/halaman.
     */
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
