<?php

/**
 * ┌─────────────────────────────────────────────────────────────────┐
 * │           DEPLOYMENT CONFIGURATION                             │
 * │  Edit this file ONCE to switch between local and live server.  │
 * └─────────────────────────────────────────────────────────────────┘
 *
 * This is the SINGLE file you need to change when moving between
 * XAMPP (localhost) and InfinityFree (or any other host).
 */

// ── DEBUG MODE ─────────────────────────────────────────────────────
// Set to TRUE  to show all PHP errors on screen (for debugging)
// Set to FALSE to hide errors (for production / live site)
define('DEBUG_MODE', true);

if (DEBUG_MODE) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
}

// ═══════════════════════════════════════════════════════════════════
// ██  DATABASE CREDENTIALS  ████████████████████████████████████████
// ═══════════════════════════════════════════════════════════════════
//
// Uncomment ONE block below. Comment out the other.
//

// ── OPTION A: LOCAL (XAMPP) ──────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'lost_found');

// ── OPTION B: INFINITYFREE (LIVE) ────────────────────────────────
// define('DB_HOST', 'sql200.infinityfree.com');       // ← Your MySQL host from InfinityFree panel
// define('DB_USER', 'if0_XXXXXXX');                   // ← Your MySQL username
// define('DB_PASS', 'your_password_here');             // ← Your MySQL password
// define('DB_NAME', 'if0_XXXXXXX_lost_found');         // ← Your MySQL database name


// ═══════════════════════════════════════════════════════════════════
// ██  SITE URL (AUTO-DETECTED)  ████████████████████████████████████
// ═══════════════════════════════════════════════════════════════════
//
// Auto-detects your site URL. If it doesn't work, hardcode it below.
//
// HARDCODE EXAMPLE (uncomment and edit):
// define('SITE_URL', 'https://yourdomain.infinityfree.com');
//
if (!defined('SITE_URL')) {
    $_dc_protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $_dc_host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $_dc_dir = str_replace('\\', '/', __DIR__);
    $_dc_docRoot = str_replace('\\', '/', rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/\\'));
    if ($_dc_docRoot !== '' && strpos($_dc_dir, $_dc_docRoot) === 0) {
        $_dc_basePath = substr($_dc_dir, strlen($_dc_docRoot));
    } else {
        $_dc_basePath = '';
    }
    define('SITE_URL', $_dc_protocol . '://' . $_dc_host . $_dc_basePath);
}


// ═══════════════════════════════════════════════════════════════════
// ██  PHP 7.4 POLYFILLS  ██████████████████████████████████████████
// ═══════════════════════════════════════════════════════════════════
// These functions were added in PHP 8.0. Polyfills ensure the app
// works on PHP 7.4 (common on shared hosting like InfinityFree).

if (!function_exists('str_starts_with')) {
    function str_starts_with(string $haystack, string $needle): bool
    {
        return strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}

if (!function_exists('str_contains')) {
    function str_contains(string $haystack, string $needle): bool
    {
        return $needle === '' || strpos($haystack, $needle) !== false;
    }
}

if (!function_exists('str_ends_with')) {
    function str_ends_with(string $haystack, string $needle): bool
    {
        return $needle === '' || substr($haystack, -strlen($needle)) === $needle;
    }
}
