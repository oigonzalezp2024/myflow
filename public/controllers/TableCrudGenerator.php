<?php

declare(strict_types=1);

namespace App\Generators;

use PDO;
use PDOException;
use RuntimeException;

class TableCrudGenerator
{
    private PDO $pdo;
    private string $outputDir;

    public function __construct(array $dbConfig, string $outputDir)
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $dbConfig['host'] ?? 'localhost',
            $dbConfig['port'] ?? '3306',
            $dbConfig['dbname'],
            $dbConfig['charset'] ?? 'utf8mb4'
        );

        try {
            $this->pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            throw new RuntimeException("Error de conexión a la base de datos: " . $e->getMessage());
        }

        $this->outputDir = rtrim($outputDir, DIRECTORY_SEPARATOR);
        if (!is_dir($this->outputDir)) {
            if (!mkdir($this->outputDir, 0755, true) && !is_dir($this->outputDir)) {
                throw new RuntimeException("No se pudo crear el directorio de salida: {$this->outputDir}");
            }
        }
    }

    /**
     * Genera dos archivos individuales: {Entity}DTO.php y {Entity}Repository.php
     *
     * @return array<string, string> Rutas de los archivos creados
     */
    public function generate(string $tableName, string $namespace = 'App\\Persistence\\Generated'): array
    {
        $columns = $this->getTableColumns($tableName);
        $primaryKey = $this->getPrimaryKey($columns);

        $pascalName = str_replace(' ', '', ucwords(str_replace(['_', '-'], ' ', $tableName)));
        $dtoClassName = $pascalName . 'DTO';
        $repoClassName = $pascalName . 'Repository';

        // 1. Generar código y archivo para el DTO
        $dtoCode = $this->buildDtoCode($namespace, $dtoClassName, $columns);
        $dtoFilePath = $this->outputDir . DIRECTORY_SEPARATOR . $dtoClassName . '.php';
        
        if (file_put_contents($dtoFilePath, $dtoCode) === false) {
            throw new RuntimeException("Error al escribir el archivo DTO en: {$dtoFilePath}");
        }

        // 2. Generar código y archivo para el Repositorio CRUD
        $repoCode = $this->buildRepoCode($namespace, $tableName, $dtoClassName, $repoClassName, $columns, $primaryKey);
        $repoFilePath = $this->outputDir . DIRECTORY_SEPARATOR . $repoClassName . '.php';

        if (file_put_contents($repoFilePath, $repoCode) === false) {
            throw new RuntimeException("Error al escribir el archivo Repository en: {$repoFilePath}");
        }

        return [
            'dto' => $dtoFilePath,
            'repository' => $repoFilePath,
        ];
    }

    private function getTableColumns(string $tableName): array
    {
        $stmt = $this->pdo->prepare("DESCRIBE `" . str_replace('`', '``', $tableName) . "`");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    private function getPrimaryKey(array $columns): string
    {
        foreach ($columns as $column) {
            if ($column['Key'] === 'PRI') {
                return $column['Field'];
            }
        }
        return $columns[0]['Field'] ?? 'id';
    }

    private function mapSqlTypeToPhp(string $sqlType): string
    {
        $sqlType = strtolower($sqlType);
        if (str_contains($sqlType, 'int')) return 'int';
        if (str_contains($sqlType, 'float') || str_contains($sqlType, 'double') || str_contains($sqlType, 'decimal')) return 'float';
        if (str_contains($sqlType, 'bool')) return 'bool';
        return 'string';
    }

    private function buildDtoCode(string $namespace, string $dtoClassName, array $columns): string
    {
        $properties = '';
        $fromArray = '';
        $toArray = '';

        foreach ($columns as $col) {
            $field = $col['Field'];
            $type = $this->mapSqlTypeToPhp($col['Type']);
            $nullable = $col['Null'] === 'YES' ? '?' : '';

            $properties .= "    public {$nullable}{$type} \${$field};\n";

            $cast = ($type === 'int' || $type === 'float') ? "({$type})" : '';
            if ($nullable) {
                $fromArray .= "        \$dto->{$field} = isset(\$data['{$field}']) ? {$cast}\$data['{$field}'] : null;\n";
            } else {
                $fromArray .= "        \$dto->{$field} = {$cast}(\$data['{$field}'] ?? " . ($type === 'string' ? "''" : '0') . ");\n";
            }

            $toArray .= "            '{$field}' => \$this->{$field},\n";
        }

        return "<?php\n\ndeclare(strict_types=1);\n\nnamespace {$namespace};\n\nclass {$dtoClassName}\n{\n{$properties}\n    public static function fromArray(array \$data): self\n    {\n        \$dto = new self();\n{$fromArray}        return \$dto;\n    }\n\n    public function toArray(): array\n    {\n        return [\n{$toArray}        ];\n    }\n}\n";
    }

    private function buildRepoCode(
        string $namespace,
        string $tableName,
        string $dtoClassName,
        string $repoClassName,
        array $columns,
        string $primaryKey
    ): string {
        $insertFields = [];
        $insertParams = [];
        $updateFields = [];

        foreach ($columns as $col) {
            $field = $col['Field'];
            if ($field === $primaryKey && str_contains(strtolower($col['Extra']), 'auto_increment')) {
                continue;
            }
            $insertFields[] = "`{$field}`";
            $insertParams[] = ":{$field}";
        }

        foreach ($columns as $col) {
            $field = $col['Field'];
            if ($field === $primaryKey) continue;
            $updateFields[] = "`{$field}` = :{$field}";
        }

        $insertSqlColumns = implode(', ', $insertFields);
        $insertSqlParams = implode(', ', $insertParams);
        $updateSqlSet = implode(', ', $updateFields);

        return "<?php\n\ndeclare(strict_types=1);\n\nnamespace {$namespace};\n\nuse PDO;\n\nclass {$repoClassName}\n{\n    private PDO \$pdo;\n\n    public function __construct(PDO \$pdo)\n    {\n        \$this->pdo = \$pdo;\n    }\n\n    public function findById(int \$id): ?{$dtoClassName}\n    {\n        \$stmt = \$this->pdo->prepare('SELECT * FROM `{$tableName}` WHERE `{$primaryKey}` = ?');\n        \$stmt->execute([\$id]);\n        \$data = \$stmt->fetch();\n\n        return \$data ? {$dtoClassName}::fromArray(\$data) : null;\n    }\n\n    /**\n     * @return {$dtoClassName}[]\n     */\n    public function findAll(): array\n    {\n        \$stmt = \$this->pdo->query('SELECT * FROM `{$tableName}`');\n        \$results = [];\n        while (\$row = \$stmt->fetch()) {\n            \$results[] = {$dtoClassName}::fromArray(\$row);\n        }\n        return \$results;\n    }\n\n    public function create({$dtoClassName} \$dto): int|string\n    {\n        \$sql = 'INSERT INTO `{$tableName}` ({$insertSqlColumns}) VALUES ({$insertSqlParams})';\n        \$stmt = \$this->pdo->prepare(\$sql);\n        \$data = \$dto->toArray();\n        unset(\$data['{$primaryKey}']);\n\n        \$stmt->execute(\$data);\n        return \$this->pdo->lastInsertId();\n    }\n\n    public function update({$dtoClassName} \$dto): bool\n    {\n        \$sql = 'UPDATE `{$tableName}` SET {$updateSqlSet} WHERE `{$primaryKey}` = :{$primaryKey}';\n        \$stmt = \$this->pdo->prepare(\$sql);\n        return \$stmt->execute(\$dto->toArray());\n    }\n\n    public function delete(int \$id): bool\n    {\n        \$stmt = \$this->pdo->prepare('DELETE FROM `{$tableName}` WHERE `{$primaryKey}` = ?');\n        return \$stmt->execute([\$id]);\n    }\n}\n";
    }
}
