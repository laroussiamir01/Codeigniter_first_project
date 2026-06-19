#!/usr/bin/env php
<?php
/**
 * Simple Redis Connection Test
 * Run: php test_redis.php
 */

$host = getenv('REDIS_HOST') ?: 'localhost';
$port = getenv('REDIS_PORT') ?: 6379;
$password = getenv('REDIS_PASSWORD') ?: '';

echo "Testing Redis Connection\n";
echo "========================\n";
echo "Host: $host\n";
echo "Port: $port\n";
echo "Password: " . (!empty($password) ? '***SET***' : 'NOT SET') . "\n\n";

try {
    echo "Attempting to connect...\n";
    $socket = @fsockopen($host, $port, $errno, $errstr, 5);
    
    if (!$socket) {
        throw new Exception("Connection failed: $errstr ($errno)");
    }
    
    echo "✓ Connected to Redis!\n";
    
    // Authenticate if password is set
    if (!empty($password)) {
        echo "Authenticating with password...\n";
        $auth_cmd = '*2' . "\r\n" . '$4' . "\r\n" . 'AUTH' . "\r\n" . '$' . strlen($password) . "\r\n" . $password . "\r\n";
        fwrite($socket, $auth_cmd);
        $response = fgets($socket, 512);
        
        if (strpos($response, '+OK') !== FALSE) {
            echo "✓ Authentication successful!\n";
        } else {
            echo "✗ Authentication failed: $response\n";
            fclose($socket);
            exit(1);
        }
    }
    
    // Send PING command
    echo "Sending PING command...\n";
    $ping_cmd = '*1' . "\r\n" . '$4' . "\r\n" . 'PING' . "\r\n";
    fwrite($socket, $ping_cmd);
    $response = fgets($socket, 512);
    
    if (strpos($response, 'PONG') !== FALSE) {
        echo "✓ PING successful!\n";
    } else {
        echo "✗ PING failed: $response\n";
    }
    
    fclose($socket);
    
    echo "\n✓ Redis connection is working!\n";
    echo "You can now run the queue worker:\n";
    echo "  php application/commands/queue_worker.php\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "\nMake sure:\n";
    echo "1. Redis server is running\n";
    echo "2. REDIS_HOST, REDIS_PORT, and REDIS_PASSWORD are set correctly\n";
    echo "3. Your firewall allows connection to the Redis server\n";
    exit(1);
}
?>
