<?php

namespace App\Services;

use Illuminate\Support\Collection;
use LdapRecord\Models\ActiveDirectory\User as LdapUser;
use Throwable;

class LdapDirectoryService
{
    private const SELECT_ATTRIBUTES = [
        'cn',
        'displayname',
        'mail',
        'name',
        'samaccountname',
        'uid',
        'userprincipalname',
    ];

    /**
     * @return array<int, array{dn: string, name: string, username: string, email: string}>
     */
    public function searchUsers(string $term, int $limit = 10): array
    {
        $term = trim($term);

        if (strlen($term) < 3) {
            return [];
        }

        $results = collect();

        foreach ($this->candidateBaseDns() as $baseDn) {
            $users = LdapUser::query()
                ->in($baseDn)
                ->select(self::SELECT_ATTRIBUTES)
                ->orFilter(function ($query) use ($term): void {
                    $query
                        ->whereStartsWith('samaccountname', $term)
                        ->orWhereStartsWith('uid', $term)
                        ->orWhereStartsWith('displayname', $term)
                        ->orWhereStartsWith('cn', $term)
                        ->orWhereStartsWith('mail', $term);
                })
                ->limit($limit)
                ->get();

            foreach ($users as $user) {
                $data = $this->formatUser($user);

                if ($data && ! $results->has($data['dn'])) {
                    $results->put($data['dn'], $data);
                }
            }

            if ($results->count() >= $limit) {
                break;
            }
        }

        return $results
            ->values()
            ->take($limit)
            ->all();
    }

    /**
     * @return array{dn: string, name: string, username: string, email: string}|null
     */
    public function findUserByDn(string $dn): ?array
    {
        $dn = trim($dn);

        if ($dn === '') {
            return null;
        }

        $user = LdapUser::find($dn, self::SELECT_ATTRIBUTES);

        if (! $user instanceof LdapUser) {
            return null;
        }

        return $this->formatUser($user);
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

    /**
     * @return array{dn: string, name: string, username: string, email: string}|null
     */
    private function formatUser(LdapUser $user): ?array
    {
        $dn = trim((string) $user->getDn());
        $name = trim((string) (
            $user->getFirstAttribute('displayname')
            ?: $user->getFirstAttribute('cn')
            ?: $user->getFirstAttribute('name')
        ));
        $username = trim((string) (
            $user->getFirstAttribute('samaccountname')
            ?: $user->getFirstAttribute('uid')
        ));
        $email = trim((string) (
            $user->getFirstAttribute('mail')
            ?: $user->getFirstAttribute('userprincipalname')
        ));

        if ($dn === '' || $name === '' || $username === '' || $email === '') {
            return null;
        }

        return [
            'dn' => $dn,
            'name' => $name,
            'username' => $username,
            'email' => $email,
        ];
    }
}
