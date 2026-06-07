<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Chat - {{ $chat->contexto }}
        </h2>
    </x-slot>

    <style>
        .chat-container { max-width: 900px; margin: 0 auto; height: calc(100vh - 200px); display: flex; flex-direction: column; }
        .chat-header { background: linear-gradient(135deg, #FFD54F 0%, #FFCA28 100%); padding: 1.5rem; border-radius: 1rem 1rem 0 0; }
        .chat-messages { flex: 1; overflow-y: auto; padding: 1.5rem; background: #f9fafb; }
        .message { margin-bottom: 1rem; display: flex; gap: 0.75rem; }
        .message.me { flex-direction: row-reverse; }
        .message-bubble { max-width: 70%; padding: 0.75rem 1rem; border-radius: 1rem; }
        .message.me .message-bubble { background: linear-gradient(135deg, #FFC800 0%, #FF9C00 100%); color: #000; }
        .message.other .message-bubble { background: white; color: #374151; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .chat-input { background: white; padding: 1.5rem; border-radius: 0 0 1rem 1rem; border-top: 2px solid #e5e7eb; }
        .file-preview { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1rem; background: #f3f4f6; border-radius: 0.5rem; margin-top: 0.5rem; }
    </style>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="chat-container bg-white shadow-lg rounded-xl">
                <!-- Header -->
                <div class="chat-header">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <h3 style="font-size: 1.25rem; font-weight: 700; color: #1a1a1a; margin-bottom: 0.25rem;">
                                {{ $chat->contexto }}
                            </h3>
                            <p style="font-size: 0.875rem; color: #1a1a1a; opacity: 0.8;">
                                Mensajes: <span id="messageCount">{{ $chat->mensaje_count }}</span>/22
                            </p>
                        </div>
                        <div style="display: flex; gap: 0.75rem;">
                            @if(auth()->user()->hasRole('cliente'))
                                <button onclick="solicitarPlanDesdeChat()" style="padding: 0.75rem 1.5rem; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; font-weight: 700; border: none; border-radius: 0.5rem; cursor: pointer; box-shadow: 0 4px 6px -1px rgba(16, 185, 129, 0.4); transition: all 0.3s;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 20px -4px rgba(16, 185, 129, 0.6)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 6px -1px rgba(16, 185, 129, 0.4)'">
                                    <i class="fas fa-paper-plane"></i> Solicitar Plan
                                </button>
                            @endif
                            <button onclick="cerrarChat()" style="padding: 0.5rem 1rem; background: rgba(0,0,0,0.1); color: #1a1a1a; font-weight: 600; border: none; border-radius: 0.5rem; cursor: pointer;">
                                <i class="fas fa-times"></i> Cerrar Chat
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Mensajes -->
                <div class="chat-messages" id="chatMessages">
                    @foreach($chat->mensajes as $mensaje)
                        <div class="message {{ $mensaje->user_id === auth()->id() ? 'me' : 'other' }}" data-message-id="{{ $mensaje->id }}">
                            <div class="message-bubble">
                                <div style="font-weight: 600; font-size: 0.75rem; margin-bottom: 0.25rem; opacity: 0.8;">
                                    {{ $mensaje->user->name ?? 'Sistema' }}
                                </div>
                                <div>{{ $mensaje->mensaje }}</div>
                                @if($mensaje->archivo_nombre)
                                    <div class="file-preview">
                                        <i class="fas fa-file"></i>
                                        <a href="{{ Storage::url($mensaje->archivo_path) }}" target="_blank" style="color: #3b82f6; text-decoration: none;">
                                            {{ $mensaje->archivo_nombre }}
                                        </a>
                                    </div>
                                @endif
                                <div style="font-size: 0.625rem; margin-top: 0.25rem; opacity: 0.6;">
                                    {{ $mensaje->created_at->format('H:i') }}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Input -->
                <div class="chat-input">
                    @if($chat->mensaje_count >= 22)
                        <div style="text-align: center; padding: 1rem; background: #fee2e2; border-radius: 0.5rem; color: #991b1b;">
                            <i class="fas fa-exclamation-triangle"></i> Límite de mensajes alcanzado. Continúe por WhatsApp o solicite el plan.
                        </div>
                    @else
                        <form id="chatForm" enctype="multipart/form-data">
                            <div style="display: flex; gap: 0.75rem;">
                                <input type="file" id="fileInput" style="display: none;" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx">
                                <button type="button" onclick="document.getElementById('fileInput').click()" style="padding: 0.75rem; background: #f3f4f6; border: none; border-radius: 0.5rem; cursor: pointer;">
                                    <i class="fas fa-paperclip"></i>
                                </button>
                                <input type="text" id="messageInput" placeholder="Escribe tu mensaje..." style="flex: 1; padding: 0.75rem; border: 2px solid #d1d5db; border-radius: 0.5rem;">
                                <button type="submit" style="padding: 0.75rem 1.5rem; background: linear-gradient(135deg, #FFC800 0%, #FF9C00 100%); color: #000; font-weight: 700; border: none; border-radius: 0.5rem; cursor: pointer;">
                                    <i class="fas fa-paper-plane"></i> Enviar
                                </button>
                            </div>
                            <div id="filePreview"></div>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script>
        let lastMessageId = {{ $chat->mensajes->last()->id ?? 0 }};
        let pollingInterval;
        let isActive = true;

        // Enviar mensaje
        const chatForm = document.getElementById('chatForm');
        if (chatForm) {
            chatForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                
                const messageInput = document.getElementById('messageInput');
                const fileInput = document.getElementById('fileInput');
                const mensaje = messageInput.value.trim();
                
                if (!mensaje && !fileInput.files[0]) {
                    alert('Por favor escribe un mensaje');
                    return;
                }

                const formData = new FormData();
                formData.append('mensaje', mensaje);
                if (fileInput.files[0]) {
                    formData.append('archivo', fileInput.files[0]);
                }

                try {
                    console.log('Enviando mensaje...', mensaje);
                    const response = await fetch('{{ route("chats.sendMessage", $chat) }}', {
                        method: 'POST',
                        headers: { 
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: formData
                    });
                    console.log('Respuesta recibida:', response.status);
                    
                    if (!response.ok) {
                        const errorData = await response.json();
                        console.error('Error del servidor:', errorData);
                        alert('Error al enviar mensaje: ' + (errorData.message || 'Error desconocido'));
                        return;
                    }
                    
                    const data = await response.json();
                    if (data.success) {
                        messageInput.value = '';
                        fileInput.value = '';
                        document.getElementById('filePreview').innerHTML = '';
                        agregarMensaje(data.mensaje, true);
                        document.getElementById('messageCount').textContent = parseInt(document.getElementById('messageCount').textContent) + 1;
                    } else {
                        alert('Error al enviar mensaje');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('Error de conexión al enviar mensaje');
                }
            });
        }

        // Polling
        function startPolling() {
            // Limpiar intervalo anterior si existe
            if (pollingInterval) {
                clearInterval(pollingInterval);
            }
            
            const interval = isActive ? 15000 : 90000; // 15 seg activo, 90 seg inactivo
            console.log('Polling iniciado:', isActive ? '20 segundos' : '60 segundos');
            pollingInterval = setInterval(checkNewMessages, interval);
        }

        async function checkNewMessages() {
            try {
                const response = await fetch(`{{ route("chats.getNewMessages", $chat) }}?last_message_id=${lastMessageId}`);
                const data = await response.json();
                
                if (data.mensajes && data.mensajes.length > 0) {
                    console.log('Nuevos mensajes recibidos:', data.mensajes.length);
                    data.mensajes.forEach(msg => {
                        if (msg.user_id !== {{ auth()->id() }}) {
                            agregarMensaje(msg, false);
                        }
                        lastMessageId = msg.id;
                    });
                }
                
                if (data.mensaje_count) {
                    document.getElementById('messageCount').textContent = data.mensaje_count;
                }
            } catch (error) {
                console.error('Error en polling:', error);
            }
        }

        function agregarMensaje(mensaje, isMe) {
            const messagesDiv = document.getElementById('chatMessages');
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${isMe ? 'me' : 'other'}`;
            messageDiv.setAttribute('data-message-id', mensaje.id);
            messageDiv.innerHTML = `
                <div class="message-bubble">
                    <div style="font-weight: 600; font-size: 0.75rem; margin-bottom: 0.25rem; opacity: 0.8;">
                        ${mensaje.user.name}
                    </div>
                    <div>${mensaje.mensaje}</div>
                    ${mensaje.archivo_nombre ? `<div class="file-preview"><i class="fas fa-file"></i><a href="/storage/${mensaje.archivo_path}" target="_blank">${mensaje.archivo_nombre}</a></div>` : ''}
                    <div style="font-size: 0.625rem; margin-top: 0.25rem; opacity: 0.6;">
                        ${new Date().toLocaleTimeString('es-ES', {hour: '2-digit', minute: '2-digit'})}
                    </div>
                </div>
            `;
            messagesDiv.appendChild(messageDiv);
            messagesDiv.scrollTop = messagesDiv.scrollHeight;
        }

        function solicitarPlanDesdeChat() {
            if (confirm('¿Deseas solicitar este plan? Se enviará tu solicitud al equipo.')) {
                // Enviar mensaje automático en el chat
                const formData = new FormData();
                formData.append('mensaje', '✅ He decidido solicitar este plan. Por favor, proceder con la contratación.');
                
                fetch('{{ route("chats.sendMessage", $chat) }}', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: formData
                }).then(response => response.json())
                .then(data => {
                    if (data.success) {
                        agregarMensaje(data.mensaje, true);
                        document.getElementById('messageCount').textContent = parseInt(document.getElementById('messageCount').textContent) + 1;
                        alert('¡Solicitud enviada! Nuestro equipo se pondrá en contacto contigo pronto.');
                    }
                }).catch(error => console.error('Error:', error));
            }
        }

        function cerrarChat() {
            if (confirm('¿Estás seguro de cerrar este chat?')) {
                clearInterval(pollingInterval);
                fetch('{{ route("chats.close", $chat) }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json'
                    }
                }).then(() => {
                    window.location.href = '{{ auth()->user()->hasRole("admin") ? route("admin.chats") : route("cliente.chats") }}';
                });
            }
        }

        // Detectar visibilidad de la ventana
        document.addEventListener('visibilitychange', () => {
            const wasActive = isActive;
            isActive = !document.hidden;
            
            if (wasActive !== isActive) {
                console.log('Visibilidad cambiada:', isActive ? 'Activo' : 'Inactivo');
                startPolling(); // Reiniciar con nuevo intervalo
            }
        });

        // Scroll al final al cargar
        document.getElementById('chatMessages').scrollTop = document.getElementById('chatMessages').scrollHeight;
        
        // Iniciar polling
        console.log('Chat iniciado');
        startPolling();
    </script>
</x-app-layout>
