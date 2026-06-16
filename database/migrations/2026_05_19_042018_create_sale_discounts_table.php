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
        Schema::create('sale_discounts', function (Blueprint $table) {

            $table->id();

            // venta
            $table->foreignId('sale_id')
                ->constrained('sales')
                ->cascadeOnDelete();

            // amortización relacionada (opcional)
            $table->foreignId('amortization_id')
                ->nullable()
                ->constrained('amortizations')
                ->nullOnDelete();

            // monto descuento
            $table->decimal('amount', 12, 2);

            // motivo
            $table->text('reason')->nullable();

            // usuario
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
        Schema::dropIfExists('sale_discounts');
    }
};
