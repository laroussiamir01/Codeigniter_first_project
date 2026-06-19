#!/usr/bin/env php
<?php
/**
 * Upstash Redis REST API Connection Test
 * Run: php test_redis.php
 */

// Load environment variables from .env files
$env_files = [
    '.env.development.local',
    '.env.local',
    '.env'
];

foreach ($env_files as $file) {
    if (file_exists($file)) {
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            // Skip comments and empty lines
            if (empty($line) || $line[0] === '#') continue;
            // Skip lines without =
            if (strpos($line, '=') === false) continue;
            
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value, '\'" ');
            putenv($key . '=' . $value);
        }
        echo "[OK] Loaded environment from: $file\n\n";
        break;
    }
}

$rest_url = getenv('UPSTASH_REDIS_REST_URL') ?: '';
$rest_token = getenv('UPSTASH_REDIS_REST_TOKEN') ?: '';

echo "Testing Upstash Redis REST API\n";
echo "==============================\n";
echo "REST URL: $rest_url\n";
echo "Token: " . (!empty($rest_token) ? '***SET*** (' . strlen($rest_token) . ' chars)' : 'NOT SET') . "\n\n";

if (empty($rest_url) || empty($rest_token)) {
    echo "ERROR: Upstash credentials not configured\n";
    exit(1);
}

echo "Attempting to connect...\n\n";

// Test PING command
$cmd = ['PING'];
$payload = json_encode($cmd);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $rest_url);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $rest_token,
    'Content-Type: application/json',
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "Command: " . json_encode($cmd) . "\n";
echo "HTTP Status: $http_code\n";

if ($error) {
    echo "ERROR: $error\n\n";
    exit(1);
}

if ($http_code !== 200) {
    echo "ERROR: HTTP $http_code\n";
    echo "Response: $response\n\n";
    exit(1);
}

$result = json_decode($response, TRUE);
echo "Response: " . json_encode($result) . "\n\n";

echo "✓ Upstash Redis REST API connection is working!\n";
echo "\nYou can now:\n";
echo "1. Start the queue worker: php application/commands/queue_worker.php\n";
echo "2. Login to test 2FA with instant OTP verification page\n";
exit(0);
?>
