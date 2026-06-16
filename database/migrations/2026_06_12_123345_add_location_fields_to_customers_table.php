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
        Schema::table('customers', function (Blueprint $table) {

            $table->string('department', 100)
                ->nullable()
                ->after('address');

            $table->string('province', 100)
                ->nullable()
                ->after('department');

            $table->string('district', 100)
                ->nullable()
                ->after('province');

            $table->string('ubigeo', 10)
                ->nullable()
                ->after('district');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {

            $table->dropColumn([
                'department',
                'province',
                'district',
                'ubigeo'
            ]);
        });
    }
};
