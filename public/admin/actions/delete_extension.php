<?php
// public/admin/actions/delete_extension.php
header('Content-Type: application/json');
require_once __DIR__ . '/../../src/includes/db.php';
require_once __DIR__ . '/../../src/includes/session_auth.php';

require_login(['Super-Admin']); // Restricted to Super-Admins

$response = ['success' => false, 'message' => 'Invalid request. Only POST method is allowed for Super-Admins.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

    if (!$id) {
        $response['message'] = 'Extension ID is required for deletion and must be an integer.';
        echo json_encode($response);
        exit;
    }

    try {
        // Optional: Fetch extension number for a more informative success/failure message
        // This also serves as a check if the extension exists before attempting to delete.
        $extDataStmt = $pdo->prepare("SELECT number FROM extensions WHERE id = :id");
        $extDataStmt->bindParam(':id', $id, PDO::PARAM_INT);
        $extDataStmt->execute();
        $extensionData = $extDataStmt->fetch(PDO::FETCH_ASSOC);

        $extensionNumberDisplay = $extensionData ? htmlspecialchars($extensionData['number']) : 'ID ' . htmlspecialchars($id);

        if (!$extensionData) {
            $response['message'] = 'Extension ' . $extensionNumberDisplay . ' not found.';
            // $response['success'] remains false
            echo json_encode($response);
            exit;
        }

        // Proceed with deletion
        $deleteStmt = $pdo->prepare("DELETE FROM extensions WHERE id = :id");
        $deleteStmt->bindParam(':id', $id, PDO::PARAM_INT);

        $deleteStmt->execute(); // Will throw PDOException on error if ATTR_ERRMODE is set

        if ($deleteStmt->rowCount() > 0) {
            $response['success'] = true;
            $response['message'] = 'Extension ' . $extensionNumberDisplay . ' deleted successfully.';
        } else {
            // This case should ideally be caught by the existence check above.
            // If reached, it implies a race condition or an unexpected state.
            $response['message'] = 'Failed to delete extension ' . $extensionNumberDisplay . '. It might have been deleted by another process just now, or an error occurred.';
            // $response['success'] remains false
        }

    } catch (PDOException $e) {
        error_log("Super-Admin Delete Extension PDO Error (ID: {$id}): " . $e->getMessage());
        // Foreign key constraints from other tables (if any referenced extensions.id with ON DELETE RESTRICT)
        // would be caught here. For example, if call logs referenced extension_id.
        // MySQL error code 1451: ER_ROW_IS_REFERENCED_2 (Cannot delete or update a parent row: a foreign key constraint fails)
        if ($e->errorInfo[1] == 1451) {
             $response['message'] = 'Cannot delete extension ' . $extensionNumberDisplay . ' as it is referenced by other records in the system.';
        } else {
            $response['message'] = 'Database error while deleting extension ' . $extensionNumberDisplay . '. Please check logs. SQLSTATE[' . $e->getCode() . ']';
        }
        // http_response_code(500); // Or 409 for Conflict
    } catch (Exception $e) {
        error_log("Super-Admin Delete Extension General Error (ID: {$id}): " . $e->getMessage());
        $response['message'] = 'An unexpected error occurred while deleting extension ' . $extensionNumberDisplay . '. Please check logs.';
        // http_response_code(500);
    }
}

echo json_encode($response);
?>
