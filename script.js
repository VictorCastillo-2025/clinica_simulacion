// Variables globales
let currentUser = null;
let currentRole = null;
let currentAdminSection = 'create-user';
let currentUserFolder = null;

// Configuración de la API
const API_BASE = ''; // Cambiar a tu dominio cuando subas al hosting

// Funciones AJAX genéricas
async function apiCall(url, method = 'GET', data = null) {
    try {
        const options = {
            method: method,
            headers: {
                'Content-Type': 'application/json',
            }
        };

        if (data && method !== 'GET') {
            if (data instanceof FormData) {
                options.body = data;
                delete options.headers['Content-Type']; // El navegador lo establece automáticamente
            } else {
                options.body = JSON.stringify(data);
            }
        }

        const response = await fetch(API_BASE + url, options);
        const result = await response.json();
        
        if (!result.success) {
            throw new Error(result.message || 'Error en la solicitud');
        }
        
        return result;
    } catch (error) {
        console.error('Error en API call:', error);
        throw error;
    }
}

// Función de login con API
async function login() {
    const username = document.getElementById('username-input').value.trim();
    const password = document.getElementById('password-input').value.trim();

    if (!username || !password) {
        alert('Por favor ingrese usuario y contraseña');
        return;
    }

    try {
        const formData = new FormData();
        formData.append('username', username);
        formData.append('password', password);

        const result = await apiCall('get_users.php?action=login', 'POST', formData);
        
        currentUser = result.data;
        currentRole = result.data.role;

        if (currentRole === 'admin') {
            await loadUsers();
            await loadDocuments();
            mostrarAdmin();
        } else {
            await loadDocuments();
            mostrarUser();
        }

    } catch (error) {
        alert('Credenciales incorrectas: ' + error.message);
    }
}

// Cargar usuarios desde la API
async function loadUsers() {
    try {
        const result = await apiCall('get_users.php?action=list');
        return result.data || [];
    } catch (error) {
        console.error('Error cargando usuarios:', error);
        return [];
    }
}

// Cargar documentos desde la API
async function loadDocuments() {
    try {
        const result = await apiCall('get_files.php?action=list');
        return result.data || {};
    } catch (error) {
        console.error('Error cargando documentos:', error);
        return {};
    }
}

// Mostrar login
function mostrarLogin() {
    const app = document.getElementById('app');
    app.innerHTML = `
        <div id="login-section">
            <h2>Iniciar Sesión</h2>
            <input type="text" id="username-input" placeholder="Usuario" required>
            <input type="password" id="password-input" placeholder="Contraseña" required>
            <button id="login-btn">Entrar</button>
        </div>
    `;
    document.getElementById('login-btn').onclick = login;
    document.getElementById('username-input').addEventListener('keydown', (e) => {
        if (e.key === 'Enter') login();
    });
    document.getElementById('password-input').addEventListener('keydown', (e) => {
        if (e.key === 'Enter') login();
    });
}

// Mostrar panel de administrador
async function mostrarAdmin() {
    try {
        const users = await loadUsers();
        const documents = await loadDocuments();

        let usersListHTML = '';
        users.forEach(user => {
            if (user.role === 'user') {
                usersListHTML += `<li>${user.username}<br><span class="password">****</span> <div class="buttons"><button onclick="editarPassword(${user.id})">Editar Contraseña</button></div></li>`;
            }
        });

        let foldersOptions = '<option value="">Selecciona carpeta</option>';
        for (let folder in documents) {
            foldersOptions += `<option value="${folder}">${folder}</option>`;
        }

        let contentHTML = '';
        if (currentAdminSection === 'create-user') {
            contentHTML = `
                <h3>Crear Usuario</h3>
                <input type="text" id="new-username" placeholder="Nuevo Usuario">
                <input type="password" id="new-password" placeholder="Contraseña">
                <button id="create-user-btn">Crear Usuario</button>
                <h3>Usuarios</h3>
                <ul>${usersListHTML}</ul>
            `;
        } else if (currentAdminSection === 'create-folder') {
            contentHTML = `
                <h3>Crear Carpeta</h3>
                <input type="text" id="new-folder" placeholder="Nueva Carpeta">
                <button onclick="crearCarpeta()">Crear Carpeta</button>
                <h3>Carpetas Existentes</h3>
                <ul>${Object.keys(documents).map(f => `<li>${f} <div class="buttons"><button onclick="eliminarCarpeta('${f}')">Eliminar</button></div></li>`).join('')}</ul>
            `;
        } else if (currentAdminSection === 'upload-docs') {
            contentHTML = `
                <h3>Subir Documentos</h3>
                <select id="folder-select">${foldersOptions}</select>
                <input type="file" id="file-input" multiple>
                <button onclick="uploadFiles()">Subir Documentos</button>
                <h3>Documentos por Carpeta</h3>
                ${Object.keys(documents).map(folder => `
                    <h4>${folder}</h4>
                    <ul>${documents[folder].map((doc, index) => `<li>${doc.name} <div class="buttons"><button onclick="deleteDoc('${folder}', ${doc.id})">Eliminar</button></div></li>`).join('')}</ul>
                `).join('')}
            `;
        }

        const app = document.getElementById('app');
        app.innerHTML = `
            <div id="admin-section">
                <div class="sidebar">
                    <button onclick="cambiarSeccionAdmin('create-user')">Crear Usuarios</button>
                    <button onclick="cambiarSeccionAdmin('create-folder')">Crear Carpetas</button>
                    <button onclick="cambiarSeccionAdmin('upload-docs')">Subir Documentos</button>
                    <button onclick="cerrarSesion()">Cerrar Sesión</button>
                </div>
                <div class="main-content">
                    ${contentHTML}
                </div>
            </div>
        `;

        // Eventos para admin
        if (currentAdminSection === 'create-user') {
            document.getElementById('create-user-btn').onclick = createUser;
        }
    } catch (error) {
        console.error('Error mostrando admin:', error);
        alert('Error cargando el panel de administración');
    }
}

// Mostrar panel de usuario
async function mostrarUser() {
    try {
        const documents = await loadDocuments();
        
        let foldersList = '';
        for (let folder in documents) {
            foldersList += `<button onclick="seleccionarCarpeta('${folder}')">${folder}</button>`;
        }

        let contentHTML = '<h3>Selecciona una carpeta para ver los documentos</h3>';
        if (currentUserFolder && documents[currentUserFolder]) {
            let docsHTML = '';
            documents[currentUserFolder].forEach((doc) => {
                docsHTML += `<li>${doc.name} <div class="buttons"><button onclick="previewDoc('${currentUserFolder}', ${doc.id})">Vista Previa</button> <button onclick="downloadDoc('${currentUserFolder}', ${doc.id})">Descargar</button></div></li>`;
            });
            contentHTML = `<h3>${currentUserFolder}</h3><ul>${docsHTML}</ul>`;
        }

        const app = document.getElementById('app');
        app.innerHTML = `
            <div id="user-section">
                <div class="sidebar">
                    <h4>Carpetas</h4>
                    ${foldersList}
                    <button onclick="cerrarSesion()">Cerrar Sesión</button>
                </div>
                <div class="main-content">
                    ${contentHTML}
                </div>
            </div>
        `;
    } catch (error) {
        console.error('Error mostrando usuario:', error);
        alert('Error cargando el panel de usuario');
    }
}

// Cambiar sección de admin
function cambiarSeccionAdmin(section) {
    currentAdminSection = section;
    mostrarAdmin();
}

// Seleccionar carpeta
function seleccionarCarpeta(folder) {
    currentUserFolder = folder;
    mostrarUser();
}

// Cerrar sesión
async function cerrarSesion() {
    try {
        await apiCall('get_users.php?action=logout', 'POST');
    } catch (error) {
        console.error('Error en logout:', error);
    }
    
    currentUser = null;
    currentRole = null;
    currentAdminSection = 'create-user';
    currentUserFolder = null;
    mostrarLogin();
}

// Crear usuario
async function createUser() {
    const username = document.getElementById('new-username').value.trim();
    const password = document.getElementById('new-password').value.trim();
    
    if (!username || !password) {
        alert('Por favor complete todos los campos');
        return;
    }

    try {
        const formData = new FormData();
        formData.append('username', username);
        formData.append('password', password);
        formData.append('role', 'user');

        await apiCall('get_users.php?action=create', 'POST', formData);
        
        document.getElementById('new-username').value = '';
        document.getElementById('new-password').value = '';
        
        await mostrarAdmin();
        alert('Usuario creado exitosamente');
    } catch (error) {
        alert('Error creando usuario: ' + error.message);
    }
}

// Editar contraseña
async function editarPassword(userId) {
    const newPassword = prompt('Nueva contraseña:');
    
    if (!newPassword || !newPassword.trim()) {
        alert('Contraseña inválida');
        return;
    }

    try {
        const formData = new FormData();
        formData.append('user_id', userId);
        formData.append('new_password', newPassword.trim());

        await apiCall('get_users.php?action=update_password', 'POST', formData);
        
        await mostrarAdmin();
        alert('Contraseña actualizada exitosamente');
    } catch (error) {
        alert('Error actualizando contraseña: ' + error.message);
    }
}

// Crear carpeta
function crearCarpeta() {
    const folderName = document.getElementById('new-folder').value.trim();
    if (!folderName) {
        alert('Por favor ingrese un nombre para la carpeta');
        return;
    }
    
    // Esta función requiere un endpoint adicional en el backend
    alert('Función de crear carpeta requiere implementación en el backend');
}

// Eliminar carpeta
function eliminarCarpeta(folder) {
    if (confirm(`¿Eliminar la carpeta "${folder}"?`)) {
        // Esta función requiere un endpoint adicional en el backend
        alert('Función de eliminar carpeta requiere implementación en el backend');
    }
}

// Subir archivos
async function uploadFiles() {
    const folder = document.getElementById('folder-select').value;
    const fileInput = document.getElementById('file-input');
    
    if (!folder) {
        alert('Por favor seleccione una carpeta');
        return;
    }

    if (fileInput.files.length === 0) {
        alert('Por favor seleccione archivos para subir');
        return;
    }

    try {
        const formData = new FormData();
        formData.append('folder', folder);
        formData.append('user_id', currentUser.id);
        
        // Agregar todos los archivos
        for (let file of fileInput.files) {
            formData.append('files[]', file);
        }

        const result = await apiCall('upload.php', 'POST', formData);
        
        fileInput.value = '';
        await mostrarAdmin();
        alert(result.message);
    } catch (error) {
        alert('Error subiendo archivos: ' + error.message);
    }
}

// Eliminar documento
async function deleteDoc(folder, docId) {
    if (!confirm('¿Está seguro de eliminar este documento?')) {
        return;
    }

    try {
        const formData = new FormData();
        formData.append('doc_id', docId);

        await apiCall('get_files.php?action=delete', 'POST', formData);
        
        await mostrarAdmin();
        alert('Documento eliminado exitosamente');
    } catch (error) {
        alert('Error eliminando documento: ' + error.message);
    }
}

// Vista previa de documento
async function previewDoc(folder, docId) {
    try {
        // Obtener información del documento
        const docs = await loadDocuments();
        const doc = documents[folder].find(d => d.id === docId);
        
        if (!doc) {
            alert('Documento no encontrado');
            return;
        }

        if (doc.type.startsWith('image/') || doc.type === 'application/pdf' || doc.type.startsWith('text/')) {
            document.getElementById('preview-content').src = doc.path;
            document.getElementById('preview-modal').style.display = 'block';
        } else {
            alert('No se puede previsualizar este tipo de archivo. Solo imágenes, PDFs y textos.');
        }
    } catch (error) {
        alert('Error cargando vista previa: ' + error.message);
    }
}

// Descargar documento
function downloadDoc(folder, docId) {
    const link = document.createElement('a');
    link.href = `uploads/${folder}/${docId}`;
    link.download = '';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// Cerrar vista previa
function closePreview() {
    document.getElementById('preview-modal').style.display = 'none';
    document.getElementById('preview-content').src = '';
}

// Verificar sesión al cargar la página
async function checkSession() {
    try {
        const result = await apiCall('get_users.php?action=check_session');
        if (result.success) {
            currentUser = result.data;
            currentRole = result.data.role;
            
            if (currentRole === 'admin') {
                await mostrarAdmin();
            } else {
                await mostrarUser();
            }
        } else {
            mostrarLogin();
        }
    } catch (error) {
        mostrarLogin();
    }
}

// Iniciar la aplicación
window.addEventListener('DOMContentLoaded', () => {
    checkSession();
});