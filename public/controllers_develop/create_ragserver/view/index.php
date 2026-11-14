<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Crear Controlador</title>
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

    .form-group {
      display: flex;
      flex-direction: column;
      gap: 8px;
      margin-bottom: 24px;
    }

    label {
      font-size: 0.85rem;
      font-weight: 600;
      color: var(--text-main);
    }

    .input-field {
      width: 100%;
      font-family: var(--mono);
      font-size: 0.9rem;
      padding: 12px 16px;
      background-color: #fafbfc;
      border: 1px solid var(--border);
      border-radius: 4px;
      color: var(--text-main);
      outline: none;
      transition: all 0.2s ease;
    }

    .input-field:focus {
      border-color: var(--ac-red);
      background-color: #ffffff;
      box-shadow: 0 0 0 3px var(--ac-red-light);
    }

    .actions {
      display: flex;
      justify-content: flex-end;
      gap: 12px;
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

    /* Contenedor dinámico de respuesta con diseño alineado al Log Terminal */
    .response-card {
      padding: 16px;
      border-radius: 4px;
      font-family: var(--mono);
      font-size: 0.8rem;
      line-height: 1.5;
    }

    .response-card.loading {
      background-color: var(--dark-header);
      color: #38bdf8;
      border: 1px solid var(--border);
    }

    .response-card.success {
      background-color: #f0fdf4;
      border: 1px solid #bbf7d0;
      color: #166534;
    }

    .response-card.error {
      background-color: var(--ac-red-light);
      border: 1px solid #fecdd3;
      color: var(--ac-red);
    }

    .response-card a {
      display: inline-block;
      margin-top: 10px;
      color: var(--dark-header);
      font-weight: 600;
      text-decoration: underline;
      transition: color 0.15s ease;
    }

    .response-card a:hover {
      color: var(--ac-red);
    }

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
        <span class="brand-title">Crear Controlador</span>
      </div>
      <div class="system-status">
        <span class="status-dot"></span>
        <span>SCAFFOLD ENGINE</span>
      </div>
    </header>
    
    <main class="card">
      <div class="card-title">Copiar Directorio Base</div>
      
      <form id="copyForm" action="../client/" method="POST">
        <div class="form-group">
          <label for="destination_folder">Nombre de la carpeta destino:</label>
          <input type="text" id="destination_folder" name="destination_folder" class="input-field" placeholder="ej. nuevo_modulo" required>
        </div>

        <div class="actions">
          <button type="submit" class="btn btn-primary" id="submitBtn">Crear directorio</button>
        </div>
      </form>

      <div id="responseContainer" style="margin-top: 20px;"></div>
    </main>

    <footer>
      cucutaindustrial.com &copy; 2026 — Restricted Access
    </footer>
  </div>

  <script>
    document.getElementById('copyForm').addEventListener('submit', async function(e) {
      e.preventDefault();
      
      const container = document.getElementById('responseContainer');
      const submitBtn = document.getElementById('submitBtn');

      submitBtn.disabled = true;
      container.innerHTML = `
        <div class="response-card loading">
          <span style="color: #64748b;">[PROCESANDO]</span> Copiando directorio y estructurando controlador...
        </div>
      `;

      const formData = new FormData(this);

      try {
        const response = await fetch(this.action, {
          method: 'POST',
          body: formData
        });

        const result = await response.json();

        if (result.success) {
          container.innerHTML = `
            <div class="response-card success">
              <div><strong>[OK]</strong> ${result.message}</div>
              <a href="../../../controllers_project/${result.data.folder_name}/" target="_blank">
                &rarr; Ingresar a "${result.data.folder_name}"
              </a>
            </div>
          `;
          this.reset();
        } else {
          const errorMsg = result.error ? result.error.message : result.message;
          container.innerHTML = `
            <div class="response-card error">
              <strong>[ERROR]</strong> ${errorMsg}
            </div>
          `;
        }
      } catch (error) {
        container.innerHTML = `
          <div class="response-card error">
            <strong>[CRITICAL]</strong> Error de conexión al procesar la solicitud.
          </div>
        `;
      } finally {
        submitBtn.disabled = false;
      }
    });
  </script>
</body>
</html>
