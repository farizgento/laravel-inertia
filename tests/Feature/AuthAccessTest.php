<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class AuthAccessTest extends TestCase
{
    private function makeUser(): User
    {
        $user = new User([
            'name' => 'Test User',
            'username' => 'test.user',
            'email' => 'test@example.com',
        ]);
        $user->id = 1;
        $user->setRelation('area', null);
        $user->setRelation('role', null);

        return $user;
    }

    public function test_guest_can_view_login_page(): void
    {
        $this->get('/login')->assertOk();
    }

    public function test_authenticated_user_is_redirected_away_from_login_page(): void
    {
        $this->actingAs($this->makeUser());

        $this->get('/login')->assertRedirect('/dashboard');
    }
}
