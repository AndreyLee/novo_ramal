<?php
// public/admin/handle_login.php
require_once __DIR__ . '/../src/includes/db.php'; // Provides $pdo
require_once __DIR__ . '/../src/includes/session_auth.php'; // Handles session_start() and auth functions

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Redirect to login page if not a POST request
    header('Location: login.php');
    exit;
}

// Get username and password from POST request
// trim() is used for username, but not typically for password as spaces can be intentional.
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? ''; // Password should not be trimmed typically

if (empty($username) || empty($password)) {
    header('Location: login.php?error=missing_fields');
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, username, password_hash, profile FROM users WHERE username = :username");
    // bindParam is good practice, alternatively, pass an array to execute().
    $stmt->bindParam(':username', $username, PDO::PARAM_STR);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password_hash'])) {

        // Regenerate session ID upon successful login for security (prevents session fixation)
        session_regenerate_id(true);

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['user_profile'] = $user['profile'];

        // Redirect to the admin dashboard or main admin page
        header('Location: index.php');
        exit;
    } else {
        // Invalid credentials
        // Optionally, you could log failed attempts here, but be mindful of log verbosity.
        // error_log("Failed login attempt for username: " . $username);
        header('Location: login.php?error=invalid_credentials');
        exit;
    }
} catch (PDOException $e) {
    // Log the database error (should be configured to log to a file in production)
    error_log("Login PDOException: " . $e->getMessage()); // Reverted to more generic, or could be "Login PDOException for username '{$username}': "
    // Redirect to login page with a generic database error
    header('Location: login.php?error=db_error');
    exit;
}
?>
