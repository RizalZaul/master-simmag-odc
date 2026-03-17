/**
 * SIMMAG ODC — Dashboard Core JS
 * public/assets/js/core/dashboard.js
 * Dependency: jQuery
 */

$(document).ready(function () {

    // ── Logo update berdasarkan state collapsed ──────────────────

    function updateLogo() {
        var $logo = $('.logo-image');
        var $sidebar = $('.dashboard-sidebar');
        if (!$logo.length) return;

        var isCollapsed = $sidebar.hasClass('collapsed') && !$sidebar.hasClass('hover-open');

        if (isCollapsed) {
            $logo.attr('src', $logo.data('logo-small'));
            $logo.removeClass('logo-large').addClass('logo-small');
        } else {
            $logo.attr('src', $logo.data('logo-large'));
            $logo.removeClass('logo-small').addClass('logo-large');
        }
    }

    // ── Restore collapsed state dari localStorage ────────────────

    var isDesktop = function () { return $(window).width() >= 992; };

    try {
        if (isDesktop() && localStorage.getItem('sidebarCollapsed') === '1') {
            $('.dashboard-sidebar').addClass('collapsed');
            $('#dashboardMain').addClass('sidebar-collapsed');
        }
    } catch (e) { }

    updateLogo();

    // ── Toggle hamburger (desktop: collapse/expand, mobile: open) ─

    $('#menuToggle').on('click', function () {
        if (isDesktop()) {
            var $sidebar = $('.dashboard-sidebar');
            $sidebar.toggleClass('collapsed');
            $('#dashboardMain').toggleClass('sidebar-collapsed', $sidebar.hasClass('collapsed'));
            updateLogo();
            // FIX 3: tutup profile dropdown saat sidebar di-collapse
            if ($sidebar.hasClass('collapsed')) {
                $('#profileDropdown').removeClass('show');
                $('#profileToggle').removeClass('active');
            }
            try {
                localStorage.setItem('sidebarCollapsed', $sidebar.hasClass('collapsed') ? '1' : '0');
            } catch (e) { }
        } else {
            // Mobile: open sidebar overlay
            $('.dashboard-sidebar').addClass('mobile-open');
            $('#sidebarOverlay').addClass('visible');
            $('body').css('overflow', 'hidden');
        }
    });

    // ── Hover expand/collapse (desktop only) ────────────────────

    $('.dashboard-sidebar').on('mouseenter', function () {
        if (isDesktop() && $(this).hasClass('collapsed')) {
            $(this).addClass('hover-open');
            updateLogo();
        }
    });

    $('.dashboard-sidebar').on('mouseleave', function () {
        if (isDesktop() && $(this).hasClass('collapsed')) {
            $(this).removeClass('hover-open');
            // Tutup submenu agar tidak tertinggal saat sidebar collapse
            if (!$(this).hasClass('hover-open')) {
                // biarkan open class submenu, hanya sembunyikan via CSS
            }
            updateLogo();
        }
    });

    // ── Mobile overlay close ─────────────────────────────────────

    $('#sidebarOverlay').on('click', function () {
        $('.dashboard-sidebar').removeClass('mobile-open');
        $(this).removeClass('visible');
        $('body').css('overflow', '');
    });

    // Resize: tutup mobile sidebar saat ke desktop
    $(window).on('resize', function () {
        if (isDesktop()) {
            $('.dashboard-sidebar').removeClass('mobile-open');
            $('#sidebarOverlay').removeClass('visible');
            $('body').css('overflow', '');
        }
    });

    // ── Submenu toggle ───────────────────────────────────────────

    $('.menu-item.has-submenu > a').on('click', function (e) {
        e.preventDefault();
        e.stopPropagation();

        // Jangan buka submenu saat collapsed (bukan hover-open)
        var $sidebar = $('.dashboard-sidebar');
        if (isDesktop() && $sidebar.hasClass('collapsed') && !$sidebar.hasClass('hover-open')) return;

        var $parent = $(this).closest('.menu-item.has-submenu');
        $parent.toggleClass('open');
        // Tutup submenu lain
        $('.menu-item.has-submenu').not($parent).removeClass('open');
    });

    // ── Profile dropdown ─────────────────────────────────────────

    $('#profileToggle').on('click', function (e) {
        e.stopPropagation();
        var $dropdown = $('#profileDropdown');
        var isVisible = $dropdown.hasClass('show');

        if (isVisible) {
            $dropdown.removeClass('show');
            $(this).removeClass('active');
        } else {
            $dropdown.addClass('show');
            $(this).addClass('active');
        }
    });

    // Klik luar → tutup profile dropdown
    $(document).on('click', function (e) {
        if (!$(e.target).closest('.sidebar-profile').length) {
            $('#profileDropdown').removeClass('show');
            $('#profileToggle').removeClass('active');
        }
    });

    $('#profileDropdown').on('click', function (e) {
        e.stopPropagation();
    });

    // ── Flash message auto-dismiss ────────────────────────────────

    $('.flash-message[data-timeout]').each(function () {
        var $msg = $(this);
        var timeout = parseInt($msg.data('timeout'), 10) || 4000;
        setTimeout(function () {
            $msg.css({ transition: 'opacity 0.4s ease', opacity: 0 });
            setTimeout(function () { $msg.remove(); }, 400);
        }, timeout);
    });

    $(document).on('click', '.flash-close', function () {
        var $msg = $(this).closest('.flash-message');
        $msg.css({ transition: 'opacity 0.3s ease', opacity: 0 });
        setTimeout(function () { $msg.remove(); }, 300);
    });

});