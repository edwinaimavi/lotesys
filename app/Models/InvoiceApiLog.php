<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceApiLog extends Model
{
    protected $fillable = [
        'invoice_id',
        'action',
        'provider',
        'endpoint',
        'http_status',
        'success',
        'request_payload',
        'response_body',
        'response_json',
        'error_message',
        'exception_message',
        'created_by',
    ];

    protected $casts = [
        'success' => 'boolean',
        'http_status' => 'integer',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
