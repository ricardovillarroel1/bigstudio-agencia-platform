<?php
namespace App\Http\Controllers;
use App\Models\User;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->get('search');
        $role = $request->get('role');
        
        $users = User::query()
            ->when($search, function($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
            })
            ->when($role, function($query) use ($role) {
                $query->role($role);
            })
            ->latest()
            ->paginate(10);
        
        return view('usuarios.index', compact('users'));
    }
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('usuarios.create');
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role' => 'required|in:admin,cliente',
            'razon_social' => 'nullable|string|max:255',
            'rut' => 'nullable|string|max:20',
            'giro' => 'nullable|string|max:255',
            'direccion' => 'nullable|string|max:500',
        ]);
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);
        // Asignar rol según selección del formulario
        $user->assignRole($request->role);

        // Guardar datos de facturación si se proporcionaron
        if ($request->filled('razon_social') || $request->filled('rut') || $request->filled('giro') || $request->filled('direccion')) {
            $cliente = Cliente::firstOrNew(['user_id' => $user->id]);
            $cliente->razon_social = $request->razon_social;
            $cliente->rut = $request->rut;
            $cliente->giro = $request->giro;
            $cliente->direccion = $request->direccion;
            $cliente->save();

            // Marcar datos de facturación como completos si todos están llenos
            if ($request->filled('razon_social') && $request->filled('rut') && $request->filled('giro') && $request->filled('direccion')) {
                $user->datos_facturacion_completos = true;
                $user->save();
            }
        }

        $roleName = $request->role === 'admin' ? 'Administrador' : 'Cliente';
        return redirect()->route('usuarios.index')
            ->with('success', $roleName . ' creado exitosamente.');
    }
    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $user = User::findOrFail($id);
        return view('usuarios.edit', compact('user'));
    }
    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($id)],
            'password' => 'nullable|string|min:8',
            'role' => 'nullable|in:admin,cliente',
            'razon_social' => 'nullable|string|max:255',
            'rut' => 'nullable|string|max:20',
            'giro' => 'nullable|string|max:255',
            'direccion' => 'nullable|string|max:500',
        ]);
        $user = User::findOrFail($id);
        
        $user->name = $request->name;
        $user->email = $request->email;
        
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }
        
        // Actualizar rol si se proporcionó
        if ($request->filled('role') && $request->role !== $user->roles->first()?->name) {
            $user->syncRoles([$request->role]);
            $user->role = $request->role;
        }

        // Actualizar datos de facturación
        $cliente = Cliente::firstOrNew(['user_id' => $user->id]);
        $cliente->user_id = $user->id;
        $cliente->razon_social = $request->razon_social;
        $cliente->rut = $request->rut;
        $cliente->giro = $request->giro;
        $cliente->direccion = $request->direccion;
        $cliente->save();

        // Marcar datos de facturación como completos si todos están llenos
        $user->datos_facturacion_completos = $request->filled('razon_social') && $request->filled('rut') && $request->filled('giro') && $request->filled('direccion');
        
        $user->save();
        return redirect()->route('usuarios.index')
            ->with('success', 'Usuario actualizado exitosamente.');
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();
        return redirect()->route('usuarios.index')
            ->with('success', 'Usuario eliminado exitosamente.');
    }
}
