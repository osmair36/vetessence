<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pet_transports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pet_id');
            $table->unsignedBigInteger('tutor_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('driver_id')->nullable();
            $table->dateTime('pickup_at');
            $table->dateTime('delivery_at')->nullable();
            $table->string('origin_address');
            $table->string('destination_address');
            $table->enum('status', ['scheduled', 'collected', 'in_route', 'delivered', 'cancelled'])->default('scheduled');
            $table->decimal('price', 8, 2)->default(0);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->timestamps();

            $table->foreign('pet_id')->references('id')->on('pets')->cascadeOnDelete();
            $table->foreign('tutor_id')->references('id')->on('tutors')->nullOnDelete();
            $table->foreign('branch_id')->references('id')->on('branches')->nullOnDelete();
            $table->foreign('driver_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('invoice_id')->references('id')->on('invoices')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pet_transports');
    }
};
