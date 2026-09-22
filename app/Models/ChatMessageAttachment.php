<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ChatMessageAttachment extends Model
{
    public const DISK = 'chat_attachments';

    protected $fillable = ['disk', 'path', 'original_name', 'mime_type', 'file_size'];

    protected $hidden = ['disk', 'path'];

    public function message()
    {
        return $this->belongsTo(ChatMessage::class, 'message_id');
    }

    public static function storage()
    {
        return Storage::build(config('chat.attachments_storage'));
    }
}
