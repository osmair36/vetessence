<?php

namespace App\Services;

use App\Models\MedicationAdministration;
use App\Models\ParasiteControl;
use App\Models\Pet;
use App\Models\Product;
use App\Models\Vaccination;
use App\Models\VaccineProtocol;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Serviço centralizado para cálculo automático da próxima data de aplicação
 * de produtos veterinários (vacinas, antiparasitários, vermífugos e medicamentos periódicos).
 *
 * Regras fundamentais:
 * - Reutiliza protocolos cadastrados (vaccine_protocols) e periodicidade do produto (products).
 * - Nunca inventa periodicidade para produtos sem protocolo cadastrado.
 * - Suporta intervalos em dias, semanas e meses (usando addMonthsNoOverflow para calendário consistente).
 * - Suporta protocolos multi-dose, dose inicial vs. reforço, espécie e faixa etária.
 * - Preserva alterações manuais feitas pelo usuário nos formulários.
 */
class ApplicationScheduleService
{
    public const VALID_UNITS = ['days', 'weeks', 'months'];

    /** @var array<string, bool> */
    protected static array $columnCache = [];

    /**
     * Calcula a próxima data de aplicação com base na data atual, produto, pet e histórico.
     *
     * @return array{
     *     calculated: bool,
     *     next_date: ?string,
     *     next_date_formatted: ?string,
     *     source: string,
     *     protocol_id: ?int,
     *     product_id: ?int,
     *     interval_value: ?int,
     *     interval_unit: ?string,
     *     current_stage: ?string,
     *     next_dose_label: ?string,
     *     message: string
     * }
     */
    public function calculateNextApplication(
        Carbon|string|null $applicationDate,
        ?Product $product = null,
        ?Pet $pet = null,
        ?string $itemName = null,
        string $context = 'general',
        ?int $excludeRecordId = null,
        ?string $doseLabel = null
    ): array {
        $appDate = $this->parseDate($applicationDate);
        if (!$appDate) {
            return $this->emptyResult($product?->id, 'Informe uma data de aplicação válida para calcular a próxima data.');
        }

        $effectiveName = trim((string) ($itemName ?: ($product?->name ?? '')));
        if (!$product && $effectiveName === '') {
            return $this->emptyResult(null, 'Selecione um produto ou informe o item aplicado.');
        }

        // Idade do animal na data da aplicação (em semanas)
        $ageWeeks = null;
        if ($pet && $pet->birth_date) {
            $diff = $pet->birth_date->copy()->startOfDay()->diffInWeeks($appDate, false);
            if ($diff >= 0) {
                $ageWeeks = (int) $diff;
            }
        }

        $petSpecies = $pet?->species ? $this->normalizeText($pet->species) : null;

        // 1) Tenta localizar protocolos estruturados na tabela vaccine_protocols
        $protocolLookup = $this->findMatchingProtocols($product, $effectiveName, $petSpecies);

        if ($protocolLookup['species_mismatch']) {
            return $this->emptyResult(
                $product?->id,
                'Existe protocolo cadastrado para este produto, porém não aplicável à espécie do animal selecionado. Informe a próxima data manualmente se necessário.'
            );
        }

        if ($protocolLookup['protocols']->isNotEmpty()) {
            $previousCount = $this->countPreviousApplications(
                $pet,
                $product,
                $effectiveName,
                $protocolLookup['protocols'],
                $appDate,
                $context,
                $excludeRecordId
            );

            $resolved = $this->resolveFromProtocols(
                $protocolLookup['protocols'],
                $appDate,
                $ageWeeks,
                $previousCount,
                $doseLabel,
                $product?->id
            );

            if ($resolved['calculated']) {
                return $resolved;
            }
        }

        // 2) Fallback: periodicidade cadastrada diretamente no produto (products.reapplication_interval / unit)
        if ($product && $this->hasProductInterval($product)) {
            $productSpecies = $product->target_species ? $this->normalizeText($product->target_species) : null;
            if ($petSpecies && $productSpecies && $productSpecies !== 'all' && !$this->speciesMatches($productSpecies, $petSpecies)) {
                return $this->emptyResult(
                    $product->id,
                    'A periodicidade cadastrada para este produto é específica para outra espécie. Informe a próxima data manualmente se necessário.'
                );
            }

            $value = (int) $product->reapplication_interval;
            $unit = (string) $product->reapplication_unit;
            $nextDate = $this->addInterval($appDate, $value, $unit);

            if ($nextDate && $nextDate->gt($appDate)) {
                $unitLabel = $this->formatIntervalLabel($value, $unit);
                return [
                    'calculated' => true,
                    'next_date' => $nextDate->format('Y-m-d'),
                    'next_date_formatted' => $nextDate->format('d/m/Y'),
                    'source' => 'product_interval',
                    'protocol_id' => null,
                    'product_id' => $product->id,
                    'interval_value' => $value,
                    'interval_unit' => $unit,
                    'current_stage' => "Intervalo do produto ({$unitLabel})",
                    'next_dose_label' => 'Reaplicação periódica',
                    'message' => "Calculada automaticamente pelo cadastro do produto: intervalo de {$unitLabel} ({$nextDate->format('d/m/Y')}).",
                ];
            }
        }

        // 3) Sem protocolo cadastrado: não inventar periodicidade
        return $this->emptyResult(
            $product?->id,
            'Nenhum protocolo de reaplicação cadastrado para este produto. Você pode informar a próxima data manualmente.'
        );
    }

    /**
     * Resolve a data final a ser gravada no backend, preservando sempre ajustes manuais.
     */
    public function resolveNextDateForPersistence(
        ?string $submittedNextDate,
        bool $wasManuallyEdited,
        Carbon|string|null $applicationDate,
        ?Product $product = null,
        ?Pet $pet = null,
        ?string $itemName = null,
        string $context = 'general',
        ?int $excludeRecordId = null,
        ?string $doseLabel = null
    ): ?string {
        $cleanSubmitted = $submittedNextDate !== null ? trim($submittedNextDate) : '';

        // Se o usuário informou uma data manualmente (ou aceitou a sugerida no formulário), prevalece!
        if ($cleanSubmitted !== '') {
            return $cleanSubmitted;
        }

        // Se o usuário marcou explicitamente que editou/limpou manualmente, não sobrescreve!
        if ($wasManuallyEdited) {
            return null;
        }

        // Caso contrário (ex.: envio sem data preenchida), calcula de forma confiável no backend
        $calc = $this->calculateNextApplication(
            $applicationDate,
            $product,
            $pet,
            $itemName,
            $context,
            $excludeRecordId,
            $doseLabel
        );

        return $calc['calculated'] ? $calc['next_date'] : null;
    }

    /**
     * Soma intervalo à data base de forma consistente com o calendário.
     * Meses utilizam addMonthsNoOverflow (ex.: 31/01 + 3 meses = 30/04).
     */
    public function addInterval(Carbon $baseDate, int $value, string $unit): ?Carbon
    {
        if ($value <= 0 || !in_array($unit, self::VALID_UNITS, true)) {
            return null;
        }

        $next = match ($unit) {
            'days' => $baseDate->copy()->addDays($value),
            'weeks' => $baseDate->copy()->addWeeks($value),
            'months' => $baseDate->copy()->addMonthsNoOverflow($value),
            default => null,
        };

        if (!$next || !$next->gt($baseDate)) {
            return null;
        }

        return $next;
    }

    /**
     * Localiza protocolos aplicáveis em vaccine_protocols (prioridade: product_id -> nome).
     *
     * @return array{protocols: Collection<int, VaccineProtocol>, species_mismatch: bool}
     */
    protected function findMatchingProtocols(?Product $product, string $itemName, ?string $petSpecies): array
    {
        if (!Schema::hasTable('vaccine_protocols')) {
            return ['protocols' => collect(), 'species_mismatch' => false];
        }

        $hasProductCol = $this->hasColumn('vaccine_protocols', 'product_id');

        // Prioridade 1: protocolos vinculados diretamente ao product_id
        if ($product && $hasProductCol) {
            $linked = VaccineProtocol::where('is_active', true)
                ->where('product_id', $product->id)
                ->get();

            if ($linked->isNotEmpty()) {
                return $this->filterProtocolsBySpecies($linked, $petSpecies);
            }
        }

        // Prioridade 2: correspondência por nome nos protocolos ativos existentes
        if ($itemName !== '') {
            $allActive = VaccineProtocol::where('is_active', true)->get();
            $normalizedInput = $this->normalizeText($itemName);

            $matched = $allActive->filter(function (VaccineProtocol $protocol) use ($normalizedInput) {
                return $this->protocolNameMatches($protocol->vaccine_name, $normalizedInput);
            });

            if ($matched->isNotEmpty()) {
                return $this->filterProtocolsBySpecies($matched, $petSpecies);
            }
        }

        return ['protocols' => collect(), 'species_mismatch' => false];
    }

    /**
     * Filtra coleção de protocolos pela espécie do animal.
     *
     * @param Collection<int, VaccineProtocol> $protocols
     * @return array{protocols: Collection<int, VaccineProtocol>, species_mismatch: bool}
     */
    protected function filterProtocolsBySpecies(Collection $protocols, ?string $petSpecies): array
    {
        if (!$petSpecies) {
            return ['protocols' => $protocols->values(), 'species_mismatch' => false];
        }

        // Primeiro: protocolos específicos para a espécie do pet
        $exact = $protocols->filter(function (VaccineProtocol $p) use ($petSpecies) {
            return $p->species && $this->speciesMatches($this->normalizeText($p->species), $petSpecies);
        });

        if ($exact->isNotEmpty()) {
            return ['protocols' => $exact->values(), 'species_mismatch' => false];
        }

        // Segundo: protocolos genéricos (species = 'all' ou vazio)
        $generic = $protocols->filter(function (VaccineProtocol $p) {
            $sp = $this->normalizeText((string) ($p->species ?? ''));
            return $sp === '' || $sp === 'all' || $sp === 'todas';
        });

        if ($generic->isNotEmpty()) {
            return ['protocols' => $generic->values(), 'species_mismatch' => false];
        }

        // Existiam protocolos, mas exclusivamente para outras espécies
        return ['protocols' => collect(), 'species_mismatch' => true];
    }

    /**
     * Resolve a etapa atual e calcula a próxima data a partir dos protocolos filtrados.
     *
     * @param Collection<int, VaccineProtocol> $protocols
     */
    protected function resolveFromProtocols(
        Collection $protocols,
        Carbon $appDate,
        ?int $ageWeeks,
        int $previousCount,
        ?string $doseLabel,
        ?int $productId
    ): array {
        $initialStages = $protocols
            ->where('is_initial', true)
            ->sortBy([
                ['dose_number', 'asc'],
                ['age_start_weeks', 'asc'],
                ['id', 'asc'],
            ])
            ->values();

        $boosterStages = $protocols
            ->where('is_initial', false)
            ->sortBy([
                ['age_start_weeks', 'asc'],
                ['id', 'asc'],
            ])
            ->values();

        // Verifica se há etapas específicas por faixa etária (ex.: filhote vs adulto)
        $ageFilteredInitial = $this->filterByAgeWindow($initialStages, $ageWeeks);
        $ageFilteredBooster = $this->filterByAgeWindow($boosterStages, $ageWeeks);

        // Interpreta se o usuário informou explicitamente número de dose ou reforço no campo "dose"
        $explicitBooster = $this->isExplicitBoosterLabel($doseLabel);
        $explicitDoseNumber = $this->parseExplicitDoseNumber($doseLabel);

        // Se o animal tem idade conhecida e já ultrapassou a faixa etária máxima de todas as doses iniciais
        // e existe protocolo de reforço cadastrado (ex.: cão adulto tomando V8 pela 1ª vez no sistema)
        $isAdultBeyondInitialSeries = false;
        if ($ageWeeks !== null && $initialStages->isNotEmpty() && !$explicitDoseNumber) {
            $allHaveMaxAge = $initialStages->every(fn (VaccineProtocol $p) => $p->age_end_weeks !== null);
            $maxInitialEnd = $initialStages->max('age_end_weeks');
            if ($allHaveMaxAge && $maxInitialEnd !== null && $ageWeeks > (int) $maxInitialEnd) {
                if ($boosterStages->isNotEmpty() || $initialStages->contains(fn (VaccineProtocol $p) => (int) ($p->booster_interval_months ?? 0) > 0)) {
                    $isAdultBeyondInitialSeries = true;
                }
            }
        }

        // Caso A: Etapa de Reforço (adulto fora da série filhote, reforço explícito, sem etapas iniciais ou já concluiu todas as doses iniciais)
        $stageIndex = $explicitDoseNumber !== null ? max(0, $explicitDoseNumber - 1) : $previousCount;

        // Se houver múltiplos protocolos iniciais distintos por faixa etária (mesmo dose_number = 1 para idades diferentes),
        // prioriza o conjunto compatível com a idade do pet
        $effectiveInitial = $initialStages;
        if ($ageWeeks !== null && $ageFilteredInitial->isNotEmpty() && $initialStages->pluck('dose_number')->duplicates()->isNotEmpty()) {
            $effectiveInitial = $ageFilteredInitial;
        }

        $effectiveBooster = $ageFilteredBooster->isNotEmpty() ? $ageFilteredBooster : $boosterStages;

        if ($explicitBooster || $isAdultBeyondInitialSeries || $effectiveInitial->isEmpty() || $stageIndex >= $effectiveInitial->count()) {
            $boosterStage = $effectiveBooster->first();
            $interval = $boosterStage ? $this->extractProtocolInterval($boosterStage) : null;

            // Fallback para booster_interval_months configurado na última dose da série inicial
            if (!$interval && $effectiveInitial->isNotEmpty()) {
                $lastWithBooster = $effectiveInitial->reverse()->first(fn (VaccineProtocol $p) => (int) ($p->booster_interval_months ?? 0) > 0);
                if ($lastWithBooster) {
                    $boosterStage = $lastWithBooster;
                    $interval = ['value' => (int) $lastWithBooster->booster_interval_months, 'unit' => 'months'];
                }
            }

            if ($interval && $boosterStage) {
                $nextDate = $this->addInterval($appDate, $interval['value'], $interval['unit']);
                if ($nextDate) {
                    $label = $this->formatIntervalLabel($interval['value'], $interval['unit']);
                    $stageDesc = $isAdultBeyondInitialSeries
                        ? "Reforço adulto ({$label})"
                        : "Reforço periódico ({$label})";

                    return [
                        'calculated' => true,
                        'next_date' => $nextDate->format('Y-m-d'),
                        'next_date_formatted' => $nextDate->format('d/m/Y'),
                        'source' => 'protocol_booster',
                        'protocol_id' => $boosterStage->id,
                        'product_id' => $productId ?? $boosterStage->product_id,
                        'interval_value' => $interval['value'],
                        'interval_unit' => $interval['unit'],
                        'current_stage' => $stageDesc,
                        'next_dose_label' => 'Reforço',
                        'message' => "Calculada automaticamente pelo protocolo ({$boosterStage->vaccine_name} — {$stageDesc}): {$nextDate->format('d/m/Y')}.",
                    ];
                }
            }

            return $this->emptyResult($productId, 'Protocolo localizado, porém sem intervalo de reforço configurado.');
        }

        // Caso B: Série inicial (multi-dose ou dose única inicial)
        /** @var VaccineProtocol $currentStage */
        $currentStage = $effectiveInitial->get($stageIndex);
        /** @var ?VaccineProtocol $nextStage */
        $nextStage = $effectiveInitial->get($stageIndex + 1);

        $currentNum = $currentStage->dose_number ?: ($stageIndex + 1);
        $interval = null;
        $nextDoseLabel = null;
        $source = 'protocol_interval';

        // 1. Intervalo explícito configurado na etapa atual (interval_value + interval_unit)
        if ($this->hasExplicitInterval($currentStage)) {
            $interval = [
                'value' => (int) $currentStage->interval_value,
                'unit' => (string) $currentStage->interval_unit,
            ];
            $nextDoseLabel = $nextStage
                ? (($nextStage->dose_number ?: ($stageIndex + 2)) . 'ª dose')
                : 'Reforço';
            $source = $nextStage ? 'protocol_dose_sequence' : 'protocol_interval';
        }
        // 2. Próxima etapa da série inicial com diferença de semanas cadastrada (ex.: V8 6→9→12 sem, V3 8→12→16 sem)
        elseif ($nextStage) {
            if ($currentStage->age_start_weeks !== null && $nextStage->age_start_weeks !== null && $nextStage->age_start_weeks > $currentStage->age_start_weeks) {
                $weeksDiff = (int) $nextStage->age_start_weeks - (int) $currentStage->age_start_weeks;
                $interval = ['value' => $weeksDiff, 'unit' => 'weeks'];
                $nextNum = $nextStage->dose_number ?: ($stageIndex + 2);
                $nextDoseLabel = "{$nextNum}ª dose";
                $source = 'protocol_dose_sequence';
            } elseif ($this->hasExplicitInterval($nextStage)) {
                $interval = [
                    'value' => (int) $nextStage->interval_value,
                    'unit' => (string) $nextStage->interval_unit,
                ];
                $nextNum = $nextStage->dose_number ?: ($stageIndex + 2);
                $nextDoseLabel = "{$nextNum}ª dose";
                $source = 'protocol_dose_sequence';
            }
        }
        // 3. Última dose da série inicial -> busca reforço (booster_interval_months na etapa atual ou etapa de reforço)
        else {
            if ((int) ($currentStage->booster_interval_months ?? 0) > 0) {
                $interval = ['value' => (int) $currentStage->booster_interval_months, 'unit' => 'months'];
                $nextDoseLabel = 'Reforço';
                $source = 'protocol_booster';
            } elseif ($effectiveBooster->isNotEmpty()) {
                $boosterStage = $effectiveBooster->first();
                $interval = $this->extractProtocolInterval($boosterStage);
                $nextDoseLabel = 'Reforço';
                $source = 'protocol_booster';
            }
        }

        if ($interval) {
            $nextDate = $this->addInterval($appDate, $interval['value'], $interval['unit']);
            if ($nextDate) {
                $intervalLabel = $this->formatIntervalLabel($interval['value'], $interval['unit']);
                $stageSummary = "{$currentNum}ª dose → {$nextDoseLabel} ({$intervalLabel})";

                return [
                    'calculated' => true,
                    'next_date' => $nextDate->format('Y-m-d'),
                    'next_date_formatted' => $nextDate->format('d/m/Y'),
                    'source' => $source,
                    'protocol_id' => $currentStage->id,
                    'product_id' => $productId ?? $currentStage->product_id,
                    'interval_value' => $interval['value'],
                    'interval_unit' => $interval['unit'],
                    'current_stage' => $stageSummary,
                    'next_dose_label' => $nextDoseLabel,
                    'message' => "Calculada automaticamente pelo protocolo ({$currentStage->vaccine_name} — {$stageSummary}): {$nextDate->format('d/m/Y')}.",
                ];
            }
        }

        return $this->emptyResult($productId, 'Protocolo localizado, porém sem intervalo definido para a próxima etapa. Informe a próxima data manualmente.');
    }

    /**
     * Conta aplicações anteriores válidas do mesmo produto/protocolo para este pet.
     * Considera apenas registros estritamente anteriores à data da aplicação atual (preservando histórico e edição).
     *
     * @param Collection<int, VaccineProtocol> $protocols
     */
    protected function countPreviousApplications(
        ?Pet $pet,
        ?Product $product,
        string $itemName,
        Collection $protocols,
        Carbon $appDate,
        string $context,
        ?int $excludeRecordId
    ): int {
        if (!$pet) {
            return 0;
        }

        $appDateStr = $appDate->format('Y-m-d');
        $protocolNames = $protocols->pluck('vaccine_name')->unique()->values();

        if ($context === 'parasite_control') {
            $q = ParasiteControl::where('pet_id', $pet->id)
                ->whereDate('application_date', '<', $appDateStr);

            if ($excludeRecordId) {
                $q->where('id', '!=', $excludeRecordId);
            }

            $records = $q->get();
            return $records->filter(function (ParasiteControl $rec) use ($product, $itemName, $protocolNames) {
                if ($product && $this->hasColumn('parasite_controls', 'product_id') && (int) $rec->product_id === (int) $product->id) {
                    return true;
                }
                return $this->matchesAnyProtocolName((string) $rec->product_name, $itemName, $protocolNames);
            })->count();
        }

        if ($context === 'medication') {
            $q = MedicationAdministration::where('pet_id', $pet->id)
                ->whereDate('administered_at', '<', $appDateStr);

            if ($excludeRecordId) {
                $q->where('id', '!=', $excludeRecordId);
            }
            if ($product) {
                $q->where('product_id', $product->id);
            }

            return $q->count();
        }

        // Padrão: contexto de vacinação / geral
        $q = Vaccination::where('pet_id', $pet->id)
            ->whereNotNull('date')
            ->whereDate('date', '<', $appDateStr);

        if ($excludeRecordId) {
            $q->where('id', '!=', $excludeRecordId);
        }

        $records = $q->get();
        return $records->filter(function (Vaccination $rec) use ($product, $itemName, $protocolNames) {
            if ($product && $rec->product_id && (int) $rec->product_id === (int) $product->id) {
                return true;
            }
            return $this->matchesAnyProtocolName((string) $rec->vaccine, $itemName, $protocolNames);
        })->count();
    }

    /**
     * @param Collection<int, VaccineProtocol> $stages
     * @return Collection<int, VaccineProtocol>
     */
    protected function filterByAgeWindow(Collection $stages, ?int $ageWeeks): Collection
    {
        if ($ageWeeks === null) {
            return $stages;
        }

        return $stages->filter(function (VaccineProtocol $p) use ($ageWeeks) {
            $minOk = $p->age_start_weeks === null || $ageWeeks >= (int) $p->age_start_weeks;
            $maxOk = $p->age_end_weeks === null || $ageWeeks <= (int) $p->age_end_weeks;
            return $minOk && $maxOk;
        })->values();
    }

    protected function hasExplicitInterval(VaccineProtocol $protocol): bool
    {
        $val = $protocol->getAttribute('interval_value');
        $unit = $protocol->getAttribute('interval_unit');
        return $val !== null && (int) $val > 0 && in_array((string) $unit, self::VALID_UNITS, true);
    }

    /**
     * @return ?array{value: int, unit: string}
     */
    protected function extractProtocolInterval(VaccineProtocol $protocol): ?array
    {
        if ($this->hasExplicitInterval($protocol)) {
            return [
                'value' => (int) $protocol->interval_value,
                'unit' => (string) $protocol->interval_unit,
            ];
        }

        if ((int) ($protocol->booster_interval_months ?? 0) > 0) {
            return [
                'value' => (int) $protocol->booster_interval_months,
                'unit' => 'months',
            ];
        }

        return null;
    }

    protected function hasProductInterval(Product $product): bool
    {
        $val = $product->getAttribute('reapplication_interval');
        $unit = $product->getAttribute('reapplication_unit');
        return $val !== null && (int) $val > 0 && in_array((string) $unit, self::VALID_UNITS, true);
    }

    protected function parseDate(Carbon|string|null $date): ?Carbon
    {
        if ($date instanceof Carbon) {
            return $date->copy()->startOfDay();
        }
        if (!is_string($date) || trim($date) === '') {
            return null;
        }

        try {
            return Carbon::parse(trim($date))->startOfDay();
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function protocolNameMatches(string $protocolName, string $normalizedInput): bool
    {
        $normProto = $this->normalizeText($protocolName);
        if ($normProto === '' || $normalizedInput === '') {
            return false;
        }

        if ($normProto === $normalizedInput) {
            return true;
        }

        // Nome base antes de parênteses: "V8 (Polivalente)" -> "v8", "Tosse dos Canis (Bordetella)" -> "tosse dos canis"
        $baseProto = trim(preg_replace('/\s*\(.*?\)\s*/', ' ', $normProto));
        if ($baseProto !== '' && ($baseProto === $normalizedInput || preg_match('/(^|\b)' . preg_quote($baseProto, '/') . '(\b|$)/', $normalizedInput))) {
            return true;
        }

        // Sinônimos clínicos diretos para protocolos já existentes no banco
        if (str_contains($normProto, 'antirrabica') && (str_contains($normalizedInput, 'raiva') || str_contains($normalizedInput, 'anti rabica') || str_contains($normalizedInput, 'antirrabica'))) {
            return true;
        }
        if (str_contains($normProto, 'bordetella') && (str_contains($normalizedInput, 'bordetella') || str_contains($normalizedInput, 'gripe') || str_contains($normalizedInput, 'tosse dos canis') || str_contains($normalizedInput, 'bronchi'))) {
            return true;
        }

        return false;
    }

    /**
     * @param Collection<int, string> $protocolNames
     */
    protected function matchesAnyProtocolName(string $recordName, string $currentItemName, Collection $protocolNames): bool
    {
        $normRec = $this->normalizeText($recordName);
        if ($normRec === '') {
            return false;
        }

        if ($currentItemName !== '' && $normRec === $this->normalizeText($currentItemName)) {
            return true;
        }

        foreach ($protocolNames as $pName) {
            if ($this->protocolNameMatches($pName, $normRec)) {
                return true;
            }
        }

        return false;
    }

    protected function speciesMatches(string $protocolSpecies, string $petSpecies): bool
    {
        if ($protocolSpecies === $petSpecies) {
            return true;
        }

        $aliases = [
            'canine' => ['canine', 'canino', 'cao', 'cachorro', 'dog'],
            'feline' => ['feline', 'felino', 'gato', 'cat'],
            'equine' => ['equine', 'equino', 'cavalo', 'horse'],
            'bird' => ['bird', 'ave', 'passaro'],
        ];

        foreach ($aliases as $group) {
            if (in_array($protocolSpecies, $group, true) && in_array($petSpecies, $group, true)) {
                return true;
            }
        }

        return false;
    }

    protected function isExplicitBoosterLabel(?string $doseLabel): bool
    {
        if (!$doseLabel) {
            return false;
        }
        $norm = $this->normalizeText($doseLabel);
        return str_contains($norm, 'reforco') || str_contains($norm, 'anual') || str_contains($norm, 'booster');
    }

    protected function parseExplicitDoseNumber(?string $doseLabel): ?int
    {
        if (!$doseLabel) {
            return null;
        }
        if (preg_match('/\b(\d+)\s*(a|ª|º|o)?\s*(dose)?\b/i', trim($doseLabel), $m)) {
            $n = (int) $m[1];
            return ($n >= 1 && $n <= 20) ? $n : null;
        }
        return null;
    }

    protected function normalizeText(string $value): string
    {
        $ascii = Str::ascii(mb_strtolower(trim($value)));
        $clean = preg_replace('/[^a-z0-9\s\(\)]+/', ' ', $ascii);
        return trim(preg_replace('/\s+/', ' ', (string) $clean));
    }

    protected function formatIntervalLabel(int $value, string $unit): string
    {
        return match ($unit) {
            'days' => $value === 1 ? '1 dia' : "{$value} dias",
            'weeks' => $value === 1 ? '1 semana' : "{$value} semanas",
            'months' => $value === 1 ? '1 mês' : "{$value} meses",
            default => "{$value} {$unit}",
        };
    }

    protected function hasColumn(string $table, string $column): bool
    {
        $key = "{$table}.{$column}";
        if (!array_key_exists($key, self::$columnCache)) {
            self::$columnCache[$key] = Schema::hasColumn($table, $column);
        }
        return self::$columnCache[$key];
    }

    public static function clearSchemaCache(): void
    {
        self::$columnCache = [];
    }

    protected function emptyResult(?int $productId, string $message): array
    {
        return [
            'calculated' => false,
            'next_date' => null,
            'next_date_formatted' => null,
            'source' => 'none',
            'protocol_id' => null,
            'product_id' => $productId,
            'interval_value' => null,
            'interval_unit' => null,
            'current_stage' => null,
            'next_dose_label' => null,
            'message' => $message,
        ];
    }
}
