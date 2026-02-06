<?php
// Load environment variables
$_ENV = parse_ini_file('.env');

// Set content type
header('Content-Type: application/json');

// Get request path
$requestUri = $_SERVER['REQUEST_URI'];
$scriptName = $_SERVER['SCRIPT_NAME'];
$path = parse_url($requestUri, PHP_URL_PATH);

// Remove script name from path if present
if (strpos($path, basename($scriptName)) !== false) {
    $path = substr($path, 0, strpos($path, basename($scriptName)));
}

// Route handling based on path
$pathParts = explode('/', trim($path, '/'));
$lastPart = end($pathParts);

// Root endpoint - return server time
if ($path === '/' || $path === '' || $lastPart === basename(__DIR__)) {
    echo json_encode([
        'server_time' => date('Y-m-d H:i:s'),
        'timestamp' => time(),
        'timezone' => date_default_timezone_get()
    ]);
    exit;
}

switch ($lastPart) {
    case 'health':
        GetHealth();
        break;
    case 'parse':
        GetParse();
        break;
    default: {
            http_response_code(404);
        };
        break;
}

function GetHealth(): void
{
    echo json_encode(['status' => 'ok']);
    exit;
}

function GetParse(): void
{
    // Get auth token from environment
    $authToken = $_ENV['AUTH_TOKEN'] ?? null;

    // Check for auth token in header or query param
    $providedToken = $_SERVER['HTTP_AUTHORIZATION'] ?? $_GET['token'] ?? null;

    if (!$authToken || !$providedToken || $providedToken !== $authToken) {
        // Log unauthorized request to Apache error log
        $clientIp = $_SERVER['HTTP_X_FORWARDED_FOR']
            ?? $_SERVER['REMOTE_ADDR']
            ?? 'unknown';
        $requestUri = $_SERVER['REQUEST_URI'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

        $logMessage = "client denied by server configuration: Authentication token mismatch - " .
            "IP: {$clientIp}, URI: {$requestUri}, User-Agent: {$userAgent}";

        error_log($logMessage);

        http_response_code(404);
        exit;
    }

    // Get address from query parameter
    $address = $_GET['address'] ?? null;

    if (!$address) {
        http_response_code(400);
        echo json_encode(['error' => 'Address parameter is required']);
        exit;
    }

    try {
        // Parse the address using Postal\Parser
        $parsedAddress = Postal\Parser::parse_address($address);
        echo json_encode($parsedAddress);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'error' => 'Failed to parse address: ' . $e->getMessage()
        ]);
    }
}
