<?php

namespace App\Services;

use App\Exceptions\LdapLoginException;
use App\Models\User;
use Illuminate\Support\Collection;
use LdapRecord\Models\ActiveDirectory\User as LdapUser;
use Throwable;

class LdapLoginService
{
    /**
     * @var array<int, string>
     */
    private array $usernameAttributes = [
        'samaccountname',
        'uid',
        'userprincipalname',
    ];

    public function attempt(string $username, string $password): User
    {
        $localUser = User::query()
            ->where('username', $username)
            ->first();

        if (! $localUser) {
            throw new LdapLoginException(401, 'Akun belum diizinkan untuk login ke aplikasi ini.');
        }

        $ldapUser = $this->findLdapUser($username);

        if (! $ldapUser) {
            throw new LdapLoginException(401, 'Username atau password salah.');
        }

        if (! $ldapUser->getConnection()->auth()->attempt($ldapUser->getDn(), $password)) {
            throw new LdapLoginException(401, 'Username atau password salah.');
        }

        return $localUser;
    }

    private function findLdapUser(string $username): ?LdapUser
    {
        foreach ($this->candidateBaseDns() as $baseDn) {
            foreach ($this->usernameAttributes as $attribute) {
                $user = LdapUser::query()
                    ->in($baseDn)
                    ->where($attribute, '=', $username)
                    ->first();

                if ($user) {
                    return $user;
                }
            }
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    private function candidateBaseDns(): array
    {
        $baseDns = Collection::make([
            trim((string) config('ldap.connections.default.base_dn', '')),
        ]);

        try {
            $rootDse = LdapUser::getRootDse();
            $baseDns->push(trim((string) $rootDse->getFirstAttribute('defaultnamingcontext', '')));
        } catch (Throwable) {
            //
        }

        return $baseDns
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
