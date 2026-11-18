<?php

namespace App\Console\Commands;

use App\Services\Backup\BackupService;
use Illuminate\Console\Command;

class DatabaseRestore extends Command
{
    protected $signature = 'backup:restore {file : Nome ou caminho absoluto do arquivo de backup} {--no-snapshot : Não criar snapshot de segurança prévio}';
    protected $description = 'Restaurar a base de dados a partir de um backup existente';

    public function handle(BackupService $backupService): int
    {
        $file = $this->argument('file');
        $createSnapshot = !$this->option('no-snapshot');

        $this->warn("ATENÇÃO: A base de dados atual será substituída pelo conteúdo de {$file}.");
        if (!$this->confirm('Deseja prosseguir com a restauração?', false)) {
            $this->info('Restauração cancelada pelo usuário.');
            return 0;
        }

        $this->line('Iniciando restauração da base de dados...');

        $result = $backupService->restoreBackup($file, $createSnapshot);

        if ($result['success']) {
            $this->info($result['message']);
            return 0;
        }

        $this->error($result['message']);
        return 1;
    }
}
