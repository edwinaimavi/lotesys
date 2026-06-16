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
        Schema::create('payments', function (Blueprint $table) {

            $table->id();

            // RELACIONES
            $table->foreignId('sale_id')
                ->constrained('sales')
                ->onDelete('cascade');

            $table->foreignId('payment_schedule_id')
                ->constrained('payment_schedules')
                ->onDelete('cascade');

            // DATOS DEL PAGO
            $table->enum('payment_type', [
                'inicial',
                'cuota',
                'amortizacion',
                'cancelacion_total',
                'mora'
            ]);

            $table->date('payment_date');

            $table->decimal('amount', 12, 2);

            $table->decimal('late_fee_paid', 12, 2)
                ->default(0);

            $table->decimal('discount', 12, 2)
                ->default(0);

            $table->text('observation')
                ->nullable();

            // MÉTODO DE PAGO
            $table->enum('payment_method', [
                'efectivo',
                'transferencia',
                'yape',
                'plin',
                'deposito'
            ]);

            $table->string('operation_number')
                ->nullable();

            // USUARIO QUE REGISTRA EL PAGO
            $table->foreignId('user_id')
                ->constrained('users');

            // ESTADO
            $table->enum('status', [
                'activo',
                'anulado'
            ])->default('activo');

            // AUDITORÍA
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
        Schema::dropIfExists('payments');
    }
};
