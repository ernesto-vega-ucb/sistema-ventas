<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use App\Services\AuditLogger;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator; // Importación correcta

class CategoriaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Categoria::query();

            if ($request->has('estado')) {
                $query->where('estado', $request->input('estado'));
            }

            if ($request->has('search') && filled($request->input('search'))) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('nombre', 'ilike', "%{$search}%")
                        ->orWhere('descripcion', 'ilike', "%{$search}%");
                });
            }

            $sortBy = (string) $request->get('sort_by', 'id');
            $sortOrder = (string) $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            $categorias = ($request->input('all') === 'true')
                ? $query->get()
                : $query->paginate((int) $request->get('per_page', 10));

            return response()->json(['success' => true, 'data' => $categorias]);

        } catch (Exception $e) {
            AuditLogger::error('Error al obtener categorías', $e);

            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:100|unique:categorias,nombre',
            'descripcion' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();
            $categoria = Categoria::create([
                'nombre' => (string) $request->input('nombre'),
                'descripcion' => (string) $request->input('descripcion'),
                'estado' => true,
            ]);
            DB::commit();

            return response()->json(['success' => true, 'data' => $categoria], 201);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function show(mixed $id): JsonResponse
    {
        $categoria = Categoria::find($id);
        if (! $categoria) {
            return response()->json(['success' => false, 'message' => 'No encontrada'], 404);
        }

        return response()->json(['success' => true, 'data' => $categoria]);
    }
}
