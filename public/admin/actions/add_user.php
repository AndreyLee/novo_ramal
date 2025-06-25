<?php
// public/admin/actions/add_user.php
header('Content-Type: application/json');
require_once __DIR__ . '/../../src/includes/db.php';
require_once __DIR__ . '/../../src/includes/session_auth.php'; // For require_login

require_login(['Super-Admin']); // Only Super-Admins can add users

$response = ['success' => false, 'message' => 'Invalid request. Only POST method is allowed for Super-Admins.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? ''; // Do not trim password, spaces can be part of it
    $profile = trim($_POST['profile'] ?? '');

    // --- Input Validations ---
    if (empty($username)) {
        $response['message'] = 'Username cannot be empty.';
        echo json_encode($response); exit;
    }
    // Add more username validation if needed (e.g., length, characters)
    // Example: if (strlen($username) < 3 || strlen($username) > 50) { ... }
    // Example: if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) { ... }


    if (empty($password)) {
        $response['message'] = 'Password cannot be empty.';
        echo json_encode($response); exit;
    }
    // Basic password length check (frontend should also guide this)
    if (strlen($password) < 8) {
        $response['message'] = 'Password must be at least 8 characters long.';
        echo json_encode($response); exit;
    }
    // Consider adding more password complexity rules if required by policy

    if (!in_array($profile, ['Admin', 'Super-Admin'], true)) { // Strict comparison
        $response['message'] = 'Invalid profile type. Must be "Admin" or "Super-Admin".';
        echo json_encode($response); exit;
    }

    try {
        // Check if username already exists (case-sensitive by default in most MySQL collations)
        $checkUserStmt = $pdo->prepare("SELECT id FROM users WHERE username = :username");
        $checkUserStmt->bindParam(':username', $username, PDO::PARAM_STR);
        $checkUserStmt->execute();
        if ($checkUserStmt->fetchColumn()) {
            $response['message'] = 'Username "' . htmlspecialchars($username) . '" already exists. Please choose a different username.';
            echo json_encode($response); exit;
        }

        // Hash the password
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        if ($password_hash === false) {
            // This should rarely happen with PASSWORD_DEFAULT unless there's a system configuration issue with PHP's crypt().
            $response['message'] = 'Failed to hash password due to a system error. Please contact support.';
            error_log("Password hashing failed for username: " . $username . ". PHP version: " . PHP_VERSION);
            echo json_encode($response); exit;
        }

        // Insert the new user
        $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, profile) VALUES (:username, :password_hash, :profile)");
        $stmt->bindParam(':username', $username, PDO::PARAM_STR);
        $stmt->bindParam(':password_hash', $password_hash, PDO::PARAM_STR);
        $stmt->bindParam(':profile', $profile, PDO::PARAM_STR);

        $stmt->execute(); // Will throw PDOException on error

        if ($stmt->rowCount() > 0) {
            $response['success'] = true;
            $response['message'] = 'User ' . htmlspecialchars($username) . ' added successfully with profile ' . htmlspecialchars($profile) . '.';
            $response['user_id'] = $pdo->lastInsertId(); // Get ID of the newly added user
        } else {
            // Should not be reached if execute() is successful and PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            $response['message'] = 'Failed to add user. No rows were affected.';
        }

    } catch (PDOException $e) {
        error_log("Super-Admin Add User PDOError: " . $e->getMessage() . " SQLSTATE: " . $e->getCode());
        // The 'username' column in 'users' has a UNIQUE constraint.
        if ($e->errorInfo[1] == 1062) { // MySQL ER_DUP_ENTRY
            // This is a fallback; the explicit check above should catch it first.
            $response['message'] = 'Username "' . htmlspecialchars($username) . '" already exists (database unique constraint violated).';
        } else {
            $response['message'] = 'Database error while adding user. Please check server logs. SQLSTATE[' . $e->getCode() . ']';
        }
        // http_response_code(500); // Or 409 for conflict
    } catch (Exception $e) {
        error_log("Super-Admin Add User General Error: " . $e->getMessage());
        $response['message'] = 'An unexpected error occurred while adding the user. Please check server logs.';
        // http_response_code(500);
    }
}

echo json_encode($response);
?>
