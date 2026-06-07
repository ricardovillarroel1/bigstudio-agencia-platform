<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Onboarding · {{ $proyecto->cliente->nombre ?? 'BigStudio' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", system-ui, sans-serif; }
        .bs-display { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", system-ui, sans-serif; font-weight: 900; letter-spacing: -0.02em; }
        .bs-grad { background: linear-gradient(135deg, #FFC800 0%, #FF9C00 50%, #FF8100 100%); }
    </style>
</head>
<body class="min-h-screen bg-gray-50">

    <header class="bs-grad text-white">
        <div class="max-w-3xl mx-auto px-4 py-10 sm:py-14">
            <div class="text-xs uppercase tracking-widest opacity-90 mb-2">BigStudio · Onboarding</div>
            <h1 class="bs-display text-3xl sm:text-5xl leading-tight">{{ $proyecto->titulo }}</h1>
            <p class="mt-3 text-white/90 text-base sm:text-lg">Bienvenido, <strong>{{ $proyecto->cliente->nombre ?? 'estimado cliente' }}</strong>.</p>
        </div>
    </header>

    <main class="max-w-3xl mx-auto px-4 py-10 space-y-6">

        <div class="bg-white rounded-xl shadow-sm p-6 sm:p-8 border border-gray-100">
            <h2 class="text-xl font-bold text-gray-800 mb-3">Estamos arrancando tu proyecto</h2>
            <p class="text-gray-600 leading-relaxed">
                Esta es la plataforma donde nos vas a entregar todo lo que necesitamos para diseñar tu tienda en Shopify:
                identidad visual, contenido, catálogo de productos, configuración comercial y mucho más. Está pensada para que la completes a tu ritmo —
                <strong>tus respuestas se guardan automáticamente</strong>, así que puedes volver cuando quieras.
            </p>
        </div>

        <div class="bg-orange-50 border border-orange-200 rounded-xl p-6 sm:p-8">
            <div class="flex items-start gap-4">
                <div class="bs-grad text-white w-12 h-12 rounded-full flex items-center justify-center text-2xl flex-shrink-0">⏱</div>
                <div>
                    <h3 class="font-bold text-gray-800 mb-1">Plazo de entrega: 15 a 20 días hábiles</h3>
                    <p class="text-gray-700 text-sm">
                        El reloj empieza a correr cuando tengamos el 100% del material que te vamos a pedir. Mientras más completo
                        y rápido lo entregues, más rápido lanzamos tu tienda.
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6 sm:p-8 border border-gray-100">
            <h3 class="font-bold text-gray-800 mb-4">¿Cómo va a funcionar?</h3>
            <ol class="space-y-3 text-gray-700">
                <li class="flex gap-3"><span class="bs-grad text-white w-7 h-7 rounded-full flex items-center justify-center text-sm font-bold flex-shrink-0">1</span><span>Te guiamos por <strong>secciones</strong>: identidad visual, contenido, productos, configuración, etc.</span></li>
                <li class="flex gap-3"><span class="bs-grad text-white w-7 h-7 rounded-full flex items-center justify-center text-sm font-bold flex-shrink-0">2</span><span>En cada sección completas datos y subes archivos (logos, fotos, planillas). <strong>Todo queda guardado al instante.</strong></span></li>
                <li class="flex gap-3"><span class="bs-grad text-white w-7 h-7 rounded-full flex items-center justify-center text-sm font-bold flex-shrink-0">3</span><span>Cuando termines, marcas <strong>"Material listo"</strong> y nos llega el aviso para arrancar.</span></li>
            </ol>
        </div>

        <div class="text-center pt-4">
            <button type="button"
                    class="bs-grad text-white font-bold px-10 py-4 rounded-xl text-lg shadow-lg hover:shadow-xl transition cursor-not-allowed opacity-90"
                    disabled>
                Empezar onboarding →
            </button>
            <p class="text-xs text-gray-500 mt-3">El wizard interactivo se habilita en el Sprint 2. Por ahora esto es la página de bienvenida.</p>
        </div>

    </main>

    <footer class="text-center text-xs text-gray-400 py-8">
        <p>BigStudio · <a href="https://www.bigstudio.cl" class="text-orange-600 hover:underline">www.bigstudio.cl</a></p>
        <p class="mt-1">¿Dudas? Escríbenos a <a href="mailto:hola@bigstudio.cl" class="text-orange-600 hover:underline">hola@bigstudio.cl</a></p>
    </footer>

</body>
</html>
