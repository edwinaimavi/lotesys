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
        Schema::create('banks', function (Blueprint $table) {

            $table->id();

            // =====================================================
            // DATOS BANCO
            // =====================================================

            $table->string('bank_name');

            $table->enum('currency', [
                'PEN',
                'USD'
            ])->default('PEN');

            $table->string('account_number');

            $table->string('cci');

            $table->string('account_holder');

            // =====================================================
            // EXTRA
            // =====================================================

            $table->text('description')
                ->nullable();

            $table->enum('status', [
                'activo',
                'inactivo'
            ])->default('activo');

            // =====================================================
            // AUDITORÍA
            // =====================================================

            $table->unsignedBigInteger('created_by')
                ->nullable();

            $table->unsignedBigInteger('updated_by')
                ->nullable();

            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('banks');
    }
};