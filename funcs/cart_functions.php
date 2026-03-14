<?php
session_start();
header('Content-Type: application/json');

// Respuesta de prueba para cualquier petición
echo json_encode([
    'success' => true,
    'message' => 'Cart functions está funcionando',
    'session' => $_SESSION,
    'post' => $_POST,
    'get' => $_GET
]);
exit;
?>