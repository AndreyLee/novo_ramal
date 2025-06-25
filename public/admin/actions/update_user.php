<?php
// public/admin/actions/update_user.php
header('Content-Type: application/json');
require_once __DIR__ . '/../../src/includes/db.php';
require_once __DIR__ . '/../../src/includes/session_auth.php'; // For require_login and get_user_id

require_login(['Super-Admin']); // Only Super-Admins can update users

$response = ['success' => false, 'message' => 'Invalid request. Only POST method is allowed.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_to_update = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $current_logged_in_user_id = get_user_id(); // Get ID of the admin performing the action

    if (!$id_to_update) {
        $response['message'] = 'User ID is required for update and must be an integer.';
        echo json_encode($response); exit;
    }

    try {
        $pdo->beginTransaction();

        // Get current details of the user being updated (and lock the row)
        $currentUserStmt = $pdo->prepare("SELECT username, profile FROM users WHERE id = :id FOR UPDATE");
        $currentUserStmt->bindParam(':id', $id_to_update, PDO::PARAM_INT);
        $currentUserStmt->execute();
        $userBeingEdited = $currentUserStmt->fetch(PDO::FETCH_ASSOC);

        if (!$userBeingEdited) {
            $response['message'] = 'User with ID ' . htmlspecialchars($id_to_update) . ' not found.';
            $pdo->rollBack(); echo json_encode($response); exit;
        }

        $update_fields_sql = [];
        $params_to_bind = [];

        // --- Username processing ---
        if (isset($_POST['username'])) {
            $username = trim($_POST['username']);
            if (empty($username)) {
                $response['message'] = 'Username cannot be empty.'; $pdo->rollBack(); echo json_encode($response); exit;
            }
            // Add other username validations if needed (length, format)
            if ($username !== $userBeingEdited['username']) {
                $checkUserStmt = $pdo->prepare("SELECT id FROM users WHERE username = :username AND id != :id_to_update");
                $checkUserStmt->bindParam(':username', $username, PDO::PARAM_STR);
                $checkUserStmt->bindParam(':id_to_update', $id_to_update, PDO::PARAM_INT);
                $checkUserStmt->execute();
                if ($checkUserStmt->fetch()) {
                    $response['message'] = 'Username "' . htmlspecialchars($username) . '" is already taken by another user.';
                    $pdo->rollBack(); echo json_encode($response); exit;
                }
                $update_fields_sql[] = "username = :username"; $params_to_bind[':username'] = $username;
            }
        }

        // --- Password processing (only if a new password is provided) ---
        if (isset($_POST['password']) && !empty($_POST['password'])) {
            $password = $_POST['password']; // Do not trim password
            if (strlen($password) < 8) {
                $response['message'] = 'New password must be at least 8 characters long.';
                $pdo->rollBack(); echo json_encode($response); exit;
            }
            // Add other password complexity rules if needed
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            if ($password_hash === false) {
                $response['message'] = 'Failed to hash new password due to a system error.';
                $pdo->rollBack(); error_log("Password hashing failed for user ID: " . $id_to_update); echo json_encode($response); exit;
            }
            $update_fields_sql[] = "password_hash = :password_hash"; $params_to_bind[':password_hash'] = $password_hash;
        }

        // --- Profile processing ---
        if (isset($_POST['profile'])) {
            $profile = trim($_POST['profile']);
            if (!in_array($profile, ['Admin', 'Super-Admin'], true)) {
                $response['message'] = 'Invalid profile type. Must be "Admin" or "Super-Admin".';
                $pdo->rollBack(); echo json_encode($response); exit;
            }
            if ($profile !== $userBeingEdited['profile']) {
                // Logic to prevent demoting the last Super-Admin
                if ($userBeingEdited['profile'] === 'Super-Admin' && $profile === 'Admin') {
                    // Count how many Super-Admins exist
                    $saCountStmt = $pdo->query("SELECT COUNT(*) FROM users WHERE profile = 'Super-Admin'");
                    $super_admin_count = $saCountStmt->fetchColumn();

                    // If the user being edited is currently a Super-Admin and is one of potentially multiple SAs,
                    // and this count is 1, it means this user IS the only Super-Admin.
                    if ($super_admin_count <= 1) {
                        $response['message'] = 'Cannot change the profile of the last Super-Admin. The system needs at least one Super-Admin.';
                        $pdo->rollBack(); echo json_encode($response); exit;
                    }
                }
                $update_fields_sql[] = "profile = :profile"; $params_to_bind[':profile'] = $profile;
            }
        }

        if (empty($update_fields_sql)) {
            $response['success'] = true; $response['message'] = 'No changes were detected for the user.';
            $pdo->commit(); // Commit transaction even if no changes, to release lock
            echo json_encode($response); exit;
        }

        // --- Construct and execute the update query ---
        $sql = "UPDATE users SET " . implode(', ', $update_fields_sql) . " WHERE id = :id_where";
        $params_to_bind[':id_where'] = $id_to_update; // Add ID for WHERE clause

        $stmt = $pdo->prepare($sql);
        // Bind all parameters dynamically
        foreach ($params_to_bind as $placeholder => $value) {
            $stmt->bindValue($placeholder, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }

        $stmt->execute(); // Will throw PDOException on error

        if ($stmt->rowCount() > 0) {
            $response['message'] = 'User updated successfully.';
        } else {
            // This means the query executed but no rows were affected, which implies the data submitted was identical to current data.
            $response['message'] = 'User details submitted, but no actual changes were made to the record (values might be the same).';
        }
        $response['success'] = true;
        $pdo->commit();

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log("Super-Admin Update User PDOError (ID: {$id_to_update}): " . $e->getMessage() . " SQLSTATE: " . $e->getCode());
        if ($e->errorInfo[1] == 1062) { // ER_DUP_ENTRY for username
            $response['message'] = 'Username "' . htmlspecialchars($params_to_bind[':username'] ?? $_POST['username']) . '" already exists (database unique constraint violated).';
        } else {
            $response['message'] = 'Database error while updating user. Please check server logs. SQLSTATE[' . $e->getCode() . ']';
        }
        // http_response_code(500); // Or 409 for conflict
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log("Super-Admin Update User General Error (ID: {$id_to_update}): " . $e->getMessage());
        $response['message'] = 'An unexpected error occurred while updating user. Please check server logs.';
        // http_response_code(500);
    }
}
echo json_encode($response);
?>
