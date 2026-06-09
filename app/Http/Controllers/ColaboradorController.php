<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ColaboradorController extends Controller
{
    public function index()
    {
        $colaboradores = User::role('colaborador')->get();

        // Get all module permissions grouped
        $permisosAgrupados = $this->getPermisosAgrupados();

        return view('config.colaboradores', compact('colaboradores', 'permisosAgrupados'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => ['required', 'min:8'],
            'permisos' => 'array',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'colaborador',
        ]);

        $user->assignRole('colaborador');

        if ($request->has('permisos')) {
            $user->syncPermissions($request->permisos);
        }

        return redirect()->route('config.colaboradores')->with('success', 'Colaborador creado exitosamente.');
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|min:8',
            'permisos' => 'array',
        ]);

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
        ]);

        if ($request->filled('password')) {
            $user->update(['password' => Hash::make($request->password)]);
        }

        $user->syncPermissions($request->permisos ?? []);

        return redirect()->route('config.colaboradores')->with('success', 'Colaborador actualizado exitosamente.');
    }

    public function toggleStatus($id)
    {
        $user = User::findOrFail($id);

        if ($user->hasRole('colaborador')) {
            // Toggle by removing/adding role
            if ($user->hasRole('colaborador')) {
                $user->removeRole('colaborador');
                return redirect()->route('config.colaboradores')->with('success', 'Colaborador desactivado.');
            }
        }

        $user->assignRole('colaborador');
        return redirect()->route('config.colaboradores')->with('success', 'Colaborador reactivado.');
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->syncPermissions([]);
        $user->removeRole('colaborador');
        $user->delete();

        return redirect()->route('config.colaboradores')->with('success', 'Colaborador eliminado.');
    }

    public function updatePermisos(Request $request, $id)
    {
        $user = User::findOrFail($id);
        
        if (!$user->hasRole("colaborador")) {
            return redirect()->route("config.colaboradores")->with("error", "El usuario no es un colaborador.");
        }
        
        $user->syncPermissions($request->permisos ?? []);
        
        return redirect()->route("config.colaboradores")->with("success", "Permisos actualizados exitosamente para " . $user->name . ".");
    }

        private function getPermisosAgrupados()
    {
        return [
            'Integraciones' => [
                'integraciones.dashboard' => 'Panel Principal',
                'integraciones.boletas' => 'Boletas Emitidas',
                'integraciones.facturas' => 'Facturas Emitidas',
                'integraciones.clientes' => 'Clientes',
                'integraciones.solicitudes' => 'Solicitudes',
                'integraciones.suscripciones' => 'Suscripciones',
                'integraciones.billing' => 'Facturación',
                'integraciones.configuracion' => 'Configuración',
                'integraciones.chats' => 'Chats',
                'integraciones.transferencias' => 'Transferencias',
                'integraciones.cobros-asignados' => 'Cobros Asignados',
                'integraciones.correos' => 'Correos Integraciones',
            ],
            'Agencia' => [
                'agencia.dashboard' => 'Dashboard Agencia',
                'agencia.clientes' => 'Clientes Agencia',
                'agencia.servicios' => 'Servicios',
                'agencia.suscripciones' => 'Suscripciones',
                'agencia.cobros' => 'Cobros',
                'agencia.cotizaciones' => 'Cotizaciones',
                'agencia.correos' => 'Correos',
            ],
            'Tareas' => [
                'agencia.tareas.mias' => 'Ver solo las tareas compartidas con él',
                'agencia.tareas' => 'Ver TODAS las tareas (panel completo)',
            ],
            'Finanzas' => [
                'finanzas.dashboard' => 'Dashboard Financiero',
                'finanzas.ingresos' => 'Ingresos',
                'finanzas.egresos' => 'Egresos / Facturas de Compra',
                'finanzas.iva' => 'Cálculo de IVA',
                'finanzas.banco' => 'Conciliación Bancaria',
                'finanzas.cuentas-cobrar' => 'Cuentas por Cobrar',
                'finanzas.cuentas-pagar' => 'Cuentas por Pagar',
                'finanzas.reportes' => 'Reportes',
                'finanzas.presupuesto' => 'Presupuesto',
            ],
            'Configuración' => [
                'config.usuarios' => 'Gestión de Usuarios',
            ],
        ];
    }
}
