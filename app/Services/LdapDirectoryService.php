<?php

namespace App\Services;

use App\Models\LdapUser;
use Illuminate\Support\Collection;
use LdapRecord\Models\ActiveDirectory\User as DirectoryUser;
use Throwable;

class LdapDirectoryService
{
    private const SELECT_ATTRIBUTES = [
        'cn',
        'displayname',
        'mail',
        'name',
        'physicaldeliveryofficename',
        'samaccountname',
        'uid',
        'userprincipalname',
    ];

    public function syncUsers(): int
    {
        $now = now();
        $sourceUsers = $this->fetchUsersFromDirectory();

        if ($sourceUsers === []) {
            return 0;
        }

        foreach (array_chunk($sourceUsers, 200) as $chunk) {
            foreach ($chunk as $attributes) {
                LdapUser::query()->updateOrCreate(
                    ['ldap_dn' => $attributes['ldap_dn']],
                    [
                        'name' => $attributes['name'],
                        'username' => $attributes['username'],
                        'email' => $attributes['email'],
                        'last_synced_at' => $now,
                    ]
                );
            }
        }

        LdapUser::query()
            ->where('last_synced_at', '<', $now)
            ->delete();

        return count($sourceUsers);
    }

    /**
     * @return array<int, array{ldap_dn: string, name: string, username: string, email: string}>
     */
    private function fetchUsersFromDirectory(): array
    {
        $results = [];

        foreach ($this->candidateBaseDns() as $baseDn) {
            $query = DirectoryUser::query()
                ->in($baseDn)
                ->select(self::SELECT_ATTRIBUTES);

            if (! $this->hasSyncBaseDn()) {
                $query = $this->applyMaintenanceUnitFilter($query);
            }

            $users = $query
                ->limit(7000)
                ->get();

            foreach ($users as $user) {
                $data = $this->formatUser($user);

                if ($data) {
                    $results[$data['ldap_dn']] = $data;
                }
            }
        }

        return array_values($results);
    }

    /**
     * @return array<int, string>
     */
    private function candidateBaseDns(): array
    {
        if ($syncBaseDn = $this->effectiveSyncBaseDn()) {
            return [$syncBaseDn];
        }

        $baseDns = Collection::make([
            trim((string) config('ldap.connections.default.base_dn', '')),
        ]);

        try {
            $baseDns->push($this->defaultNamingContext());
        } catch (Throwable) {
            //
        }

        return $baseDns
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function hasSyncBaseDn(): bool
    {
        return $this->effectiveSyncBaseDn() !== '';
    }

    private function effectiveSyncBaseDn(): string
    {
        $syncBaseDn = trim((string) config('ldap.sync.base_dn', ''));

        if ($syncBaseDn !== '') {
            return $syncBaseDn;
        }

        $configuredBaseDn = trim((string) config('ldap.connections.default.base_dn', ''));

        if ($configuredBaseDn === '') {
            return '';
        }

        try {
            $defaultNamingContext = $this->defaultNamingContext();

            if ($defaultNamingContext !== '' && strcasecmp($configuredBaseDn, $defaultNamingContext) !== 0) {
                return $configuredBaseDn;
            }
        } catch (Throwable) {
            //
        }

        return '';
    }

    private function defaultNamingContext(): string
    {
        $rootDse = DirectoryUser::getRootDse();

        return trim((string) $rootDse->getFirstAttribute('defaultnamingcontext', ''));
    }

    private function applyMaintenanceUnitFilter($query)
    {
        $attribute = trim((string) config('ldap.sync.unit_attribute', 'physicaldeliveryofficename'));
        $values = array_values(array_filter(array_map(
            'trim',
            (array) config('ldap.sync.unit_values', [])
        )));

        if ($attribute === '' || $values === []) {
            return $query;
        }

        $escapedAttribute = $query->escape($attribute)->forDnAndFilter()->get();
        $rawFilter = '(|'.collect($values)
            ->map(fn (string $value) => sprintf(
                '(%s=%s)',
                $escapedAttribute,
                $query->escape($value)->get()
            ))
            ->implode('').')';

        return $query->rawFilter($rawFilter);
    }

    /**
     * @return array{ldap_dn: string, name: string, username: string, email: string}|null
     */
    private function formatUser(DirectoryUser $user): ?array
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
            'ldap_dn' => $dn,
            'name' => $name,
            'username' => $username,
            'email' => $email,
        ];
    }
}
