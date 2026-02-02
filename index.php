<?php
require_once 'vendor/autoload.php';

use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

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

// Check if path ends with /parse (handle subdirectories)
if (end(explode('/', $path)) != 'parse') {
    http_response_code(404);
    echo json_encode(['error' => 'Not found. Use /parse?address=your_address', 'path' => $path]);
    exit;
}

// Get auth token from environment
$authToken = $_ENV['AUTH_TOKEN'] ?? null;

// Check for auth token in header or query param
$providedToken = $_SERVER['HTTP_AUTHORIZATION'] ?? $_GET['token'] ?? null;

if (!$authToken || !$providedToken || $providedToken !== $authToken) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
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
    
    // Return the parsed data
    echo json_encode([
        'success' => true,
        'original_address' => $address,
        'parsed_data' => $parsedAddress
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to parse address: ' . $e->getMessage()
    ]);
}