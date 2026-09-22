<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_lots', function (Blueprint $table) {
            $table->id();

            $table->foreignId('sale_id')
                ->constrained('sales')
                ->cascadeOnDelete();

            $table->foreignId('lot_id')
                ->constrained('lots')
                ->restrictOnDelete();

            // Precio aplicado a este lote dentro de la venta.
            // Es un snapshot y no cambia si luego cambia el precio maestro del lote.
            $table->decimal('sale_price', 12, 2);

            $table->boolean('is_primary')->default(false);

            $table->timestamps();

            $table->unique(['sale_id', 'lot_id']);
            $table->index('lot_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_lots');
    }
};
