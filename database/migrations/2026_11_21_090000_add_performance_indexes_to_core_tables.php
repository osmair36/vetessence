<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medical_records', function (Blueprint $table) {
            $table->index('pet_id', 'idx_mr_pet_id');
            $table->index('vet_id', 'idx_mr_vet_id');
            $table->index('date', 'idx_mr_date');
            $table->index(['pet_id', 'date'], 'idx_mr_pet_date');
        });

        Schema::table('pets', function (Blueprint $table) {
            $table->index('name', 'idx_pets_name');
            $table->index('species', 'idx_pets_species');
            $table->index('is_active', 'idx_pets_is_active');
        });

        Schema::table('tutors', function (Blueprint $table) {
            $table->index('name', 'idx_tutors_name');
            $table->index('cpf', 'idx_tutors_cpf');
            $table->index('phone', 'idx_tutors_phone');
        });

        if (Schema::hasTable('pet_tutor')) {
            Schema::table('pet_tutor', function (Blueprint $table) {
                $table->index(['pet_id', 'tutor_id'], 'idx_pet_tutor_pair');
                $table->index('tutor_id', 'idx_pet_tutor_tutor_id');
            });
        }

        Schema::table('appointments', function (Blueprint $table) {
            $table->index(['date', 'time'], 'idx_appointments_date_time');
            $table->index('pet_id', 'idx_appointments_pet_id');
            $table->index('vet_id', 'idx_appointments_vet_id');
            $table->index('status', 'idx_appointments_status');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->index('tutor_id', 'idx_invoices_tutor_id');
            $table->index('status', 'idx_invoices_status');
            $table->index('paid_at', 'idx_invoices_paid_at');
            $table->index(['status', 'due_date'], 'idx_invoices_status_due');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->index('name', 'idx_products_name');
            $table->index('is_active', 'idx_products_is_active');
            $table->index('category_id', 'idx_products_category_id');
            $table->index(['is_active', 'stock'], 'idx_products_active_stock');
        });
    }

    public function down(): void
    {
        Schema::table('medical_records', function (Blueprint $table) {
            $table->dropIndex('idx_mr_pet_id');
            $table->dropIndex('idx_mr_vet_id');
            $table->dropIndex('idx_mr_date');
            $table->dropIndex('idx_mr_pet_date');
        });

        Schema::table('pets', function (Blueprint $table) {
            $table->dropIndex('idx_pets_name');
            $table->dropIndex('idx_pets_species');
            $table->dropIndex('idx_pets_is_active');
        });

        Schema::table('tutors', function (Blueprint $table) {
            $table->dropIndex('idx_tutors_name');
            $table->dropIndex('idx_tutors_cpf');
            $table->dropIndex('idx_tutors_phone');
        });

        if (Schema::hasTable('pet_tutor')) {
            Schema::table('pet_tutor', function (Blueprint $table) {
                $table->dropIndex('idx_pet_tutor_pair');
                $table->dropIndex('idx_pet_tutor_tutor_id');
            });
        }

        Schema::table('appointments', function (Blueprint $table) {
            $table->dropIndex('idx_appointments_date_time');
            $table->dropIndex('idx_appointments_pet_id');
            $table->dropIndex('idx_appointments_vet_id');
            $table->dropIndex('idx_appointments_status');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex('idx_invoices_tutor_id');
            $table->dropIndex('idx_invoices_status');
            $table->dropIndex('idx_invoices_paid_at');
            $table->dropIndex('idx_invoices_status_due');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('idx_products_name');
            $table->dropIndex('idx_products_is_active');
            $table->dropIndex('idx_products_category_id');
            $table->dropIndex('idx_products_active_stock');
        });
    }
};
