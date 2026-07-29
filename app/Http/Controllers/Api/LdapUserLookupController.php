<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LdapUser;
use Illuminate\Http\Request;

class LdapUserLookupController extends Controller
{
    public function __invoke(Request $request): array
    {
        $validated = $request->validate([
            'search' => ['required', 'string', 'min:3', 'max:80'],
        ]);

        $term = trim($validated['search']);
        $normalizedTerm = mb_strtolower($term);

        $users = LdapUser::query()
            ->select(['id', 'name', 'username', 'email'])
            ->where(function ($query) use ($term) {
                $query
                    ->whereRaw('LOWER(name) LIKE ?', ['%' . mb_strtolower($term) . '%'])
                    ->orWhereRaw('LOWER(username) = ?', [$term])
                    ->orWhereRaw('LOWER(email) = ?', [$term]);
            })
            ->orderByRaw(
                'CASE WHEN LOWER(username) = ? OR LOWER(email) = ? THEN 0 ELSE 1 END',
                [$normalizedTerm, $normalizedTerm]
            )
            ->orderBy('name')
            ->limit(10)
            ->get();

        return [
            'data' => $users->map(fn (LdapUser $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
            ])->values(),
        ];
    }
}
