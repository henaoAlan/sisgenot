<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Period;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * PeriodController — Gestión de períodos académicos.
 *
 * Permisos:
 *   - admin: CRUD + abrir/cerrar períodos
 *   - teacher/student: solo lectura
 *
 * Endpoints:
 *   GET    /api/periods                   → Listar períodos
 *   POST   /api/periods                   → Crear (admin)
 *   GET    /api/periods/{id}              → Ver detalle
 *   PUT    /api/periods/{id}              → Actualizar (admin)
 *   DELETE /api/periods/{id}              → Eliminar (admin)
 *   PATCH  /api/periods/{id}/toggle       → Abrir/cerrar período (admin)
 */
class PeriodController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $periods = Period::query()
            ->when($request->year, fn($q) => $q->where('year', $request->year))
            ->when($request->is_open !== null, fn($q) => $q->where('is_open', filter_var($request->is_open, FILTER_VALIDATE_BOOLEAN)))
            ->orderBy('year', 'desc')
            ->orderBy('ordering')
            ->withCount('grades')
            ->get();

        return response()->json(['data' => $periods], 200);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'year'     => ['required', 'integer', 'min:2000', 'max:2100'],
            'ordering' => ['required', 'integer', 'min:1', 'max:10',
                           \Illuminate\Validation\Rule::unique('periods')->where(fn($q) => $q->where('year', $request->year))],
            'name'     => ['required', 'string', 'max:50'],
            'is_open'  => ['boolean'],
        ], [
            'ordering.unique' => 'Ya existe un período con ese orden en el año especificado.',
        ]);

        $period = Period::create($validated);

        return response()->json([
            'message' => 'Período creado exitosamente.',
            'period'  => $period,
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $period = Period::withCount('grades')->findOrFail($id);

        return response()->json(['period' => $period], 200);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $period = Period::findOrFail($id);

        $validated = $request->validate([
            'name'    => ['sometimes', 'string', 'max:50'],
            'is_open' => ['sometimes', 'boolean'],
        ]);

        $period->update($validated);

        return response()->json([
            'message' => 'Período actualizado exitosamente.',
            'period'  => $period,
        ], 200);
    }

    public function destroy(int $id): JsonResponse
    {
        $period = Period::findOrFail($id);

        if ($period->grades()->count() > 0) {
            return response()->json([
                'message' => 'No se puede eliminar un período con notas registradas.',
                'error'   => 'PERIOD_HAS_GRADES',
            ], 409);
        }

        $period->delete();

        return response()->json(['message' => 'Período eliminado exitosamente.'], 200);
    }

    /**
     * Alterna el estado abierto/cerrado de un período.
     * Cuando se cierra, no se podrán registrar nuevas notas.
     */
    public function toggle(int $id): JsonResponse
    {
        $period = Period::findOrFail($id);
        $period->update(['is_open' => ! $period->is_open]);

        $estado = $period->is_open ? 'abierto' : 'cerrado';

        return response()->json([
            'message' => "Período {$estado} exitosamente.",
            'period'  => $period,
        ], 200);
    }
}
