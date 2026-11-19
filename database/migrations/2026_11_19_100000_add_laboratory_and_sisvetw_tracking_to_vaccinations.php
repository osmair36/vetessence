<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vaccinations', function (Blueprint $table) {
            if (!Schema::hasColumn('vaccinations', 'laboratory')) {
                $table->string('laboratory', 100)->nullable()->after('manufacturer');
            }
            if (!Schema::hasColumn('vaccinations', 'status')) {
                $table->string('status', 20)->default('applied')->after('dose');
            }
            if (!Schema::hasColumn('vaccinations', 'sisvetw_recno')) {
                $table->unsignedInteger('sisvetw_recno')->nullable()->index();
            }
            if (!Schema::hasColumn('vaccinations', 'sisvetw_ficha')) {
                $table->string('sisvetw_ficha', 20)->nullable()->index();
            }
            if (!Schema::hasColumn('vaccinations', 'sisvetw_cod_vac')) {
                $table->string('sisvetw_cod_vac', 10)->nullable();
            }
        });

        // Sincroniza registros existentes que ja tenham manufacturer preenchido
        DB::table('vaccinations')
            ->whereNull('laboratory')
            ->whereNotNull('manufacturer')
            ->update(['laboratory' => DB::raw('manufacturer')]);

        if (!Schema::hasTable('sisvetw_pet_maps')) {
            Schema::create('sisvetw_pet_maps', function (Blueprint $table) {
                $table->id();
                $table->string('sisvetw_ficha', 20)->unique();
                $table->unsignedBigInteger('pet_id')->index();
                $table->string('sisvetw_propr', 20)->nullable();
                $table->string('pet_name', 120)->nullable();
                $table->string('tutor_name', 150)->nullable();
                $table->string('matched_by', 50)->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sisvetw_pet_maps');

        Schema::table('vaccinations', function (Blueprint $table) {
            foreach (['sisvetw_cod_vac', 'sisvetw_ficha', 'sisvetw_recno', 'status', 'laboratory'] as $col) {
                if (Schema::hasColumn('vaccinations', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
