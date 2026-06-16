<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = [

        'payment_id',
        'sale_id',
        'company_id',
        'document_type',
        'series',
        'number',

        'issue_date',
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
        'currency',
        'subtotal',
        'tax_amount',
        'total_amount',

        'sunat_status',

        'hash_code',

        'xml_path',
        'cdr_path',
        'pdf_path',

        'sunat_ticket',
        'sunat_code',
        'sunat_message',

        'created_by',
        'updated_by',
    ];


    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }
}
    //
