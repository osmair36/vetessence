<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Category;
use App\Models\MedicalRecord;
use App\Models\Pet;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Tutor;
use App\Models\User;
use App\Models\Vaccination;
use App\Models\WeightRecord;
use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SisvetwImportService
{
    protected string $basePath;
    protected ?int $branchId = null;
    protected ?int $defaultVetId = null;
    protected array $stats = [];

    // Mapeamentos em memória para alta performance
    protected array $tutorMap = [];   // AX_CODIGO => tutor_id
    protected array $petMap = [];     // BC_FICHA => pet_id
    protected array $vaccineMap = []; // AK_CODIGO => AK_DESCR
    protected array $speciesMap = []; // AE_CODIGO => 'canine', 'feline', etc.
    protected array $breedMap = [];   // AF_CODIGO => Nome da Raça

    public function __construct(string $basePath = 'C:\\SISVETW')
    {
        @ini_set('memory_limit', '1024M');
        @set_time_limit(0);

        $this->basePath = rtrim($basePath, '\\/');
        $mainBranch = Branch::where('is_main', true)->first() ?? Branch::first();
        $this->branchId = $mainBranch ? $mainBranch->id : 1;

        $vet = User::where('is_veterinarian', true)->first() ?? User::first();
        $this->defaultVetId = $vet ? $vet->id : 1;
    }

    /**
     * Limpa completamente os dados fictícios anteriores
     */
    public function clearExistingData(): array
    {
        $tablesToClear = [
            'pet_tutor',
            'vaccinations',
            'vaccination_reminders',
            'weight_records',
            'medical_records',
            'appointments',
            'prescriptions',
            'laboratory_orders',
            'laboratory_tests',
            'imaging_exams',
            'surgeries',
            'anesthesia_monitorings',
            'anesthesia_vital_signs',
            'hospitalizations',
            'hospitalization_daily_records',
            'hospitalization_fluid_therapies',
            'hospitalization_prescriptions',
            'consent_forms',
            'consent_logs',
            'dental_charts',
            'dental_conditions',
            'teleconsultations',
            'boardings',
            'boarding_daily_tasks',
            'pet_shop_subscriptions',
            'pet_shop_consumptions',
            'pet_transports',
            'invoices',
            'invoice_items',
            'stock_movements',
            'inventory_batches',
            'purchase_orders',
            'purchase_order_items',
            'commission_logs',
            'pets',
            'tutors',
            'products',
            'suppliers',
        ];

        $cleared = [];
        $isSqlite = DB::getDriverName() === 'sqlite';

        if ($isSqlite) {
            DB::statement('PRAGMA foreign_keys = OFF;');
        } else {
            DB::statement('SET FOREIGN_KEY_CHECKS = 0;');
        }

        foreach ($tablesToClear as $table) {
            if (Schema::hasTable($table)) {
                $count = DB::table($table)->count();
                DB::table($table)->truncate();
                $cleared[$table] = $count;
            }
        }

        if ($isSqlite) {
            DB::statement('PRAGMA foreign_keys = ON;');
        } else {
            DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
        }

        return $cleared;
    }

    /**
     * Carrega tabelas de apoio (espécies, raças, vacinas)
     */
    public function loadAuxiliaryTables(): void
    {
        // 1. Espécies
        $speciesDbf = $this->basePath . DIRECTORY_SEPARATOR . 'ddespe01.dbf';
        if (file_exists($speciesDbf)) {
            $rows = $this->streamDbf($speciesDbf);
            foreach ($rows as $r) {
                $code = trim($r['AE_CODIGO'] ?? '');
                $descr = strtoupper(trim($r['AE_DESCR'] ?? ''));
                if (str_contains($descr, 'CANIN') || str_contains($descr, 'CAO') || str_contains($descr, 'CÃO')) {
                    $this->speciesMap[$code] = 'canine';
                } elseif (str_contains($descr, 'FELIN') || str_contains($descr, 'GAT')) {
                    $this->speciesMap[$code] = 'feline';
                } elseif (str_contains($descr, 'EQUIN') || str_contains($descr, 'CAVAL')) {
                    $this->speciesMap[$code] = 'equine';
                } elseif (str_contains($descr, 'BOVIN')) {
                    $this->speciesMap[$code] = 'bovine';
                } elseif (str_contains($descr, 'AVIC') || str_contains($descr, 'AVE')) {
                    $this->speciesMap[$code] = 'bird';
                } else {
                    $this->speciesMap[$code] = 'other';
                }
            }
        }
        $this->speciesMap['001'] = 'canine';
        $this->speciesMap['002'] = 'feline';

        // 2. Raças
        $racaDbf = $this->basePath . DIRECTORY_SEPARATOR . 'DDRACA01.DBF';
        if (file_exists($racaDbf)) {
            $rows = $this->streamDbf($racaDbf);
            foreach ($rows as $r) {
                $code = trim($r['AF_CODIGO'] ?? '');
                $name = trim($r['AF_DESCR'] ?? '');
                if ($code && $name) {
                    $this->breedMap[$code] = mb_convert_case($name, MB_CASE_TITLE, 'UTF-8');
                }
            }
        }

        // 3. Tipos de Vacinas
        $vaciDbf = $this->basePath . DIRECTORY_SEPARATOR . 'DDVACI01.DBF';
        if (file_exists($vaciDbf)) {
            $rows = $this->streamDbf($vaciDbf);
            foreach ($rows as $r) {
                $code = trim($r['AK_CODIGO'] ?? '');
                $name = trim($r['AK_DESCR'] ?? '');
                if ($code && $name) {
                    $this->vaccineMap[$code] = $name;
                }
            }
        }
    }

    /**
     * Importa Fornecedores
     */
    public function importSuppliers(): int
    {
        $dbf = $this->basePath . DIRECTORY_SEPARATOR . 'DDFORN01.DBF';
        if (!file_exists($dbf)) return 0;

        $rows = $this->streamDbf($dbf);
        $count = 0;

        foreach ($rows as $r) {
            $name = trim($r['AZ_NOME'] ?? '');
            if (!$name) continue;

            $ddd = trim($r['AZ_DDD'] ?? '');
            $fone = trim($r['AZ_FONE1'] ?? '');
            $phone = ($ddd && $fone) ? "({$ddd}) {$fone}" : ($fone ?: null);

            Supplier::create([
                'name' => $name,
                'cnpj' => trim($r['AZ_CGC'] ?? $r['AZ_CPF'] ?? '') ?: null,
                'phone' => $phone,
                'email' => trim($r['AZ_EMAIL'] ?? '') ?: null,
                'address' => trim($r['AZ_END'] ?? '') ?: null,
                'neighborhood' => null,
                'city' => trim($r['AZ_CIDADE'] ?? '') ?: null,
                'state' => trim($r['AZ_UF'] ?? '') ?: null,
                'zipcode' => trim($r['AZ_CEP'] ?? '') ?: null,
                'notes' => trim($r['AZ_COMPLEM'] ?? '') ?: null,
                'is_active' => true,
                'branch_id' => $this->branchId,
            ]);
            $count++;
        }

        return $count;
    }

    /**
     * Importa Produtos e Medicamentos
     */
    public function importProducts(): int
    {
        $dbf = $this->basePath . DIRECTORY_SEPARATOR . 'DDPROD01.DBF';
        if (!file_exists($dbf)) return 0;

        $catDefault = Category::firstOrCreate(
            ['name' => 'Geral', 'branch_id' => $this->branchId],
            ['type' => 'product', 'description' => 'Categoria Padrão']
        );

        $rows = $this->streamDbf($dbf);
        $count = 0;

        foreach ($rows as $r) {
            $name = trim($r['AS_DESCR'] ?? '');
            if (!$name) continue;

            $sku = trim($r['AS_CODIGO'] ?? '');
            $barcode = trim($r['AS_CODBARR'] ?? '') ?: null;
            $unit = trim($r['AS_UNIDADE'] ?? 'UN') ?: 'UN';
            $salePrice = (float)str_replace(',', '.', trim($r['AS_PRECO1'] ?? '0'));
            $costPrice = (float)str_replace(',', '.', trim($r['AS_PRECO2'] ?? '0'));
            $stock = (float)str_replace(',', '.', trim($r['AS_QTDEEST'] ?? '0'));
            $minStock = (float)str_replace(',', '.', trim($r['AS_MINIMO'] ?? '0'));
            $maxStock = (float)str_replace(',', '.', trim($r['AS_MAXIMO'] ?? '0'));

            $dtVal = trim($r['AS_DTVALID'] ?? '');
            $expDate = $this->parseDate($dtVal);

            Product::create([
                'name' => $name,
                'sku' => $sku ?: null,
                'barcode' => $barcode,
                'unit' => $unit,
                'sale_price' => $salePrice > 0 ? $salePrice : 0,
                'cost_price' => $costPrice > 0 ? $costPrice : 0,
                'stock' => $stock,
                'min_stock' => $minStock,
                'max_stock' => $maxStock > 0 ? $maxStock : null,
                'expiration_date' => $expDate,
                'category_id' => $catDefault->id,
                'is_active' => true,
                'branch_id' => $this->branchId,
            ]);
            $count++;
        }

        return $count;
    }

    /**
     * Importa Tutores / Proprietários (DDPROP01.DBF)
     */
    public function importTutors(): int
    {
        $dbf = $this->basePath . DIRECTORY_SEPARATOR . 'DDPROP01.DBF';
        if (!file_exists($dbf)) return 0;

        $rows = $this->streamDbf($dbf);
        $count = 0;

        foreach ($rows as $r) {
            $legacyCode = trim($r['AX_CODIGO'] ?? '');
            $name = trim($r['AX_NOME'] ?? '');
            if (!$name) continue;

            $ddd = trim($r['AX_DDD'] ?? '');
            $f1 = trim($r['AX_FONE1'] ?? '');
            $f2 = trim($r['AX_FONE2'] ?? '');
            $f3 = trim($r['AX_FONE3'] ?? '');

            $phone1 = ($ddd && $f1) ? "({$ddd}) {$f1}" : ($f1 ?: null);
            $phone2 = ($ddd && $f2) ? "({$ddd}) {$f2}" : ($f2 ?: ($f3 ?: null));

            if (!$phone1 || $phone1 === '-') {
                $phone1 = $phone2 ?: 'Não informado';
            }

            $cpf = trim($r['AX_CPF'] ?? '') ?: null;
            $rg = trim($r['AX_RG'] ?? '') ?: null;
            $address = trim($r['AX_END'] ?? '') ?: null;
            $neighborhood = trim($r['AX_BAIRRO'] ?? '') ?: null;
            $city = trim($r['AX_CIDADE'] ?? '') ?: null;
            $state = trim($r['AX_UF'] ?? '') ?: null;
            $cep = trim($r['AX_CEP'] ?? '') ?: null;
            if ($cep === '-' || $cep === '00000-000') $cep = null;

            $notes = trim($r['AX_COMPLEM'] ?? '');
            if (!empty($r['AX_MOTIVO'])) {
                $notes .= ($notes ? ' | Motivo: ' : 'Motivo: ') . trim($r['AX_MOTIVO']);
            }

            $tutor = Tutor::create([
                'name' => $name,
                'cpf' => $cpf,
                'rg' => $rg,
                'phone' => $phone1,
                'phone_secondary' => $phone2,
                'email' => trim($r['AX_EMAIL'] ?? '') ?: null,
                'address' => $address,
                'neighborhood' => $neighborhood,
                'city' => $city,
                'state' => $state,
                'zipcode' => $cep,
                'notes' => $notes ?: null,
                'created_at_branch_id' => $this->branchId,
            ]);

            if ($legacyCode) {
                $this->tutorMap[$legacyCode] = $tutor->id;
            }
            $count++;
        }

        return $count;
    }

    /**
     * Importa Animais / Pacientes (DDFICH01.DBF + ddfich01.FPT)
     * e gera prontuários a partir do campo memo de observações clínicas.
     */
    public function importPets(): array
    {
        $dbf = $this->basePath . DIRECTORY_SEPARATOR . 'DDFICH01.DBF';
        $fpt = $this->basePath . DIRECTORY_SEPARATOR . 'ddfich01.FPT';
        if (!file_exists($dbf)) return ['pets' => 0, 'medical_records' => 0];

        $rows = $this->streamDbf($dbf, $fpt, ['BC_OBS']);
        $petsCount = 0;
        $medicalCount = 0;

        foreach ($rows as $r) {
            $legacyFicha = trim($r['BC_FICHA'] ?? '');
            $name = trim($r['BC_NOME'] ?? '');
            if (!$name) continue;

            $sexoRaw = strtoupper(trim($r['BC_SEXO'] ?? 'M'));
            $gender = ($sexoRaw === 'F' || $sexoRaw === 'FEMEA') ? 'female' : 'male';

            $espCode = trim($r['BC_ESPECIE'] ?? '001');
            $species = $this->speciesMap[$espCode] ?? 'canine';

            $racaCode = trim($r['BC_RACA'] ?? '');
            $breed = $this->breedMap[$racaCode] ?? 'SRD (Sem Raça Definida)';

            $birthDate = $this->parseDate(trim($r['BC_DT_NASC'] ?? ''));
            $coat = trim($r['BC_PELAGEM'] ?? '') ?: null;
            $microchip = trim($r['BC_CHIP'] ?? '') ?: null;
            $pedigree = trim($r['BC_PED_NUM'] ?? '') ?: null;
            $isDead = (bool)($r['BC_MORTO'] ?? false);

            $pet = Pet::create([
                'name' => $name,
                'species' => $species,
                'breed' => $breed,
                'gender' => $gender,
                'birth_date' => $birthDate,
                'coat' => $coat,
                'microchip_number' => $microchip,
                'rg_number' => $pedigree,
                'is_active' => !$isDead,
                'created_at_branch_id' => $this->branchId,
                'notes' => trim($r['BC_MOTIVO'] ?? '') ?: null,
            ]);

            if ($legacyFicha) {
                $this->petMap[$legacyFicha] = $pet->id;
            }
            $petsCount++;

            // Vínculo com Tutor
            $legacyPropr = trim($r['BC_PROPR'] ?? '');
            if ($legacyPropr && isset($this->tutorMap[$legacyPropr])) {
                $tutorId = $this->tutorMap[$legacyPropr];
                DB::table('pet_tutor')->insert([
                    'pet_id' => $pet->id,
                    'tutor_id' => $tutorId,
                    'is_primary' => true,
                    'relationship' => 'Tutor',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Histórico Clínico / Prontuário do Memo FPT
            $obsClinica = trim($r['BC_OBS'] ?? '');
            if ($obsClinica && strlen($obsClinica) > 5) {
                MedicalRecord::create([
                    'pet_id' => $pet->id,
                    'user_id' => $this->defaultVetId,
                    'vet_id' => $this->defaultVetId,
                    'branch_id' => $this->branchId,
                    'date' => now()->toDateString(),
                    'type' => 'consultation',
                    'chief_complaint' => 'Histórico Clínico Importado do Sistema Anterior (SISVETW)',
                    'notes' => $obsClinica,
                    'anamnesis' => $obsClinica,
                ]);
                $medicalCount++;
            }
        }

        return ['pets' => $petsCount, 'medical_records' => $medicalCount];
    }

    /**
     * Importa Vacinações (DDFI_V01.DBF)
     */
    public function importVaccinations(): int
    {
        $dbf = $this->basePath . DIRECTORY_SEPARATOR . 'DDFI_V01.DBF';
        if (!file_exists($dbf)) return 0;

        $rows = $this->streamDbf($dbf);
        $count = 0;
        $batch = [];

        foreach ($rows as $r) {
            $legacyFicha = trim($r['BF_FICHA'] ?? '');
            if (!$legacyFicha || !isset($this->petMap[$legacyFicha])) {
                continue;
            }

            $petId = $this->petMap[$legacyFicha];
            $dtVac = $this->parseDate(trim($r['BF_DATA'] ?? ''));
            if (!$dtVac) continue;

            $codVac = trim($r['BF_COD_VAC'] ?? '');
            $vaccineName = $this->vaccineMap[$codVac] ?? ($codVac ? "Vacina Cód. {$codVac}" : 'Vacina Geral');

            $lab = trim($r['BF_LABORAT'] ?? '') ?: null;
            $lote = trim($r['BF_PARTIDA'] ?? '') ?: null;

            $batch[] = [
                'pet_id' => $petId,
                'vet_id' => $this->defaultVetId,
                'vaccine' => $vaccineName,
                'manufacturer' => $lab,
                'batch' => $lote,
                'lot' => $lote,
                'date' => $dtVac,
                'next_date' => Carbon::parse($dtVac)->addYear()->toDateString(),
                'branch_id' => $this->branchId,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $count++;

            if (count($batch) >= 1000) {
                DB::table('vaccinations')->insert($batch);
                $batch = [];
                unset($batch);
                $batch = [];
            }
        }

        if (!empty($batch)) {
            DB::table('vaccinations')->insert($batch);
            unset($batch);
        }

        return $count;
    }

    /**
     * Importa Histórico de Pesagens (DDPESO01.DBF)
     */
    public function importWeights(): int
    {
        $dbf = $this->basePath . DIRECTORY_SEPARATOR . 'DDPESO01.DBF';
        if (!file_exists($dbf)) return 0;

        $rows = $this->streamDbf($dbf);
        $count = 0;
        $batch = [];
        $latestWeights = [];

        foreach ($rows as $r) {
            $legacyFicha = trim($r['PD_FICHA'] ?? '');
            if (!$legacyFicha || !isset($this->petMap[$legacyFicha])) {
                continue;
            }

            $petId = $this->petMap[$legacyFicha];
            $date = $this->parseDate(trim($r['PD_DATA'] ?? ''));
            if (!$date) continue;

            $weight = (float)str_replace(',', '.', trim($r['PD_PESO'] ?? '0'));
            if ($weight <= 0) continue;

            $batch[] = [
                'pet_id' => $petId,
                'weight' => $weight,
                'measurement_date' => $date,
                'measured_by' => $this->defaultVetId,
                'notes' => 'Importação SISVETW',
                'branch_id' => $this->branchId,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (!isset($latestWeights[$petId]) || $date > $latestWeights[$petId]['date']) {
                $latestWeights[$petId] = ['weight' => $weight, 'date' => $date];
            }

            $count++;

            if (count($batch) >= 1000) {
                DB::table('weight_records')->insert($batch);
                $batch = [];
            }
        }

        if (!empty($batch)) {
            DB::table('weight_records')->insert($batch);
            unset($batch);
        }

        // Atualiza o peso mais recente no cadastro de cada Pet
        foreach ($latestWeights as $petId => $pData) {
            Pet::where('id', $petId)->update(['weight' => $pData['weight']]);
        }

        return $count;
    }

    /**
     * Importa Agendamentos / Banho & Tosa (DDAGEN02.DBF + DDAGEN02.FPT)
     */
    public function importAppointments(int $limit = 5000): int
    {
        $dbf = $this->basePath . DIRECTORY_SEPARATOR . 'DDAGEN02.DBF';
        $fpt = $this->basePath . DIRECTORY_SEPARATOR . 'DDAGEN02.FPT';
        if (!file_exists($dbf)) return 0;

        $rows = $this->streamDbf($dbf, $fpt, ['HW_MEMO']);
        $count = 0;
        $batch = [];

        $defaultPet = Pet::firstOrCreate(
            ['name' => 'Atendimento Avulso'],
            [
                'species' => 'canine',
                'breed' => 'Não informado',
                'gender' => 'male',
                'is_active' => true,
                'created_at_branch_id' => $this->branchId,
                'notes' => 'Registro reservado para agendamentos e atendimentos avulsos do histórico SISVETW',
            ]
        );

        // Filtra agendamentos mais recentes ou que tenham texto memo
        foreach ($rows as $r) {
            $dateRaw = trim($r['HW_DATA'] ?? '');
            $date = $this->parseDate($dateRaw);
            if (!$date) continue;

            $desc = trim($r['HW_MEMO'] ?? '');
            if (!$desc) continue;

            $time = trim($r['HW_HORA'] ?? '') ?: '09:00';
            if (strlen($time) === 5 && !str_contains($time, ':')) {
                $time = substr($time, 0, 2) . ':' . substr($time, 2, 2);
            }

            $tipoAgenda = trim($r['HW_AGENDA'] ?? '1');
            $type = ($tipoAgenda === '1') ? 'grooming' : 'consultation';

            $batch[] = [
                'pet_id' => $defaultPet->id,
                'vet_id' => $this->defaultVetId,
                'user_id' => $this->defaultVetId,
                'date' => $date,
                'time' => substr($time, 0, 5),
                'type' => $type,
                'status' => 'completed',
                'reason' => mb_substr($desc, 0, 150),
                'notes' => $desc,
                'branch_id' => $this->branchId,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $count++;

            if (count($batch) >= 1000) {
                DB::table('appointments')->insert($batch);
                $batch = [];
                if ($count >= $limit) break;
            }
        }

        if (!empty($batch)) {
            DB::table('appointments')->insert($batch);
            unset($batch);
        }

        return $count;
    }

    /**
     * Leitor DBF + FPT (Memo) em modo streaming (Generator) com consumo mínimo de memória O(1)
     */
    protected function streamDbf(string $dbfPath, ?string $fptPath = null, array $memoFields = []): \Generator
    {
        $fp = fopen($dbfPath, 'rb');
        if (!$fp) return;

        $header = fread($fp, 32);
        if (strlen($header) < 32) {
            fclose($fp);
            return;
        }

        $headerData = unpack('Cversion/Cyy/Cmm/Cdd/Vnum_records/vheader_len/vrecord_len', $header);
        $numRecords = $headerData['num_records'];
        $headerLen = $headerData['header_len'];
        $recordLen = $headerData['record_len'];

        $numFields = intdiv($headerLen - 33, 32);
        $fields = [];
        $offset = 1;
        for ($i = 0; $i < $numFields; $i++) {
            $fd = fread($fp, 32);
            if (strlen($fd) < 32 || ord($fd[0]) === 0x0D) break;
            $name = trim(str_replace("\0", '', substr($fd, 0, 11)));
            $type = chr(ord($fd[11]));
            $len = ord($fd[16]);
            $fields[] = ['name' => $name, 'type' => $type, 'len' => $len, 'offset' => $offset];
            $offset += $len;
        }

        $fptFp = null;
        $fptBlockSize = 512;
        if ($fptPath && file_exists($fptPath)) {
            $fptFp = fopen($fptPath, 'rb');
            if ($fptFp) {
                $fptHead = fread($fptFp, 512);
                if (strlen($fptHead) >= 8) {
                    $bsData = unpack('nblock_size', substr($fptHead, 6, 2));
                    if (!empty($bsData['block_size'])) {
                        $fptBlockSize = $bsData['block_size'];
                    }
                }
            }
        }

        fseek($fp, $headerLen);

        for ($r = 0; $r < $numRecords; $r++) {
            $rec = fread($fp, $recordLen);
            if (strlen($rec) < $recordLen) break;

            if ($rec[0] === '*') continue;

            $row = [];
            foreach ($fields as $f) {
                $raw = substr($rec, $f['offset'], $f['len']);

                if ($f['type'] === 'M' && in_array($f['name'], $memoFields) && $fptFp) {
                    $blockNo = 0;
                    if (strlen($raw) === 4) {
                        $bData = unpack('Vblock', $raw);
                        $blockNo = $bData['block'] ?? 0;
                    } elseif (is_numeric(trim($raw))) {
                        $blockNo = (int)trim($raw);
                    }

                    if ($blockNo > 0) {
                        fseek($fptFp, $blockNo * $fptBlockSize);
                        $bHead = fread($fptFp, 8);
                        if (strlen($bHead) === 8) {
                            $bh = unpack('Ntype/Nlen', $bHead);
                            $memoLen = $bh['len'] ?? 0;
                            if ($memoLen > 0 && $memoLen < 2000000) {
                                $memoBytes = fread($fptFp, $memoLen);
                                $row[$f['name']] = trim(mb_convert_encoding($memoBytes, 'UTF-8', 'Windows-1252'));
                                continue;
                            }
                        }
                    }
                    $row[$f['name']] = '';
                } elseif ($f['type'] === 'L') {
                    $row[$f['name']] = ($raw === 'T' || $raw === 't' || $raw === 'Y' || $raw === 'y');
                } else {
                    $val = trim($raw);
                    $row[$f['name']] = mb_convert_encoding($val, 'UTF-8', 'Windows-1252');
                }
            }

            yield $row;
        }

        fclose($fp);
        if ($fptFp) fclose($fptFp);
    }

    /**
     * Converte data legada YYYYMMDD para YYYY-MM-DD
     */
    protected function parseDate(?string $dateStr): ?string
    {
        if (!$dateStr) return null;
        $clean = preg_replace('/[^0-9]/', '', $dateStr);
        if (strlen($clean) === 8) {
            $y = (int)substr($clean, 0, 4);
            $m = (int)substr($clean, 4, 2);
            $d = (int)substr($clean, 6, 2);
            if ($y >= 1950 && $y <= 2035 && $m >= 1 && $m <= 12 && $d >= 1 && $d <= 31) {
                return sprintf('%04d-%02d-%02d', $y, $m, $d);
            }
        }
        return null;
    }
}
