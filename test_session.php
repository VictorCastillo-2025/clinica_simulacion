<?php
// Test de sesión simple
header('Content-Type: application/json');

session_start();

if (isset($_GET['test'])) {
    if ($_GET['test'] === 'set') {
        $_SESSION['test'] = 'Hola mundo';
        echo json_encode(['success' => true, 'message' => 'Sesión creada']);
    } elseif ($_GET['test'] === 'get') {
        echo json_encode([
            'success' => true, 
            'message' => 'Sesión leída',
            'session_data' => $_SESSION['test'] ?? 'No hay datos'
        ]);
    }
} else {
    echo json_encode(['success' => true, 'message' => 'Archivo de prueba funciona']);
}
?>