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
        Schema::create('amortizations', function (Blueprint $table) {

            $table->id();

            // =====================================================
            // RELACIONES
            // =====================================================

            $table->foreignId('sale_id')
                ->constrained('sales')
                ->cascadeOnDelete();

            $table->foreignId('payment_id')
                ->nullable()
                ->constrained('payments')
                ->nullOnDelete();

            // =====================================================
            // DATOS AMORTIZACIÓN
            // =====================================================

            $table->date('date');

            $table->decimal('amount', 12, 2);

            $table->enum('recalculation_type', [

                'reducir_cuota',
                'reducir_tiempo',
                'descuento'

            ]);

            // =====================================================
            // RESULTADOS DEL RECÁLCULO
            // =====================================================

            $table->integer('reduced_installments')
                ->nullable();

            $table->decimal('new_installment', 12, 2)
                ->nullable();

            $table->text('observation')
                ->nullable();

            // =====================================================
            // AUDITORÍA
            // =====================================================

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('amortizations');
    }
};
