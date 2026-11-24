<?php

$keycloakUrl = rtrim((string) env('BPS_SSO_KEYCLOAK_URL', env('KEYCLOAK_URL', '')), '/');
$keycloakRealm = env('BPS_SSO_REALM', env('REALM', 'pegawai'));
$keycloakOidcBase = $keycloakUrl === ''
    ? null
    : $keycloakUrl."/realms/{$keycloakRealm}/protocol/openid-connect";

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'bps_sso' => [
        // Supports the variable names in config/sso/sso_login.php. BPS_SSO_*
        // overrides are available for apps sharing a single .env file.
        'keycloak_url' => $keycloakUrl,
        'realm' => $keycloakRealm,
        'client_id' => env('BPS_SSO_CLIENT_ID', env('CLIENT_ID')),
        'client_secret' => env('BPS_SSO_CLIENT_SECRET', env('CLIENT_SECRET')),
        'authorize_url' => env('BPS_SSO_AUTHORIZE_URL', $keycloakOidcBase ? "{$keycloakOidcBase}/auth" : null),
        'token_url' => env('BPS_SSO_TOKEN_URL', $keycloakOidcBase ? "{$keycloakOidcBase}/token" : null),
        'userinfo_url' => env('BPS_SSO_USERINFO_URL', $keycloakOidcBase ? "{$keycloakOidcBase}/userinfo" : null),
        'redirect' => env('BPS_SSO_REDIRECT_URI', env('REDIRECT_URI', rtrim(env('APP_URL', 'http://localhost'), '/').'/')),
        'scopes' => env('BPS_SSO_SCOPES', 'openid profile email'),
        'use_pkce' => env('BPS_SSO_USE_PKCE', false),
        'token_auth_method' => env('BPS_SSO_TOKEN_AUTH_METHOD', 'client_secret_post'),
        'ca_bundle' => env('BPS_SSO_CA_BUNDLE'),
        'verify_ssl' => env('BPS_SSO_VERIFY_SSL', true),
        'profile_path' => env('BPS_SSO_PROFILE_PATH'),
        'nip_claims' => array_filter(array_map('trim', explode(',', env('BPS_SSO_NIP_CLAIMS', 'nip_baru,nip,preferred_username,username')))),
        'email_claims' => array_filter(array_map('trim', explode(',', env('BPS_SSO_EMAIL_CLAIMS', 'email')))),
    ],

];
