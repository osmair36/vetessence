<?php

namespace App\Console\Commands;

use App\Services\Backup\BackupService;
use Illuminate\Console\Command;

class DatabaseBackupCleanup extends Command
{
    protected $signature = 'backup:cleanup {--keep= : Quantidade de dias de retenção}';
    protected $description = 'Remover backups antigos conforme política de retenção';

    public function handle(BackupService $backupService): int
    {
        $keep = $this->option('keep') !== null ? (int) $this->option('keep') : null;
        $deleted = $backupService->cleanupOldBackups($keep);

        $this->info("Rotina de limpeza concluída: {$deleted} backup(s) antigo(s) removido(s).");
        return 0;
    }
}
