<?php
// Configuración de cabeceras para CORS
header("Access-Control-Allow-Origin: *"); // AJUSTA ESTO EN PRODUCCIÓN POR SEGURIDAD
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

// Manejo de la petición OPTIONS (pre-flight request)
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Obtener y decodificar los datos JSON
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Validar los datos recibidos
if (
    !isset($data['host'], $data['username'], $data['password'], $data['dbName'], $data['tableName'], $data['fields']) ||
    !is_array($data['fields']) ||
    empty($data['fields'])
) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'Faltan campos de configuración esenciales o los campos de tabla están vacíos.']);
    exit();
}

$host = $data['host'];
$port = isset($data['port']) && !empty($data['port']) ? (int)$data['port'] : 3306;
$username = $data['username'];
$password = $data['password'];
$dbName = $data['dbName'];
$tableName = $data['tableName'];
$fields = $data['fields'];

// --- MODIFICACIÓN: Si el primer campo empieza con 'id_', se configura como PRIMARY KEY AUTO_INCREMENT por defecto ---
if (!empty($fields)) {
    // Accedemos al primer campo directamente por referencia para modificarlo
    $firstField = &$fields[0]; // Usar referencia para modificar el array original
    $firstColName = $firstField['key'];

    if (str_starts_with($firstColName, 'id_')) {
        // Si el nombre del primer campo empieza con 'id_', FORZAMOS su tipo
        // para que sea INT AUTO_INCREMENT PRIMARY KEY.
        // Esto ignora cualquier valor que el usuario haya puesto en el frontend para este campo.
        $firstField['value'] = 'INT AUTO_INCREMENT PRIMARY KEY';
        error_log("Primer campo detectado como ID auto-incremental. Forzando tipo: " . $firstField['value']);
    }
}
// --- FIN DE LA MODIFICACIÓN ---

$pdo = null; // Inicializar la variable de conexión PDO

try {
    // --- 1. Conectar al servidor MySQL usando PDO ---
    // DSN (Data Source Name): Sin especificar la base de datos inicialmente
    $dsn = "mysql:host={$host};port={$port}";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // Configurar PDO para lanzar excepciones en errores
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // Por defecto, obtener resultados como array asociativo
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8" // Asegurar UTF-8
    ]);

    error_log("Conectado a MySQL en {$host}:{$port} como {$username} usando PDO.");

    // --- 2. Crear la base de datos si no existe ---
    // Usamos `exec` para sentencias que no devuelven un conjunto de resultados
    $createDbSql = "CREATE DATABASE IF NOT EXISTS `{$dbName}`";
    $pdo->exec($createDbSql);
    error_log("Base de datos '{$dbName}' asegurada.");

    // --- 3. Cambiar la conexión a la base de datos recién creada/existente ---
    $pdo->exec("USE `{$dbName}`");
    error_log("Cambiado a la base de datos '{$dbName}'.");

    // --- 4. Construir y ejecutar la sentencia CREATE TABLE IF NOT EXISTS ---
    $columnDefinitions = [];
    foreach ($fields as $field) { // Ahora el array $fields ya tiene el valor modificado si aplica
        $colName = $field['key']; // Asumimos que la clave del campo ya está limpia
        $colType = $field['value']; // Este será el valor forzado si el campo es un 'id_'

        // IMPORTANTE: NOTA DE SEGURIDAD CRÍTICA (Repetida y fundamental)
        // El $colType ($field['value']) viene directamente del input del usuario.
        // Esto es una ENORME VULNERABILIDAD de inyección SQL si no se valida RIGUROSAMENTE.
        // En una aplicación real, DEBES tener una lista blanca de tipos de datos SQL permitidos
        // (ej: 'INT', 'VARCHAR(255)', 'TEXT', 'DATETIME', etc.) y validar $field['value']
        // contra esa lista. No permitas que el usuario inserte cualquier cadena aquí.
        // Para este demo, continuamos asumiendo que el usuario ingresa tipos SQL válidos y seguros,
        // excepto para el primer campo 'id_' que estamos forzando.
        // Para nombres de columna ($colName), también es buena práctica validarlos o escaparlos
        // de forma específica para identificadores, aunque los backticks en MySQL ayudan.

        $columnDefinitions[] = "`{$colName}` {$colType}";
    }
    $tableColumnsSql = implode(', ', $columnDefinitions);

    $createTableSql = "CREATE TABLE IF NOT EXISTS `{$tableName}` ({$tableColumnsSql})";
    error_log("Sentencia CREATE TABLE: " . $createTableSql); // Para depuración

    $pdo->exec($createTableSql);
    error_log("Tabla '{$tableName}' asegurada en la base de datos '{$dbName}'.");

    // --- 5. Devolver los datos de la tabla si existe (o si acaba de ser creada) ---
    $tableData = [];
    $selectSql = "SELECT * FROM `{$tableName}`";
    $message = 'Base de datos y tabla aseguradas con éxito.';

    // Usamos query() para sentencias SELECT que devuelven resultados
    $stmt = $pdo->query($selectSql);
    $tableData = $stmt->fetchAll(PDO::FETCH_ASSOC); // Obtener todas las filas como un array asociativo

    if (count($tableData) > 0) {
        $message .= ' Datos de la tabla recuperados.';
    } else {
        $message .= ' La tabla está vacía.';
    }

    // Respuesta exitosa con los datos de la tabla
    echo json_encode([
        'success' => true,
        'message' => $message,
        'tableData' => $tableData // Incluimos los datos de la tabla aquí
    ]);

} catch (PDOException $e) {
    // Capturar excepciones PDO para errores de base de datos
    http_response_code(500); // Internal Server Error
    echo json_encode([
        'success' => false,
        'message' => 'Error de PDO al asegurar base de datos o tabla: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    // Capturar otras excepciones generales
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error inesperado: ' . $e->getMessage()
    ]);
} finally {
    // Asegurarse de cerrar la conexión (PDO la cierra automáticamente cuando el script termina,
    // o puedes establecer $pdo = null;)
    $pdo = null;
    error_log('Conexión PDO cerrada/liberada.');
}
