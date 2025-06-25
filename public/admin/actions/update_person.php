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
    // Basic validation for name length or characters can be added here
    // Example: if (strlen($name) > 255) { /* error */ }

    try {
        // Optional: Check if another person with the same name already exists (excluding the current person)
        // This is particularly important if 'name' column has a UNIQUE constraint.
        // $checkStmt = $pdo->prepare("SELECT id FROM persons WHERE name = :name AND id != :id_current");
        // $checkStmt->bindParam(':name', $name, PDO::PARAM_STR);
        // $checkStmt->bindParam(':id_current', $id, PDO::PARAM_INT);
        // $checkStmt->execute();
        // if ($checkStmt->fetchColumn()) {
        //     $response['message'] = 'Another person with this name already exists.';
        //     echo json_encode($response);
        //     exit;
        // }

        $stmt = $pdo->prepare("UPDATE persons SET name = :name WHERE id = :id");
        $stmt->bindParam(':name', $name, PDO::PARAM_STR);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        $stmt->execute(); // Will throw PDOException on error if ATTR_ERRMODE is set

        if ($stmt->rowCount() > 0) {
            $response['success'] = true;
            $response['message'] = 'Person updated successfully.';
        } else {
            // If rowCount is 0, it could mean the person ID was not found,
            // or the submitted name was identical to the existing name.
            // A SELECT query prior to UPDATE could differentiate these cases,
            // but for simplicity, we'll assume the ID is valid if no exception is thrown.
            $response['success'] = true; // Query executed without error
            $response['message'] = 'Person details were unchanged (name might be the same or ID not found if not checked prior).';
        }

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
