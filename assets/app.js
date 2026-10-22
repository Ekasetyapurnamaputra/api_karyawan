/**
 * Karyawan-WA Layout & Navigation Script
 * Vanilla JS for responsive drawer, backdrop, accessibility, and interactions
 */

(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        const menuBtn = document.getElementById('appMobileMenuBtn');
        const drawer = document.getElementById('appDrawer');
        const backdrop = document.getElementById('appDrawerBackdrop');
        const closeBtn = document.getElementById('appDrawerCloseBtn');

        if (!menuBtn || !drawer || !backdrop) {
            return;
        }

        function openDrawer() {
            drawer.classList.add('is-open');
            backdrop.classList.add('is-open');
            menuBtn.setAttribute('aria-expanded', 'true');
            document.body.style.overflow = 'hidden';
        }

        function closeDrawer() {
            drawer.classList.remove('is-open');
            backdrop.classList.remove('is-open');
            menuBtn.setAttribute('aria-expanded', 'false');
            document.body.style.overflow = '';
        }

        menuBtn.addEventListener('click', function (e) {
            e.preventDefault();
            if (drawer.classList.contains('is-open')) {
                closeDrawer();
            } else {
                openDrawer();
            }
        });

        if (closeBtn) {
            closeBtn.addEventListener('click', function (e) {
                e.preventDefault();
                closeDrawer();
            });
        }

        backdrop.addEventListener('click', function () {
            closeDrawer();
        });

        // Close on Escape key press
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && drawer.classList.contains('is-open')) {
                closeDrawer();
            }
        });

        // Auto close drawer if window expands to desktop size (>= 1024px)
        window.addEventListener('resize', function () {
            if (window.innerWidth >= 1024 && drawer.classList.contains('is-open')) {
                closeDrawer();
            }
        });
    });
})();

