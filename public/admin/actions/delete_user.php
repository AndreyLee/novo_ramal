<?php
// public/admin/actions/delete_user.php
header('Content-Type: application/json');
require_once __DIR__ . '/../../src/includes/db.php';
require_once __DIR__ . '/../../src/includes/session_auth.php'; // For require_login AND get_user_id()

require_login(['Super-Admin']); // Only Super-Admins can delete users

$response = ['success' => false, 'message' => 'Invalid request. Only POST method is allowed.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_to_delete = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $current_logged_in_user_id = get_user_id(); // Fetches ID of the admin performing the action

    if (!$id_to_delete) {
        $response['message'] = 'User ID for deletion is required and must be an integer.';
        echo json_encode($response); exit;
    }

    // --- Business Rule: Prevent self-deletion ---
    if ($id_to_delete === $current_logged_in_user_id) {
        $response['message'] = 'You cannot delete your own account. Another Super-Admin must perform this action if necessary.';
        echo json_encode($response); exit;
    }

    try {
        $pdo->beginTransaction();

        // Get profile of the user to be deleted (and lock row)
        $userStmt = $pdo->prepare("SELECT username, profile FROM users WHERE id = :id FOR UPDATE");
        $userStmt->bindParam(':id', $id_to_delete, PDO::PARAM_INT);
        $userStmt->execute();
        $user_to_delete_details = $userStmt->fetch(PDO::FETCH_ASSOC);

        if (!$user_to_delete_details) {
            $response['message'] = 'User with ID ' . htmlspecialchars($id_to_delete) . ' not found.';
            $pdo->rollBack(); echo json_encode($response); exit;
        }

        $username_to_delete = htmlspecialchars($user_to_delete_details['username']);

        // --- Business Rule: Prevent deletion of the last Super-Admin ---
        if ($user_to_delete_details['profile'] === 'Super-Admin') {
            $saCountStmt = $pdo->query("SELECT COUNT(*) FROM users WHERE profile = 'Super-Admin'");
            $super_admin_count = $saCountStmt->fetchColumn();
            if ($super_admin_count <= 1) {
                $response['message'] = 'Cannot delete user "' . $username_to_delete . '" as this is the last Super-Admin account. The system requires at least one Super-Admin.';
                $pdo->rollBack(); echo json_encode($response); exit;
            }
        }

        // Proceed with deletion
        $deleteStmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
        $deleteStmt->bindParam(':id', $id_to_delete, PDO::PARAM_INT);

        $deleteStmt->execute(); // Will throw PDOException on error

        if ($deleteStmt->rowCount() > 0) {
            $response['success'] = true;
            $response['message'] = 'User "' . $username_to_delete . '" deleted successfully.';
        } else {
            // This case should ideally be caught by the 'user not found' check earlier.
            // If reached, it implies a race condition or an unexpected state.
            $response['message'] = 'Failed to delete user "' . $username_to_delete . '". User might have been deleted by another process.';
            // $response['success'] remains false
        }
        $pdo->commit();

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log("Super-Admin Delete User PDOError (ID: {$id_to_delete}): " . $e->getMessage() . " SQLSTATE: " . $e->getCode());
        $response['message'] = 'Database error while deleting user. Please check server logs. SQLSTATE[' . $e->getCode() . ']';
        // http_response_code(500);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log("Super-Admin Delete User General Error (ID: {$id_to_delete}): " . $e->getMessage());
        $response['message'] = 'An unexpected error occurred while deleting user. Please check server logs.';
        // http_response_code(500);
    }
}
echo json_encode($response);
?>
