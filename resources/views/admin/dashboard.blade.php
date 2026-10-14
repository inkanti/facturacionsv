@extends('admin.layout')
@section('titulo', 'Panel')
@section('contenido')
    <h1>Panel general</h1>
    <div class="tarjetas">
        <div class="tarjeta"><div class="num">{{ $totales['tenants'] }}</div><div class="lbl">Negocios registrados</div></div>
        <div class="tarjeta"><div class="num">{{ $totales['activas'] }}</div><div class="lbl">Suscripciones activas</div></div>
        <div class="tarjeta"><div class="num">{{ $totales['trials'] }}</div><div class="lbl">En prueba gratis</div></div>
        <div class="tarjeta"><div class="num">{{ $totales['bloqueadas'] }}</div><div class="lbl">Vencidas / suspendidas</div></div>
        <div class="tarjeta"><div class="num">${{ number_format($ingresoMensual, 2) }}</div><div class="lbl">Ingreso mensual estimado</div></div>
    </div>
    <h1 style="font-size:1.1rem;">Últimos negocios</h1>
    <table>
        <thead><tr><th>ID</th><th>Nombre</th><th>Registrado</th></tr></thead>
        <tbody>
        @foreach($ultimosTenants as $t)
            <tr>
                <td>{{ $t->id }}</td>
                <td><a href="{{ route('admin.tenants.show', $t) }}">{{ $t->name }}</a></td>
                <td>{{ $t->created_at?->format('d/m/Y') }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endsection