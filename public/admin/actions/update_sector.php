<?php
// public/admin/actions/update_sector.php
header('Content-Type: application/json');
require_once __DIR__ . '/../../src/includes/db.php';
require_once __DIR__ . '/../../src/includes/session_auth.php';

require_login(['Super-Admin']); // Restricted to Super-Admins

$response = ['success' => false, 'message' => 'Invalid request. Only POST method is allowed for Super-Admins.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $name = trim($_POST['name'] ?? '');

    if (!$id) {
        $response['message'] = 'Sector ID is required for update and must be an integer.';
        echo json_encode($response);
        exit;
    }
    if (empty($name)) {
        $response['message'] = 'Sector name cannot be empty.';
        echo json_encode($response);
        exit;
    }
    // Basic validation for name length or characters can be added here
    // Example: if (strlen($name) > 255) { /* error */ }

    try {
        // Check if the target sector ID exists before attempting to update
        $sectorExistsStmt = $pdo->prepare("SELECT id FROM sectors WHERE id = :id");
        $sectorExistsStmt->bindParam(':id', $id, PDO::PARAM_INT);
        $sectorExistsStmt->execute();
        if (!$sectorExistsStmt->fetchColumn()) {
            $response['message'] = 'The sector you are trying to update does not exist.';
            // $response['success'] remains false
            echo json_encode($response);
            exit;
        }

        // Check if another sector (with a different ID) already has the new name
        // This prevents renaming a sector to an existing sector's name.
        $checkStmt = $pdo->prepare("SELECT id FROM sectors WHERE name = :name AND id != :id_current");
        $checkStmt->bindParam(':name', $name, PDO::PARAM_STR);
        $checkStmt->bindParam(':id_current', $id, PDO::PARAM_INT);
        $checkStmt->execute();
        if ($checkStmt->fetchColumn()) {
            $response['message'] = 'Another sector with this name already exists. Please choose a different name.';
            // $response['success'] remains false
            echo json_encode($response);
            exit;
        }

        // Proceed with the update
        $stmt = $pdo->prepare("UPDATE sectors SET name = :name WHERE id = :id");
        $stmt->bindParam(':name', $name, PDO::PARAM_STR);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        $stmt->execute(); // Will throw PDOException on error if ATTR_ERRMODE is set

        if ($stmt->rowCount() > 0) {
            $response['success'] = true;
            $response['message'] = 'Sector updated successfully.';
        } else {
            // If rowCount is 0, it means the name submitted was identical to the existing name for that sector ID.
            // The checks above ensure the sector ID exists and the new name isn't a duplicate of *another* sector.
            $response['success'] = true;
            $response['message'] = 'No changes were made to the sector name (it might be the same as before).';
        }

    } catch (PDOException $e) {
        error_log("Super-Admin Update Sector PDO Error (ID: {$id}): " . $e->getMessage());
        // The 'name' column in 'sectors' has a UNIQUE constraint. The check above should catch most logical duplicates.
        // This PDO exception (1062) would be a fallback if the check logic had a flaw or for race conditions (though less likely here).
        if ($e->errorInfo[1] == 1062) {
            $response['message'] = 'Another sector with this name already exists (database unique constraint violated).';
        } else {
            $response['message'] = 'Database error while updating sector. Please check logs.';
        }
        // http_response_code(500); // Or 409 for Conflict
    } catch (Exception $e) {
        error_log("Super-Admin Update Sector General Error (ID: {$id}): " . $e->getMessage());
        $response['message'] = 'An unexpected error occurred. Please check logs.';
        // http_response_code(500);
    }
}

echo json_encode($response);
?>
