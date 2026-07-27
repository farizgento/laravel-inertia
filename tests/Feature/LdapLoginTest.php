<?php

namespace Tests\Feature;

use App\Exceptions\LdapLoginException;
use App\Models\Area;
use App\Models\Role;
use App\Models\User;
use App\Services\LdapLoginService;
use Mockery;
use Tests\TestCase;

class LdapLoginTest extends TestCase
{
    private function makeAuthenticatedUser(): User
    {
        $user = new User([
            'name' => 'Edy Rustanto',
            'username' => 'edy.rustanto',
            'email' => 'edy.rustanto@example.com',
        ]);
        $user->id = 101;
        $user->setRelation('role', new Role([
            'id' => 1,
            'key' => Role::KEY_MGR_TOOL,
            'name' => 'Mgr Tool',
        ]));
        $user->setRelation('area', new Area([
            'id' => 2,
            'name' => 'Unit Bisnis Pemeliharaan',
            'kode' => 'UBH',
        ]));

        return $user;
    }

    public function test_login_uses_ldap_service_and_returns_local_user(): void
    {
        $user = $this->makeAuthenticatedUser();

        $service = Mockery::mock(LdapLoginService::class);
        $service->shouldReceive('attempt')
            ->once()
            ->with('edy.rustanto', 'Secret123')
            ->andReturn($user);

        $this->app->instance(LdapLoginService::class, $service);

        $this->postJson('/api/auth/login', [
            'username' => 'edy.rustanto',
            'password' => 'Secret123',
        ])
            ->assertOk()
            ->assertJsonPath('user.username', 'edy.rustanto')
            ->assertJsonPath('user.role.key', Role::KEY_MGR_TOOL)
            ->assertJsonPath('user.area.name', 'Unit Bisnis Pemeliharaan');

        $this->assertAuthenticated('web');
    }

    public function test_login_returns_401_when_ldap_service_rejects_credentials(): void
    {
        $service = Mockery::mock(LdapLoginService::class);
        $service->shouldReceive('attempt')
            ->once()
            ->andThrow(new LdapLoginException(401, 'Username atau password salah.'));

        $this->app->instance(LdapLoginService::class, $service);

        $this->postJson('/api/auth/login', [
            'username' => 'edy.rustanto',
            'password' => 'wrong',
        ])
            ->assertStatus(401)
            ->assertJson([
                'message' => 'Username atau password salah.',
            ]);
    }
}
