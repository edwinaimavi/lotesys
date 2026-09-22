<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChatConversation;
use App\Models\ChatConversationUserState;
use App\Models\ChatMessage;
use App\Models\ChatMessageAttachment;
use App\Models\User;
use App\Rules\ChatAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ChatController extends Controller
{
    public function users(Request $request)
    {
        $data = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $me = (int) Auth::id();

        $visibleLastBody = ChatMessage::select('body')
            ->whereColumn('conversation_id', 'chat.id')
            ->where(function ($query) {
                $query->whereNull('state.cleared_through_message_id')
                    ->orWhereColumn('chat_messages.id', '>', 'state.cleared_through_message_id');
            })
            ->latest('id')
            ->limit(1);

        $visibleLastAt = ChatMessage::select('created_at')
            ->whereColumn('conversation_id', 'chat.id')
            ->where(function ($query) {
                $query->whereNull('state.cleared_through_message_id')
                    ->orWhereColumn('chat_messages.id', '>', 'state.cleared_through_message_id');
            })
            ->latest('id')
            ->limit(1);

        $visibleUnread = ChatMessage::selectRaw('COUNT(*)')
            ->whereColumn('conversation_id', 'chat.id')
            ->where('sender_id', '!=', $me)
            ->whereNull('read_at')
            ->where(function ($query) {
                $query->whereNull('state.cleared_through_message_id')
                    ->orWhereColumn('chat_messages.id', '>', 'state.cleared_through_message_id');
            });

        $users = User::query()
            ->leftJoin('chat_conversations as chat', function ($join) use ($me) {
                $join->on(function ($pair) use ($me) {
                    $pair->on('chat.user_one_id', '=', 'users.id')->where('chat.user_two_id', $me);
                })->orOn(function ($pair) use ($me) {
                    $pair->on('chat.user_two_id', '=', 'users.id')->where('chat.user_one_id', $me);
                });
            })
            ->leftJoin('chat_conversation_user_states as state', function ($join) use ($me) {
                $join->on('state.conversation_id', '=', 'chat.id')->where('state.user_id', $me);
            })
            ->leftJoin('chat_user_presence as presence', 'presence.user_id', '=', 'users.id')
            ->where('users.id', '!=', $me)
            ->select(['users.id', 'users.name', 'users.lastname', 'users.photo'])
            ->selectRaw('CASE WHEN presence.last_seen_at >= ? THEN 1 ELSE 0 END as is_online', [now()->subSeconds(45)])
            ->addSelect([
                'last_body' => $visibleLastBody,
                'last_visible_at' => $visibleLastAt,
                'unread_count' => $visibleUnread,
            ])
            ->when(trim($data['search'] ?? '') !== '', function ($query) use ($data) {
                $term = '%' . trim($data['search']) . '%';
                $query->where(fn ($name) => $name
                    ->where('users.name', 'like', $term)
                    ->orWhere('users.lastname', 'like', $term));
            })
            ->orderByRaw('CASE WHEN last_visible_at IS NULL THEN 1 ELSE 0 END')
            ->orderByDesc('last_visible_at')
            ->orderBy('users.name')
            ->orderBy('users.id')
            ->simplePaginate(30);

        return response()->json(['status' => 'success', 'data' => [
            'users' => $users->getCollection()->map(fn ($user) => [
                'id' => $user->id,
                'name' => trim($user->name . ' ' . $user->lastname),
                'avatar' => $user->photo ? Storage::url($user->photo) : null,
                'last_message' => $user->last_body === null
                    ? ($user->last_visible_at ? 'Archivo adjunto' : null)
                    : Str::limit($user->last_body, 80),
                'last_message_at' => $user->last_visible_at,
                'unread_count' => (int) $user->unread_count,
                'is_online' => (bool) $user->is_online,
            ]),
            'has_more' => $users->hasMorePages(),
            'page' => $users->currentPage(),
        ]]);
    }

    public function messages(Request $request, User $user)
    {
        $data = $request->validate([
            'before_id' => ['nullable', 'integer', 'min:1', 'prohibits:after_id'],
            'after_id' => ['nullable', 'integer', 'min:0', 'prohibits:before_id'],
        ]);

        $conversation = $this->getOrCreateConversation($user);
        $state = $this->stateFor($conversation);
        $query = $conversation->messages()->with('attachments');
        $this->applyVisibleCutoff($query, $state);

        $after = isset($data['after_id']);
        if ($after) {
            $query->where('id', '>', $data['after_id'])->orderBy('id');
        } else {
            if (isset($data['before_id'])) {
                $query->where('id', '<', $data['before_id']);
            }
            $query->orderByDesc('id');
        }

        $batch = $query->limit(31)->get();
        $messages = $batch->take(30);
        if (!$after) {
            $messages = $messages->reverse();
        }

        $online = DB::table('chat_user_presence')
            ->where('user_id', $user->id)
            ->where('last_seen_at', '>=', now()->subSeconds(45))
            ->exists();

        return response()->json(['status' => 'success', 'data' => [
            'messages' => $messages->values()->map(fn ($message) => $this->messageData($message)),
            'has_more' => $batch->count() > 30,
            'is_online' => $online,
            'unread_count' => $this->conversationUnreadCount($conversation, $state),
            'peer' => $this->peerData($user, $online),
        ]]);
    }

    public function send(Request $request, User $user)
    {
        if (is_string($request->input('body'))) {
            $request->merge(['body' => trim($request->input('body'))]);
        }

        $data = $request->validate([
            'body' => [
                'bail', 'required_without:attachments', 'nullable', 'string', 'max:2000',
                function ($attribute, $value, $fail) {
                    if ($value !== strip_tags($value)) {
                        $fail('El mensaje debe contener solamente texto, sin HTML.');
                    }
                },
            ],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['bail', 'required', 'file', 'max:5120', new ChatAttachment],
        ], [
            'body.required_without' => 'Escribe un mensaje o adjunta al menos un archivo.',
            'attachments.max' => 'Puedes adjuntar como máximo 5 archivos por mensaje.',
            'attachments.*.max' => 'Cada archivo debe pesar como máximo 5 MB.',
        ]);

        $conversation = $this->getOrCreateConversation($user);
        $storedPaths = [];

        try {
            $message = DB::transaction(function () use ($conversation, $data, $request, &$storedPaths) {
                $locked = ChatConversation::whereKey($conversation->id)->lockForUpdate()->firstOrFail();
                $message = $locked->messages()->create([
                    'sender_id' => Auth::id(),
                    'body' => isset($data['body']) && $data['body'] !== '' ? $data['body'] : null,
                ]);

                foreach ($request->file('attachments', []) as $file) {
                    $name = Str::random(40) . '.' . strtolower($file->getClientOriginalExtension());
                    $storedPaths[] = $message->id . '/' . $name;
                    $path = ChatMessageAttachment::storage()->putFileAs((string) $message->id, $file, $name);
                    if (!$path) {
                        throw new \RuntimeException('No se pudo almacenar el adjunto.');
                    }
                    $message->attachments()->create([
                        'disk' => ChatMessageAttachment::DISK,
                        'path' => $path,
                        'original_name' => mb_substr($file->getClientOriginalName(), 0, 255),
                        'mime_type' => ChatAttachment::mimeType($file),
                        'file_size' => $file->getSize(),
                    ]);
                }

                $locked->update(['last_message_at' => $message->created_at]);

                ChatConversationUserState::where('conversation_id', $locked->id)
                    ->where('user_id', Auth::id())
                    ->update(['hidden_at' => null]);

                return $message->load('attachments');
            });
        } catch (\Throwable $exception) {
            foreach ($storedPaths as $path) {
                ChatMessageAttachment::storage()->delete($path);
            }
            report($exception);
            return response()->json([
                'status' => 'error',
                'message' => 'No se pudo enviar el mensaje. Inténtalo nuevamente.',
            ], 500);
        }

        return response()->json(['status' => 'success', 'data' => $this->messageData($message)], 201);
    }

    public function heartbeat()
    {
        $now = now();
        DB::table('chat_user_presence')->upsert([
            'user_id' => Auth::id(),
            'last_seen_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ], ['user_id'], ['last_seen_at', 'updated_at']);

        return response()->json(['status' => 'success', 'data' => ['is_online' => true]]);
    }

    public function attachment(ChatMessage $message, ChatMessageAttachment $attachment)
    {
        abort_unless((int) $attachment->message_id === (int) $message->id, 404);
        abort_unless($message->conversation()->forUser((int) Auth::id())->exists(), 404);

        $state = ChatConversationUserState::where('conversation_id', $message->conversation_id)
            ->where('user_id', Auth::id())
            ->first();
        if ($state?->cleared_through_message_id && (int) $message->id <= (int) $state->cleared_through_message_id) {
            abort(404);
        }

        abort_unless($attachment->disk === ChatMessageAttachment::DISK, 404);
        $storage = ChatMessageAttachment::storage();
        abort_unless($storage->exists($attachment->path), 404);

        $inline = in_array($attachment->mime_type, [
            'image/jpeg', 'image/png', 'image/webp', 'application/pdf',
        ], true);
        $extension = pathinfo($attachment->path, PATHINFO_EXTENSION);
        $base = Str::slug(pathinfo(str_replace('\\', '/', $attachment->original_name), PATHINFO_FILENAME));
        $downloadName = Str::limit($base ?: 'adjunto', 100, '') . '.' . $extension;

        return $storage->response($attachment->path, $downloadName, [
            'Content-Type' => $attachment->mime_type,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
            'Content-Security-Policy' => "default-src 'none'; sandbox",
        ], $inline ? 'inline' : 'attachment');
    }

    public function markRead(Request $request, User $user)
    {
        $data = $request->validate(['through_id' => ['nullable', 'integer', 'min:1']]);
        $conversation = $this->getOrCreateConversation($user);
        $state = $this->stateFor($conversation);
        $query = $conversation->messages()
            ->where('sender_id', '!=', Auth::id())
            ->whereNull('read_at');
        $this->applyVisibleCutoff($query, $state);

        $count = $query
            ->when(isset($data['through_id']), fn ($query) => $query->where('id', '<=', $data['through_id']))
            ->update(['read_at' => now()]);

        return response()->json(['status' => 'success', 'data' => ['marked' => $count]]);
    }

    public function unreadCount()
    {
        $me = (int) Auth::id();
        $count = ChatMessage::query()
            ->join('chat_conversations as conversations', 'conversations.id', '=', 'chat_messages.conversation_id')
            ->leftJoin('chat_conversation_user_states as state', function ($join) use ($me) {
                $join->on('state.conversation_id', '=', 'conversations.id')->where('state.user_id', $me);
            })
            ->where(function ($query) use ($me) {
                $query->where('conversations.user_one_id', $me)->orWhere('conversations.user_two_id', $me);
            })
            ->where('chat_messages.sender_id', '!=', $me)
            ->whereNull('chat_messages.read_at')
            ->where(function ($query) {
                $query->whereNull('state.cleared_through_message_id')
                    ->orWhereColumn('chat_messages.id', '>', 'state.cleared_through_message_id');
            })
            ->count();

        return response()->json(['status' => 'success', 'data' => ['unread_count' => $count]]);
    }

    public function clearConversation(User $user)
    {
        $conversation = $this->getOrCreateConversation($user);
        $state = $this->storeUserCutoff($conversation, false);

        return response()->json(['status' => 'success', 'data' => [
            'cleared_at' => $state->cleared_at?->toIso8601String(),
            'message' => 'La conversación se vació para ti.',
        ]]);
    }

    public function deleteConversation(User $user)
    {
        $conversation = $this->getOrCreateConversation($user);
        $state = $this->storeUserCutoff($conversation, true);

        return response()->json(['status' => 'success', 'data' => [
            'hidden_at' => $state->hidden_at?->toIso8601String(),
            'message' => 'La conversación se eliminó de tu vista.',
        ]]);
    }

    private function storeUserCutoff(ChatConversation $conversation, bool $hide): ChatConversationUserState
    {
        return DB::transaction(function () use ($conversation, $hide) {
            $locked = ChatConversation::whereKey($conversation->id)->lockForUpdate()->firstOrFail();
            $lastId = (int) ($locked->messages()->max('id') ?? 0);
            $now = now();

            $state = ChatConversationUserState::firstOrCreate([
                'conversation_id' => $locked->id,
                'user_id' => Auth::id(),
            ]);

            $state->forceFill([
                'cleared_at' => $now,
                'hidden_at' => $hide ? $now : null,
                'cleared_through_message_id' => $lastId,
            ])->save();

            return $state->fresh();
        });
    }

    private function getOrCreateConversation(User $otherUser): ChatConversation
    {
        $me = (int) Auth::id();
        if ($me === (int) $otherUser->id) {
            throw ValidationException::withMessages(['user' => 'No puedes conversar contigo mismo.']);
        }

        return ChatConversation::firstOrCreate([
            'user_one_id' => min($me, (int) $otherUser->id),
            'user_two_id' => max($me, (int) $otherUser->id),
        ]);
    }

    private function stateFor(ChatConversation $conversation): ?ChatConversationUserState
    {
        return ChatConversationUserState::where('conversation_id', $conversation->id)
            ->where('user_id', Auth::id())
            ->first();
    }

    private function applyVisibleCutoff($query, ?ChatConversationUserState $state): void
    {
        if ($state?->cleared_through_message_id) {
            $query->where('id', '>', $state->cleared_through_message_id);
        }
    }

    private function conversationUnreadCount(ChatConversation $conversation, ?ChatConversationUserState $state): int
    {
        $query = $conversation->messages()
            ->where('sender_id', '!=', Auth::id())
            ->whereNull('read_at');
        $this->applyVisibleCutoff($query, $state);

        return $query->count();
    }

    private function peerData(User $user, bool $online): array
    {
        return [
            'id' => $user->id,
            'name' => trim($user->name . ' ' . $user->lastname),
            'avatar' => $user->photo ? Storage::url($user->photo) : null,
            'is_online' => $online,
        ];
    }

    private function messageData(ChatMessage $message): array
    {
        return [
            'id' => $message->id,
            'body' => $message->body,
            'sender_id' => $message->sender_id,
            'is_mine' => (int) $message->sender_id === (int) Auth::id(),
            'created_at' => $message->created_at->toIso8601String(),
            'created_time' => $message->created_at->format('H:i'),
            'read_at' => $message->read_at?->toIso8601String(),
            'attachments' => $message->attachments->map(fn ($attachment) => [
                'id' => $attachment->id,
                'original_name' => $attachment->original_name,
                'mime_type' => $attachment->mime_type,
                'file_size' => $attachment->file_size,
                'is_image' => str_starts_with($attachment->mime_type, 'image/'),
                'url' => route('admin.chat.attachments.show', [$message, $attachment]),
            ]),
        ];
    }
}
