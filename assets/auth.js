/**
 * Karyawan-WA Authentication Client Interactions
 * Pure Vanilla JS: Accessible Show/Hide Password Toggle
 */

(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        const toggleButtons = document.querySelectorAll('.password-toggle-btn');

        toggleButtons.forEach(function (button) {
            button.addEventListener('click', function (e) {
                e.preventDefault();

                const targetId = button.getAttribute('data-target');
                const targetInput = targetId ? document.getElementById(targetId) : button.parentElement.querySelector('input');

                if (!targetInput) {
                    return;
                }

                const isPassword = (targetInput.getAttribute('type') === 'password');
                const newType = isPassword ? 'text' : 'password';
                targetInput.setAttribute('type', newType);

                // Update aria attributes and SVG icon
                button.setAttribute('aria-label', isPassword ? 'Sembunyikan password' : 'Tampilkan password');
                button.setAttribute('title', isPassword ? 'Sembunyikan password' : 'Tampilkan password');

                // Toggle eye / eye-off SVG icons
                const eyeIcon = button.querySelector('.icon-eye');
                const eyeOffIcon = button.querySelector('.icon-eye-off');

                if (eyeIcon && eyeOffIcon) {
                    if (isPassword) {
                        eyeIcon.style.display = 'none';
                        eyeOffIcon.style.display = 'block';
                    } else {
                        eyeIcon.style.display = 'block';
                        eyeOffIcon.style.display = 'none';
                    }
                }
            });
        });
    });
})();

