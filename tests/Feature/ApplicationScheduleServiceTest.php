<?php

namespace Tests\Feature;

use App\Models\MedicationAdministration;
use App\Models\ParasiteControl;
use App\Models\Pet;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Models\Vaccination;
use App\Models\VaccineProtocol;
use App\Services\ApplicationScheduleService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Suite completa de testes para cálculo automático de próximas aplicações
 * de vacinas, antiparasitários, vermífugos e medicamentos periódicos.
 *
 * Executa 100% em banco SQLite isolado em memória (:memory:),
 * sem depender de dados de produção e sem tocar no banco SQLite de produção.
 */
class ApplicationScheduleServiceTest extends TestCase
{
    protected ApplicationScheduleService $service;
    protected User $vet;

    protected function setUp(): void
    {
        parent::setUp();

        // Força conexão SQLite isolada em memória (:memory:) — nunca toca o banco de produção
        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', ':memory:');
        Config::set('database.connections.sqlite.foreign_key_constraints', true);
        DB::purge('sqlite');
        DB::reconnect('sqlite');
        ApplicationScheduleService::clearSchemaCache();

        $this->createBaseSchemaForSqlite();

        // Executa a migration real da funcionalidade em cima do schema base SQLite
        $migration = require database_path('migrations/2026_11_23_200000_add_application_schedule_protocol_fields.php');
        $migration->up();
        ApplicationScheduleService::clearSchemaCache();

        $this->service = app(ApplicationScheduleService::class);

        $role = Role::create([
            'name' => 'Veterinário',
            'slug' => 'veterinario',
            'description' => 'Veterinário teste',
        ]);

        $this->vet = User::create([
            'name' => 'Dra. Teste Vet',
            'email' => 'vet.schedule@example.test',
            'password' => bcrypt('secret123'),
            'role_id' => $role->id,
            'is_active' => true,
            'is_veterinarian' => true,
        ]);

        // Autoriza gates no ambiente de teste isolado
        Gate::before(fn () => true);
    }

    protected function createBaseSchemaForSqlite(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('guard_name')->default('web');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->unsignedBigInteger('role_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_veterinarian')->default(false);
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('pets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('species')->nullable();
            $table->string('breed')->nullable();
            $table->string('gender')->nullable();
            $table->date('birth_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_at_branch_id')->nullable();
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->string('sku')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('cost_price', 10, 2)->default(0);
            $table->decimal('sale_price', 10, 2)->default(0);
            $table->integer('stock')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('vaccine_protocols', function (Blueprint $table) {
            $table->id();
            $table->string('species', 50);
            $table->string('vaccine_name', 200);
            $table->integer('age_start_weeks')->nullable();
            $table->integer('age_end_weeks')->nullable();
            $table->boolean('is_initial')->default(false);
            $table->integer('dose_number')->nullable();
            $table->integer('booster_interval_months')->nullable();
            $table->boolean('is_core')->default(false);
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('vaccinations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pet_id');
            $table->unsignedBigInteger('vet_id')->nullable();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->string('vaccine', 100);
            $table->string('batch', 50)->nullable();
            $table->string('lot', 50)->nullable();
            $table->string('laboratory', 100)->nullable();
            $table->string('manufacturer', 100)->nullable();
            $table->string('dose', 50)->nullable();
            $table->date('date')->nullable();
            $table->date('next_date')->nullable();
            $table->string('status', 30)->default('applied');
            $table->string('followup_status', 20)->default('pending');
            $table->unsignedBigInteger('completed_by_vaccination_id')->nullable();
            $table->timestamp('last_contact_at')->nullable();
            $table->text('last_contact_notes')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->timestamps();
        });

        Schema::create('vaccination_reminders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vaccination_id')->nullable();
            $table->unsignedBigInteger('pet_id')->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancelled_reason', 150)->nullable();
            $table->timestamps();
        });

        Schema::create('parasite_controls', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pet_id');
            $table->string('product_name', 200);
            $table->string('active_ingredient', 200)->nullable();
            $table->string('type', 50);
            $table->date('application_date');
            $table->date('next_due_date')->nullable();
            $table->string('dose', 100)->nullable();
            $table->string('batch', 100)->nullable();
            $table->unsignedBigInteger('vet_id');
            $table->unsignedBigInteger('medical_record_id')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->timestamps();
        });

        Schema::create('medication_administrations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('pet_id')->nullable();
            $table->unsignedBigInteger('prescribed_by')->nullable();
            $table->unsignedBigInteger('administered_by')->nullable();
            $table->decimal('quantity_prescribed', 10, 4)->default(1);
            $table->decimal('quantity_administered', 10, 4)->default(1);
            $table->string('unit', 30)->default('un');
            $table->string('route', 50)->nullable();
            $table->string('status', 30)->default('administered');
            $table->text('notes')->nullable();
            $table->timestamp('administered_at')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->timestamps();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('guard_name')->default('web');
            $table->timestamps();
        });

        Schema::create('role_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');
        });

        Schema::create('model_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
        });

        Schema::create('model_has_roles', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
        });
    }

    protected function makePet(array $overrides = []): Pet
    {
        return Pet::create(array_merge([
            'name' => 'Thor',
            'species' => 'canine',
            'gender' => 'male',
            'birth_date' => '2024-01-10',
            'is_active' => true,
        ], $overrides));
    }

    protected function makeProduct(array $overrides = []): Product
    {
        return Product::create(array_merge([
            'sku' => 'SKU-' . uniqid(),
            'name' => 'Produto Teste',
            'cost_price' => 50,
            'sale_price' => 100,
            'stock' => 10,
            'is_active' => true,
        ], $overrides));
    }

    // 1. Produto com protocolo de 30 dias
    public function test_01_product_with_30_days_protocol(): void
    {
        $pet = $this->makePet();
        $product = $this->makeProduct([
            'name' => 'NexGard 28mg',
            'active_ingredient' => 'Afoxolaner',
            'reapplication_interval' => 30,
            'reapplication_unit' => 'days',
        ]);

        $res = $this->service->calculateNextApplication('2026-06-10', $product, $pet, $product->name, 'parasite_control');

        $this->assertTrue($res['calculated']);
        $this->assertEquals('2026-07-10', $res['next_date']);
        $this->assertEquals('10/07/2026', $res['next_date_formatted']);
        $this->assertEquals('product_interval', $res['source']);
        $this->assertEquals(30, $res['interval_value']);
        $this->assertEquals('days', $res['interval_unit']);
    }

    // 2. Produto com protocolo de 60 dias
    public function test_02_product_with_60_days_protocol(): void
    {
        $pet = $this->makePet();
        $product = $this->makeProduct([
            'name' => 'Antiparasitário 60d',
            'reapplication_interval' => 60,
            'reapplication_unit' => 'days',
        ]);

        $res = $this->service->calculateNextApplication('2026-06-10', $product, $pet, $product->name, 'parasite_control');

        $this->assertTrue($res['calculated']);
        $this->assertEquals('2026-08-09', $res['next_date']);
        $this->assertEquals(60, $res['interval_value']);
        $this->assertEquals('days', $res['interval_unit']);
    }

    // 3. Produto com intervalo de 3 meses (incluindo mês mais curto: 31/01 -> 30/04 sem overflow)
    public function test_03_product_with_3_months_interval_handles_short_months(): void
    {
        $pet = $this->makePet();
        $product = $this->makeProduct([
            'name' => 'Vermífugo Trimestral',
            'reapplication_interval' => 3,
            'reapplication_unit' => 'months',
        ]);

        $resShortMonth = $this->service->calculateNextApplication('2026-01-31', $product, $pet, $product->name, 'parasite_control');
        $this->assertTrue($resShortMonth['calculated']);
        $this->assertEquals('2026-04-30', $resShortMonth['next_date']);

        $resLeapCheck = $this->service->calculateNextApplication('2026-11-30', $product, $pet, $product->name, 'parasite_control');
        $this->assertTrue($resLeapCheck['calculated']);
        $this->assertEquals('2027-02-28', $resLeapCheck['next_date']);
    }

    // 4. Produto sem protocolo cadastrado (não inventa periodicidade e permite preenchimento manual)
    public function test_04_product_without_protocol_does_not_invent_date_and_allows_manual(): void
    {
        $pet = $this->makePet();
        $product = $this->makeProduct(['name' => 'Suplemento Vitamínico Sem Protocolo']);

        $res = $this->service->calculateNextApplication('2026-06-10', $product, $pet, $product->name, 'parasite_control');
        $this->assertFalse($res['calculated']);
        $this->assertNull($res['next_date']);
        $this->assertEquals('none', $res['source']);

        // Deve permitir salvar manualmente pelo controller
        $response = $this->actingAs($this->vet)->post(route('parasite-controls.store'), [
            'pet_id' => $pet->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'type' => 'combination',
            'application_date' => '2026-06-10',
            'next_due_date' => '2026-09-15',
            'next_due_date_manual' => '1',
            'vet_id' => $this->vet->id,
        ]);

        $response->assertRedirect(route('parasite-controls.index'));
        $this->assertEquals('2026-09-15', ParasiteControl::first()->next_due_date->format('Y-m-d'));
    }

    // 5. Preenchimento automático ao informar a data atual (AJAX + persistência backend)
    public function test_05_auto_fill_via_endpoint_and_backend_store(): void
    {
        $pet = $this->makePet();
        $product = $this->makeProduct([
            'name' => 'Simparic 20mg',
            'active_ingredient' => 'Sarolaner',
            'reapplication_interval' => 35,
            'reapplication_unit' => 'days',
        ]);

        // Via endpoint AJAX dinâmico
        $ajax = $this->actingAs($this->vet)->postJson(route('application-schedule.calculate'), [
            'application_date' => '2026-06-10',
            'product_id' => $product->id,
            'pet_id' => $pet->id,
            'context' => 'parasite_control',
        ]);

        $ajax->assertOk()
            ->assertJson([
                'calculated' => true,
                'next_date' => '2026-07-15',
                'next_date_formatted' => '15/07/2026',
            ]);

        // Via backend store sem enviar next_due_date (backend calcula de forma confiável)
        $this->actingAs($this->vet)->post(route('parasite-controls.store'), [
            'pet_id' => $pet->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'type' => 'combination',
            'application_date' => '2026-06-10',
            'next_due_date' => null,
            'next_due_date_manual' => '0',
            'vet_id' => $this->vet->id,
        ])->assertRedirect();

        $saved = ParasiteControl::latest('id')->first();
        $this->assertEquals('2026-07-15', $saved->next_due_date->format('Y-m-d'));
    }

    // 6 e 7. Alteração manual da próxima data e preservação da alteração manual
    public function test_06_and_07_manual_override_prevails_and_is_preserved(): void
    {
        $pet = $this->makePet();
        $product = $this->makeProduct([
            'name' => 'Antipulgas 30 Dias',
            'reapplication_interval' => 30,
            'reapplication_unit' => 'days',
        ]);

        // Usuário altera manualmente a data sugerida (10/07 -> 20/07)
        $this->actingAs($this->vet)->post(route('parasite-controls.store'), [
            'pet_id' => $pet->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'type' => 'flea',
            'application_date' => '2026-06-10',
            'next_due_date' => '2026-07-20',
            'next_due_date_manual' => '1',
            'vet_id' => $this->vet->id,
        ])->assertRedirect();

        $saved = ParasiteControl::first();
        $this->assertEquals('2026-07-20', $saved->next_due_date->format('Y-m-d'));

        // Se o usuário limpar manualmente a data (next_due_date = null + manual = true), não sobrescreve!
        $resolvedCleared = $this->service->resolveNextDateForPersistence(
            null,
            true,
            '2026-06-10',
            $product,
            $pet,
            $product->name,
            'parasite_control'
        );
        $this->assertNull($resolvedCleared);
    }

    // 8 e 9. Atualização da sugestão quando o produto ou a data atual são alterados
    public function test_08_and_09_suggestion_updates_when_product_or_date_changes(): void
    {
        $pet = $this->makePet();
        $prod30 = $this->makeProduct(['name' => 'Prod 30d', 'reapplication_interval' => 30, 'reapplication_unit' => 'days']);
        $prod90 = $this->makeProduct(['name' => 'Prod 90d', 'reapplication_interval' => 90, 'reapplication_unit' => 'days']);

        // Produto A na data 2026-06-01
        $r1 = $this->actingAs($this->vet)->postJson(route('application-schedule.calculate'), [
            'application_date' => '2026-06-01',
            'product_id' => $prod30->id,
            'pet_id' => $pet->id,
        ])->assertOk()->json();
        $this->assertEquals('2026-07-01', $r1['next_date']);

        // Alterou o produto para B (90 dias) na mesma data
        $r2 = $this->actingAs($this->vet)->postJson(route('application-schedule.calculate'), [
            'application_date' => '2026-06-01',
            'product_id' => $prod90->id,
            'pet_id' => $pet->id,
        ])->assertOk()->json();
        $this->assertEquals('2026-08-30', $r2['next_date']);

        // Alterou a data atual para 2026-06-15 mantendo produto B
        $r3 = $this->actingAs($this->vet)->postJson(route('application-schedule.calculate'), [
            'application_date' => '2026-06-15',
            'product_id' => $prod90->id,
            'pet_id' => $pet->id,
        ])->assertOk()->json();
        $this->assertEquals('2026-09-13', $r3['next_date']);
    }

    // 10. Protocolo com várias doses (sequência heterogênea: 1ª->2ª +14d, 2ª->3ª +30d, 3ª->Reforço +12m)
    public function test_10_multi_dose_protocol_respects_registered_sequence_and_history(): void
    {
        $pet = $this->makePet(['birth_date' => '2026-04-01']);
        $product = $this->makeProduct(['name' => 'Vacina Multi-Etapa']);

        // Cadastra sequência: 1ª dose (+14 dias), 2ª dose (+30 dias), 3ª dose (reforço 12 meses)
        VaccineProtocol::create([
            'product_id' => $product->id,
            'protocol_type' => 'vaccine',
            'species' => 'canine',
            'vaccine_name' => 'Vacina Multi-Etapa',
            'is_initial' => true,
            'dose_number' => 1,
            'interval_value' => 14,
            'interval_unit' => 'days',
            'is_active' => true,
        ]);
        VaccineProtocol::create([
            'product_id' => $product->id,
            'protocol_type' => 'vaccine',
            'species' => 'canine',
            'vaccine_name' => 'Vacina Multi-Etapa',
            'is_initial' => true,
            'dose_number' => 2,
            'interval_value' => 30,
            'interval_unit' => 'days',
            'is_active' => true,
        ]);
        VaccineProtocol::create([
            'product_id' => $product->id,
            'protocol_type' => 'vaccine',
            'species' => 'canine',
            'vaccine_name' => 'Vacina Multi-Etapa',
            'is_initial' => true,
            'dose_number' => 3,
            'booster_interval_months' => 12,
            'is_active' => true,
        ]);

        // 1ª aplicação em 01/06/2026 -> 2ª dose em 15/06/2026 (+14 dias)
        $calc1 = $this->service->calculateNextApplication('2026-06-01', $product, $pet, $product->name, 'vaccination');
        $this->assertTrue($calc1['calculated']);
        $this->assertEquals('2026-06-15', $calc1['next_date']);
        $this->assertEquals('2ª dose', $calc1['next_dose_label']);

        // Registra a 1ª aplicação no histórico
        Vaccination::create([
            'pet_id' => $pet->id,
            'vet_id' => $this->vet->id,
            'product_id' => $product->id,
            'vaccine' => $product->name,
            'date' => '2026-06-01',
            'next_date' => '2026-06-15',
        ]);

        // 2ª aplicação em 15/06/2026 -> 3ª dose em 15/07/2026 (+30 dias)
        $calc2 = $this->service->calculateNextApplication('2026-06-15', $product, $pet, $product->name, 'vaccination');
        $this->assertTrue($calc2['calculated']);
        $this->assertEquals('2026-07-15', $calc2['next_date']);
        $this->assertEquals('3ª dose', $calc2['next_dose_label']);

        // Registra a 2ª aplicação no histórico
        Vaccination::create([
            'pet_id' => $pet->id,
            'vet_id' => $this->vet->id,
            'product_id' => $product->id,
            'vaccine' => $product->name,
            'date' => '2026-06-15',
            'next_date' => '2026-07-15',
        ]);

        // 3ª aplicação em 15/07/2026 -> Reforço em 15/07/2027 (+12 meses)
        $calc3 = $this->service->calculateNextApplication('2026-07-15', $product, $pet, $product->name, 'vaccination');
        $this->assertTrue($calc3['calculated']);
        $this->assertEquals('2027-07-15', $calc3['next_date']);
        $this->assertEquals('Reforço', $calc3['next_dose_label']);
    }

    // 11. Protocolo específico por espécie
    public function test_11_species_specific_protocol_applies_only_to_matching_species(): void
    {
        $dog = $this->makePet(['name' => 'Rex', 'species' => 'canine']);
        $cat = $this->makePet(['name' => 'Mimi', 'species' => 'feline']);
        $bird = $this->makePet(['name' => 'Louro', 'species' => 'bird']);

        $product = $this->makeProduct(['name' => 'Antiparasitário Espécie-Específico']);

        // Cães: 30 dias | Gatos: 45 dias | Aves: sem protocolo
        VaccineProtocol::create([
            'product_id' => $product->id,
            'protocol_type' => 'antiparasitic',
            'species' => 'canine',
            'vaccine_name' => $product->name,
            'is_initial' => false,
            'interval_value' => 30,
            'interval_unit' => 'days',
            'is_active' => true,
        ]);
        VaccineProtocol::create([
            'product_id' => $product->id,
            'protocol_type' => 'antiparasitic',
            'species' => 'feline',
            'vaccine_name' => $product->name,
            'is_initial' => false,
            'interval_value' => 45,
            'interval_unit' => 'days',
            'is_active' => true,
        ]);

        $dogRes = $this->service->calculateNextApplication('2026-06-01', $product, $dog, $product->name, 'parasite_control');
        $catRes = $this->service->calculateNextApplication('2026-06-01', $product, $cat, $product->name, 'parasite_control');
        $birdRes = $this->service->calculateNextApplication('2026-06-01', $product, $bird, $product->name, 'parasite_control');

        $this->assertEquals('2026-07-01', $dogRes['next_date']);
        $this->assertEquals('2026-07-16', $catRes['next_date']);
        $this->assertFalse($birdRes['calculated']);
        $this->assertNull($birdRes['next_date']);
    }

    // 12. Cálculo considerando a idade do animal (filhote série inicial vs adulto reforço anual)
    public function test_12_calculation_respects_pet_age_puppy_vs_adult(): void
    {
        // Protocolo V8 no formato exato já existente no banco do VetEssence
        VaccineProtocol::create([
            'species' => 'canine', 'vaccine_name' => 'V8 (Polivalente)',
            'age_start_weeks' => 6, 'age_end_weeks' => 8, 'is_initial' => true, 'dose_number' => 1, 'booster_interval_months' => null, 'is_active' => true,
        ]);
        VaccineProtocol::create([
            'species' => 'canine', 'vaccine_name' => 'V8 (Polivalente)',
            'age_start_weeks' => 9, 'age_end_weeks' => 11, 'is_initial' => true, 'dose_number' => 2, 'booster_interval_months' => null, 'is_active' => true,
        ]);
        VaccineProtocol::create([
            'species' => 'canine', 'vaccine_name' => 'V8 (Polivalente)',
            'age_start_weeks' => 12, 'age_end_weeks' => 16, 'is_initial' => true, 'dose_number' => 3, 'booster_interval_months' => null, 'is_active' => true,
        ]);
        VaccineProtocol::create([
            'species' => 'canine', 'vaccine_name' => 'V8 (Polivalente)',
            'age_start_weeks' => null, 'age_end_weeks' => null, 'is_initial' => false, 'dose_number' => null, 'booster_interval_months' => 12, 'is_active' => true,
        ]);

        // Filhote com 7 semanas na data da aplicação -> recebe 1ª dose, próxima em 3 semanas (21 dias)
        $puppy = $this->makePet(['name' => 'Filhote', 'species' => 'canine', 'birth_date' => '2026-04-15']);
        $resPuppy = $this->service->calculateNextApplication('2026-06-03', null, $puppy, 'V8 (Polivalente)', 'vaccination');
        $this->assertTrue($resPuppy['calculated']);
        $this->assertEquals('2026-06-24', $resPuppy['next_date']); // +3 semanas
        $this->assertEquals('protocol_dose_sequence', $resPuppy['source']);

        // Cão adulto com 2 anos na data da aplicação -> entra direto no reforço anual (+12 meses)
        $adult = $this->makePet(['name' => 'Adulto', 'species' => 'canine', 'birth_date' => '2024-06-01']);
        $resAdult = $this->service->calculateNextApplication('2026-06-03', null, $adult, 'V8 (Polivalente)', 'vaccination');
        $this->assertTrue($resAdult['calculated']);
        $this->assertEquals('2027-06-03', $resAdult['next_date']); // +12 meses
        $this->assertEquals('protocol_booster', $resAdult['source']);
    }

    // 13 e 14. Edição de aplicação já registrada e preservação do histórico de aplicações
    public function test_13_and_14_editing_existing_application_preserves_history_and_future_records(): void
    {
        $pet = $this->makePet();
        $product = $this->makeProduct([
            'name' => 'Vacina Anual',
            'reapplication_interval' => 12,
            'reapplication_unit' => 'months',
        ]);

        $app1 = Vaccination::create([
            'pet_id' => $pet->id,
            'vet_id' => $this->vet->id,
            'product_id' => $product->id,
            'vaccine' => 'Vacina Anual',
            'date' => '2025-06-10',
            'next_date' => '2026-06-10',
            'notes' => 'Primeira aplicação histórica',
        ]);

        $app2 = Vaccination::create([
            'pet_id' => $pet->id,
            'vet_id' => $this->vet->id,
            'product_id' => $product->id,
            'vaccine' => 'Vacina Anual',
            'date' => '2026-06-10',
            'next_date' => '2027-06-10',
            'notes' => 'Segunda aplicação futura já registrada',
        ]);

        // Edita observação da aplicação 1 preservando sua data original
        $this->actingAs($this->vet)->put(route('vaccinations.update', $app1), [
            'vaccine' => 'Vacina Anual',
            'date' => '2025-06-10',
            'next_date' => '2026-06-10',
            'vet_id' => $this->vet->id,
            'product_id' => $product->id,
            'notes' => 'Observação atualizada',
        ])->assertRedirect(route('vaccinations.index'));

        $app1->refresh();
        $app2->refresh();

        // Histórico preservado: ainda existem exatamente 2 registros, sem duplicatas
        $this->assertEquals(2, Vaccination::where('pet_id', $pet->id)->count());
        $this->assertEquals('2025-06-10', $app1->date->format('Y-m-d'));
        $this->assertEquals('2026-06-10', $app1->next_date->format('Y-m-d'));
        // Aplicação futura já registrada não foi alterada indevidamente
        $this->assertEquals('2026-06-10', $app2->date->format('Y-m-d'));
        $this->assertEquals('2027-06-10', $app2->next_date->format('Y-m-d'));
    }

    // 15. Compatibilidade com SQLite (inclusive MedicationAdministration e reversibilidade da migration)
    public function test_15_sqlite_compatibility_medication_administration_and_reversible_migration(): void
    {
        $this->assertEquals('sqlite', DB::getDriverName());

        $pet = $this->makePet();
        $med = $this->makeProduct([
            'name' => 'Medicamento Periódico 14 Dias',
            'reapplication_interval' => 14,
            'reapplication_unit' => 'days',
        ]);

        $calc = $this->service->calculateNextApplication('2026-06-01', $med, $pet, $med->name, 'medication');
        $this->assertTrue($calc['calculated']);
        $this->assertEquals('2026-06-15', $calc['next_date']);

        $admin = MedicationAdministration::create([
            'product_id' => $med->id,
            'pet_id' => $pet->id,
            'administered_by' => $this->vet->id,
            'quantity_prescribed' => 1,
            'quantity_administered' => 1,
            'unit' => 'ml',
            'administered_at' => Carbon::parse('2026-06-01 10:00:00'),
            'next_application_date' => $calc['next_date'],
        ]);

        $this->assertEquals('2026-06-15', $admin->fresh()->next_application_date->format('Y-m-d'));

        // Verifica que a migration é reversível (down -> up) no SQLite sem erro
        $migration = require database_path('migrations/2026_11_23_200000_add_application_schedule_protocol_fields.php');
        $migration->down();
        $this->assertFalse(Schema::hasColumn('products', 'reapplication_interval'));
        $this->assertFalse(Schema::hasColumn('vaccine_protocols', 'interval_value'));
        $this->assertFalse(Schema::hasColumn('parasite_controls', 'product_id'));
        $this->assertFalse(Schema::hasColumn('medication_administrations', 'next_application_date'));

        $migration->up();
        $this->assertTrue(Schema::hasColumn('products', 'reapplication_interval'));
        $this->assertTrue(Schema::hasColumn('vaccine_protocols', 'interval_value'));
        $this->assertTrue(Schema::hasColumn('parasite_controls', 'product_id'));
        $this->assertTrue(Schema::hasColumn('medication_administrations', 'next_application_date'));
    }
}
