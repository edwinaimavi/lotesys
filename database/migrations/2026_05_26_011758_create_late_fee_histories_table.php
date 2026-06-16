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
        Schema::create('late_fee_histories', function (Blueprint $table) {

            $table->id();

            // =========================================
            // RELACIÓN CRONOGRAMA
            // =========================================

            $table->unsignedBigInteger(
                'payment_schedule_id'
            );

            // =========================================
            // MORA GENERADA
            // =========================================

            $table->decimal(
                'late_fee_amount',
                12,
                2
            )->default(0);

            // =========================================
            // DÍAS MORA
            // =========================================

            $table->integer(
                'late_days'
            )->default(0);

            // =========================================
            // MORA DIARIA APLICADA
            // =========================================

            $table->decimal(
                'daily_late_fee_applied',
                12,
                2
            )->default(0);

            // =========================================
            // TIPO CÁLCULO
            // =========================================

            $table->enum(
                'calculation_type',
                [

                    'automatico',
                    'manual',
                    'reproceso'

                ]
            )->default('automatico');

            // =========================================
            // FECHA CÁLCULO
            // =========================================

            $table->dateTime(
                'calculated_at'
            );

            // =========================================
            // ELIMINACIÓN / REVERSIÓN
            // =========================================

            $table->unsignedBigInteger(
                'deleted_by'
            )->nullable();

            $table->text(
                'deletion_reason'
            )->nullable();

            // =========================================
            // ESTADO
            // =========================================

            $table->enum(
                'status',
                [

                    'activo',
                    'anulado'

                ]
            )->default('activo');

            // =========================================
            // AUDITORÍA
            // =========================================

            $table->unsignedBigInteger(
                'created_by'
            )->nullable();

            $table->unsignedBigInteger(
                'updated_by'
            )->nullable();

            // =========================================
            // TIMESTAMPS
            // =========================================

            $table->timestamps();

            // =========================================
            // FOREIGN KEYS
            // =========================================

            $table->foreign(
                'payment_schedule_id'
            )
                ->references('id')
                ->on('payment_schedules')
                ->cascadeOnDelete();

            $table->foreign(
                'created_by'
            )
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->foreign(
                'updated_by'
            )
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->foreign(
                'deleted_by'
            )
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
        Schema::dropIfExists(
            'late_fee_histories'
        );
    }
};
