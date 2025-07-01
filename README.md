# MyFlow: Plataforma de Automatización de Workflows

Este repositorio contiene el prototipo inicial de una aplicación web para la automatización de workflows, inspirada en herramientas como n8n. **MyFlow** se concibe como una interfaz visual intuitiva que permite a los usuarios definir y gestionar sus propias secuencias de tareas automatizadas, incluyendo la interacción fluida con bases de datos MySQL.

## Contenido del Repositorio

* `index.html`: La estructura principal de la interfaz de usuario de **MyFlow**.
* `style.css`: Los estilos CSS para la interfaz visual de **MyFlow**.
* `script.js`: La lógica JavaScript del frontend, que maneja la interacción del usuario, la creación/manipulación de nodos, y la comunicación con el backend.
* `create_db_table.php`: El script PHP de backend para la gestión de la base de datos (conexión, creación de DB/tabla) utilizando PDO.

## Características Implementadas (Primer Commit)

### Frontend
* **Interfaz de Usuario:** Un diseño básico que simula la estructura de una herramienta de automatización, incluyendo barra lateral, cabecera y área de editor.
* **Gestión de Nodos:**
    * Arrastrar y soltar nodos en el canvas.
    * Conexión de nodos mediante líneas visuales.
    * Guardado y carga de la configuración de workflows en/desde JSON (almacenamiento local y descarga/carga de archivo).
    * **Configuración de Nodos de Base de Datos:** Modal para introducir credenciales de conexión (Host, Puerto, Usuario, Contraseña, Nombre de BD, Nombre de Tabla) y definir campos dinámicos para la tabla.

### Backend (`create_db_table.php`)
* **Conexión a MySQL con PDO:** Establece una conexión segura a la base de datos.
* **Creación de Base de Datos y Tabla:** Si no existen, el script crea la base de datos y la tabla especificadas.
* **Manejo Automático de ID Primario:** Si el primer campo definido en el frontend para una tabla comienza con `id_` (ej. `id_usuario`), el backend lo configurará automáticamente como `INT AUTO_INCREMENT PRIMARY KEY`, simplificando la entrada del usuario y asegurando la consistencia.
* **Recuperación de Datos:** Después de asegurar la tabla, devuelve los datos existentes en ella.
* **Manejo de Errores:** Captura y reporta errores de conexión o SQL.

## Requisitos y Configuración

Para ejecutar este prototipo de **MyFlow**, necesitarás un entorno de servidor web que soporte PHP y MySQL/MariaDB.

1.  **Servidor Web con PHP:** Se recomienda usar XAMPP, MAMP, WAMP o un servidor Apache/Nginx con PHP instalado.
2.  **Base de Datos MySQL/MariaDB:**
    * Asegúrate de que tu servidor MySQL/MariaDB esté en ejecución.
    * Para configuraciones por defecto (ej. XAMPP):
        * **Host:** `localhost`
        * **Puerto:** `3306`
        * **Usuario:** `root`
        * **Contraseña:** (dejar **vacío**)
3.  **Habilitar Extensión PDO MySQL en PHP:**
    * Abre tu archivo `php.ini`.
    * Busca la línea `;extension=pdo_mysql` y quítale el punto y coma `;` al principio para descomentarla.
    * Guarda el archivo y **reinicia tu servidor web (Apache)**.

### Pasos para Poner en Marcha

1.  Clona o descarga este repositorio.
2.  Coloca todos los archivos (`index.html`, `style.css`, `script.js`, `create_db_table.php`) en el directorio raíz de tu servidor web (ej. `htdocs` en XAMPP, `www` en MAMP).
3.  Abre tu navegador y navega a la URL donde hayas colocado los archivos (ej. `http://localhost/tu_carpeta/index.html`).
4.  Para probar la funcionalidad de base de datos, abre el modal de configuración de DB desde un nodo y rellena los campos. Recuerda que si el primer campo de tu tabla empieza con `id_`, el backend lo configurará automáticamente como `PRIMARY KEY AUTO_INCREMENT`.

## Próximos Pasos / Mejoras Futuras

* Implementación de más tipos de nodos (ej. HTTP Request, Email, etc.).
* Validación más robusta de los nombres de columna y tipos de datos en el backend para evitar inyecciones SQL.
* Manejo de actualizaciones de tabla (alterar columnas existentes).
* Interfaz de usuario mejorada para la definición de campos de tabla.
* Funcionalidad de ejecución real de workflows.
* Persistencia de workflows en base de datos en lugar de localStorage/JSON file.

---