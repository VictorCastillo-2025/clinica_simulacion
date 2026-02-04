<?php
require_once 'config.php';

// Respuesta por defecto
$response = ['success' => false, 'message' => ''];

try {
    // Verificar si hay archivos para subir
    if (!isset($_FILES['files']) || empty($_FILES['files']['name'][0])) {
        throw new Exception('No se seleccionaron archivos');
    }

    // Verificar carpeta
    if (!isset($_POST['folder']) || empty($_POST['folder'])) {
        throw new Exception('No se especificó carpeta');
    }

    $folder = $_POST['folder'];
    $userId = $_POST['user_id'] ?? 1; // Usuario por defecto si no se especifica

    // Crear directorio si no existe
    $uploadPath = $uploadDir . $folder;
    if (!file_exists($uploadPath)) {
        if (!mkdir($uploadPath, 0777, true)) {
            throw new Exception('Error al crear la carpeta');
        }
    }

    $files = $_FILES['files'];
    $uploadedFiles = [];
    $errors = [];

    // Procesar cada archivo
    for ($i = 0; $i < count($files['name']); $i++) {
        if ($files['error'][$i] === UPLOAD_ERR_OK) {
            $fileName = $files['name'][$i];
            $fileTmpPath = $files['tmp_name'][$i];
            $fileSize = $files['size'][$i];
            $fileType = $files['type'][$i];

            // Validar archivo
            if ($fileSize > $maxFileSize) {
                $errors[] = "El archivo $fileName excede el tamaño máximo";
                continue;
            }

            if (!in_array($fileType, $allowedTypes)) {
                $errors[] = "El archivo $fileName no es un tipo permitido";
                continue;
            }

            // Generar nombre único para evitar sobrescribir
            $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
            $uniqueName = uniqid() . '_' . time() . '.' . $fileExtension;
            $destination = $uploadPath . '/' . $uniqueName;

            // Mover archivo
            if (move_uploaded_file($fileTmpPath, $destination)) {
                // Guardar en base de datos
                $stmt = $conn->prepare("INSERT INTO documentos (nombre, nombre_original, carpeta, tipo_archivo, tamaño, ruta, usuario_subio) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$uniqueName, $fileName, $folder, $fileType, $fileSize, $destination, $userId]);

                $uploadedFiles[] = [
                    'id' => $conn->lastInsertId(),
                    'nombre' => $fileName,
                    'ruta' => $destination,
                    'tamaño' => $fileSize
                ];
            } else {
                $errors[] = "Error al subir el archivo $fileName";
            }
        } else {
            $errors[] = "Error en el archivo " . $files['name'][$i];
        }
    }

    // Preparar respuesta
    $response = [
        'success' => true,
        'message' => count($uploadedFiles) . ' archivos subidos correctamente',
        'files' => $uploadedFiles
    ];

    if (!empty($errors)) {
        $response['message'] .= '. Errores: ' . implode(', ', $errors);
    }

} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => $e->getMessage()
    ];
}

echo json_encode($response);
?>