<?php

namespace App\Console\Commands;

use App\Services\SisvetwVaccinationReimportService;
use Illuminate\Console\Command;

class SisvetwReimportVaccinationsCommand extends Command
{
    protected $signature = 'sisvetw:reimport-vaccinations
                            {--path=C:\SISVETW : Caminho da pasta contendo os arquivos DBF do SISVETW}
                            {--dry-run : Simula a reimportacao sem alterar o banco de dados}
                            {--rollback= : Caminho de um backup NDJSON para reverter a tabela vaccinations}';

    protected $description = 'Corrige e reimporta exclusivamente os dados de vacinacao do SISVETW preservando os demais modulos e lancamentos manuais';

    public function handle(): int
    {
        $path = (string) $this->option('path');
        $dryRun = (bool) $this->option('dry-run');
        $rollbackPath = $this->option('rollback');

        $service = new SisvetwVaccinationReimportService($path);

        if ($rollbackPath) {
            $this->warn("Iniciando reversao da tabela vaccinations a partir de: {$rollbackPath}");
            $restored = $service->rollbackFromNdjson((string) $rollbackPath);
            $this->info("Reversao concluida: {$restored} registros restaurados.");
            return 0;
        }

        if (!is_dir($path)) {
            $this->error("Diretorio SISVETW nao encontrado: {$path}");
            return 1;
        }

        $this->info('========================================================================');
        $this->info('  REIMPORTACAO EXCLUSIVA DE VACINACOES: SISVETW -> VETESSENSE');
        $this->info('========================================================================');
        $this->line("Origem: {$path}");
        $this->line('Modo: ' . ($dryRun ? 'SIMULACAO (Dry-Run)' : 'EXECUCAO REAL'));
        $this->newLine();

        $result = $service->execute($dryRun);
        $s = $result['stats'];

        $this->table(
            ['Indicador', 'Quantidade'],
            [
                ['Animais mapeados com seguranca (sisvetw_pet_maps)', $s['pets_mapped']],
                ['Animais sem correspondencia', $s['pets_unmatched']],
                ['Total ativo no DDFI_V01.DBF', $s['source_total_active']],
                ['Registros deletados (*) ignorados no DBF', $s['source_deleted_skipped']],
                ['Vacinas aplicadas a importar / corrigir', $s['applied_records_to_import']],
                ['  - Com proxima dose REAL obtida do SISVETW', $s['applied_with_real_next_date']],
                ['  - Sem proxima dose no SISVETW (next_date = NULL)', $s['applied_without_next_date']],
                ['  - Com campo Laboratorio preenchido', $s['applied_with_laboratory']],
                ['Doses futuras consolidadas como next_date', $s['scheduled_consolidated_as_next_date']],
                ['Doses futuras sem dose anterior (registradas em log)', $s['scheduled_future_without_prior_dose']],
                ['Doses passadas vazias nao aplicadas (ignoradas)', $s['scheduled_past_unapplied_discarded']],
                ['Ignorados: BF_FICHA vazia', $s['skipped_empty_ficha']],
                ['Ignorados: animal sem correspondencia', $s['skipped_unmatched_pet']],
                ['Ignorados: BF_DATA vazia', $s['skipped_empty_date']],
                ['Ignorados: BF_DATA com ano corrompido', $s['skipped_corrupt_date']],
                ['Ignorados: duplicatas exatas no DBF', $s['skipped_exact_duplicates']],
                ['Registros legados substituidos', $s['legacy_replaced_count']],
                ['Registros manuais da Vetessense preservados', $s['manual_preserved_count']],
            ]
        );

        $this->line("Log detalhado de inconsistencias: {$result['inconsistencies_log']}");
        $this->info($dryRun ? 'Simulacao concluida sem alterar dados.' : 'Reimportacao concluida com sucesso!');

        return 0;
    }
}
