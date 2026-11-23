<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Campos opcionais de periodicidade e especie no cadastro de produtos
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'active_ingredient')) {
                $table->string('active_ingredient', 200)->nullable()->after('description');
            }
            if (!Schema::hasColumn('products', 'target_species')) {
                $table->string('target_species', 50)->nullable()->after('active_ingredient');
            }
            if (!Schema::hasColumn('products', 'reapplication_interval')) {
                $table->unsignedInteger('reapplication_interval')->nullable()->after('target_species');
            }
            if (!Schema::hasColumn('products', 'reapplication_unit')) {
                $table->string('reapplication_unit', 15)->nullable()->after('reapplication_interval');
            }
        });

        // 2. Extensao dos protocolos existentes para suportar qualquer produto e intervalos em dias/semanas/meses
        Schema::table('vaccine_protocols', function (Blueprint $table) {
            if (!Schema::hasColumn('vaccine_protocols', 'product_id')) {
                $table->unsignedBigInteger('product_id')->nullable()->after('id');
                $table->index('product_id', 'idx_vaccine_protocols_product_id');
            }
            if (!Schema::hasColumn('vaccine_protocols', 'protocol_type')) {
                $table->string('protocol_type', 30)->default('vaccine')->after('product_id');
            }
            if (!Schema::hasColumn('vaccine_protocols', 'interval_value')) {
                $table->unsignedInteger('interval_value')->nullable()->after('dose_number');
            }
            if (!Schema::hasColumn('vaccine_protocols', 'interval_unit')) {
                $table->string('interval_unit', 15)->nullable()->after('interval_value');
            }
        });

        // 3. Vinculo opcional de produto no controle parasitario (mantendo product_name intacto)
        Schema::table('parasite_controls', function (Blueprint $table) {
            if (!Schema::hasColumn('parasite_controls', 'product_id')) {
                $table->unsignedBigInteger('product_id')->nullable()->after('pet_id');
                $table->index('product_id', 'idx_parasite_controls_product_id');
            }
        });

        // 4. Data da proxima aplicacao na administracao de medicamentos
        Schema::table('medication_administrations', function (Blueprint $table) {
            if (!Schema::hasColumn('medication_administrations', 'next_application_date')) {
                $table->date('next_application_date')->nullable()->after('administered_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('medication_administrations', function (Blueprint $table) {
            if (Schema::hasColumn('medication_administrations', 'next_application_date')) {
                $table->dropColumn('next_application_date');
            }
        });

        Schema::table('parasite_controls', function (Blueprint $table) {
            if (Schema::hasColumn('parasite_controls', 'product_id')) {
                try {
                    $table->dropIndex('idx_parasite_controls_product_id');
                } catch (\Throwable $e) {
                }
                $table->dropColumn('product_id');
            }
        });

        Schema::table('vaccine_protocols', function (Blueprint $table) {
            foreach (['interval_unit', 'interval_value', 'protocol_type'] as $col) {
                if (Schema::hasColumn('vaccine_protocols', $col)) {
                    $table->dropColumn($col);
                }
            }
            if (Schema::hasColumn('vaccine_protocols', 'product_id')) {
                try {
                    $table->dropIndex('idx_vaccine_protocols_product_id');
                } catch (\Throwable $e) {
                }
                $table->dropColumn('product_id');
            }
        });

        Schema::table('products', function (Blueprint $table) {
            foreach (['reapplication_unit', 'reapplication_interval', 'target_species', 'active_ingredient'] as $col) {
                if (Schema::hasColumn('products', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
