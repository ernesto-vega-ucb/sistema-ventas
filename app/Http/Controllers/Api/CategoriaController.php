<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use App\Services\AuditLogger;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CategoriaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Categoria::query();

            if ($request->has('estado')) {
                $query->where('estado', $request->input('estado'));
            }

            if ($request->has('search') && $request->input('search') != '') {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('nombre', 'ilike', "%{$search}%")
                      ->orWhere('descripcion', 'ilike', "%{$search}%");
                });
            }

            $sortBy = (string) $request->get('sort_by', 'id');
            $sortOrder = (string) $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            if ($request->input('all') === 'true') {
                $categorias = $query->get();
            } else {
                $perPage = (int) $request->get('per_page', 10);
                $categorias = $query->paginate($perPage);
            }

            return response()->json(['success' => true, 'data' => $categorias], 200);

        } catch (Exception $e) {
            AuditLogger::error('Error al obtener categorías', $e);
            return response()->json(['success' => false, 'message' => 'Error', 'error' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'nombre' => 'required|string|max:100|unique:categorias,nombre',
                'descripcion' => 'nullable|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
            }

            DB::beginTransaction();
            $categoria = Categoria::create([
                'nombre' => $request->input('nombre'),
                'descripcion' => $request->input('descripcion'),
                'estado' => true,
            ]);
            DB::commit();

            AuditLogger::insercion("Categoría creada: ID {$categoria->id}", $categoria->toArray());

            return response()->json(['success' => true, 'data' => $categoria], 201);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function show(mixed $id): JsonResponse
    {
        $categoria = Categoria::find($id);
        if (!$categoria) {
            return response()->json(['success' => false, 'message' => 'No encontrada'], 404);
        }
        return response()->json(['success' => true, 'data' => $categoria]);
    }

    public function update(Request $request, mixed $id): JsonResponse
    {
        $categoria = Categoria::find($id);
        if (!$categoria) {
            return response()->json(['success' => false, 'message' => 'No encontrada'], 404);
        }

        $categoria->update([
            'nombre' => $request->input('nombre'),
            'descripcion' => $request->input('descripcion'),
        ]);

        return response()->json(['success' => true, 'data' => $categoria]);
    }

    public function toggleEstado(mixed $id): JsonResponse
    {
        $categoria = Categoria::find($id);
        if (!$categoria) {
            return response()->json(['success' => false, 'message' => 'No encontrada'], 404);
        }

        $nuevoEstado = !$categoria->estado;
        $categoria->update(['estado' => $nuevoEstado]);

        return response()->json(['success' => true, 'data' => $categoria]);
    }
}
