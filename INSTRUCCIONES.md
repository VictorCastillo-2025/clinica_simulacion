# Clínica de Simulación - Sistema Multiusuario

## Archivos Creados

### Backend PHP
- `config.php` - Configuración de base de datos y archivos
- `upload.php` - Manejo de subida de archivos
- `get_files.php` - Gestión de documentos (listar, eliminar)
- `get_users.php` - Gestión de usuarios (login, CRUD)
- `database.sql` - Estructura de base de datos MySQL

### Frontend Modificado
- `script.js` - Actualizado para usar AJAX en lugar de localStorage

### Estructura de Carpetas
- `uploads/` - Carpeta para archivos subidos
- `uploads/.htaccess` - Configuración de seguridad

## Pasos para Instalación

### 1. Base de Datos
1. Entra a phpMyAdmin en tu hosting
2. Importa el archivo `database.sql`
3. Verifica que se crearon las tablas: `usuarios`, `documentos`, `carpetas`

### 2. Configuración
1. Edita `config.php` con tus datos de MySQL:
   ```php
   $host = 'localhost'; // o el host de tu hosting
   $dbname = 'clinica_simulacion'; // nombre de tu base de datos
   $username = 'tu_usuario_mysql'; // tu usuario de MySQL
   $password = 'tu_contraseña_mysql'; // tu contraseña de MySQL
   ```

### 3. Subir Archivos
1. Sube todos los archivos a tu hosting (via FTP, cPanel File Manager, etc.)
2. Asegúrate que la carpeta `uploads/` tenga permisos de escritura (755 o 777)
3. Verifica que los archivos PHP tengan los permisos correctos (644)

### 4. Probar
1. Accede a tu dominio
2. Inicia sesión con:
   - Usuario: `admin`
   - Contraseña: `admin123`

### 5. Usuarios Adicionales (pre-creados)
- doctor / doctor123
- enfermera / enfermera123  
- secretaria / secretaria123

## Características
- ✅ Multiusuario real
- ✅ Archivos compartidos entre usuarios
- ✅ Sistema de carpetas
- ✅ Subida y descarga de archivos
- ✅ Roles (admin/user)
- ✅ Vista previa de archivos
- ✅ 24/7 disponible en tu hosting

## Cambios Importantes
- Ya no usa localStorage (era local al navegador)
- Ahora usa base de datos MySQL para compartir datos
- Los archivos se guardan en el servidor, no en el navegador
- Todos los usuarios ven los mismos archivos

## Notas
- Modifica la constante `API_BASE` en `script.js` si es necesario
- Los tipos de archivo permitidos están configurados en `config.php`
- El tamaño máximo de archivo es 50MB (configurable)