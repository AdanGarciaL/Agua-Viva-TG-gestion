<?php
// index.php
session_start();
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
    <title>Login - Agua Viva POS</title>
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="login-body">
    <div class="login-container">
        <img src="assets/img/logo-agua-viva.png" alt="Logo Agua Viva" class="logo">
        
        <div class="login-modo-selector">
            <button id="btn-modo-admin" class="btn-modo active">Admin / Superadmin</button>
            <button id="btn-modo-vendedor" class="btn-modo">Vendedor (Tiendita)</button>
            <div class="slider"></div>
        </div>

        <form id="loginFormAdmin" class="login-form active">
            <h2>Iniciar Sesión</h2>
            <div class="input-group">
                <label for="username">Usuario:</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="input-group">
                <label for="password">Contraseña:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn">Entrar</button>
            <p id="login-error-admin" class="error-msg"></p>
        </form>

        <form id="loginFormVendedor" class="login-form">
            <h2>Acceso Vendedor</h2>
            <div class="input-group">
                <label for="vendedor-nombre">Tu Nombre:</label>
                <input type="text" id="vendedor-nombre" name="vendedor_nombre" placeholder="¿Quién atiende?" required>
            </div>
            
            <div class="input-group">
                <label for="vendedor-estigma">Estigma:</label>
                <select id="vendedor-estigma" name="vendedor_estigma" required>
                    <option value="" disabled selected>Selecciona tu estigma</option>
                    <option value="alcholico">Alcohólico</option>
                    <option value="codependiente">Codependiente</option>
                    <option value="neurotico">Neurótico</option>
                    </select>
            </div>
            
            <div class="input-group">
                <label for="vendedor-padrino">Nombre de Padrino:</label>
                <input type="text" id="vendedor-padrino" name="vendedor_padrino" placeholder="¿Quién te apadrina?" required>
            </div>
            <button type="submit" class="btn">Entrar</button>
            <p id="login-error-vendedor" class="error-msg"></p>
        </form>

    </div>
    
    <script src="assets/js/login.js"></script>
</body>
</html>