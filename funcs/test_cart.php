<?php
session_start();
header('Content-Type: application/json');

// Limpiar carrito para prueba
$_SESSION['carrito'] = [];

$response = [
    'success' => true,
    'message' => 'Test funcionando',
    'session' => $_SESSION,
    'post' => $_POST,
    'get' => $_GET
];

echo json_encode($response);
?>