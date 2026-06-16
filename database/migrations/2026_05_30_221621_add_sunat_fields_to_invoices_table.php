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
        Schema::table('invoices', function (Blueprint $table) {

            // ============================================
            // EMPRESA EMISORA
            // ============================================

            $table->unsignedBigInteger('company_id')
                ->nullable()
                ->after('sale_id');

            // ============================================
            // CLIENTE (SNAPSHOT)
            // ============================================

            $table->string('customer_document_type', 2)
                ->nullable()
                ->after('issue_date');

            $table->string('customer_document', 20)
                ->nullable()
                ->after('customer_document_type');

            $table->string('customer_name', 255)
                ->nullable()
                ->after('customer_document');

            // ============================================
            // DIRECCIÓN CLIENTE
            // ============================================

            $table->string('customer_address', 255)
                ->nullable()
                ->after('customer_name');

            $table->string('customer_department', 100)
                ->nullable()
                ->after('customer_address');

            $table->string('customer_province', 100)
                ->nullable()
                ->after('customer_department');

            $table->string('customer_district', 100)
                ->nullable()
                ->after('customer_province');

            $table->string('customer_ubigeo', 10)
                ->nullable()
                ->after('customer_district');

            // ============================================
            // COMPROBANTE
            // ============================================

            $table->text('concept')
                ->nullable()
                ->after('customer_ubigeo');

            $table->text('legend')
                ->nullable()
                ->after('concept');

            $table->string('currency', 10)
                ->default('PEN')
                ->after('legend');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {

            $table->dropColumn([
                'company_id',

                'customer_document_type',
                'customer_document',
                'customer_name',

                'customer_address',
                'customer_department',
                'customer_province',
                'customer_district',
                'customer_ubigeo',

                'concept',
                'legend',

                'currency'
            ]);
        });
    }
};
