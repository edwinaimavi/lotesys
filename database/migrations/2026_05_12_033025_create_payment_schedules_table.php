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
        Schema::create('payment_schedules', function (Blueprint $table) {

            $table->id();

            $table->foreignId('sale_id')
                ->constrained('sales')
                ->onDelete('cascade');

            $table->integer('installment_number');

            $table->date('due_date');

            $table->decimal('installment_amount', 12, 2)->default(0);

            $table->decimal('capital', 12, 2)->default(0);

            $table->decimal('interest', 12, 2)->default(0);

            $table->decimal('late_fee', 12, 2)->default(0);

            $table->decimal('total_amount', 12, 2)->default(0);

            $table->decimal('remaining_balance', 12, 2)->default(0);

            $table->enum('status', [
                'pendiente',
                'pagado',
                'vencido',
                'parcial'
            ])->default('pendiente');

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
        Schema::dropIfExists('payment_schedules');
    }
};
