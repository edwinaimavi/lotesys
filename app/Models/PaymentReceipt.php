<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class PaymentReceipt extends Model
{
    public const DISK = 'payment_receipts';

    protected $fillable = [
        'disk', 'path', 'original_name', 'mime_type', 'file_size', 'created_by',
    ];

    protected $hidden = ['disk', 'path'];

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public static function storage()
    {
        return Storage::build(config('payments.receipts_storage'));
    }
}
