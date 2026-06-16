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
        Schema::create('late_fee_settings', function (Blueprint $table) {

            $table->id();

            // =========================================
            // CONFIGURACIÓN MORA
            // =========================================

            $table->integer('grace_days')
                ->default(0);

            $table->decimal('daily_late_fee', 12, 2)
                ->default(0);

            $table->decimal('max_late_fee', 12, 2)
                ->nullable();

            // =========================================
            // CONFIGURACIÓN AVANZADA
            // =========================================

            $table->boolean('apply_sundays')
                ->default(false);

            $table->boolean('apply_holidays')
                ->default(false);

            // =========================================
            // ESTADO
            // =========================================

            $table->enum('status', [
                'activo',
                'inactivo'
            ])->default('activo');

            // =========================================
            // AUDITORÍA
            // =========================================

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
        Schema::dropIfExists('late_fee_settings');
    }
};
