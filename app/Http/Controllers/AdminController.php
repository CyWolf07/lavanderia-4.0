<?php

namespace App\Http\Controllers;

use App\Models\FacturaRecolector;
use App\Models\HistorialProduccion;
use App\Models\Produccion;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    public function dashboard()
    {
        $totalUsuarios = User::count();
        $totalProducciones = Produccion::count();
        $ingresosTotales = Produccion::sum('total');

        $ultimasProducciones = Produccion::with(['user', 'prenda'])
            ->latest()
            ->take(10)
            ->get();

        $usuarios = User::query()
            ->orderBy('name')
            ->get();

        $resumenMensualPrendas = $this->resumenMensualPrendas();

        $periodosCerrados = HistorialProduccion::query()
            ->selectRaw('periodo, SUM(total) as total_general, SUM(cantidad) as total_prendas')
            ->groupBy('periodo')
            ->orderByDesc('periodo')
            ->get();

        return view('admin.dashboard', compact(
            'totalUsuarios',
            'totalProducciones',
            'ingresosTotales',
            'ultimasProducciones',
            'usuarios',
            'resumenMensualPrendas',
            'periodosCerrados'
        ));
    }

    public function storeUser(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'cedula' => ['nullable', 'string', 'max:50', 'unique:users,cedula'],
            'contacto' => ['nullable', 'string', 'max:50'],
            'rol' => ['required', 'in:admin,programador,usuario,recolector'],
            'activo' => ['nullable', 'boolean'],
            'puede_editar_precios' => ['nullable', 'boolean'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $rol = $this->resolverRol($data['rol']);

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'cedula' => $data['cedula'] ?? null,
            'contacto' => $data['contacto'] ?? null,
            'rol' => $data['rol'],
            'rol_id' => $rol->id,
            'activo' => $request->boolean('activo', true),
            'puede_editar_precios' => $data['rol'] === 'recolector' && $request->boolean('puede_editar_precios'),
            'password' => Hash::make($data['password']),
        ]);

        return redirect()->route('admin.dashboard')->with('success', 'Usuario creado correctamente.');
    }

    public function updateUser(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'cedula' => ['nullable', 'string', 'max:50', Rule::unique('users', 'cedula')->ignore($user->id)],
            'contacto' => ['nullable', 'string', 'max:50'],
            'rol' => ['required', 'in:admin,programador,usuario,recolector'],
            'activo' => ['nullable', 'boolean'],
            'puede_editar_precios' => ['nullable', 'boolean'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $rol = $this->resolverRol($data['rol']);

        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'cedula' => $data['cedula'] ?? null,
            'contacto' => $data['contacto'] ?? null,
            'rol' => $data['rol'],
            'rol_id' => $rol->id,
            'activo' => $request->boolean('activo', true),
            'puede_editar_precios' => $data['rol'] === 'recolector' && $request->boolean('puede_editar_precios'),
        ]);

        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        if ($user->id === auth()->id() && ! $user->activo) {
            return redirect()->route('admin.dashboard')->with('error', 'No puedes inhabilitar tu propia cuenta desde aqui.');
        }

        $user->save();

        return redirect()->route('admin.dashboard')->with('success', 'Usuario actualizado correctamente.');
    }

    public function destroyUser(User $user)
    {
        if ($user->id === auth()->id()) {
            return redirect()->route('admin.dashboard')->with('error', 'No puedes eliminar tu propia cuenta desde aqui.');
        }

        $user->delete();

        return redirect()->route('admin.dashboard')->with('success', 'Usuario eliminado correctamente.');
    }

    public function toggleUserStatus(User $user)
    {
        if ($user->id === auth()->id()) {
            return redirect()->route('admin.dashboard')->with('error', 'No puedes cambiar el estado de tu propia cuenta desde aqui.');
        }

        $user->activo = ! $user->activo;
        $user->save();

        return back()->with(
            'success',
            $user->activo ? 'Usuario habilitado correctamente.' : 'Usuario inhabilitado correctamente.'
        );
    }

    public function toggleRecolectorPriceEdit(User $user)
    {
        if (! $user->esRecolector()) {
            return back()->with('error', 'Solo los recolectores pueden tener permiso de editar precios.');
        }

        $user->puede_editar_precios = ! $user->puede_editar_precios;
        $user->save();

        return back()->with(
            'success',
            $user->puede_editar_precios
                ? 'Edición de precios habilitada para el recolector.'
                : 'Edición de precios inhabilitada para el recolector.'
        );
    }

    public function printReports(Request $request)
    {
        $producciones = Produccion::with(['user', 'prenda'])
            ->orderBy('fecha')
            ->orderBy('user_id')
            ->orderBy('id')
            ->get();

        $facturasRecolector = FacturaRecolector::with(['recolector', 'cliente', 'detalles'])
            ->orderBy('fecha_ingreso')
            ->orderBy('recolector_id')
            ->orderBy('id')
            ->get();

        $resumenUsuarios = $this->resumenProduccionUsuarios($producciones);
        $resumenRecolectores = $this->resumenRecolectores($facturasRecolector);

        $tipoReporte = $request->input('tipo_reporte', 'detallado');
        $grupo = $request->input('grupo', 'usuarios');
        $registroId = $request->filled('registro_id') ? (int) $request->input('registro_id') : null;

        $detalleUsuarios = $this->detalleProduccionUsuarios($producciones);
        $detalleRecolectores = $this->detalleFacturasRecolector($facturasRecolector);

        if ($registroId) {
            $detalleUsuarios = $detalleUsuarios->where('id', $registroId)->values();
            $detalleRecolectores = $detalleRecolectores->where('id', $registroId)->values();
        }

        return view('admin.reportes-impresion', [
            'tipoReporte' => $tipoReporte,
            'grupo' => $grupo,
            'registroId' => $registroId,
            'resumenUsuarios' => $resumenUsuarios,
            'resumenRecolectores' => $resumenRecolectores,
            'detalleUsuarios' => $detalleUsuarios,
            'detalleRecolectores' => $detalleRecolectores,
            'totalGeneralUsuarios' => $resumenUsuarios->sum('total'),
            'totalGeneralRecolectores' => $resumenRecolectores->sum('total'),
            'totalPrendasUsuarios' => $resumenUsuarios->sum('cantidad'),
            'totalPrendasRecolectores' => $resumenRecolectores->sum('cantidad'),
            'autoPrint' => $request->boolean('imprimir'),
        ]);
    }

    public function destroyHistorial(HistorialProduccion $historialProduccion)
    {
        $historialProduccion->delete();

        return redirect()->route('admin.dashboard')->with('success', 'Registro historico eliminado correctamente.');
    }

    private function resolverRol(string $rol): Rol
    {
        return Rol::firstOrCreate(
            ['nombre' => ucfirst($rol)],
            ['descripcion' => 'Rol '.$rol.' del sistema']
        );
    }

    private function resumenMensualPrendas()
    {
        $activos = Produccion::with('prenda')
            ->whereYear('fecha', now()->year)
            ->whereMonth('fecha', now()->month)
            ->get()
            ->groupBy(fn ($item) => $item->prenda->nombre ?? 'Sin nombre')
            ->map(fn ($items, $nombre) => [
                'prenda' => $nombre,
                'cantidad' => $items->sum('cantidad'),
                'total' => $items->sum('total'),
            ]);

        $historicos = HistorialProduccion::query()
            ->where('anio', now()->year)
            ->where('mes', now()->month)
            ->get()
            ->groupBy('prenda_nombre')
            ->map(fn ($items, $nombre) => [
                'prenda' => $nombre,
                'cantidad' => $items->sum('cantidad'),
                'total' => $items->sum('total'),
            ]);

        return $activos
            ->mergeRecursive($historicos)
            ->map(function ($item, $nombre) {
                $cantidad = is_array($item['cantidad']) ? array_sum($item['cantidad']) : $item['cantidad'];
                $total = is_array($item['total']) ? array_sum($item['total']) : $item['total'];

                return [
                    'prenda' => $nombre,
                    'cantidad' => $cantidad,
                    'total' => $total,
                ];
            })
            ->sortByDesc('cantidad')
            ->values();
    }

    private function resumenProduccionUsuarios(Collection $producciones): Collection
    {
        return $producciones
            ->groupBy('user_id')
            ->map(function (Collection $registros, $userId) {
                $usuario = $registros->first()->user;

                return [
                    'id' => (int) $userId,
                    'nombre' => $usuario->name ?? 'Usuario eliminado',
                    'rol' => $usuario?->obtenerRol() ?? 'usuario',
                    'cantidad' => (int) $registros->sum('cantidad'),
                    'total' => (float) $registros->sum('total'),
                ];
            })
            ->sortBy('nombre')
            ->values();
    }

    private function resumenRecolectores(Collection $facturas): Collection
    {
        return $facturas
            ->groupBy('recolector_id')
            ->map(function (Collection $registros, $recolectorId) {
                $recolector = $registros->first()->recolector;

                return [
                    'id' => (int) $recolectorId,
                    'nombre' => $recolector->name ?? 'Recolector eliminado',
                    'rol' => $recolector?->obtenerRol() ?? 'recolector',
                    'cantidad' => (int) $registros->sum('total_prendas'),
                    'total' => (float) $registros->sum('total'),
                ];
            })
            ->sortBy('nombre')
            ->values();
    }

    private function detalleProduccionUsuarios(Collection $producciones): Collection
    {
        return $producciones
            ->groupBy('user_id')
            ->map(function (Collection $registros, $userId) {
                $usuario = $registros->first()->user;

                return [
                    'id' => (int) $userId,
                    'nombre' => $usuario->name ?? 'Usuario eliminado',
                    'rol' => $usuario?->obtenerRol() ?? 'usuario',
                    'cedula' => $usuario->cedula ?? 'No registrada',
                    'contacto' => $usuario->contacto ?? 'No registrado',
                    'cantidad' => (int) $registros->sum('cantidad'),
                    'total' => (float) $registros->sum('total'),
                    'registros' => $registros,
                ];
            })
            ->sortBy('nombre')
            ->values();
    }

    private function detalleFacturasRecolector(Collection $facturas): Collection
    {
        return $facturas
            ->groupBy('recolector_id')
            ->map(function (Collection $registros, $recolectorId) {
                $recolector = $registros->first()->recolector;

                return [
                    'id' => (int) $recolectorId,
                    'nombre' => $recolector->name ?? 'Recolector eliminado',
                    'rol' => $recolector?->obtenerRol() ?? 'recolector',
                    'cedula' => $recolector->cedula ?? 'No registrada',
                    'contacto' => $recolector->contacto ?? 'No registrado',
                    'cantidad' => (int) $registros->sum('total_prendas'),
                    'total' => (float) $registros->sum('total'),
                    'registros' => $registros,
                ];
            })
            ->sortBy('nombre')
            ->values();
    }
}


