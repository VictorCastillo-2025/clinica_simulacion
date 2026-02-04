<?php
// Test de conexión a base de datos
header('Content-Type: application/json');

$host = 'localhost';
$dbname = 'u821991958_clinica_simula';
$username = 'u821991958_adminsimula';
$password = '@Diopm.simula.2026';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Test simple query
    $stmt = $conn->query("SELECT COUNT(*) as count FROM usuarios");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'message' => 'Conexión exitosa',
        'usuarios_count' => $result['count']
    ]);
    
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error de conexión: ' . $e->getMessage()
    ]);
}
?>