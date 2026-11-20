<?php

declare(strict_types=1);

@ini_set('memory_limit', '512M');

$baseDir = realpath(__DIR__ . '/..');
if ($baseDir === false) {
    fwrite(STDERR, "[ERRO] Nao foi possivel localizar a pasta raiz da aplicacao.\n");
    exit(1);
}

$port = 8181;
$mode = 'prepare';
$isInstall = false;

foreach (array_slice($argv, 1) as $arg) {
    if ($arg === '--prepare') {
        $mode = 'prepare';
    } elseif ($arg === '--check-port') {
        $mode = 'check-port';
    } elseif (str_starts_with($arg, '--check-port=')) {
        $mode = 'check-port';
        $port = max(1, (int) substr($arg, strlen('--check-port=')));
    } elseif ($arg === '--open-browser') {
        $mode = 'open-browser';
    } elseif ($arg === '--install') {
        $isInstall = true;
    } elseif (str_starts_with($arg, '--port=')) {
        $port = max(1, (int) substr($arg, strlen('--port=')));
    }
}

if ($mode === 'check-port') {
    exit(isPortOpen('127.0.0.1', $port, 0.35) ? 0 : 1);
}

if ($mode === 'open-browser') {
    $url = "http://localhost:{$port}";
    // Aguarda o servidor responder antes de abrir o navegador (ate 4 segundos)
    for ($i = 0; $i < 20; $i++) {
        if (isPortOpen('127.0.0.1', $port, 0.2)) {
            break;
        }
        usleep(200000);
    }
    if (DIRECTORY_SEPARATOR === '\\') {
        pclose(popen('start "" "' . $url . '"', 'r'));
    }
    exit(0);
}

// =========================================================================
// MODO: PREPARE (Executado pelo instalar.bat e pelo iniciar.bat)
// =========================================================================

// 1. Garantir diretorios essenciais
$dirs = [
    'storage',
    'storage/app',
    'storage/app/backups',
    'storage/app/public',
    'storage/framework',
    'storage/framework/cache',
    'storage/framework/cache/data',
    'storage/framework/sessions',
    'storage/framework/views',
    'storage/logs',
    'bootstrap/cache',
    'database',
];

foreach ($dirs as $rel) {
    $full = $baseDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    if (!is_dir($full)) {
        @mkdir($full, 0775, true);
    }
}

// 2. Remover caches compilados antigos que possam ter caminhos de outra maquina
foreach (['config.php', 'routes-v7.php', 'events.php'] as $cacheFile) {
    $path = $baseDir . DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . $cacheFile;
    if (is_file($path)) {
        @unlink($path);
    }
}

// 3. Garantir arquivo .env valido com SQLite e APP_KEY
$envFile = $baseDir . DIRECTORY_SEPARATOR . '.env';
$envExample = $baseDir . DIRECTORY_SEPARATOR . '.env.example';

if (!is_file($envFile)) {
    if (is_file($envExample)) {
        @copy($envExample, $envFile);
        echo "[OK] Arquivo .env criado a partir do modelo padrao.\n";
    } else {
        $minimalEnv = implode("\r\n", [
            'APP_NAME="Amigo Bicho - Clinica Veterinaria e Pet Shop"',
            'APP_ENV=local',
            'APP_KEY=',
            'APP_DEBUG=false',
            "APP_URL=http://localhost:{$port}",
            'DB_CONNECTION=sqlite',
            'DB_DATABASE=database/database.sqlite',
            'SESSION_DRIVER=file',
            'CACHE_DRIVER=file',
            'QUEUE_CONNECTION=sync',
            '',
        ]);
        @file_put_contents($envFile, $minimalEnv);
        echo "[OK] Arquivo .env minimo criado.\n";
    }
}

if (is_file($envFile)) {
    $envContent = (string) @file_get_contents($envFile);
    $originalEnv = $envContent;

    // Forcar DB_CONNECTION=sqlite
    $envContent = upsertEnvVar($envContent, 'DB_CONNECTION', 'sqlite');
    $envContent = upsertEnvVar($envContent, 'DB_DATABASE', 'database/database.sqlite');

    // Garantir APP_KEY preenchida
    if (!preg_match('/^APP_KEY=base64:[A-Za-z0-9+\/=]{20,}/m', $envContent)) {
        $newKey = 'base64:' . base64_encode(random_bytes(32));
        $envContent = upsertEnvVar($envContent, 'APP_KEY', $newKey);
        echo "[OK] Chave criptografica (APP_KEY) gerada automaticamente.\n";
    }

    // Atualizar APP_URL se estiver vazia ou apontando para porta antiga 8000 / sem porta
    if (preg_match('/^APP_URL=(.*)$/m', $envContent, $m)) {
        $currentUrl = trim($m[1]);
        if ($currentUrl === '' || $currentUrl === 'http://localhost' || $currentUrl === 'http://127.0.0.1:8000') {
            $envContent = upsertEnvVar($envContent, 'APP_URL', "http://localhost:{$port}");
        }
    } else {
        $envContent = upsertEnvVar($envContent, 'APP_URL', "http://localhost:{$port}");
    }

    if ($envContent !== $originalEnv) {
        if (@file_put_contents($envFile, $envContent) === false) {
            fwrite(STDERR, "[AVISO] Nao foi possivel atualizar o arquivo .env (verifique permissoes de escrita).\n");
        }
    }
}

// 4. Validar banco de dados SQLite (rapido via PDO; so executa artisan se realmente necessario)
$dbFile = $baseDir . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'database.sqlite';
$needsFullInit = false;
$needsMigrate = false;
$needsDeptSeed = false;
$needsBreedSeed = false;

if (!is_file($dbFile) || filesize($dbFile) === 0) {
    $needsFullInit = true;
} else {
    try {
        $pdo = new PDO('sqlite:' . $dbFile);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
        $tableSet = array_flip($tables ?: []);

        if (!isset($tableSet['users']) || !isset($tableSet['migrations'])) {
            $needsFullInit = true;
        } else {
            $userCount = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
            if ($userCount === 0) {
                $needsFullInit = true;
            }

            // Verificar se ha alguma migration nova pendente
            $applied = $pdo->query('SELECT migration FROM migrations')->fetchAll(PDO::FETCH_COLUMN);
            $appliedSet = array_flip($applied ?: []);
            $migrationFiles = glob($baseDir . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'migrations' . DIRECTORY_SEPARATOR . '*.php') ?: [];
            foreach ($migrationFiles as $mf) {
                $name = basename($mf, '.php');
                if (!isset($appliedSet[$name])) {
                    $needsMigrate = true;
                    break;
                }
            }

            if (isset($tableSet['departments'])) {
                if ((int) $pdo->query('SELECT COUNT(*) FROM departments')->fetchColumn() === 0) {
                    $needsDeptSeed = true;
                }
            }
            if (isset($tableSet['breed_defaults'])) {
                if ((int) $pdo->query('SELECT COUNT(*) FROM breed_defaults')->fetchColumn() === 0) {
                    $needsBreedSeed = true;
                }
            }
        }
        $pdo = null;
    } catch (Throwable $e) {
        fwrite(STDERR, "[AVISO] Erro ao inspecionar SQLite: {$e->getMessage()}\n");
        $needsMigrate = true;
    }
}

if ($needsFullInit) {
    echo "[INFO] Inicializando banco de dados local pela primeira vez...\n";
    if (!is_file($dbFile)) {
        @touch($dbFile);
    }
    runArtisan($baseDir, ['migrate', '--force']);
    runArtisan($baseDir, ['db:seed', '--force']);
    echo "[OK] Banco de dados inicializado com sucesso.\n";
} else {
    if ($needsMigrate) {
        echo "[INFO] Aplicando atualizacoes pendentes na base de dados...\n";
        runArtisan($baseDir, ['migrate', '--force']);
        echo "[OK] Atualizacoes de banco aplicadas.\n";
    }
    if ($needsDeptSeed) {
        runArtisan($baseDir, ['db:seed', '--class=Database\\Seeders\\DepartmentPositionSeeder', '--force'], true);
    }
    if ($needsBreedSeed) {
        runArtisan($baseDir, ['db:seed', '--class=Database\\Seeders\\BreedDefaultSeeder', '--force'], true);
    }
    echo "[OK] Base de dados SQLite validada.\n";
}

// 5. Garantir storage:link na instalacao
if ($isInstall) {
    $publicStorage = $baseDir . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'storage';
    if (!file_exists($publicStorage)) {
        runArtisan($baseDir, ['storage:link'], true);
    }
}

// 6. Detectar IP real da rede local e gerar server_info.txt e atalho .url
$serverIp = detectLanIp();
$computerName = getenv('COMPUTERNAME') ?: gethostname() ?: 'SERVIDOR';
$now = date('d/m/Y H:i:s');

$serverInfo = implode("\r\n", [
    "SERVIDOR_NOME={$computerName}",
    "SERVIDOR_IP={$serverIp}",
    "PORTA={$port}",
    "URL_LOCAL=http://localhost:{$port}",
    "URL_REDE=http://{$serverIp}:{$port}",
    "URL_REDE_HOSTNAME=http://{$computerName}:{$port}",
    "ULTIMA_INICIALIZACAO={$now}",
    '',
]);
@file_put_contents($baseDir . DIRECTORY_SEPARATOR . 'server_info.txt', $serverInfo);

$faviconPath = $baseDir . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'amigo-bicho.ico';
$urlShortcut = implode("\r\n", [
    '[InternetShortcut]',
    "URL=http://{$serverIp}:{$port}",
    'IconIndex=0',
    "IconFile={$faviconPath}",
    '',
]);
@file_put_contents($baseDir . DIRECTORY_SEPARATOR . 'Amigo Bicho - Terminal.url', $urlShortcut);

if (!$isInstall) {
    echo "\n";
    echo " ========================================================================\n";
    echo "   SERVIDOR AMIGO BICHO PRONTO PARA EXECUCAO!\n";
    echo " ========================================================================\n";
    echo "\n";
    echo "   [Acesso neste Computador - Servidor]:\n";
    echo "   >> http://localhost:{$port}\n";
    echo "\n";
    echo "   [Acesso nos Terminais em Rede - Outros Computadores]:\n";
    echo "   >> http://{$serverIp}:{$port}\n";
    echo "   >> http://{$computerName}:{$port}\n";
    echo "\n";
    echo " ========================================================================\n";
    echo "   COMO CONECTAR OS TERMINAIS EM REDE:\n";
    echo "   1. Compartilhe esta pasta na rede local.\n";
    echo "   2. No Terminal, acesse: \\\\{$computerName}\\Amigo Bicho\n";
    echo "   3. Execute: Conectar ao Amigo Bicho (Terminal).bat\n";
    echo " ========================================================================\n";
    echo "\n";
    echo "   Mantenha esta janela aberta enquanto a clinica estiver funcionando.\n";
    echo "   Para encerrar o sistema, basta fechar esta janela.\n";
    echo "\n";
    echo " ========================================================================\n";
    echo "\n";
}

exit(0);

// =========================================================================
// FUNCOES AUXILIARES
// =========================================================================

function isPortOpen(string $host, int $port, float $timeout = 0.35): bool
{
    $conn = @fsockopen($host, $port, $errno, $errstr, $timeout);
    if (is_resource($conn)) {
        fclose($conn);
        return true;
    }
    return false;
}

function upsertEnvVar(string $content, string $key, string $value): string
{
    $pattern = '/^' . preg_quote($key, '/') . '=.*$/m';
    if (preg_match($pattern, $content)) {
        return (string) preg_replace($pattern, $key . '=' . $value, $content, 1);
    }
    $eol = str_contains($content, "\r\n") ? "\r\n" : "\n";
    return rtrim($content, "\r\n") . $eol . $key . '=' . $value . $eol;
}

function runArtisan(string $baseDir, array $args, bool $quiet = false): int
{
    $command = array_merge(
        [PHP_BINARY, '-d', 'memory_limit=512M', $baseDir . DIRECTORY_SEPARATOR . 'artisan'],
        $args
    );

    $nullDev = (DIRECTORY_SEPARATOR === '\\') ? 'NUL' : '/dev/null';
    $descriptors = $quiet
        ? [0 => ['file', $nullDev, 'r'], 1 => ['file', $nullDev, 'w'], 2 => ['file', $nullDev, 'w']]
        : [0 => STDIN, 1 => STDOUT, 2 => STDERR];

    $process = @proc_open($command, $descriptors, $pipes, $baseDir);
    if (!is_resource($process)) {
        return 1;
    }

    return proc_close($process);
}

function detectLanIp(): string
{
    $candidates = [];

    $output = @shell_exec('ipconfig');
    if (is_string($output) && $output !== '') {
        // Divide por adaptador para priorizar adaptadores reais com Gateway Padrao
        $sections = preg_split('/\r?\n(?=\S)/', $output) ?: [$output];
        foreach ($sections as $section) {
            $isVirtual = (bool) preg_match('/vEthernet|WSL|Hyper-V|VirtualBox|VMware|Loopback/i', $section);
            $hasGateway = (bool) preg_match('/Gateway[^:]*:\s*\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/i', $section);

            if (preg_match_all('/IPv4[^:]*:\s*(\d{1,3}(?:\.\d{1,3}){3})/i', $section, $matches)) {
                foreach ($matches[1] as $ip) {
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                        $candidates[$ip] = min(
                            $candidates[$ip] ?? 99,
                            ipPriority($ip, $hasGateway, $isVirtual)
                        );
                    }
                }
            }
        }
    }

    $hostIp = @gethostbyname((string) gethostname());
    if (is_string($hostIp) && filter_var($hostIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        $candidates[$hostIp] = min($candidates[$hostIp] ?? 99, ipPriority($hostIp, false, false));
    }

    foreach (array_keys($candidates) as $ip) {
        if (str_starts_with($ip, '127.') || str_starts_with($ip, '169.254.') || $ip === '0.0.0.0') {
            unset($candidates[$ip]);
        }
    }

    if ($candidates === []) {
        return '127.0.0.1';
    }

    asort($candidates, SORT_NUMERIC);
    return (string) array_key_first($candidates);
}

function ipPriority(string $ip, bool $hasGateway, bool $isVirtual): int
{
    $score = 10;
    if (str_starts_with($ip, '192.168.')) {
        $score = 1;
    } elseif (str_starts_with($ip, '10.')) {
        $score = 2;
    } elseif (preg_match('/^172\.(1[6-9]|2\d|3[01])\./', $ip)) {
        $score = 3;
    }

    if ($hasGateway) {
        $score -= 10;
    }
    if ($isVirtual) {
        $score += 20;
    }
    return $score;
}
