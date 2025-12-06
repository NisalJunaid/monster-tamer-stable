<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_dashboard(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_user_can_register_and_access_dashboard(): void
    {
        $response = $this->post('/register', [
            'name' => 'Blue',
            'email' => 'blue@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
        $this->assertNotEmpty(session('api_token'));

        $dashboard = $this->get('/dashboard');
        $dashboard->assertOk();
        $dashboard->assertSee('Welcome, Blue');
    }

    public function test_non_admin_cannot_access_admin_area(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)->get('/admin')->assertStatus(403);
    }
}
