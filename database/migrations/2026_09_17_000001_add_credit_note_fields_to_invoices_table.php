<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('related_invoice_id')
                ->nullable()
                ->after('company_id')
                ->constrained('invoices')
                ->nullOnDelete();

            $table->string('credit_note_reason_code', 2)
                ->nullable()
                ->after('sunat_message');

            $table->text('credit_note_reason')
                ->nullable()
                ->after('credit_note_reason_code');

            $table->timestamp('voided_at')
                ->nullable()
                ->after('credit_note_reason');

            $table->foreignId('voided_by')
                ->nullable()
                ->after('voided_at')
                ->constrained('users')
                ->nullOnDelete();

            $table->index(
                ['related_invoice_id', 'document_type', 'sunat_status'],
                'invoices_related_document_status_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex('invoices_related_document_status_index');
            $table->dropConstrainedForeignId('voided_by');
            $table->dropColumn([
                'voided_at',
                'credit_note_reason',
                'credit_note_reason_code',
            ]);
            $table->dropConstrainedForeignId('related_invoice_id');
        });
    }
};
