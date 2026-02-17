// login.js - Versión BLINDADA v3.0 - GARANTIZA FUNCIONAMIENTO
document.addEventListener('DOMContentLoaded', () => {

    // Elementos del DOM
    const btnModoAdmin = document.getElementById('btn-modo-admin');
    const btnModoVendedor = document.getElementById('btn-modo-vendedor');
    const formAdmin = document.getElementById('loginFormAdmin');
    const formVendedor = document.getElementById('loginFormVendedor');
    const errorAdmin = document.getElementById('login-error-admin');
    const errorVendedor = document.getElementById('login-error-vendedor');
    const slider = document.querySelector('.login-modo-selector .slider');

    // ════════════════════════════════════════════════════════════
    // SISTEMA DE VERIFICACIÓN BLINDADO
    // ════════════════════════════════════════════════════════════
    
    let appReady = false;
    let checkCount = 0;
    const MAX_CHECKS = 15; // 15 * 500ms = 7.5 segundos máximo
    
    // Mostrar mensaje de "Iniciando..."
    const msg = document.createElement('div');
    msg.id = 'app-startup-msg';
    msg.style.cssText = 'position: fixed; top: 10px; left: 50%; transform: translateX(-50%); background: #fff3cd; border: 1px solid #ffc107; color: #856404; padding: 10px 20px; border-radius: 4px; z-index: 10000; font-size: 12px;';
    msg.textContent = 'Inicializando aplicación...';
    document.body.appendChild(msg);
    
    function checkAppReady() {
        fetch('ping.php?t=' + Date.now())
            .then(r => r.json())
            .then(data => {
                if (data.ok) {
                    appReady = true;
                    msg.style.display = 'none';
                    console.log('✓ App ready:', data);
                    // Mostrar notificación de éxito
                    showReadyNotification();
                } else {
                    checkCount++;
                    if (checkCount < MAX_CHECKS) {
                        // Reintentar
                        setTimeout(checkAppReady, 500);
                    } else {
                        // Timeout - mostrar botón de reparación
                        msg.textContent = 'Timeout. ';
                        const fixBtn = document.createElement('a');
                        fixBtn.href = 'verify-and-fix.php';
                        fixBtn.textContent = 'Reparar BD aquí';
                        fixBtn.style.cssText = 'color: #d32f2f; text-decoration: underline; cursor: pointer; font-weight: bold;';
                        msg.appendChild(fixBtn);
                    }
                }
            })
            .catch(err => {
                checkCount++;
                console.log('Ping attempt', checkCount, 'failed:', err);
                if (checkCount < MAX_CHECKS) {
                    setTimeout(checkAppReady, 500);
                }
            });
    }
    
    // Comenzar verificación
    checkAppReady();
    
    function showReadyNotification() {
        setTimeout(() => {
            if (msg && msg.parentNode) {
                msg.style.cssText += '; background: #d4edda; border-color: #28a745; color: #155724;';
                msg.textContent = '✓ Aplicación lista';
                setTimeout(() => {
                    if (msg && msg.parentNode) msg.parentNode.removeChild(msg);
                }, 2000);
            }
        }, 300);
    }

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

        // Verificar que app está lista
        if (!appReady) {
            mostrarError(errorAdmin, 'La aplicación aún se está iniciando. Espera unos segundos...');
            return;
        }

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

        // Verificar que app está lista
        if (!appReady) {
            mostrarError(errorVendedor, 'La aplicación aún se está iniciando. Espera unos segundos...');
            return;
        }

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