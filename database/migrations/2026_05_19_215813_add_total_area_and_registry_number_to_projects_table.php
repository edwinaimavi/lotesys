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
        Schema::table('projects', function (Blueprint $table) {

            // Área total del proyecto
            $table->decimal('total_area', 12, 2)
                ->nullable()
                ->after('department');

            // Número de partida registral
            $table->string('registry_number', 100)
                ->nullable()
                ->after('total_area');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {

            $table->dropColumn([
                'total_area',
                'registry_number'
            ]);
        });
    }
};
