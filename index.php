<?php
// index.php - v4.0 - Tienda Regional
// Cargar configuración centralizada
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'config.php';

// Configurar cookies de sesión seguras cuando sea posible
if (PHP_VERSION_ID >= 70300) {
    session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'secure' => false, 'httponly' => true, 'samesite' => 'Lax']);
} else {
    session_set_cookie_params(0, '/','', false, true);
}
session_start();
if (isset($_SESSION["usuario"])) { session_regenerate_id(true); header("Location: dashboard.php"); exit(); }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Tienda Regional (TG Gestión v5.0)</title>
    <link href="assets/css/style.css" rel="stylesheet">
    <!-- SweetAlert2: Local + CDN fallback -->
    <link href="assets/vendor/sweetalert2/sweetalert2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.0/dist/sweetalert2.min.css" rel="stylesheet" onerror="console.warn('CDN fallback for SweetAlert2 CSS')"
</head>
<body class="login-body">
    <div class="login-container">
        <div style="margin-bottom: 1rem;">
            <img src="assets/img/logo-agua-viva.png" alt="Logo Agua Viva" class="logo" onerror="this.style.display='none'">
            <div style="font-weight:bold; letter-spacing:2px; opacity:0.5; font-size:0.8rem;">SISTEMA INTEGRAL</div>
        </div>
        
        <div class="login-modo-selector">
            <button id="btn-modo-admin" class="btn-modo active">Admin / Superadmin</button>
            <button id="btn-modo-vendedor" class="btn-modo">Vendedor (Tienda)</button>
            <div class="slider"></div>
        </div>

        <div class="forms-wrapper">
            <form id="loginFormAdmin" class="login-form active" novalidate>
                <h2 style="color:var(--color-primario);">Iniciar Sesión</h2>
                <div class="input-group">
                    <label for="username">Usuario:</label>
                    <input type="text" id="username" name="username" required autocomplete="username" placeholder="Admin/Superadmin">
                </div>
                <div class="input-group">
                    <label for="password">Contraseña:</label>
                    <input type="password" id="password" name="password" required autocomplete="current-password" placeholder="••••••">
                </div>
                <button type="submit" class="btn" id="btn-login-admin">
                    Entrar al Sistema
                </button>
                <p id="login-error-admin" class="error-msg" style="color:var(--color-danger); margin-top:10px;"></p>
            </form>

            <form id="loginFormVendedor" class="login-form" novalidate>
                <h2 style="color:var(--color-secundario);">Acceso Vendedor</h2>
                <div class="input-group">
                    <label for="vendedor-nombre">Tu Nombre:</label>
                    <input type="text" id="vendedor-nombre" name="vendedor_nombre" required placeholder="¿Quién atiende hoy?" minlength="2">
                </div>
                
                <div class="input-group">
                    <label for="vendedor-estigma">Estigma:</label>
                    <select id="vendedor-estigma" name="vendedor_estigma" required>
                        <option value="" disabled selected>-- Selecciona --</option>
                        <option value="alcholico">Alcohólico</option>
                        <option value="drogadicto">Drogadicto</option>
                        <option value="neurotico">Neurótico</option>
                        <option value="codependiente">Codependiente</option>
                        <option value="dependiente">Dependiente</option>
                        <option value="bulimia_anorexia">Bulimia / Anorexia</option>
                        <option value="alcholico_codependiente">Alcohólico Codependiente</option>
                        <option value="adicto">Adicto</option>
                    </select>
                </div>
                
                <div class="input-group">
                    <label for="vendedor-padrino">Nombre de Padrino:</label>
                    <input type="text" id="vendedor-padrino" name="vendedor_padrino" required placeholder="¿Quién te apadrina?" minlength="2">
                </div>
                
                <button type="submit" class="btn" id="btn-login-vendedor" style="background: linear-gradient(135deg, var(--color-secundario), var(--color-acento)); margin-top: 20px;">
                    Iniciar Sesión
                </button>
                <p id="login-error-vendedor" class="error-msg" style="color:var(--color-danger); margin-top:10px;"></p>
            </form>
        </div>
    </div>
    
    <!-- SweetAlert2: Local + CDN fallback -->
    <script src="assets/vendor/sweetalert2/sweetalert2.min.js"></script>
    <script>
    // Cargar desde CDN si no está disponible localmente
    if (typeof Swal === 'undefined') {
        var script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/sweetalert2@11.10.0/dist/sweetalert2.all.min.js';
        script.onload = function() { console.log('SweetAlert2 cargado desde CDN'); };
        script.onerror = function() {
            console.warn('SweetAlert2 CDN falló, usando fallback');
            window.Swal = {
                fire: function(opts) {
                    if (opts && opts.title) alert((opts.title || '') + "\n" + (opts.text || ''));
                    return Promise.resolve({ isConfirmed: true, value: true });
                }
            };
        };
        document.head.appendChild(script);
    }
    </script>
    <script src="assets/js/login.js"></script>
</body>
</html>