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
        Schema::table('sales', function (Blueprint $table) {

            $table->enum('payment_mode', [
                'automatico',
                'personalizado'
            ])->default('automatico')
                ->after('installments_count');

            $table->decimal('custom_payment', 12, 2)
                ->nullable()
                ->after('payment_mode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {

            $table->dropColumn([
                'payment_mode',
                'custom_payment'
            ]);
        });
    }
};
