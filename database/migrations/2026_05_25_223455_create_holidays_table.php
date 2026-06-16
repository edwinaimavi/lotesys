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
        Schema::create('holidays', function (Blueprint $table) {

            $table->id();

            // =========================================
            // FECHA FERIADO
            // =========================================

            $table->date('date');

            // =========================================
            // DESCRIPCIÓN
            // =========================================

            $table->string('description');

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

            // =========================================
            // TIMESTAMPS
            // =========================================

            $table->timestamps();

            // =========================================
            // FOREIGN KEYS
            // =========================================

            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->foreign('updated_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('holidays');
    }
};
