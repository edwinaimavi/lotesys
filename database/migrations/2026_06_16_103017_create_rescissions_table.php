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
        Schema::create('rescissions', function (Blueprint $table) {
            $table->id();

            // Venta asociada
            $table->foreignId('sale_id')
                ->constrained('sales')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            // Información de la rescisión
            $table->date('rescission_date');
            $table->text('reason');
            $table->unsignedInteger('overdue_installments')->default(0);

            // Resumen económico
            $table->decimal('amount_paid', 12, 2)->default(0);
            $table->decimal('penalty_amount', 12, 2)->default(0);

            // Observaciones
            $table->text('observation')->nullable();

            // Usuario que realizó la rescisión
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->timestamps();

            // Evitar que una venta tenga más de una rescisión
            $table->unique('sale_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rescissions');
    }
};