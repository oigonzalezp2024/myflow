# Guía de Instalación y Ejecución: RAG Server

## 1. Configuración de variables de entorno

Debes establecer las siguientes variables directamente en tu terminal antes de ejecutar el servidor.

### En sistemas basados en Unix/Linux/macOS:

```bash
export GEMINI_API_KEY="YOUR_GEMINI_API_KEY"
export SERVERPORT="9020"
export WVPORT="9035"

```

### En Windows PowerShell:

```powershell
$env:GEMINI_API_KEY="tu_clave_de_api"
$env:SERVERPORT="9020"
$env:WVPORT="9035"

```

---

## 2. Infraestructura con Docker

Navega hasta la carpeta de pruebas y levanta los contenedores:

```bash
cd tests/
docker compose up -d

```

---

## 3. Ejecución del `ragserver`

Una vez configuradas las variables y los servicios de Docker, abre otra terminal, navega hasta el directorio del servidor e inícialelo con Go:

```bash
cd ragserver
go run .

```

---

## 4. Integración con PHP

Abre otra terminal para ejecutar el script de demostración en PHP:

```bash
cd /myflow/empresa/public/
php ragserver-demo.php

```

### Ejemplo de salida esperada:

```powershell
PS C:\xampp\htdocs\myflow\empresa\public> php ragserver-demo.php
Indexando documentos...
Array
(
    [status] => ok
    [http_code] => 200
)

Consultando a Gemini...
Según la información proporcionada:

*   El horario de soporte técnico para mantenimiento industrial es de **lunes a viernes de 7:30 AM a 5:30 PM**.
*   La garantía estándar de los equipos fabricados dura **12 meses** y cubre defectos de soldadura y estructura.
PS C:\xampp\htdocs\myflow\empresa\public> 

```

---
