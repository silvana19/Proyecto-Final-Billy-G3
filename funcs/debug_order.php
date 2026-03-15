<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
header('Content-Type: application/json');

echo json_encode([
    'session_id'   => session_id(),
    'user_id'      => $_SESSION['user_id'] ?? 'NO SESSION',
    'cart_count'   => count($_SESSION['cart'] ?? []),
    'cart_items'   => $_SESSION['cart'] ?? [],
    'raw_input'    => file_get_contents('php://input'),
]);