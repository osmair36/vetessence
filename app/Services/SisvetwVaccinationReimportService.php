<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\User;
use App\Models\Vaccination;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SisvetwVaccinationReimportService
{
    protected string $basePath;
    protected int $branchId;
    protected int $defaultVetId;
    protected string $referenceToday;

    /** @var array<string, array{name:string, doses:int, period:int}> */
    protected array $vaccineCatalog = [];

    /** @var array<string, string> */
    protected array $protocolFamilies = [
        // V10 (Polivalente Canina)
        '001' => 'V10',
        '002' => 'V10',
        '003' => 'V10',
        // Gripe Canina
        '005' => 'GRIPE',
        '006' => 'GRIPE',
        '055' => 'GRIPE',
        '35'  => 'GRIPE',
        // Giardia
        '007' => 'GIARDIA',
        '008' => 'GIARDIA',
        // Felocel Quadrupla (V4 Felina)
        '009' => 'FELOCEL_V4',
        '010' => 'FELOCEL_V4',
        // Quintupla Felina (V5)
        '035' => 'QUINTUPLA_FELINA',
        '037' => 'QUINTUPLA_FELINA',
        // Felocell FeLV
        '013' => 'FELOCELL_FELV',
        '014' => 'FELOCELL_FELV',
        // Vermifugo
        '011' => 'VERMIFUGO',
        '032' => 'VERMIFUGO',
        '33'  => 'VERMIFUGO',
        '111' => 'VERMIFUGO',
        // Advocate
        '019' => 'ADVOCATE',
        '50'  => 'ADVOCATE',
        // Revolution
        '30'  => 'REVOLUTION',
        '033' => 'REVOLUTION',
    ];

    public function __construct(string $basePath = 'C:\\SISVETW', ?string $referenceToday = null)
    {
        @ini_set('memory_limit', '1024M');
        @set_time_limit(0);

        $this->basePath = rtrim($basePath, '\\/');
        $mainBranch = Branch::where('is_main', true)->first() ?? Branch::first();
        $this->branchId = $mainBranch ? (int) $mainBranch->id : 1;

        $vet = User::where('is_veterinarian', true)->first() ?? User::first();
        $this->defaultVetId = $vet ? (int) $vet->id : 1;

        $this->referenceToday = $referenceToday ?? date('Y-m-d');
    }

    /**
     * Executa diagnostico (dryRun=true) ou reimportacao real exclusiva de vacinacoes,
     * espelhando rigorosamente as datas reais de aplicacao e proximas doses do SISVETW.
     */
    public function execute(bool $dryRun = true): array
    {
        $this->loadVaccineCatalog();
        $petMappingResult = $this->buildPetMapping(!$dryRun);
        $fichaToPetId = $petMappingResult['map'];

        $dbfPath = $this->basePath . DIRECTORY_SEPARATOR . 'DDFI_V01.DBF';
        if (!file_exists($dbfPath)) {
            throw new \RuntimeException("Arquivo de vacinacoes nao encontrado: {$dbfPath}");
        }

        $stats = [
            'source_total_active' => 0,
            'source_deleted_skipped' => 0,
            'skipped_empty_ficha' => 0,
            'skipped_unmatched_pet' => 0,
            'skipped_empty_date' => 0,
            'skipped_corrupt_date' => 0,
            'skipped_exact_duplicates' => 0,
            'classified_applied' => 0,
            'classified_empty_scheduled' => 0,
            'classified_note_not_applied' => 0,
            'applied_records_to_import' => 0,
            'scheduled_only_records_to_import' => 0,
            'total_records_to_import' => 0,
            'applied_with_real_next_date' => 0,
            'applied_without_next_date' => 0,
            'applied_with_laboratory' => 0,
            'followup_completed' => 0,
            'followup_pending' => 0,
            'followup_none' => 0,
            'contact_notes_preserved' => 0,
            'legacy_replaced_count' => 0,
            'manual_preserved_count' => 0,
            'pets_mapped' => count($fichaToPetId),
            'pets_unmatched' => count($petMappingResult['unmatched']),
            'unknown_vaccine_codes' => [],
        ];

        $inconsistencies = [
            'generated_at' => now()->toDateTimeString(),
            'source_path' => $dbfPath,
            'unmatched_pets' => $petMappingResult['unmatched'],
            'empty_ficha_rows' => [],
            'unmatched_ficha_rows' => [],
            'empty_date_rows' => [],
            'corrupt_date_rows' => [],
            'duplicate_rows' => [],
            'scheduled_only_imported' => [],
            'future_applied_source_anomalies' => [],
            'unknown_vaccine_codes' => [],
        ];

        $seenDupKeys = [];
        $seriesByPetFamily = [];
        $deletedCount = 0;

        foreach ($this->streamDbf($dbfPath, $deletedCount) as $r) {
            $stats['source_total_active']++;

            $ficha = $r['BF_FICHA'];
            $rawDate = $r['BF_DATA'];
            $codVac = $r['BF_COD_VAC'];
            $lab = $r['BF_LABORAT'];
            $part = $r['BF_PARTIDA'];

            if ($codVac !== '' && !isset($this->vaccineCatalog[$codVac])) {
                $stats['unknown_vaccine_codes'][$codVac] = ($stats['unknown_vaccine_codes'][$codVac] ?? 0) + 1;
            }

            if ($ficha === '') {
                $stats['skipped_empty_ficha']++;
                $inconsistencies['empty_ficha_rows'][] = $r;
                continue;
            }

            if (!isset($fichaToPetId[$ficha])) {
                $stats['skipped_unmatched_pet']++;
                if (count($inconsistencies['unmatched_ficha_rows']) < 200) {
                    $inconsistencies['unmatched_ficha_rows'][] = $r;
                }
                continue;
            }

            if ($rawDate === '') {
                $stats['skipped_empty_date']++;
                $inconsistencies['empty_date_rows'][] = $r;
                continue;
            }

            $isoDate = $this->parseStrictDate($rawDate);
            if ($isoDate === null) {
                $stats['skipped_corrupt_date']++;
                $inconsistencies['corrupt_date_rows'][] = $r;
                continue;
            }

            $dupKey = "{$ficha}|{$isoDate}|{$codVac}|{$lab}|{$part}";
            if (isset($seenDupKeys[$dupKey])) {
                $stats['skipped_exact_duplicates']++;
                $inconsistencies['duplicate_rows'][] = $r;
                continue;
            }
            $seenDupKeys[$dupKey] = true;

            $petId = $fichaToPetId[$ficha];
            $family = $this->protocolFamilies[$codVac] ?? ('COD_' . $codVac);
            $seriesKey = "{$petId}|{$family}";

            $classification = $this->classifyDbfRow($lab, $part, $codVac);
            if ($classification === 'applied') {
                $stats['classified_applied']++;
            } elseif ($classification === 'empty') {
                $stats['classified_empty_scheduled']++;
            } else {
                $stats['classified_note_not_applied']++;
            }

            $r['_pet_id'] = $petId;
            $r['_iso_date'] = $isoDate;
            $r['_cls'] = $classification;
            $r['_is_applied'] = ($classification === 'applied');
            $seriesByPetFamily[$seriesKey][] = $r;
        }
        $stats['source_deleted_skipped'] = $deletedCount;
        $inconsistencies['unknown_vaccine_codes'] = $stats['unknown_vaccine_codes'];

        // Processa cada serie (pet, familia vacinal) espelhando rigorosamente as datas do SISVETW
        $recordsToInsert = [];
        $completionLinksByRecno = []; // [source_recno => completing_recno]
        $nowStr = now()->format('Y-m-d H:i:s');

        foreach ($seriesByPetFamily as $seriesRows) {
            usort($seriesRows, static function (array $a, array $b): int {
                return strcmp($a['_iso_date'], $b['_iso_date']) ?: ($a['_recno'] <=> $b['_recno']);
            });

            $n = count($seriesRows);
            $hasAnyApplied = false;
            foreach ($seriesRows as $sr) {
                if ($sr['_is_applied']) {
                    $hasAnyApplied = true;
                    break;
                }
            }

            // Caso 1: Serie com APENAS agendamentos vazios no SISVETW (nenhuma dose aplicada anterior)
            // Ex.: filhotes com 1a Raiva ou Vermifugo agendados para data futura/pendente no SISVETW
            if (!$hasAnyApplied) {
                $firstEmpty = null;
                foreach ($seriesRows as $sr) {
                    if ($sr['_cls'] === 'empty') {
                        $firstEmpty = $sr;
                        break;
                    }
                }
                if ($firstEmpty !== null) {
                    $codVac = $firstEmpty['BF_COD_VAC'];
                    $recordsToInsert[] = [
                        'pet_id' => $firstEmpty['_pet_id'],
                        'vet_id' => $this->defaultVetId,
                        'vaccine' => $this->resolveVaccineName($codVac),
                        'laboratory' => null,
                        'manufacturer' => null,
                        'batch' => null,
                        'lot' => null,
                        'dose' => '1ª dose (agendada)',
                        'status' => 'scheduled',
                        'followup_status' => Vaccination::FOLLOWUP_PENDING,
                        'completed_by_vaccination_id' => null,
                        'last_contact_at' => null,
                        'last_contact_notes' => null,
                        'date' => null,
                        'next_date' => $firstEmpty['_iso_date'],
                        'notes' => 'Agendamento importado fielmente do SISVETW (sem dose aplicada anterior).',
                        'reminder_sent' => 0,
                        'branch_id' => $this->branchId,
                        'sisvetw_recno' => $firstEmpty['_recno'],
                        'sisvetw_ficha' => $firstEmpty['BF_FICHA'],
                        'sisvetw_cod_vac' => $codVac !== '' ? $codVac : null,
                        'created_at' => $nowStr,
                        'updated_at' => $nowStr,
                    ];
                    $stats['scheduled_only_records_to_import']++;
                    $stats['followup_pending']++;
                    $inconsistencies['scheduled_only_imported'][] = [
                        'recno' => $firstEmpty['_recno'],
                        'ficha' => $firstEmpty['BF_FICHA'],
                        'pet_id' => $firstEmpty['_pet_id'],
                        'next_date' => $firstEmpty['_iso_date'],
                        'cod_vac' => $codVac,
                        'vaccine' => $this->resolveVaccineName($codVac),
                    ];
                }
                continue;
            }

            // Indice rapido por recno dentro da serie para localizar bloco contiguo do SISVETW (recno + 1)
            $byRecnoInSeries = [];
            foreach ($seriesRows as $idx => $sr) {
                $byRecnoInSeries[$sr['_recno']] = $idx;
            }

            $doseCounterByCod = [];

            for ($i = 0; $i < $n; $i++) {
                $row = $seriesRows[$i];
                if (!$row['_is_applied']) {
                    continue;
                }

                $isoDate = $row['_iso_date'];
                $codVac = $row['BF_COD_VAC'];
                $recno = $row['_recno'];

                if ($isoDate > $this->referenceToday) {
                    $inconsistencies['future_applied_source_anomalies'][] = [
                        'recno' => $recno,
                        'ficha' => $row['BF_FICHA'],
                        'pet_id' => $row['_pet_id'],
                        'date' => $isoDate,
                        'cod_vac' => $codVac,
                        'lab' => $row['BF_LABORAT'],
                        'part' => $row['BF_PARTIDA'],
                    ];
                }

                $doseCounterByCod[$codVac] = ($doseCounterByCod[$codVac] ?? 0) + 1;
                $doseNum = $doseCounterByCod[$codVac];

                // 1) Proxima dose APLICADA REAL na serie (estritamente posterior a $isoDate)
                $nextAppliedRow = null;
                for ($j = $i + 1; $j < $n; $j++) {
                    if ($seriesRows[$j]['_is_applied'] && $seriesRows[$j]['_iso_date'] > $isoDate) {
                        $nextAppliedRow = $seriesRows[$j];
                        break;
                    }
                }

                // 2) Proxima data REAL do SISVETW (3 niveis de prioridade estritamente baseados no DDFI_V01.DBF):
                //    Prioridade 1: Registro imediatamente seguinte no bloco fisico do SISVETW (recno + 1)
                //                  com mesmo BF_COD_VAC e data > $isoDate (evita colisao com notas antigas ou outro codigo).
                //    Prioridade 2: Primeiro registro cronologico posterior (date > $isoDate) com recno > $recno
                //                  (suporta transicoes filhote->adulto como 001->003, 007->008, 032->011).
                //    Prioridade 3: Primeiro registro cronologico posterior (date > $isoDate) da serie
                //                  (suporta lancamento manual antecipado sem apagar agendamento ja existente).
                $nextRow = null;

                if (isset($byRecnoInSeries[$recno + 1])) {
                    $cand = $seriesRows[$byRecnoInSeries[$recno + 1]];
                    if ($cand['BF_COD_VAC'] === $codVac && $cand['_iso_date'] > $isoDate) {
                        $nextRow = $cand;
                    }
                }

                if ($nextRow === null) {
                    for ($j = $i + 1; $j < $n; $j++) {
                        if ($seriesRows[$j]['_iso_date'] > $isoDate && $seriesRows[$j]['_recno'] > $recno) {
                            $nextRow = $seriesRows[$j];
                            break;
                        }
                    }
                }

                if ($nextRow === null) {
                    for ($j = $i + 1; $j < $n; $j++) {
                        if ($seriesRows[$j]['_iso_date'] > $isoDate) {
                            $nextRow = $seriesRows[$j];
                            break;
                        }
                    }
                }

                $nextDate = $nextRow ? $nextRow['_iso_date'] : null;

                // Determina followup_status e preserva anotacoes de contato (ex.: AVISADO VIA WHATS) do SISVETW
                $followupStatus = Vaccination::FOLLOWUP_NONE;
                $lastContactAt = null;
                $lastContactNotes = null;

                if ($nextDate !== null) {
                    $stats['applied_with_real_next_date']++;
                    if ($nextAppliedRow !== null) {
                        $followupStatus = Vaccination::FOLLOWUP_COMPLETED;
                        $stats['followup_completed']++;
                        $completionLinksByRecno[$recno] = $nextAppliedRow['_recno'];
                    } else {
                        $followupStatus = Vaccination::FOLLOWUP_PENDING;
                        $stats['followup_pending']++;
                    }

                    // Se o proximo registro era uma nota de aviso/contato no SISVETW, preserva sem criar vacina falsa
                    if ($nextRow !== null && $nextRow['_cls'] === 'note') {
                        $noteParts = array_filter([
                            trim($nextRow['BF_LABORAT']),
                            (trim($nextRow['BF_PARTIDA']) !== '' && !preg_match('/^[xX]+$/', trim($nextRow['BF_PARTIDA'])))
                                ? trim($nextRow['BF_PARTIDA'])
                                : '',
                        ]);
                        if (!empty($noteParts)) {
                            $brDate = substr($nextDate, 8, 2) . '/' . substr($nextDate, 5, 2) . '/' . substr($nextDate, 0, 4);
                            $lastContactNotes = "SISVETW ({$brDate}): " . implode(' — ', $noteParts);
                            $lastContactAt = $nextDate . ' 12:00:00';
                            $stats['contact_notes_preserved']++;
                        }
                    }
                } else {
                    $stats['applied_without_next_date']++;
                    $stats['followup_none']++;
                }

                $lab = $row['BF_LABORAT'] !== '' ? $row['BF_LABORAT'] : null;
                $lot = $row['BF_PARTIDA'] !== '' ? $row['BF_PARTIDA'] : null;

                if ($lab !== null) {
                    $stats['applied_with_laboratory']++;
                }

                $catalogInfo = $this->vaccineCatalog[$codVac] ?? null;
                $protocolDoses = $catalogInfo ? $catalogInfo['doses'] : 0;
                $doseLabel = ($protocolDoses === 1 && $doseNum === 1) ? 'Dose única' : "{$doseNum}ª dose";

                $recordsToInsert[] = [
                    'pet_id' => $row['_pet_id'],
                    'vet_id' => $this->defaultVetId,
                    'vaccine' => $this->resolveVaccineName($codVac),
                    'laboratory' => $lab,
                    'manufacturer' => $lab,
                    'batch' => $lot,
                    'lot' => $lot,
                    'dose' => $doseLabel,
                    'status' => 'applied',
                    'followup_status' => $followupStatus,
                    'completed_by_vaccination_id' => null,
                    'last_contact_at' => $lastContactAt,
                    'last_contact_notes' => $lastContactNotes,
                    'date' => $isoDate,
                    'next_date' => $nextDate,
                    'notes' => null,
                    'reminder_sent' => 0,
                    'branch_id' => $this->branchId,
                    'sisvetw_recno' => $recno,
                    'sisvetw_ficha' => $row['BF_FICHA'],
                    'sisvetw_cod_vac' => $codVac !== '' ? $codVac : null,
                    'created_at' => $nowStr,
                    'updated_at' => $nowStr,
                ];
                $stats['applied_records_to_import']++;
            }
        }

        $stats['total_records_to_import'] = count($recordsToInsert);

        // Identifica registros legados (substituiveis) vs registros manuais da Vetessense (preservados!)
        $legacyIds = $this->findLegacyVaccinationIds();
        $totalExisting = DB::table('vaccinations')->count();
        $stats['legacy_replaced_count'] = count($legacyIds);
        $stats['manual_preserved_count'] = $totalExisting - count($legacyIds);

        // Grava relatorio de auditoria/inconsistencias
        $logPath = storage_path('logs/sisvetw-vaccinations-inconsistencies.json');
        @file_put_contents($logPath, json_encode($inconsistencies, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        if (!$dryRun) {
            DB::transaction(function () use ($legacyIds, $recordsToInsert, $completionLinksByRecno) {
                // Remove exclusivamente os registros migrados do SISVETW, preservando 100% dos manuais
                if (!empty($legacyIds)) {
                    foreach (array_chunk($legacyIds, 500) as $chunk) {
                        DB::table('vaccinations')->whereIn('id', $chunk)->delete();
                    }
                }

                // Insere os registros corrigidos em lotes
                foreach (array_chunk($recordsToInsert, 500) as $chunk) {
                    DB::table('vaccinations')->insert($chunk);
                }

                // Vincula completed_by_vaccination_id entre doses concluidas e a dose real subsequente
                if (!empty($completionLinksByRecno)) {
                    $recnoToId = DB::table('vaccinations')
                        ->whereNotNull('sisvetw_recno')
                        ->pluck('id', 'sisvetw_recno')
                        ->all();

                    $updatesByTarget = [];
                    foreach ($completionLinksByRecno as $srcRecno => $dstRecno) {
                        if (isset($recnoToId[$srcRecno], $recnoToId[$dstRecno])) {
                            $updatesByTarget[(int) $recnoToId[$dstRecno]][] = (int) $recnoToId[$srcRecno];
                        }
                    }

                    foreach ($updatesByTarget as $targetId => $sourceIds) {
                        foreach (array_chunk($sourceIds, 500) as $chunk) {
                            DB::table('vaccinations')
                                ->whereIn('id', $chunk)
                                ->update(['completed_by_vaccination_id' => $targetId]);
                        }
                    }
                }
            });

            Cache::forget('vaccination_center_dashboard_stats');
        }

        return [
            'dry_run' => $dryRun,
            'stats' => $stats,
            'inconsistencies_log' => $logPath,
        ];
    }

    /**
     * Classifica rigorosamente uma linha do DDFI_V01.DBF em:
     *  - 'empty'   : BF_LABORAT e BF_PARTIDA vazios (agendamento puro do SISVETW)
     *  - 'applied' : vacinacao/procedimento efetivamente aplicado (clinica, casa ou outro local)
     *  - 'note'    : aviso/contato/recusa/anotacao administrativa (ex.: AVISADO VIA WHATS, X, NAO FEZ)
     */
    protected function classifyDbfRow(string $labRaw, string $partRaw, string $codVac = ''): string
    {
        $lab = $this->normalizeText($labRaw);
        $part = $this->normalizeText($partRaw);

        if ($lab === '' && $part === '') {
            return 'empty';
        }

        $both = trim($lab . ' ' . $part);

        // 1. Marcadores puros de descarte/rasura (X, XX, ., -, ?, :()
        if (preg_match('/^[X\.\-\?\!\*\/\:;\(\) ]+$/', $lab) && preg_match('/^[X\.\-\?\!\*\/\:;\(\) ]*$/', $part)) {
            return 'note';
        }

        // 2. Frases explicitas de NAO APLICACAO / AVISO / ADIADO / OBSERVACAO ADMINISTRATIVA
        $hardNotApplied = [
            'NAO FEZ', 'NAO QUIS', 'NAO QUER', 'NAO VEIO', 'NAO VAI FAZ', 'NAO IRA FAZ',
            'NAO FAZ', 'NAO APLIC', 'NAO TOMOU', 'NAO COMPAREC', 'NAO CONSEG', 'NAO PODE',
            'NAO TROUXE', 'NAO REALIZ', 'NAO RETORN', 'NAO DEU', 'NAO PERMIT', 'NAO DEIXA',
            'NAO VAI PEGAR', 'NAO VAI DAR', 'NAO APARECE', 'NAO ENCONTR', 'NAO ESTAVA BEM',
            'NAO TEM MAIS', 'NAO VAI POR ENQUANTO', 'NAO DEIXOU O', 'NAO DEIXOU FAZER',
            'DEIXOU A VACINA', 'AVISAR HJ', 'AVISAR ', 'PEDIU AVISAR', 'SEGURAR PARA FAZER',
            'ATRASOU ESQUEMA', 'ATRASOU O ESQUEMA', 'ATRASOU O PROTOCOLO', 'ATRASOU RECOMECAR',
            'ATRASO DA', 'EXPLICADO SOBRE ATRA', 'PROTOCOLO GRIPE E',
            'DIFICIL DE DAR', 'FOI BEM DIFICIL', 'TOMANDO ', 'JA TEM EM CASA', 'JA TEM DE TODOS',
            'TEM EM CASA', 'TEM MEIO', 'TEM METADE', 'TEM FRONT', 'JA TEM CASA', 'JA TEM COMPRIMIDO',
            'FAZ NA PROXIMA', 'FEITA EM ATRASO', 'DEIXAR PARA PROXIMO', 'DEIXAR PARA PROXIMA',
            'DEIXAR P PROXIMO', 'DEIXAR MAIS', 'VAI DEIXAR', 'VAI ESPERAR', 'ESPERAR',
            'PODE ESTAR PRENHA', 'ESTA DE CRIA', 'SO FAZ QUANDO', 'FICOU DE VER',
            'FICOU DE RESOLVER', 'FICOU TRAZER', 'FICOU MANDAR', 'IA PENSAR', 'VERIA DEPOIS',
            'IA VER QUANDO', 'IA FAZER', 'PASSA PEGAR', 'MARCAR JUNTO', 'LIBERAR DINHEIRO',
            'SO LEMBRAR', 'LEMBRAR NOVAMENTE', 'LEMBRAR DE TRAZER', 'RECOMENDO', 'RECOMENDADO',
            'VAI MANDAR FOTO', 'VAI TRAZER', 'CONFERIR SE FEZ', 'ESTA CIENTE', 'DONO FOI ORIENTADO',
            'FOI ORIENTAD', 'FOI CONVERSADO', 'JA FOI CONV', 'CONVERSADO', 'FOI FALADO',
            'FOI EXPLICADO', 'FOI OFERIDO', 'FOI OFERECIDO', 'OPTOU MANTER', 'QUER FAZER',
            'FAZER PROTOCOLO', 'FAZER TOPICO', 'FAZER MAIS VACINAS', 'MAIS VACINAS',
            'VACINAS POR ENQUANTO', 'ACHOU CARO', 'A CARTEIRINHA ESTA', 'CARTEIRINHA',
            'CARTEINHA ESTA', 'TAXA DE R$', 'ESTA COM CARRAPATO', 'INFESTADA DE CARRAPATO',
            'ANIMAL BRAVO', 'ANIMAL AGITADO', 'ANIMAL MORDE', 'VER NAS OBS', 'VER COM ',
            'A 1º FICHA', 'A 1ª FICHA', 'ESTA NO CIO', 'VEIO PARA CONSULTA',
            'VEIO PARA O BANHO', 'VEIO AO BANHO', 'TRATAMENTO DE SARNA',
            'DISSE QUE VI FAZER', 'AGENDAR DAQUI', 'ANOTADO NA AGEDA', 'ANOTADO NA AGENDA',
            'REPETIR HJ', 'LIGAMOS MAIS', 'NINGUEM ATENDEU', 'CONSULTA',
        ];
        foreach ($hardNotApplied as $h) {
            if (str_contains($both, $h)) {
                return 'note';
            }
        }

        // 3. Confirmacoes explicitas de aplicacao realizada (casa, outro local, domicilio, coleira, exame)
        $explicitApplied = [
            'FEZ EM CASA', 'FIZ EM CASA', 'JA FEZ', 'JAZ FEZ', 'FEZ FORA', 'FEZ OUTRA',
            'FEZ EM OUTRA', 'FEZ OUTRO', 'FEZ EM OUTRO', 'FEZ EM JESUITAS', 'FEZ NA ', 'FEZ NO ',
            'FEZ COM ', 'FEZ AUGUSTO', 'FEZ JESSICA', 'FEZ EM ', 'FEZ NACIONAL', 'FEZ VACINA',
            'FEZ AQUELE DIA', 'FEZ A 5 DIAS', 'FEZ UMA DOSE', 'FEZ A PRIMEIRA DOSE', 'FEZ ESQUEMA',
            'FEZ REVOLUTION', 'FEZ ANNITA', 'FAZ EM CASA', 'FAZ CONTROLE EM CAS', 'FAZ NA KATIA',
            'FAZ COM OUTRO', 'ESTA FAZENDO EM CASA', 'ESTA FAZENDO CONTROLE', 'FAZ EM FOZ',
            'FAZ EM MARINGA', 'ESTA VACINANDO EM', 'APLICOU EM CASA', 'APLICADO NOS DOIS',
            'APLICACAO DE NEPTRA', 'COLOCOU COLEIRA', 'DEU EM CASA', 'DEU 1/4 DO COMP',
            'FOI DADO UMA AMOSTRA', 'FOI DADO PREDIDERME', 'TOMOU EM CASA', 'VACINOU',
            'VACINADO EM', 'JA FOI VACINAD', 'JA FOI FEITO', 'JA FOI FEITA', 'FOI FEITO',
            'FOI FEITA', 'FEITO A DOMICILIO', 'FEITO EM OUTRO', 'QUE FEZ', 'AVISOU QUE FEZ',
            'MAYKON FEZ', 'IRMAO DELA FAZ', 'TINHA EM CASA FEZ', 'USOU GIARDICID',
            'USOU METADE', 'LEVOU FAZER EM CASA', 'LEVOU MY PET', 'DONO FEZ CONTROLE',
            'NEGATIVO', 'POSITIVO',
        ];
        foreach ($explicitApplied as $a) {
            if (str_contains($both, $a)) {
                return 'applied';
            }
        }

        // Evento de CIO registrado explicitamente no codigo 021
        if ($codVac === '021' && ($lab === 'CIO' || $part === 'CIO')) {
            return 'applied';
        }

        // OK isolado (nao combinado com aviso/telefone/negacao)
        if (($lab === 'OK' && ($part === 'OK' || $part === '' || $part === 'X')) || $part === 'OK') {
            if (
                !str_contains($lab, 'AVISAD')
                && !str_contains($lab, 'TEL')
                && !str_contains($lab, 'WHATS')
                && !str_contains($lab, 'NAO')
                && !str_contains($lab, 'CARTE')
            ) {
                return 'applied';
            }
        }

        // 4. Laboratorios, fabricantes e produtos veterinarios conhecidos
        $realLabTokens = [
            'ZOETIS', 'ZOEITS', 'ZOETS', 'ZOESTIS', 'ZOETIZ', 'PFIZER', 'PIFZER', 'PFZER',
            'VIRBAC', 'VIBRAC', 'VIRBC', 'OURO FINO', 'OUROFINO', 'BAYER', 'BAYERR',
            'ELANCO', 'KONIG', 'KONING', 'VETNIL', 'SYNTEC', 'MERIAL', 'CEVA',
            'FORT DODGE', 'FORTDODGE', 'MSD', 'ALERE', 'ALLERE', 'NOVART', 'BIOGENESIS',
            'VENCO', 'VENCOFARMA', 'HERTAPE', 'CALIER', 'LABOVET', 'AGENER', 'UNIAO',
            'DECHRA', 'BOEHRING', 'INTERVET', 'SCHERING', 'RECOMBITEK', 'RECOMBITEC',
            'VANGUARD', 'NOBIVAC', 'FELOCELL', 'BRONCHICINE', 'DEFENSOR', 'RABISIN',
            'SIMPARIC', 'BRAVECTO', 'NEXGARD', 'CREDELI', 'ADVOCATE', 'REVOLUTION',
            'ENDOGARD', 'DRONTAL', 'MILBEMAX', 'BASKEN', 'VETMAX', 'CANEX', 'VERMOTRIX',
            'EFFIPRO', 'FRONTLINE', 'SERESTO', 'SCALIBOR', 'CYTOPOINT', 'OSURNIA', 'NEPTRA',
            'CYDECTIN', 'CIDECTIN', 'NEOPET', 'CESTODAN', 'CHEMITAL', 'GIARDICID', 'CAPSTAR',
            'BANNI', 'ECTOFEND', 'DUOPRAT', 'HANTAVET', 'PETZI', 'PETZ PLUS', 'PET PLUS',
            'VERMICANIS', 'DECTOMAX', 'ENDAL', 'PANACUR', 'VERMET', 'MULTIDOG', 'MULTI DOG',
            'TOP DOG', 'BIOFEL', 'IMUNOVET', 'IVOMEC', 'IVERMECT', 'BIOVET', 'HIPRA',
            'WORLD', 'CEVAVAC', 'BIONEW', 'PROLAB', 'INATA', 'VETBRANDS', 'COVELI',
            'DUPRAT', 'AVERT', 'BIOLAB', 'CEPAV', 'VANSIL', 'JA SAUDE', 'UCE',
            'INJETAVEL', 'COMPRIMIDO', 'SUBCUTAN', 'OUVIDO DIREITO', 'OUVIDO ESQUERDO',
        ];
        foreach ($realLabTokens as $tok) {
            if (str_contains($lab, $tok) || str_contains($part, $tok)) {
                return 'applied';
            }
        }

        // 5. Padrao de partida/lote ou dosagem no campo BF_PARTIDA (ex.: 012/25, 10KG, 0,5ML)
        if (preg_match('/\d{2,4}\/\d{2,4}/', $part) || preg_match('/\d+[,\.]?\d*\s*(KG|ML|MG)/', $part)) {
            return 'applied';
        }

        // 6. Todo o restante (AVISADO VIA WHATS, SMS, TELEFONE, RECADO, MUDOU, DOADO, etc.) -> nota NAO aplicada
        return 'note';
    }

    protected function normalizeText(string $s): string
    {
        $s = mb_strtoupper(trim($s));
        $s = str_replace(
            ['Á', 'À', 'Ã', 'Â', 'É', 'È', 'Ê', 'Í', 'Ì', 'Î', 'Ó', 'Ò', 'Õ', 'Ô', 'Ú', 'Ù', 'Û', 'Ç'],
            ['A', 'A', 'A', 'A', 'E', 'E', 'E', 'I', 'I', 'I', 'O', 'O', 'O', 'O', 'U', 'U', 'U', 'C'],
            $s
        );
        return (string) preg_replace('/\s+/', ' ', $s);
    }

    /**
     * Restaura a tabela vaccinations a partir de um backup NDJSON gerado antes da reimportacao.
     */
    public function rollbackFromNdjson(string $backupPath): int
    {
        if (!is_file($backupPath)) {
            throw new \RuntimeException("Arquivo de backup nao encontrado: {$backupPath}");
        }

        $rows = [];
        $fp = fopen($backupPath, 'rb');
        while (($line = fgets($fp)) !== false) {
            $line = trim($line);
            if ($line === '') continue;
            $decoded = json_decode($line, true);
            if (is_array($decoded)) {
                $rows[] = $decoded;
            }
        }
        fclose($fp);

        $columns = Schema::getColumnListing('vaccinations');
        $colSet = array_flip($columns);

        DB::transaction(function () use ($rows, $colSet) {
            DB::table('vaccinations')->delete();
            $batch = [];
            foreach ($rows as $row) {
                $filtered = array_intersect_key($row, $colSet);
                if (isset($colSet['laboratory']) && !array_key_exists('laboratory', $filtered)) {
                    $filtered['laboratory'] = $filtered['manufacturer'] ?? null;
                }
                if (isset($colSet['status']) && !array_key_exists('status', $filtered)) {
                    $filtered['status'] = 'applied';
                }
                $batch[] = $filtered;
                if (count($batch) >= 500) {
                    DB::table('vaccinations')->insert($batch);
                    $batch = [];
                }
            }
            if (!empty($batch)) {
                DB::table('vaccinations')->insert($batch);
            }
        });

        Cache::forget('vaccination_center_dashboard_stats');
        return count($rows);
    }

    /**
     * Identifica IDs na tabela vaccinations originados do SISVETW (para substituicao controlada),
     * preservando qualquer vacinacao cadastrada ou editada manualmente na Vetessense.
     *
     * @return int[]
     */
    protected function findLegacyVaccinationIds(): array
    {
        return DB::table('vaccinations')
            ->where(function ($q) {
                $q->whereNotNull('sisvetw_recno')
                  ->orWhere(function ($q2) {
                      $q2->whereBetween('created_at', ['2026-11-18 10:23:00', '2026-11-18 10:26:59'])
                         ->whereColumn('created_at', 'updated_at');
                  });
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * Constroi e valida correspondencia segura BC_FICHA -> pets.id (sem associacao por suposicao).
     */
    protected function buildPetMapping(bool $persist = true): array
    {
        $sisTutors = [];
        $propDbf = $this->basePath . DIRECTORY_SEPARATOR . 'DDPROP01.DBF';
        if (file_exists($propDbf)) {
            $del = 0;
            foreach ($this->streamDbf($propDbf, $del) as $t) {
                if ($t['AX_CODIGO'] !== '' && $t['AX_NOME'] !== '') {
                    $sisTutors[$t['AX_CODIGO']] = $t['AX_NOME'];
                }
            }
        }

        $sisPets = [];
        $fichDbf = $this->basePath . DIRECTORY_SEPARATOR . 'DDFICH01.DBF';
        if (!file_exists($fichDbf)) {
            throw new \RuntimeException("Arquivo de animais nao encontrado: {$fichDbf}");
        }
        $del = 0;
        foreach ($this->streamDbf($fichDbf, $del) as $p) {
            if ($p['BC_NOME'] === '') continue;
            $sisPets[] = $p;
        }

        $dbPets = DB::select("
            SELECT p.id, p.name, p.birth_date, p.gender,
                   (SELECT t.name FROM tutors t JOIN pet_tutor pt ON pt.tutor_id = t.id WHERE pt.pet_id = p.id LIMIT 1) AS tutor_name
            FROM pets p
            ORDER BY p.id ASC
        ");

        $dbById = [];
        $dbByNameTutor = [];
        foreach ($dbPets as $dp) {
            $dbById[(int) $dp->id] = $dp;
            $key = mb_strtoupper(trim($dp->name)) . '|' . mb_strtoupper(trim((string) ($dp->tutor_name ?? '')));
            $dbByNameTutor[$key][] = $dp;
        }

        $existingMap = [];
        if (Schema::hasTable('sisvetw_pet_maps')) {
            $existingMap = DB::table('sisvetw_pet_maps')->pluck('pet_id', 'sisvetw_ficha')->all();
        }

        $map = [];
        $unmatched = [];
        $rowsToSave = [];
        $nowStr = now()->format('Y-m-d H:i:s');

        foreach ($sisPets as $idx => $sp) {
            $ficha = $sp['BC_FICHA'];
            if ($ficha === '') continue;

            $sisName = $sp['BC_NOME'];
            $sisPropr = $sp['BC_PROPR'] ?? '';
            $sisTutor = $sisTutors[$sisPropr] ?? null;

            $matchedPetId = null;
            $matchedBy = null;

            if (isset($existingMap[$ficha]) && isset($dbById[(int) $existingMap[$ficha]])) {
                $matchedPetId = (int) $existingMap[$ficha];
                $matchedBy = 'existing_map';
            }

            if ($matchedPetId === null) {
                $expectedId = $idx + 1;
                $cand = $dbById[$expectedId] ?? null;
                if ($cand && $cand->name === $sisName && ($sisTutor === null || $cand->tutor_name === $sisTutor)) {
                    $matchedPetId = (int) $cand->id;
                    $matchedBy = 'seq_id+name+tutor';
                }
            }

            if ($matchedPetId === null && $sisTutor !== null) {
                $key = mb_strtoupper($sisName) . '|' . mb_strtoupper($sisTutor);
                $cands = $dbByNameTutor[$key] ?? [];
                if (count($cands) === 1) {
                    $matchedPetId = (int) $cands[0]->id;
                    $matchedBy = 'unique_name+tutor';
                } elseif (count($cands) > 1) {
                    $sisBirth = $this->parseStrictDate($sp['BC_DT_NASC'] ?? '');
                    if ($sisBirth !== null) {
                        $filtered = array_values(array_filter($cands, fn ($c) => $c->birth_date === $sisBirth));
                        if (count($filtered) === 1) {
                            $matchedPetId = (int) $filtered[0]->id;
                            $matchedBy = 'unique_name+tutor+birth_date';
                        }
                    }
                }
            }

            if ($matchedPetId !== null) {
                $map[$ficha] = $matchedPetId;
                $rowsToSave[] = [
                    'sisvetw_ficha' => $ficha,
                    'pet_id' => $matchedPetId,
                    'sisvetw_propr' => $sisPropr !== '' ? $sisPropr : null,
                    'pet_name' => mb_substr($sisName, 0, 120),
                    'tutor_name' => $sisTutor ? mb_substr($sisTutor, 0, 150) : null,
                    'matched_by' => $matchedBy,
                    'created_at' => $nowStr,
                    'updated_at' => $nowStr,
                ];
            } else {
                $unmatched[] = [
                    'ficha' => $ficha,
                    'name' => $sisName,
                    'propr' => $sisPropr,
                    'tutor' => $sisTutor,
                ];
            }
        }

        if ($persist && Schema::hasTable('sisvetw_pet_maps') && !empty($rowsToSave)) {
            DB::transaction(function () use ($rowsToSave) {
                foreach (array_chunk($rowsToSave, 500) as $chunk) {
                    DB::table('sisvetw_pet_maps')->upsert(
                        $chunk,
                        ['sisvetw_ficha'],
                        ['pet_id', 'sisvetw_propr', 'pet_name', 'tutor_name', 'matched_by', 'updated_at']
                    );
                }
            });
        }

        return ['map' => $map, 'unmatched' => $unmatched];
    }

    protected function loadVaccineCatalog(): void
    {
        $vaciDbf = $this->basePath . DIRECTORY_SEPARATOR . 'DDVACI01.DBF';
        if (!file_exists($vaciDbf)) {
            return;
        }
        $del = 0;
        foreach ($this->streamDbf($vaciDbf, $del) as $r) {
            $code = $r['AK_CODIGO'] ?? '';
            $descr = $r['AK_DESCR'] ?? '';
            if ($code !== '' && $descr !== '') {
                $this->vaccineCatalog[$code] = [
                    'name' => $descr,
                    'doses' => (int) ($r['AK_DOSES'] ?? 0),
                    'period' => (int) ($r['AK_PERIODO'] ?? 0),
                ];
            }
        }
    }

    protected function resolveVaccineName(string $codVac): string
    {
        if ($codVac !== '' && isset($this->vaccineCatalog[$codVac])) {
            return $this->vaccineCatalog[$codVac]['name'];
        }
        return $codVac !== '' ? "Vacina Cód. {$codVac}" : 'Vacina Geral';
    }

    /**
     * Converte data nativa DBF (YYYYMMDD) para YYYY-MM-DD com validacao estrita de calendario.
     */
    protected function parseStrictDate(?string $dateStr): ?string
    {
        if (!$dateStr) {
            return null;
        }
        $clean = preg_replace('/[^0-9]/', '', $dateStr);
        if (strlen($clean) !== 8) {
            return null;
        }
        $y = (int) substr($clean, 0, 4);
        $m = (int) substr($clean, 4, 2);
        $d = (int) substr($clean, 6, 2);

        if ($y < 1990 || $y > 2035 || !checkdate($m, $d, $y)) {
            return null;
        }

        return sprintf('%04d-%02d-%02d', $y, $m, $d);
    }

    /**
     * Leitor DBF em streaming com numero de registro (_recno) e contagem de deletados.
     */
    protected function streamDbf(string $dbfPath, int &$deletedCount = 0): \Generator
    {
        $deletedCount = 0;
        $fp = fopen($dbfPath, 'rb');
        if (!$fp) {
            return;
        }

        $header = fread($fp, 32);
        if (strlen($header) < 32) {
            fclose($fp);
            return;
        }

        $hd = unpack('Cversion/Cyy/Cmm/Cdd/Vnum_records/vheader_len/vrecord_len', $header);
        $numRecords = $hd['num_records'];
        $headerLen = $hd['header_len'];
        $recordLen = $hd['record_len'];

        $numFields = intdiv($headerLen - 33, 32);
        $fields = [];
        $offset = 1;
        for ($i = 0; $i < $numFields; $i++) {
            $fd = fread($fp, 32);
            if (strlen($fd) < 32 || ord($fd[0]) === 0x0D) {
                break;
            }
            $name = trim(str_replace("\0", '', substr($fd, 0, 11)));
            $type = chr(ord($fd[11]));
            $len = ord($fd[16]);
            $fields[] = ['name' => $name, 'type' => $type, 'len' => $len, 'offset' => $offset];
            $offset += $len;
        }

        fseek($fp, $headerLen);

        for ($r = 0; $r < $numRecords; $r++) {
            $rec = fread($fp, $recordLen);
            if (strlen($rec) < $recordLen) {
                break;
            }
            if ($rec[0] === '*') {
                $deletedCount++;
                continue;
            }
            $row = ['_recno' => $r + 1];
            foreach ($fields as $f) {
                $raw = substr($rec, $f['offset'], $f['len']);
                if ($f['type'] === 'L') {
                    $row[$f['name']] = ($raw === 'T' || $raw === 't' || $raw === 'Y' || $raw === 'y');
                } else {
                    $row[$f['name']] = mb_convert_encoding(trim($raw), 'UTF-8', 'Windows-1252');
                }
            }
            yield $row;
        }

        fclose($fp);
    }
}
