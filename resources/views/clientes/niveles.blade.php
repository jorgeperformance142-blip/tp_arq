@extends('layouts.app')
@section('title','Niveles de Fidelización')
@section('subtitle','Define las metas de puntos y los beneficios por nivel')

@section('content')
<div class="px-6 py-6 space-y-5">
  <div class="bg-white rounded-xl shadow p-6 space-y-4">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
      <div>
        <h2 class="text-lg font-semibold text-slate-800">Escala de niveles</h2>
        <p class="text-sm text-slate-500">Los clientes ascienden automáticamente al alcanzar los puntos requeridos.</p>
      </div>
      <a href="{{ route('clientes.index') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-md border border-slate-200 hover:bg-slate-50 text-sm text-slate-700">👥 Ver clientes</a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
      @foreach($niveles as $nivel)
        <div class="border border-slate-100 rounded-xl p-4 bg-slate-50">
          <div class="flex items-center justify-between mb-2">
            <h3 class="text-lg font-semibold text-slate-800">{{ $nivel['nombre'] }}</h3>
            <span class="px-3 py-1 text-xs rounded-full bg-slate-900 text-white">{{ ucfirst($nivel['slug']) }}</span>
          </div>
          <p class="text-sm text-slate-500 mb-3">Desde {{ number_format($nivel['min'], 0, ',', '.') }} pts</p>
          <ul class="space-y-2 text-sm text-slate-700 list-disc list-inside">
            @foreach($nivel['beneficios'] as $b)
              <li>{{ $b }}</li>
            @endforeach
          </ul>
        </div>
      @endforeach
    </div>
  </div>

  <div class="bg-white rounded-xl shadow p-6 space-y-3">
    <h2 class="text-lg font-semibold text-slate-800">¿Cómo se asciende?</h2>
    <ol class="list-decimal list-inside text-sm text-slate-700 space-y-2">
      <li>Acumulá puntos a partir de las compras registradas en la <a href="{{ route('bolsas.index') }}" class="text-slate-900 font-medium hover:underline">bolsa de puntos</a>.</li>
      <li>Al superar el umbral del siguiente nivel, el cliente asciende automáticamente.</li>
      <li>Los beneficios del nuevo nivel se habilitan de inmediato y se reflejan en el detalle del cliente.</li>
    </ol>
  </div>
</div>
@endsection
