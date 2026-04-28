<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'stage_crhd');
define('DB_USER', 'stage_crhd');
define('DB_PASS', 'crhd_pass!.');
define('DB_CHARSET', 'utf8mb4');
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('UPLOAD_URL', '/uploads/');

if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);

// CORS — always first
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

// Cache raw input once — php://input can only be read once
$_RAW_INPUT = null;
function rawInput() {
    global $_RAW_INPUT;
    if ($_RAW_INPUT === null) $_RAW_INPUT = file_get_contents('php://input');
    return $_RAW_INPUT;
}
function getInput() {
    $data = rawInput() ? (json_decode(rawInput(), true) ?? []) : [];
    unset($data['_token']); // remove auth token from data
    return $data;
}

// Get token from ANY source
function getToken() {
    // 1. Query string — most reliable through Apache
    if (!empty($_GET['token'])) return trim($_GET['token']);
    // 2. Request body _token field
    $raw = rawInput();
    if ($raw) {
        $d = json_decode($raw, true);
        if (!empty($d['_token'])) return trim($d['_token']);
    }
    // 3. Authorization header (may be stripped by Apache)
    foreach (['HTTP_AUTHORIZATION','REDIRECT_HTTP_AUTHORIZATION'] as $k) {
        if (!empty($_SERVER[$k])) return trim(str_replace('Bearer ','',$_SERVER[$k]));
    }
    if (function_exists('getallheaders')) {
        foreach (getallheaders() as $k => $v) {
            if (strtolower($k) === 'authorization') return trim(str_replace('Bearer ','',$v));
        }
    }
    // 4. Cookie
    return $_COOKIE['crhd_token'] ?? '';
}

function requireAuth() {
    $token = getToken();
    if (!$token) jsonResponse(['error'=>'No token provided'],401);
    $data = json_decode(base64_decode($token), true);
    if (!$data || empty($data['exp']) || $data['exp'] < time()) {
        jsonResponse(['error'=>'Token expired or invalid'],401);
    }
}

function generateToken($uid) {
    return base64_encode(json_encode(['uid'=>(int)$uid,'exp'=>time()+86400*30]));
}

function getDB() {
    static $pdo = null;
    if ($pdo) return $pdo;
    $dsn = 'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset='.DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
    ]);
    return $pdo;
}

function jsonResponse($data, $code=200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}
