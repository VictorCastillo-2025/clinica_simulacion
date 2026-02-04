<?php
// Configuración segura de sesiones
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Strict');

// Configuración de la base de datos
$host = 'localhost';
$dbname = 'u821991958_clinica_simula';
$username = 'u821991958_adminsimula';
$password = '@Diopm.simula.2026';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Error de conexión: ' . $e->getMessage()]);
    exit;
}

// Configuración de subida de archivos
$uploadDir = 'uploads/';
$maxFileSize = 50 * 1024 * 1024; // 50MB máximo
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf', 'text/plain', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];

// Headers para respuestas JSON
header('Content-Type: application/json');
?>