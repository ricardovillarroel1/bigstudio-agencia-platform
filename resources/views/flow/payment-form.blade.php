<x-app-layout>

    <div class="container mx-auto px-4 py-8">
        <div class="max-w-md mx-auto bg-white rounded-lg shadow-md p-6">
            <h2 class="text-2xl font-bold mb-6 text-center">Realizar Pago con Flow</h2>

            @if ($errors->any())
            <div class="alert alert-danger mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
                @endforeach
            </div>
            @endif

            <form action="{{ route('flow.create-payment') }}" method="POST" class="space-y-4">
                @csrf

                <div>
                    <label for="subject" class="block text-sm font-medium text-gray-700">Descripción del pago</label>
                    <input type="text" id="subject" name="subject" required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500"
                        placeholder="Ej: Pago de servicios">
                </div>

                <div>
                    <label for="amount" class="block text-sm font-medium text-gray-700">Monto (CLP)</label>
                    <input type="number" id="amount" name="amount" required min="350"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500"
                        placeholder="350">
                    <small class="text-gray-500">Monto mínimo: $350 CLP</small>
                </div>

                <button type="submit"
                    class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    Pagar con Flow
                </button>
            </form>

            @if(false)
            <div class="mt-4 p-4 bg-yellow-50 border border-yellow-200 rounded-md">
                <p class="text-sm text-yellow-800">
                    <strong>Modo de prueba:</strong> Este es un entorno de testing.
                    <br><strong>Tarjeta de prueba APROBADA:</strong> 4051885600446623
                    <br><strong>Vencimiento:</strong> 11/27 | <strong>CVV:</strong> 123
                    <br><strong>RUT:</strong> 11111111-1 | <strong>Clave:</strong> 123
                </p>
                <p class="text-xs text-yellow-700 mt-2">
                    <strong>Otras tarjetas de prueba:</strong><br>
                    • Rechazada: 4551708161768059<br>
                    • Perú aprobada: 5293138086430769
                </p>
            </div>
            @endif
        </div>
    </div>
</x-app-layout>
