<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        
        <style>
            body {
                background: #000000;
                min-height: 100vh;
                position: relative;
                overflow-y: auto; overflow-x: hidden; -webkit-overflow-scrolling: touch;
            }
            
            /* Fondo con gradiente */
            body::before {
                content: '';
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: linear-gradient(135deg, #000000 0%, #1a1a1a 50%, #2a1a00 100%);
                z-index: 0;
            }
            
            /* Circuitos neón animados */
            .circuit-bg {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                z-index: 1;
                pointer-events: none;
            }
            
            .circuit-line {
                position: absolute;
                background: linear-gradient(90deg, transparent, #FFC800, transparent);
                box-shadow: 0 0 10px #FFC800, 0 0 20px #FF9C00;
                animation: pulse 3s ease-in-out infinite;
            }
            
            .circuit-line.vertical {
                width: 2px;
                height: 40%;
            }
            
            .circuit-line.horizontal {
                height: 2px;
                width: 30%;
            }
            
            .circuit-dot {
                position: absolute;
                width: 8px;
                height: 8px;
                background: #FFC800;
                border-radius: 50%;
                box-shadow: 0 0 15px #FFC800, 0 0 30px #FF9C00;
                animation: glow 2s ease-in-out infinite;
            }
            
            /* Líneas laterales izquierda */
            .circuit-line.left-1 { left: 10%; top: 15%; height: 35%; }
            .circuit-line.left-2 { left: 15%; top: 45%; width: 20%; }
            .circuit-line.left-3 { left: 8%; top: 65%; height: 25%; }
            
            /* Líneas laterales derecha */
            .circuit-line.right-1 { right: 10%; top: 20%; height: 30%; }
            .circuit-line.right-2 { right: 15%; top: 55%; width: 18%; }
            .circuit-line.right-3 { right: 12%; top: 75%; height: 20%; }
            
            /* Puntos de conexión */
            .circuit-dot.dot-1 { left: 10%; top: 15%; animation-delay: 0s; }
            .circuit-dot.dot-2 { left: 15%; top: 45%; animation-delay: 0.5s; }
            .circuit-dot.dot-3 { right: 10%; top: 20%; animation-delay: 1s; }
            .circuit-dot.dot-4 { right: 15%; top: 55%; animation-delay: 1.5s; }
            
            @keyframes pulse {
                0%, 100% { opacity: 0.3; }
                50% { opacity: 0.8; }
            }
            
            @keyframes glow {
                0%, 100% { 
                    box-shadow: 0 0 10px #FFC800, 0 0 20px #FF9C00;
                    transform: scale(1);
                }
                50% { 
                    box-shadow: 0 0 20px #FFC800, 0 0 40px #FF9C00, 0 0 60px #FFA000;
                    transform: scale(1.3);
                }
            }
            
            .content-wrapper {
                position: relative;
                z-index: 10;
            }
            
            .logo-container {
                background: #000000;
                padding: 1rem 2rem;
                border-radius: 1.5rem;
                margin-bottom: 1rem;
                border: 3px solid rgba(248, 184, 0, 0.08);
                box-shadow: 
                    0 0 60px 20px rgba(248, 184, 0, 0.08),
                    0 0 100px 40px rgba(248, 184, 0, 0.04),
                    0 25px 50px -12px rgba(0, 0, 0, 0.5);
            }
            .logo-container img {
                height: 180px;
                width: auto;
                display: block;
            }
            .login-card {
                background: white;
                border-radius: 1rem;
                box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
                padding: 2rem 2.5rem;
                max-width: 480px;
                width: 100%;
            }
        </style>
        <x-meta-pixel />
    </head>
    <body class="font-sans antialiased">
        <!-- Circuitos de fondo -->
        <div class="circuit-bg">
            <!-- Líneas izquierda -->
            <div class="circuit-line vertical left-1"></div>
            <div class="circuit-line horizontal left-2"></div>
            <div class="circuit-line vertical left-3"></div>
            
            <!-- Líneas derecha -->
            <div class="circuit-line vertical right-1"></div>
            <div class="circuit-line horizontal right-2"></div>
            <div class="circuit-line vertical right-3"></div>
            
            <!-- Puntos de conexión -->
            <div class="circuit-dot dot-1"></div>
            <div class="circuit-dot dot-2"></div>
            <div class="circuit-dot dot-3"></div>
            <div class="circuit-dot dot-4"></div>
        </div>
        
        <div class="content-wrapper" style="min-height: 100vh; display: flex; flex-direction: column; justify-content: center; align-items: center; padding: 1.5rem;">
            <!-- Logo Container -->
            <div class="logo-container">
                <img src="{{ asset('images/logo.jpeg') }}" alt="Logo">
            </div>

            <!-- Login Card -->
            <div class="login-card">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
