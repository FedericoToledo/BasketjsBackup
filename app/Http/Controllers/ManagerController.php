<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ManagerController extends Controller
{
    public function activar(Request $request): JsonResponse
    {
        $user = $request->user();
        $tieneRetiro = $user->jugadores()->where('retiro', true)->exists();
        if (! $tieneRetiro) {
            return response()->json(['ok' => false, 'error' => 'Primero tenés que terminar una carrera.'], 422);
        }

        $user->es_manager = true;
        $user->save();

        return response()->json(['ok' => true]);
    }

    public function nuevo(Request $request): JsonResponse
    {
        if (! $request->user()->es_manager) {
            return response()->json(['ok' => false, 'error' => 'Solo un manager puede crear otro jugador.'], 403);
        }

        return response()->json(['ok' => true]);
    }
}
