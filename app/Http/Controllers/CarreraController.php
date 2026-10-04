<?php

namespace App\Http\Controllers;

use App\Models\Jugador;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CarreraController extends Controller
{
    public function sesion(Request $request): JsonResponse
    {
        $user = $request->user();
        $jugadores = [];
        $activa = null;

        if ($user) {
            $filas = $user->jugadores()->orderByDesc('id')->get();
            $jugadores = $filas->map(fn (Jugador $jugador) => [
                'id' => $jugador->id,
                'nombre' => $jugador->nombre,
                'posicion' => $jugador->posicion,
                'retiro' => (bool) $jugador->retiro,
            ])->all();
            $viva = $filas->first(fn (Jugador $jugador) => ! $jugador->retiro);
            $mostrar = $viva ?: $filas->first();
            $activa = $mostrar ? $mostrar->fichaLista() : null;
        }

        return response()->json([
            'google' => filled(config('services.google.client_id')),
            'user' => $user ? [
                'nombre' => $user->name,
                'email' => $user->email,
                'avatar' => $user->avatar,
                'manager' => (bool) $user->es_manager,
            ] : null,
            'jugadores' => $jugadores,
            'activa' => $activa,
        ]);
    }

    public function ver(Request $request, int $id): JsonResponse
    {
        $jugador = $request->user()->jugadores()->find($id);
        if (! $jugador) {
            return response()->json(['ok' => false, 'error' => 'No encuentro ese jugador.'], 404);
        }

        return response()->json(['ok' => true, 'ficha' => $jugador->fichaLista()]);
    }

    public function guardar(Request $request): JsonResponse
    {
        $datos = $request->json()->all();
        if (! is_array($datos) || $datos === []) {
            return response()->json(['ok' => false, 'error' => 'No llegaron los datos del jugador.'], 422);
        }

        $nombre = trim((string) ($datos['nombre'] ?? ''));
        $posicion = (string) ($datos['posicion'] ?? '');
        if ($nombre === '' || ! in_array($posicion, ['PG', 'SG', 'SF', 'PF', 'C'], true)) {
            return response()->json(['ok' => false, 'error' => 'Completá el nombre y elegí una posición.'], 422);
        }

        $stats = is_array($datos['stats'] ?? null) ? $datos['stats'] : [];
        $user = $request->user();
        $id = (int) ($datos['id'] ?? 0);
        $retiro = ! empty($datos['retirado']) || ($datos['fase'] ?? '') === 'retiro';
        $jugador = $id > 0 ? Jugador::find($id) : null;

        if ($jugador && $jugador->user_id && (! $user || (int) $jugador->user_id !== (int) $user->id)) {
            return response()->json(['ok' => false, 'error' => 'Esa carrera pertenece a otra cuenta.'], 403);
        }
        if (! $jugador) {
            $id = 0;
            if ($user) {
                if ($user->jugadores()->where('retiro', false)->exists()) {
                    return response()->json(['ok' => false, 'error' => 'Ya tenés una carrera activa.'], 422);
                }
                if ($user->jugadores()->exists() && ! $user->es_manager) {
                    return response()->json(['ok' => false, 'error' => 'Pasá a manager para crear otro jugador.'], 422);
                }
            }
        }

        $attrs = [
            'nombre' => $nombre,
            'posicion' => $posicion,
            'velocidad' => (int) ($stats['velocidad'] ?? 50),
            'tiro' => (int) ($stats['tiro'] ?? 50),
            'rebote' => (int) ($stats['fuerza'] ?? 50),
            'pase' => (int) ($stats['control'] ?? 50),
            'defensa' => (int) ($stats['defensa'] ?? 50),
            'equipo_id' => ! empty($datos['equipoId']) ? (int) $datos['equipoId'] : null,
            'retiro' => $retiro,
        ];
        if ($user) {
            $attrs['user_id'] = $user->id;
        }

        if ($jugador) {
            $jugador->fill($attrs);
        } else {
            $jugador = new Jugador($attrs);
        }
        $datos['id'] = $jugador->id ?: 0;
        $datos['retirado'] = $retiro;
        $jugador->ficha = json_encode($datos, JSON_UNESCAPED_UNICODE);
        $jugador->save();
        $datos['id'] = $jugador->id;
        $jugador->ficha = json_encode($datos, JSON_UNESCAPED_UNICODE);
        $jugador->save();

        return response()->json(['ok' => true, 'id' => $jugador->id]);
    }
}
