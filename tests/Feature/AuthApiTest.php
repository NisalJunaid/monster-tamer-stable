<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_receive_token(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Trainer Red',
            'email' => 'red@example.com',
            'password' => 'secretpass',
            'password_confirmation' => 'secretpass',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('users', ['email' => 'red@example.com']);
        $this->assertNotEmpty($response->json('token'));
    }

    public function test_user_can_login_and_receive_token(): void
    {
        $user = User::factory()->create([
            'email' => 'leaf@example.com',
            'password' => Hash::make('secretpass'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'leaf@example.com',
            'password' => 'secretpass',
        ]);

        $response->assertOk();
        $this->assertNotEmpty($response->json('token'));
    }

    public function test_logout_revokes_token_and_blocks_protected_route(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)->getJson('/api/profile')->assertOk();

        $this->withToken($token)->postJson('/api/logout')->assertNoContent();

        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->withToken($token)->getJson('/api/profile')->assertUnauthorized();
    }

    public function test_protected_route_requires_authentication(): void
    {
        $this->getJson('/api/profile')->assertUnauthorized();
    }
}
