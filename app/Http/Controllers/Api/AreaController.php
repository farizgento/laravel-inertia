<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AreaController extends Controller
{
    public function index()
    {
        return Area::query()
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'kode']);
    }

    private function authorizeSuperAdmin(Request $request): User
    {
        $actor = $request->user();
        $actor?->loadMissing('role');

        abort_unless($actor && $actor->role?->key === Role::KEY_SUPER_ADMIN, 403);

        return $actor;
    }

    private function formatArea(Area $area): array
    {
        return [
            'id' => $area->id,
            'name' => $area->name,
            'slug' => $area->slug,
            'kode' => $area->kode,
            'users_count' => (int) ($area->users_count ?? 0),
            'created_at' => $area->created_at?->format('d M Y H:i'),
        ];
    }

    private function generateUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($name);
        if ($baseSlug === '') {
            $baseSlug = 'area';
        }

        $slug = $baseSlug;
        $suffix = 2;

        while (
            Area::query()
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = $baseSlug . '-' . $suffix;
            $suffix += 1;
        }

        return $slug;
    }

    public function managementIndex(Request $request): array
    {
        $this->authorizeSuperAdmin($request);

        $search = trim((string) $request->query('search', ''));
        $perPage = (int) $request->query('per_page', 10);
        $perPage = $perPage > 0 ? min($perPage, 100) : 10;

        $query = Area::query()
            ->withCount('users')
            ->orderBy('name');

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('name', 'like', '%' . $search . '%')
                    ->orWhere('slug', 'like', '%' . $search . '%')
                    ->orWhere('kode', 'like', '%' . $search . '%');
            });
        }

        $areas = $query->paginate($perPage);

        return [
            'data' => $areas->getCollection()
                ->map(fn (Area $area) => $this->formatArea($area))
                ->values(),
            'meta' => [
                'current_page' => $areas->currentPage(),
                'last_page' => $areas->lastPage(),
                'per_page' => $areas->perPage(),
                'total' => $areas->total(),
            ],
        ];
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizeSuperAdmin($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('areas', 'name')],
            'kode' => ['required', 'string', 'max:255', Rule::unique('areas', 'kode')],
        ]);

        $area = Area::create([
            'name' => $validated['name'],
            'slug' => $this->generateUniqueSlug($validated['name']),
            'kode' => $validated['kode'],
        ]);

        $area->loadCount('users');

        return response()->json([
            'message' => 'Area berhasil ditambahkan.',
            'area' => $this->formatArea($area),
        ], 201);
    }

    public function update(Request $request, Area $area): JsonResponse
    {
        $this->authorizeSuperAdmin($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('areas', 'name')->ignore($area->id)],
            'kode' => ['required', 'string', 'max:255', Rule::unique('areas', 'kode')->ignore($area->id)],
        ]);

        $area->update([
            'name' => $validated['name'],
            'slug' => $this->generateUniqueSlug($validated['name'], $area->id),
            'kode' => $validated['kode'],
        ]);

        $area->loadCount('users');

        return response()->json([
            'message' => 'Area berhasil diperbarui.',
            'area' => $this->formatArea($area->fresh()->loadCount('users')),
        ]);
    }

    public function destroy(Request $request, Area $area): JsonResponse
    {
        $this->authorizeSuperAdmin($request);

        if ($area->users()->exists()) {
            return response()->json([
                'message' => 'Area tidak dapat dihapus karena masih memiliki pengguna.',
            ], 422);
        }

        try {
            $area->delete();
        } catch (QueryException $exception) {
            return response()->json([
                'message' => 'Area tidak dapat dihapus karena masih terhubung dengan data lain.',
            ], 422);
        }

        return response()->json([
            'message' => 'Area berhasil dihapus.',
        ]);
    }
}
