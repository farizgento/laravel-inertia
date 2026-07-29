<?php

namespace App\Console\Commands;

use App\Services\LdapDirectoryService;
use Illuminate\Console\Command;

class SyncLdapUsers extends Command
{
    protected $signature = 'ldap:sync-users';

    protected $description = 'Sync LDAP users into the local cache table';

    public function handle(LdapDirectoryService $ldapDirectoryService): int
    {
        $count = $ldapDirectoryService->syncUsers();

        $this->info("Synced {$count} LDAP users.");

        return self::SUCCESS;
    }
}
