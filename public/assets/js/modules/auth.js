/**
 * SIMMAG ODC — Auth / Login JS
 * public/assets/js/modules/auth.js
 * Dependency: jQuery (loaded via CDN di login.php)
 */

$(function () {
    'use strict';

    const $form = $('#loginForm');
    const $btnLogin = $('#btnLogin');
    const $alertGlobal = $('#authAlert');

    // ── Password Toggle ──────────────────────────────────────────

    $('#btnTogglePw').on('click', function () {
        const $input = $('#inputPassword');
        const $icon = $(this).find('i');

        if ($input.attr('type') === 'password') {
            $input.attr('type', 'text');
            $icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            $input.attr('type', 'password');
            $icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });

    // ── Show / Hide inline error ──────────────────────────────────

    function showFieldError($field, msg) {
        $field.addClass('is-invalid');
        const $err = $field.closest('.form-group').find('.form-error');
        $err.text(msg).addClass('visible');
    }

    function clearFieldError($field) {
        $field.removeClass('is-invalid');
        $field.closest('.form-group').find('.form-error').text('').removeClass('visible');
    }

    function showAlert(type, msg) {
        $alertGlobal
            .removeClass('error success')
            .addClass(type)
            .find('.alert-text').text(msg);
        $alertGlobal.addClass('visible');
    }

    function hideAlert() {
        $alertGlobal.removeClass('visible error success');
    }

    // Clear error on input
    $form.find('.form-control').on('input', function () {
        clearFieldError($(this));
        hideAlert();
    });

    // ── AJAX Submit ───────────────────────────────────────────────

    $form.on('submit', function (e) {
        e.preventDefault();

        hideAlert();
        clearFieldError($('#inputUsername'));
        clearFieldError($('#inputPassword'));

        const username = $.trim($('#inputUsername').val());
        const password = $('#inputPassword').val();

        // Client-side basic validation
        if (!username) {
            showFieldError($('#inputUsername'), 'Username atau email tidak boleh kosong.');
            return;
        }
        if (!password) {
            showFieldError($('#inputPassword'), 'Password tidak boleh kosong.');
            return;
        }

        // Loading state
        $btnLogin.addClass('loading').prop('disabled', true);

        $.ajax({
            url: $form.attr('action'),
            method: 'POST',
            data: $form.serialize(),
            dataType: 'json',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function (res) {
                if (res.success) {
                    showAlert('success', res.message || 'Login berhasil...');
                    // Redirect setelah sedikit delay agar user lihat pesan
                    setTimeout(function () {
                        window.location.href = res.redirect;
                    }, 600);
                } else {
                    $btnLogin.removeClass('loading').prop('disabled', false);
                    showAlert('error', res.message || 'Login gagal.');

                    if (res.field === 'password') {
                        showFieldError($('#inputPassword'), res.message);
                    } else if (res.field === 'username') {
                        showFieldError($('#inputUsername'), res.message);
                    }
                }
            },
            error: function (xhr) {
                $btnLogin.removeClass('loading').prop('disabled', false);
                let msg = 'Terjadi kesalahan. Silakan coba lagi.';
                try {
                    const res = JSON.parse(xhr.responseText);
                    if (res.message) msg = res.message;
                    if (res.field === 'password') showFieldError($('#inputPassword'), res.message);
                } catch (err) { /* */ }
                showAlert('error', msg);
            }
        });
    });

    // ── CSRF token refresh (opsional, jika CI4 CSRF aktif) ───────

    // Jika pakai CSRF token di form, uncomment ini:
    // function refreshCsrfToken() { /* fetch token baru lalu update hidden input */ }

});