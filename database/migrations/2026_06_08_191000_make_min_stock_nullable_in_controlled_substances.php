<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            // SQLite doesn't support MODIFY COLUMN; column is already nullable in original schema
        } else {
            DB::statement('ALTER TABLE controlled_substances MODIFY min_stock DECIMAL(10,2) NULL DEFAULT 0');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE controlled_substances MODIFY min_stock DECIMAL(10,2) NOT NULL DEFAULT 0');
        }
    }
};
