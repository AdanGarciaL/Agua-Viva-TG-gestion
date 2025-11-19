<?php
// index.php
// VERSIÓN FINAL BLINDADA v2.7
session_start();

// Si ya hay sesión, mandar directo al dashboard
if (isset($_SESSION["usuario"])) {
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso - TG Gestión</title>
    <link href="assets/css/style.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.0/dist/sweetalert2.min.css" rel="stylesheet">
</head>
<body class="login-body">
    <div class="login-container">
        <img src="assets/img/logo-agua-viva.png" alt="Logo Agua Viva" class="logo" onerror="this.style.display='none'">
        
        <div class="login-modo-selector">
            <button id="btn-modo-admin" class="btn-modo active">Admin / Superadmin</button>
            <button id="btn-modo-vendedor" class="btn-modo">Vendedor (Tienda)</button>
            <div class="slider"></div>
        </div>

        <form id="loginFormAdmin" class="login-form active" novalidate>
            <h2>Iniciar Sesión</h2>
            <div class="input-group">
                <label for="username">Usuario:</label>
                <input type="text" id="username" name="username" required autocomplete="username" placeholder="Ej: AdanGL">
            </div>
            <div class="input-group">
                <label for="password">Contraseña:</label>
                <input type="password" id="password" name="password" required autocomplete="current-password" placeholder="••••••">
            </div>
            <button type="submit" class="btn" id="btn-login-admin">Entrar</button>
            <p id="login-error-admin" class="error-msg"></p>
        </form>

        <form id="loginFormVendedor" class="login-form" novalidate>
            <h2>Acceso Vendedor</h2>
            <div class="input-group">
                <label for="vendedor-nombre">Tu Nombre:</label>
                <input type="text" id="vendedor-nombre" name="vendedor_nombre" required placeholder="¿Quién atiende?" minlength="2">
            </div>
            
            <div class="input-group">
                <label for="vendedor-estigma">Estigma:</label>
                <select id="vendedor-estigma" name="vendedor_estigma" required>
                    <option value="" disabled selected>Selecciona una opción</option>
                    <option value="alcholico">Alcohólico</option>
                    <option value="codependiente">Codependiente</option>
                    <option value="neurotico">Neurótico</option>
                    <option value="adicto">Adicto</option>
                    <option value="familiar">Familiar</option>
                </select>
            </div>
            
            <div class="input-group">
                <label for="vendedor-padrino">Nombre de Padrino:</label>
                <input type="text" id="vendedor-padrino" name="vendedor_padrino" required placeholder="¿Quién te apadrina?" minlength="2">
            </div>
            <button type="submit" class="btn" id="btn-login-vendedor">Entrar</button>
            <p id="login-error-vendedor" class="error-msg"></p>
        </form>

    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.0/dist/sweetalert2.all.min.js"></script>
    <script src="assets/js/login.js"></script>
</body>
</html>