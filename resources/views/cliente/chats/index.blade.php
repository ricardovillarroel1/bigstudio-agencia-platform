<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Mis Chats') }}
        </h2>
    </x-slot>

    <style>
        .chat-card { background: white; border-radius: 1rem; padding: 1.5rem; margin-bottom: 1rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1); transition: all 0.3s; cursor: pointer; }
        .chat-card:hover { box-shadow: 0 8px 16px rgba(0,0,0,0.15); transform: translateY(-2px); }
        .chat-badge { padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; }
        .badge-activo { background: #d1fae5; color: #065f46; }
        .badge-cerrado { background: #fee2e2; color: #991b1b; }
    </style>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div style="text-align: center; margin-bottom: 2rem;">
                <h1 style="font-size: 2rem; font-weight: 800; color: #111827; margin-bottom: 0.5rem;">
                    Mis Conversaciones
                </h1>
                <p style="color: #6b7280;">Consulta con nuestros asesores sobre los planes</p>
            </div>

            @if($chats->isEmpty())
                <div style="text-align: center; padding: 4rem 2rem; background: white; border-radius: 1rem;">
                    <i class="fas fa-comments" style="font-size: 4rem; color: #d1d5db; margin-bottom: 1rem;"></i>
                    <h3 style="font-size: 1.5rem; font-weight: 700; color: #374151; margin-bottom: 0.5rem;">
                        No tienes chats activos
                    </h3>
                    <p style="color: #6b7280; margin-bottom: 1.5rem;">
                        Inicia una conversación desde la sección de Planes
                    </p>
                    <a href="{{ route('cliente.planes') }}" style="display: inline-flex; align-items: center; padding: 0.75rem 1.5rem; background: linear-gradient(135deg, #FFC800 0%, #FF9C00 100%); color: #000; font-weight: 700; border-radius: 0.5rem; text-decoration: none;">
                        <i class="fas fa-clipboard-list"></i> &nbsp;Ver Planes
                    </a>
                </div>
            @else
                @foreach($chats as $chat)
                    <div class="chat-card" onclick="window.location.href='{{ route('chats.show', $chat) }}'">
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                            <div style="flex: 1;">
                                <h3 style="font-size: 1.125rem; font-weight: 700; color: #111827; margin-bottom: 0.5rem;">
                                    {{ $chat->contexto }}
                                </h3>
                                <div style="display: flex; align-items: center; gap: 1rem; font-size: 0.875rem; color: #6b7280;">
                                    <span><i class="fas fa-calendar"></i> {{ $chat->created_at->format('d/m/Y') }}</span>
                                    <span><i class="fas fa-comments"></i> {{ $chat->mensaje_count }}/22 mensajes</span>
                                </div>
                            </div>
                            <span class="chat-badge badge-{{ $chat->estado }}">
                                {{ $chat->estado === 'activo' ? 'Activo' : 'Cerrado' }}
                            </span>
                        </div>

                        @if($chat->ultimoMensaje)
                            <div style="padding: 0.75rem; background: #f9fafb; border-radius: 0.5rem; border-left: 4px solid #FFC800;">
                                <div style="font-size: 0.75rem; font-weight: 600; color: #6b7280; margin-bottom: 0.25rem;">
                                    {{ $chat->ultimoMensaje->user->name ?? 'Sistema' }}
                                </div>
                                <div style="color: #374151;">
                                    {{ Str::limit($chat->ultimoMensaje->mensaje, 100) }}
                                </div>
                                <div style="font-size: 0.75rem; color: #9ca3af; margin-top: 0.25rem;">
                                    {{ $chat->ultimoMensaje->created_at->diffForHumans() }}
                                </div>
                            </div>
                        @endif

                        @if($chat->mensajesNoLeidos()->where('user_id', '!=', auth()->id())->count() > 0)
                            <div style="margin-top: 0.75rem;">
                                <span style="padding: 0.25rem 0.75rem; background: #ef4444; color: white; border-radius: 9999px; font-size: 0.75rem; font-weight: 700;">
                                    {{ $chat->mensajesNoLeidos()->where('user_id', '!=', auth()->id())->count() }} nuevos
                                </span>
                            </div>
                        @endif
                    </div>
                @endforeach
            @endif
        </div>
    </div>
</x-app-layout>
