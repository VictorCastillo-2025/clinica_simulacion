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
            // Listar todas las carpetas disponibles
            $stmt = $conn->prepare("
                SELECT DISTINCT carpeta, COUNT(*) as count 
                FROM documentos 
                GROUP BY carpeta 
                ORDER BY carpeta
            ");
            $stmt->execute();
            $folders = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $response = [
                'success' => true,
                'data' => $folders,
                'message' => 'Carpetas cargadas correctamente'
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