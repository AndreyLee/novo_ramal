<?php
// public/admin/actions/change_own_password.php
header('Content-Type: application/json');
require_once __DIR__ . '/../../../src/includes/db.php'; // Ajustado o caminho para db.php
require_once __DIR__ . '/../../../src/includes/session_auth.php'; // Ajustado o caminho para session_auth.php

// Require login for any authenticated user (Admin or Super-Admin)
require_login(['Admin', 'Super-Admin']);

$response = ['success' => false, 'message' => 'Invalid request. Only POST method is allowed.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_user_id = get_user_id();
    if (!$current_user_id) {
        // Should not happen if require_login() is effective
        $response['message'] = 'User session not found or invalid.';
        echo json_encode($response);
        exit;
    }

    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_new_password = $_POST['confirm_new_password'] ?? '';

    // --- Input Validations ---
    if (empty($current_password) || empty($new_password) || empty($confirm_new_password)) {
        $response['message'] = 'All password fields are required.';
        echo json_encode($response);
        exit;
    }

    if (strlen($new_password) < 8) {
        $response['message'] = 'New password must be at least 8 characters long.';
        echo json_encode($response);
        exit;
    }

    if ($new_password !== $confirm_new_password) {
        $response['message'] = 'New password and confirmation password do not match.';
        echo json_encode($response);
        exit;
    }

    try {
        $pdo->beginTransaction();

        // Fetch current password hash for the logged-in user
        $stmt_get_current_hash = $pdo->prepare("SELECT password_hash FROM users WHERE id = :user_id FOR UPDATE");
        $stmt_get_current_hash->bindParam(':user_id', $current_user_id, PDO::PARAM_INT);
        $stmt_get_current_hash->execute();
        $user_data = $stmt_get_current_hash->fetch(PDO::FETCH_ASSOC);

        if (!$user_data) {
            $response['message'] = 'User not found. This should not happen for a logged-in user.';
            $pdo->rollBack();
            echo json_encode($response);
            exit;
        }

        $current_password_hash_from_db = $user_data['password_hash'];

        // Verify current password
        if (!password_verify($current_password, $current_password_hash_from_db)) {
            $response['message'] = 'Incorrect current password.';
            $pdo->rollBack();
            echo json_encode($response);
            exit;
        }

        // Check if the new password is the same as the old one
        if (password_verify($new_password, $current_password_hash_from_db)) {
            $response['message'] = 'New password cannot be the same as the current password.';
            $pdo->rollBack();
            echo json_encode($response);
            exit;
        }

        // Hash the new password
        $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        if ($new_password_hash === false) {
            $response['message'] = 'Failed to hash new password due to a system error.';
            error_log("Password hashing failed for user ID: " . $current_user_id);
            $pdo->rollBack();
            echo json_encode($response);
            exit;
        }

        // Update the password in the database
        $stmt_update_password = $pdo->prepare("UPDATE users SET password_hash = :new_password_hash WHERE id = :user_id");
        $stmt_update_password->bindParam(':new_password_hash', $new_password_hash, PDO::PARAM_STR);
        $stmt_update_password->bindParam(':user_id', $current_user_id, PDO::PARAM_INT);
        $stmt_update_password->execute();

        if ($stmt_update_password->rowCount() > 0) {
            $response['success'] = true;
            $response['message'] = 'Password changed successfully.';
            $pdo->commit();
        } else {
            // This might happen if the new hash is somehow identical to the old one (highly unlikely with proper salt)
            // or if the update failed for an unexpected reason not caught by exceptions.
            $response['message'] = 'Failed to update password. No changes were made.';
            $pdo->rollBack(); // Rollback if no rows affected, indicating a potential issue.
        }

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Change Own Password PDOError (User ID: {$current_user_id}): " . $e->getMessage() . " SQLSTATE: " . $e->getCode());
        $response['message'] = 'Database error while changing password. Please check server logs. SQLSTATE[' . $e->getCode() . ']';
        // http_response_code(500); // Optional: Internal Server Error
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Change Own Password General Error (User ID: {$current_user_id}): " . $e->getMessage());
        $response['message'] = 'An unexpected error occurred while changing password. Please check server logs.';
        // http_response_code(500); // Optional: Internal Server Error
    }
}

echo json_encode($response);
?>
