<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\LdapDirectoryService;
use Illuminate\Http\Request;
use Throwable;

class LdapUserLookupController extends Controller
{
    public function __invoke(Request $request, LdapDirectoryService $ldapDirectoryService): array
    {
        $validated = $request->validate([
            'search' => ['required', 'string', 'min:3', 'max:80'],
        ]);

        try {
            return [
                'data' => $ldapDirectoryService->searchUsers($validated['search']),
            ];
        } catch (Throwable) {
            return [
                'data' => [],
                'message' => 'Gagal mencari data LDAP.',
            ];
        }
    }
}
