<?php
// api.php - Backend Proxy for Heimdall UI

// Allow CORS for development
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Enable error reporting to catch fatal errors
ini_set('display_errors', 0); // Don't output raw text
error_reporting(E_ALL);
set_error_handler(function($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        http_response_code(500);
        echo json_encode(["error" => "Fatal PHP Error: " . $error['message'] . " in " . $error['file'] . " on line " . $error['line']]);
        exit();
    }
});

// Function to find and parse .env file robustly
function loadEnv() {
    $paths = [__DIR__ . '/../.env', __DIR__ . '/.env'];
    foreach ($paths as $path) {
        if (file_exists($path)) {
            // Use file() instead of parse_ini_file in case it's disabled by host
            $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if ($lines !== false) {
                $env = [];
                foreach ($lines as $line) {
                    if (strpos(trim($line), '#') === 0) continue;
                    $parts = explode('=', $line, 2);
                    if (count($parts) === 2) {
                        $env[trim($parts[0])] = trim($parts[1]);
                    }
                }
                return $env;
            }
        }
    }
    return false;
}

try {
    $env = loadEnv();
if (!$env) {
    http_response_code(500);
    echo json_encode(["error" => "Configuration (.env) not found"]);
    exit();
}

$homey_url = $env['HOMEY_BASE_URL'] ?? '';
$homey_token = $env['HOMEY_API_KEY'] ?? '';
$allowed_pins_str = $env['ALLOWED_PINS'] ?? '';

if (!$homey_url || !$homey_token) {
    http_response_code(500);
    echo json_encode(["error" => "Homey configuration missing"]);
    exit();
}

// Helper to make Homey API requests without cURL
function homeyRequest($method, $path, $body = null) {
    global $homey_url, $homey_token;
    
    $url = rtrim($homey_url, '/') . '/api/manager/devices' . $path;
    
    $options = [
        'http' => [
            'header'  => "Authorization: Bearer {$homey_token}\r\nContent-Type: application/json\r\n",
            'method'  => $method,
            'ignore_errors' => true // Don't throw PHP warnings on 400/500 responses
        ],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false
        ]
    ];
    
    if ($body !== null) {
        $options['http']['content'] = json_encode($body);
    }
    
    $context  = stream_context_create($options);
    $response = file_get_contents($url, false, $context);
    
    $httpCode = 500;
    if (isset($http_response_header) && count($http_response_header) > 0) {
        preg_match('#HTTP/[0-9\.]+\s+([0-9]+)#', $http_response_header[0], $matches);
        if (isset($matches[1])) {
            $httpCode = (int)$matches[1];
        }
    }
    
    return ['code' => $httpCode, 'data' => json_decode($response, true)];
}

// Helper to get Heimdall Device ID
function getHeimdallDeviceId() {
    $res = homeyRequest('GET', '/device');
    if ($res['code'] !== 200 || !is_array($res['data'])) {
        return null;
    }
    
    foreach ($res['data'] as $id => $device) {
        if (($device['driverId'] ?? '') === 'surveillancemodeswitch' || 
            stripos($device['name'] ?? '', 'surveillance') !== false) {
            return $id;
        }
    }
    return null;
}

$action = $_GET['action'] ?? '';

if ($action === 'status') {
    $deviceId = getHeimdallDeviceId();
    if (!$deviceId) {
        http_response_code(404);
        echo json_encode(["error" => "Heimdall device not found"]);
        exit();
    }
    
    $res = homeyRequest('GET', "/device/{$deviceId}");
    $state = $res['data']['capabilitiesObj']['homealarm_state']['value'] ?? null;
    
    if ($state) {
        echo json_encode(["status" => $state]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Could not retrieve status"]);
    }
    exit();
}

if ($action === 'set_mode') {
    $input = json_decode(file_get_contents('php://input'), true);
    $mode = $input['mode'] ?? '';
    $pin = $input['pin'] ?? '';
    
    if (!in_array($mode, ['armed', 'partially_armed', 'disarmed'])) {
        http_response_code(400);
        echo json_encode(["error" => "Invalid mode"]);
        exit();
    }
    
    // Validate PIN if disarming
    if ($mode === 'disarmed') {
        $allowed_pins = array_map('trim', explode(',', $allowed_pins_str));
        if (!in_array((string)$pin, $allowed_pins, true)) {
            http_response_code(401);
            echo json_encode(["error" => "FOUTIEVE CODE"]);
            exit();
        }
    }
    
    $deviceId = getHeimdallDeviceId();
    if (!$deviceId) {
        http_response_code(404);
        echo json_encode(["error" => "Heimdall device not found"]);
        exit();
    }
    
    $res = homeyRequest('PUT', "/device/{$deviceId}/capability/homealarm_state", ["value" => $mode]);
    
    if ($res['code'] >= 200 && $res['code'] < 300) {
        echo json_encode(["success" => true, "status" => $mode]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Failed to update Homey"]);
    }
    exit();
}

http_response_code(400);
echo json_encode(["error" => "Invalid action"]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(["error" => "Exception: " . $e->getMessage()]);
    exit();
}
