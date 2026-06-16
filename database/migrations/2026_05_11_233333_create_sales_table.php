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
        Schema::create('sales', function (Blueprint $table) {

            $table->id();

            // =====================================================
            // RELACIONES
            // =====================================================

            $table->foreignId('customer_id')
                ->constrained('customers');

            $table->foreignId('lot_id')
                ->constrained('lots');

            // =====================================================
            // DATOS VENTA
            // =====================================================

            $table->string('sale_code')->unique();

            $table->date('sale_date');

            $table->decimal('lot_price', 12, 2);

            $table->decimal('initial_payment', 12, 2)
                ->default(0);

            $table->decimal('balance_finance', 12, 2)
                ->default(0);

            $table->integer('installments_count')
                ->default(1);

            $table->decimal('monthly_payment', 12, 2)
                ->default(0);

            $table->decimal('interest_rate', 5, 2)
                ->default(0);

            $table->date('first_payment_date')
                ->nullable();

            $table->integer('payment_day')
                ->nullable();

            // =====================================================
            // ESTADO
            // =====================================================

            $table->enum('status', [
                'activo',
                'cancelado',
                'rescindido',
                'finalizado'
            ])->default('activo');

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
        Schema::dropIfExists('sales');
    }
};