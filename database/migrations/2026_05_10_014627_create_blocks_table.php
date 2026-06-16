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
        Schema::create('blocks', function (Blueprint $table) {

            $table->id();

            /*
            |--------------------------------------------------------------------------
            | RELACIÓN PROYECTO
            |--------------------------------------------------------------------------
            */

            $table->foreignId('project_id')
                ->constrained('projects')
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | INFORMACIÓN MANZANA
            |--------------------------------------------------------------------------
            */

            $table->string('name');

            $table->text('description')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | ESTADO
            |--------------------------------------------------------------------------
            */

            $table->tinyInteger('status')
                ->default(1)
                ->comment('1=activo,0=inactivo,-1=eliminado');

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
        Schema::table('blocks', function (Blueprint $table) {

            $table->dropForeign(['project_id']);

            $table->dropForeign(['created_by']);

            $table->dropForeign(['updated_by']);
        });

        Schema::dropIfExists('blocks');
    }
};
