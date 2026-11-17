<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRetentionToControlledSubstanceLogs extends Migration
{
    public function up()
    {
        Schema::table('controlled_substance_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('controlled_substance_logs', 'retention_until')) {
                $table->date('retention_until')->nullable()->after('notes');
            }
            if (!Schema::hasColumn('controlled_substance_logs', 'is_archived')) {
                $table->boolean('is_archived')->default(false)->after('retention_until');
            }
        });

        // Compatible with both MySQL and SQLite
        if (DB::getDriverName() === 'sqlite') {
            DB::statement("UPDATE controlled_substance_logs SET retention_until = date(created_at, '+2 years') WHERE retention_until IS NULL");
        } else {
            DB::statement('UPDATE controlled_substance_logs SET retention_until = DATE_ADD(created_at, INTERVAL 2 YEAR) WHERE retention_until IS NULL');
        }
    }

    public function down()
    {
        Schema::table('controlled_substance_logs', function (Blueprint $table) {
            $table->dropColumn(['retention_until', 'is_archived']);
        });
    }
}
