# Script para reparar comillas especiales en api_admin.php

$filePath = "c:\Program Files\TG Gestion V5\phpdesktop-chrome-130.1-php-8.3\www\api\api_admin.php"

# Leer el contenido del archivo
$fileContent = Get-Content $filePath -Raw -Encoding UTF8

# Reemplazar comillas especiales con comillas normales
# Comillas dobles curvas: " " por "
$fileContent = $fileContent -replace [char]8220, '"'  # " - comilla abierta
$fileContent = $fileContent -replace [char]8221, '"'  # " - comilla cerrada

# Comillas simples curvas: ' ' por '
$fileContent = $fileContent -replace [char]8216, "'"  # ' - comilla abierta
$fileContent = $fileContent -replace [char]8217, "'"  # ' - comilla cerrada

# Escribir el archivo reparado
$fileContent | Set-Content $filePath -Encoding UTF8

Write-Host "✓ Archivo reparado: Se reemplazaron todas las comillas especiales"
