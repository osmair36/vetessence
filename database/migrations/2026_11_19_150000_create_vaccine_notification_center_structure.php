<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Evolucao da tabela vaccinations (status de acompanhamento, conclusao e contato)
        Schema::table('vaccinations', function (Blueprint $table) {
            if (!Schema::hasColumn('vaccinations', 'followup_status')) {
                $table->string('followup_status', 20)->default('pending');
            }
            if (!Schema::hasColumn('vaccinations', 'completed_by_vaccination_id')) {
                $table->unsignedBigInteger('completed_by_vaccination_id')->nullable();
            }
            if (!Schema::hasColumn('vaccinations', 'last_contact_at')) {
                $table->timestamp('last_contact_at')->nullable();
            }
            if (!Schema::hasColumn('vaccinations', 'last_contact_notes')) {
                $table->text('last_contact_notes')->nullable();
            }
        });

        // Indices para consultas instantaneas no Dashboard e na rotina diaria
        $this->addIndexIfMissing('vaccinations', ['followup_status', 'next_date'], 'idx_vaccinations_followup_next_date');
        $this->addIndexIfMissing('vaccinations', ['pet_id', 'vaccine', 'date'], 'idx_vaccinations_pet_vaccine_date');

        // 2. Evolucao da tabela vaccination_reminders (idempotencia, etapas, rastreio de entrega/leitura)
        Schema::table('vaccination_reminders', function (Blueprint $table) {
            if (!Schema::hasColumn('vaccination_reminders', 'tutor_id')) {
                $table->unsignedBigInteger('tutor_id')->nullable();
            }
            if (!Schema::hasColumn('vaccination_reminders', 'reminder_type')) {
                $table->string('reminder_type', 30)->default('manual');
            }
            if (!Schema::hasColumn('vaccination_reminders', 'reference_date')) {
                $table->date('reference_date')->nullable();
            }
            if (!Schema::hasColumn('vaccination_reminders', 'attempt_number')) {
                $table->unsignedTinyInteger('attempt_number')->default(1);
            }
            if (!Schema::hasColumn('vaccination_reminders', 'idempotency_key')) {
                $table->string('idempotency_key', 140)->nullable();
            }
            if (!Schema::hasColumn('vaccination_reminders', 'destination')) {
                $table->string('destination', 100)->nullable();
            }
            if (!Schema::hasColumn('vaccination_reminders', 'message_body')) {
                $table->text('message_body')->nullable();
            }
            if (!Schema::hasColumn('vaccination_reminders', 'provider')) {
                $table->string('provider', 50)->nullable();
            }
            if (!Schema::hasColumn('vaccination_reminders', 'provider_message_id')) {
                $table->string('provider_message_id', 140)->nullable();
            }
            if (!Schema::hasColumn('vaccination_reminders', 'delivered_at')) {
                $table->timestamp('delivered_at')->nullable();
            }
            if (!Schema::hasColumn('vaccination_reminders', 'read_at')) {
                $table->timestamp('read_at')->nullable();
            }
            if (!Schema::hasColumn('vaccination_reminders', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable();
            }
            if (!Schema::hasColumn('vaccination_reminders', 'cancelled_reason')) {
                $table->string('cancelled_reason', 150)->nullable();
            }
            if (!Schema::hasColumn('vaccination_reminders', 'sent_by_user_id')) {
                $table->unsignedBigInteger('sent_by_user_id')->nullable();
            }
            if (!Schema::hasColumn('vaccination_reminders', 'branch_id')) {
                $table->unsignedBigInteger('branch_id')->nullable();
            }
        });

        $this->addUniqueIndexIfMissing('vaccination_reminders', ['idempotency_key'], 'uq_vaccination_reminders_idempotency');
        $this->addIndexIfMissing('vaccination_reminders', ['status', 'scheduled_date'], 'idx_vac_reminders_status_sched');
        $this->addIndexIfMissing('vaccination_reminders', ['vaccination_id', 'status'], 'idx_vac_reminders_vac_status');
        $this->addIndexIfMissing('vaccination_reminders', ['provider_message_id'], 'idx_vac_reminders_provider_msg');
        $this->addIndexIfMissing('vaccination_reminders', ['tutor_id'], 'idx_vac_reminders_tutor');

        // 3. Consentimento explicito de WhatsApp em tutors (LGPD)
        Schema::table('tutors', function (Blueprint $table) {
            if (!Schema::hasColumn('tutors', 'whatsapp_opt_in')) {
                $table->boolean('whatsapp_opt_in')->nullable()->default(null);
            }
            if (!Schema::hasColumn('tutors', 'whatsapp_opt_in_at')) {
                $table->timestamp('whatsapp_opt_in_at')->nullable();
            }
            if (!Schema::hasColumn('tutors', 'whatsapp_opt_out_at')) {
                $table->timestamp('whatsapp_opt_out_at')->nullable();
            }
            if (!Schema::hasColumn('tutors', 'whatsapp_opt_in_source')) {
                $table->string('whatsapp_opt_in_source', 50)->nullable();
            }
        });

        // 4. Backfill O(N) de followup_status nas vacinacoes existentes
        $this->backfillFollowupStatus();

        // 5. Configuracoes padrao na tabela settings (auto_send desativado por seguranca inicial)
        $this->seedDefaultSettings();
    }

    public function down(): void
    {
        Schema::table('tutors', function (Blueprint $table) {
            foreach (['whatsapp_opt_in_source', 'whatsapp_opt_out_at', 'whatsapp_opt_in_at', 'whatsapp_opt_in'] as $col) {
                if (Schema::hasColumn('tutors', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('vaccination_reminders', function (Blueprint $table) {
            foreach ([
                'branch_id', 'sent_by_user_id', 'cancelled_reason', 'cancelled_at',
                'read_at', 'delivered_at', 'provider_message_id', 'provider',
                'message_body', 'destination', 'idempotency_key', 'attempt_number',
                'reference_date', 'reminder_type', 'tutor_id',
            ] as $col) {
                if (Schema::hasColumn('vaccination_reminders', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('vaccinations', function (Blueprint $table) {
            foreach (['last_contact_notes', 'last_contact_at', 'completed_by_vaccination_id', 'followup_status'] as $col) {
                if (Schema::hasColumn('vaccinations', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }

    protected function backfillFollowupStatus(): void
    {
        // Primeiro marca quem nao tem next_date como 'none'
        DB::table('vaccinations')->whereNull('next_date')->update(['followup_status' => 'none']);

        $families = [
            '001' => 'V10', '002' => 'V10', '003' => 'V10',
            '005' => 'GRIPE', '006' => 'GRIPE', '055' => 'GRIPE', '35' => 'GRIPE',
            '007' => 'GIARDIA', '008' => 'GIARDIA',
            '009' => 'FELOCEL_V4', '010' => 'FELOCEL_V4',
            '035' => 'QUINTUPLA_FELINA', '037' => 'QUINTUPLA_FELINA',
            '013' => 'FELOCELL_FELV', '014' => 'FELOCELL_FELV',
            '011' => 'VERMIFUGO', '032' => 'VERMIFUGO', '33' => 'VERMIFUGO',
        ];

        $pdo = DB::connection()->getPdo();
        $stmt = $pdo->query("SELECT id, pet_id, vaccine, sisvetw_cod_vac, date, next_date FROM vaccinations ORDER BY pet_id ASC, date ASC, id ASC");

        $groups = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $cod = $row['sisvetw_cod_vac'] ?? '';
            $fam = ($cod !== '' && isset($families[$cod]))
                ? $families[$cod]
                : mb_strtoupper(trim((string) $row['vaccine']));
            $groups[$row['pet_id'] . '|' . $fam][] = $row;
        }

        $completedIds = []; // id => next_vaccination_id
        foreach ($groups as $rows) {
            $n = count($rows);
            for ($i = 0; $i < $n; $i++) {
                if (empty($rows[$i]['next_date'])) {
                    continue;
                }
                for ($j = $i + 1; $j < $n; $j++) {
                    if ($rows[$j]['date'] > $rows[$i]['date']) {
                        $completedIds[(int) $rows[$i]['id']] = (int) $rows[$j]['id'];
                        break;
                    }
                }
            }
        }

        if (!empty($completedIds)) {
            $pdo->beginTransaction();
            $upd = $pdo->prepare("UPDATE vaccinations SET followup_status = 'completed', completed_by_vaccination_id = ? WHERE id = ?");
            foreach ($completedIds as $id => $byId) {
                $upd->execute([$byId, $id]);
            }
            $pdo->commit();
        }
    }

    protected function seedDefaultSettings(): void
    {
        if (!Schema::hasTable('settings')) {
            return;
        }

        $defaults = [
            'vaccine_reminders.auto_send_enabled' => '0',
            'vaccine_reminders.remind_7_days_enabled' => '1',
            'vaccine_reminders.remind_due_today_enabled' => '1',
            'vaccine_reminders.remind_overdue_enabled' => '1',
            'vaccine_reminders.overdue_interval_days' => '7',
            'vaccine_reminders.max_overdue_attempts' => '3',
            'vaccine_reminders.overdue_max_age_days' => '180',
            'vaccine_reminders.daily_run_time' => '09:00',
            'vaccine_reminders.template_7_days' => "Olá, {NOME_TUTOR}! Tudo bem? 🐾\n\nEstamos passando para lembrar que a vacinação do seu pet {NOME_ANIMAL} está prevista para daqui a 7 dias.\n\n💉 Vacina: {NOME_VACINA}\n\n📅 Data prevista: {DATA_VACINACAO}\n\nÉ importante manter o acompanhamento da vacinação do seu companheiro em dia.\n\nEntre em contato conosco para agendar a aplicação!\n\nSerá um prazer receber vocês! ❤️",
            'vaccine_reminders.template_due_today' => "Olá, {NOME_TUTOR}! Tudo bem? 🐾\n\nHoje está prevista a vacinação do seu pet {NOME_ANIMAL}.\n\n💉 Vacina: {NOME_VACINA}\n\n📅 Data prevista: {DATA_VACINACAO}\n\nEstamos à disposição para agendar a aplicação e manter a saúde do seu companheiro em dia.\n\nEntre em contato conosco!",
            'vaccine_reminders.template_overdue' => "Olá, {NOME_TUTOR}! Tudo bem? 🐾\n\nIdentificamos que a vacinação do seu pet {NOME_ANIMAL} ainda consta como pendente em nosso sistema.\n\n💉 Vacina: {NOME_VACINA}\n\n📅 Data prevista: {DATA_VACINACAO}\n\nGostaríamos de lembrar que é importante manter o calendário de vacinação atualizado.\n\nEntre em contato conosco para verificar a situação e agendar o atendimento.\n\nAguardamos vocês! ❤️",
        ];

        $now = now()->format('Y-m-d H:i:s');
        foreach ($defaults as $key => $value) {
            $exists = DB::table('settings')->where('key', $key)->exists();
            if (!$exists) {
                DB::table('settings')->insert([
                    'key' => $key,
                    'value' => $value,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    protected function addIndexIfMissing(string $table, array $columns, string $name): void
    {
        try {
            Schema::table($table, function (Blueprint $t) use ($columns, $name) {
                $t->index($columns, $name);
            });
        } catch (\Throwable $e) {
            // Indice ja existe
        }
    }

    protected function addUniqueIndexIfMissing(string $table, array $columns, string $name): void
    {
        try {
            Schema::table($table, function (Blueprint $t) use ($columns, $name) {
                $t->unique($columns, $name);
            });
        } catch (\Throwable $e) {
            // Indice ja existe
        }
    }
};
