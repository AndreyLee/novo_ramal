<?php
// public/admin/actions/update_person.php
header('Content-Type: application/json');
require_once __DIR__ . '/../../src/includes/db.php';
require_once __DIR__ . '/../../src/includes/session_auth.php';

require_login(['Admin', 'Super-Admin']);

$response = ['success' => false, 'message' => 'Invalid request. Only POST method is allowed.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $name = trim($_POST['name'] ?? '');
    $sector_id = filter_input(INPUT_POST, 'sector_id', FILTER_VALIDATE_INT);

    if (!$id) {
        $response['message'] = 'Person ID is required for update.';
        echo json_encode($response);
        exit;
    }
    if (empty($name)) {
        $response['message'] = 'Person name cannot be empty.';
        echo json_encode($response);
        exit;
    }
    if (empty($sector_id)) {
        $response['message'] = 'Sector ID is required and must be a valid integer.';
        echo json_encode($response);
        exit;
    }

    try {
        // Check if the provided sector_id actually exists
        $sectorCheckStmt = $pdo->prepare("SELECT id FROM sectors WHERE id = :sector_id");
        $sectorCheckStmt->bindParam(':sector_id', $sector_id, PDO::PARAM_INT);
        $sectorCheckStmt->execute();
        if (!$sectorCheckStmt->fetchColumn()) {
            $response['message'] = 'Invalid Sector ID. The selected sector does not exist.';
            echo json_encode($response);
            exit;
        }

        // Optional: Check for duplicate person name if names should be unique
        // ...

        $stmt = $pdo->prepare("UPDATE persons SET name = :name, sector_id = :sector_id WHERE id = :id");
        $stmt->bindParam(':name', $name, PDO::PARAM_STR);
        $stmt->bindParam(':sector_id', $sector_id, PDO::PARAM_INT);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        $stmt->execute();

        // rowCount() can be tricky with UPDATEs if the data is the same as existing.
        // A more robust check might involve comparing old and new values or just relying on execute() not throwing an error.
        // For this case, we'll consider it a success if no PDOException was thrown.
        $response['success'] = true;
        $response['message'] = 'Person updated successfully.';
        // if ($stmt->rowCount() > 0) {
        //     $response['message'] = 'Person updated successfully.';
        // } else {
        //     $response['message'] = 'Person details were unchanged or person not found.';
        // }

    } catch (PDOException $e) {
        error_log("Admin Update Person PDO Error (ID: {$id}): " . $e->getMessage());
         if ($e->errorInfo[1] == 1062) { // MySQL ER_DUP_ENTRY
            $response['message'] = 'Another person with this name already exists (duplicate entry).';
        } else {
            $response['message'] = 'Database error while updating person. Please check logs.';
        }
        // http_response_code(500); // Or 400 for bad request (e.g. duplicate)
    } catch (Exception $e) {
        error_log("Admin Update Person General Error (ID: {$id}): " . $e->getMessage());
        $response['message'] = 'An unexpected error occurred. Please check logs.';
        // http_response_code(500);
    }
}

echo json_encode($response);
?>
