<?php
// api_logout.php (Ahora en /api/)
session_start();
session_unset();
session_destroy();
// Redirige a la página de login que está un nivel ARRIBA
header("Location: ../index.php"); 
exit();
?>