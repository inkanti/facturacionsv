<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    public function index()
    {
        $planes = Plan::orderBy('orden')->paginate(15);

        return view('admin.planes.index', compact('planes'));
    }

    public function edit(Plan $plan)
    {
        return view('admin.planes.edit', compact('plan'));
    }

    public function update(Request $request, Plan $plan)
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:60'],
            'descripcion' => ['nullable', 'string'],
            'precio_mensual' => ['required', 'numeric', 'min:0'],
            'limite_emisores' => ['required', 'integer', 'min:1'],
            'limite_usuarios' => ['required', 'integer', 'min:1'],
            'limite_documentos_mes' => ['nullable', 'integer', 'min:1'],
            'es_visible' => ['nullable', 'boolean'],
            'estado' => ['required', 'in:activo,inactivo'],
            'orden' => ['required', 'integer', 'min:0'],
        ]);

        $datos['es_visible'] = $request->boolean('es_visible');
        $plan->update($datos);

        return redirect()->route('admin.planes.index')->with('exito', 'Plan actualizado.');
    }
}