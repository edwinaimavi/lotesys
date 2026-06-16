<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {

            $table->unsignedBigInteger('bank_id')
                ->nullable()
                ->after('payment_method');

            $table->foreign('bank_id')
                ->references('id')
                ->on('banks')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {

            $table->dropForeign(['bank_id']);
            $table->dropColumn('bank_id');
        });
    }
};
