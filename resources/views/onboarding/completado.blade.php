<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>¡Material listo! · BigStudio</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", system-ui, sans-serif; }
        .bs-display { font-weight: 900; letter-spacing: -0.02em; }
        .bs-grad { background: linear-gradient(135deg, #FFC800 0%, #FF9C00 50%, #FF8100 100%); }
        @keyframes pop { 0% { transform: scale(0.5); opacity: 0; } 60% { transform: scale(1.1); } 100% { transform: scale(1); opacity: 1; } }
        .bs-pop { animation: pop 0.5s ease-out; }
    </style>
</head>
<body class="min-h-screen bg-gray-50 flex items-center justify-center px-4 py-10">
    <div class="max-w-xl w-full text-center">
        <div class="bs-pop bs-grad text-white w-24 h-24 rounded-full flex items-center justify-center text-5xl mx-auto mb-6 shadow-xl">🎉</div>

        <h1 class="bs-display text-3xl sm:text-4xl text-gray-800 mb-3">¡Material listo!</h1>

        <p class="text-gray-700 text-lg mb-2">
            Gracias <strong>{{ $proyecto->cliente->nombre ?? 'estimado cliente' }}</strong>.
        </p>

        <p class="text-gray-600 mb-8">
            Recibimos tu material y arrancamos el proyecto <strong>{{ $proyecto->titulo }}</strong>.
            Nuestro equipo te contactará en menos de <strong>24 horas hábiles</strong> para coordinar el kickoff.
        </p>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 text-left mb-8">
            <h2 class="font-bold text-gray-800 mb-3 text-center">¿Qué sigue?</h2>
            <ol class="space-y-3">
                <li class="flex gap-3">
                    <span class="bs-grad text-white w-7 h-7 rounded-full flex items-center justify-center text-sm font-bold flex-shrink-0">1</span>
                    <span class="text-gray-700">Revisamos tu material en detalle (24-48 horas).</span>
                </li>
                <li class="flex gap-3">
                    <span class="bs-grad text-white w-7 h-7 rounded-full flex items-center justify-center text-sm font-bold flex-shrink-0">2</span>
                    <span class="text-gray-700">Agendamos kickoff por videollamada para alinear expectativas.</span>
                </li>
                <li class="flex gap-3">
                    <span class="bs-grad text-white w-7 h-7 rounded-full flex items-center justify-center text-sm font-bold flex-shrink-0">3</span>
                    <span class="text-gray-700"><strong>Arranca el reloj:</strong> hasta {{ $proyecto->plantilla->dias_habiles_estimados ?? 20 }} días hábiles hasta tu tienda lista.</span>
                </li>
            </ol>
        </div>

        <a href="mailto:hola@bigstudio.cl"
           class="inline-block bg-white border-2 border-orange-500 text-orange-600 font-bold px-6 py-3 rounded-lg hover:bg-orange-50 transition">
            ¿Dudas? Escríbenos a hola@bigstudio.cl
        </a>

        <p class="text-xs text-gray-400 mt-6">Completado el {{ $proyecto->fecha_completado?->format('d/m/Y H:i') }}</p>
    </div>
</body>
</html>
