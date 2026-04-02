<?php

namespace App\Services;

use CodeIgniter\Email\Email;

/**
 * EmailService
 *
 * Service reusable untuk kirim email di SIMMAG ODC.
 */
class EmailService
{
  protected Email $mailer;

  public function __construct()
  {
    helper('tgl');
    $this->mailer = \Config\Services::email();
    $this->mailer->initialize([
      'protocol'   => 'smtp',
      'SMTPHost'   => env('email.SMTPHost', 'smtp.gmail.com'),
      'SMTPUser'   => env('email.SMTPUser', ''),
      'SMTPPass'   => env('email.SMTPPass', ''),
      'SMTPPort'   => (int) env('email.SMTPPort', 587),
      'SMTPCrypto' => env('email.SMTPCrypto', 'tls'),
      'mailType'   => 'html',
      'charset'    => 'utf-8',
      'fromEmail'  => env('email.fromEmail', ''),
      'fromName'   => env('email.fromName', 'SIMMAG ODC'),
    ]);
  }

  // ── Kirim Info Login PKL ke 1 Anggota ──────────────────────────

  public function sendInfoLoginPkl(array $anggota, ?string $namaInstansi = null): bool
  {
    $html = $this->buildTemplateLogin($anggota, $namaInstansi);

    $this->mailer->clear();
    $this->mailer->setTo($anggota['email'], $anggota['nama_lengkap']);
    $this->mailer->setSubject('Informasi Login SIMMAG ODC — ' . $anggota['nama_lengkap']);
    $this->mailer->setMessage($html);

    return $this->mailer->send();
  }

  // ── Kirim Rekapan Semua Anggota ke Ketua Instansi ──────────────

  public function sendRekapKetua(array $ketua, array $allAnggota, array $kelompok): bool
  {
    $html = $this->buildTemplateRekapKetua($ketua, $allAnggota, $kelompok);

    $this->mailer->clear();
    $this->mailer->setTo($ketua['email'], $ketua['nama_lengkap']);
    $this->mailer->setSubject('Rekapan Info Login Kelompok PKL — ' . ($kelompok['nama_kelompok'] ?? 'PKL'));
    $this->mailer->setMessage($html);

    return $this->mailer->send();
  }

  // ── Template: Info Login Anggota ────────────────────────────────

  private function buildTemplateLogin(array $a, ?string $namaInstansi): string
  {
    $kategoriLabel = $namaInstansi ? esc($namaInstansi) : 'Mandiri';
    $periode       = tglShortIndo($a['tgl_mulai']) . ' s/d ' . tglShortIndo($a['tgl_akhir']);

    return <<<HTML
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
  body{font-family:'Segoe UI',Arial,sans-serif;background:#f1f5f9;margin:0;padding:12px;}
  .wrap{max-width:560px;margin:0 auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 12px rgba(0,0,0,.08);}
  .header{background:linear-gradient(135deg,#0f766e,#0d9488);padding:24px 24px;text-align:center;}
  .header h1{color:#fff;margin:0;font-size:1.2rem;}
  .header p{color:rgba(255,255,255,.85);margin:6px 0 0;font-size:.85rem;}
  .body{padding:24px;}
  .greeting{font-size:1rem;color:#1e293b;margin-bottom:14px;}
  .intro{color:#475569;font-size:.875rem;line-height:1.6;margin-bottom:18px;}
  table.cred{width:100%;border-collapse:collapse;margin-bottom:18px;}
  table.cred td{padding:10px 12px;border:1px solid #e5e7eb;font-size:.875rem;}
  table.cred td:first-child{background:#f8fafc;color:#64748b;font-weight:600;width:36%;white-space:nowrap;}
  table.cred td:last-child{color:#1e293b;font-weight:700;word-break:break-all;}
  .info-box{background:#f0fdfa;border:1px solid #ccfbf1;border-radius:8px;padding:12px 16px;margin-bottom:18px;}
  .info-box .label{font-size:.75rem;font-weight:700;color:#0f766e;text-transform:uppercase;letter-spacing:.04em;}
  .info-box .value{font-size:.875rem;color:#134e4a;margin-top:4px;}
  .warning{background:#fffbeb;border:1px solid #fde68a;border-radius:8px;padding:12px 14px;font-size:.8rem;color:#92400e;}
  .footer{padding:14px 24px;background:#f8fafc;text-align:center;font-size:.75rem;color:#94a3b8;border-top:1px solid #e5e7eb;}
</style>
</head>
<body>
<div class="wrap">
  <div class="header">
    <h1>Informasi Login PKL</h1>
    <p>SIMMAG ODC — PT Our Digital Creative</p>
  </div>
  <div class="body">
    <p class="greeting">Halo, <strong>{$a['nama_lengkap']}</strong>!</p>
    <p class="intro">Akun PKL Anda telah dibuat oleh admin. Berikut informasi login Anda ke sistem SIMMAG ODC:</p>
    <table class="cred">
      <tr><td>Username</td><td>{$a['username']}</td></tr>
      <tr><td>Password</td><td>{$a['password_plain']}</td></tr>
    </table>
    <div class="info-box">
      <div class="label">Info PKL Anda</div>
      <div class="value">Kategori : {$kategoriLabel}</div>
      <div class="value">Periode  : {$periode}</div>
    </div>
    <div class="warning">⚠️ Segera ganti password Anda setelah login pertama.</div>
  </div>
  <div class="footer">© SIMMAG ODC — PT Our Digital Creative</div>
</div>
</body>
</html>
HTML;
  }

  // ── Template: Rekapan Ketua ─────────────────────────────────────

  private function buildTemplateRekapKetua(array $ketua, array $allAnggota, array $kelompok): string
  {
    $periode = tglShortIndo($kelompok['tgl_mulai']) . ' s/d ' . tglShortIndo($kelompok['tgl_akhir']);

    // A4-FIX: Ganti tabel → card per anggota.
    // Tabel 5 kolom butuh ~520px, tidak pernah nyaman di mobile portrait.
    // Card layout vertikal tidak membutuhkan scroll horizontal sama sekali.
    $cards = '';
    foreach ($allAnggota as $idx => $a) {
      $no        = $idx + 1;
      $isKetua   = $a['role'] === 'ketua';
      $roleBadge = $isKetua
        ? '<span style="font-size:.7rem;background:#0f766e;color:#fff;padding:2px 8px;border-radius:4px;vertical-align:middle;margin-left:6px;">Ketua</span>'
        : '<span style="font-size:.7rem;background:#e0f2fe;color:#0369a1;padding:2px 8px;border-radius:4px;vertical-align:middle;margin-left:6px;">Anggota</span>';

      $cards .= '<div style="background:#f8fafc;border:1px solid #e5e7eb;border-radius:10px;padding:16px;margin-bottom:12px;">'
        . '<div style="font-weight:700;font-size:.9rem;color:#0f766e;margin-bottom:12px;padding-bottom:8px;border-bottom:1px solid #e5e7eb;">'
        . $no . '. ' . esc($a['nama_lengkap']) . $roleBadge
        . '</div>'
        . '<table style="width:100%;border-collapse:collapse;font-size:.84rem;">'
        . '<tr><td style="color:#64748b;padding:5px 0;width:38%;vertical-align:top;">Email</td>'
        . '<td style="color:#1e293b;padding:5px 0;font-weight:600;word-break:break-all;">' . esc($a['email']) . '</td></tr>'
        . '<tr><td style="color:#64748b;padding:5px 0;vertical-align:top;">Username</td>'
        . '<td style="color:#1e293b;padding:5px 0;font-weight:600;">' . esc($a['username']) . '</td></tr>'
        . '<tr><td style="color:#64748b;padding:5px 0;vertical-align:top;">Password</td>'
        . '<td style="color:#0f766e;padding:5px 0;font-weight:700;letter-spacing:.02em;">' . esc($a['password_plain']) . '</td></tr>'
        . '</table>'
        . '</div>';
    }

    $namaKelompok = esc($kelompok['nama_kelompok'] ?? '-');
    $namaInstansi = esc($kelompok['nama_instansi'] ?? '-');

    return <<<HTML
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
  body{font-family:'Segoe UI',Arial,sans-serif;background:#f1f5f9;margin:0;padding:12px;}
  .wrap{max-width:600px;margin:0 auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 12px rgba(0,0,0,.08);}
  .header{background:linear-gradient(135deg,#0f766e,#0d9488);padding:24px;text-align:center;}
  .header h1{color:#fff;margin:0;font-size:1.2rem;}
  .header p{color:rgba(255,255,255,.85);margin:6px 0 0;font-size:.85rem;}
  .body{padding:24px;}
  .intro{color:#475569;font-size:.875rem;line-height:1.6;margin-bottom:18px;}
  .info-box{background:#f0fdfa;border:1px solid #ccfbf1;border-radius:8px;padding:14px 16px;margin-bottom:20px;}
  .info-box .label{font-size:.75rem;font-weight:700;color:#0f766e;text-transform:uppercase;margin-bottom:6px;}
  .info-box .value{font-size:.875rem;color:#134e4a;margin-top:3px;}
  .section-title{font-size:.8rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.04em;margin-bottom:12px;}
  .warning{background:#fffbeb;border:1px solid #fde68a;border-radius:8px;padding:12px 14px;font-size:.8rem;color:#92400e;margin-top:4px;}
  .footer{padding:14px 24px;background:#f8fafc;text-align:center;font-size:.75rem;color:#94a3b8;border-top:1px solid #e5e7eb;}
  @media(max-width:480px){
    body{padding:6px;}
    .body{padding:16px 12px;}
    .header{padding:20px 12px;}
  }
</style>
</head>
<body>
<div class="wrap">
  <div class="header">
    <h1>Rekapan Info Login Kelompok PKL</h1>
    <p>SIMMAG ODC — PT Our Digital Creative</p>
  </div>
  <div class="body">
    <p class="intro">Halo <strong>{$ketua['nama_lengkap']}</strong>, berikut rekapan informasi login seluruh anggota kelompok PKL Anda:</p>
    <div class="info-box">
      <div class="label">Info Kelompok</div>
      <div class="value">Nama Kelompok : {$namaKelompok}</div>
      <div class="value">Instansi      : {$namaInstansi}</div>
      <div class="value">Periode       : {$periode}</div>
    </div>
    <div class="section-title"><i>👤</i> Data Login Anggota</div>
    {$cards}
    <div class="warning">⚠️ Harap informasikan kepada setiap anggota untuk segera mengganti password setelah login pertama.</div>
  </div>
  <div class="footer">© SIMMAG ODC — PT Our Digital Creative</div>
</div>
</body>
</html>
HTML;
  }

  // ── Kirim OTP Form Biodata ──────────────────────────────────────

  public function sendOtpBiodata(string $email, string $otp): bool
  {
    $html = $this->buildTemplateOtp($email, $otp);

    $this->mailer->clear();
    $this->mailer->setTo($email);
    $this->mailer->setSubject('Kode OTP Pendaftaran PKL — SIMMAG ODC');
    $this->mailer->setMessage($html);

    return $this->mailer->send();
  }

  public function sendOtpResetPassword(string $email, string $otp, string $role): bool
  {
    $html = $this->buildTemplateResetPasswordOtp($email, $otp, $role);

    $this->mailer->clear();
    $this->mailer->setTo($email);
    $this->mailer->setSubject('Kode OTP Reset Password — SIMMAG ODC');
    $this->mailer->setMessage($html);

    return $this->mailer->send();
  }

  // ── Template OTP ────────────────────────────────────────────────

  private function buildTemplateOtp(string $email, string $otp): string
  {
    return <<<HTML
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
  body{font-family:'Segoe UI',Arial,sans-serif;background:#f1f5f9;margin:0;padding:12px;}
  .wrap{max-width:480px;margin:0 auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 12px rgba(0,0,0,.08);}
  .header{background:linear-gradient(135deg,#0f766e,#0d9488);padding:24px;text-align:center;}
  .header h1{color:#fff;margin:0;font-size:1.1rem;}
  .header p{color:rgba(255,255,255,.85);margin:6px 0 0;font-size:.82rem;}
  .body{padding:28px 24px;text-align:center;}
  .greeting{font-size:.9rem;color:#475569;margin-bottom:20px;}
  .otp-box{display:inline-block;background:#f0fdfa;border:2px dashed #0f766e;border-radius:12px;padding:18px 36px;margin:0 auto 20px;}
  .otp-code{font-size:2.2rem;font-weight:800;letter-spacing:.3em;color:#0f766e;font-family:monospace;}
  .otp-note{font-size:.8rem;color:#64748b;margin-top:6px;}
  .warning{background:#fffbeb;border:1px solid #fde68a;border-radius:8px;padding:10px 14px;font-size:.78rem;color:#92400e;margin-top:16px;}
  .footer{padding:14px 24px;background:#f8fafc;text-align:center;font-size:.72rem;color:#94a3b8;border-top:1px solid #e5e7eb;}
</style>
</head>
<body>
<div class="wrap">
  <div class="header">
    <h1>Verifikasi Email</h1>
    <p>SIMMAG ODC — Form Pendaftaran PKL</p>
  </div>
  <div class="body">
    <p class="greeting">Gunakan kode OTP berikut untuk memverifikasi email <strong>{$email}</strong>:</p>
    <div class="otp-box">
      <div class="otp-code">{$otp}</div>
      <div class="otp-note">Berlaku selama <strong>5 menit</strong></div>
    </div>
    <div class="warning">⚠️ Jangan berikan kode ini kepada siapapun. Admin tidak pernah meminta kode OTP.</div>
  </div>
  <div class="footer">© SIMMAG ODC — PT Our Digital Creative</div>
</div>
</body>
</html>
HTML;
  }

  private function buildTemplateResetPasswordOtp(string $email, string $otp, string $role): string
  {
    $roleLabel = strtoupper($role) === 'ADMIN' ? 'Admin' : 'PKL';

    return <<<HTML
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
  body{font-family:'Segoe UI',Arial,sans-serif;background:#f1f5f9;margin:0;padding:12px;}
  .wrap{max-width:480px;margin:0 auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 12px rgba(0,0,0,.08);}
  .header{background:linear-gradient(135deg,#0f766e,#0d9488);padding:24px;text-align:center;}
  .header h1{color:#fff;margin:0;font-size:1.1rem;}
  .header p{color:rgba(255,255,255,.85);margin:6px 0 0;font-size:.82rem;}
  .body{padding:28px 24px;text-align:center;}
  .greeting{font-size:.9rem;color:#475569;margin-bottom:20px;line-height:1.6;}
  .otp-box{display:inline-block;background:#f0fdfa;border:2px dashed #0f766e;border-radius:12px;padding:18px 36px;margin:0 auto 20px;}
  .otp-code{font-size:2.2rem;font-weight:800;letter-spacing:.3em;color:#0f766e;font-family:monospace;}
  .otp-note{font-size:.8rem;color:#64748b;margin-top:6px;}
  .warning{background:#fffbeb;border:1px solid #fde68a;border-radius:8px;padding:10px 14px;font-size:.78rem;color:#92400e;margin-top:16px;text-align:left;}
  .footer{padding:14px 24px;background:#f8fafc;text-align:center;font-size:.72rem;color:#94a3b8;border-top:1px solid #e5e7eb;}
</style>
</head>
<body>
<div class="wrap">
  <div class="header">
    <h1>Reset Password</h1>
    <p>SIMMAG ODC — Verifikasi OTP</p>
  </div>
  <div class="body">
    <p class="greeting">Ada permintaan reset password untuk akun <strong>{$roleLabel}</strong> dengan email <strong>{$email}</strong>.</p>
    <div class="otp-box">
      <div class="otp-code">{$otp}</div>
      <div class="otp-note">Berlaku selama <strong>5 menit</strong></div>
    </div>
    <div class="warning">⚠️ Jika Anda tidak merasa meminta reset password, abaikan email ini. Jangan berikan kode OTP kepada siapapun.</div>
  </div>
  <div class="footer">© SIMMAG ODC — PT Our Digital Creative</div>
</div>
</body>
</html>
HTML;
  }
}
