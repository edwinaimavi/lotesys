<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    protected $fillable = ['sender_id', 'body', 'read_at'];

    protected $casts = ['read_at' => 'datetime'];

    public function conversation()
    {
        return $this->belongsTo(ChatConversation::class, 'conversation_id');
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function attachments()
    {
        return $this->hasMany(ChatMessageAttachment::class, 'message_id');
    }
}
