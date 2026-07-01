<?php
// Load Environment Variables from .env file
function loadEnv($path) {
    if (!file_exists($path)) {
        return;
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0) {
            continue;
        }
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $key = trim($parts[0]);
            $val = trim($parts[1]);
            // Strip wrapping quotes
            if (preg_match('/^["\'](.*)["\']$/', $val, $matches)) {
                $val = $matches[1];
            }
            if (getenv($key) === false) {
                putenv("$key=$val");
            }
            if (!isset($_ENV[$key])) {
                $_ENV[$key] = $val;
            }
            if (!isset($_SERVER[$key])) {
                $_SERVER[$key] = $val;
            }
        }
    }
}

// Load env file from project root
loadEnv(__DIR__ . '/../.env');

// Database Constants
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root'); 
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');    
define('DB_NAME', getenv('DB_NAME') ?: 'curtiss_erp'); 

// App Root URL - Dynamically determined for local dev (XAMPP) & Plesk production
if (isset($_SERVER['HTTP_HOST'])) {
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
    $script = $_SERVER['SCRIPT_NAME'];
    $dir = str_replace('\\', '/', dirname($script));
    if ($dir === '/') {
        $dir = '';
    }
    define('APP_URL', $protocol . '://' . $_SERVER['HTTP_HOST'] . $dir);
} else {
    define('APP_URL', 'https://curtiss.suzxlabs.com');
}

// Site Name
define('APP_NAME', 'CURTISS ERP');

// Brevo API Configuration
define('BREVO_API_KEY', getenv('BREVO_API_KEY') ?: '');

$GLOBALS['image_debug_logs'] = [];

// Clean and format product image URL from ERP
function getProductImageUrl($path) {
    if (empty($path)) {
        return '';
    }
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    if (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false) {
        $erpBase = $scheme . '://' . $host . '/Curtiss-ERP/';
    } else {
        $erpBase = 'https://curtiss.suzxlabs.com/';
    }
    
    $path = ltrim($path, '/');
    if (strpos($path, 'public/') === 0) {
        $path = substr($path, 7);
    }
    
    if (strpos($path, 'uploads/') === 0) {
        $url = $erpBase . $path;
    } else {
        $url = $erpBase . 'uploads/products/' . $path;
    }
    
    $GLOBALS['image_debug_logs'][] = [
        'type' => 'product',
        'input' => $path,
        'resolved' => $url
    ];
    return $url;
}

// Clean and format banner image URL from ERP
function getBannerImageUrl($path) {
    if (empty($path)) {
        return '';
    }
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    if (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false) {
        $erpBase = $scheme . '://' . $host . '/Curtiss-ERP/';
    } else {
        $erpBase = 'https://curtiss.suzxlabs.com/';
    }
    
    $path = ltrim($path, '/');
    if (strpos($path, 'public/') === 0) {
        $path = substr($path, 7);
    }
    
    if (strpos($path, 'uploads/') === 0) {
        $url = $erpBase . $path;
    } else {
        $url = $erpBase . 'uploads/banners/' . $path;
    }
    
    $GLOBALS['image_debug_logs'][] = [
        'type' => 'banner',
        'input' => $path,
        'resolved' => $url
    ];
    return $url;
}

