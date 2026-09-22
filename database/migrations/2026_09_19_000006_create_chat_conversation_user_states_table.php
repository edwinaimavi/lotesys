<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_conversation_user_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('chat_conversations')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('cleared_at')->nullable();
            $table->timestamp('hidden_at')->nullable();
            $table->unsignedBigInteger('cleared_through_message_id')->nullable();
            $table->timestamps();

            $table->unique(['conversation_id', 'user_id']);
            $table->index(['user_id', 'hidden_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_conversation_user_states');
    }
};
