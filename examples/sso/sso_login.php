<?php
/**
 * Contoh Integrasi SSO BPS (OpenID Connect / Authorization Code Flow) - PHP
 * 
 * Skenario:
 * 1. Pengguna membuka aplikasi -> Di-redirect ke Keycloak SSO Login page
 * 2. Setelah berhasil login -> Keycloak meredirect kembali ke callback_uri dengan parameter `code`
 * 3. Aplikasi menukarkan `code` dengan `access_token`, `id_token`, dan mengambil profil pengguna via `/userinfo`
 */

session_start();

require_once __DIR__ . '/env.php';
load_env(__DIR__ . '/../../.env');

// Konfigurasi SSO & Credential (dibaca dari file .env di root project)
$keycloak_url   = env('KEYCLOAK_URL', 'http://localhost:8082');
$realm          = env('REALM', 'pegawai');
$client_id      = env('CLIENT_ID', 'YOUR_CLIENT_ID');
$client_secret  = env('CLIENT_SECRET', 'YOUR_CLIENT_SECRET');
$redirect_uri   = env('REDIRECT_URI', 'http://localhost:8000/sso_login.php');

$base_realm_url    = rtrim($keycloak_url, '/') . "/realms/{$realm}/protocol/openid-connect";
$auth_endpoint     = "{$base_realm_url}/auth";
$token_endpoint    = "{$base_realm_url}/token";
$userinfo_endpoint = "{$base_realm_url}/userinfo";
$logout_endpoint   = "{$base_realm_url}/logout";

// -----------------------------------------------------------------------------
// Action: Logout
// -----------------------------------------------------------------------------
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    $id_token = $_SESSION['id_token'] ?? '';
    session_destroy();
    
    // Redirect ke Keycloak Logout URL (OIDC RP-Initiated Logout)
    $logout_url = $logout_endpoint . '?post_logout_redirect_uri=' . urlencode($redirect_uri);
    if ($id_token) {
        $logout_url .= '&id_token_hint=' . urlencode($id_token);
    }
    header('Location: ' . $logout_url);
    exit;
}

// -----------------------------------------------------------------------------
// Step 2: Menerima Authorization Code & Tukar dengan Access Token
// -----------------------------------------------------------------------------
if (isset($_GET['code'])) {
    // Validasi state: harus sama dengan nilai yang disimpan di sesi saat Step 1.
    // Mencegah login CSRF (callback berisi code milik orang lain disuntikkan ke browser pengguna).
    $expected_state = $_SESSION['oauth_state'] ?? '';
    unset($_SESSION['oauth_state']); // sekali pakai
    if ($expected_state === '' || !hash_equals($expected_state, $_GET['state'] ?? '')) {
        http_response_code(400);
        die('State tidak valid. Silakan ulangi login.');
    }

    $code = $_GET['code'];

    // Body parameter untuk token request
    $params = array(
        'grant_type'   => 'authorization_code',
        'client_id'    => $client_id,
        'client_secret' => $client_secret,
        'code'         => $code,
        'redirect_uri' => $redirect_uri
    );

    $ch = curl_init($token_endpoint);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    curl_close($ch);

    $token_data = json_decode($response, true);

    if (isset($token_data['access_token'])) {
        $_SESSION['access_token'] = $token_data['access_token'];
        $_SESSION['id_token']     = $token_data['id_token'] ?? '';

        // Ambil UserInfo dari Keycloak
        $ch = curl_init($userinfo_endpoint);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: Bearer ' . $_SESSION['access_token']
        ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $userinfo_response = curl_exec($ch);
        curl_close($ch);

        $_SESSION['user'] = json_decode($userinfo_response, true);

        // Redirect balik agar URL bersih dari query parameter ?code=...
        header('Location: ' . $redirect_uri);
        exit;
    } else {
        die('Gagal mendapatkan token: ' . htmlspecialchars($response));
    }
}

// -----------------------------------------------------------------------------
// Step 1: Inisiasi Login SSO (Redirect ke Keycloak Login Page)
// -----------------------------------------------------------------------------
if (isset($_GET['action']) && $_GET['action'] === 'login') {
    // State acak per permintaan login, disimpan di sesi untuk dicocokkan saat callback
    $_SESSION['oauth_state'] = bin2hex(random_bytes(16));

    $login_url = $auth_endpoint . '?' . http_build_query(array(
        'response_type' => 'code',
        'client_id'     => $client_id,
        'redirect_uri'  => $redirect_uri,
        'scope'         => 'openid profile email',
        'state'         => $_SESSION['oauth_state']
    ));
    header('Location: ' . $login_url);
    exit;
}

// -----------------------------------------------------------------------------
// Halaman Tampilan Sederhana
// -----------------------------------------------------------------------------
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Contoh Integrasi SSO BPS - PHP</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background-color: #f4f6f8; color: #333; }
        .card { background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); max-width: 600px; }
        .btn { display: inline-block; padding: 10px 20px; color: #fff; background: #0284c7; text-decoration: none; border-radius: 5px; font-weight: bold; }
        .btn-logout { background: #ef4444; }
        pre { background: #1e293b; color: #f8fafc; padding: 15px; border-radius: 6px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="card">
        <h2>🛡️ Contoh Integrasi SSO BPS (PHP)</h2>
        <?php if (isset($_SESSION['user'])): ?>
            <p>Selamat datang, <strong><?= htmlspecialchars($_SESSION['user']['name'] ?? $_SESSION['user']['preferred_username'] ?? 'Pengguna') ?></strong>!</p>
            <h3>Profil Pengguna (UserInfo):</h3>
            <pre><?= json_encode($_SESSION['user'], JSON_PRETTY_PRINT) ?></pre>
            <p><a href="?action=logout" class="btn btn-logout">Logout dari SSO BPS</a></p>
        <?php else: ?>
            <p>Anda belum login.</p>
            <p><a href="?action=login" class="btn">Login dengan SSO BPS</a></p>
        <?php endif; ?>
    </div>
</body>
</html>
