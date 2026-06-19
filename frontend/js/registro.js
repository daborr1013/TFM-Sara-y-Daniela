/* registro.js - Funciones para Login y Registro */

document.addEventListener('DOMContentLoaded', () => {
    // === VALIDACIÓN DE REGISTRO ===
    const registerForm = document.querySelector('form');
    const password = document.getElementById('contraseña');
    const confirmPassword = document.getElementById('confirmar_contraseña');

    if (registerForm && confirmPassword) {
        // Validación de coincidencia de contraseñas en tiempo real
        const validatePasswords = () => {
            if (password.value !== confirmPassword.value && confirmPassword.value !== '') {
                confirmPassword.setCustomValidity('Las contraseñas no coinciden');
            } else {
                confirmPassword.setCustomValidity('');
            }
        };

        password.addEventListener('input', validatePasswords);
        confirmPassword.addEventListener('input', validatePasswords);
    }

    // === INTERACCIONES PREMIUM ===
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        if (form.matches('[data-login-form], [data-register-form]')) {
            return;
        }

        form.addEventListener('submit', function() {
            const button = this.querySelector('button');
            if (button) {
                const originalText = button.textContent;
                button.disabled = true;
                button.innerHTML = '<span class="loading-dots">Procesando...</span>';
                
                // Si hay un error de validación del navegador, rehabilitar
                setTimeout(() => {
                    if (!this.checkValidity()) {
                        button.disabled = false;
                        button.textContent = originalText;
                    }
                }, 1000);
            }
        });
    });

    // === EFECTOS VISUALES EN CAMPOS ===
    const inputs = document.querySelectorAll('input');
    inputs.forEach(input => {
        input.addEventListener('focus', () => {
            input.parentElement.classList.add('focused');
        });
        input.addEventListener('blur', () => {
            input.parentElement.classList.remove('focused');
        });
    });
});
