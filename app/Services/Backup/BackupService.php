<?php

namespace App\Services\Backup;

use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class BackupService
{
    protected string $backupDir;

    public function __construct()
    {
        $this->backupDir = storage_path('app/backups');
        if (!is_dir($this->backupDir)) {
            @mkdir($this->backupDir, 0755, true);
        }
    }

    /**
     * Obter todas as configurações de rotina de backup.
     */
    public function getSettings(): array
    {
        return [
            'enabled' => Setting::get('backup.enabled', '1') === '1',
            'frequency' => Setting::get('backup.frequency', 'daily'),
            'time' => Setting::get('backup.time', '01:00'),
            'compress' => Setting::get('backup.compress', '1') === '1',
            'keep_days' => (int) Setting::get('backup.keep_days', 30),
            'custom_path' => Setting::get('backup.custom_path', ''),
        ];
    }

    /**
     * Salvar configurações de rotina de backup.
     */
    public function saveSettings(array $data): void
    {
        Setting::set('backup.enabled', !empty($data['enabled']) ? '1' : '0');
        Setting::set('backup.frequency', $data['frequency'] ?? 'daily');
        Setting::set('backup.time', $data['time'] ?? '01:00');
        Setting::set('backup.compress', !empty($data['compress']) ? '1' : '0');
        Setting::set('backup.keep_days', (string) max(1, (int) ($data['keep_days'] ?? 30)));
        Setting::set('backup.custom_path', trim($data['custom_path'] ?? ''));
    }

    /**
     * Testa se um diretório customizado é acessível e possui permissão de escrita.
     */
    public function testDestination(string $path): array
    {
        $path = trim($path);
        if (empty($path)) {
            return [
                'success' => false,
                'message' => 'O caminho de destino não pode estar vazio.',
            ];
        }

        if (!file_exists($path)) {
            $created = @mkdir($path, 0755, true);
            if (!$created && !is_dir($path)) {
                return [
                    'success' => false,
                    'message' => "O diretório informado não existe e não pôde ser criado: {$path}",
                ];
            }
        }

        if (!is_dir($path)) {
            return [
                'success' => false,
                'message' => "O caminho informado não é um diretório válido: {$path}",
            ];
        }

        $testFile = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'test_perm_' . uniqid() . '.tmp';
        $written = @file_put_contents($testFile, 'test');
        if ($written === false) {
            return [
                'success' => false,
                'message' => "Sem permissão de gravação no diretório: {$path}",
            ];
        }

        @unlink($testFile);

        return [
            'success' => true,
            'message' => "Destino validado com sucesso! Permissão de leitura e escrita confirmada em: {$path}",
        ];
    }

    /**
     * Executa o backup da base de dados.
     */
    public function createBackup(?bool $compress = null, ?string $customDestination = null, string $prefix = 'backup'): array
    {
        $settings = $this->getSettings();
        $compress = $compress ?? $settings['compress'];
        $customDestination = $customDestination ?? $settings['custom_path'];

        $timestamp = now()->format('Y-m-d_H-i-s');
        $connection = config('database.default', 'sqlite');
        $driver = config("database.connections.{$connection}.driver", 'sqlite');

        try {
            if ($driver === 'sqlite') {
                $result = $this->backupSqlite($timestamp, $prefix);
            } else {
                $result = $this->backupMysql($timestamp, $prefix);
            }

            if (!$result['success']) {
                return $result;
            }

            $sourcePath = $result['path'];
            $finalFilename = $result['filename'];

            // Compactação Gzip
            if ($compress) {
                $gzFilename = $finalFilename . '.gz';
                $gzPath = $this->backupDir . DIRECTORY_SEPARATOR . $gzFilename;

                $this->gzipFile($sourcePath, $gzPath);
                @unlink($sourcePath);

                $sourcePath = $gzPath;
                $finalFilename = $gzFilename;
            }

            $size = @filesize($sourcePath) ?: 0;

            // Cópia para o destino customizado, se configurado
            $destinationNote = '';
            if (!empty($customDestination)) {
                $test = $this->testDestination($customDestination);
                if ($test['success']) {
                    $targetPath = rtrim($customDestination, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $finalFilename;
                    if (@copy($sourcePath, $targetPath)) {
                        $destinationNote = " e copiado para destino externo ({$customDestination})";
                    } else {
                        $destinationNote = " (alerta: falha ao copiar para destino externo)";
                    }
                }
            }

            return [
                'success' => true,
                'filename' => $finalFilename,
                'path' => $sourcePath,
                'size' => $size,
                'formatted_size' => $this->formatBytes($size),
                'message' => "Backup {$finalFilename} criado com sucesso ({$this->formatBytes($size)}){$destinationNote}.",
            ];
        } catch (\Throwable $e) {
            Log::error('Falha ao gerar backup: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return [
                'success' => false,
                'message' => 'Erro ao gerar backup: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Backup SQLite utilizando VACUUM INTO ou cópia direta.
     */
    protected function backupSqlite(string $timestamp, string $prefix): array
    {
        $dbPath = config('database.connections.sqlite.database');
        if (!file_exists($dbPath) && file_exists(database_path('database.sqlite'))) {
            $dbPath = database_path('database.sqlite');
        }

        if (!file_exists($dbPath)) {
            return [
                'success' => false,
                'message' => "Arquivo do banco SQLite não encontrado em: {$dbPath}",
            ];
        }

        $filename = "{$prefix}-{$timestamp}.sqlite";
        $destPath = $this->backupDir . DIRECTORY_SEPARATOR . $filename;

        // Se estiver fora de transação, tenta VACUUM INTO para snapshot atômico consistente
        $copied = false;
        if (DB::transactionLevel() === 0) {
            try {
                @DB::statement("PRAGMA wal_checkpoint(TRUNCATE)");
                DB::statement("VACUUM INTO ?", [$destPath]);
                $copied = file_exists($destPath);
            } catch (\Throwable) {
                $copied = false;
            }
        }

        // Fallback seguro via cópia direta de arquivo
        if (!$copied) {
            if (!@copy($dbPath, $destPath)) {
                return [
                    'success' => false,
                    'message' => "Falha ao copiar banco SQLite para: {$destPath}",
                ];
            }
        }

        return [
            'success' => true,
            'filename' => $filename,
            'path' => $destPath,
        ];
    }

    /**
     * Backup MySQL utilizando mysqldump ou exportador nativo em PHP.
     */
    protected function backupMysql(string $timestamp, string $prefix): array
    {
        $filename = "{$prefix}-{$timestamp}.sql";
        $destPath = $this->backupDir . DIRECTORY_SEPARATOR . $filename;

        $db = config('database.connections.mysql');
        $host = $db['host'] ?? '127.0.0.1';
        $port = $db['port'] ?? '3306';
        $database = $db['database'] ?? '';
        $username = $db['username'] ?? '';
        $password = $db['password'] ?? '';

        // Tenta mysqldump caso disponível no sistema
        $mysqldumpPath = $this->findBinary('mysqldump');
        if ($mysqldumpPath) {
            $process = new Process([
                $mysqldumpPath,
                '-h', $host,
                '-P', (string) $port,
                '-u', $username,
                '-p' . $password,
                '--default-character-set=utf8mb4',
                '--single-transaction',
                '--quick',
                $database,
            ]);
            $process->setTimeout(300);
            $process->run();

            if ($process->isSuccessful()) {
                file_put_contents($destPath, $process->getOutput());
                return [
                    'success' => true,
                    'filename' => $filename,
                    'path' => $destPath,
                ];
            }
        }

        // Fallback nativo PHP para MySQL se mysqldump não estiver no PATH
        return $this->dumpMysqlViaPhp($destPath, $filename, $database);
    }

    /**
     * Dump nativo MySQL em PHP (garante backup 100% autônomo sem depender do PATH do SO).
     */
    protected function dumpMysqlViaPhp(string $destPath, string $filename, string $database): array
    {
        $handle = fopen($destPath, 'w');
        if (!$handle) {
            return ['success' => false, 'message' => "Não foi possível abrir o arquivo de destino: {$destPath}"];
        }

        fwrite($handle, "-- Backup Amigo Bicho - Database: {$database}\n");
        fwrite($handle, "-- Data: " . date('Y-m-d H:i:s') . "\n");
        fwrite($handle, "SET FOREIGN_KEY_CHECKS=0;\nSET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n\n");

        $tables = DB::select("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");
        $prop = 'Tables_in_' . $database;

        foreach ($tables as $t) {
            $table = $t->$prop ?? array_values((array) $t)[0];

            // Create table
            $create = DB::select("SHOW CREATE TABLE `{$table}`");
            $createSql = $create[0]->{'Create Table'} ?? '';
            fwrite($handle, "DROP TABLE IF EXISTS `{$table}`;\n");
            fwrite($handle, $createSql . ";\n\n");

            // Dump data em lotes
            DB::table($table)->orderByRaw('1')->chunk(500, function ($rows) use ($handle, $table) {
                if ($rows->isEmpty()) return;
                $columns = array_keys((array) $rows->first());
                $colsList = implode('`, `', $columns);

                fwrite($handle, "INSERT INTO `{$table}` (`{$colsList}`) VALUES\n");
                $firstRow = true;
                foreach ($rows as $row) {
                    if (!$firstRow) fwrite($handle, ",\n");
                    $vals = [];
                    foreach ((array) $row as $val) {
                        if (is_null($val)) {
                            $vals[] = 'NULL';
                        } elseif (is_numeric($val)) {
                            $vals[] = $val;
                        } else {
                            $vals[] = "'" . addslashes((string) $val) . "'";
                        }
                    }
                    fwrite($handle, "(" . implode(', ', $vals) . ")");
                    $firstRow = false;
                }
                fwrite($handle, ";\n\n");
            });
        }

        fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
        fclose($handle);

        return [
            'success' => true,
            'filename' => $filename,
            'path' => $destPath,
        ];
    }

    /**
     * Restaura a base de dados a partir de um arquivo de backup.
     */
    public function restoreBackup(string $filePathOrName, bool $createSnapshot = true): array
    {
        // Se foi passado apenas o nome do arquivo, resolve no diretório de backups
        $filePath = file_exists($filePathOrName)
            ? $filePathOrName
            : $this->backupDir . DIRECTORY_SEPARATOR . basename($filePathOrName);

        if (!file_exists($filePath)) {
            return [
                'success' => false,
                'message' => "Arquivo de backup não encontrado em: {$filePath}",
            ];
        }

        $snapshotNote = '';
        if ($createSnapshot) {
            $snapshot = $this->createBackup(true, null, 'pre_restore_safety');
            if ($snapshot['success']) {
                $snapshotNote = " Snapshot de segurança pré-restauração salvo como: {$snapshot['filename']}.";
            }
        }

        $connection = config('database.default', 'sqlite');
        $driver = config("database.connections.{$connection}.driver", 'sqlite');

        // Se for comprimido em GZIP, descomprime para arquivo temporário
        $isGz = str_ends_with(strtolower($filePath), '.gz');
        $workFile = $filePath;
        $tempDecompressed = null;

        if ($isGz) {
            $tempDecompressed = tempnam(sys_get_temp_dir(), 'restore_') . '.' . pathinfo(substr($filePath, 0, -3), PATHINFO_EXTENSION);
            $this->gunzipFile($filePath, $tempDecompressed);
            $workFile = $tempDecompressed;
        }

        try {
            if ($driver === 'sqlite') {
                $res = $this->restoreSqlite($workFile);
            } else {
                $res = $this->restoreMysql($workFile);
            }

            if ($tempDecompressed && file_exists($tempDecompressed)) {
                @unlink($tempDecompressed);
            }

            if ($res['success']) {
                $res['message'] .= $snapshotNote;
            }

            return $res;
        } catch (\Throwable $e) {
            if ($tempDecompressed && file_exists($tempDecompressed)) {
                @unlink($tempDecompressed);
            }

            Log::error('Erro ao restaurar banco de dados: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return [
                'success' => false,
                'message' => 'Falha durante o processo de restauração: ' . $e->getMessage() . $snapshotNote,
            ];
        }
    }

    /**
     * Restauração de base SQLite.
     */
    protected function restoreSqlite(string $filePath): array
    {
        // Valida se o arquivo é realmente um banco SQLite válido
        $header = @file_get_contents($filePath, false, null, 0, 16);
        if ($header !== "SQLite format 3\0") {
            return [
                'success' => false,
                'message' => 'O arquivo fornecido não é uma base de dados SQLite válida (cabeçalho corrompido ou formato incompatível).',
            ];
        }

        $targetDb = config('database.connections.sqlite.database');
        if (!file_exists($targetDb) && file_exists(database_path('database.sqlite'))) {
            $targetDb = database_path('database.sqlite');
        }

        // Desconecta o PDO para liberar travas de arquivo no Windows
        DB::disconnect();

        // Substitui o banco de dados atual
        if (!@copy($filePath, $targetDb)) {
            DB::reconnect();
            return [
                'success' => false,
                'message' => "Não foi possível sobrescrever o arquivo do banco SQLite em: {$targetDb}",
            ];
        }

        // Reconecta e executa integridade
        DB::reconnect();
        try {
            $integrity = DB::select('PRAGMA integrity_check');
            $status = $integrity[0]->integrity_check ?? 'ok';
            if ($status !== 'ok') {
                return [
                    'success' => false,
                    'message' => "O banco restaurado apresentou avisos de integridade: {$status}",
                ];
            }
        } catch (\Throwable) {
            // Ignora se pragma falhar
        }

        return [
            'success' => true,
            'message' => 'Base de dados restaurada com sucesso!',
        ];
    }

    /**
     * Restauração de base MySQL.
     */
    protected function restoreMysql(string $filePath): array
    {
        $db = config('database.connections.mysql');
        $host = $db['host'] ?? '127.0.0.1';
        $port = $db['port'] ?? '3306';
        $database = $db['database'] ?? '';
        $username = $db['username'] ?? '';
        $password = $db['password'] ?? '';

        $mysqlBinary = $this->findBinary('mysql');
        if ($mysqlBinary) {
            $cmd = [
                $mysqlBinary,
                '-h', $host,
                '-P', (string) $port,
                '-u', $username,
                '-p' . $password,
                '--default-character-set=utf8mb4',
                $database,
            ];
            $process = new Process($cmd);
            $process->setInput(fopen($filePath, 'r'));
            $process->setTimeout(600);
            $process->run();

            if ($process->isSuccessful()) {
                return ['success' => true, 'message' => 'Base de dados MySQL restaurada com sucesso via cliente MySQL!'];
            }
        }

        // Fallback nativo: executa comandos via PDO
        $sql = file_get_contents($filePath);
        if ($sql === false) {
            return ['success' => false, 'message' => 'Não foi possível ler o arquivo SQL de restauração.'];
        }

        DB::unprepared($sql);

        return [
            'success' => true,
            'message' => 'Base de dados MySQL restaurada com sucesso via execução de instruções SQL!',
        ];
    }

    /**
     * Lista todos os backups armazenados localmente.
     */
    public function listBackups(): Collection
    {
        $files = Storage::files('backups');

        return collect($files)->map(function ($file) {
            $name = basename($file);
            $fullPath = storage_path('app/' . $file);
            $size = @filesize($fullPath) ?: 0;
            $mtime = @filemtime($fullPath) ?: time();

            $type = 'manual';
            if (str_starts_with($name, 'pre_restore')) {
                $type = 'pre_restore';
            } elseif (str_contains($name, 'auto') || str_starts_with($name, 'backup-')) {
                $type = 'automatic';
            }

            return [
                'name' => $name,
                'path' => $file,
                'full_path' => $fullPath,
                'size' => $size,
                'formatted_size' => $this->formatBytes($size),
                'last_modified' => $mtime,
                'formatted_date' => Carbon::createFromTimestamp($mtime)->format('d/m/Y H:i:s'),
                'type' => $type,
                'is_compressed' => str_ends_with(strtolower($name), '.gz'),
            ];
        })->sortByDesc('last_modified')->values();
    }

    /**
     * Remove backups mais antigos que a quantidade de dias especificada.
     */
    public function cleanupOldBackups(?int $keepDays = null): int
    {
        $keepDays = $keepDays ?? (int) Setting::get('backup.keep_days', 30);
        $threshold = now()->subDays($keepDays)->timestamp;

        $backups = $this->listBackups();
        if ($backups->count() <= 1) {
            // Mantém pelo menos um backup no sistema
            return 0;
        }

        $deleted = 0;
        // Ignora o mais recente
        $candidates = $backups->slice(1);

        foreach ($candidates as $b) {
            if ($b['last_modified'] < $threshold) {
                Storage::delete($b['path']);
                $deleted++;
            }
        }

        return $deleted;
    }

    /**
     * Compacta um arquivo usando gzip.
     */
    protected function gzipFile(string $sourcePath, string $destPath): void
    {
        $src = fopen($sourcePath, 'rb');
        $dst = gzopen($destPath, 'wb9');

        while (!feof($src)) {
            gzwrite($dst, fread($src, 1024 * 512));
        }

        fclose($src);
        gzclose($dst);
    }

    /**
     * Descompacta um arquivo gzip.
     */
    protected function gunzipFile(string $sourcePath, string $destPath): void
    {
        $src = gzopen($sourcePath, 'rb');
        $dst = fopen($destPath, 'wb');

        while (!gzeof($src)) {
            fwrite($dst, gzread($src, 1024 * 512));
        }

        gzclose($src);
        fclose($dst);
    }

    /**
     * Localiza um executável no PATH do sistema.
     */
    protected function findBinary(string $binary): ?string
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $cmd = "where {$binary} 2>nul";
            $output = @shell_exec($cmd);
            if ($output) {
                $lines = explode("\n", trim($output));
                if (!empty($lines[0]) && file_exists(trim($lines[0]))) {
                    return trim($lines[0]);
                }
            }
        } else {
            $cmd = "which {$binary} 2>/dev/null";
            $output = @shell_exec($cmd);
            if ($output && file_exists(trim($output))) {
                return trim($output);
            }
        }

        return null;
    }

    /**
     * Formata bytes em representação humana legível.
     */
    public function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        }
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' B';
    }
}
