<?php
// Configuración de la base de datos
$host = 'localhost';
$dbname = 'clinica_simulacion';
$username = 'root';
$password = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// Configuración de subida de archivos
$uploadDir = 'uploads/';
$maxFileSize = 50 * 1024 * 1024; // 50MB máximo
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf', 'text/plain', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];

// Headers para respuestas JSON
header('Content-Type: application/json');
?>