<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\User;
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
     * Executa diagnostico (dryRun=true) ou reimportacao real exclusiva de vacinacoes.
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
            'applied_records_to_import' => 0,
            'applied_with_real_next_date' => 0,
            'applied_without_next_date' => 0,
            'applied_with_laboratory' => 0,
            'scheduled_consolidated_as_next_date' => 0,
            'scheduled_future_without_prior_dose' => 0,
            'scheduled_past_unapplied_discarded' => 0,
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
            'future_scheduled_without_prior_dose' => [],
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

            $r['_pet_id'] = $petId;
            $r['_iso_date'] = $isoDate;
            $r['_is_applied'] = ($lab !== '' || $part !== '');
            $seriesByPetFamily[$seriesKey][] = $r;
        }
        $stats['source_deleted_skipped'] = $deletedCount;
        $inconsistencies['unknown_vaccine_codes'] = $stats['unknown_vaccine_codes'];

        // Processa cada serie (pet, familia vacinal) em ordem cronologica exata
        $recordsToInsert = [];
        $nowStr = now()->format('Y-m-d H:i:s');

        foreach ($seriesByPetFamily as $seriesRows) {
            usort($seriesRows, static function (array $a, array $b): int {
                return strcmp($a['_iso_date'], $b['_iso_date']) ?: ($a['_recno'] <=> $b['_recno']);
            });

            $n = count($seriesRows);
            $hasPriorApplied = false;
            $doseCounterByCod = [];

            for ($i = 0; $i < $n; $i++) {
                $row = $seriesRows[$i];
                $isoDate = $row['_iso_date'];
                $codVac = $row['BF_COD_VAC'];
                $isApplied = $row['_is_applied'];

                if (!$isApplied) {
                    if ($isoDate > $this->referenceToday) {
                        if ($hasPriorApplied) {
                            $stats['scheduled_consolidated_as_next_date']++;
                        } else {
                            $stats['scheduled_future_without_prior_dose']++;
                            if (count($inconsistencies['future_scheduled_without_prior_dose']) < 200) {
                                $inconsistencies['future_scheduled_without_prior_dose'][] = [
                                    'recno' => $row['_recno'],
                                    'ficha' => $row['BF_FICHA'],
                                    'pet_id' => $row['_pet_id'],
                                    'date' => $isoDate,
                                    'cod_vac' => $codVac,
                                    'vaccine' => $this->resolveVaccineName($codVac),
                                ];
                            }
                        }
                    } else {
                        $stats['scheduled_past_unapplied_discarded']++;
                    }
                    continue;
                }

                $hasPriorApplied = true;
                $doseCounterByCod[$codVac] = ($doseCounterByCod[$codVac] ?? 0) + 1;
                $doseNum = $doseCounterByCod[$codVac];

                // Proxima data REAL: proxima linha cronologica da mesma serie com data estritamente maior
                $nextDate = null;
                for ($j = $i + 1; $j < $n; $j++) {
                    if ($seriesRows[$j]['_iso_date'] > $isoDate) {
                        $nextDate = $seriesRows[$j]['_iso_date'];
                        break;
                    }
                }

                if ($nextDate !== null) {
                    $stats['applied_with_real_next_date']++;
                } else {
                    $stats['applied_without_next_date']++;
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
                    'date' => $isoDate,
                    'next_date' => $nextDate,
                    'notes' => null,
                    'reminder_sent' => 0,
                    'branch_id' => $this->branchId,
                    'sisvetw_recno' => $row['_recno'],
                    'sisvetw_ficha' => $row['BF_FICHA'],
                    'sisvetw_cod_vac' => $codVac !== '' ? $codVac : null,
                    'created_at' => $nowStr,
                    'updated_at' => $nowStr,
                ];
            }
        }

        $stats['applied_records_to_import'] = count($recordsToInsert);

        // Identifica registros legados (substituiveis) vs registros manuais da Vetessense (preservados!)
        $legacyIds = $this->findLegacyVaccinationIds();
        $totalExisting = DB::table('vaccinations')->count();
        $stats['legacy_replaced_count'] = count($legacyIds);
        $stats['manual_preserved_count'] = $totalExisting - count($legacyIds);

        // Grava relatorio de inconsistencias para analise
        $logPath = storage_path('logs/sisvetw-vaccinations-inconsistencies.json');
        @file_put_contents($logPath, json_encode($inconsistencies, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        if (!$dryRun) {
            DB::transaction(function () use ($legacyIds, $recordsToInsert) {
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
            });
        }

        return [
            'dry_run' => $dryRun,
            'stats' => $stats,
            'inconsistencies_log' => $logPath,
        ];
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
                      // Lote da importacao inicial de 2026-11-18 10:24 sem edicao posterior
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

            // 1. Ja mapeado em sisvetw_pet_maps e pet ainda existe
            if (isset($existingMap[$ficha]) && isset($dbById[(int) $existingMap[$ficha]])) {
                $matchedPetId = (int) $existingMap[$ficha];
                $matchedBy = 'existing_map';
            }

            // 2. Correspondencia 1-para-1 validada (ID sequencial da migracao + nome + tutor)
            if ($matchedPetId === null) {
                $expectedId = $idx + 1;
                $cand = $dbById[$expectedId] ?? null;
                if ($cand && $cand->name === $sisName && ($sisTutor === null || $cand->tutor_name === $sisTutor)) {
                    $matchedPetId = (int) $cand->id;
                    $matchedBy = 'seq_id+name+tutor';
                }
            }

            // 3. Fallback estrito (somente se unico por nome + tutor, nunca apenas nome!)
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
