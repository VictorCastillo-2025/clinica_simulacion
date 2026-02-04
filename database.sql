-- Base de datos para Clínica de Simulación
-- Crear estructura completa desde cero

USE u821991958_clinica_simula;

-- Tabla de usuarios
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insertar usuario admin por defecto
INSERT INTO usuarios (username, password, role) VALUES 
('admin', 'admin123', 'admin');

-- Tabla de documentos (primero)
CREATE TABLE documentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL,
    nombre_original VARCHAR(255) NOT NULL,
    carpeta VARCHAR(100) NOT NULL,
    tipo_archivo VARCHAR(100) NOT NULL,
    tamaño INT NOT NULL,
    ruta VARCHAR(500) NOT NULL,
    usuario_subio INT NOT NULL,
    fecha_subida TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_carpeta (carpeta),
    INDEX idx_usuario (usuario_subio)
);

-- Tabla de carpetas (después)
CREATE TABLE carpetas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) UNIQUE NOT NULL,
    creada_por INT NOT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insertar carpetas por defecto (después de crear usuarios)
INSERT INTO carpetas (nombre, creada_por) VALUES 
('Documentos Médicos', 1),
('Historias Clínicas', 1),
('Resultados', 1),
('Imágenes', 1);

-- Crear usuarios de ejemplo
INSERT INTO usuarios (username, password, role) VALUES 
('doctor', 'doctor123', 'user'),
('enfermera', 'enfermera123', 'user'),
('secretaria', 'secretaria123', 'user');

-- Mostrar las tablas creadas
SHOW TABLES;