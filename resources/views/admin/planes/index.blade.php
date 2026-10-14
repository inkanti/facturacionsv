@extends('admin.layout')
@section('titulo', 'Planes')
@section('contenido')
    <h1>Planes</h1>
    <table>
        <thead>
        <tr><th>Orden</th><th>Nombre</th><th>Precio/mes</th><th>Emisores</th><th>Usuarios</th><th>Docs/mes</th><th>Visible</th><th>Estado</th><th></th></tr>
        </thead>
        <tbody>
        @foreach($planes as $plan)
            <tr>
                <td>{{ $plan->orden }}</td>
                <td>{{ $plan->nombre }}</td>
                <td>${{ $plan->precio_mensual }}</td>
                <td>{{ $plan->limite_emisores }}</td>
                <td>{{ $plan->limite_usuarios }}</td>
                <td>{{ $plan->limite_documentos_mes ?? '∞' }}</td>
                <td>{{ $plan->es_visible ? 'Sí' : 'No' }}</td>
                <td>{{ $plan->estado }}</td>
                <td><a class="btn btn-gris" href="{{ route('admin.planes.edit', $plan) }}">Editar</a></td>
            </tr>
        @endforeach
        </tbody>
    </table>
    {{ $planes->links() }}
@endsection