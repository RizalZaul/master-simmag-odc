$(function () {
    'use strict';

    var cfg = window.AUTH_FORGOT_CFG || {};
    var state = {
        email: '',
        role: '',
        otpTimer: null,
        lockTimer: null,
        lockRemaining: 0,
        isVerifyingOtp: false,
        isSubmittingReset: false
    };

    var $alertGlobal = $('#authAlert');

    function updateCsrfHash(newHash) {
        if (!newHash) {
            return;
        }

        cfg.csrfHash = newHash;
        $('input[name="' + cfg.csrfName + '"]').val(newHash);
    }

    function getCsrfBody(extra) {
        var data = {};
        data[cfg.csrfName] = cfg.csrfHash;
        return $.extend(data, extra || {});
    }

    function showAlert(type, msg) {
        $alertGlobal
            .removeClass('error success')
            .addClass(type)
            .find('.alert-text').text(msg || '');
        $alertGlobal.addClass('visible');
    }

    function hideAlert() {
        $alertGlobal.removeClass('visible error success');
        $alertGlobal.find('.alert-text').text('');
    }

    function showFieldError($field, msg) {
        $field.addClass('is-invalid');
        $field.closest('.form-group').find('.form-error').addClass('visible').find('span').text(msg);
    }

    function clearFieldError($field) {
        $field.removeClass('is-invalid');
        $field.closest('.form-group').find('.form-error').removeClass('visible').find('span').text('');
    }

    function clearAllFieldErrors() {
        $('.form-control').each(function () {
            clearFieldError($(this));
        });
    }

    function buildMissingFieldsMessage(missingFields, totalRequired) {
        var labels = Array.from(new Set((missingFields || []).filter(Boolean)));
        if (!labels.length) return 'Semua field harus diisi.';
        if (totalRequired && labels.length >= totalRequired) return 'Semua field harus diisi.';
        if (labels.length === 1) return labels[0] + ' wajib diisi.';
        return 'Field berikut wajib diisi: ' + labels.join(', ') + '.';
    }

    function setStep(step) {
        $('.auth-step').removeClass('is-active is-done');

        $('.auth-step').each(function () {
            var stepNo = parseInt($(this).data('step'), 10);
            if (stepNo < step) {
                $(this).addClass('is-done');
            } else if (stepNo === step) {
                $(this).addClass('is-active');
            }
        });

        $('#otpPanel').toggle(step >= 2);
        $('#resetPanel').toggle(step >= 3);
    }

    function setButtonLoading($btn, isLoading) {
        $btn.toggleClass('loading', isLoading).prop('disabled', isLoading);
    }

    function formatCountdown(seconds) {
        var total = Math.max(0, parseInt(seconds, 10) || 0);
        var minutes = Math.floor(total / 60);
        var remain = total % 60;
        return String(minutes).padStart(2, '0') + ':' + String(remain).padStart(2, '0');
    }

    function stopOtpCountdown() {
        if (state.otpTimer) {
            clearInterval(state.otpTimer);
            state.otpTimer = null;
        }
    }

    function startOtpCountdown(seconds) {
        stopOtpCountdown();

        var remaining = Math.max(0, parseInt(seconds, 10) || 0);
        $('#otpCountdownText').text('Berlaku ' + formatCountdown(remaining));

        state.otpTimer = setInterval(function () {
            remaining -= 1;
            if (remaining <= 0) {
                stopOtpCountdown();
                $('#otpCountdownText').text('OTP kadaluarsa. Kirim ulang OTP.');
                return;
            }

            $('#otpCountdownText').text('Berlaku ' + formatCountdown(remaining));
        }, 1000);
    }

    function stopLockCountdown() {
        if (state.lockTimer) {
            clearInterval(state.lockTimer);
            state.lockTimer = null;
        }

        state.lockRemaining = 0;
        $('#lockNotice').hide();
        $('#btnSendResetOtp').prop('disabled', false);
    }

    function startLockCountdown(seconds) {
        stopLockCountdown();

        state.lockRemaining = Math.max(0, parseInt(seconds, 10) || 0);
        $('#lockNotice').show();
        $('#btnSendResetOtp').prop('disabled', true);

        function render() {
            $('#lockCountdownText').text('Coba lagi dalam ' + formatCountdown(state.lockRemaining) + '.');
        }

        render();

        state.lockTimer = setInterval(function () {
            state.lockRemaining -= 1;
            if (state.lockRemaining <= 0) {
                stopLockCountdown();
                showAlert('success', 'Masa tunggu selesai. Anda dapat meminta OTP kembali.');
                return;
            }

            render();
        }, 1000);
    }

    function resetToEmailStep() {
        stopOtpCountdown();
        stopLockCountdown();
        hideAlert();
        clearAllFieldErrors();
        state.isVerifyingOtp = false;
        state.isSubmittingReset = false;

        setStep(1);
        $('#inputResetEmail').prop('readonly', false).focus();
        $('#inputResetOtp').val('');
        $('#inputPasswordBaru, #inputKonfirmasiPassword').val('');
        $('#btnChangeResetEmail').hide();
        $('#otpEmailTarget').text('-');
        $('#otpCountdownText').text('Berlaku 05:00');
    }

    function handleAjaxError(xhr, fallbackField) {
        var response = null;

        try {
            response = JSON.parse(xhr.responseText || '{}');
        } catch (err) {
            response = null;
        }

        if (response && response.csrfHash) {
            updateCsrfHash(response.csrfHash);
        }

        if (response && response.lockRemaining) {
            startLockCountdown(response.lockRemaining);
        }

        if (response && response.blocked) {
            stopOtpCountdown();
            $('#btnSendResetOtp, #btnVerifyResetOtp, #btnSubmitResetPassword').prop('disabled', true);
        }

        if (response && response.resetExpired) {
            resetToEmailStep();
        }

        showAlert('error', (response && response.message) || 'Terjadi kesalahan. Silakan coba lagi.');

        if (response && response.field) {
            if (response.field === 'email') {
                showFieldError($('#inputResetEmail'), response.message);
            } else if (response.field === 'otp') {
                showFieldError($('#inputResetOtp'), response.message);
            } else if (response.field === 'password') {
                showFieldError($(fallbackField || '#inputPasswordBaru'), response.message);
            }
        }
    }

    function sendOtp() {
        var $btn = $('#btnSendResetOtp');
        var email = $.trim($('#inputResetEmail').val());

        hideAlert();
        clearFieldError($('#inputResetEmail'));

        if (!email) {
            showFieldError($('#inputResetEmail'), 'Email akun tidak boleh kosong.');
            return;
        }

        setButtonLoading($btn, true);

        $.ajax({
            url: cfg.sendOtpUrl,
            method: 'POST',
            dataType: 'json',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            data: getCsrfBody({ email: email }),
            success: function (res) {
                updateCsrfHash(res && res.csrfHash);
                setButtonLoading($btn, false);

                if (!res || !res.success) {
                    handleAjaxError({ responseText: JSON.stringify(res || {}) }, '#inputResetEmail');
                    return;
                }

                state.email = res.email || email;
                state.role = res.role || '';

                $('#inputResetEmail').val(state.email).prop('readonly', true);
                $('#otpEmailTarget').text(res.maskedEmail || state.email);
                $('#btnChangeResetEmail').show();

                setStep(2);
                startOtpCountdown(res.ttl || 300);
                $('#inputResetOtp').focus();
                showAlert('success', res.message || 'OTP berhasil dikirim.');
            },
            error: function (xhr) {
                setButtonLoading($btn, false);
                handleAjaxError(xhr, '#inputResetEmail');
            }
        });
    }

    function verifyOtp() {
        var email = $.trim($('#inputResetEmail').val());
        var otp = $.trim($('#inputResetOtp').val());

        if (state.isVerifyingOtp) {
            return;
        }

        hideAlert();
        clearFieldError($('#inputResetOtp'));

        if (!otp || otp.length !== 6) {
            showFieldError($('#inputResetOtp'), 'Masukkan kode OTP 6 digit.');
            return;
        }

        state.isVerifyingOtp = true;
        $('#inputResetOtp').prop('disabled', true);

        $.ajax({
            url: cfg.verifyOtpUrl,
            method: 'POST',
            dataType: 'json',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            data: getCsrfBody({ email: email, otp: otp }),
            success: function (res) {
                updateCsrfHash(res && res.csrfHash);
                state.isVerifyingOtp = false;
                $('#inputResetOtp').prop('disabled', false);

                if (!res || !res.success) {
                    handleAjaxError({ responseText: JSON.stringify(res || {}) }, '#inputResetOtp');
                    return;
                }

                stopOtpCountdown();
                setStep(3);
                $('#inputPasswordBaru').focus();
                showAlert('success', res.message || 'OTP berhasil diverifikasi.');
            },
            error: function (xhr) {
                state.isVerifyingOtp = false;
                $('#inputResetOtp').prop('disabled', false);
                handleAjaxError(xhr, '#inputResetOtp');
            }
        });
    }

    function submitResetPassword() {
        var $btn = $('#btnSubmitResetPassword');
        var email = $.trim($('#inputResetEmail').val());
        var passwordBaru = $('#inputPasswordBaru').val();
        var konfirmasi = $('#inputKonfirmasiPassword').val();
        var missingFields = [];

        if (state.isSubmittingReset) {
            return;
        }

        hideAlert();
        clearFieldError($('#inputPasswordBaru'));
        clearFieldError($('#inputKonfirmasiPassword'));

        if (!passwordBaru) {
            missingFields.push('Password Baru');
            showFieldError($('#inputPasswordBaru'), 'Password baru tidak boleh kosong.');
        }

        if (!konfirmasi) {
            missingFields.push('Konfirmasi Password');
            showFieldError($('#inputKonfirmasiPassword'), 'Konfirmasi password tidak boleh kosong.');
        }

        if (missingFields.length) {
            showAlert('error', buildMissingFieldsMessage(missingFields, 2));
            return;
        }

        state.isSubmittingReset = true;
        setButtonLoading($btn, true);

        $.ajax({
            url: cfg.resetUrl,
            method: 'POST',
            dataType: 'json',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            data: getCsrfBody({
                email: email,
                password_baru: passwordBaru,
                konfirmasi_password: konfirmasi
            }),
            success: function (res) {
                updateCsrfHash(res && res.csrfHash);
                state.isSubmittingReset = false;
                setButtonLoading($btn, false);

                if (!res || !res.success) {
                    handleAjaxError({ responseText: JSON.stringify(res || {}) }, '#inputPasswordBaru');
                    return;
                }

                showAlert('success', res.message || 'Password berhasil diperbarui.');

                setTimeout(function () {
                    window.location.href = res.redirect || cfg.loginUrl;
                }, 900);
            },
            error: function (xhr) {
                state.isSubmittingReset = false;
                setButtonLoading($btn, false);
                handleAjaxError(xhr, '#inputPasswordBaru');
            }
        });
    }

    $('.btn-toggle-pw').on('click', function () {
        var target = $(this).data('target');
        var $input = $(target);
        var $icon = $(this).find('i');

        if (!$input.length) {
            return;
        }

        if ($input.attr('type') === 'password') {
            $input.attr('type', 'text');
            $icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            $input.attr('type', 'password');
            $icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });

    $('.form-control').on('input', function () {
        clearFieldError($(this));
        hideAlert();
    });

    $('#inputResetOtp').on('input', function () {
        var value = ($(this).val() || '').replace(/\D+/g, '').slice(0, 6);
        $(this).val(value);
    });

    $('#btnChangeResetEmail').on('click', function () {
        resetToEmailStep();
    });

    $('#btnSendResetOtp').on('click', sendOtp);
    $('#btnSubmitResetPassword').on('click', submitResetPassword);

    $('#inputResetEmail').on('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            sendOtp();
        }
    });

    $('#inputResetOtp').on('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            if ($.trim($(this).val()).length === 6) {
                verifyOtp();
            }
        }
    });

    $('#inputPasswordBaru, #inputKonfirmasiPassword').on('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            submitResetPassword();
        }
    });

    $('#inputResetOtp').on('input', function () {
        var value = $.trim($(this).val());
        if (value.length === 6) {
            verifyOtp();
        }
    });
});
