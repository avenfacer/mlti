<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BpsSsoController extends Controller
{
    public function landing(Request $request)
    {
        if ($request->hasAny(['code', 'state', 'error'])) {
            return $this->callback($request);
        }

        return view('portal');
    }

    public function redirect(Request $request)
    {
        if (! $this->isConfigured()) {
            return $this->backToLogin('SSO BPS belum dikonfigurasi. Hubungi administrator aplikasi.');
        }

        $state = Str::random(64);
        $codeVerifier = Str::random(96);

        $request->session()->put([
            'bps_sso_state' => $state,
            'bps_sso_code_verifier' => $codeVerifier,
        ]);

        $query = [
            'response_type' => 'code',
            'client_id' => config('services.bps_sso.client_id'),
            'redirect_uri' => config('services.bps_sso.redirect'),
            'scope' => config('services.bps_sso.scopes'),
            'state' => $state,
        ];

        if (config('services.bps_sso.use_pkce')) {
            $query['code_challenge'] = rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '=');
            $query['code_challenge_method'] = 'S256';
        }

        $separator = str_contains(config('services.bps_sso.authorize_url'), '?') ? '&' : '?';

        return redirect(config('services.bps_sso.authorize_url').$separator.http_build_query($query, '', '&', PHP_QUERY_RFC3986));
    }

    public function callback(Request $request)
    {
        if ($request->filled('error')) {
            Log::warning('BPS SSO authorization was denied.', [
                'error' => $request->string('error')->toString(),
            ]);

            return $this->backToLogin('Masuk dengan SSO BPS dibatalkan atau ditolak.');
        }

        $expectedState = $request->session()->pull('bps_sso_state');
        $codeVerifier = $request->session()->pull('bps_sso_code_verifier');
        $state = $request->string('state')->toString();
        $code = $request->string('code')->toString();

        if (blank($expectedState) || blank($state) || ! hash_equals($expectedState, $state)) {
            Log::warning('BPS SSO callback received with an invalid state.');

            return $this->backToLogin('Sesi masuk SSO BPS tidak valid. Silakan coba lagi.');
        }

        if (blank($code)) {
            return $this->backToLogin('Kode otorisasi SSO BPS tidak ditemukan.');
        }

        if (! $this->isConfigured()) {
            return $this->backToLogin('SSO BPS belum dikonfigurasi. Hubungi administrator aplikasi.');
        }

        try {
            $tokenResponse = $this->requestAccessToken($code, $codeVerifier);
        } catch (ConnectionException $exception) {
            Log::warning('Could not contact BPS SSO token endpoint.', ['exception' => $exception->getMessage()]);

            return $this->backToLogin('Layanan SSO BPS sedang tidak dapat dihubungi. Silakan coba lagi.');
        }

        if (! $tokenResponse->successful() || blank($tokenResponse->json('access_token'))) {
            Log::warning('BPS SSO token exchange failed.', ['status' => $tokenResponse->status()]);

            return $this->backToLogin('SSO BPS tidak dapat memverifikasi akun Anda. Silakan coba lagi.');
        }

        try {
            $profileResponse = $this->ssoHttpClient()
                ->withToken($tokenResponse->json('access_token'))
                ->get(config('services.bps_sso.userinfo_url'));
        } catch (ConnectionException $exception) {
            Log::warning('Could not contact BPS SSO userinfo endpoint.', ['exception' => $exception->getMessage()]);

            return $this->backToLogin('Profil SSO BPS tidak dapat dimuat. Silakan coba lagi.');
        }

        if (! $profileResponse->successful() || ! is_array($profileResponse->json())) {
            Log::warning('BPS SSO userinfo request failed.', ['status' => $profileResponse->status()]);

            return $this->backToLogin('Profil SSO BPS tidak dapat diverifikasi. Silakan coba lagi.');
        }

        $profile = $this->profileFromResponse($profileResponse->json());
        $nip = $this->nipFromProfile($profile);
        $email = $this->emailFromProfile($profile);

        if (blank($nip) && blank($email)) {
            Log::warning('BPS SSO profile did not include a configured NIP or email claim.');

            return $this->backToLogin('NIP atau email tidak ditemukan pada profil SSO BPS Anda.');
        }

        $user = $this->findLocalUser($nip, $email);

        if (! $user) {
            Log::notice('Authenticated BPS SSO user was not found in the local directory.');

            return $this->backToLogin('Akun SSO BPS Anda belum terdaftar pada Portal Internal BPS Provinsi Jambi.');
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect()->route('portal');
    }

    private function requestAccessToken(string $code, ?string $codeVerifier)
    {
        $payload = [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => config('services.bps_sso.redirect'),
        ];

        if (config('services.bps_sso.use_pkce') && filled($codeVerifier)) {
            $payload['code_verifier'] = $codeVerifier;
        }

        $request = $this->ssoHttpClient()->asForm();
        if (config('services.bps_sso.token_auth_method') === 'client_secret_basic') {
            $request = $request->withBasicAuth(
                config('services.bps_sso.client_id'),
                config('services.bps_sso.client_secret'),
            );
        } else {
            $payload['client_id'] = config('services.bps_sso.client_id');
            $payload['client_secret'] = config('services.bps_sso.client_secret');
        }

        return $request->post(config('services.bps_sso.token_url'), $payload);
    }

    private function ssoHttpClient()
    {
        $caBundle = config('services.bps_sso.ca_bundle');
        $verify = filled($caBundle) ? $caBundle : config('services.bps_sso.verify_ssl');

        return Http::acceptJson()
            ->timeout(10)
            ->withOptions(['verify' => $verify]);
    }

    private function profileFromResponse(array $profile): array
    {
        $profilePath = config('services.bps_sso.profile_path');
        if (filled($profilePath)) {
            $profile = data_get($profile, $profilePath, []);
        }

        return is_array($profile) ? $profile : [];
    }

    private function nipFromProfile(array $profile): ?string
    {
        foreach (config('services.bps_sso.nip_claims', []) as $claim) {
            $value = data_get($profile, $claim);
            if (is_scalar($value) && filled((string) $value)) {
                return trim((string) $value);
            }
        }

        return null;
    }

    private function emailFromProfile(array $profile): ?string
    {
        foreach (config('services.bps_sso.email_claims', []) as $claim) {
            $value = data_get($profile, $claim);
            if (is_scalar($value) && filter_var(trim((string) $value), FILTER_VALIDATE_EMAIL)) {
                return trim((string) $value);
            }
        }

        return null;
    }

    private function findLocalUser(?string $nip, ?string $email): ?User
    {
        return User::query()
            ->where(function ($query) use ($nip, $email) {
                if (filled($nip)) {
                    $query->where('nip_baru', $nip)
                        ->orWhere('nip_lama', $nip);
                }

                if (filled($email)) {
                    $query->orWhere('email', $email);
                }
            })
            ->first();
    }

    private function isConfigured(): bool
    {
        return collect([
            config('services.bps_sso.client_id'),
            config('services.bps_sso.client_secret'),
            config('services.bps_sso.authorize_url'),
            config('services.bps_sso.token_url'),
            config('services.bps_sso.userinfo_url'),
            config('services.bps_sso.redirect'),
        ])->every(fn ($value) => filled($value));
    }

    private function backToLogin(string $message)
    {
        return redirect()->route('login')->with('login_error', $message);
    }
}
