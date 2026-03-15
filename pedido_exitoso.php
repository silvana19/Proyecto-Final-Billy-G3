<?php
session_start();
require_once "config/db.php";

$pedido_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$pedido_id) {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedido Confirmado - Farmacia del Amor</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .success-container {
            max-width: 600px;
            margin: 100px auto;
            padding: 40px;
            background: white;
            border-radius: 20px;
            box-shadow: var(--shadow-elevation-medium);
            text-align: center;
        }
        .success-icon {
            font-size: 5rem;
            margin-bottom: 20px;
        }
        .success-title {
            color: var(--color-accent);
            font-size: 2rem;
            margin-bottom: 20px;
        }
        .success-message {
            color: var(--color-gray);
            margin-bottom: 30px;
            line-height: 1.8;
        }
        .order-number {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 10px;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--color-primary);
            margin-bottom: 30px;
        }
        .btn-home {
            display: inline-block;
            padding: 15px 40px;
            background: var(--color-accent);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-home:hover {
            background: var(--color-accent-hover);
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="success-container">
        <div class="success-icon">✅</div>
        <h1 class="success-title">¡Pedido Confirmado!</h1>
        <div class="order-number">
            Pedido #<?php echo str_pad($pedido_id, 6, '0', STR_PAD_LEFT); ?>
        </div>
        <p class="success-message">
            Hemos recibido tu pedido correctamente.<br>
            Te contactaremos pronto para confirmar la entrega.<br>
            ¡Gracias por confiar en Farmacia del Amor!
        </p>
        <a href="index.php" class="btn-home">Volver al Inicio</a>
    </div>
</body>
</html>