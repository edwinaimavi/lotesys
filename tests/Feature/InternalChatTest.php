<?php

namespace Tests\Feature;

use App\Models\ChatConversation;
use App\Models\ChatConversationUserState;
use App\Models\ChatMessage;
use App\Models\ChatMessageAttachment;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InternalChatTest extends TestCase
{
    private User $alice;
    private User $bob;
    private User $carol;

    protected function setUp(): void
    {
        parent::setUp();
        // Solo SQLite en memoria y la migración nueva del chat, nunca la BD local.
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('lastname')->nullable();
            $table->string('photo')->nullable();
            $table->string('email');
            $table->string('password');
            $table->string('remember_token')->nullable();
            $table->timestamps();
        });
        (require database_path('migrations/2026_09_19_000003_create_chat_tables.php'))->up();
        (require database_path('migrations/2026_09_19_000004_create_chat_user_presence_table.php'))->up();
        (require database_path('migrations/2026_09_19_000005_make_chat_message_body_nullable_and_create_attachments.php'))->up();
        (require database_path('migrations/2026_09_19_000006_create_chat_conversation_user_states_table.php'))->up();
        $storage = Storage::fake('chat_attachment_test');
        config(['chat.attachments_storage.root' => $storage->path('')]);
        $this->alice = User::create(['name' => 'Ana', 'lastname' => 'Pérez', 'email' => 'ana@example.test', 'password' => 'secret-ana']);
        $this->bob = User::create(['name' => 'Bruno', 'lastname' => 'López', 'email' => 'bruno@example.test', 'password' => 'secret-bruno']);
        $this->carol = User::create(['name' => 'Carla', 'lastname' => 'Díaz', 'email' => 'carla@example.test', 'password' => 'secret-carla']);
        $this->actingAs($this->alice);
    }

    private function send(User $to, string $body = 'Hola', array $extra = [])
    {
        return $this->postJson(route('admin.chat.send', $to), array_merge(['body' => $body], $extra));
    }

    private function conversation(): ChatConversation
    {
        return ChatConversation::firstOrCreate(['user_one_id' => $this->alice->id, 'user_two_id' => $this->bob->id]);
    }

    public function test_authenticated_user_sends_trimmed_text_with_trusted_sender(): void
    {
        $this->send($this->bob, '  Hola Bruno  ', ['sender_id' => $this->carol->id, 'conversation_id' => 999])
            ->assertCreated()->assertJsonPath('status', 'success')->assertJsonPath('data.body', 'Hola Bruno')
            ->assertJsonPath('data.sender_id', $this->alice->id)->assertJsonPath('data.is_mine', true);
        $this->assertDatabaseHas('chat_messages', ['sender_id' => $this->alice->id, 'body' => 'Hola Bruno']);
        $this->assertNotNull($this->conversation()->fresh()->last_message_at);
    }

    public function test_cannot_chat_with_self(): void
    {
        $this->send($this->alice)->assertUnprocessable()->assertJsonValidationErrors('user');
        $this->getJson(route('admin.chat.messages', $this->alice))->assertUnprocessable();
        $this->postJson(route('admin.chat.read', $this->alice))->assertUnprocessable();
        $this->assertSame(0, ChatConversation::count());
    }

    public function test_guests_cannot_use_any_endpoint(): void
    {
        auth()->forgetGuards();
        $this->getJson(route('admin.chat.users'))->assertUnauthorized();
        $this->getJson(route('admin.chat.messages', $this->bob))->assertUnauthorized();
        $this->postJson(route('admin.chat.send', $this->bob), ['body' => 'Hola'])->assertUnauthorized();
        $this->postJson(route('admin.chat.read', $this->bob))->assertUnauthorized();
        $this->getJson(route('admin.chat.unread-count'))->assertUnauthorized();
        $this->postJson(route('admin.chat.heartbeat'))->assertUnauthorized();
        $this->postJson(route('admin.chat.clear', $this->bob))->assertUnauthorized();
        $this->deleteJson(route('admin.chat.conversation.delete', $this->bob))->assertUnauthorized();
    }

    public function test_both_directions_and_repeated_opens_use_one_normalized_pair(): void
    {
        $this->actingAs($this->bob);
        $this->send($this->alice)->assertCreated();
        $this->actingAs($this->alice);
        $this->send($this->bob, 'Respuesta')->assertCreated();
        $this->getJson(route('admin.chat.messages', $this->bob))->assertOk();
        $this->assertSame(1, ChatConversation::count());
        $this->assertSame(2, $this->conversation()->messages()->count());
        $this->assertDatabaseHas('chat_conversations', ['user_one_id' => $this->alice->id, 'user_two_id' => $this->bob->id]);
    }

    public function test_database_unique_constraint_rejects_duplicate_pairs(): void
    {
        $this->conversation();
        $this->expectException(QueryException::class);
        ChatConversation::create(['user_one_id' => $this->bob->id, 'user_two_id' => $this->alice->id]);
    }

    public function test_third_user_cannot_read_or_mark_another_pair_even_with_supplied_ids(): void
    {
        $id = $this->send($this->bob, 'Privado A/B')->assertCreated()->json('data.id');
        $pairId = $this->conversation()->id;
        $this->actingAs($this->carol);
        $this->getJson(route('admin.chat.messages', $this->bob) . '?conversation_id=' . $pairId)
            ->assertOk()->assertJsonCount(0, 'data.messages');
        $this->postJson(route('admin.chat.read', $this->bob), ['conversation_id' => $pairId, 'through_id' => $id])->assertOk();
        $this->assertNull(ChatMessage::findOrFail($id)->read_at);
        $this->send($this->bob, 'C/B', ['conversation_id' => $pairId])->assertCreated();
        $this->assertSame(1, $this->conversation()->messages()->count());
        $this->getJson(route('admin.chat.users'))->assertDontSee('Privado A/B');
    }

    public function test_empty_message_is_rejected(): void
    {
        $this->send($this->bob, " \n\t ")->assertUnprocessable()->assertJsonValidationErrors('body');
        $this->assertSame(0, ChatMessage::count());
    }

    public function test_long_message_is_rejected(): void
    {
        $this->send($this->bob, str_repeat('a', 2001))->assertUnprocessable()->assertJsonValidationErrors('body');
        $this->send($this->bob, str_repeat('a', 2000))->assertCreated();
    }

    public function test_html_is_rejected_but_plain_text_is_preserved(): void
    {
        $this->send($this->bob, '<script>alert(1)</script>')->assertUnprocessable()->assertJsonValidationErrors('body');
        $this->send($this->bob, "Texto & detalles\nSegunda línea")->assertCreated()->assertJsonPath('data.body', "Texto & detalles\nSegunda línea");
    }

    public function test_initial_history_returns_last_thirty_only_for_this_pair(): void
    {
        $pair = $this->conversation();
        for ($i = 1; $i <= 65; $i++) {
            $pair->messages()->create(['sender_id' => $this->bob->id, 'body' => "Mensaje $i"]);
        }
        $this->send($this->carol, 'Otra pareja')->assertCreated();
        $this->getJson(route('admin.chat.messages', $this->bob))->assertOk()
            ->assertJsonCount(30, 'data.messages')->assertJsonPath('data.messages.0.body', 'Mensaje 36')
            ->assertJsonPath('data.messages.29.body', 'Mensaje 65')->assertJsonPath('data.has_more', true)
            ->assertJsonPath('data.messages.0.is_mine', false)->assertDontSee('Otra pareja');
    }

    public function test_after_cursor_returns_only_new_messages_in_ascending_order(): void
    {
        $first = $this->send($this->bob, 'Primero')->assertCreated()->json('data.id');
        $this->send($this->bob, 'Segundo')->assertCreated();
        $this->getJson(route('admin.chat.messages', $this->bob) . '?after_id=' . $first)->assertOk()
            ->assertJsonCount(1, 'data.messages')->assertJsonPath('data.messages.0.body', 'Segundo');
    }

    public function test_after_cursor_batches_do_not_skip_a_large_backlog(): void
    {
        $pair = $this->conversation();
        for ($i = 1; $i <= 35; $i++) {
            $pair->messages()->create(['sender_id' => $this->bob->id, 'body' => "Mensaje $i"]);
        }
        $first = $this->getJson(route('admin.chat.messages', $this->bob) . '?after_id=0')->assertOk()
            ->assertJsonCount(30, 'data.messages')->assertJsonPath('data.has_more', true);
        $cursor = $first->json('data.messages.29.id');
        $this->getJson(route('admin.chat.messages', $this->bob) . '?after_id=' . $cursor)->assertOk()
            ->assertJsonCount(5, 'data.messages')->assertJsonPath('data.messages.0.body', 'Mensaje 31');
    }

    public function test_before_cursor_returns_previous_history(): void
    {
        $this->send($this->bob, 'Anterior')->assertCreated();
        $last = $this->send($this->bob, 'Último')->assertCreated()->json('data.id');
        $this->getJson(route('admin.chat.messages', $this->bob) . '?before_id=' . $last)->assertOk()
            ->assertJsonCount(1, 'data.messages')->assertJsonPath('data.messages.0.body', 'Anterior');
        $this->getJson(route('admin.chat.messages', $this->bob) . '?before_id=2&after_id=1')->assertUnprocessable();
    }

    public function test_read_updates_only_received_messages_up_to_the_displayed_cursor(): void
    {
        $own = $this->send($this->bob, 'Propio')->assertCreated()->json('data.id');
        $this->actingAs($this->bob);
        $received = $this->send($this->alice, 'Recibido')->assertCreated()->json('data.id');
        $later = $this->send($this->alice, 'Todavía no cargado')->assertCreated()->json('data.id');
        $this->actingAs($this->alice);
        $this->postJson(route('admin.chat.read', $this->bob), ['through_id' => $received])->assertOk()->assertJsonPath('data.marked', 1);
        $this->assertNull(ChatMessage::findOrFail($own)->read_at);
        $this->assertNotNull(ChatMessage::findOrFail($received)->read_at);
        $this->assertNull(ChatMessage::findOrFail($later)->read_at);
    }

    public function test_global_unread_is_real_sum_and_excludes_unrelated_pairs(): void
    {
        $this->send($this->bob)->assertCreated();
        $this->actingAs($this->bob);
        $this->send($this->alice)->assertCreated();
        $this->send($this->alice)->assertCreated();
        $this->send($this->carol)->assertCreated();
        $this->actingAs($this->carol);
        $this->send($this->alice)->assertCreated();
        $this->actingAs($this->alice);
        $this->getJson(route('admin.chat.unread-count'))->assertOk()->assertJsonPath('data.unread_count', 3);
        $this->postJson(route('admin.chat.read', $this->bob))->assertOk();
        $this->getJson(route('admin.chat.unread-count'))->assertOk()->assertJsonPath('data.unread_count', 1);
    }

    public function test_directory_excludes_self_and_sensitive_fields_and_does_not_create_pairs(): void
    {
        $response = $this->getJson(route('admin.chat.users'))->assertOk()->assertJsonCount(2, 'data.users');
        $this->assertSame(0, ChatConversation::count());
        foreach ($response->json('data.users') as $user) {
            $this->assertNotSame($this->alice->id, $user['id']);
            $this->assertSame(['id', 'name', 'avatar', 'last_message', 'last_message_at', 'unread_count', 'is_online'], array_keys($user));
        }
        $response->assertDontSee('password')->assertDontSee('email')->assertDontSee('remember_token')->assertDontSee('secret-');
        $this->getJson(route('admin.chat.users') . '?search=López')->assertOk()->assertJsonCount(1, 'data.users')
            ->assertJsonPath('data.users.0.id', $this->bob->id);
    }

    public function test_directory_orders_recent_conversations_first(): void
    {
        $this->send($this->carol, 'Reciente')->assertCreated();
        $this->getJson(route('admin.chat.users'))->assertOk()->assertJsonPath('data.users.0.id', $this->carol->id)
            ->assertJsonPath('data.users.0.last_message', 'Reciente');
    }

    public function test_send_is_throttled_without_throttling_history(): void
    {
        for ($i = 0; $i < 60; $i++) {
            $this->send($this->bob, "Mensaje $i")->assertCreated();
        }
        $this->send($this->bob)->assertStatus(429);
        $this->getJson(route('admin.chat.messages', $this->bob))->assertOk();
    }

    public function test_csrf_is_required_when_test_bypass_is_disabled(): void
    {
        $this->app->instance('env', 'production');
        try {
            $this->send($this->bob)->assertStatus(419);
            $this->postJson(route('admin.chat.heartbeat'))->assertStatus(419);
            $this->withSession(['_token' => 'chat-test-token'])->withHeader('X-CSRF-TOKEN', 'chat-test-token');
            $this->send($this->bob)->assertCreated();
        } finally {
            $this->app->instance('env', 'testing');
        }
    }

    public function test_heartbeat_upserts_authenticated_presence_using_server_time(): void
    {
        $this->freezeTime();
        try {
            $this->postJson(route('admin.chat.heartbeat'), ['user_id' => $this->bob->id, 'last_seen_at' => '2099-01-01'])
                ->assertOk()->assertJsonPath('data.is_online', true);
            $this->assertDatabaseHas('chat_user_presence', ['user_id' => $this->alice->id, 'last_seen_at' => now()->toDateTimeString()]);
            $this->assertDatabaseMissing('chat_user_presence', ['user_id' => $this->bob->id]);
            $this->travel(20)->seconds();
            $this->postJson(route('admin.chat.heartbeat'))->assertOk();
            $this->assertDatabaseCount('chat_user_presence', 1);
            $this->assertDatabaseHas('chat_user_presence', ['user_id' => $this->alice->id, 'last_seen_at' => now()->toDateTimeString()]);
        } finally { $this->travelBack(); }
    }

    public function test_online_window_is_inclusive_at_45_seconds_and_expires_afterwards(): void
    {
        $this->freezeTime();
        try {
            $this->actingAs($this->bob)->postJson(route('admin.chat.heartbeat'))->assertOk();
            $this->actingAs($this->alice);
            $this->travel(45)->seconds();
            $this->getJson(route('admin.chat.users') . '?search=Bruno')->assertOk()->assertJsonPath('data.users.0.is_online', true);
            $this->getJson(route('admin.chat.messages', $this->bob))->assertOk()->assertJsonPath('data.is_online', true);
            $this->travel(1)->seconds();
            $this->getJson(route('admin.chat.users') . '?search=Bruno')->assertOk()->assertJsonPath('data.users.0.is_online', false);
            $this->getJson(route('admin.chat.messages', $this->bob))->assertOk()->assertJsonPath('data.is_online', false);
        } finally { $this->travelBack(); }
    }

    public function test_authenticated_session_without_heartbeat_is_not_online(): void
    {
        $this->actingAs($this->bob)->getJson(route('admin.chat.unread-count'))->assertOk();
        $this->actingAs($this->alice)->getJson(route('admin.chat.users') . '?search=Bruno')
            ->assertOk()->assertJsonPath('data.users.0.is_online', false);
    }

    public function test_image_only_message_has_nullable_body_and_private_attachment(): void
    {
        $response = $this->send($this->bob, '', ['attachments' => [UploadedFile::fake()->image('foto.jpg')]])
            ->assertCreated()->assertJsonPath('data.body', null)->assertJsonCount(1, 'data.attachments')
            ->assertJsonPath('data.attachments.0.is_image', true)->assertJsonMissingPath('data.attachments.0.path');
        $attachment = ChatMessageAttachment::firstOrFail();
        $this->assertTrue(ChatMessageAttachment::storage()->exists($attachment->path));
        $this->assertNotSame('foto.jpg', basename($attachment->path));
        $this->assertSame($response->json('data.id'), $attachment->message_id);
        $this->assertArrayNotHasKey('path', $attachment->toArray());
        $this->getJson(route('admin.chat.users') . '?search=Bruno')->assertJsonPath('data.users.0.last_message', 'Archivo adjunto');
    }

    public function test_pdf_only_message_and_inline_response(): void
    {
        $pdf = UploadedFile::fake()->createWithContent('contrato.pdf', "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF");
        $response = $this->send($this->bob, '', ['attachments' => [$pdf]])->assertCreated()->assertJsonPath('data.body', null)
            ->assertJsonPath('data.attachments.0.mime_type', 'application/pdf');
        $download = $this->get($response->json('data.attachments.0.url'))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('inline;', $download->headers->get('Content-Disposition'));
    }

    public function test_completely_empty_message_and_empty_attachment_array_are_rejected(): void
    {
        $this->send($this->bob, ' ', ['attachments' => []])->assertUnprocessable()->assertJsonValidationErrors('body');
        $this->assertSame(0, ChatMessage::count());
    }

    public function test_attachment_size_and_five_file_limit(): void
    {
        $this->send($this->bob, '', ['attachments' => [UploadedFile::fake()->image('grande.jpg')->size(5121)]])
            ->assertUnprocessable()->assertJsonValidationErrors('attachments.0');
        $files = array_map(fn ($i) => UploadedFile::fake()->image("foto$i.png"), range(1, 6));
        $this->send($this->bob, '', ['attachments' => $files])->assertUnprocessable()->assertJsonValidationErrors('attachments');
        $response = $this->send($this->bob, 'Cinco fotos', ['attachments' => array_slice($files, 0, 5)])->assertCreated();
        $this->assertSame(1, ChatMessage::count());
        $this->assertSame(5, ChatMessage::findOrFail($response->json('data.id'))->attachments()->count());
    }

    public function test_forbidden_extensions_and_disguised_content_are_rejected(): void
    {
        foreach (['php', 'exe', 'js', 'html', 'svg', 'bat', 'cmd', 'zip', 'rar', 'jpg', 'pdf', 'doc', 'xls'] as $extension) {
            $file = UploadedFile::fake()->createWithContent('archivo.' . $extension, '<?php echo "unsafe";');
            $this->send($this->bob, '', ['attachments' => [$file]])->assertUnprocessable()->assertJsonValidationErrors('attachments.0');
        }
        $this->assertSame(0, ChatMessageAttachment::count());
    }

    public function test_safe_text_download_and_participant_access(): void
    {
        $file = UploadedFile::fake()->createWithContent('notas de reunión.txt', 'Notas del proyecto.');
        $response = $this->send($this->bob, '', ['attachments' => [$file]])->assertCreated();
        $url = $response->json('data.attachments.0.url');
        $this->actingAs($this->bob);
        $download = $this->get($url)->assertOk()->assertHeader('Content-Type', 'text/plain; charset=UTF-8')->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertStringContainsString('attachment; filename=notas-de-reunion.txt', $download->headers->get('Content-Disposition'));
        $this->actingAs($this->carol)->get($url)->assertNotFound();
        auth()->forgetGuards();
        $this->getJson($url)->assertUnauthorized();
    }

    public function test_attachment_must_belong_to_the_requested_message(): void
    {
        $response = $this->send($this->bob, '', ['attachments' => [UploadedFile::fake()->image('foto.jpg')]])->assertCreated();
        $other = $this->send($this->bob)->assertCreated()->json('data.id');
        $this->get(route('admin.chat.attachments.show', [$other, $response->json('data.attachments.0.id')]))->assertNotFound();
    }

    public function test_history_and_polling_include_attachment_metadata_without_paths(): void
    {
        $response = $this->send($this->bob, 'Documento', ['attachments' => [UploadedFile::fake()->image('foto.png')]])->assertCreated();
        $this->actingAs($this->bob);
        foreach (['', '?after_id=0'] as $cursor) {
            $this->getJson(route('admin.chat.messages', $this->alice) . $cursor)->assertOk()
                ->assertJsonCount(1, 'data.messages.0.attachments')
                ->assertJsonPath('data.messages.0.attachments.0.id', $response->json('data.attachments.0.id'))
                ->assertJsonMissingPath('data.messages.0.attachments.0.path')
                ->assertJsonMissingPath('data.messages.0.attachments.0.disk');
        }
    }

    public function test_attachment_failure_rolls_back_message_and_removes_all_files(): void
    {
        $count = 0;
        ChatMessageAttachment::creating(function () use (&$count) {
            if (++$count === 2) throw new \RuntimeException('Simulated attachment failure');
        });
        try {
            $this->send($this->bob, 'Dos fotos', ['attachments' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.jpg')]])
                ->assertStatus(500);
            $this->assertSame(0, ChatMessage::count());
            $this->assertSame(0, ChatMessageAttachment::count());
            $this->assertSame([], ChatMessageAttachment::storage()->allFiles());
            $this->assertNull($this->conversation()->fresh()->last_message_at);
        } finally { ChatMessageAttachment::flushEventListeners(); }
    }

    public function test_ooxml_documents_are_checked_as_packages_not_arbitrary_zip_files(): void
    {
        foreach (['docx', 'xlsx'] as $extension) {
            $path = tempnam(sys_get_temp_dir(), 'chat-office-');
            try {
                $file = new UploadedFile($path, 'documento.' . $extension, null, null, true);
                $zip = new \ZipArchive;
                $zip->open($file->getPathname(), \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
                $zip->addFromString('archivo.txt', 'Un ZIP no es un documento Office');
                $zip->close();
                $this->send($this->bob, '', ['attachments' => [$file]])->assertUnprocessable();
                $zip->open($file->getPathname());
                $document = $extension === 'docx' ? 'word/document.xml' : 'xl/workbook.xml';
                $type = $extension === 'docx' ? 'wordprocessingml.document' : 'spreadsheetml.sheet';
                $zip->addFromString($document, '<?xml version="1.0"?><document/>');
                $zip->addFromString('_rels/.rels', '<?xml version="1.0"?><Relationships/>');
                $zip->addFromString('[Content_Types].xml', '<Types><Override ContentType="application/vnd.openxmlformats-officedocument.' . $type . '.main+xml"/></Types>');
                $zip->close();
                clearstatcache(true, $path);
                $this->send($this->bob, '', ['attachments' => [$file]])->assertCreated();
            } finally {
                if (is_file($path)) unlink($path);
            }
        }
    }

    public function test_zero_is_valid_text_and_is_not_converted_to_null(): void
    {
        $this->send($this->bob, '0')->assertCreated()->assertJsonPath('data.body', '0');
    }
    public function test_clear_hides_previous_messages_only_for_current_user(): void
    {
        $this->send($this->bob, 'Antes de vaciar')->assertCreated();

        $this->postJson(route('admin.chat.clear', $this->bob))->assertOk();
        $this->getJson(route('admin.chat.messages', $this->bob))->assertOk()->assertJsonCount(0, 'data.messages');

        $this->actingAs($this->bob);
        $this->getJson(route('admin.chat.messages', $this->alice))->assertOk()
            ->assertJsonCount(1, 'data.messages')
            ->assertJsonPath('data.messages.0.body', 'Antes de vaciar');

        $this->assertSame(1, ChatMessage::count());
    }

    public function test_clear_keeps_attachment_records_and_files_but_revokes_old_link_for_that_user(): void
    {
        $response = $this->send($this->bob, '', [
            'attachments' => [UploadedFile::fake()->image('evidencia.jpg')],
        ])->assertCreated();
        $attachment = ChatMessageAttachment::firstOrFail();
        $url = $response->json('data.attachments.0.url');

        $this->postJson(route('admin.chat.clear', $this->bob))->assertOk();

        $this->assertSame(1, ChatMessageAttachment::count());
        $this->assertTrue(ChatMessageAttachment::storage()->exists($attachment->path));
        $this->get($url)->assertNotFound();

        $this->actingAs($this->bob)->get($url)->assertOk();
    }

    public function test_delete_hides_recent_history_only_for_current_user(): void
    {
        $this->send($this->bob, 'Conversación visible')->assertCreated();

        $this->deleteJson(route('admin.chat.conversation.delete', $this->bob))->assertOk();
        $this->getJson(route('admin.chat.users') . '?search=Bruno')->assertOk()
            ->assertJsonPath('data.users.0.last_message', null)
            ->assertJsonPath('data.users.0.unread_count', 0);
        $this->getJson(route('admin.chat.messages', $this->bob))->assertOk()->assertJsonCount(0, 'data.messages');

        $this->actingAs($this->bob);
        $this->getJson(route('admin.chat.users') . '?search=Ana')->assertOk()
            ->assertJsonPath('data.users.0.last_message', 'Conversación visible');
        $this->getJson(route('admin.chat.messages', $this->alice))->assertOk()->assertJsonCount(1, 'data.messages');

        $this->assertSame(1, ChatConversation::count());
        $this->assertSame(1, ChatMessage::count());
    }

    public function test_new_message_after_delete_makes_conversation_visible_again_without_old_history(): void
    {
        $this->send($this->bob, 'Mensaje antiguo')->assertCreated();
        $this->deleteJson(route('admin.chat.conversation.delete', $this->bob))->assertOk();

        $this->actingAs($this->bob);
        $this->send($this->alice, 'Mensaje nuevo')->assertCreated();

        $this->actingAs($this->alice);
        $this->getJson(route('admin.chat.users') . '?search=Bruno')->assertOk()
            ->assertJsonPath('data.users.0.last_message', 'Mensaje nuevo')
            ->assertJsonPath('data.users.0.unread_count', 1);
        $this->getJson(route('admin.chat.messages', $this->bob))->assertOk()
            ->assertJsonCount(1, 'data.messages')
            ->assertJsonPath('data.messages.0.body', 'Mensaje nuevo');
        $this->assertSame(1, ChatConversation::count());
    }

    public function test_own_new_message_after_delete_reuses_same_conversation_and_reappears(): void
    {
        $this->send($this->bob, 'Viejo')->assertCreated();
        $conversationId = $this->conversation()->id;
        $this->deleteJson(route('admin.chat.conversation.delete', $this->bob))->assertOk();

        $this->send($this->bob, 'Vuelvo a escribir')->assertCreated();

        $this->assertSame(1, ChatConversation::count());
        $this->assertSame($conversationId, $this->conversation()->id);
        $this->getJson(route('admin.chat.messages', $this->bob))->assertOk()
            ->assertJsonCount(1, 'data.messages')
            ->assertJsonPath('data.messages.0.body', 'Vuelvo a escribir');
        $this->getJson(route('admin.chat.users') . '?search=Bruno')->assertOk()
            ->assertJsonPath('data.users.0.last_message', 'Vuelvo a escribir');
    }

    public function test_old_unread_messages_stop_counting_after_clear(): void
    {
        $this->actingAs($this->bob);
        $this->send($this->alice, 'No leído 1')->assertCreated();
        $this->send($this->alice, 'No leído 2')->assertCreated();

        $this->actingAs($this->alice);
        $this->getJson(route('admin.chat.unread-count'))->assertOk()->assertJsonPath('data.unread_count', 2);
        $this->postJson(route('admin.chat.clear', $this->bob))->assertOk();
        $this->getJson(route('admin.chat.unread-count'))->assertOk()->assertJsonPath('data.unread_count', 0);

        $this->assertSame(2, ChatMessage::whereNull('read_at')->count());
    }

    public function test_clear_and_delete_cannot_target_self(): void
    {
        $this->postJson(route('admin.chat.clear', $this->alice))->assertUnprocessable()->assertJsonValidationErrors('user');
        $this->deleteJson(route('admin.chat.conversation.delete', $this->alice))->assertUnprocessable()->assertJsonValidationErrors('user');
        $this->assertSame(0, ChatConversationUserState::count());
    }

    public function test_supplied_conversation_id_cannot_clear_or_delete_another_pair(): void
    {
        $this->send($this->bob, 'Privado A/B')->assertCreated();
        $ab = $this->conversation();

        $this->actingAs($this->carol);
        $this->postJson(route('admin.chat.clear', $this->bob), ['conversation_id' => $ab->id])->assertOk();
        $this->assertDatabaseMissing('chat_conversation_user_states', [
            'conversation_id' => $ab->id,
            'user_id' => $this->carol->id,
        ]);

        $this->deleteJson(route('admin.chat.conversation.delete', $this->bob), ['conversation_id' => $ab->id])->assertOk();
        $this->assertDatabaseMissing('chat_conversation_user_states', [
            'conversation_id' => $ab->id,
            'user_id' => $this->carol->id,
        ]);
        $this->assertSame(1, $ab->messages()->count());
    }

    public function test_clear_cutoff_uses_message_id_so_same_second_new_message_is_visible(): void
    {
        $this->freezeTime();
        try {
            $oldId = $this->send($this->bob, 'Antes')->assertCreated()->json('data.id');
            $this->postJson(route('admin.chat.clear', $this->bob))->assertOk();
            $newId = $this->send($this->bob, 'Después')->assertCreated()->json('data.id');

            $this->assertGreaterThan($oldId, $newId);
            $this->getJson(route('admin.chat.messages', $this->bob))->assertOk()
                ->assertJsonCount(1, 'data.messages')
                ->assertJsonPath('data.messages.0.body', 'Después');
        } finally {
            $this->travelBack();
        }
    }


}
