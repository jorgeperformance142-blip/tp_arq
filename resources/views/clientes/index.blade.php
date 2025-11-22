@extends('layouts.app')
@section('title','Gestión de Clientes')

@section('content')
    <div class="px-6 py-6 space-y-4">

    {{-- Flash --}}
    @if(session('ok'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-2 rounded">
        {{ session('ok') }}
        </div>
    @endif
  {{-- Buscador + Filtros --}}
  <form method="get" class="bg-white rounded-xl shadow p-3 flex items-center gap-3">
    <div class="flex items-center gap-2 flex-1">
      <span class="text-slate-400">🔍</span>
      <input type="text" name="q" value="{{ $q }}"
             placeholder="Buscar por nombre, email o documento..."
             class="w-full border border-slate-200 rounded-md px-3 py-2 focus:outline-none focus:ring focus:ring-slate-200" />
    </div>

    <select name="estado" class="border border-slate-200 rounded-md px-2 py-2">
      <option value="">Todos</option>
      <option value="1" @selected($estado==='1')>Activos</option>
      <option value="0" @selected($estado==='0')>Inactivos</option>
    </select>

    <select name="per_page" class="border border-slate-200 rounded-md px-2 py-2">
      @foreach([10,25,50] as $n)
        <option value="{{ $n }}" @selected((int)$perPage===$n)>{{ $n }}/pág</option>
      @endforeach
    </select>

    <button class="bg-slate-900 text-white text-sm px-3 py-2 rounded-md hover:bg-slate-800">Filtrar</button>
    <a href="{{ route('clientes.segmentacion') }}"
       class="inline-flex items-center gap-2 border border-slate-200 text-slate-700 text-sm px-3 py-2 rounded-lg hover:bg-slate-50"
       title="Crear segmentaciones según edad, ubicación o historial de compras">
       🧩 Segmentar clientes
    </a>
    <a href="{{ route('clientes.create') }}"
        class="inline-flex items-center gap-2 bg-slate-900 text-white text-sm px-3 py-2 rounded-lg hover:bg-slate-800">
        ＋ Nuevo Cliente
        </a>
  </form>

  {{-- Tabla --}}
  <div class="bg-white rounded-xl shadow overflow-hidden">
    <div class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-slate-600">
          <tr>
            <th class="text-left px-4 py-3">Cliente</th>
            <th class="text-left px-4 py-3">Documento</th>
            <th class="text-left px-4 py-3">Email</th>
            <th class="text-left px-4 py-3">Teléfono</th>
            <th class="text-left px-4 py-3">Puntos</th>
            <th class="text-left px-4 py-3">Estado</th>
            <th class="text-left px-4 py-3">Acciones</th>
          </tr>
        </thead>
        <tbody class="divide-y">
          @forelse ($clientes as $c)
            <tr class="hover:bg-slate-50">
              <td class="px-4 py-3">
                <div class="font-medium text-gray-800">{{ $c->nombre }} {{ $c->apellido }}</div>
                <div class="text-xs text-gray-500">{{ $c->nacionalidad ?: '—' }}</div>
              </td>
              <td class="px-4 py-3">
                <div class="text-gray-800">{{ $c->numero_documento ?: '—' }}</div>
                <div class="text-xs text-gray-500">{{ $c->tipo_documento ?: '—' }}</div>
              </td>
              <td class="px-4 py-3">{{ $c->email ?: '—' }}</td>
              <td class="px-4 py-3">{{ $c->telefono ?: '—' }}</td>
              <td class="px-4 py-3">
                <span class="px-2 py-1 text-xs rounded-full border border-blue-200 bg-blue-50 text-blue-700">
                  {{ number_format($c->puntos) }} pts
                </span>
              </td>
              <td class="px-4 py-3">
                @if ($c->activo)
                  <span class="px-2 py-1 text-xs rounded-full bg-emerald-100 text-emerald-700">Activo</span>
                @else
                  <span class="px-2 py-1 text-xs rounded-full bg-slate-200 text-slate-700">Inactivo</span>
                @endif
              </td>
              <td class="px-4 py-3">
                <div class="flex items-center gap-2">
                  <a href="{{ route('clientes.show', $c->id) }}" class="p-1.5 rounded border hover:bg-slate-50" title="Ver">👁️</a>
                  <a href="{{ route('clientes.edit', $c->id) }}" class="p-1.5 rounded border hover:bg-slate-50" title="Editar">✏️</a>
                  <form action="{{ route('clientes.destroy', $c->id) }}" method="post" onsubmit="return confirm('¿Eliminar cliente?')">
                    @csrf @method('DELETE')
                    <button class="p-1.5 rounded border hover:bg-red-50" title="Eliminar">🗑️</button>
                  </form>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="px-4 py-10 text-center text-slate-500">Sin resultados.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    {{-- Paginación --}}
    <div class="px-4 py-3 border-t">
      {{ $clientes->links() }}
    </div>
  </div>
</div>
@endsection
