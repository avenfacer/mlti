<?php
/**
 * Contoh Penggunaan API Pegawai BPS - PHP (cURL)
 * Kriteria pencarian: Email
 */

require_once __DIR__ . '/env.php';
load_env(__DIR__ . '/../../.env');

// Konfigurasi SSO & Credential (dibaca dari file .env di root project)
$keycloak_url   = env('KEYCLOAK_URL', 'http://localhost:8082');
$realm          = env('REALM', 'pegawai');
$client_id      = env('CLIENT_ID', 'YOUR_CLIENT_ID');
$client_secret  = env('CLIENT_SECRET', 'YOUR_CLIENT_SECRET');

$url_base       = rtrim($keycloak_url, '/') . "/realms/{$realm}/";
$url_token      = $url_base . 'protocol/openid-connect/token';
$url_api        = $url_base . 'api';

// Contoh Request: GET /realms/pegawai/api/email/jane.smith@bps.go.id
$query_search   = '/email/fahmialazhar@bps.go.id';

try {
    // ----------------------------------------------------------------------
    // Tahap 1: Mendapatkan Access Token
    // ----------------------------------------------------------------------
    $ch = curl_init($url_token);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
    curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");
    curl_setopt($ch, CURLOPT_USERPWD, $client_id . ":" . $client_secret);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response_token = curl_exec($ch);
    if (curl_errno($ch)) {
        throw new Exception(curl_error($ch));
    }
    curl_close($ch);

    $json_token = json_decode($response_token, true);
    if (!isset($json_token['access_token'])) {
        die("Gagal mengambil token: " . $response_token . "\n <br>");
    }

    $access_token = $json_token['access_token'];
    echo "=== Token Berhasil Didapatkan ===\n <br>";

    // ----------------------------------------------------------------------
    // Tahap 2: Mendapatkan Data Pegawai
    // ----------------------------------------------------------------------
    $ch = curl_init($url_api . $query_search);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Accept: application/json',
        'Authorization: Bearer ' . $access_token
    ));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response_api = curl_exec($ch);
    if (curl_errno($ch)) {
        throw new Exception(curl_error($ch));
    }
    curl_close($ch);

    $results = json_decode($response_api, true);
    // 401 = token tidak valid; 403 = token bukan token client (service account)
    if (!is_array($results) || isset($results['error'])) {
        die("API Error: " . $response_api . "\n <br>");
    }

    echo "=== Hasil Pencarian ($query_search) ===\n <br>";
    $i = 1;
    foreach ($results as $user) {
        echo "\n$i. Username: " . $user['username'] . "\n <br>";
        echo "   Attributes:\n";
        if (isset($user['attributes'])) {
            foreach ($user['attributes'] as $key => $values) {
                echo "   - $key: " . implode(', ', $values) . "\n <br>";
            }
        }
        $i++;
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
