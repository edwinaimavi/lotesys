const widget = document.getElementById('krea-chat');

if (widget) {
    document.body.append(widget);

    const el = id => document.getElementById(`krea-chat-${id}`);
    const panel = el('panel');
    const usersContainer = el('users');
    const windowsContainer = el('windows');
    const viewer = el('viewer');
    document.body.append(viewer);

    const currentUserId = Number(widget.dataset.currentUserId || 0);
    const storageKey = `kreasys_chat_windows_${currentUserId}`;
    const userCache = new Map();
    const windows = new Map();
    const processedAudioIds = new Set();

    let directoryOpen = false;
    let directoryBusy = false;
    let directoryTimer;
    let unreadTimer;
    let windowPollTimer;
    let heartbeatTimer;
    let heartbeatBusy = false;
    let heartbeatController;
    let searchTimer;
    let search = '';
    let usersPage = 1;
    let requestedPage = 1;
    let stopped = false;
    let audioContext;
    let lastUsedSequence = 0;

    const blocked = () => document.body.classList.contains('modal-open') ||
        document.body.classList.contains('swal2-shown') || Boolean(document.querySelector('dialog[open]'));

    const urlFor = (kind, id) => widget.dataset[kind].replace('__USER__', encodeURIComponent(id));

    const maxWindows = () => {
        if (window.innerWidth < 768) return 1;
        if (window.innerWidth < 1200) return 3;
        return 5;
    };

    function directoryError(message = '') {
        el('error').textContent = message;
        el('error').hidden = !message;
    }

    function windowError(state, message = '') {
        state.error.textContent = message;
        state.error.hidden = !message;
    }

    function unlockSound() {
        try {
            const Audio = window.AudioContext || window.webkitAudioContext;
            if (!Audio) return;
            audioContext ||= new Audio();
            if (audioContext.state === 'suspended') audioContext.resume().catch(() => {});
        } catch { /* El audio es accesorio; nunca debe romper el chat. */ }
    }

    const CHAT_NAME_TITLES = new Set([
        'ing', 'ingeniero', 'ingeniera', 'sr', 'sra', 'srta',
        'dr', 'dra', 'lic', 'arq', 'prof', 'profesor', 'profesora'
    ]);

    function cleanNameParts(name) {
        const parts = String(name || '')
            .trim()
            .split(/\s+/)
            .filter(Boolean);

        while (parts.length > 1) {
            const normalized = parts[0].toLocaleLowerCase('es')
                .replace(/[.,:;]+$/g, '');
            if (!CHAT_NAME_TITLES.has(normalized)) break;
            parts.shift();
        }

        return parts;
    }

    function compactDisplayName(name) {
        const parts = cleanNameParts(name);
        if (!parts.length) return 'Usuario';
        if (parts.length === 1) return parts[0];

        const surnameInitial = Array.from(parts[1])[0]?.toLocaleUpperCase('es') || '';
        return surnameInitial ? `${parts[0]} ${surnameInitial}.` : parts[0];
    }

    function userInitials(name) {
        const parts = cleanNameParts(name);
        return parts
            .slice(0, 2)
            .map(part => Array.from(part)[0]?.toLocaleUpperCase('es') || '')
            .join('') || 'U';
    }

    function playChatTone(sent = false) {
        if (document.hidden || audioContext?.state !== 'running') return;

        try {
            const now = audioContext.currentTime;
            const notes = sent
                ? [
                    { at: 0, from: 760, to: 1040, duration: .095, volume: .065 },
                ]
                : [
                    { at: 0, from: 620, to: 820, duration: .095, volume: .115 },
                    { at: .105, from: 850, to: 1120, duration: .115, volume: .105 },
                ];

            notes.forEach(note => {
                const start = now + note.at;
                const oscillator = audioContext.createOscillator();
                const gain = audioContext.createGain();

                oscillator.type = sent ? 'sine' : 'triangle';
                oscillator.frequency.setValueAtTime(note.from, start);
                oscillator.frequency.exponentialRampToValueAtTime(note.to, start + note.duration);

                gain.gain.setValueAtTime(.0001, start);
                gain.gain.exponentialRampToValueAtTime(note.volume, start + .012);
                gain.gain.setValueAtTime(note.volume, start + Math.max(.014, note.duration * .45));
                gain.gain.exponentialRampToValueAtTime(.0001, start + note.duration);

                oscillator.connect(gain);
                gain.connect(audioContext.destination);
                oscillator.start(start);
                oscillator.stop(start + note.duration + .01);
                oscillator.onended = () => {
                    oscillator.disconnect();
                    gain.disconnect();
                };
            });
        } catch { /* Restricciones del navegador no deben interrumpir el chat. */ }
    }

    function messageSound(messages, sent = false) {
        const fresh = messages.filter(message => !processedAudioIds.has(message.id));
        fresh.forEach(message => processedAudioIds.add(message.id));
        if (!fresh.length) return;
        playChatTone(sent);
    }

    const fileIcon = name => {
        const extension = String(name || '').split('.').pop().toLowerCase();
        if (extension === 'pdf') return 'fas fa-file-pdf';
        if (['doc', 'docx'].includes(extension)) return 'fas fa-file-word';
        if (['xls', 'xlsx'].includes(extension)) return 'fas fa-file-excel';
        return 'fas fa-file-alt';
    };

    const fileSize = size => Number(size || 0) >= 1048576
        ? `${(Number(size) / 1048576).toFixed(1)} MB`
        : `${Math.max(0, Number(size || 0) / 1024).toFixed(1)} KB`;

    async function json(url, { method = 'GET', body, signal } = {}) {
        const controller = new AbortController();
        const cancel = () => controller.abort();
        signal?.addEventListener('abort', cancel, { once: true });
        if (signal?.aborted) cancel();

        const multipart = body instanceof FormData;
        const timeout = setTimeout(cancel, multipart ? 120000 : 15000);

        try {
            const response = await fetch(url, {
                method,
                signal: controller.signal,
                credentials: 'same-origin',
                cache: 'no-store',
                headers: {
                    Accept: 'application/json',
                    ...(multipart ? {} : { 'Content-Type': 'application/json' }),
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                body: body === undefined ? undefined : (multipart ? body : JSON.stringify(body)),
            });

            if (response.status === 401 || response.status === 419) {
                stopped = true;
                throw new Error('Tu sesión ha caducado. Recarga la página para continuar.');
            }

            const result = await response.json().catch(() => ({}));
            if (!response.ok) {
                const validation = Object.values(result.errors || {})[0]?.[0];
                throw new Error(validation || result.message || (response.status === 429
                    ? 'Has enviado muchos mensajes. Espera un minuto.'
                    : 'No se pudo conectar con el chat. Inténtalo nuevamente.'));
            }

            return result.data;
        } finally {
            clearTimeout(timeout);
            signal?.removeEventListener('abort', cancel);
        }
    }

    function avatar(node, user) {
        node.replaceChildren();
        const fallback = () => {
            const initials = document.createElement('span');
            initials.className = 'krea-chat-avatar-initials';
            initials.textContent = userInitials(user?.name);
            initials.setAttribute('aria-hidden', 'true');
            node.replaceChildren(initials);
        };

        if (user?.avatar) {
            const image = document.createElement('img');
            image.src = user.avatar;
            image.alt = '';
            image.addEventListener('error', fallback, { once: true });
            node.append(image);
        } else {
            fallback();
        }
    }

    function presence(node, online) {
        node.textContent = typeof online !== 'boolean' ? '' : (online ? 'En línea' : 'Desconectado');
        node.classList.toggle('is-online', Boolean(online));
    }

    function saveWindowState() {
        try {
            const data = [...windows.values()]
                .sort((a, b) => a.lastUsed - b.lastUsed)
                .map(state => ({ user_id: state.peer.id, minimized: state.minimized }));
            localStorage.setItem(storageKey, JSON.stringify(data));
        } catch { /* Preferencia visual opcional. */ }
    }

    function readStoredWindows() {
        try {
            const parsed = JSON.parse(localStorage.getItem(storageKey) || '[]');
            return Array.isArray(parsed) ? parsed
                .filter(item => Number(item?.user_id) > 0)
                .slice(-maxWindows()) : [];
        } catch {
            return [];
        }
    }

    function updateMobileState() {
        widget.classList.toggle('has-mobile-window', window.innerWidth < 768 && windows.size > 0);
    }

    function touchWindow(state) {
        state.lastUsed = ++lastUsedSequence;
        saveWindowState();
    }

    function enforceWindowLimit(exceptId = null) {
        const limit = maxWindows();
        while (windows.size >= limit && !windows.has(Number(exceptId))) {
            const victim = [...windows.values()]
                .sort((a, b) => a.lastUsed - b.lastUsed)[0];
            if (!victim) break;
            closeWindow(victim, false);
        }
    }

    function renderUsers() {
        const scrollTop = usersContainer.scrollTop;
        const focusedId = document.activeElement?.dataset.chatUser;
        const fragment = document.createDocumentFragment();
        const users = [...userCache.values()].sort((a, b) =>
            String(b.last_message_at || '').localeCompare(String(a.last_message_at || '')) ||
            a.name.localeCompare(b.name) || Number(a.id) - Number(b.id));

        for (const user of users) {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'krea-chat-user';
            button.dataset.chatUser = user.id;

            const picture = document.createElement('span');
            picture.className = 'krea-chat-avatar';
            avatar(picture, user);

            const copy = document.createElement('span');
            copy.className = 'krea-chat-user-copy';
            const name = document.createElement('strong');
            name.textContent = user.name;
            const preview = document.createElement('span');
            preview.className = 'krea-chat-preview';
            preview.textContent = user.last_message ?? 'Iniciar conversación';
            const online = document.createElement('span');
            online.className = 'krea-chat-presence';
            presence(online, user.is_online);
            copy.append(name, preview, online);

            const meta = document.createElement('span');
            meta.className = 'krea-chat-user-meta';
            if (user.last_message_at) {
                const time = document.createElement('time');
                time.textContent = String(user.last_message_at).slice(11, 16);
                time.title = user.last_message_at;
                meta.append(time);
            }
            if (user.unread_count) {
                const count = document.createElement('span');
                count.className = 'krea-chat-user-count';
                count.textContent = user.unread_count > 99 ? '99+' : user.unread_count;
                count.setAttribute('aria-label', `${user.unread_count} mensajes no leídos`);
                meta.append(count);
            }

            button.append(picture, copy, meta);
            button.addEventListener('click', () => {
                openConversation(user);
                if (window.innerWidth >= 768) setDirectoryOpen(false);
            });
            fragment.append(button);
        }

        if (!users.length) {
            const empty = document.createElement('p');
            empty.className = 'krea-chat-empty';
            empty.textContent = 'No se encontraron usuarios.';
            fragment.append(empty);
        }

        usersContainer.replaceChildren(fragment);
        usersContainer.scrollTop = scrollTop;
        if (focusedId) {
            usersContainer.querySelector(`[data-chat-user="${Number(focusedId)}"]`)?.focus({ preventScroll: true });
        }
    }

    function setDirectoryOpen(open) {
        directoryOpen = Boolean(open);
        panel.hidden = !directoryOpen;
        el('launcher').setAttribute('aria-expanded', String(directoryOpen));
        if (directoryOpen) {
            el('search').focus();
            void refreshDirectory(true);
        }
    }

    async function refreshDirectory(force = false) {
        if ((!directoryOpen && !force) || directoryBusy || document.hidden || stopped || blocked()) return;
        directoryBusy = true;
        try {
            const page = requestedPage;
            const url = new URL(widget.dataset.usersUrl);
            url.searchParams.set('search', search);
            url.searchParams.set('page', page);
            const data = await json(url);

            if (page === 1) {
                userCache.clear();
            }
            data.users.forEach(user => userCache.set(Number(user.id), user));
            renderUsers();

            if (page >= usersPage) {
                usersPage = page;
                el('more-users').hidden = !data.has_more;
            }
            if (requestedPage === page) requestedPage = 1;
            directoryError('');
        } catch (exception) {
            directoryError(exception.message);
        } finally {
            directoryBusy = false;
            el('more-users').disabled = false;
        }
    }

    async function refreshUnread() {
        if (document.hidden || stopped) return;
        try {
            const data = await json(widget.dataset.unreadUrl);
            const count = Number(data.unread_count || 0);
            el('badge').textContent = count > 99 ? '99+' : count;
            el('badge').hidden = count === 0;
            el('launcher').setAttribute('aria-label', `Chat interno, ${count} mensajes no leídos`);
        } catch { /* El siguiente ciclo reintenta. */ }
    }

    async function heartbeat() {
        clearTimeout(heartbeatTimer);
        if (document.hidden || stopped) return;
        if (heartbeatBusy) return;
        heartbeatBusy = true;
        heartbeatController = new AbortController();
        try {
            await json(widget.dataset.heartbeatUrl, {
                method: 'POST',
                body: {},
                signal: heartbeatController.signal,
            });
        } catch { /* La presencia se reintentará sin bloquear el chat. */ }
        finally {
            heartbeatBusy = false;
            if (!document.hidden && !stopped) heartbeatTimer = setTimeout(heartbeat, 20000);
        }
    }

    function createStaticWindowMarkup() {
        const root = document.createElement('section');
        root.className = 'krea-chat-window';
        root.innerHTML = `
            <header class="krea-chat-window-header">
                <span class="krea-chat-avatar krea-chat-window-avatar"></span>
                <button type="button" class="krea-chat-window-identity" aria-label="Restaurar conversación">
                    <strong class="krea-chat-window-name">Cargando...</strong>
                    <span class="krea-chat-presence krea-chat-window-presence"></span>
                </button>
                <span class="krea-chat-window-unread" hidden>0</span>
                <button type="button" class="krea-chat-icon krea-chat-minimize" aria-label="Minimizar conversación" title="Minimizar">
                    <i class="fas fa-minus"></i>
                </button>
                <div class="krea-chat-actions">
                    <button type="button" class="krea-chat-icon krea-chat-menu-toggle" aria-label="Opciones de conversación" title="Opciones">
                        <i class="fas fa-ellipsis-v"></i>
                    </button>
                    <div class="krea-chat-menu" hidden>
                        <button type="button" class="krea-chat-clear"><i class="fas fa-broom"></i> Vaciar chat</button>
                        <button type="button" class="krea-chat-delete"><i class="fas fa-trash-alt"></i> Eliminar conversación</button>
                    </div>
                </div>
                <button type="button" class="krea-chat-icon krea-chat-window-close" aria-label="Cerrar conversación" title="Cerrar">
                    <i class="fas fa-times"></i>
                </button>
            </header>
            <div class="krea-chat-window-body">
                <div class="krea-chat-window-error krea-chat-error" hidden></div>
                <div class="krea-chat-history" role="log" aria-live="polite" aria-relevant="additions">
                    <button type="button" class="krea-chat-more krea-chat-older" hidden>Cargar mensajes anteriores</button>
                    <div class="krea-chat-messages"></div>
                    <p class="krea-chat-empty">Todavía no hay mensajes. Inicia la conversación.</p>
                </div>
                <button type="button" class="krea-chat-new" hidden>Nuevos mensajes ↓</button>
                <div class="krea-chat-attachment-tray" aria-label="Adjuntos seleccionados" hidden></div>
                <form class="krea-chat-composer" enctype="multipart/form-data">
                    <button type="button" class="krea-chat-attach" aria-label="Adjuntar archivos" title="Adjuntar hasta 5 archivos de 5 MB">
                        <i class="fas fa-paperclip"></i>
                    </button>
                    <input class="krea-chat-files" type="file" multiple accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.xls,.xlsx,.txt" hidden>
                    <label class="sr-only">Mensaje</label>
                    <textarea class="krea-chat-body" rows="2" maxlength="2000" placeholder="Escribe un mensaje..."></textarea>
                    <button type="submit" class="krea-chat-send" aria-label="Enviar mensaje" title="Enviar mensaje">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </form>
            </div>`;
        return root;
    }

    function bindWindowElements(state) {
        const q = selector => state.root.querySelector(selector);
        state.avatar = q('.krea-chat-window-avatar');
        state.identity = q('.krea-chat-window-identity');
        state.name = q('.krea-chat-window-name');
        state.presence = q('.krea-chat-window-presence');
        state.unread = q('.krea-chat-window-unread');
        state.minimize = q('.krea-chat-minimize');
        state.menuToggle = q('.krea-chat-menu-toggle');
        state.menu = q('.krea-chat-menu');
        state.clearButton = q('.krea-chat-clear');
        state.deleteButton = q('.krea-chat-delete');
        state.close = q('.krea-chat-window-close');
        state.bodyWrap = q('.krea-chat-window-body');
        state.error = q('.krea-chat-window-error');
        state.history = q('.krea-chat-history');
        state.older = q('.krea-chat-older');
        state.messages = q('.krea-chat-messages');
        state.empty = q('.krea-chat-empty');
        state.newButton = q('.krea-chat-new');
        state.tray = q('.krea-chat-attachment-tray');
        state.form = q('.krea-chat-composer');
        state.attach = q('.krea-chat-attach');
        state.files = q('.krea-chat-files');
        state.composer = q('.krea-chat-body');
        state.send = q('.krea-chat-send');
    }

    function updateWindowPeer(state, peer) {
        state.peer = { ...state.peer, ...peer, id: Number(peer.id || state.peer.id) };
        const fullName = state.peer.name || 'Conversación';
        state.name.textContent = compactDisplayName(fullName);
        state.name.title = fullName;
        state.identity.setAttribute('aria-label', `Conversación con ${fullName}. Clic para restaurar.`);
        presence(state.presence, state.peer.is_online);
        avatar(state.avatar, state.peer);
    }

    function updateWindowUnread(state, count) {
        const value = Math.max(0, Number(count || 0));
        state.unreadCount = value;
        state.unread.textContent = value > 99 ? '99+' : value;
        state.unread.hidden = value === 0;
    }

    function clearDraftAttachments(state) {
        state.draftAttachments.forEach(item => {
            if (item.url) URL.revokeObjectURL(item.url);
        });
        state.draftAttachments = [];
        state.files.value = '';
        renderDraftAttachments(state);
    }

    function renderDraftAttachments(state) {
        state.tray.replaceChildren();
        state.tray.hidden = !state.draftAttachments.length;

        state.draftAttachments.forEach((item, index) => {
            const card = document.createElement('div');
            card.className = 'krea-chat-preview-file';

            const preview = document.createElement(item.url ? 'img' : 'i');
            if (item.url) {
                preview.src = item.url;
                preview.alt = item.file.name;
            } else {
                preview.className = fileIcon(item.file.name);
            }

            const name = document.createElement('span');
            name.className = 'krea-chat-file-name';
            name.textContent = name.title = item.file.name;

            const size = document.createElement('small');
            size.textContent = fileSize(item.file.size);

            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'krea-chat-remove-file';
            remove.textContent = '×';
            remove.setAttribute('aria-label', `Quitar ${item.file.name}`);
            remove.disabled = state.sending;
            remove.addEventListener('click', () => {
                if (state.sending) return;
                if (item.url) URL.revokeObjectURL(item.url);
                state.draftAttachments.splice(index, 1);
                renderDraftAttachments(state);
            });

            card.append(preview, name, size, remove);
            state.tray.append(card);
        });
    }

    function openImage(attachment) {
        el('viewer-title').textContent = attachment.original_name;
        el('viewer-image').src = attachment.url;
        viewer.showModal();
    }

    function renderAttachments(attachments) {
        const group = document.createElement('div');
        group.className = 'krea-chat-message-files';

        for (const attachment of attachments || []) {
            if (attachment.is_image) {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'krea-chat-image';
                button.setAttribute('aria-label', `Ampliar ${attachment.original_name}`);

                const image = document.createElement('img');
                image.src = attachment.url;
                image.alt = attachment.original_name;
                image.loading = 'lazy';
                button.append(image);
                button.addEventListener('click', () => openImage(attachment));
                group.append(button);
            } else {
                const link = document.createElement('a');
                link.className = 'krea-chat-document';
                link.href = attachment.url;
                link.target = '_blank';
                link.rel = 'noopener noreferrer';

                const icon = document.createElement('i');
                icon.className = fileIcon(attachment.original_name);

                const copy = document.createElement('span');
                const name = document.createElement('strong');
                name.className = 'krea-chat-file-name';
                name.textContent = name.title = attachment.original_name;
                const info = document.createElement('small');
                info.textContent = `${fileSize(attachment.file_size)} · ${attachment.mime_type === 'application/pdf' ? 'Ver PDF' : 'Descargar'}`;
                copy.append(name, info);
                link.append(icon, copy);
                group.append(link);
            }
        }

        return group;
    }

    function nearBottom(state) {
        return state.history.scrollHeight - state.history.scrollTop - state.history.clientHeight < 65;
    }

    function scrollEnd(state) {
        state.history.scrollTop = state.history.scrollHeight;
        state.newButton.hidden = true;
    }

    function insertMessages(state, messages) {
        let added = 0;
        for (const message of messages) {
            if (state.messageNodes.has(Number(message.id))) continue;

            const node = document.createElement('div');
            node.className = `krea-chat-message${message.is_mine ? ' is-mine' : ''}`;

            if (message.body) {
                const body = document.createElement('p');
                body.textContent = message.body;
                node.append(body);
            }

            if (message.attachments?.length) {
                node.append(renderAttachments(message.attachments));
            }

            const time = document.createElement('time');
            time.dateTime = message.created_at;
            time.textContent = message.created_time;
            time.title = new Date(message.created_at).toLocaleString();
            node.append(time);

            const nextId = [...state.messageNodes.keys()]
                .filter(id => id > Number(message.id))
                .sort((a, b) => a - b)[0];
            state.messages.insertBefore(node, state.messageNodes.get(nextId) || null);
            state.messageNodes.set(Number(message.id), node);
            added++;
        }

        state.empty.hidden = state.messageNodes.size > 0;
        return added;
    }

    function resetWindowHistory(state) {
        state.version++;
        state.controller?.abort();
        state.initialized = false;
        state.afterId = 0;
        state.readThrough = 0;
        state.messageNodes.clear();
        state.messages.replaceChildren();
        state.empty.hidden = false;
        state.older.hidden = true;
        state.newButton.hidden = true;
        updateWindowUnread(state, 0);
        clearDraftAttachments(state);
    }

    async function markWindowRead(state) {
        if (state.minimized || document.hidden || blocked() || !state.afterId || state.afterId <= state.readThrough) return;
        try {
            await json(urlFor('readUrl', state.peer.id), {
                method: 'POST',
                body: { through_id: state.afterId },
            });
            state.readThrough = state.afterId;
            updateWindowUnread(state, 0);
            void refreshUnread();
        } catch { /* Se reintentará en el siguiente polling. */ }
    }

    async function loadOlder(state) {
        if (state.olderBusy || !state.messageNodes.size) return;
        state.olderBusy = true;
        state.older.disabled = true;
        const version = state.version;
        const url = new URL(urlFor('messagesUrl', state.peer.id));
        url.searchParams.set('before_id', Math.min(...state.messageNodes.keys()));

        try {
            const height = state.history.scrollHeight;
            const top = state.history.scrollTop;
            const data = await json(url);
            if (version !== state.version || !windows.has(state.peer.id)) return;
            updateWindowPeer(state, data.peer || { is_online: data.is_online });
            insertMessages(state, data.messages);
            data.messages.forEach(message => processedAudioIds.add(message.id));
            state.older.hidden = !data.has_more;
            state.history.scrollTop = top + state.history.scrollHeight - height;
        } catch (exception) {
            if (version === state.version) windowError(state, exception.message);
        } finally {
            state.olderBusy = false;
            state.older.disabled = false;
        }
    }

    async function pollWindow(state, initial = false) {
        if (state.pollBusy || document.hidden || stopped || blocked() || !windows.has(state.peer.id)) return;
        state.pollBusy = true;
        const version = state.version;
        state.controller = new AbortController();

        try {
            const url = new URL(urlFor('messagesUrl', state.peer.id));
            if (!initial && state.initialized) url.searchParams.set('after_id', state.afterId);
            const data = await json(url, { signal: state.controller.signal });
            if (version !== state.version || !windows.has(state.peer.id)) return;

            updateWindowPeer(state, data.peer || { is_online: data.is_online });
            const received = data.messages.filter(message => !message.is_mine);
            if (!state.initialized || initial) {
                data.messages.forEach(message => processedAudioIds.add(message.id));
            } else {
                messageSound(received);
            }

            const follow = !state.initialized || (!state.minimized && nearBottom(state));
            const added = insertMessages(state, data.messages);
            if (data.messages.length) state.afterId = Number(data.messages[data.messages.length - 1].id);
            if (!state.initialized || initial) state.older.hidden = !data.has_more;
            state.initialized = true;
            state.send.disabled = state.sending;
            updateWindowUnread(state, data.unread_count);

            if (follow && !state.minimized) scrollEnd(state);
            else if (added && !state.minimized) state.newButton.hidden = false;

            if (!state.minimized) await markWindowRead(state);
            windowError(state, '');
        } catch (exception) {
            if (version === state.version && !state.controller?.signal.aborted) {
                windowError(state, exception.name === 'AbortError'
                    ? 'El chat tarda en responder. Volveremos a intentarlo.'
                    : exception.message);
            }
        } finally {
            state.pollBusy = false;
        }
    }

    function minimizeWindow(state, minimized = true) {
        state.minimized = Boolean(minimized);
        state.root.classList.toggle('is-minimized', state.minimized);
        state.minimize.setAttribute('aria-label', state.minimized ? 'Restaurar conversación' : 'Minimizar conversación');
        state.minimize.title = state.minimized ? 'Restaurar' : 'Minimizar';
        state.minimize.innerHTML = state.minimized ? '<i class="fas fa-window-restore"></i>' : '<i class="fas fa-minus"></i>';
        state.menu.hidden = true;
        touchWindow(state);
        updateMobileState();

        if (!state.minimized) {
            state.composer.focus();
            scrollEnd(state);
            void markWindowRead(state);
        }
    }

    function closeWindow(state, focusLauncher = true) {
        if (!state || !windows.has(state.peer.id)) return;
        state.version++;
        state.controller?.abort();
        clearDraftAttachments(state);
        state.root.remove();
        windows.delete(state.peer.id);
        saveWindowState();
        updateMobileState();
        if (focusLauncher && windows.size === 0) el('launcher').focus();
    }

    async function confirmAction({ title, text, confirmText, danger = false }) {
        if (window.Swal?.fire) {
            const result = await window.Swal.fire({
                icon: 'warning',
                title,
                text,
                showCancelButton: true,
                confirmButtonText: confirmText,
                cancelButtonText: 'Cancelar',
                confirmButtonColor: danger ? '#b84a4a' : '#6f9e20',
                reverseButtons: true,
            });
            return result.isConfirmed;
        }
        return window.confirm(`${title}\n\n${text}`);
    }

    async function clearConversation(state) {
        const confirmed = await confirmAction({
            title: '¿Vaciar conversación?',
            text: 'Los mensajes anteriores dejarán de mostrarse para ti. Esta acción no afecta el historial del otro usuario.',
            confirmText: 'Vaciar chat',
        });
        if (!confirmed || !windows.has(state.peer.id)) return;

        state.clearButton.disabled = true;
        try {
            await json(urlFor('clearUrl', state.peer.id), { method: 'POST', body: {} });
            resetWindowHistory(state);
            await pollWindow(state, true);
            const cached = userCache.get(state.peer.id);
            if (cached) {
                cached.last_message = null;
                cached.last_message_at = null;
                cached.unread_count = 0;
                renderUsers();
            }
            void refreshDirectory(directoryOpen);
            void refreshUnread();
        } catch (exception) {
            windowError(state, exception.message);
        } finally {
            state.clearButton.disabled = false;
        }
    }

    async function deleteConversation(state) {
        const confirmed = await confirmAction({
            title: '¿Eliminar conversación?',
            text: 'La conversación desaparecerá de tu lista de recientes. El otro usuario conservará sus mensajes.',
            confirmText: 'Eliminar conversación',
            danger: true,
        });
        if (!confirmed || !windows.has(state.peer.id)) return;

        state.deleteButton.disabled = true;
        try {
            await json(urlFor('deleteUrl', state.peer.id), { method: 'DELETE' });
            const cached = userCache.get(state.peer.id);
            if (cached) {
                cached.last_message = null;
                cached.last_message_at = null;
                cached.unread_count = 0;
            }
            closeWindow(state, false);
            renderUsers();
            void refreshDirectory(directoryOpen);
            void refreshUnread();
        } catch (exception) {
            windowError(state, exception.message);
            state.deleteButton.disabled = false;
        }
    }

    function bindWindowEvents(state) {
        state.root.addEventListener('pointerdown', () => {
            unlockSound();
            touchWindow(state);
        });

        state.identity.addEventListener('click', () => {
            if (state.minimized) minimizeWindow(state, false);
        });
        state.minimize.addEventListener('click', event => {
            event.stopPropagation();
            minimizeWindow(state, !state.minimized);
        });
        state.close.addEventListener('click', () => closeWindow(state));
        state.menuToggle.addEventListener('click', event => {
            event.stopPropagation();
            state.menu.hidden = !state.menu.hidden;
        });
        state.menu.addEventListener('click', event => event.stopPropagation());
        state.clearButton.addEventListener('click', () => {
            state.menu.hidden = true;
            void clearConversation(state);
        });
        state.deleteButton.addEventListener('click', () => {
            state.menu.hidden = true;
            void deleteConversation(state);
        });

        state.older.addEventListener('click', () => void loadOlder(state));
        state.newButton.addEventListener('click', () => scrollEnd(state));
        state.history.addEventListener('scroll', () => {
            if (nearBottom(state)) state.newButton.hidden = true;
        });

        state.composer.addEventListener('keydown', event => {
            if (event.key === 'Enter' && !event.shiftKey && !event.isComposing) {
                event.preventDefault();
                state.form.requestSubmit();
            }
        });

        state.attach.addEventListener('click', () => state.files.click());
        state.files.addEventListener('change', () => {
            const files = Array.from(state.files.files || []);
            state.files.value = '';
            if (state.sending) return;
            if (state.draftAttachments.length + files.length > 5) {
                windowError(state, 'Puedes adjuntar como máximo 5 archivos por mensaje.');
                return;
            }
            if (files.some(file => file.size > 5 * 1024 * 1024 || !/\.(jpe?g|png|webp|pdf|docx?|xlsx?|txt)$/i.test(file.name))) {
                windowError(state, 'Selecciona imágenes o documentos permitidos de hasta 5 MB por archivo.');
                return;
            }

            windowError(state, '');
            files.forEach(file => state.draftAttachments.push({
                file,
                url: ['image/jpeg', 'image/png', 'image/webp'].includes(file.type) ? URL.createObjectURL(file) : null,
            }));
            renderDraftAttachments(state);
        });

        state.form.addEventListener('submit', async event => {
            event.preventDefault();
            const body = state.composer.value.trim();
            if ((!body && !state.draftAttachments.length) || !state.initialized || state.sending || stopped) return;

            unlockSound();
            state.sending = true;
            state.composer.disabled = state.send.disabled = state.attach.disabled = true;
            renderDraftAttachments(state);
            windowError(state, '');

            try {
                const formData = new FormData();
                formData.append('body', body);
                state.draftAttachments.forEach(item => formData.append('attachments[]', item.file));
                const message = await json(urlFor('messagesUrl', state.peer.id), {
                    method: 'POST',
                    body: formData,
                });
                messageSound([message], true);
                insertMessages(state, [message]);
                state.composer.value = '';
                clearDraftAttachments(state);
                scrollEnd(state);
                touchWindow(state);
                void pollWindow(state);
                void refreshUnread();
                if (directoryOpen) void refreshDirectory(true);
            } catch (exception) {
                windowError(state, exception.name === 'AbortError'
                    ? 'No se pudo confirmar el envío. Revisa el historial antes de intentarlo nuevamente.'
                    : exception.message);
            } finally {
                state.sending = false;
                state.composer.disabled = state.attach.disabled = false;
                state.send.disabled = !state.initialized || stopped;
                renderDraftAttachments(state);
                if (!state.minimized) state.composer.focus();
            }
        });
    }

    function openConversation(user, minimized = false, { persist = true } = {}) {
        const id = Number(user.id);
        if (!id || id === currentUserId) return null;

        if (windows.has(id)) {
            const state = windows.get(id);
            updateWindowPeer(state, user);
            if (!minimized) minimizeWindow(state, false);
            touchWindow(state);
            return state;
        }

        enforceWindowLimit(id);

        const root = createStaticWindowMarkup();
        windowsContainer.append(root);
        const state = {
            peer: { id, name: user.name || 'Cargando...', avatar: user.avatar || null, is_online: user.is_online },
            root,
            minimized: Boolean(minimized),
            unreadCount: Number(user.unread_count || 0),
            initialized: false,
            afterId: 0,
            readThrough: 0,
            messageNodes: new Map(),
            draftAttachments: [],
            pollBusy: false,
            olderBusy: false,
            sending: false,
            controller: null,
            version: 0,
            lastUsed: ++lastUsedSequence,
        };

        bindWindowElements(state);
        windows.set(id, state);
        updateWindowPeer(state, state.peer);
        updateWindowUnread(state, state.unreadCount);
        bindWindowEvents(state);
        state.root.classList.toggle('is-minimized', state.minimized);
        state.minimize.innerHTML = state.minimized ? '<i class="fas fa-window-restore"></i>' : '<i class="fas fa-minus"></i>';
        state.send.disabled = true;

        if (persist) saveWindowState();
        updateMobileState();
        if (!state.minimized) state.composer.focus();
        void pollWindow(state, true);
        return state;
    }

    async function restoreWindows() {
        const stored = readStoredWindows();
        for (const item of stored) {
            openConversation({ id: Number(item.user_id), name: 'Cargando...', avatar: null }, Boolean(item.minimized), { persist: false });
        }
        saveWindowState();
    }

    function scheduleWindowPolling() {
        clearTimeout(windowPollTimer);
        if (document.hidden || stopped) return;
        windowPollTimer = setTimeout(async () => {
            if (!blocked()) {
                await Promise.all([...windows.values()].map(state => pollWindow(state)));
            }
            scheduleWindowPolling();
        }, 3000);
    }

    function scheduleDirectoryPolling() {
        clearTimeout(directoryTimer);
        if (document.hidden || stopped) return;
        directoryTimer = setTimeout(async () => {
            if (directoryOpen && !blocked()) await refreshDirectory();
            scheduleDirectoryPolling();
        }, 10000);
    }

    function scheduleUnreadPolling() {
        clearTimeout(unreadTimer);
        if (document.hidden || stopped) return;
        unreadTimer = setTimeout(async () => {
            if (!blocked()) await refreshUnread();
            scheduleUnreadPolling();
        }, 10000);
    }

    function restartPolling() {
        clearTimeout(windowPollTimer);
        clearTimeout(directoryTimer);
        clearTimeout(unreadTimer);
        scheduleWindowPolling();
        scheduleDirectoryPolling();
        scheduleUnreadPolling();
    }

    el('launcher').addEventListener('click', () => {
        unlockSound();
        setDirectoryOpen(!directoryOpen);
    });
    el('close').addEventListener('click', () => setDirectoryOpen(false));

    el('search').addEventListener('input', () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => {
            search = el('search').value.trim();
            usersPage = requestedPage = 1;
            userCache.clear();
            void refreshDirectory(true);
        }, 300);
    });

    el('more-users').addEventListener('click', () => {
        requestedPage = usersPage + 1;
        el('more-users').disabled = true;
        void refreshDirectory(true);
    });

    document.addEventListener('click', () => {
        windows.forEach(state => { state.menu.hidden = true; });
    });

    document.addEventListener('keydown', event => {
        if (event.key !== 'Escape') return;
        if (viewer.open) return;
        const menuOpen = [...windows.values()].find(state => !state.menu.hidden);
        if (menuOpen) {
            menuOpen.menu.hidden = true;
            return;
        }
        if (directoryOpen) setDirectoryOpen(false);
    });

    widget.addEventListener('pointerdown', unlockSound);
    widget.addEventListener('keydown', unlockSound);

    el('viewer-close').addEventListener('click', () => viewer.close());
    viewer.addEventListener('close', () => {
        el('viewer-image').removeAttribute('src');
        restartPolling();
    });

    document.addEventListener('visibilitychange', () => {
        clearTimeout(windowPollTimer);
        clearTimeout(directoryTimer);
        clearTimeout(unreadTimer);
        clearTimeout(heartbeatTimer);
        if (document.hidden) {
            heartbeatController?.abort();
            windows.forEach(state => state.controller?.abort());
        } else {
            void heartbeat();
            void refreshUnread();
            if (directoryOpen) void refreshDirectory(true);
            windows.forEach(state => void pollWindow(state));
            restartPolling();
        }
    });

    new MutationObserver(() => {
        if (!blocked() && !document.hidden) {
            windows.forEach(state => void pollWindow(state));
            void refreshUnread();
        }
    }).observe(document.body, { attributes: true, attributeFilter: ['class'] });

    window.addEventListener('resize', () => {
        const limit = maxWindows();
        while (windows.size > limit) {
            const victim = [...windows.values()].sort((a, b) => a.lastUsed - b.lastUsed)[0];
            if (!victim) break;
            closeWindow(victim, false);
        }
        updateMobileState();
        position();
    });

    // Reserva el footer visible y acompaña el teclado móvil.
    const footer = document.querySelector('.main-footer');
    let positionFrame;
    function position() {
        cancelAnimationFrame(positionFrame);
        positionFrame = requestAnimationFrame(() => {
            const viewport = window.visualViewport;
            const height = viewport?.height || window.innerHeight;
            const bottom = height + (viewport?.offsetTop || 0);
            const rect = footer?.getBoundingClientRect();
            const footerSpace = rect && rect.top < bottom && rect.bottom > 0
                ? Math.min(rect.height, bottom - rect.top)
                : 0;
            widget.style.setProperty('--chat-footer-space', `${16 + footerSpace + Math.max(0, window.innerHeight - bottom)}px`);
            widget.style.setProperty('--chat-panel-limit', `${Math.max(150, height - footerSpace - 104)}px`);
        });
    }

    window.addEventListener('scroll', position, { passive: true });
    window.visualViewport?.addEventListener('resize', position);
    window.visualViewport?.addEventListener('scroll', position);
    if (footer && typeof ResizeObserver !== 'undefined') new ResizeObserver(position).observe(footer);

    position();
    void restoreWindows();
    void heartbeat();
    void refreshUnread();
    restartPolling();
}
