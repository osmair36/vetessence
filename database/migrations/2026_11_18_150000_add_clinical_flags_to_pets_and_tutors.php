<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pets', function (Blueprint $table) {
            if (!Schema::hasColumn('pets', 'is_neutered')) {
                $table->boolean('is_neutered')->default(false)->after('gender');
            }
            if (!Schema::hasColumn('pets', 'is_aggressive')) {
                $table->boolean('is_aggressive')->default(false)->after('is_neutered');
            }
            if (!Schema::hasColumn('pets', 'pathologies')) {
                $table->text('pathologies')->nullable()->after('notes');
            }
            if (!Schema::hasColumn('pets', 'surgical_history')) {
                $table->text('surgical_history')->nullable()->after('pathologies');
            }
        });

        Schema::table('tutors', function (Blueprint $table) {
            if (!Schema::hasColumn('tutors', 'birth_date')) {
                $table->date('birth_date')->nullable()->after('name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pets', function (Blueprint $table) {
            $table->dropColumn(['is_neutered', 'is_aggressive', 'pathologies', 'surgical_history']);
        });

        Schema::table('tutors', function (Blueprint $table) {
            $table->dropColumn(['birth_date']);
        });
    }
};
