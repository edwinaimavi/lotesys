<div id="krea-chat" class="krea-chat"
    data-current-user-id="{{ auth()->id() }}"
    data-users-url="{{ route('admin.chat.users') }}"
    data-unread-url="{{ route('admin.chat.unread-count') }}"
    data-heartbeat-url="{{ route('admin.chat.heartbeat') }}"
    data-messages-url="{{ route('admin.chat.messages', ['user' => '__USER__']) }}"
    data-read-url="{{ route('admin.chat.read', ['user' => '__USER__']) }}"
    data-clear-url="{{ route('admin.chat.clear', ['user' => '__USER__']) }}"
    data-delete-url="{{ route('admin.chat.conversation.delete', ['user' => '__USER__']) }}">

    <section id="krea-chat-panel" class="krea-chat-panel" aria-label="Chat interno" hidden>
        <header class="krea-chat-header">
            <div class="krea-chat-heading">
                <h2>Chat interno</h2>
                <span>Conversaciones privadas</span>
            </div>
            <button type="button" id="krea-chat-close" class="krea-chat-icon" aria-label="Cerrar lista de chat">
                <i class="fas fa-times" aria-hidden="true"></i>
            </button>
        </header>

        <div id="krea-chat-error" class="krea-chat-error" role="status" hidden></div>

        <div class="krea-chat-directory">
            <label class="krea-chat-search">
                <span class="sr-only">Buscar usuario</span>
                <i class="fas fa-search" aria-hidden="true"></i>
                <input id="krea-chat-search" type="search" placeholder="Buscar usuario..." maxlength="100" autocomplete="off">
            </label>
            <div id="krea-chat-users" class="krea-chat-users" aria-label="Usuarios"></div>
            <button type="button" id="krea-chat-more-users" class="krea-chat-more" hidden>Más usuarios</button>
        </div>
    </section>

    <div id="krea-chat-windows" class="krea-chat-windows" aria-label="Conversaciones abiertas"></div>

    <button type="button" id="krea-chat-launcher" class="krea-chat-launcher" aria-label="Abrir chat interno"
        aria-controls="krea-chat-panel" aria-expanded="false">
        <i class="fas fa-comments" aria-hidden="true"></i>
        <span id="krea-chat-badge" class="krea-chat-badge" hidden>0</span>
    </button>
</div>

<dialog id="krea-chat-viewer" class="krea-chat-viewer" aria-labelledby="krea-chat-viewer-title">
    <header>
        <strong id="krea-chat-viewer-title">Imagen adjunta</strong>
        <button type="button" id="krea-chat-viewer-close" aria-label="Cerrar visor">&times;</button>
    </header>
    <img id="krea-chat-viewer-image" alt="Imagen adjunta ampliada">
</dialog>

@push('css')
    <link rel="stylesheet" href="{{ asset('css/kreasys-chat.css') }}">
@endpush
@push('js')
    @vite(['resources/js/pages/chat.js'])
@endpush
