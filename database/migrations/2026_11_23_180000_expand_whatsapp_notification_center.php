<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Expandir vaccination_reminders para suportar Vacinas, Aniversariantes e Banho e Tosa
        Schema::table('vaccination_reminders', function (Blueprint $table) {
            if (!Schema::hasColumn('vaccination_reminders', 'category')) {
                $table->string('category', 30)->default('vaccination')->after('id');
            }
            if (!Schema::hasColumn('vaccination_reminders', 'appointment_id')) {
                $table->unsignedBigInteger('appointment_id')->nullable()->after('vaccination_id');
            }
        });

        // Tornar vaccination_id nullable (para lembretes de aniversario e banho e tosa)
        Schema::table('vaccination_reminders', function (Blueprint $table) {
            $table->unsignedBigInteger('vaccination_id')->nullable()->change();
        });

        $this->addIndexIfMissing('vaccination_reminders', ['category', 'status'], 'idx_vac_reminders_cat_status');
        $this->addIndexIfMissing('vaccination_reminders', ['appointment_id'], 'idx_vac_reminders_appointment');
        $this->addIndexIfMissing('pets', ['is_active', 'birth_date'], 'idx_pets_active_birth_date');
        $this->addIndexIfMissing('appointments', ['type', 'date', 'status'], 'idx_appointments_type_date_status');

        // 2. Seed de novas configuracoes padrao na tabela settings
        $this->seedNewSettings();
    }

    public function down(): void
    {
        Schema::table('vaccination_reminders', function (Blueprint $table) {
            if (Schema::hasColumn('vaccination_reminders', 'appointment_id')) {
                $table->dropColumn('appointment_id');
            }
            if (Schema::hasColumn('vaccination_reminders', 'category')) {
                $table->dropColumn('category');
            }
        });
    }

    protected function addIndexIfMissing(string $table, array $columns, string $indexName): void
    {
        try {
            Schema::table($table, function (Blueprint $blueprint) use ($columns, $indexName) {
                $blueprint->index($columns, $indexName);
            });
        } catch (\Throwable $e) {
            // Indice ja existe
        }
    }

    protected function seedNewSettings(): void
    {
        if (!Schema::hasTable('settings')) {
            return;
        }

        $defaults = [
            'vaccine_reminders.enable_vaccinations' => '1',
            'vaccine_reminders.remind_2_days_enabled' => '1',
            'vaccine_reminders.enable_birthdays' => '1',
            'vaccine_reminders.enable_grooming' => '1',
            'vaccine_reminders.grooming_remind_1_day_enabled' => '1',
            'vaccine_reminders.grooming_remind_today_enabled' => '1',
            'vaccine_reminders.template_2_days' => "Olá, {NOME_TUTOR}! Tudo bem? 🐾\n\nFaltam apenas 2 dias para a vacinação do seu pet {NOME_ANIMAL}!\n\n💉 Vacina: {NOME_VACINA}\n\n📅 Data prevista: {DATA_VACINACAO}\n\nAinda não agendou? Entre em contato conosco para garantir o melhor horário e manter a proteção do seu companheiro em dia.\n\nAguardamos vocês! ❤️",
            'vaccine_reminders.template_birthday' => "Parabéns, {NOME_ANIMAL}! 🎉🐾🎂\n\nOlá, {NOME_TUTOR}! Hoje é um dia muito especial: o aniversário do seu companheiro {NOME_ANIMAL} ({IDADE_ANIMAL})!\n\nToda a equipe da {CLINICA} deseja muita saúde, alegria, petiscos e muitos anos de vida ao lado da sua família.\n\nUm grande abraço com carinho! ❤️",
            'vaccine_reminders.template_grooming' => "Olá, {NOME_TUTOR}! Tudo bem? 🐾🛁\n\nPassando para confirmar o agendamento de Banho e Tosa do seu pet {NOME_ANIMAL}.\n\n✂️ Serviço: {SERVICO_BANHO_TOSA}\n📅 Data: {DATA_AGENDAMENTO}\n⏰ Horário: {HORARIO_AGENDAMENTO}\n\nPor favor, confirme sua presença ou nos avise caso precise reagendar.\n\nSerá um prazer deixar o(a) {NOME_ANIMAL} ainda mais cheiroso(a) e feliz! ❤️",
        ];

        $now = now();
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
};
