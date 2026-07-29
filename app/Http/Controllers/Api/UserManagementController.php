<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LdapUser;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

class UserManagementController extends Controller
{
    private const DEFAULT_ALLOWED_ROLE_KEYS = [
        Role::KEY_GUEST,
        Role::KEY_USER,
        Role::KEY_SP_TOOL,
        Role::KEY_PIC_TOOL,
        Role::KEY_MGR_TOOL,
    ];

    private const SUPER_ADMIN_EXTRA_ROLE_KEYS = [
        Role::KEY_ADMIN,
    ];

    private function authorizeIndexActor(Request $request): User
    {
        $actor = $request->user();
        $actor?->loadMissing(['role', 'area']);

        abort_unless(
            $actor && in_array($actor->role?->key, [Role::KEY_MGR_TOOL, Role::KEY_ADMIN, Role::KEY_SUPER_ADMIN], true),
            403
        );

        return $actor;
    }

    private function authorizeCrudActor(Request $request): User
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

    private function visibleRoleKeysForActor(User $actor): array
    {
        if (in_array($actor->role?->key, [Role::KEY_MGR_TOOL, Role::KEY_SUPER_ADMIN], true)) {
            return [
                ...self::DEFAULT_ALLOWED_ROLE_KEYS,
                ...self::SUPER_ADMIN_EXTRA_ROLE_KEYS,
            ];
        }

        return $this->allowedRoleKeysForActor($actor);
    }

    private function applyWritableArea(User $actor, array $validated): array
    {
        if (! $this->isAreaScopedAdmin($actor)) {
            return $validated;
        }

        abort_unless($actor->area_id, 403, 'Admin belum memiliki area.');

        if ((int) $validated['area_id'] !== (int) $actor->area_id) {
            abort(403, 'Admin hanya dapat mengelola pengguna pada area sendiri.');
        }

        $validated['area_id'] = (int) $actor->area_id;

        return $validated;
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

    private function visibleUsersQuery(User $actor)
    {
        $visibleRoleKeys = $this->visibleRoleKeysForActor($actor);

        $query = User::query()
            ->with(['area', 'role'])
            ->whereHas('role', function ($query) use ($visibleRoleKeys) {
                $query->whereIn('key', $visibleRoleKeys);
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
            'username' => $user->username,
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
        return Role::firstOrCreate(
            ['key' => $roleKey],
            [
                'name' => match ($roleKey) {
                    Role::KEY_GUEST => 'Guest',
                    Role::KEY_USER => 'User',
                    Role::KEY_SP_TOOL => 'SP Tool',
                    Role::KEY_PIC_TOOL => 'PIC Tool',
                    Role::KEY_MGR_TOOL => 'Mgr Tool',
                    Role::KEY_ADMIN => 'Admin',
                    Role::KEY_SUPER_ADMIN => 'Super Admin',
                    default => $roleKey,
                },
            ]
        );
    }

    public function index(Request $request): array
    {
        $actor = $this->authorizeIndexActor($request);
        $visibleRoleKeys = $this->visibleRoleKeysForActor($actor);

        $search = trim((string) $request->query('search', ''));
        $roleKey = trim((string) $request->query('role', ''));
        $areaId = (int) $request->query('area_id', 0);
        $perPage = (int) $request->query('per_page', 10);
        $perPage = $perPage > 0 ? min($perPage, 100) : 10;

        $query = $this->visibleUsersQuery($actor);

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('name', 'like', '%' . $search . '%')
                    ->orWhere('username', 'like', '%' . $search . '%')
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

        if (in_array($roleKey, $visibleRoleKeys, true)) {
            $query->whereHas('role', function ($roleQuery) use ($roleKey) {
                $roleQuery->where('key', $roleKey);
            });
        }

        if (in_array($actor->role?->key, [Role::KEY_MGR_TOOL, Role::KEY_SUPER_ADMIN], true) && $areaId > 0) {
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
        $actor = $this->authorizeCrudActor($request);
        $allowedRoleKeys = $this->allowedRoleKeysForActor($actor);

        $validated = $request->validate([
            'ldap_user_id' => ['required', 'integer', 'exists:ldap_users,id'],
            'role_key' => ['required', 'string', Rule::in($allowedRoleKeys)],
            'area_id' => ['required', 'integer', 'exists:areas,id'],
        ]);
        $validated = $this->applyWritableArea($actor, $validated);

        $ldapUser = LdapUser::query()->find($validated['ldap_user_id']);

        if (! $ldapUser) {
            return response()->json([
                'message' => 'Data pengguna LDAP belum tersedia di cache lokal.',
                'errors' => [
                    'ldap_user_id' => ['Pilih pengguna dari hasil pencarian LDAP.'],
                ],
            ], 422);
        }

        $ldapValidator = Validator::make([
            'name' => $ldapUser->name,
            'username' => $ldapUser->username,
            'email' => $ldapUser->email,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users,username'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
        ]);

        if ($ldapValidator->fails()) {
            return response()->json([
                'message' => 'Data LDAP tidak dapat digunakan.',
                'errors' => $ldapValidator->errors(),
            ], 422);
        }

        $role = $this->findRole($validated['role_key']);

        $user = User::create([
            'name' => $ldapUser->name,
            'username' => $ldapUser->username,
            'email' => $ldapUser->email,
            'role_id' => $role->id,
            'area_id' => (int) $validated['area_id'],
        ]);

        return response()->json([
            'message' => 'Pengguna berhasil ditambahkan.',
            'user' => $user->load(['area', 'role']),
        ], 201);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $actor = $this->authorizeCrudActor($request);
        $allowedRoleKeys = $this->allowedRoleKeysForActor($actor);

        $manageableUser = $this->manageableUsersQuery($actor)->find($user->id);
        abort_unless($manageableUser, 404);

        $validated = $request->validate([
            'role_key' => ['required', 'string', Rule::in($allowedRoleKeys)],
            'area_id' => ['required', 'integer', 'exists:areas,id'],
        ]);
        $validated = $this->applyWritableArea($actor, $validated);

        $role = $this->findRole($validated['role_key']);

        $payload = [
            'role_id' => $role->id,
            'area_id' => (int) $validated['area_id'],
        ];

        $user->update($payload);

        return response()->json([
            'message' => 'Pengguna berhasil diperbarui.',
            'user' => $user->fresh()->load(['area', 'role']),
        ]);
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        $actor = $this->authorizeCrudActor($request);

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
