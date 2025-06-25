<?php
// public/admin/actions/unassign_extension.php
header('Content-Type: application/json');
require_once __DIR__ . '/../../src/includes/db.php';
require_once __DIR__ . '/../../src/includes/session_auth.php';

require_login(['Admin', 'Super-Admin']);

$response = ['success' => false, 'message' => 'Invalid request. Only POST method is allowed.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $extension_id = filter_input(INPUT_POST, 'extension_id', FILTER_VALIDATE_INT);

    if (!$extension_id) {
        $response['message'] = 'Extension ID is required and must be a valid integer.';
        echo json_encode($response);
        exit;
    }

    try {
        // Fetch current extension details to provide better feedback
        $extCheckStmt = $pdo->prepare("SELECT number, status, person_id FROM extensions WHERE id = :extension_id");
        $extCheckStmt->bindParam(':extension_id', $extension_id, PDO::PARAM_INT);
        $extCheckStmt->execute();
        $current_extension = $extCheckStmt->fetch(PDO::FETCH_ASSOC);

        if (!$current_extension) {
            $response['message'] = 'Extension not found.';
            // $response['success'] remains false
            echo json_encode($response);
            exit;
        }

        // Check if the extension is already Vago and person_id is NULL
        if ($current_extension['status'] === 'Vago' && $current_extension['person_id'] === null) {
            $response['success'] = true; // Operation is idempotent in this case
            $response['message'] = 'Extension ' . htmlspecialchars($current_extension['number']) . ' is already Vago and unassigned.';
            echo json_encode($response);
            exit;
        }

        // Proceed to update the extension
        $stmt = $pdo->prepare("UPDATE extensions SET person_id = NULL, status = 'Vago' WHERE id = :extension_id");
        $stmt->bindParam(':extension_id', $extension_id, PDO::PARAM_INT);

        $stmt->execute(); // Will throw PDOException on error if ATTR_ERRMODE is set

        if ($stmt->rowCount() > 0) {
            $response['success'] = true;
            $response['message'] = 'Extension ' . htmlspecialchars($current_extension['number']) . ' has been successfully unassigned and set to Vago.';
        } else {
            // This state should ideally be caught by the check above (already vago and unassigned).
            // If reached, it implies the extension existed but wasn't updated (e.g. status was Vago but person_id was somehow still set, then cleared).
            // Or, it could be a concurrency issue if the row was changed between the SELECT and UPDATE.
            // For simplicity, consider it a success if no error, but acknowledge no change.
            $response['success'] = true;
            $response['message'] = 'No changes were made to extension ' . htmlspecialchars($current_extension['number']) . '. It might have been already in the desired state or an issue occurred.';
        }

    } catch (PDOException $e) {
        error_log("Admin Unassign Extension PDO Error (ExtensionID: {$extension_id}): " . $e->getMessage());
        $response['message'] = 'Database error while unassigning extension. Please check logs.';
        // http_response_code(500);
    } catch (Exception $e) {
        error_log("Admin Unassign Extension General Error (ExtensionID: {$extension_id}): " . $e->getMessage());
        $response['message'] = 'An unexpected error occurred. Please check logs.';
        // http_response_code(500);
    }
}

echo json_encode($response);
?>
