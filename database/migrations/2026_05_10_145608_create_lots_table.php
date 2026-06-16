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
        Schema::create('lots', function (Blueprint $table) {

            $table->id();

            /*
            |--------------------------------------------------------------------------
            | RELACIONES
            |--------------------------------------------------------------------------
            */

            $table->foreignId('project_id')
                ->constrained('projects')
                ->cascadeOnDelete();

            $table->foreignId('block_id')
                ->constrained('blocks')
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | INFORMACIÓN DEL LOTE
            |--------------------------------------------------------------------------
            */

            $table->string('code')
                ->unique();

            $table->string('number');

            $table->decimal('area', 10, 2);

            $table->string('unit_measure')
                ->default('m2');

            /*
            |--------------------------------------------------------------------------
            | PRECIOS
            |--------------------------------------------------------------------------
            */

            $table->decimal('cash_price', 12, 2)
                ->default(0);

            $table->decimal('financed_price', 12, 2)
                ->default(0);

            /*
            |--------------------------------------------------------------------------
            | ESTADO DEL LOTE
            |--------------------------------------------------------------------------
            */

            $table->enum('status', [

                'disponible',
                'separado',
                'vendido',
                'rescindido',
                'bloqueado'

            ])->default('disponible');

            /*
            |--------------------------------------------------------------------------
            | OBSERVACIÓN
            |--------------------------------------------------------------------------
            */

            $table->text('observation')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | AUDITORÍA
            |--------------------------------------------------------------------------
            */

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            /*
            |--------------------------------------------------------------------------
            | TIMESTAMPS
            |--------------------------------------------------------------------------
            */

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lots', function (Blueprint $table) {

            $table->dropForeign(['project_id']);

            $table->dropForeign(['block_id']);

            $table->dropForeign(['created_by']);

            $table->dropForeign(['updated_by']);
        });

        Schema::dropIfExists('lots');
    }
};
