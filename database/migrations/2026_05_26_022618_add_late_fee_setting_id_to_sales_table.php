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

            // =========================================
            // CONFIGURACIÓN DE MORA
            // =========================================

            $table->unsignedBigInteger('late_fee_setting_id')
                ->nullable()
                ->after('payment_day');

            // =========================================
            // FOREIGN KEY
            // =========================================

            $table->foreign('late_fee_setting_id')
                ->references('id')
                ->on('late_fee_settings')
                ->onDelete('set null');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {

            // =========================================
            // ELIMINAR FK
            // =========================================

            $table->dropForeign([
                'late_fee_setting_id'
            ]);

            // =========================================
            // ELIMINAR CAMPO
            // =========================================

            $table->dropColumn(
                'late_fee_setting_id'
            );

        });
    }
};