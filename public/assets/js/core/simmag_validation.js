(function (window) {
    'use strict';

    var lastInputToast = {
        key: '',
        timestamp: 0
    };

    var liveRules = {
        person_name: {
            message: 'hanya boleh berisi huruf, spasi, titik, koma, apostrof, dan tanda hubung',
            sanitize: function (value) {
                return sanitizeTextValue(value, /[^\p{L}\s.,'-]+/gu, true);
            }
        },
        instansi_name: {
            message: 'hanya boleh berisi huruf, angka, spasi, apostrof, tanda hubung, tanda kurung, dan titik',
            sanitize: function (value) {
                return sanitizeTextValue(value, /[^\p{L}0-9\s'.()\-]+/gu, true);
            }
        },
        nickname: {
            message: 'mengandung karakter yang tidak valid',
            sanitize: function (value) {
                return sanitizeLooseValue(value, 10);
            }
        },
        group_name: {
            message: 'mengandung karakter yang tidak valid',
            sanitize: function (value) {
                return sanitizeLooseValue(value, 20);
            }
        },
        address: {
            message: 'hanya boleh berisi huruf, angka, spasi, apostrof, tanda hubung, titik, koma, garis miring, dan tanda angka (#)',
            sanitize: function (value) {
                return sanitizeTextValue(value, /[^\p{L}0-9\s'.,\-\/#+]+/gu, true);
            }
        },
        city: {
            message: 'hanya boleh berisi huruf dan spasi',
            sanitize: function (value) {
                return sanitizeTextValue(value, /[^\p{L}\s]+/gu, true);
            }
        },
        jurusan: {
            message: 'hanya boleh berisi huruf, spasi, titik, tanda hubung, dan tanda kurung',
            sanitize: function (value) {
                return sanitizeTextValue(value, /[^\p{L}\s.()\-]+/gu, true);
            }
        },
        name_code: {
            message: 'hanya boleh berisi huruf, angka, dan spasi',
            sanitize: function (value) {
                return sanitizeTextValue(value, /[^\p{L}0-9\s]+/gu, true);
            }
        },
        phone: {
            message: 'hanya boleh berisi angka dan tanda tambah (+)',
            sanitize: function (value) {
                var raw = String(value == null ? '' : value).replace(/\s+/g, '');
                var filtered = raw.replace(/[^\d+]+/g, '');
                if (filtered.indexOf('+') > 0) {
                    filtered = filtered.replace(/\+/g, '');
                } else if (filtered.indexOf('+') === 0) {
                    filtered = '+' + filtered.slice(1).replace(/\+/g, '');
                }
                return filtered.slice(0, 20);
            }
        },
        email: {
            message: 'hanya boleh berisi huruf, angka, titik, underscore, persen, plus, tanda hubung, dan simbol @',
            sanitize: function (value) {
                return String(value == null ? '' : value)
                    .replace(/\s+/g, '')
                    .replace(/[^A-Za-z0-9._%+\-@]+/g, '')
                    .slice(0, 100);
            }
        },
        url: {
            message: 'mengandung karakter URL yang tidak valid',
            sanitize: function (value) {
                return String(value == null ? '' : value)
                    .replace(/\s+/g, '')
                    .replace(/[^A-Za-z0-9\-._~:/?#\[\]@!$&'()*+,;=%]+/g, '');
            }
        },
        numeric: {
            message: 'hanya boleh berisi angka',
            sanitize: function (value) {
                return String(value == null ? '' : value).replace(/\D+/g, '');
            }
        },
        loose_text: {
            message: 'mengandung karakter yang tidak valid',
            sanitize: function (value) {
                return sanitizeLooseValue(value, null);
            }
        }
    };

    function sanitizeTextValue(value, invalidPattern, collapseSpaces) {
        var raw = String(value == null ? '' : value).replace(invalidPattern, '');
        raw = raw.replace(/^\s+/g, '');
        if (collapseSpaces !== false) {
            raw = raw.replace(/\s{2,}/g, ' ');
        }
        return raw;
    }

    function sanitizeLooseValue(value, maxLength) {
        var raw = String(value == null ? '' : value)
            .replace(/[\r\n\t]+/g, '')
            .replace(/^\s+/g, '')
            .replace(/\s{2,}/g, ' ');

        if (typeof maxLength === 'number' && maxLength > 0) {
            raw = raw.slice(0, maxLength);
        }

        return raw;
    }

    function showInputToast(message) {
        if (!message || !window.Swal) {
            return;
        }

        var now = Date.now();
        if (lastInputToast.key === message && now - lastInputToast.timestamp < 1200) {
            return;
        }

        lastInputToast = {
            key: message,
            timestamp: now
        };

        window.Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'warning',
            title: message,
            showConfirmButton: false,
            timer: 1800,
            timerProgressBar: true
        });
    }

    function normalizeSpaces(value) {
        return String(value == null ? '' : value)
            .trim()
            .replace(/\s{2,}/g, ' ');
    }

    function hasInvalidSpacing(value) {
        var str = String(value == null ? '' : value);
        return str !== str.trim() || /\s{2,}/.test(str);
    }

    function buildSpacingError(label) {
        return label + ' tidak boleh diawali/diakhiri dengan spasi dan tidak boleh mengandung spasi ganda.';
    }

    function buildMissingFieldsMessage(missingFields, totalRequired) {
        var labels = Array.from(new Set((missingFields || []).filter(Boolean)));
        if (!labels.length) return 'Semua field harus diisi.';
        if (totalRequired && labels.length >= totalRequired) return 'Semua field harus diisi.';
        if (labels.length === 1) return labels[0] + ' wajib diisi.';
        return 'Field berikut wajib diisi: ' + labels.join(', ') + '.';
    }

    function validatePatternField(label, value, min, max, pattern, allowedText, checkSpacing) {
        var raw = String(value == null ? '' : value);
        var normalized = normalizeSpaces(raw);
        var useSpacing = checkSpacing !== false;

        if (normalized === '') {
            return label + ' wajib diisi.';
        }

        if (useSpacing && hasInvalidSpacing(raw)) {
            return buildSpacingError(label);
        }

        if (normalized.length < min) {
            return label + ' minimal ' + min + ' karakter.';
        }

        if (normalized.length > max) {
            return label + ' maksimal ' + max + ' karakter.';
        }

        if (pattern && !pattern.test(normalized)) {
            return label + ' hanya boleh berisi ' + allowedText + '.';
        }

        return '';
    }

    function validateLooseField(label, value, min, max) {
        var raw = String(value == null ? '' : value);
        var normalized = normalizeSpaces(raw);

        if (normalized === '') {
            return label + ' wajib diisi.';
        }

        if (hasInvalidSpacing(raw)) {
            return buildSpacingError(label);
        }

        if (normalized.length < min) {
            return label + ' minimal ' + min + ' karakter.';
        }

        if (normalized.length > max) {
            return label + ' maksimal ' + max + ' karakter.';
        }

        if (/[\r\n\t]/.test(normalized)) {
            return label + ' mengandung karakter yang tidak valid.';
        }

        return '';
    }

    function validateEmail(value, label) {
        var raw = String(value == null ? '' : value);
        var fieldLabel = label || 'Email';

        if (raw.trim() === '') {
            return fieldLabel + ' wajib diisi.';
        }

        if (hasInvalidSpacing(raw)) {
            return buildSpacingError(fieldLabel);
        }

        if (raw.length > 100) {
            return fieldLabel + ' maksimal 100 karakter.';
        }

        var pattern = /^[A-Za-z0-9](?:[A-Za-z0-9._%+\-]{0,62}[A-Za-z0-9])?@[A-Za-z0-9](?:[A-Za-z0-9\-]{0,61}[A-Za-z0-9])?(?:\.[A-Za-z0-9](?:[A-Za-z0-9\-]{0,61}[A-Za-z0-9])?)+$/;
        if (!pattern.test(raw)) {
            return fieldLabel + ' tidak valid.';
        }

        var parts = raw.split('@');
        if (parts.length !== 2 || /\.$/.test(parts[1])) {
            return fieldLabel + ' tidak valid.';
        }

        return '';
    }

    function validatePassword(value, confirmation) {
        var password = String(value == null ? '' : value);
        if (password === '') {
            return 'Password wajib diisi.';
        }
        if (password.length < 8) {
            return 'Password minimal 8 karakter.';
        }
        if (password.length > 24) {
            return 'Password maksimal 24 karakter.';
        }
        if (!/[A-Z]/.test(password)) {
            return 'Password harus mengandung minimal 1 huruf kapital (A-Z).';
        }
        if (!/[a-z]/.test(password)) {
            return 'Password harus mengandung minimal 1 huruf kecil (a-z).';
        }
        if (!/[0-9]/.test(password)) {
            return 'Password harus mengandung minimal 1 angka (0-9).';
        }
        if (!/[^A-Za-z0-9]/.test(password)) {
            return 'Password harus mengandung minimal 1 simbol.';
        }
        if (typeof confirmation !== 'undefined' && confirmation !== null && password !== String(confirmation)) {
            return 'Konfirmasi password tidak cocok.';
        }
        return '';
    }

    function validatePhone(value, label) {
        var raw = String(value == null ? '' : value);
        var fieldLabel = label || 'No WA';

        if (raw.trim() === '') {
            return fieldLabel + ' wajib diisi.';
        }

        if (hasInvalidSpacing(raw)) {
            return buildSpacingError(fieldLabel);
        }

        if (!/^\+?\d{7,20}$/.test(raw)) {
            return fieldLabel + ' hanya boleh berisi angka dan tanda tambah (+), dengan panjang 7 sampai 20 karakter.';
        }

        return '';
    }

    function validateNumberRange(value, label, min, max) {
        var raw = String(value == null ? '' : value).trim();
        var fieldLabel = label || 'Nilai';

        if (raw === '') {
            return fieldLabel + ' wajib diisi.';
        }

        if (!/^\d+$/.test(raw)) {
            return fieldLabel + ' hanya boleh berisi angka.';
        }

        var number = parseInt(raw, 10);
        if (number < min) {
            return fieldLabel + ' minimal ' + min + '.';
        }
        if (typeof max === 'number' && number > max) {
            return fieldLabel + ' maksimal ' + max + '.';
        }

        return '';
    }

    function validateHttpsUrl(value, label) {
        var raw = String(value == null ? '' : value).trim();
        var fieldLabel = label || 'URL';

        if (raw === '') {
            return fieldLabel + ' wajib diisi.';
        }

        try {
            var parsed = new URL(raw);
            if (parsed.protocol !== 'https:') {
                return fieldLabel + ' harus diawali dengan https://';
            }
        } catch (error) {
            return fieldLabel + ' harus diawali dengan https://';
        }

        return '';
    }

    function validateDateOnly(value, label) {
        var raw = String(value == null ? '' : value).trim();
        var fieldLabel = label || 'Tanggal';

        if (raw === '') {
            return fieldLabel + ' wajib diisi.';
        }

        if (!/^\d{4}-\d{2}-\d{2}$/.test(raw) || Number.isNaN(new Date(raw + 'T00:00:00').getTime())) {
            return fieldLabel + ' harus dipilih dari pemilih tanggal yang disediakan.';
        }

        return '';
    }

    function validateDateTime(value, label) {
        var raw = String(value == null ? '' : value).trim();
        var fieldLabel = label || 'Tanggal';

        if (raw === '') {
            return fieldLabel + ' wajib diisi.';
        }

        if (!/^\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}(:\d{2})?$/.test(raw) || Number.isNaN(new Date(raw.replace(' ', 'T')).getTime())) {
            return fieldLabel + ' harus dipilih dari pemilih tanggal yang disediakan.';
        }

        return '';
    }

    function parseDateOnly(value) {
        var raw = String(value == null ? '' : value).trim();
        if (!/^\d{4}-\d{2}-\d{2}$/.test(raw)) {
            return null;
        }

        var date = new Date(raw + 'T00:00:00');
        return Number.isNaN(date.getTime()) ? null : date;
    }

    function validatePklStartDate(value) {
        var dateError = validateDateOnly(value, 'Tanggal Mulai PKL');
        if (dateError) {
            return dateError;
        }

        var inputDate = parseDateOnly(value);
        if (!inputDate) {
            return 'Tanggal Mulai PKL harus dipilih dari pemilih tanggal yang disediakan.';
        }

        var today = new Date();
        today.setHours(0, 0, 0, 0);

        var minDate = new Date(today);
        minDate.setDate(minDate.getDate() + 14);

        var maxDate = new Date(today);
        maxDate.setMonth(maxDate.getMonth() + 3);

        if (inputDate < minDate) {
            return 'Tanggal Mulai PKL minimal 2 minggu dari hari ini.';
        }

        if (inputDate > maxDate) {
            return 'Tanggal Mulai PKL maksimal 3 bulan dari hari ini.';
        }

        return '';
    }

    function validatePklEndDate(startValue, endValue) {
        var endError = validateDateOnly(endValue, 'Tanggal Akhir PKL');
        if (endError) {
            return endError;
        }

        var startError = validateDateOnly(startValue, 'Tanggal Mulai PKL');
        if (startError) {
            return startError;
        }

        var startDate = parseDateOnly(startValue);
        var endDate = parseDateOnly(endValue);
        if (!startDate || !endDate) {
            return 'Tanggal Akhir PKL harus dipilih dari pemilih tanggal yang disediakan.';
        }

        var minEndDate = new Date(startDate);
        minEndDate.setMonth(minEndDate.getMonth() + 2);

        if (endDate < minEndDate) {
            return 'Tanggal Akhir PKL minimal 2 bulan dari Tanggal Mulai PKL.';
        }

        return '';
    }

    function getRuleConfig(ruleName) {
        return liveRules[ruleName] || null;
    }

    function applyInputRules(mappings, root) {
        var base = root || document;
        if (!base || !mappings || !mappings.length) {
            return;
        }

        mappings.forEach(function (mapping) {
            if (!mapping || !mapping.selector || !mapping.rule) {
                return;
            }

            base.querySelectorAll(mapping.selector).forEach(function (element) {
                element.dataset.svRule = mapping.rule;
                if (mapping.label) {
                    element.dataset.svLabel = mapping.label;
                }
                if (mapping.toast) {
                    element.dataset.svToast = mapping.toast;
                } else {
                    delete element.dataset.svToast;
                }
            });
        });
    }

    function sanitizeLiveField(element, notify) {
        if (!element || !element.dataset) {
            return;
        }

        var ruleName = element.dataset.svRule;
        var config = getRuleConfig(ruleName);
        if (!config || typeof config.sanitize !== 'function') {
            return;
        }

        var original = String(element.value == null ? '' : element.value);
        var sanitized = config.sanitize(original);
        if (sanitized === original) {
            return;
        }

        element.value = sanitized;
        if (notify !== false) {
            var label = element.dataset.svLabel || 'Field ini';
            showInputToast(element.dataset.svToast || (label + ' ' + config.message + '.'));
        }
    }

    function normalizeFieldOnBlur(element) {
        if (!element || !element.dataset) {
            return;
        }

        var ruleName = element.dataset.svRule;
        if (!ruleName) {
            return;
        }

        if (ruleName === 'email' || ruleName === 'phone' || ruleName === 'numeric' || ruleName === 'url') {
            element.value = String(element.value == null ? '' : element.value).trim();
            return;
        }

        element.value = normalizeSpaces(element.value);
    }

    document.addEventListener('input', function (event) {
        var target = event.target;
        if (!target || !(target.matches('input[data-sv-rule], textarea[data-sv-rule]'))) {
            return;
        }

        sanitizeLiveField(target, true);
    });

    document.addEventListener('blur', function (event) {
        var target = event.target;
        if (!target || !(target.matches('input[data-sv-rule], textarea[data-sv-rule]'))) {
            return;
        }

        sanitizeLiveField(target, false);
        normalizeFieldOnBlur(target);
    }, true);

    window.SimmagValidation = {
        normalizeSpaces: normalizeSpaces,
        hasInvalidSpacing: hasInvalidSpacing,
        buildSpacingError: buildSpacingError,
        buildMissingFieldsMessage: buildMissingFieldsMessage,
        validatePatternField: validatePatternField,
        validateLooseField: validateLooseField,
        validateEmail: validateEmail,
        validatePassword: validatePassword,
        validatePhone: validatePhone,
        validateNumberRange: validateNumberRange,
        validateHttpsUrl: validateHttpsUrl,
        validateDateOnly: validateDateOnly,
        validateDateTime: validateDateTime,
        validatePklStartDate: validatePklStartDate,
        validatePklEndDate: validatePklEndDate,
        applyInputRules: applyInputRules,
        sanitizeLiveField: sanitizeLiveField
    };
})(window);
