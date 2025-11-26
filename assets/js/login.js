// login.js - Versión Final Blindada v2.7
document.addEventListener('DOMContentLoaded', () => {

    // Elementos del DOM
    const btnModoAdmin = document.getElementById('btn-modo-admin');
    const btnModoVendedor = document.getElementById('btn-modo-vendedor');
    const formAdmin = document.getElementById('loginFormAdmin');
    const formVendedor = document.getElementById('loginFormVendedor');
    const errorAdmin = document.getElementById('login-error-admin');
    const errorVendedor = document.getElementById('login-error-vendedor');
    const slider = document.querySelector('.login-modo-selector .slider');

    // --- LÓGICA VISUAL (SLIDER) ---
    btnModoAdmin.addEventListener('click', () => {
        formAdmin.classList.add('active');
        formVendedor.classList.remove('active');
        btnModoAdmin.classList.add('active');
        btnModoVendedor.classList.remove('active');
        limpiarErrores();
        if (slider) slider.style.transform = 'translateX(0)';
    });

    btnModoVendedor.addEventListener('click', () => {
        formVendedor.classList.add('active');
        formAdmin.classList.remove('active');
        btnModoVendedor.classList.add('active');
        btnModoAdmin.classList.remove('active');
        limpiarErrores();
        if (slider) slider.style.transform = 'translateX(100%)';
    });

    function limpiarErrores() {
        errorAdmin.textContent = '';
        errorVendedor.textContent = '';
        errorAdmin.classList.remove('shake');
        errorVendedor.classList.remove('shake');
    }

    function mostrarError(elemento, mensaje) {
        elemento.textContent = mensaje;
        elemento.classList.add('shake');
        setTimeout(() => elemento.classList.remove('shake'), 500);
    }

    // Función para bloquear botón mientras carga (Anti-Spam de clics)
    function setCargando(btn, cargando) {
        if (cargando) {
            btn.disabled = true;
            btn.dataset.textoOriginal = btn.textContent;
            btn.textContent = "Verificando...";
            btn.style.opacity = "0.7";
            btn.style.cursor = "wait";
        } else {
            btn.disabled = false;
            btn.textContent = btn.dataset.textoOriginal || "Entrar";
            btn.style.opacity = "1";
            btn.style.cursor = "pointer";
        }
    }

    // --- LOGIN ADMIN ---
    formAdmin.addEventListener('submit', function(e) {
        e.preventDefault();
        const btn = document.getElementById('btn-login-admin');
        const user = document.getElementById('username').value.trim();
        const pass = document.getElementById('password').value.trim();

        // Validación Local (Ahorra tiempo al servidor)
        if (!user || !pass) {
            mostrarError(errorAdmin, 'Escribe usuario y contraseña.');
            return;
        }

        setCargando(btn, true);

        const formData = new FormData(this);
        formData.append('modo', 'admin');

        fetch('api/api_login.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) throw new Error('Error de conexión');
            return response.json(); // Intentar leer JSON
        })
        .then(data => {
            if (data.success) {
                // Guardar token CSRF en sessionStorage si viene
                if (data.csrf_token) sessionStorage.setItem('csrf_token', data.csrf_token);
                window.location.href = 'dashboard.php';
            } else {
                mostrarError(errorAdmin, data.message || 'Datos incorrectos.');
                setCargando(btn, false);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarError(errorAdmin, 'No se pudo conectar al sistema.');
            setCargando(btn, false);
        });
    });

    // --- LOGIN VENDEDOR ---
    formVendedor.addEventListener('submit', function(e) {
        e.preventDefault(); 
        const btn = document.getElementById('btn-login-vendedor');
        const nombre = document.getElementById('vendedor-nombre').value.trim();
        const estigma = document.getElementById('vendedor-estigma').value;
        const padrino = document.getElementById('vendedor-padrino').value.trim();

        // Validaciones absurdas (Prevenir nombres de 1 letra o vacíos)
        if (nombre.length < 3) {
            mostrarError(errorVendedor, 'Tu nombre es muy corto.');
            return;
        }
        if (padrino.length < 3) {
            mostrarError(errorVendedor, 'El nombre del padrino es muy corto.');
            return;
        }
        if (!estigma) {
            mostrarError(errorVendedor, 'Selecciona un estigma.');
            return;
        }

        setCargando(btn, true);
        const formData = new FormData(this);
        formData.append('modo', 'vendedor');

        fetch('api/api_login.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (data.csrf_token) sessionStorage.setItem('csrf_token', data.csrf_token);
                window.location.href = 'dashboard.php';
            } else {
                mostrarError(errorVendedor, data.message);
                setCargando(btn, false);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarError(errorVendedor, 'Error de red o base de datos.');
            setCargando(btn, false);
        });
    });
});