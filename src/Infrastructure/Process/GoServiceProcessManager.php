<?php

declare(strict_types=1);

namespace App\Infrastructure\Process;

use RuntimeException;
use InvalidArgumentException;

final class GoServiceProcessManager
{
    private string $serviceName;
    private string $workingDirectory;
    private string $command;
    private string $pidFilePath;
    private string $logFilePath;

    /**
     * @param string $serviceName Identificador único del servicio (ej. 'ragserver')
     * @param string $workingDirectory Ruta absoluta donde reside el main.go y go.mod
     * @param string $storageDirectory Ruta donde se guardarán los archivos .pid y .log
     * @param string $command Comando a ejecutar (por defecto 'go run .')
     */
    public function __construct(
        string $serviceName,
        string $workingDirectory,
        string $storageDirectory,
        string $command = 'go run .'
    ) {
        if (!is_dir($workingDirectory)) {
            throw new InvalidArgumentException("El directorio de trabajo no existe: {$workingDirectory}");
        }

        if (!is_dir($storageDirectory) && !mkdir($storageDirectory, 0755, true) && !is_dir($storageDirectory)) {
            throw new RuntimeException("No se pudo crear el directorio de almacenamiento: {$storageDirectory}");
        }

        $this->serviceName = preg_replace('/[^a-zA-Z0-9_-]/', '', $serviceName);
        $this->workingDirectory = rtrim($workingDirectory, DIRECTORY_SEPARATOR);
        $this->command = $command;

        $storageBase = rtrim($storageDirectory, DIRECTORY_SEPARATOR);
        $this->pidFilePath = "{$storageBase}" . DIRECTORY_SEPARATOR . "{$this->serviceName}.pid";
        $this->logFilePath = "{$storageBase}" . DIRECTORY_SEPARATOR . "{$this->serviceName}.log";
    }

    /**
     * Inicia el proceso en segundo plano de forma no bloqueante.
     *
     * @return int El PID del proceso iniciado.
     * @throws RuntimeException Si el servicio ya está corriendo o falla el arranque.
     */
    public function start(): int
    {
        if ($this->isRunning()) {
            $currentPid = $this->getPid();
            throw new RuntimeException("El servicio '{$this->serviceName}' ya se encuentra en ejecución (PID: {$currentPid}).");
        }

        // Limpiamos residuos de un cierre inesperado previo
        $this->cleanupPidFile();

        $pid = $this->isWindows()
            ? $this->spawnWindowsProcess()
            : $this->spawnUnixProcess();

        if ($pid <= 0) {
            throw new RuntimeException("Fallo crítico al intentar obtener el PID del proceso '{$this->serviceName}'.");
        }

        file_put_contents($this->pidFilePath, (string) $pid, LOCK_EX);

        return $pid;
    }

    /**
     * Detiene el proceso y todo su árbol de subprocesos hijos (vital para `go run`).
     *
     * @return bool True si se detuvo correctamente o ya no estaba corriendo.
     * @throws RuntimeException Si no se logra terminar el proceso.
     */
    public function stop(): bool
    {
        $pid = $this->getPid();

        if ($pid === null) {
            return true;
        }

        if (!$this->isProcessAlive($pid)) {
            $this->cleanupPidFile();
            return true;
        }

        $stopped = $this->isWindows()
            ? $this->killWindowsProcessTree($pid)
            : $this->killUnixProcessTree($pid);

        if (!$stopped && $this->isProcessAlive($pid)) {
            throw new RuntimeException("No se pudo detener el proceso '{$this->serviceName}' (PID: {$pid}).");
        }

        $this->cleanupPidFile();

        return true;
    }

    /**
     * Reinicia el servicio de forma segura.
     */
    public function restart(): int
    {
        $this->stop();
        usleep(300000); // 300ms de margen para liberar puertos TCP
        return $this->start();
    }

    /**
     * Verifica si el servicio está actualmente activo en el sistema operativo.
     */
    public function isRunning(): bool
    {
        $pid = $this->getPid();

        if ($pid === null) {
            return false;
        }

        if (!$this->isProcessAlive($pid)) {
            $this->cleanupPidFile();
            return false;
        }

        return true;
    }

    /**
     * Obtiene el PID almacenado o null si no existe.
     */
    public function getPid(): ?int
    {
        if (!file_exists($this->pidFilePath)) {
            return null;
        }

        $content = trim((string) file_get_contents($this->pidFilePath));
        return is_numeric($content) ? (int) $content : null;
    }

    /**
     * Lee las últimas N líneas del log generado por Go (stdout y stderr).
     */
    public function getLogs(int $lines = 50): string
    {
        if (!file_exists($this->logFilePath)) {
            return '';
        }

        $fileContent = file($this->logFilePath, FILE_IGNORE_NEW_LINES);
        if ($fileContent === false) {
            return '';
        }

        $slice = array_slice($fileContent, -$lines);
        return implode(PHP_EOL, $slice);
    }

    /**
     * Lanza el proceso desacoplado en Windows usando PowerShell para capturar el PID exacto.
     */
    private function spawnWindowsProcess(): int
    {
        $escapedWorkDir = escapeshellarg($this->workingDirectory);
        $escapedLog = escapeshellarg($this->logFilePath);
        
        // Usamos cmd /c con redirección dentro de Start-Process oculto para capturar stdout/stderr y retornar el PID real
        $psCommand = sprintf(
            'powershell -NoProfile -Command "$p = Start-Process -FilePath \'cmd.exe\' -ArgumentList \'/c %s > %s 2>&1\' -WorkingDirectory %s -WindowStyle Hidden -PassThru; Write-Output $p.Id"',
            str_replace("'", "''", $this->command),
            str_replace("'", "''", $escapedLog),
            $escapedWorkDir
        );

        $output = shell_exec($psCommand);
        return is_string($output) ? (int) trim($output) : 0;
    }

    /**
     * Lanza el proceso desacoplado en entornos Unix/Linux/macOS.
     */
    private function spawnUnixProcess(): int
    {
        $escapedWorkDir = escapeshellarg($this->workingDirectory);
        $escapedLog = escapeshellarg($this->logFilePath);

        // setsid crea una nueva sesión de procesos para poder matar todo el grupo luego
        $fullCommand = sprintf(
            'cd %s && setsid %s > %s 2>&1 < /dev/null & echo $!',
            $escapedWorkDir,
            $this->command,
            $escapedLog
        );

        $output = shell_exec($fullCommand);
        return is_string($output) ? (int) trim($output) : 0;
    }

    /**
     * Mata el proceso y todos sus subprocesos en Windows (/T = Tree, /F = Force).
     */
    private function killWindowsProcessTree(int $pid): bool
    {
        exec("taskkill /F /T /PID {$pid} 2>&1", $output, $exitCode);
        return $exitCode === 0;
    }

    /**
     * Mata el grupo de procesos completo en Unix/Linux.
     */
    private function killUnixProcessTree(int $pid): bool
    {
        // El signo negativo antes del PID indica matar todo el Process Group (PGID)
        exec("kill -9 -{$pid} 2>&1", $output, $exitCode);
        if ($exitCode !== 0) {
            exec("kill -9 {$pid} 2>&1", $output, $exitCode);
        }
        return $exitCode === 0;
    }

    /**
     * Consulta al sistema operativo si el PID sigue vivo.
     */
    private function isProcessAlive(int $pid): bool
    {
        if ($this->isWindows()) {
            $output = shell_exec("tasklist /FI \"PID eq {$pid}\" /NH 2>NUL");
            return is_string($output) && str_contains($output, (string) $pid);
        }

        if (function_exists('posix_kill')) {
            return posix_kill($pid, 0);
        }

        exec("ps -p {$pid}", $output, $exitCode);
        return $exitCode === 0;
    }

    private function cleanupPidFile(): void
    {
        if (file_exists($this->pidFilePath)) {
            @unlink($this->pidFilePath);
        }
    }

    private function isWindows(): bool
    {
        return PHP_OS_FAMILY === 'Windows';
    }
}
