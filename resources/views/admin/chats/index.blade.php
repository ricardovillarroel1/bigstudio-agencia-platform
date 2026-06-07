<x-app-layout>

    <style>
        /* Chat takes full available height minus header */
        .chat-layout { display: flex; height: calc(100vh - 65px); overflow: hidden; }
        .chat-sidebar { width: 280px; min-width: 280px; background: white; border-right: 1px solid #e5e7eb; display: flex; flex-direction: column; }
        .chat-sidebar-header { padding: 0.75rem; border-bottom: 1px solid #e5e7eb; }
        .chat-sidebar-header h3 { font-size: 0.9rem; font-weight: 700; color: #1f2937; margin-bottom: 0.5rem; }
        .chat-sidebar-tabs { display: flex; border-bottom: 1px solid #e5e7eb; }
        .chat-sidebar-tab { flex: 1; padding: 0.5rem; text-align: center; font-size: 0.7rem; font-weight: 600; cursor: pointer; border-bottom: 3px solid transparent; color: #6b7280; transition: all 0.2s; }
        .chat-sidebar-tab.active { color: #FFC800; border-bottom-color: #FFC800; background: #fffbeb; }
        .chat-sidebar-tab:hover { background: #f9fafb; }
        .chat-client-list { flex: 1; overflow-y: auto; }
        .chat-client-item { display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem 0.75rem; cursor: pointer; border-bottom: 1px solid #f3f4f6; transition: background 0.2s; }
        .chat-client-item:hover { background: #fffbeb; }
        .chat-client-item.active { background: #fef3c7; border-left: 4px solid #FFC800; }
        .chat-client-avatar { width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; color: white; font-size: 0.8rem; flex-shrink: 0; }
        .chat-client-info { flex: 1; min-width: 0; }
        .chat-client-name { font-weight: 600; font-size: 0.8rem; color: #1f2937; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .chat-client-email { font-size: 0.65rem; color: #6b7280; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .chat-client-status { font-size: 0.6rem; margin-top: 1px; }
        .chat-unread-badge { background: #ef4444; color: white; font-size: 0.6rem; font-weight: 700; padding: 0.1rem 0.4rem; border-radius: 9999px; }
        .chat-main-area { flex: 1; display: flex; flex-direction: column; background: #ECE5DD; }
        .chat-main-header { padding: 0.75rem 1rem; background: white; border-bottom: 1px solid #e5e7eb; display: flex; align-items: center; gap: 0.75rem; }
        /* Fondo tipo WhatsApp: patron sutil sobre beige */
        .chat-messages-area { flex: 1; overflow-y: auto; padding: 1.25rem 1.5rem; display: flex; flex-direction: column; gap: 0.15rem;
            background-color: #ECE5DD;
            background-image: radial-gradient(rgba(0,0,0,0.035) 1px, transparent 1px);
            background-size: 18px 18px; }
        .chat-input-section { padding: 0.75rem 1rem; background: white; border-top: 1px solid #e5e7eb; }

        /* Separador de fecha (Hoy / Ayer / fecha) */
        .chat-date-sep { align-self: center; background: rgba(255,255,255,0.92); color: #5b6b7a; font-size: 0.65rem; font-weight: 700;
            padding: 0.2rem 0.7rem; border-radius: 9999px; margin: 0.6rem 0 0.3rem; box-shadow: 0 1px 1px rgba(0,0,0,0.06); text-transform: uppercase; letter-spacing: 0.03em; }

        /* Fila de mensaje: avatar + burbuja */
        .chat-row { display: flex; align-items: flex-end; gap: 0.45rem; max-width: 78%; margin-top: 0.12rem; }
        .chat-row.me { align-self: flex-end; flex-direction: row-reverse; }
        .chat-row.other { align-self: flex-start; }
        .chat-row.system { align-self: center; max-width: 88%; }
        .chat-row.grouped { margin-top: 0.08rem; }
        .chat-avatar { width: 28px; height: 28px; border-radius: 50%; flex-shrink: 0; display: flex; align-items: center; justify-content: center;
            font-size: 0.7rem; font-weight: 800; color: #fff; box-shadow: 0 1px 2px rgba(0,0,0,0.15); }
        .chat-avatar.me { background: linear-gradient(135deg, #FF9C00, #FF8100); }
        .chat-avatar.other { background: linear-gradient(135deg, #64748b, #475569); }
        .chat-avatar.spacer { visibility: hidden; }

        .chat-msg { display: flex; flex-direction: column; }
        .chat-msg.system { align-self: center; }
        .chat-msg-bubble { padding: 0.45rem 0.7rem 0.35rem; border-radius: 0.85rem; font-size: 0.82rem; line-height: 1.45;
            position: relative; box-shadow: 0 1px 1px rgba(0,0,0,0.08); word-break: break-word; }
        .chat-row.me .chat-msg-bubble { background: linear-gradient(135deg, #FFE7A8 0%, #FFD66B 100%); color: #3a2e00; border-bottom-right-radius: 0.25rem; }
        .chat-row.other .chat-msg-bubble { background: #ffffff; color: #1f2937; border-bottom-left-radius: 0.25rem; }
        /* Cola de la burbuja (solo en el primer mensaje del grupo) */
        .chat-row:not(.grouped).me .chat-msg-bubble::after { content: ''; position: absolute; bottom: 0; right: -6px;
            width: 0; height: 0; border: 6px solid transparent; border-left-color: #FFD66B; border-bottom: none; }
        .chat-row:not(.grouped).other .chat-msg-bubble::after { content: ''; position: absolute; bottom: 0; left: -6px;
            width: 0; height: 0; border: 6px solid transparent; border-right-color: #ffffff; border-bottom: none; }
        .chat-msg.system .chat-msg-bubble { background: rgba(255,255,255,0.92); color: #5b6b7a; border: none; font-size: 0.72rem; text-align: center; box-shadow: 0 1px 1px rgba(0,0,0,0.06); }
        .chat-sender { font-weight: 700; font-size: 0.66rem; margin-bottom: 0.1rem; color: #FF8100; }
        .chat-row.other .chat-sender { color: #475569; }
        .chat-meta { display: flex; align-items: center; gap: 0.25rem; justify-content: flex-end; margin-top: 0.1rem; }
        .chat-msg-time { font-size: 0.6rem; color: #9aa3ad; }
        .chat-check { font-size: 0.62rem; color: #34a3f5; line-height: 1; }
        .chat-file-attach { display: flex; align-items: center; gap: 0.4rem; padding: 0.4rem 0.5rem; background: rgba(0,0,0,0.05); border-radius: 0.5rem; margin-top: 0.35rem; font-size: 0.72rem; }
        .chat-file-attach a { color: #2563eb; text-decoration: none; font-weight: 600; }
        .chat-file-attach a:hover { text-decoration: underline; }
        .chat-empty-state { display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; color: #9ca3af; }
        .chat-search-input { width: 100%; padding: 0.4rem 0.6rem; border: 1px solid #e5e7eb; border-radius: 0.4rem; font-size: 0.8rem; outline: none; }
        .chat-search-input:focus { border-color: #FFC800; box-shadow: 0 0 0 2px rgba(255, 193, 7, 0.2); }
        .chat-ticket-filters { display: flex; gap: 0.3rem; padding: 0.4rem 0.75rem; }
        .chat-ticket-filter { padding: 0.15rem 0.5rem; border-radius: 9999px; font-size: 0.65rem; font-weight: 600; cursor: pointer; border: 1px solid #e5e7eb; background: white; }
        .chat-ticket-filter.active { background: #FFC800; color: #000; border-color: #FFC800; }
        @media (max-width: 768px) {
            .chat-sidebar { width: 100%; position: absolute; z-index: 10; }
            .chat-main-area { width: 100%; }
            .chat-sidebar.hidden-mobile { display: none; }
        }
    </style>

    <div class="chat-layout">
        {{-- Sidebar: Lista de clientes --}}
        <div class="chat-sidebar" id="chatSidebar">
            <div class="chat-sidebar-header">
                <h3>Centro de Comunicaciones</h3>
                <input type="text" class="chat-search-input" id="searchClients" placeholder="Buscar cliente..." oninput="filterClients()">
            </div>

            <div class="chat-sidebar-tabs">
                <div class="chat-sidebar-tab active" onclick="switchTab('todos')" id="tab-todos">Todos</div>
                <div class="chat-sidebar-tab" onclick="switchTab('activos')" id="tab-activos">Con Plan</div>
                <div class="chat-sidebar-tab" onclick="switchTab('registrados')" id="tab-registrados">Registrados</div>
                <div class="chat-sidebar-tab" onclick="switchTab('tickets')" id="tab-tickets">Tickets</div>
            </div>

            <div class="chat-ticket-filters" id="ticketFilters" style="display: none;">
                <span class="chat-ticket-filter active" onclick="filterTickets('todos')" id="tf-todos">Todos</span>
                <span class="chat-ticket-filter" onclick="filterTickets('activo')" id="tf-activo">Abiertos</span>
                <span class="chat-ticket-filter" onclick="filterTickets('cerrado')" id="tf-cerrado">Cerrados</span>
            </div>

            <div class="chat-client-list" id="clientList">
                {{-- Se llena dinámicamente --}}
            </div>
        </div>

        {{-- Chat Area --}}
        <div class="chat-main-area" id="chatArea">
            <div class="chat-empty-state" id="emptyState">
                <svg style="width: 3rem; height: 3rem; margin-bottom: 0.75rem; color: #d1d5db;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                </svg>
                <h3 style="font-size: 1rem; font-weight: 600; color: #6b7280;">Selecciona un cliente</h3>
                <p style="color: #9ca3af; font-size: 0.8rem;">Elige un cliente de la lista para iniciar o continuar una conversación</p>
            </div>

            <div id="activeChatView" style="display: none; height: 100%; flex-direction: column;">
                <div class="chat-main-header" id="chatHeader">
                    <button onclick="goBack()" title="Volver a la lista" style="background: none; border: none; cursor: pointer; padding: 0.3rem; border-radius: 0.3rem; color: #6b7280;">
                        <svg style="width: 1.1rem; height: 1.1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                    </button>
                    <div class="chat-client-avatar" id="chatAvatar" style="background: #FFC800;">A</div>
                    <div>
                        <div style="font-weight: 700; font-size: 0.9rem;" id="chatClientName">Cliente</div>
                        <div style="font-size: 0.7rem; color: #6b7280;" id="chatClientEmail">email@example.com</div>
                    </div>
                    <div style="margin-left: auto; display: flex; gap: 0.4rem; align-items: center;">
                        <span id="chatStatus" style="padding: 0.15rem 0.5rem; border-radius: 9999px; font-size: 0.65rem; font-weight: 600;"></span>
                        <span id="chatMsgCount" style="font-size: 0.7rem; color: #6b7280;"></span>
                    </div>
                </div>

                <div class="chat-messages-area" id="chatMessages">
                    {{-- Mensajes se cargan dinámicamente --}}
                </div>

                <div class="chat-input-section" id="chatInputArea">
                    <form id="messageForm" enctype="multipart/form-data" style="display: flex; gap: 0.4rem; align-items: end;">
                        @csrf
                        <label for="fileInput" style="cursor: pointer; padding: 0.4rem; color: #6b7280; border-radius: 0.3rem;" title="Adjuntar archivo">
                            <svg style="width: 1.1rem; height: 1.1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path>
                            </svg>
                        </label>
                        <input type="file" id="fileInput" name="archivo" style="display: none;" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx" onchange="showFileName()">
                        <div style="flex: 1; position: relative;">
                            <input type="text" id="messageInput" name="mensaje" placeholder="Escribe un mensaje..." style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #e5e7eb; border-radius: 1.5rem; font-size: 0.8rem; outline: none;" autocomplete="off">
                            <div id="filePreview" style="display: none; position: absolute; bottom: 100%; left: 0; background: #fef3c7; padding: 0.15rem 0.5rem; border-radius: 0.3rem; font-size: 0.7rem; margin-bottom: 0.15rem;">
                                <span id="fileName"></span>
                                <button type="button" onclick="clearFile()" style="margin-left: 0.3rem; color: #ef4444; background: none; border: none; cursor: pointer; font-weight: bold;">X</button>
                            </div>
                        </div>
                        <button type="submit" style="background: linear-gradient(135deg, #FFC800 0%, #FF9C00 100%); color: #000; border: none; border-radius: 50%; width: 34px; height: 34px; cursor: pointer; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <svg style="width: 1rem; height: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                            </svg>
                        </button>
                    </form>
                    <div id="closeChatArea" style="margin-top: 0.3rem; text-align: center;">
                        <button onclick="cerrarChat()" style="color: #ef4444; background: none; border: none; cursor: pointer; font-size: 0.7rem; font-weight: 600;">
                            Cerrar este ticket
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Data from server
        const allClients = {!! json_encode($allClients) !!};
        const allChats = {!! json_encode($allChats) !!};
        const suscripciones = {!! json_encode($suscripciones) !!};
        const currentUserId = {{ auth()->id() }};
        const csrfToken = '{{ csrf_token() }}';

        let currentChatId = null;
        let currentClientId = null;
        let lastMessageId = 0;
        let pollingInterval = null;
        let currentTab = 'todos';
        let currentTicketFilter = 'todos';

        const avatarColors = ['#ef4444', '#f59e0b', '#10b981', '#3b82f6', '#8b5cf6', '#ec4899', '#14b8a6', '#f97316'];

        function getAvatarColor(id) {
            return avatarColors[id % avatarColors.length];
        }

        function switchTab(tab) {
            currentTab = tab;
            document.querySelectorAll('.chat-sidebar-tab').forEach(t => t.classList.remove('active'));
            document.getElementById('tab-' + tab).classList.add('active');
            document.getElementById('ticketFilters').style.display = tab === 'tickets' ? 'flex' : 'none';
            renderClientList();
        }

        function filterTickets(filter) {
            currentTicketFilter = filter;
            document.querySelectorAll('.chat-ticket-filter').forEach(f => f.classList.remove('active'));
            document.getElementById('tf-' + filter).classList.add('active');
            renderClientList();
        }

        function filterClients() {
            renderClientList();
        }

        function renderClientList() {
            const search = document.getElementById('searchClients').value.toLowerCase();
            const listEl = document.getElementById('clientList');
            listEl.innerHTML = '';

            if (currentTab === 'tickets') {
                let chats = allChats;
                if (currentTicketFilter !== 'todos') {
                    chats = chats.filter(c => c.estado === currentTicketFilter);
                }
                if (search) {
                    chats = chats.filter(c => 
                        (c.cliente?.name || '').toLowerCase().includes(search) || 
                        (c.cliente?.email || '').toLowerCase().includes(search) ||
                        (c.contexto || '').toLowerCase().includes(search)
                    );
                }

                if (chats.length === 0) {
                    listEl.innerHTML = '<div style="padding: 1.5rem; text-align: center; color: #9ca3af; font-size: 0.8rem;">No hay tickets</div>';
                    return;
                }

                chats.forEach(chat => {
                    const isActive = currentChatId === chat.id;
                    const unread = chat.mensajes_no_leidos_count || 0;
                    const div = document.createElement('div');
                    div.className = 'chat-client-item' + (isActive ? ' active' : '');
                    div.onclick = () => openChat(chat.id, chat.cliente_id);
                    div.innerHTML = `
                        <div class="chat-client-avatar" style="background: ${getAvatarColor(chat.cliente_id)};">
                            ${(chat.cliente?.name || '?')[0].toUpperCase()}
                        </div>
                        <div class="chat-client-info">
                            <div class="chat-client-name">${chat.cliente?.name || 'Desconocido'}</div>
                            <div class="chat-client-email">${chat.contexto || ''}</div>
                            <div class="chat-client-status">
                                <span style="color: ${chat.estado === 'activo' ? '#059669' : '#9ca3af'};">
                                    ${chat.estado === 'activo' ? '● Abierto' : '● Cerrado'}
                                </span>
                                · ${chat.mensaje_count || 0} msgs
                                ${chat.ultimo_mensaje ? ` · ${timeAgo(chat.ultimo_mensaje_at)}` : ''}
                            </div>
                        </div>
                        ${unread > 0 ? `<span class="chat-unread-badge">${unread}</span>` : ''}
                    `;
                    listEl.appendChild(div);
                });
                return;
            }

            // Show clients
            let clients = allClients;
            if (currentTab === 'activos') {
                clients = clients.filter(c => suscripciones.includes(c.id));
            } else if (currentTab === 'registrados') {
                clients = clients.filter(c => !suscripciones.includes(c.id));
            }

            if (search) {
                clients = clients.filter(c => 
                    c.name.toLowerCase().includes(search) || 
                    c.email.toLowerCase().includes(search)
                );
            }

            if (clients.length === 0) {
                listEl.innerHTML = '<div style="padding: 1.5rem; text-align: center; color: #9ca3af; font-size: 0.8rem;">No se encontraron clientes</div>';
                return;
            }

            clients.forEach(client => {
                const isActive = currentClientId === client.id && currentTab !== 'tickets';
                const hasPlan = suscripciones.includes(client.id);
                const clientChats = allChats.filter(c => c.cliente_id === client.id && c.estado === 'activo');
                const unreadTotal = clientChats.reduce((sum, c) => sum + (c.mensajes_no_leidos_count || 0), 0);

                const div = document.createElement('div');
                div.className = 'chat-client-item' + (isActive ? ' active' : '');
                div.onclick = () => selectClient(client.id, client.name, client.email);
                div.innerHTML = `
                    <div class="chat-client-avatar" style="background: ${getAvatarColor(client.id)};">
                        ${client.name[0].toUpperCase()}
                    </div>
                    <div class="chat-client-info">
                        <div class="chat-client-name">${client.name}</div>
                        <div class="chat-client-email">${client.email}</div>
                        <div class="chat-client-status">
                            <span style="color: ${hasPlan ? '#059669' : '#9ca3af'};">
                                ${hasPlan ? '● Plan activo' : '○ Sin plan'}
                            </span>
                            ${clientChats.length > 0 ? ` · ${clientChats.length} chat(s)` : ''}
                        </div>
                    </div>
                    ${unreadTotal > 0 ? `<span class="chat-unread-badge">${unreadTotal}</span>` : ''}
                `;
                listEl.appendChild(div);
            });
        }

        function selectClient(clientId, name, email) {
            currentClientId = clientId;
            const existingChat = allChats.find(c => c.cliente_id === clientId && c.estado === 'activo');
            if (existingChat) {
                openChat(existingChat.id, clientId);
            } else {
                showClientChat(clientId, name, email, null);
            }
            renderClientList();
        }

        function showClientChat(clientId, name, email, chatId) {
            document.getElementById('emptyState').style.display = 'none';
            document.getElementById('activeChatView').style.display = 'flex';
            document.getElementById('chatClientName').textContent = name;
            document.getElementById('chatClientEmail').textContent = email;
            document.getElementById('chatAvatar').textContent = name[0].toUpperCase();
            document.getElementById('chatAvatar').style.background = getAvatarColor(clientId);

            const messagesDiv = document.getElementById('chatMessages');
            if (!chatId) {
                messagesDiv.innerHTML = `
                    <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; gap: 0.75rem;">
                        <p style="color: #6b7280; font-size: 0.85rem;">No hay conversación activa con este cliente.</p>
                        <button onclick="startNewChat(${clientId})" style="background: linear-gradient(135deg, #FFC800 0%, #FF9C00 100%); color: #000; font-weight: 700; padding: 0.5rem 1.25rem; border-radius: 0.4rem; border: none; cursor: pointer; font-size: 0.85rem;">
                            Iniciar Conversación
                        </button>
                    </div>
                `;
                document.getElementById('chatInputArea').style.display = 'none';
                document.getElementById('chatStatus').textContent = '';
                document.getElementById('chatMsgCount').textContent = '';
                currentChatId = null;
            }
        }

        async function startNewChat(clientId) {
            try {
                const response = await fetch('/admin/chats/create-for-client', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json' },
                    body: JSON.stringify({ cliente_id: clientId })
                });
                const data = await response.json();
                if (data.success) {
                    allChats.unshift(data.chat);
                    openChat(data.chat.id, clientId);
                }
            } catch (error) {
                console.error('Error creating chat:', error);
            }
        }

        async function openChat(chatId, clientId) {
            currentChatId = chatId;
            currentClientId = clientId;
            lastMessageId = 0;
            if (pollingInterval) clearInterval(pollingInterval);

            const chat = allChats.find(c => c.id === chatId);
            if (!chat) return;
            const client = chat.cliente || allClients.find(c => c.id === clientId);
            if (!client) return;

            document.getElementById('emptyState').style.display = 'none';
            document.getElementById('activeChatView').style.display = 'flex';
            document.getElementById('chatClientName').textContent = client.name;
            document.getElementById('chatClientEmail').textContent = client.email;
            document.getElementById('chatAvatar').textContent = client.name[0].toUpperCase();
            document.getElementById('chatAvatar').style.background = getAvatarColor(clientId);
            document.getElementById('chatStatus').textContent = chat.estado === 'activo' ? 'Abierto' : 'Cerrado';
            document.getElementById('chatStatus').style.background = chat.estado === 'activo' ? '#dcfce7' : '#f3f4f6';
            document.getElementById('chatStatus').style.color = chat.estado === 'activo' ? '#166534' : '#6b7280';
            document.getElementById('chatMsgCount').textContent = `${chat.mensaje_count || 0}/22 msgs`;
            
            const isOpen = chat.estado === 'activo';
            document.getElementById('chatInputArea').style.display = isOpen ? 'block' : 'none';

            const messagesDiv = document.getElementById('chatMessages');
            messagesDiv.innerHTML = '<div style="text-align: center; padding: 1.5rem; color: #9ca3af; font-size: 0.8rem;">Cargando mensajes...</div>';

            try {
                const response = await fetch(`/chats/${chatId}/new-messages?last_message_id=0`);
                const data = await response.json();
                messagesDiv.innerHTML = ''; bsLastSenderKey = null; bsLastDateKey = null;

                if (data.mensajes && data.mensajes.length > 0) {
                    data.mensajes.forEach(msg => {
                        appendMessage(msg);
                        lastMessageId = Math.max(lastMessageId, msg.id);
                    });
                } else {
                    messagesDiv.innerHTML = '<div style="text-align: center; padding: 1.5rem; color: #9ca3af; font-size: 0.8rem;">No hay mensajes aún. Escribe el primer mensaje.</div>';
                }
                messagesDiv.scrollTop = messagesDiv.scrollHeight;

                await fetch(`/chats/${chatId}/mark-read`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json' }
                });
                if (chat) chat.mensajes_no_leidos_count = 0;
                updateNavBadge();

                if (isOpen) {
                    pollingInterval = setInterval(() => pollMessages(chatId), 10000);
                }
            } catch (error) {
                messagesDiv.innerHTML = '<div style="text-align: center; padding: 1.5rem; color: #ef4444; font-size: 0.8rem;">Error cargando mensajes</div>';
            }
            renderClientList();
        }

        let bsLastSenderKey = null;   // para agrupar consecutivos
        let bsLastDateKey = null;     // para separador de fecha

        function bsDateLabel(d) {
            const now = new Date();
            const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
            const that = new Date(d.getFullYear(), d.getMonth(), d.getDate());
            const diff = Math.round((today - that) / 86400000);
            if (diff === 0) return 'Hoy';
            if (diff === 1) return 'Ayer';
            return d.toLocaleDateString('es-CL', { day: '2-digit', month: 'long', year: 'numeric' });
        }
        function bsEscape(s) {
            return (s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
        }

        function appendMessage(msg) {
            const messagesDiv = document.getElementById('chatMessages');
            const isMe = msg.user_id === currentUserId;
            const isSystem = msg.user_id === null;
            const created = new Date(msg.created_at);

            // Separador de fecha
            const dateKey = created.toDateString();
            if (dateKey !== bsLastDateKey) {
                const sep = document.createElement('div');
                sep.className = 'chat-date-sep';
                sep.textContent = bsDateLabel(created);
                messagesDiv.appendChild(sep);
                bsLastDateKey = dateKey;
                bsLastSenderKey = null; // reinicia agrupacion al cambiar de dia
            }

            if (isSystem) {
                const row = document.createElement('div');
                row.className = 'chat-row system';
                row.innerHTML = `<div class="chat-msg system"><div class="chat-msg-bubble">${bsEscape(msg.mensaje)}</div></div>`;
                messagesDiv.appendChild(row);
                bsLastSenderKey = null;
                return;
            }

            const senderKey = (isMe ? 'me' : 'other') + ':' + (msg.user_id || '0');
            const grouped = senderKey === bsLastSenderKey;
            bsLastSenderKey = senderKey;

            const senderName = isMe ? 'Tú' : (msg.user?.name || 'Cliente');
            const initial = (senderName || '?').charAt(0).toUpperCase();
            const time = created.toLocaleTimeString('es-CL', { hour: '2-digit', minute: '2-digit' });

            let fileHtml = '';
            if (msg.archivo_nombre) {
                fileHtml = `<div class="chat-file-attach">
                    <svg style="width:0.85rem;height:0.85rem;flex-shrink:0;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>
                    <a href="/storage/${msg.archivo_path}" target="_blank">${bsEscape(msg.archivo_nombre)}</a>
                </div>`;
            }

            const checkHtml = isMe ? '<span class="chat-check">&#10003;&#10003;</span>' : '';
            const senderHtml = (!grouped && !isMe) ? `<div class="chat-sender">${bsEscape(senderName)}</div>` : '';
            const avatarHtml = grouped
                ? `<div class="chat-avatar spacer"></div>`
                : `<div class="chat-avatar ${isMe ? 'me' : 'other'}">${initial}</div>`;

            const row = document.createElement('div');
            row.className = `chat-row ${isMe ? 'me' : 'other'}${grouped ? ' grouped' : ''}`;
            row.innerHTML = `
                ${avatarHtml}
                <div class="chat-msg">
                    <div class="chat-msg-bubble">
                        ${senderHtml}
                        <div style="white-space: pre-wrap;">${bsEscape(msg.mensaje)}</div>
                        ${fileHtml}
                        <div class="chat-meta"><span class="chat-msg-time">${time}</span>${checkHtml}</div>
                    </div>
                </div>
            `;
            messagesDiv.appendChild(div = row);
        }

        async function pollMessages(chatId) {
            if (currentChatId !== chatId) return;
            try {
                const response = await fetch(`/chats/${chatId}/new-messages?last_message_id=${lastMessageId}`);
                const data = await response.json();
                if (data.mensajes && data.mensajes.length > 0) {
                    const messagesDiv = document.getElementById('chatMessages');
                    if (messagesDiv.querySelector('div[style*="text-align: center"]')) {
                        messagesDiv.innerHTML = '';
                    }
                    data.mensajes.forEach(msg => {
                        if (msg.user_id !== currentUserId) {
                            appendMessage(msg);
                        }
                        lastMessageId = Math.max(lastMessageId, msg.id);
                    });
                    messagesDiv.scrollTop = messagesDiv.scrollHeight;
                }
            } catch (error) {
                console.error('Polling error:', error);
            }
        }

        function goBack() {
            if (pollingInterval) clearInterval(pollingInterval);
            currentChatId = null;
            currentClientId = null;
            document.getElementById('emptyState').style.display = 'flex';
            document.getElementById('activeChatView').style.display = 'none';
            renderClientList();
        }

        function showFileName() {
            const input = document.getElementById('fileInput');
            if (input.files.length > 0) {
                document.getElementById('fileName').textContent = input.files[0].name;
                document.getElementById('filePreview').style.display = 'block';
            }
        }

        function clearFile() {
            document.getElementById('fileInput').value = '';
            document.getElementById('filePreview').style.display = 'none';
        }

        document.getElementById('messageForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            if (!currentChatId) return;
            const messageInput = document.getElementById('messageInput');
            const fileInput = document.getElementById('fileInput');
            const mensaje = messageInput.value.trim();
            if (!mensaje && fileInput.files.length === 0) return;

            const formData = new FormData();
            formData.append('mensaje', mensaje || '(Archivo adjunto)');
            if (fileInput.files.length > 0) {
                formData.append('archivo', fileInput.files[0]);
            }

            try {
                const response = await fetch(`/chats/${currentChatId}/messages`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken },
                    body: formData
                });
                const data = await response.json();
                if (data.success) {
                    const messagesDiv = document.getElementById('chatMessages');
                    if (messagesDiv.querySelector('div[style*="text-align: center"]')) {
                        messagesDiv.innerHTML = '';
                    }
                    appendMessage(data.mensaje);
                    lastMessageId = Math.max(lastMessageId, data.mensaje.id);
                    messagesDiv.scrollTop = messagesDiv.scrollHeight;
                    messageInput.value = '';
                    clearFile();
                    const chat = allChats.find(c => c.id === currentChatId);
                    if (chat) {
                        chat.mensaje_count = (chat.mensaje_count || 0) + 1;
                        document.getElementById('chatMsgCount').textContent = `${chat.mensaje_count}/22 msgs`;
                    }
                } else {
                    alert(data.message || 'Error al enviar mensaje');
                }
            } catch (error) {
                console.error('Error sending message:', error);
            }
        });

        async function cerrarChat() {
            if (!currentChatId) return;
            if (!confirm('¿Estás seguro de cerrar este ticket?')) return;
            try {
                await fetch(`/chats/${currentChatId}/close`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json' }
                });
                const chat = allChats.find(c => c.id === currentChatId);
                if (chat) chat.estado = 'cerrado';
                goBack();
            } catch (error) {
                console.error('Error closing chat:', error);
            }
        }

        function timeAgo(dateStr) {
            if (!dateStr) return '';
            const now = new Date();
            const date = new Date(dateStr);
            const diff = Math.floor((now - date) / 1000);
            if (diff < 60) return 'hace un momento';
            if (diff < 3600) return `hace ${Math.floor(diff/60)} min`;
            if (diff < 86400) return `hace ${Math.floor(diff/3600)}h`;
            return `hace ${Math.floor(diff/86400)}d`;
        }

        async function updateNavBadge() {
            try {
                const response = await fetch('/admin/chats/unread-count');
                const data = await response.json();
                const badge = document.getElementById('unreadBadge');
                if (badge) {
                    if (data.count > 0) {
                        badge.textContent = data.count;
                        badge.style.display = 'block';
                    } else {
                        badge.style.display = 'none';
                    }
                }
            } catch (e) {
                console.error('Error updating nav badge:', e);
            }
        }

        // Initialize
        renderClientList();
    </script>
</x-app-layout>
