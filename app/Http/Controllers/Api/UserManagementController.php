<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserManagementController extends Controller
{
    private const DEFAULT_ALLOWED_ROLE_KEYS = [
        Role::KEY_USER,
        Role::KEY_SP_TOOL,
        Role::KEY_PIC_TOOLS,
    ];

    private const SUPER_ADMIN_EXTRA_ROLE_KEYS = [
        Role::KEY_MGR_TOOL,
        Role::KEY_ADMIN,
    ];

    private function authorizeActor(Request $request): User
    {
        $actor = $request->user();
        $actor?->loadMissing(['role', 'area']);

        abort_unless(
            $actor && in_array($actor->role?->key, [Role::KEY_ADMIN, Role::KEY_SUPER_ADMIN], true),
            403
        );

        return $actor;
    }

    private function isAreaScopedAdmin(User $actor): bool
    {
        return $actor->role?->key === Role::KEY_ADMIN;
    }

    private function allowedRoleKeysForActor(User $actor): array
    {
        if ($actor->role?->key === Role::KEY_SUPER_ADMIN) {
            return [
                ...self::DEFAULT_ALLOWED_ROLE_KEYS,
                ...self::SUPER_ADMIN_EXTRA_ROLE_KEYS,
            ];
        }

        return self::DEFAULT_ALLOWED_ROLE_KEYS;
    }

    private function manageableUsersQuery(User $actor)
    {
        $allowedRoleKeys = $this->allowedRoleKeysForActor($actor);

        $query = User::query()
            ->with(['area', 'role'])
            ->whereHas('role', function ($query) use ($allowedRoleKeys) {
                $query->whereIn('key', $allowedRoleKeys);
            });

        if ($this->isAreaScopedAdmin($actor)) {
            if ($actor->area_id) {
                $query->where('area_id', $actor->area_id);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        return $query;
    }

    private function formatUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'area_id' => $user->area_id,
            'area_name' => $user->area?->name ?? '-',
            'role_id' => $user->role_id,
            'role_key' => $user->role?->key ?? '',
            'role_name' => $user->role?->name ?? '-',
            'created_at' => optional($user->created_at)?->toISOString(),
        ];
    }

    private function findRole(string $roleKey): Role
    {
        return Role::query()->where('key', $roleKey)->firstOrFail();
    }

    private function resolveMgrArea(): ?Area
    {
        return Area::query()
            ->whereRaw('upper(name) = ?', ['KS TUBUN'])
            ->first();
    }

    private function resolveAreaIdForRole(Role $role, int $areaId, ?int $ignoreUserId = null): int
    {
        if ($role->key !== Role::KEY_MGR_TOOL) {
            return $areaId;
        }

        $mgrArea = $this->resolveMgrArea();

        if (! $mgrArea) {
            abort(response()->json([
                'message' => 'Area KS TUBUN belum tersedia.',
                'errors' => [
                    'area_id' => ['Area KS TUBUN belum tersedia.'],
                ],
            ], 422));
        }

        $mgrExists = User::query()
            ->where('role_id', $role->id)
            ->when($ignoreUserId, fn ($query) => $query->where('id', '!=', $ignoreUserId))
            ->exists();

        if ($mgrExists) {
            abort(response()->json([
                'message' => 'User Mgr Tool sudah tersedia.',
                'errors' => [
                    'role_key' => ['Mgr Tool hanya boleh satu user dan wajib berada di KS TUBUN.'],
                ],
            ], 422));
        }

        return (int) $mgrArea->id;
    }

    public function index(Request $request): array
    {
        $actor = $this->authorizeActor($request);
        $allowedRoleKeys = $this->allowedRoleKeysForActor($actor);

        $search = trim((string) $request->query('search', ''));
        $roleKey = trim((string) $request->query('role', ''));
        $areaId = (int) $request->query('area_id', 0);
        $perPage = (int) $request->query('per_page', 10);
        $perPage = $perPage > 0 ? min($perPage, 100) : 10;

        $query = $this->manageableUsersQuery($actor);

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%')
                    ->orWhereHas('area', function ($areaQuery) use ($search) {
                        $areaQuery->where('name', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('role', function ($roleQuery) use ($search) {
                        $roleQuery
                            ->where('name', 'like', '%' . $search . '%')
                            ->orWhere('key', 'like', '%' . $search . '%');
                    });
            });
        }

        if (in_array($roleKey, $allowedRoleKeys, true)) {
            $query->whereHas('role', function ($roleQuery) use ($roleKey) {
                $roleQuery->where('key', $roleKey);
            });
        }

        if ($actor->role?->key === Role::KEY_SUPER_ADMIN && $areaId > 0) {
            $query->where('area_id', $areaId);
        }

        $users = $query
            ->orderBy('name')
            ->paginate($perPage);

        return [
            'data' => $users->getCollection()
                ->map(fn (User $user) => $this->formatUser($user))
                ->values(),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        ];
    }

    public function store(Request $request): JsonResponse
    {
        $actor = $this->authorizeActor($request);
        $allowedRoleKeys = $this->allowedRoleKeysForActor($actor);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'role_key' => ['required', 'string', Rule::in($allowedRoleKeys)],
            'area_id' => ['required', 'integer', 'exists:areas,id'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $role = $this->findRole($validated['role_key']);
        $areaId = $this->resolveAreaIdForRole($role, (int) $validated['area_id']);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role_id' => $role->id,
            'area_id' => $areaId,
        ]);

        return response()->json([
            'message' => 'Pengguna berhasil ditambahkan.',
            'user' => $user->load(['area', 'role']),
        ], 201);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $actor = $this->authorizeActor($request);
        $allowedRoleKeys = $this->allowedRoleKeysForActor($actor);

        $manageableUser = $this->manageableUsersQuery($actor)->find($user->id);
        abort_unless($manageableUser, 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role_key' => ['required', 'string', Rule::in($allowedRoleKeys)],
            'area_id' => ['required', 'integer', 'exists:areas,id'],
            'password' => ['nullable', 'confirmed', Password::min(8)],
        ]);

        $role = $this->findRole($validated['role_key']);
        $areaId = $this->resolveAreaIdForRole($role, (int) $validated['area_id'], $user->id);

        $payload = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role_id' => $role->id,
            'area_id' => $areaId,
        ];

        if (! empty($validated['password'])) {
            $payload['password'] = $validated['password'];
        }

        $user->update($payload);

        return response()->json([
            'message' => 'Pengguna berhasil diperbarui.',
            'user' => $user->fresh()->load(['area', 'role']),
        ]);
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        $actor = $this->authorizeActor($request);

        $manageableUser = $this->manageableUsersQuery($actor)->find($user->id);
        abort_unless($manageableUser, 404);

        if ((int) $actor->id === (int) $user->id) {
            return response()->json([
                'message' => 'Akun yang sedang dipakai tidak bisa dihapus.',
            ], 422);
        }

        try {
            $user->delete();
        } catch (QueryException $exception) {
            return response()->json([
                'message' => 'Pengguna tidak dapat dihapus karena masih terhubung dengan data lain.',
            ], 422);
        }

        return response()->json([
            'message' => 'Pengguna berhasil dihapus.',
        ]);
    }
}
