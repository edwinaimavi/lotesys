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
        Schema::create('companies', function (Blueprint $table) {

            $table->id();

            $table->string('business_name');

            $table->string('trade_name');

            $table->string('ruc', 11)->unique();

            $table->string('address')->nullable();

            $table->string('phone', 20)->nullable();

            $table->string('email')->nullable();

            $table->tinyInteger('status')
                  ->default(1)
                  ->comment('1=active,0=inactive');

            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};