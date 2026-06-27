<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->boolean('is_legacy_sale')
                ->default(false)
                ->after('status');

            $table->date('collection_rules_start_date')
                ->nullable()
                ->after('is_legacy_sale');

            $table->text('legacy_observation')
                ->nullable()
                ->after('collection_rules_start_date');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn([
                'is_legacy_sale',
                'collection_rules_start_date',
                'legacy_observation',
            ]);
        });
    }
};
