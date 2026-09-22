<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class ChatConversation extends Model
{
    protected $fillable = ['user_one_id', 'user_two_id', 'last_message_at'];

    protected $casts = ['last_message_at' => 'datetime'];

    protected static function booted(): void
    {
        static::saving(function (self $conversation) {
            $first = (int) $conversation->user_one_id;
            $second = (int) $conversation->user_two_id;
            if ($first === $second) {
                throw ValidationException::withMessages(['user' => 'No puedes conversar contigo mismo.']);
            }
            $conversation->user_one_id = min($first, $second);
            $conversation->user_two_id = max($first, $second);
        });
    }

    public function userOne()
    {
        return $this->belongsTo(User::class, 'user_one_id');
    }

    public function userTwo()
    {
        return $this->belongsTo(User::class, 'user_two_id');
    }

    public function messages()
    {
        return $this->hasMany(ChatMessage::class, 'conversation_id');
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where(fn ($pair) => $pair->where('user_one_id', $userId)->orWhere('user_two_id', $userId));
    }
}
