<?php
session_start();
require_once "../config/db.php";

// ACTIVAR REPORTE DE ERRORES PARA DEPURACIÓN
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Verificar que la conexión existe
if (!isset($conn)) {
    die("Error: No hay conexión a la base de datos");
}

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Verificar que los campos existen
    if (!isset($_POST['email']) || !isset($_POST['password'])) {
        $_SESSION['error'] = "Por favor complete todos los campos";
        header("Location: ../login.php");
        exit();
    }
    
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $_SESSION['error'] = "Por favor complete todos los campos";
        header("Location: ../login.php");
        exit();
    }
    
    // IMPORTANTE: La columna se llama 'correo' no 'email'
    $sql = "SELECT * FROM usuarios WHERE correo = ?";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        $_SESSION['error'] = "Error en la consulta: " . $conn->error;
        header("Location: ../login.php");
        exit();
    }
    
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['password'])) {
            // Login exitoso
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['nombre'] = $user['nombre'];
            $_SESSION['email'] = $user['correo']; // Usamos 'correo' aquí
            $_SESSION['rol'] = $user['rol'];
            $_SESSION['just_logged_in'] = true;
            
            header("Location: ../index.php");
            exit();
        } else {
            $_SESSION['error'] = "Contraseña incorrecta";
            header("Location: ../login.php");
            exit();
        }
    } else {
        $_SESSION['error'] = "Usuario no encontrado";
        header("Location: ../login.php");
        exit();
    }
    
    $stmt->close();
} else {
    header("Location: ../login.php");
    exit();
}
?>