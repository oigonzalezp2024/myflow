<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>File Ingestion</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@500;700&family=JetBrains+Mono:wght@400;500&family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
  <style>
    :root {
      --bg: #f7f9fa;
      --card-bg: #ffffff;
      --border: #e1e6eb;
      --text-main: #1a2129;
      --text-muted: #64748b;
      --ac-red: #c9182b;
      --ac-red-light: #fff0f2;
      --dark-header: #0f172a;
      --mono: 'JetBrains Mono', monospace;
      --serif: 'Cinzel', serif;
    }

    * { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      background-color: var(--bg);
      color: var(--text-main);
      font-family: 'Inter', sans-serif;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
      padding: 40px 20px;
      line-height: 1.5;
    }

    body::before {
      content: "";
      position: fixed;
      top: 0; left: 0; width: 100%; height: 100%;
      background-image: 
        linear-gradient(to right, rgba(0, 0, 0, 0.02) 1px, transparent 1px),
        linear-gradient(to bottom, rgba(0, 0, 0, 0.02) 1px, transparent 1px);
      background-size: 32px 32px;
      pointer-events: none;
      z-index: -1;
    }

    .container {
      width: 100%;
      max-width: 680px;
    }

    header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      border-bottom: 2px solid var(--dark-header);
      padding-bottom: 16px;
      margin-bottom: 32px;
    }

    .brand {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .brand svg {
      width: 28px;
      height: 28px;
      fill: var(--ac-red);
    }

    .brand-title {
      font-family: var(--serif);
      font-size: 1.25rem;
      font-weight: 700;
      letter-spacing: 2px;
      color: var(--dark-header);
      text-transform: uppercase;
    }

    .system-status {
      font-family: var(--mono);
      font-size: 0.75rem;
      color: var(--text-muted);
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .status-dot {
      width: 8px;
      height: 8px;
      background-color: #10b981;
      border-radius: 50%;
    }

    .card {
      background: var(--card-bg);
      border: 1px solid var(--border);
      border-top: 4px solid var(--ac-red);
      box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.04);
      padding: 32px;
      margin-bottom: 24px;
    }

    .card-title {
      font-size: 0.9rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 1px;
      color: var(--text-muted);
      margin-bottom: 20px;
    }

    .dropzone {
      border: 2px dashed var(--border);
      border-radius: 4px;
      padding: 40px 20px;
      text-align: center;
      cursor: pointer;
      transition: all 0.2s ease;
      background-color: #fafbfc;
      position: relative;
    }

    .dropzone:hover, .dropzone.dragover {
      border-color: var(--ac-red);
      background-color: var(--ac-red-light);
    }

    .dropzone input {
      position: absolute;
      top: 0; left: 0; width: 100%; height: 100%;
      opacity: 0;
      cursor: pointer;
    }

    .dropzone-icon {
      margin-bottom: 12px;
      color: var(--text-muted);
    }

    .dropzone-text {
      font-size: 0.95rem;
      color: var(--text-main);
      font-weight: 500;
    }

    .dropzone-subtext {
      font-size: 0.75rem;
      color: var(--text-muted);
      margin-top: 4px;
      font-family: var(--mono);
    }

    .actions {
      display: flex;
      justify-content: flex-end;
      gap: 12px;
      margin-top: 24px;
    }

    .btn {
      font-family: var(--serif);
      font-size: 0.8rem;
      font-weight: 700;
      letter-spacing: 1.5px;
      padding: 10px 24px;
      border: 1px solid transparent;
      cursor: pointer;
      transition: all 0.15s ease;
      text-transform: uppercase;
    }

    .btn:disabled {
      opacity: 0.5;
      cursor: not-allowed;
    }

    .btn-primary {
      background-color: var(--dark-header);
      color: #ffffff;
    }

    .btn-primary:hover:not(:disabled) {
      background-color: var(--ac-red);
    }

    .btn-secondary {
      background-color: transparent;
      border-color: var(--border);
      color: var(--text-main);
    }

    .btn-secondary:hover:not(:disabled) {
      background-color: var(--bg);
    }

    .log-container {
      background: #0f172a;
      color: #e2e8f0;
      font-family: var(--mono);
      font-size: 0.78rem;
      padding: 16px;
      border-radius: 4px;
      max-height: 180px;
      overflow-y: auto;
      margin-bottom: 24px;
    }

    .log-entry {
      margin-bottom: 4px;
      line-height: 1.4;
    }

    .log-entry .timestamp { color: #64748b; }
    .log-entry.info { color: #38bdf8; }
    .log-entry.success { color: #4ade80; }
    .log-entry.error { color: #f87171; }

    footer {
      text-align: center;
      font-size: 0.75rem;
      color: var(--text-muted);
      margin-top: auto;
      padding-top: 24px;
      font-family: var(--mono);
    }
  </style>
</head>
<body>

  <div class="container">
    <header>
      <div class="brand">
        <span class="brand-title">Cargado de archivos</span>
      </div>
      <div class="system-status">
        <span class="status-dot"></span>
        <span>SECURE INGESTION ENGINE</span>
      </div>
    </header>
    
    <main class="card">
      <div class="card-title">Ingreso Seguro de Archivos</div>
      
      <form id="uploadForm">
        <div class="dropzone" id="dropzone">
          <input type="file" name="fileToUpload" id="fileInput" accept=".jpg,.jpeg,.png,.gif,.pdf" required>
          <div class="dropzone-icon">
            <svg width="32" height="32" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
              <path d="M12 16.5V3.75m0 0L7.5 8.25m4.5-4.5l4.5 4.5M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5"></path>
            </svg>
          </div>
          <div class="dropzone-text" id="fileName">Selecciona o arrastra una imagen o PDF</div>
          <div class="dropzone-subtext">JPG, PNG, GIF, PDF — Máximo 500 KB</div>
        </div>

        <div class="actions">
          <button type="reset" class="btn btn-secondary" id="resetBtn">Limpiar</button>
          <button type="submit" class="btn btn-primary" id="submitBtn">Subir Archivo</button>
        </div>
      </form>
    </main>
    
    <!-- Tarjeta de Galería de Documentos e Imágenes Ingeridos -->
    <main class="card" id="galleryCard" style="display: none;">
      <div class="card-title">Documentos ingresados</div>
      <div id="imageGallery" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap: 12px;"></div>
    </main>
    
    <div class="log-container" id="terminal">
      <div class="log-entry info"><span class="timestamp">[SYS INIT]</span> Motor de auditoría cucutaindustrial.com listo. Esperando archivo...</div>
    </div>

    <footer>
      cucutaindustrial.com Security Framework &copy; 2026 — Restricted Access
    </footer>
  </div>

  <script>
    const uploadForm = document.getElementById('uploadForm');
    const fileInput = document.getElementById('fileInput');
    const fileNameDisplay = document.getElementById('fileName');
    const dropzone = document.getElementById('dropzone');
    const resetBtn = document.getElementById('resetBtn');
    const submitBtn = document.getElementById('submitBtn');
    const terminal = document.getElementById('terminal');
    const galleryCard = document.getElementById('galleryCard');
    const imageGallery = document.getElementById('imageGallery');

    function log(msg, type = 'info') {
      const time = new Date().toISOString().split('T')[1].slice(0, 8);
      const div = document.createElement('div');
      div.className = `log-entry ${type}`;
      div.innerHTML = `<span class="timestamp">[${time}]</span> ${msg}`;
      terminal.appendChild(div);
      terminal.scrollTop = terminal.scrollHeight;
    }

    // Función para consultar gallery.php y renderizar la galería
    async function loadGallery() {
      try {
        const response = await fetch('gallery.php');
        const data = await response.json();

        if (data.success && data.images.length > 0) {
          galleryCard.style.display = 'block';
          imageGallery.innerHTML = '';

          data.images.forEach(itemData => {
            const item = document.createElement('div');
            item.style.cssText = 'border: 1px solid var(--border); padding: 8px; background: #fafbfc; border-radius: 4px; text-align: center; display: flex; flex-direction: column; justify-content: space-between;';

            // Evaluar extensión del archivo para cambiar la vista previa
            const isPdf = itemData.file_name.toLowerCase().endsWith('.pdf') || (itemData.type && itemData.type === 'pdf');

            let previewHtml = '';
            if (isPdf) {
              previewHtml = `
                <a href="${itemData.path}" target="_blank" style="text-decoration: none; display: flex; flex-direction: column; align-items: center; justify-content: center; height: 90px; background: #fee2e2; border-radius: 2px; padding: 8px;">
                  <svg width="32" height="32" fill="none" stroke="#dc2626" stroke-width="1.5" viewBox="0 0 24 24"><path d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"></path></svg>
                  <span style="font-size: 0.65rem; color: #dc2626; font-weight: bold; margin-top: 4px; font-family: var(--mono);">PDF DOC</span>
                </a>
              `;
            } else {
              previewHtml = `
                <a href="${itemData.path}" target="_blank">
                  <img src="${itemData.path}" alt="${itemData.file_name}" style="width: 100%; height: 90px; object-fit: cover; border-radius: 2px; display: block; margin-bottom: 6px;">
                </a>
              `;
            }

            item.innerHTML = `
              ${previewHtml}
              <span style="font-family: var(--mono); font-size: 0.65rem; color: var(--text-muted); word-break: break-all; margin-top: 6px;">${itemData.file_name}</span>
            `;
            imageGallery.appendChild(item);
          });
        } else {
          galleryCard.style.display = 'none';
        }
      } catch (err) {
        console.error('Error al sincronizar la galería.');
      }
    }

    // Cargar galería al iniciar la página
    window.addEventListener('DOMContentLoaded', loadGallery);

    fileInput.addEventListener('change', (e) => {
      const file = e.target.files[0];
      if (file) {
        fileNameDisplay.textContent = `${file.name} (${(file.size / 1024).toFixed(1)} KB)`;
        log(`Archivo seleccionado: ${file.name}`, 'info');

        if (file.size > 500000) {
          log(`[ALERTA PRELIMINAR] El archivo supera los 500 KB recomendados.`, 'error');
        }
      }
    });

    // Envío asíncrono con Fetch hacia upload.php
    uploadForm.addEventListener('submit', async (e) => {
      e.preventDefault();

      if (!fileInput.files.length) {
        log('Selecciona un archivo antes de procesar la subida.', 'error');
        return;
      }

      const formData = new FormData(uploadForm);
      
      submitBtn.disabled = true;
      resetBtn.disabled = true;
      log('Enviando paquete a upload.php para inspección MIME...', 'info');

      try {
        const response = await fetch('upload.php', {
          method: 'POST',
          body: formData
        });

        const data = await response.json();

        if (data.success) {
          log(`[HTTP ${response.status}] ${data.message}`, 'success');
          log(`HASH ASIGNADO: ${data.file_name}`, 'success');
          log(`TIPO DETECTADO: ${data.mime_type}`, 'info');

          // Actualizar galería de inmediato tras éxito
          loadGallery();
          uploadForm.reset();
          fileNameDisplay.textContent = 'Selecciona o arrastra una imagen o PDF';
        } else {
          log(`[ERROR HTTP ${response.status}] ${data.message}`, 'error');
        }
      } catch (err) {
        log(`[CRITICAL] Error de red o servidor devolvió contenido no válido (No-JSON).`, 'error');
      } finally {
        submitBtn.disabled = false;
        resetBtn.disabled = false;
      }
    });

    ['dragover', 'dragenter'].forEach(evt => {
      dropzone.addEventListener(evt, (e) => {
        e.preventDefault();
        dropzone.classList.add('dragover');
      });
    });

    ['dragleave', 'drop'].forEach(evt => {
      dropzone.addEventListener(evt, () => dropzone.classList.remove('dragover'));
    });

    resetBtn.addEventListener('click', () => {
      fileNameDisplay.textContent = 'Selecciona o arrastra una imagen o PDF';
      log('Formulario reiniciado.', 'info');
    });
  </script>
</body>
</html>
