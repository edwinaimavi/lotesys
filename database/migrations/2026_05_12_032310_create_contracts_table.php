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
        Schema::create('contracts', function (Blueprint $table) {

            $table->id();

            $table->foreignId('sale_id')
                ->constrained('sales')
                ->onDelete('cascade');

            $table->string('contract_number')->unique();

            $table->date('contract_date');

            $table->string('pdf_file')->nullable();

            $table->text('observation')->nullable();

            $table->enum('status', [
                'activo',
                'anulado',
                'finalizado'
            ])->default('activo');

            // Auditoría
            $table->unsignedBigInteger('created_by')->nullable();

            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
