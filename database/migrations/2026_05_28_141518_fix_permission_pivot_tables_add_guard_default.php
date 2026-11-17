<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite does not support MODIFY COLUMN; skip on SQLite (columns already have correct type)
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE model_has_roles MODIFY COLUMN guard_name VARCHAR(255) NOT NULL DEFAULT "web"');
            DB::statement('ALTER TABLE model_has_permissions MODIFY COLUMN guard_name VARCHAR(255) NOT NULL DEFAULT "web"');
        } else {
            // On SQLite: update existing null/empty guard_name values
            DB::statement('UPDATE model_has_roles SET guard_name = "web" WHERE guard_name IS NULL OR guard_name = ""');
            DB::statement('UPDATE model_has_permissions SET guard_name = "web" WHERE guard_name IS NULL OR guard_name = ""');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE model_has_roles MODIFY COLUMN guard_name VARCHAR(255) NOT NULL');
            DB::statement('ALTER TABLE model_has_permissions MODIFY COLUMN guard_name VARCHAR(255) NOT NULL');
        }
    }
};
