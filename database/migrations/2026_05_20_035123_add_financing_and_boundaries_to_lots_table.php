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
        Schema::table('lots', function (Blueprint $table) {

            // =====================================================
            // COLINDANTES
            // =====================================================

            $table->string('north_boundary')
                ->nullable()
                ->after('observation');

            $table->string('south_boundary')
                ->nullable()
                ->after('north_boundary');

            $table->string('east_boundary')
                ->nullable()
                ->after('south_boundary');

            $table->string('west_boundary')
                ->nullable()
                ->after('east_boundary');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lots', function (Blueprint $table) {

            $table->dropColumn([

                'north_boundary',
                'south_boundary',
                'east_boundary',
                'west_boundary'

            ]);
        });
    }
};
