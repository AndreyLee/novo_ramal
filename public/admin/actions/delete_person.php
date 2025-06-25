<?php
// public/admin/actions/delete_person.php
header('Content-Type: application/json');
require_once __DIR__ . '/../../src/includes/db.php';
require_once __DIR__ . '/../../src/includes/session_auth.php';

require_login(['Admin', 'Super-Admin']);

$response = ['success' => false, 'message' => 'Invalid request. Only POST method is allowed.'];

// Typically, DELETE operations are done via POST or DELETE HTTP methods.
// Using POST for simplicity with HTML forms.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

    if (!$id) {
        $response['message'] = 'Person ID is required for deletion.';
        echo json_encode($response);
        exit;
    }

    try {
        // Before deleting a person, check if they are assigned to any extensions.
        // This maintains data integrity.
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM extensions WHERE person_id = :person_id AND status = 'Atribuído'");
        $checkStmt->bindParam(':person_id', $id, PDO::PARAM_INT);
        $checkStmt->execute();
        $extension_count = $checkStmt->fetchColumn();

        if ($extension_count > 0) {
            $response['message'] = 'This person is currently assigned to ' . $extension_count . ' extension(s). Please unassign or reassign these extensions before deleting this person.';
            // $response['success'] remains false
            echo json_encode($response);
            exit;
        }

        // Proceed with deletion if not assigned to any extensions
        $stmt = $pdo->prepare("DELETE FROM persons WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        $stmt->execute(); // Will throw PDOException on error

        if ($stmt->rowCount() > 0) {
            $response['success'] = true;
            $response['message'] = 'Person deleted successfully.';
        } else {
            // If rowCount is 0, the person ID was not found.
            $response['success'] = false; // Explicitly false as delete target not found
            $response['message'] = 'Person not found or already deleted.';
            // http_response_code(404); // Not Found
        }

    } catch (PDOException $e) {
        error_log("Admin Delete Person PDO Error (ID: {$id}): " . $e->getMessage());
        // Foreign key constraint violations from other tables (if person_id is not ON DELETE SET NULL)
        // would also be caught here, though the check above is more specific for 'extensions'.
        $response['message'] = 'Database error while deleting person. Please check logs.';
        // http_response_code(500);
    } catch (Exception $e) {
        error_log("Admin Delete Person General Error (ID: {$id}): " . $e->getMessage());
        $response['message'] = 'An unexpected error occurred. Please check logs.';
        // http_response_code(500);
    }
}

echo json_encode($response);
?>
