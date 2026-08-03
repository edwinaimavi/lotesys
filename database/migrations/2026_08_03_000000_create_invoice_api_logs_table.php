<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_api_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->string('action', 50)->nullable();
            $table->string('provider', 50)->default('APISPERU');
            $table->string('endpoint')->nullable();
            $table->integer('http_status')->nullable();
            $table->boolean('success')->default(false);
            $table->longText('request_payload')->nullable();
            $table->longText('response_body')->nullable();
            $table->longText('response_json')->nullable();
            $table->text('error_message')->nullable();
            $table->text('exception_message')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_api_logs');
    }
};
