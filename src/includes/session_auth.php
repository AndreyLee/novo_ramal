<?php
// src/includes/session_auth.php

// Determine if HTTPS is used - for the 'secure' flag
// Check standard HTTPS, then common reverse proxy headers
$is_https = false;
if (isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) === 'on') {
    $is_https = true;
} elseif (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') {
    $is_https = true;
} elseif (isset($_SERVER['HTTP_FRONT_END_HTTPS']) && strtolower($_SERVER['HTTP_FRONT_END_HTTPS']) === 'on') {
    // Some other less common proxy headers
    $is_https = true;
}


if (session_status() == PHP_SESSION_NONE) {
    // Set secure cookie parameters before starting the session
    // Note: $_SERVER['HTTP_HOST'] can be manipulated by user if not behind a trusted proxy setup.
    // For higher security, define the domain in a config file if possible.
    $cookie_domain = $_SERVER['HTTP_HOST'] ?? 'localhost'; // Fallback for CLI or weird setups

    session_set_cookie_params([
        'lifetime' => 0, // 0 = until browser is closed
        'path' => '/admin/', // Cookie restricted to /admin/ path for admin sessions
        // 'domain' => $cookie_domain,
        'secure' => false, // Explicitly set to false
        'httponly' => true, // Prevent JavaScript access to session cookie
        'samesite' => 'Lax' // CSRF protection: 'Lax' or 'Strict'. Lax is often more compatible.
    ]);
    session_start();
}

/**
 * Checks if a user is logged in.
 *
 * @return bool True if logged in, false otherwise.
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Requires the user to be logged in and have an allowed profile.
 * If not, redirects to the login page.
 *
 * @param array $allowed_profiles List of user profiles allowed to access the page.
 *                                Defaults to ['Admin', 'Super-Admin'].
 */
function require_login($allowed_profiles = ['Admin', 'Super-Admin']) {
    if (!is_logged_in()) {
        // Consider the base URL or a config for the redirect path
        header('Location: /admin/login.php?error=not_logged_in');
        exit;
    }

    $user_profile = $_SESSION['user_profile'] ?? null;
    if (empty($user_profile) || !in_array($user_profile, $allowed_profiles, true)) {
        // User is logged in but does not have the required profile
        header('Location: /admin/login.php?error=unauthorized');
        exit;
    }
}

/**
 * Gets the profile of the currently logged-in user.
 *
 * @return string|null The user's profile or null if not set.
 */
function get_user_profile() {
    return $_SESSION['user_profile'] ?? null;
}

/**
 * Gets the ID of the currently logged-in user.
 *
 * @return mixed|null The user's ID or null if not set.
 */
function get_user_id() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Logs out the current user by destroying the session and redirecting to login page.
 */
function logout_user() {
    // Unset all session variables
    $_SESSION = array();

    // If session cookies are used, delete the session cookie
    // This requires the same cookie parameters as when it was set.
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params(); // Get currently effective params
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    // Finally, destroy the session.
    if (session_status() == PHP_SESSION_ACTIVE) {
        session_destroy();
    }

    // Redirect to login page with a status message
    header('Location: /admin/login.php?status=logged_out');
    exit;
}

?>
