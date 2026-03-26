<?php

/**
 * Laravel - A PHP Framework For Web Artisans
 *
 * @package  Laravel
 * @author   Taylor Otwell <taylor@laravel.com>
 */

$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? ''
);

// This file allows us to emulate Apache's "mod_rewrite" functionality from the
// built-in PHP web server. This provides a convenient way to test a Laravel
// application without having installed a "real" web server software here.
if ($uri !== '/' && file_exists(__DIR__.'/public'.$uri)) {
    // Block direct access to certificate template images.
    // They must be served via the authenticated /secure/template-image/ route
    // so that unauthenticated users cannot download certificate backgrounds.
    if (preg_match('#^/images/templates/(.+)$#', $uri, $matches)) {
        // Rewrite the URI so Laravel's router handles this with auth middleware
        $_SERVER['REQUEST_URI'] = '/secure/template-image/' . $matches[1];
        require_once __DIR__.'/public/index.php';
        return true;
    }
    return false;
}

require_once __DIR__.'/public/index.php';
