@extends('admin.layout')
@section('titulo', 'Editar plan ' . $plan->nombre)
@section('contenido')
    <h1>Editar plan: {{ $plan->nombre }}</h1>
    <form method="POST" action="{{ route('admin.planes.update', $plan) }}" style="max-width:480px;">
        @csrf
        @method('PUT')
        <label>Nombre</label>
        <input type="text" name="nombre" value="{{ old('nombre', $plan->nombre) }}">

        <label>Descripción</label>
        <textarea name="descripcion" rows="3">{{ old('descripcion', $plan->descripcion) }}</textarea>

        <label>Precio mensual (USD)</label>
        <input type="number" step="0.01" min="0" name="precio_mensual" value="{{ old('precio_mensual', $plan->precio_mensual) }}">

        <label>Límite de emisores</label>
        <input type="number" min="1" name="limite_emisores" value="{{ old('limite_emisores', $plan->limite_emisores) }}">

        <label>Límite de usuarios</label>
        <input type="number" min="1" name="limite_usuarios" value="{{ old('limite_usuarios', $plan->limite_usuarios) }}">

        <label>Límite de documentos/mes (vacío = ilimitado)</label>
        <input type="number" min="1" name="limite_documentos_mes" value="{{ old('limite_documentos_mes', $plan->limite_documentos_mes) }}">

        <label>Orden</label>
        <input type="number" min="0" name="orden" value="{{ old('orden', $plan->orden) }}">

        <label>Estado</label>
        <select name="estado">
            <option value="activo" @selected(old('estado', $plan->estado) === 'activo')>Activo</option>
            <option value="inactivo" @selected(old('estado', $plan->estado) === 'inactivo')>Inactivo</option>
        </select>

        <label style="display:flex; gap:.5rem; align-items:center; margin-top:1rem;">
            <input type="checkbox" name="es_visible" value="1" @checked(old('es_visible', $plan->es_visible)) style="width:auto;">
            Visible para asignar a tenants
        </label>

        <div style="margin-top:1.5rem; display:flex; gap:.5rem;">
            <button class="btn btn-azul">Guardar</button>
            <a class="btn btn-gris" href="{{ route('admin.planes.index') }}">Cancelar</a>
        </div>
    </form>
@endsection