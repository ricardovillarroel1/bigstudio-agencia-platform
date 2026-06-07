<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\ChatMessage;
use App\Models\Plan;
use App\Models\User;
use App\Models\Suscripcion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ChatController extends Controller
{
    // Vista de chats para cliente
    public function index()
    {
        $chats = Chat::where('cliente_id', auth()->id())
            ->with(['plan', 'ultimoMensaje'])
            ->latest('ultimo_mensaje_at')
            ->get();
        
        return view('cliente.chats.index', compact('chats'));
    }

    // Vista de chats para admin
    public function adminIndex()
    {
        $chats = Chat::with(['cliente', 'plan', 'ultimoMensaje'])
            ->where('estado', 'activo')
            ->latest('ultimo_mensaje_at')
            ->get();
        
        // Data for enhanced admin chat view
        $allClients = User::where("role", "!=", "admin")
            ->select("id", "name", "email", "role")
            ->orderBy("name")
            ->get();
        
        $allChats = Chat::with(["cliente:id,name,email", "ultimoMensaje.user:id,name"])
            ->latest("ultimo_mensaje_at")
            ->get();
        
        $suscripciones = Suscripcion::where("estado", "activa")
            ->pluck("user_id");

        return view('admin.chats.index', compact('chats', 'allClients', 'allChats', 'suscripciones'));
    }

    // Crear nuevo chat
    public function store(Request $request)
    {
        $validated = $request->validate([
            'plan_id' => 'required|exists:planes,id',
        ]);

        $plan = Plan::with('empresa')->find($validated['plan_id']);
        $contexto = "Plan: {$plan->nombre} - \${$plan->precio} ({$plan->empresa->nombre})";

        $chat = Chat::create([
            'cliente_id' => auth()->id(),
            'plan_id' => $validated['plan_id'],
            'contexto' => $contexto,
            'estado' => 'activo',
        ]);

        return response()->json([
            'success' => true,
            'chat_id' => $chat->id,
        ]);
    }

    // Ver chat específico
    public function show(Chat $chat)
    {
        // Verificar permisos
        if (!auth()->user()->hasRole('admin') && $chat->cliente_id !== auth()->id()) {
            abort(403);
        }

        $chat->load(['mensajes.user', 'plan.empresa', 'cliente']);
        
        // Marcar mensajes como leídos
        if (auth()->user()->hasRole('admin')) {
            $chat->mensajes()->where('user_id', '!=', auth()->id())->where('leido', false)->update(['leido' => true]);
        } else {
            // Para el cliente: marcar como leídos los mensajes del admin y del sistema (user_id NULL)
            $chat->mensajes()->where(function($q) {
                $q->where('user_id', '!=', auth()->id())
                  ->orWhereNull('user_id');
            })->where('leido', false)->update(['leido' => true]);
        }

        return view('chats.show', compact('chat'));
    }

    // Enviar mensaje
    public function sendMessage(Request $request, Chat $chat)
    {
        // Verificar permisos
        if (!auth()->user()->hasRole('admin') && $chat->cliente_id !== auth()->id()) {
            abort(403);
        }

        // Verificar límite de mensajes
        if ($chat->mensaje_count >= 22) {
            return response()->json([
                'success' => false,
                'message' => 'Límite de mensajes alcanzado',
            ], 400);
        }

        $validated = $request->validate([
            'mensaje' => 'required|string|max:1000',
            'archivo' => 'nullable|file|max:10240|mimes:jpg,jpeg,png,pdf,doc,docx',
        ]);

        $archivoPath = null;
        $archivoNombre = null;

        if ($request->hasFile('archivo')) {
            $file = $request->file('archivo');
            $archivoNombre = $file->getClientOriginalName();
            $archivoPath = $file->store('chat_archivos', 'public');
        }

        $mensaje = ChatMessage::create([
            'chat_id' => $chat->id,
            'user_id' => auth()->id(),
            'mensaje' => $validated['mensaje'],
            'archivo_path' => $archivoPath,
            'archivo_nombre' => $archivoNombre,
        ]);

        $chat->increment('mensaje_count');
        $chat->update(['ultimo_mensaje_at' => now()]);

        return response()->json([
            'success' => true,
            'mensaje' => $mensaje->load('user'),
        ]);
    }

    // Obtener nuevos mensajes (polling)
    public function getNewMessages(Chat $chat, Request $request)
    {
        $lastMessageId = $request->get('last_message_id', 0);
        
        $mensajes = $chat->mensajes()
            ->where('id', '>', $lastMessageId)
            ->with('user')
            ->get();

        return response()->json([
            'mensajes' => $mensajes,
            'mensaje_count' => $chat->mensaje_count,
        ]);
    }

    // Cerrar chat
    public function close(Chat $chat)
    {
        // Verificar permisos
        if (!auth()->user()->hasRole('admin') && $chat->cliente_id !== auth()->id()) {
            abort(403);
        }

        $cerradoPor = auth()->user()->hasRole('admin') ? 'admin' : 'cliente';

        $chat->update([
            'estado' => 'cerrado',
            'cerrado_at' => now(),
            'cerrado_por' => $cerradoPor,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Chat cerrado exitosamente',
        ]);
    }

    // Obtener contador de mensajes sin leer (para admin)
    public function getUnreadCount()
    {
        $count = ChatMessage::whereHas('chat', function($query) {
            $query->where('estado', 'activo');
        })
        ->where('user_id', '!=', auth()->id())
        ->where('leido', false)
        ->count();

        return response()->json(['count' => $count]);
    }
    // Admin: crear chat para un cliente específico
    public function createForClient(Request $request)
    {
        $request->validate([
            "cliente_id" => "required|exists:users,id",
        ]);

        $clienteId = $request->cliente_id;
        $cliente = \App\Models\User::find($clienteId);

        $chat = Chat::create([
            "cliente_id" => $clienteId,
            "contexto" => "Conversación iniciada por administrador",
            "estado" => "activo",
            "mensaje_count" => 0,
            "ultimo_mensaje_at" => now(),
        ]);

        $chat->load("cliente:id,name,email");

        return response()->json([
            "success" => true,
            "chat" => $chat,
        ]);
    }

    // Marcar mensajes como leídos (AJAX - para admin y cliente)
    public function markAsRead(Chat $chat)
    {
        if (!auth()->user()->hasRole('admin') && $chat->cliente_id !== auth()->id()) {
            abort(403);
        }

        if (auth()->user()->hasRole('admin')) {
            // Admin: marcar como leídos los mensajes del cliente y del sistema
            $chat->mensajes()->where(function($q) {
                $q->where('user_id', '!=', auth()->id())
                  ->orWhereNull('user_id');
            })->where('leido', false)->update(['leido' => true]);
        } else {
            // Cliente: marcar como leídos los mensajes del admin y del sistema
            $chat->mensajes()->where(function($q) {
                $q->where('user_id', '!=', auth()->id())
                  ->orWhereNull('user_id');
            })->where('leido', false)->update(['leido' => true]);
        }

        return response()->json(['success' => true]);
    }

    // Obtener contador de mensajes sin leer (para cliente)
    public function getUnreadCountCliente()
    {
        $count = ChatMessage::whereHas('chat', function($query) {
            $query->where('cliente_id', auth()->id())
                  ->where('estado', 'activo');
        })
        ->where(function($q) {
            $q->where('user_id', '!=', auth()->id())
              ->orWhereNull('user_id');
        })
        ->where('leido', false)
        ->count();

        return response()->json(['count' => $count]);
    }
}
