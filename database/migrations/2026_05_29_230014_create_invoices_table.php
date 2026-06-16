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
        Schema::create('invoices', function (Blueprint $table) {

            $table->id();

            // RELATIONS
            $table->foreignId('payment_id')
                ->constrained('payments')
                ->cascadeOnDelete();

            $table->foreignId('sale_id')
                ->constrained('sales')
                ->cascadeOnDelete();

            // DOCUMENT
            $table->enum('document_type', [
                'invoice',      // Factura
                'receipt',      // Boleta
                'credit_note',
                'debit_note',
                'sale_note'
            ]);

            $table->string('series', 10);

            $table->string('number', 20);

            $table->date('issue_date');

            // AMOUNTS
            $table->decimal('subtotal', 12, 2)->default(0);

            $table->decimal('tax_amount', 12, 2)->default(0);

            $table->decimal('total_amount', 12, 2)->default(0);

            // SUNAT
            $table->enum('sunat_status', [
                'pending',
                'accepted',
                'rejected',
                'voided'
            ])->default('pending');

            $table->string('hash_code')->nullable();

            // FILES
            $table->string('xml_path')->nullable();

            $table->string('cdr_path')->nullable();

            $table->string('pdf_path')->nullable();

            // SUNAT RESPONSE
            $table->string('sunat_ticket')->nullable();

            $table->string('sunat_code')->nullable();

            $table->text('sunat_message')->nullable();

            // AUDIT
            $table->unsignedBigInteger('created_by')->nullable();

            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
