let currentRole = null;
let currentAdminSection = 'create-user';
let currentUserFolder = null;
let documents = JSON.parse(localStorage.getItem('documents')) || {};
let users = JSON.parse(localStorage.getItem('users')) || [{username: 'admin', password: 'admin123', role: 'admin'}];

function login() {
    const username = document.getElementById('username-input').value.trim();
    const password = document.getElementById('password-input').value.trim();
    const user = users.find(u => u.username === username && u.password === password);
    if (user) {
        currentRole = user.role;
        if (user.role === 'admin') {
            mostrarAdmin();
        } else {
            mostrarUser();
        }
    } else {
        alert('Credenciales incorrectas');
    }
}

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

function mostrarAdmin() {
    let usersListHTML = '';
    users.forEach(user => {
        if (user.role === 'user') {
            usersListHTML += `<li>${user.username}<br><span class="password" data-password=${JSON.stringify(user.password)}>****</span> <div class="buttons"><button onclick="togglePassword(this)">Ver</button> <button onclick="editarPassword('${user.username}')">Editar Contraseña</button></div></li>`;
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
              <ul>${Object.keys(documents).map(f => `<li>${f} <div class="buttons"><button onclick="editarCarpeta('${f}')">Editar</button> <button onclick="eliminarCarpeta('${f}')" ${documents[f].length > 0 ? 'disabled' : ''}>Eliminar</button></div></li>`).join('')}</ul>
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
                <ul>${documents[folder].map((doc, index) => `<li>${doc.name} <div class="buttons"><button onclick="deleteDoc('${folder}', ${index})">Eliminar</button></div></li>`).join('')}</ul>
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
}

function mostrarUser() {
    let foldersList = '';
    for (let folder in documents) {
        foldersList += `<button onclick="seleccionarCarpeta('${folder}')">${folder}</button>`;
    }

    let contentHTML = '<h3>Selecciona una carpeta para ver los documentos</h3>';
    if (currentUserFolder && documents[currentUserFolder]) {
        let docsHTML = '';
        documents[currentUserFolder].forEach((doc, index) => {
            docsHTML += `<li>${doc.name} <div class="buttons"><button onclick="previewDoc('${currentUserFolder}', ${index})">Vista Previa</button> <button onclick="downloadDoc('${currentUserFolder}', ${index})">Descargar</button></div></li>`;
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
}

function cambiarSeccionAdmin(section) {
    currentAdminSection = section;
    mostrarAdmin();
}

function seleccionarCarpeta(folder) {
    currentUserFolder = folder;
    mostrarUser();
}

function cerrarSesion() {
    currentRole = null;
    currentAdminSection = 'create-user';
    currentUserFolder = null;
    mostrarLogin();
}



function createUser() {
    const username = document.getElementById('new-username').value.trim();
    const password = document.getElementById('new-password').value.trim();
    if (username && password && !users.some(u => u.username === username)) {
        users.push({username, password, role: 'user'});
        localStorage.setItem('users', JSON.stringify(users));
        mostrarAdmin();
    } else {
        alert('Usuario ya existe o campos vacíos');
    }
}

function togglePassword(btn) {
    const li = btn.closest('li');
    const span = li.querySelector('.password');
    if (span.textContent === '****') {
        span.textContent = span.dataset.password;
        btn.textContent = 'Ocultar';
    } else {
        span.textContent = '****';
        btn.textContent = 'Ver';
    }
}

function editarPassword(username) {
    const newPassword = prompt('Nueva contraseña para ' + username + ':');
    if (newPassword && newPassword.trim()) {
        const user = users.find(u => u.username === username);
        if (user) {
            user.password = newPassword.trim();
            localStorage.setItem('users', JSON.stringify(users));
            mostrarAdmin();
        }
    } else {
        alert('Contraseña inválida');
    }
}

function crearCarpeta() {
    const folderName = document.getElementById('new-folder').value.trim();
    if (folderName && !documents[folderName]) {
        documents[folderName] = [];
        localStorage.setItem('documents', JSON.stringify(documents));
        mostrarAdmin();
    } else {
        alert('Carpeta ya existe o nombre vacío');
    }
}

function editarCarpeta(folder) {
    const newName = prompt('Nuevo nombre para la carpeta:', folder);
    if (newName && newName.trim() && newName !== folder && !documents[newName]) {
        documents[newName] = documents[folder];
        delete documents[folder];
        localStorage.setItem('documents', JSON.stringify(documents));
        mostrarAdmin();
    } else if (newName === folder) {
        // sin cambios
    } else {
        alert('Nombre inválido o ya existe');
    }
}

function eliminarCarpeta(folder) {
    if (documents[folder].length > 0) {
        alert('No se puede eliminar una carpeta que contiene documentos');
    } else if (confirm(`¿Eliminar la carpeta "${folder}"?`)) {
        delete documents[folder];
        localStorage.setItem('documents', JSON.stringify(documents));
        mostrarAdmin();
    }
}

function uploadFiles() {
    const folder = document.getElementById('folder-select').value;
    if (folder) {
        uploadDocs(folder);
    } else {
        alert('Selecciona una carpeta');
    }
}

function uploadDocs(folder) {
    const files = document.getElementById('file-input').files;
    if (!documents[folder]) documents[folder] = [];
    for (let file of files) {
        const reader = new FileReader();
        reader.onload = (e) => {
            documents[folder].push({name: file.name, data: e.target.result, type: file.type});
            localStorage.setItem('documents', JSON.stringify(documents));
            mostrarAdmin();
        };
        reader.readAsDataURL(file);
    }
}

function deleteDoc(folder, index) {
    documents[folder].splice(index, 1);
    localStorage.setItem('documents', JSON.stringify(documents));
    mostrarAdmin();
}

function previewDoc(folder, index) {
    const doc = documents[folder][index];
    if (doc.type.startsWith('image/') || doc.type === 'application/pdf' || doc.type.startsWith('text/')) {
        document.getElementById('preview-content').src = doc.data;
        document.getElementById('preview-modal').style.display = 'block';
    } else {
        alert('No se puede previsualizar este tipo de archivo. Solo imágenes, PDFs y textos.');
    }
}

function closePreview() {
    document.getElementById('preview-modal').style.display = 'none';
    document.getElementById('preview-content').src = '';
}

// Iniciar la aplicación mostrando login
mostrarLogin();