<?php

namespace Tests\Feature;

use App\Models\Room;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BpsSsoLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.bps_sso', [
            'client_id' => 'mlti-client',
            'client_secret' => 'test-secret',
            'authorize_url' => 'https://sso.example.test/realms/pegawai/protocol/openid-connect/auth',
            'token_url' => 'https://sso.example.test/realms/pegawai/protocol/openid-connect/token',
            'userinfo_url' => 'https://sso.example.test/realms/pegawai/protocol/openid-connect/userinfo',
            'redirect' => 'http://localhost:8000/',
            'scopes' => 'openid profile email',
            'use_pkce' => true,
            'token_auth_method' => 'client_secret_post',
            'ca_bundle' => null,
            'verify_ssl' => true,
            'profile_path' => null,
            'nip_claims' => ['nip', 'nip_baru', 'preferred_username', 'username'],
            'email_claims' => ['email'],
        ]);
    }

    public function test_redirect_to_bps_sso_has_state_and_pkce_challenge(): void
    {
        $response = $this->get(route('login.sso.redirect'));

        $response->assertRedirect();
        $this->assertStringStartsWith(config('services.bps_sso.authorize_url'), $response->headers->get('Location'));
        $this->assertStringContainsString('response_type=code', $response->headers->get('Location'));
        $this->assertStringContainsString('redirect_uri=http%3A%2F%2Flocalhost%3A8000%2F', $response->headers->get('Location'));
        $this->assertStringContainsString('code_challenge_method=S256', $response->headers->get('Location'));
        $response->assertSessionHas('bps_sso_state');
        $response->assertSessionHas('bps_sso_code_verifier');
    }

    public function test_known_user_can_login_with_bps_sso_profile_nip(): void
    {
        Team::create(['id' => 1, 'fungsi' => 'Kepala']);
        Room::create(['id' => 4, 'ruang' => 'Kepala']);
        $user = User::create([
            'nip_lama' => 340011248,
            'nip_baru' => '196703221994011001',
            'name' => 'John Doe',
            'email' => 'johndoe@bps.go.id',
            'password' => bcrypt('password'),
            'fungsi' => 1,
            'id_ruang' => 4,
        ]);

        Http::fake([
            config('services.bps_sso.token_url') => Http::response(['access_token' => 'access-token'], 200),
            config('services.bps_sso.userinfo_url') => Http::response(['nip' => $user->nip_baru], 200),
        ]);

        $state = Str::random(64);
        $response = $this->withSession([
            'bps_sso_state' => $state,
            'bps_sso_code_verifier' => Str::random(96),
        ])->get(route('portal', [
            'code' => 'authorization-code',
            'state' => $state,
        ]));

        $response->assertRedirect(route('portal'));
        $this->assertAuthenticatedAs($user);
        Http::assertSent(fn (ClientRequest $request) => $request->url() === config('services.bps_sso.token_url')
            && $request['grant_type'] === 'authorization_code');
        Http::assertSent(fn (ClientRequest $request) => $request->url() === config('services.bps_sso.userinfo_url')
            && $request->hasHeader('Authorization', 'Bearer access-token'));
    }

    public function test_callback_rejects_invalid_state_before_requesting_a_token(): void
    {
        Http::fake();

        $response = $this->withSession(['bps_sso_state' => 'expected-state'])
            ->get(route('portal', [
                'code' => 'authorization-code',
                'state' => 'unexpected-state',
            ]));

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('login_error');
        Http::assertNothingSent();
        $this->assertGuest();
    }

    public function test_known_user_can_login_with_bps_sso_profile_email(): void
    {
        Team::create(['id' => 1, 'fungsi' => 'Kepala']);
        Room::create(['id' => 4, 'ruang' => 'Kepala']);
        $user = User::create([
            'nip_lama' => 340011249,
            'name' => 'Jane Doe',
            'email' => 'janedoe@bps.go.id',
            'password' => bcrypt('password'),
            'fungsi' => 1,
            'id_ruang' => 4,
        ]);

        Http::fake([
            config('services.bps_sso.token_url') => Http::response(['access_token' => 'access-token'], 200),
            config('services.bps_sso.userinfo_url') => Http::response(['email' => $user->email], 200),
        ]);

        $state = Str::random(64);
        $response = $this->withSession([
            'bps_sso_state' => $state,
            'bps_sso_code_verifier' => Str::random(96),
        ])->get(route('portal', [
            'code' => 'authorization-code',
            'state' => $state,
        ]));

        $response->assertRedirect(route('portal'));
        $this->assertAuthenticatedAs($user);
    }
}
