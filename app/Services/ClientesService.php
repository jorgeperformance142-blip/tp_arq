<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class ClientesService
{
    public function __construct(private NivelesService $nivelesSvc) {}

    // LISTADO con búsqueda, filtro y saldo desde vista
    public function listar(string $q = '', $estado = null, int $perPage = 10)
    {
        $clientes = DB::table('clientes as c')
            ->leftJoin('vw_saldo_puntos_cliente as v', 'v.cliente_id', '=', 'c.id')
            ->select(
                'c.id','c.nombre','c.apellido','c.numero_documento','c.tipo_documento',
                'c.nacionalidad','c.email','c.telefono','c.fecha_nacimiento','c.activo',
                DB::raw('COALESCE(v.saldo_total,0) as puntos')
            )
            ->when($q !== '', function ($sql) use ($q) {
                $like = '%'.$q.'%';
                $sql->where(function($w) use ($like) {
                    $w->where('c.nombre','like',$like)
                      ->orWhere('c.apellido','like',$like)
                      ->orWhere('c.email','like',$like)
                      ->orWhere('c.numero_documento','like',$like);
                });
            })
            ->when($estado !== null && $estado !== '', fn($sql) => $sql->where('c.activo',(int)$estado))
            ->orderBy('c.apellido')->orderBy('c.nombre')
            ->paginate($perPage);

        return $this->adjuntarNivelAPaginador($clientes);
    }

    // OBTENER un cliente (sin saldo)
    public function obtener(int $id): ?object
    {
        $cliente = DB::table('clientes as c')
            ->leftJoin('vw_saldo_puntos_cliente as v', 'v.cliente_id', '=', 'c.id')
            ->select('c.*', DB::raw('COALESCE(v.saldo_total,0) as puntos'))
            ->where('c.id',$id)
            ->first();

        return $cliente ? $this->adjuntarNivel($cliente) : null;
    }

    // CREAR
    public function crear(array $data): int
    {
        return (int) DB::table('clientes')->insertGetId([
            'nombre'           => $data['nombre'],
            'apellido'         => $data['apellido'],
            'numero_documento' => $data['numero_documento'] ?? null,
            'tipo_documento'   => $data['tipo_documento']   ?? null,
            'nacionalidad'     => $data['nacionalidad']     ?? null,
            'email'            => $data['email']            ?? null,
            'telefono'         => $data['telefono']         ?? null,
            'fecha_nacimiento' => $data['fecha_nacimiento'] ?? null,
            'activo'           => !empty($data['activo']) ? 1 : 0,
        ]);
    }

    // ACTUALIZAR
    public function actualizar(int $id, array $data): void
    {
        DB::table('clientes')->where('id',$id)->update([
            'nombre'           => $data['nombre'],
            'apellido'         => $data['apellido'],
            'numero_documento' => $data['numero_documento'] ?? null,
            'tipo_documento'   => $data['tipo_documento']   ?? null,
            'nacionalidad'     => $data['nacionalidad']     ?? null,
            'email'            => $data['email']            ?? null,
            'telefono'         => $data['telefono']         ?? null,
            'fecha_nacimiento' => $data['fecha_nacimiento'] ?? null,
            'activo'           => !empty($data['activo']) ? 1 : 0,
        ]);
    }

    // ELIMINAR
    public function eliminar(int $id): void
    {
        DB::table('clientes')->where('id',$id)->delete();
    }

    public function nacionalidades(): array
    {
        return DB::table('clientes')
            ->whereNotNull('nacionalidad')
            ->where('nacionalidad','<>','')
            ->distinct()
            ->orderBy('nacionalidad')
            ->pluck('nacionalidad')
            ->toArray();
    }

    public function segmentar(array $filters, int $perPage = 10)
    {
        $compras = DB::table('bolsas_puntos')
            ->select(
                'cliente_id',
                DB::raw('COALESCE(SUM(monto_operacion),0) as monto_total'),
                DB::raw('COUNT(*) as operaciones'),
                DB::raw('COALESCE(SUM(puntaje_asignado),0) as puntos_asignados')
            )
            ->groupBy('cliente_id');

        $query = DB::table('clientes as c')
            ->leftJoinSub($compras, 'b', 'b.cliente_id', '=', 'c.id')
            ->select(
                'c.id','c.nombre','c.apellido','c.numero_documento','c.nacionalidad','c.email','c.telefono','c.activo',
                DB::raw('TIMESTAMPDIFF(YEAR, c.fecha_nacimiento, CURDATE()) as edad'),
                DB::raw('COALESCE(b.monto_total,0) as monto_total'),
                DB::raw('COALESCE(b.operaciones,0) as compras'),
                DB::raw('COALESCE(b.puntos_asignados,0) as puntos_asignados')
            )
            ->when(($filters['q'] ?? '') !== '', function ($sql) use ($filters) {
                $like = '%'.$filters['q'].'%';
                $sql->where(function($w) use ($like) {
                    $w->where('c.nombre','like',$like)
                      ->orWhere('c.apellido','like',$like)
                      ->orWhere('c.email','like',$like)
                      ->orWhere('c.numero_documento','like',$like);
                });
            })
            ->when(isset($filters['estado']) && $filters['estado'] !== '', fn($sql) => $sql->where('c.activo',(int)$filters['estado']))
            ->when(array_key_exists('edad_min',$filters) && $filters['edad_min'] !== null, function ($sql) use ($filters) {
                $sql->whereRaw('TIMESTAMPDIFF(YEAR, c.fecha_nacimiento, CURDATE()) >= ?', [(int)$filters['edad_min']]);
            })
            ->when(array_key_exists('edad_max',$filters) && $filters['edad_max'] !== null, function ($sql) use ($filters) {
                $sql->whereRaw('TIMESTAMPDIFF(YEAR, c.fecha_nacimiento, CURDATE()) <= ?', [(int)$filters['edad_max']]);
            })
            ->when(($filters['nacionalidad'] ?? '') !== '', fn($sql) => $sql->where('c.nacionalidad', $filters['nacionalidad']))
            ->when(array_key_exists('monto_min',$filters) && $filters['monto_min'] !== null, function ($sql) use ($filters) {
                $sql->whereRaw('COALESCE(b.monto_total,0) >= ?', [$filters['monto_min']]);
            })
            ->when(array_key_exists('monto_max',$filters) && $filters['monto_max'] !== null, function ($sql) use ($filters) {
                $sql->whereRaw('COALESCE(b.monto_total,0) <= ?', [$filters['monto_max']]);
            })
            ->when(array_key_exists('compras_min',$filters) && $filters['compras_min'] !== null, function ($sql) use ($filters) {
                $sql->whereRaw('COALESCE(b.operaciones,0) >= ?', [$filters['compras_min']]);
            })
            ->when(array_key_exists('puntos_min',$filters) && $filters['puntos_min'] !== null, function ($sql) use ($filters) {
                $sql->whereRaw('COALESCE(b.puntos_asignados,0) >= ?', [$filters['puntos_min']]);
            });

        switch ($filters['orden'] ?? 'monto_desc') {
            case 'compras_desc':
                $query->orderByDesc('compras');
                break;
            case 'puntos_desc':
                $query->orderByDesc('puntos_asignados');
                break;
            case 'edad_desc':
                $query->orderByDesc('edad');
                break;
            case 'recientes':
                $query->orderByDesc('c.fecha_alta');
                break;
            default:
                $query->orderByDesc('monto_total');
        }

        $query->orderBy('c.apellido')->orderBy('c.nombre');

        $clientes = $query->paginate($perPage);

        return $this->adjuntarNivelAPaginador($clientes);
    }

    private function adjuntarNivel(object $cliente): object
    {
        $puntos = (int) ($cliente->puntos ?? $cliente->puntos_asignados ?? 0);
        $nivel = $this->nivelesSvc->nivelParaPuntos($puntos);

        $cliente->nivel = $nivel;
        $cliente->nivel_id = $nivel['id'] ?? null;

        return $cliente;
    }

    private function adjuntarNivelAPaginador(LengthAwarePaginator $paginator): LengthAwarePaginator
    {
        $paginator->setCollection(
            $paginator->getCollection()->map(fn($c) => $this->adjuntarNivel($c))
        );

        return $paginator;
    }
}
