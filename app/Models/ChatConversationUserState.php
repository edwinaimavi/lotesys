<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatConversationUserState extends Model
{
    protected $fillable = [
        'conversation_id',
        'user_id',
        'cleared_at',
        'hidden_at',
        'cleared_through_message_id',
    ];

    protected $casts = [
        'cleared_at' => 'datetime',
        'hidden_at' => 'datetime',
        'cleared_through_message_id' => 'integer',
    ];

    public function conversation()
    {
        return $this->belongsTo(ChatConversation::class, 'conversation_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
