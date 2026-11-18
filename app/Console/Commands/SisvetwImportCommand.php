<?php

namespace App\Console\Commands;

use App\Services\SisvetwImportService;
use Illuminate\Console\Command;

class SisvetwImportCommand extends Command
{
    protected $signature = 'sisvetw:import 
                            {--path=C:\SISVETW : Caminho da pasta contendo os arquivos DBF e FPT}
                            {--fresh : Limpa e zera os dados fictícios antes da importação}
                            {--dry-run : Apenas simula a importação sem gravar no banco de dados}';

    protected $description = 'Importa com segurança a base de dados do sistema antigo SISVETW (DBF/FPT) para o Amigo Bicho';

    public function handle()
    {
        $path = $this->option('path');
        $fresh = $this->option('fresh');
        $dryRun = $this->option('dry-run');

        $this->info("==================================================================");
        $this->info("   IMPORTADOR DE DADOS LEGADOS: SISVETW -> AMIGO BICHO");
        $this->info("==================================================================");
        $this->line("Diretório de Origem: <comment>{$path}</comment>");
        $this->line("Modo Fresh: " . ($fresh ? '<fg=red>SIM (apagar dados fictícios prévios)</>' : '<fg=yellow>NÃO</>'));
        $this->line("Modo Simulação: " . ($dryRun ? '<fg=cyan>SIM (Dry-Run)</>' : '<fg=green>NÃO (Gravação Real)</>'));
        $this->newLine();

        if (!is_dir($path)) {
            $this->error("O diretório informado [{$path}] não existe!");
            return 1;
        }

        $service = new SisvetwImportService($path);

        if ($dryRun) {
            $this->warn("MODO SIMULAÇÃO ATIVADO: Nenhum registro será alterado no banco.");
        }

        // 1. Limpeza de dados fictícios
        if ($fresh && !$dryRun) {
            $this->info("🧹 Limpando dados fictícios e tabelas transacionais...");
            $cleared = $service->clearExistingData();
            foreach ($cleared as $table => $c) {
                if ($c > 0) {
                    $this->line("  - Tabela [{$table}]: {$c} registros fictícios removidos.");
                }
            }
            $this->info("✓ Dados fictícios limpos com sucesso!");
            $this->newLine();
        }

        // 2. Carrega tabelas de apoio
        $this->info("📋 Carregando tabelas de apoio (espécies, raças, vacinas)...");
        $service->loadAuxiliaryTables();
        $this->info("✓ Tabelas de apoio carregadas!");
        $this->newLine();

        if ($dryRun) {
            $this->info("Simulação concluída com sucesso.");
            return 0;
        }

        // 3. Fornecedores
        $this->info("🏭 Importando Fornecedores (DDFORN01)...");
        $suppliersCount = $service->importSuppliers();
        $this->line("  ✓ {$suppliersCount} fornecedores importados.");

        // 4. Produtos e Estoque
        $this->info("📦 Importando Produtos e Estoque (DDPROD01)...");
        $productsCount = $service->importProducts();
        $this->line("  ✓ {$productsCount} produtos/medicamentos importados.");

        // 5. Tutores / Clientes
        $this->info("👥 Importando Tutores / Proprietários (DDPROP01)...");
        $tutorsCount = $service->importTutors();
        $this->line("  ✓ {$tutorsCount} tutores importados.");

        // 6. Animais / Pets e Prontuários
        $this->info("🐾 Importando Animais e Prontuários Clínicos (DDFICH01 + FPT)...");
        $petsResult = $service->importPets();
        $this->line("  ✓ {$petsResult['pets']} animais/pacientes importados.");
        $this->line("  ✓ {$petsResult['medical_records']} históricos clínicos/prontuários consolidados.");

        // 7. Vacinações
        $this->info("💉 Importando Histórico de Vacinações (DDFI_V01)...");
        $vaccinesCount = $service->importVaccinations();
        $this->line("  ✓ {$vaccinesCount} aplicações de vacinas importadas.");

        // 8. Histórico de Pesagens
        $this->info("⚖️ Importando Histórico de Pesagens (DDPESO01)...");
        $weightsCount = $service->importWeights();
        $this->line("  ✓ {$weightsCount} registros de pesagens importados.");

        // 9. Agendamentos
        $this->info("📅 Importando Histórico de Agendamentos / Banho & Tosa (DDAGEN02)...");
        $appointmentsCount = $service->importAppointments(5000);
        $this->line("  ✓ {$appointmentsCount} agendamentos importados.");

        $this->newLine();
        $this->info("==================================================================");
        $this->info("   RESUMO FINAL DA IMPORTAÇÃO COMPLETA");
        $this->info("==================================================================");
        $this->table(
            ['Módulo / Entidade', 'Total Importado'],
            [
                ['Fornecedores', $suppliersCount],
                ['Produtos e Estoque', $productsCount],
                ['Tutores / Clientes', $tutorsCount],
                ['Animais / Pacientes', $petsResult['pets']],
                ['Históricos Clínicos (Prontuários)', $petsResult['medical_records']],
                ['Aplicações de Vacinas', $vaccinesCount],
                ['Históricos de Peso', $weightsCount],
                ['Agendamentos / Atendimentos', $appointmentsCount],
            ]
        );

        $this->info("🎉 Importação finalizada com sucesso absoluto e integridade total!");
        return 0;
    }
}
