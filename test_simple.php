<?php
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['test' => 'ok', 'time' => date('Y-m-d H:i:s')]);
?>
