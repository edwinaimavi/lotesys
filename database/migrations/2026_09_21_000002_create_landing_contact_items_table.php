<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('landing_contact_items', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20);
            $table->string('label')->nullable();
            $table->string('value', 1000);
            $table->string('url', 2048)->nullable();
            $table->string('network', 30)->nullable();
            $table->boolean('is_primary')->default(false);
            $table->boolean('use_for_cta')->default(false);
            $table->boolean('show_in_footer')->default(true);
            $table->unsignedInteger('sort_order')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['is_active', 'sort_order']);
            // A nullable unique slot also protects concurrent writes to an empty table.
            $table->unsignedTinyInteger('cta_slot')->nullable()
                ->storedAs('CASE WHEN use_for_cta = 1 THEN 1 ELSE NULL END')->unique();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('landing_contact_items');
    }
};
