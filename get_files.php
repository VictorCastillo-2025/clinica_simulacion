<?php
require_once 'config.php';

$response = ['success' => false, 'data' => null, 'message' => ''];

try {
    $action = $_GET['action'] ?? 'list';

    switch ($action) {
        case 'list':
            // Listar todos los documentos agrupados por carpeta
            $stmt = $conn->prepare("
                SELECT d.*, u.username as usuario_subio 
                FROM documentos d 
                LEFT JOIN usuarios u ON d.usuario_subio = u.id 
                ORDER BY d.carpeta, d.fecha_subida DESC
            ");
            $stmt->execute();
            $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Agrupar por carpeta
            $groupedDocuments = [];
            foreach ($documents as $doc) {
                $folder = $doc['carpeta'];
                if (!isset($groupedDocuments[$folder])) {
                    $groupedDocuments[$folder] = [];
                }
                $groupedDocuments[$folder][] = [
                    'id' => $doc['id'],
                    'name' => $doc['nombre_original'],
                    'type' => $doc['tipo_archivo'],
                    'size' => $doc['tamaño'],
                    'path' => $doc['ruta'],
                    'uploaded_by' => $doc['usuario_subio'],
                    'upload_date' => $doc['fecha_subida']
                ];
            }

            $response = [
                'success' => true,
                'data' => $groupedDocuments,
                'message' => 'Documentos cargados correctamente'
            ];
            break;

        case 'folder':
            // Listar documentos de una carpeta específica
            if (!isset($_GET['folder']) || empty($_GET['folder'])) {
                throw new Exception('No se especificó la carpeta');
            }

            $folder = $_GET['folder'];
            $stmt = $conn->prepare("
                SELECT d.*, u.username as usuario_subio 
                FROM documentos d 
                LEFT JOIN usuarios u ON d.usuario_subio = u.id 
                WHERE d.carpeta = ? 
                ORDER BY d.fecha_subida DESC
            ");
            $stmt->execute([$folder]);
            $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $folderDocuments = array_map(function($doc) {
                return [
                    'id' => $doc['id'],
                    'name' => $doc['nombre_original'],
                    'type' => $doc['tipo_archivo'],
                    'size' => $doc['tamaño'],
                    'path' => $doc['ruta'],
                    'uploaded_by' => $doc['usuario_subio'],
                    'upload_date' => $doc['fecha_subida']
                ];
            }, $documents);

            $response = [
                'success' => true,
                'data' => $folderDocuments,
                'message' => 'Documentos de la carpeta cargados correctamente'
            ];
            break;

        case 'delete':
            // Eliminar un documento
            if (!isset($_POST['doc_id'])) {
                throw new Exception('No se especificó el documento a eliminar');
            }

            $docId = $_POST['doc_id'];
            
            // Obtener información del documento
            $stmt = $conn->prepare("SELECT ruta FROM documentos WHERE id = ?");
            $stmt->execute([$docId]);
            $doc = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$doc) {
                throw new Exception('Documento no encontrado');
            }

            // Eliminar archivo físico
            if (file_exists($doc['ruta'])) {
                unlink($doc['ruta']);
            }

            // Eliminar de la base de datos
            $stmt = $conn->prepare("DELETE FROM documentos WHERE id = ?");
            $stmt->execute([$docId]);

            $response = [
                'success' => true,
                'message' => 'Documento eliminado correctamente'
            ];
            break;

        case 'folders':
            // Obtener lista de carpetas
            $folders = [];
            
            // Obtener carpetas de la tabla carpetas
            $stmt = $conn->prepare("
                SELECT c.nombre, COUNT(d.id) as count 
                FROM carpetas c 
                LEFT JOIN documentos d ON c.nombre = d.carpeta 
                GROUP BY c.id, c.nombre 
                ORDER BY c.nombre
            ");
            $stmt->execute();
            $dbFolders = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Debug
            error_log("Debug: dbFolders = " . json_encode($dbFolders));
            
            foreach ($dbFolders as $folder) {
                $folders[] = ['carpeta' => $folder['nombre'], 'count' => $folder['count']];
            }
            
            // También obtener carpetas que solo existen físicamente (para compatibilidad)
            if (is_dir($uploadDir)) {
                $physicalFolders = array_diff(scandir($uploadDir), ['.', '..']);
                foreach ($physicalFolders as $folder) {
                    if (is_dir($uploadDir . $folder)) {
                        $found = false;
                        foreach ($folders as $f) {
                            if ($f['carpeta'] === $folder) {
                                $found = true;
                                break;
                            }
                        }
                        if (!$found) {
                            $folders[] = ['carpeta' => $folder, 'count' => 0];
                        }
                    }
                }
            }
            
            // Ordenar alfabéticamente
            usort($folders, function($a, $b) {
                return strcasecmp($a['carpeta'], $b['carpeta']);
            });

            $response = [
                'success' => true,
                'data' => $folders,
                'message' => 'Carpetas cargadas correctamente'
            ];
            break;

        case 'create_folder':
            // Crear nueva carpeta
            if (!isset($_POST['folder_name']) || empty($_POST['folder_name'])) {
                throw new Exception('Nombre de carpeta requerido');
            }

            $folderName = $_POST['folder_name'];
            $userId = $_POST['user_id'] ?? 1;

            // Verificar si ya existe en tabla carpetas
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM carpetas WHERE nombre = ?");
            $stmt->execute([$folderName]);
            $exists = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

            if ($exists > 0) {
                throw new Exception('La carpeta ya existe');
            }

            // Crear carpeta física en el servidor
            $folderPath = $uploadDir . $folderName;
            if (!file_exists($folderPath)) {
                if (!mkdir($folderPath, 0777, true)) {
                    throw new Exception('Error al crear la carpeta física');
                }
            }

            // Guardar en tabla carpetas
            $stmt = $conn->prepare("INSERT INTO carpetas (nombre, creada_por) VALUES (?, ?)");
            $stmt->execute([$folderName, $userId]);

            $response = [
                'success' => true,
                'message' => 'Carpeta creada exitosamente'
            ];
            break;

        case 'delete_folder':
            // Eliminar carpeta
            if (!isset($_POST['folder_name']) || empty($_POST['folder_name'])) {
                throw new Exception('Nombre de carpeta requerido');
            }

            $folderName = $_POST['folder_name'];

            // Verificar si hay documentos en la carpeta (en BD)
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM documentos WHERE carpeta = ?");
            $stmt->execute([$folderName]);
            $docCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

            if ($docCount > 0) {
                throw new Exception('No se puede eliminar una carpeta con documentos');
            }

            // Eliminar carpeta física
            $folderPath = $uploadDir . $folderName;
            if (file_exists($folderPath)) {
                if (!rmdir($folderPath)) {
                    throw new Exception('Error al eliminar la carpeta física');
                }
            }

            // Eliminar registro de la tabla carpetas
            $stmt = $conn->prepare("DELETE FROM carpetas WHERE nombre = ?");
            $stmt->execute([$folderName]);

            $response = [
                'success' => true,
                'message' => 'Carpeta eliminada exitosamente'
            ];
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