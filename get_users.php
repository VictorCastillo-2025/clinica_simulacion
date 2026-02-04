<?php
require_once 'config.php';

$response = ['success' => false, 'data' => null, 'message' => ''];

try {
    $action = $_GET['action'] ?? 'list';

    switch ($action) {
        case 'login':
            // Autenticar usuario
            if (!isset($_POST['username']) || !isset($_POST['password'])) {
                throw new Exception('Faltan credenciales');
            }

            $username = $_POST['username'];
            $password = $_POST['password'];

            $stmt = $conn->prepare("SELECT id, username, password, role FROM usuarios WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && $user['password'] === $password) {
                // Iniciar sesión
                session_start();
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];

                $response = [
                    'success' => true,
                    'data' => [
                        'id' => $user['id'],
                        'username' => $user['username'],
                        'role' => $user['role']
                    ],
                    'message' => 'Login exitoso'
                ];
            } else {
                throw new Exception('Credenciales incorrectas');
            }
            break;

        case 'list':
            // Listar todos los usuarios
            $stmt = $conn->prepare("SELECT id, username, role, created_at FROM usuarios ORDER BY username");
            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $response = [
                'success' => true,
                'data' => $users,
                'message' => 'Usuarios cargados correctamente'
            ];
            break;

        case 'create':
            // Crear nuevo usuario
            if (!isset($_POST['username']) || !isset($_POST['password'])) {
                throw new Exception('Faltan datos del usuario');
            }

            $username = $_POST['username'];
            $password = $_POST['password'];
            $role = $_POST['role'] ?? 'user';

            // Verificar si el usuario ya existe
            $stmt = $conn->prepare("SELECT id FROM usuarios WHERE username = ?");
            $stmt->execute([$username]);
            
            if ($stmt->fetch()) {
                throw new Exception('El usuario ya existe');
            }

            // Crear usuario
            $stmt = $conn->prepare("INSERT INTO usuarios (username, password, role) VALUES (?, ?, ?)");
            $stmt->execute([$username, $password, $role]);

            $response = [
                'success' => true,
                'data' => ['id' => $conn->lastInsertId()],
                'message' => 'Usuario creado correctamente'
            ];
            break;

        case 'update_password':
            // Actualizar contraseña de usuario
            if (!isset($_POST['user_id']) || !isset($_POST['new_password'])) {
                throw new Exception('Faltan datos para actualizar');
            }

            $userId = $_POST['user_id'];
            $newPassword = $_POST['new_password'];

            $stmt = $conn->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
            $stmt->execute([$newPassword, $userId]);

            $response = [
                'success' => true,
                'message' => 'Contraseña actualizada correctamente'
            ];
            break;

        case 'delete':
            // Eliminar usuario (solo si no es admin y tiene documentos asignados)
            if (!isset($_POST['user_id'])) {
                throw new Exception('No se especificó el usuario a eliminar');
            }

            $userId = $_POST['user_id'];

            // Verificar si es admin
            $stmt = $conn->prepare("SELECT role FROM usuarios WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                throw new Exception('Usuario no encontrado');
            }

            if ($user['role'] === 'admin') {
                throw new Exception('No se puede eliminar un usuario administrador');
            }

            // Verificar si tiene documentos
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM documentos WHERE usuario_subio = ?");
            $stmt->execute([$userId]);
            $docCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

            if ($docCount > 0) {
                throw new Exception('No se puede eliminar un usuario con documentos asociados');
            }

            // Eliminar usuario
            $stmt = $conn->prepare("DELETE FROM usuarios WHERE id = ?");
            $stmt->execute([$userId]);

            $response = [
                'success' => true,
                'message' => 'Usuario eliminado correctamente'
            ];
            break;

        case 'logout':
            // Cerrar sesión
            session_start();
            session_destroy();
            
            $response = [
                'success' => true,
                'message' => 'Sesión cerrada correctamente'
            ];
            break;

        case 'check_session':
            // Verificar si hay sesión activa
            session_start();
            
            if (isset($_SESSION['user_id'])) {
                $response = [
                    'success' => true,
                    'data' => [
                        'id' => $_SESSION['user_id'],
                        'username' => $_SESSION['username'],
                        'role' => $_SESSION['role']
                    ],
                    'message' => 'Sesión activa'
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'No hay sesión activa'
                ];
            }
            break;

        default:
            throw new Exception('Acción no válida');
    }

} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => $e->getMessage()
    ];
}

echo json_encode($response);
?>