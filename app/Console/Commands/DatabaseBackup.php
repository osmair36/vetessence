<?php

namespace App\Console\Commands;

use App\Services\Backup\BackupService;
use Illuminate\Console\Command;

class DatabaseBackup extends Command
{
    protected $signature = 'backup:database {--compress : Comprimir backup com Gzip} {--destination= : Caminho de destino customizado para cópia}';
    protected $description = 'Criar backup do banco de dados (SQLite ou MySQL)';

    public function handle(BackupService $backupService): int
    {
        $compress = $this->option('compress') ? true : null;
        $destination = $this->option('destination') ?: null;

        $this->line('Iniciando rotina de backup da base de dados...');

        $result = $backupService->createBackup($compress, $destination);

        if ($result['success']) {
            $this->info($result['message']);
            return 0;
        }

        $this->error($result['message']);
        return 1;
    }
}
