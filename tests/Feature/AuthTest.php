<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function registerData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ], $overrides);
    }

    public function test_guest_can_view_login_and_register_pages(): void
    {
        $this->get('/login')->assertOk()->assertViewIs('auth.login');
        $this->get('/register')->assertOk()->assertViewIs('auth.register');
    }

    public function test_authenticated_user_is_redirected_from_login_and_register(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/login')->assertRedirect('/');
        $this->actingAs($user)->get('/register')->assertRedirect('/');
    }

    public function test_user_can_register(): void
    {
        $response = $this->post('/register', $this->registerData());

        $response->assertRedirect('/');
        $this->assertDatabaseHas('users', ['name' => 'テストユーザー', 'email' => 'test@example.com']);
        $this->assertAuthenticated();
    }

    public function test_register_requires_all_fields(): void
    {
        $this->post('/register', [])->assertSessionHasErrors([
            'name' => 'お名前を入力してください',
            'email' => 'メールアドレスを入力してください',
            'password' => 'パスワードを入力してください',
        ]);
        $this->assertGuest();
    }

    public function test_register_rejects_invalid_email(): void
    {
        $this->post('/register', $this->registerData(['email' => 'invalid']))
            ->assertSessionHasErrors(['email' => 'メールアドレスはメール形式で入力してください']);
    }

    public function test_register_rejects_duplicate_email(): void
    {
        User::factory()->create(['email' => 'test@example.com']);

        $this->post('/register', $this->registerData())
            ->assertSessionHasErrors(['email' => 'このメールアドレスは既に登録されています']);
    }

    public function test_register_rejects_too_long_name(): void
    {
        $this->post('/register', $this->registerData(['name' => str_repeat('あ', 256)]))
            ->assertSessionHasErrors(['name' => 'お名前は255文字以内で入力してください']);
    }

    public function test_register_rejects_short_password(): void
    {
        $this->post('/register', $this->registerData(['password' => 'short', 'password_confirmation' => 'short']))
            ->assertSessionHasErrors(['password' => 'パスワードは8文字以上で入力してください']);
    }

    public function test_register_rejects_unmatched_password_confirmation(): void
    {
        $this->post('/register', $this->registerData(['password_confirmation' => 'different']))
            ->assertSessionHasErrors(['password' => 'パスワードと確認用パスワードが一致しません']);
    }

    public function test_user_can_login(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $response->assertRedirect('/');
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_requires_email_and_password(): void
    {
        $this->post('/login', [])->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください',
            'password' => 'パスワードを入力してください',
        ]);
    }

    public function test_login_rejects_invalid_email_format(): void
    {
        $this->post('/login', ['email' => 'invalid', 'password' => 'password'])
            ->assertSessionHasErrors(['email' => 'メールアドレスはメール形式で入力してください']);
    }

    public function test_login_fails_with_wrong_credentials(): void
    {
        $user = User::factory()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'wrong-password'])
            ->assertSessionHasErrors(['email' => 'ログイン情報が登録されていません']);
        $this->assertGuest();
    }

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }
}
