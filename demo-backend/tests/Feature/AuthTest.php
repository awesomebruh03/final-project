<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_creates_a_user_and_returns_it_with_a_token(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Sara',
            'email' => 'sara@example.com',
            'password' => '123456',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['user' => ['id', 'name', 'email'], 'token'])
            ->assertJsonPath('user.name', 'Sara')
            ->assertJsonPath('user.email', 'sara@example.com')
            ->assertJsonMissingPath('user.password');

        $user = User::where('email', 'sara@example.com')->firstOrFail();

        $this->assertTrue(Hash::check('123456', $user->password));
        $this->assertNotSame('123456', $user->password);
        $this->assertSame(1, $user->tokens()->count());

        $this->withToken($response->json('token'))
            ->getJson('/api/expenses')
            ->assertOk();
    }

    public function test_register_returns_409_when_the_email_is_already_registered(): void
    {
        User::factory()->create(['email' => 'sara@example.com']);

        $response = $this->postJson('/api/register', [
            'name' => 'Sara',
            'email' => 'Sara@Example.com',
            'password' => '123456',
        ]);

        $response->assertStatus(409)
            ->assertJsonStructure(['message']);

        $this->assertSame(1, User::where('email', 'sara@example.com')->count());
    }

    public function test_register_returns_400_for_invalid_input(): void
    {
        $response = $this->postJson('/api/register', []);

        $response->assertStatus(400)
            ->assertJsonStructure(['message']);

        $this->assertNotEmpty($response->json('message'));
        $this->assertDatabaseCount('users', 0);
    }

    public function test_login_returns_the_user_and_a_token_with_valid_credentials(): void
    {
        User::factory()->create([
            'name' => 'Sara',
            'email' => 'sara@example.com',
            'password' => '123456',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'sara@example.com',
            'password' => '123456',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['user' => ['id', 'name', 'email'], 'token'])
            ->assertJsonPath('user.email', 'sara@example.com')
            ->assertJsonMissingPath('user.password');
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function invalidCredentials(): array
    {
        return [
            'wrong password' => ['sara@example.com', 'wrong-password'],
            'unknown email' => ['nobody@example.com', '123456'],
        ];
    }

    #[DataProvider('invalidCredentials')]
    public function test_login_returns_401_with_a_vague_message_for_invalid_credentials(string $email, string $password): void
    {
        User::factory()->create([
            'email' => 'sara@example.com',
            'password' => '123456',
        ]);

        $this->postJson('/api/login', [
            'email' => $email,
            'password' => $password,
        ])->assertStatus(401)
            ->assertExactJson(['message' => 'Wrong email or password']);
    }

    public function test_logout_revokes_the_current_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/logout')
            ->assertOk()
            ->assertExactJson(['ok' => true]);

        $this->assertSame(0, $user->tokens()->count());

        $this->app['auth']->forgetGuards();

        $this->withToken($token)
            ->getJson('/api/expenses')
            ->assertStatus(401);
    }
}
