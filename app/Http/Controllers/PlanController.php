<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Empresa;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search');
        
        $planes = Plan::with('empresa')
            ->when($search, function($query) use ($search) {
                $query->where('nombre', 'like', "%{$search}%")
                      ->orWhere('descripcion', 'like', "%{$search}%")
                      ->orWhereHas('empresa', function($q) use ($search) {
                          $q->where('nombre', 'like', "%{$search}%");
                      });
            })
            ->latest()
            ->paginate(10);
        
        return view('planes.index', compact('planes'));
    }

    public function create()
    {
        $empresas = Empresa::all();
        return view('planes.create', compact('empresas'));
    }

    public function store(Request $request)
    {
        $empresa = Empresa::find($request->empresa_id);
        $isLioren = $empresa && $empresa->slug === 'lioren';

        $rules = [
            'empresa_id' => ['required', 'exists:empresas,id'],
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['required', 'string'],
            'precio' => ['required', 'numeric', 'min:0'],
            'moneda' => ['required', 'in:CLP,UF'],
            'activo' => ['boolean'],
            'plan_anual_activo' => ['boolean'],
            'precio_anual' => ['nullable', 'numeric', 'min:0'],
            'descuento_anual' => ['nullable', 'integer', 'min:0', 'max:100'],
        ];

        if ($isLioren) {
            $rules['facturacion_enabled'] = ['boolean'];
            $rules['boletas_enabled'] = ['boolean'];
            $rules['shopify_visibility_enabled'] = ['boolean'];
            $rules['notas_credito_enabled'] = ['boolean'];
            $rules['sync_inventario_enabled'] = ['boolean'];
            $rules['order_limit_enabled'] = ['boolean'];
            $rules['monthly_order_limit'] = ['nullable', 'integer', 'min:1'];
        } else {
            $rules['caracteristicas'] = ['required', 'array'];
            $rules['caracteristicas.*'] = ['required', 'string'];
        }

        $validated = $request->validate($rules);

        // Convertir campos booleanos
        if ($isLioren) {
            $validated['facturacion_enabled'] = (bool) ($request->input('facturacion_enabled', 0));
            $validated['boletas_enabled'] = (bool) ($request->input('boletas_enabled', 0));
            $validated['shopify_visibility_enabled'] = (bool) ($request->input('shopify_visibility_enabled', 0));
            $validated['notas_credito_enabled'] = (bool) ($request->input('notas_credito_enabled', 0));
            $validated['sync_inventario_enabled'] = (bool) ($request->input('sync_inventario_enabled', 0));
            $validated['order_limit_enabled'] = (bool) ($request->input('order_limit_enabled', 0));
        }

        // Campos de plan anual
        $validated['plan_anual_activo'] = (bool) ($request->input('plan_anual_activo', 0));
        $validated['descuento_anual'] = $request->input('descuento_anual');
        $validated['precio_anual'] = $request->input('precio_anual');

        // Auto-calcular precio anual si hay descuento pero no precio anual manual
        if ($validated['plan_anual_activo'] && !empty($validated['descuento_anual']) && empty($validated['precio_anual'])) {
            $precioMensual = (float) $validated['precio'];
            $descuento = (int) $validated['descuento_anual'];
            $validated['precio_anual'] = round($precioMensual * 12 * (1 - $descuento / 100), 2);
        }

        // Construir características para Lioren
        if ($isLioren) {
            $caracteristicas = [];
            if ($request->facturacion_enabled) $caracteristicas[] = '✅ Emisión de facturas electrónicas';
            if ($request->boletas_enabled) $caracteristicas[] = '🧾 Emisión de boletas electrónicas';
            if ($request->shopify_visibility_enabled) $caracteristicas[] = '👁️ Visibilidad desde Shopify';
            if ($request->notas_credito_enabled) $caracteristicas[] = '🔄 Notas de Crédito Automáticas';
            if ($request->sync_inventario_enabled) $caracteristicas[] = '📦 Sincronización de Inventario';
            if ($request->documentos_postventa_enabled) $caracteristicas[] = '📝 Documentos Postventa';
            if ($request->order_limit_enabled && $request->monthly_order_limit) {
                $caracteristicas[] = "📊 Límite: {$request->monthly_order_limit} pedidos/mes";
            } elseif (!$request->order_limit_enabled) {
                $caracteristicas[] = '♾️ Sin límite de pedidos';
            }
            $validated['caracteristicas'] = $caracteristicas;
        }

        Plan::create($validated);

        return redirect()->route('planes.index')
            ->with('success', 'Plan creado exitosamente');
    }

    public function show(Plan $plan)
    {
        return redirect()->route('planes.index');
    }

    public function edit(Plan $plan)
    {
        $empresas = Empresa::all();
        return view('planes.edit', compact('plan', 'empresas'));
    }

    public function update(Request $request, $plane)
    {
        \Log::info('========== UPDATE PLAN START ==========');
        
        $plan = Plan::find($plane);
        
        if (!$plan) {
            \Log::error('Plan not found', ['id' => $plane]);
            return redirect()->route('planes.index')
                ->withErrors(['error' => 'Plan no encontrado']);
        }

        try {
            $empresa = Empresa::find($request->empresa_id);
            $isLioren = $empresa && $empresa->slug === 'lioren';

            $rules = [
                'empresa_id' => ['required', 'exists:empresas,id'],
                'nombre' => ['required', 'string', 'max:255'],
                'descripcion' => ['required', 'string'],
                'precio' => ['required', 'numeric', 'min:0'],
                'moneda' => ['required', 'in:CLP,UF'],
                'activo' => ['boolean'],
                'plan_anual_activo' => ['boolean'],
                'precio_anual' => ['nullable', 'numeric', 'min:0'],
                'descuento_anual' => ['nullable', 'integer', 'min:0', 'max:100'],
            ];

            if ($isLioren) {
                $rules['facturacion_enabled'] = ['boolean'];
                $rules['boletas_enabled'] = ['boolean'];
                $rules['shopify_visibility_enabled'] = ['boolean'];
                $rules['notas_credito_enabled'] = ['boolean'];
                $rules['sync_inventario_enabled'] = ['boolean'];
                $rules['documentos_postventa_enabled'] = ['boolean'];
                $rules['order_limit_enabled'] = ['boolean'];
                $rules['monthly_order_limit'] = ['nullable', 'integer', 'min:1'];
            } else {
                $rules['caracteristicas'] = ['required', 'array'];
                $rules['caracteristicas.*'] = ['required', 'string'];
            }

            $validated = $request->validate($rules);

            // Convertir campos booleanos
            if ($isLioren) {
                $validated['facturacion_enabled'] = (bool) ($request->input('facturacion_enabled', 0));
                $validated['boletas_enabled'] = (bool) ($request->input('boletas_enabled', 0));
                $validated['shopify_visibility_enabled'] = (bool) ($request->input('shopify_visibility_enabled', 0));
                $validated['notas_credito_enabled'] = (bool) ($request->input('notas_credito_enabled', 0));
                $validated['sync_inventario_enabled'] = (bool) ($request->input('sync_inventario_enabled', 0));
                $validated['documentos_postventa_enabled'] = (bool) ($request->input('documentos_postventa_enabled', 0));
                $validated['order_limit_enabled'] = (bool) ($request->input('order_limit_enabled', 0));
            }

            // Campos de plan anual
            $validated['plan_anual_activo'] = (bool) ($request->input('plan_anual_activo', 0));
            $validated['descuento_anual'] = $request->input('descuento_anual');
            $validated['precio_anual'] = $request->input('precio_anual');

            // Auto-calcular precio anual si hay descuento pero no precio anual manual
            if ($validated['plan_anual_activo'] && !empty($validated['descuento_anual']) && empty($validated['precio_anual'])) {
                $precioMensual = (float) $validated['precio'];
                $descuento = (int) $validated['descuento_anual'];
                $validated['precio_anual'] = round($precioMensual * 12 * (1 - $descuento / 100), 2);
            }

            // Construir características para Lioren
            if ($isLioren) {
                $caracteristicas = [];
                if ($request->facturacion_enabled) $caracteristicas[] = '✅ Emisión de facturas electrónicas';
                if ($request->boletas_enabled) $caracteristicas[] = '🧾 Emisión de boletas electrónicas';
                if ($request->shopify_visibility_enabled) $caracteristicas[] = '👁️ Visibilidad desde Shopify';
                if ($request->notas_credito_enabled) $caracteristicas[] = '🔄 Notas de Crédito Automáticas';
                if ($request->sync_inventario_enabled) $caracteristicas[] = '📦 Sincronización de Inventario';
                if ($request->documentos_postventa_enabled) $caracteristicas[] = '📝 Documentos Postventa';
                if ($request->order_limit_enabled && $request->monthly_order_limit) {
                    $caracteristicas[] = "📊 Límite: {$request->monthly_order_limit} pedidos/mes";
                } elseif (!$request->order_limit_enabled) {
                    $caracteristicas[] = '♾️ Sin límite de pedidos';
                }
                $validated['caracteristicas'] = $caracteristicas;
            }

            $plan->update($validated);

            \Log::info('========== UPDATE PLAN SUCCESS ==========');

            return redirect()->route('planes.index', ['refresh' => time()])
                ->with('success', 'Plan actualizado exitosamente');
                
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Validation Failed', ['errors' => $e->errors()]);
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            \Log::error('Update Failed', ['error' => $e->getMessage()]);
            return redirect()->back()->withErrors(['error' => 'Error al actualizar el plan'])->withInput();
        }
    }

    public function destroy(Plan $plan)
    {
        $plan->delete();

        return redirect()->route('planes.index')
            ->with('success', 'Plan eliminado exitosamente');
    }
}
