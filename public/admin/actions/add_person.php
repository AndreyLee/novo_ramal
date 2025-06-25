<?php
// public/admin/actions/add_person.php
header('Content-Type: application/json');
require_once __DIR__ . '/../../src/includes/db.php';
require_once __DIR__ . '/../../src/includes/session_auth.php';

require_login(['Admin', 'Super-Admin']);

$response = ['success' => false, 'message' => 'Invalid request. Only POST method is allowed.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');

    if (empty($name)) {
        $response['message'] = 'Person name cannot be empty.';
        echo json_encode($response);
        exit;
    }

    // Basic validation for name length or characters can be added here if desired
    // Example: if (strlen($name) > 255) { /* error */ }

    try {
        // It's good practice to check if a person with the same name already exists
        // if the 'name' column in 'persons' table is meant to be unique.
        // Assuming 'name' in 'persons' does NOT have a UNIQUE constraint for this example,
        // as the original schema didn't specify one. If it does, the duplicate check below or
        // relying on PDO exception (1062) for unique constraint violation is appropriate.

        /*
        // Optional: Explicit check for duplicate name if 'name' is unique and you want a custom message before insert attempt
        $checkStmt = $pdo->prepare("SELECT id FROM persons WHERE name = :name");
        $checkStmt->bindParam(':name', $name, PDO::PARAM_STR);
        $checkStmt->execute();
        if ($checkStmt->fetchColumn()) {
            $response['message'] = 'A person with this name already exists.';
            $response['success'] = false; // Explicitly set success to false
            echo json_encode($response);
            exit;
        }
        */

        $stmt = $pdo->prepare("INSERT INTO persons (name) VALUES (:name)");
        $stmt->bindParam(':name', $name, PDO::PARAM_STR);

        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Person added successfully.';
            $response['person_id'] = $pdo->lastInsertId(); // Send back the new person's ID
        } else {
            // This part might be hard to reach if ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION is set,
            // as execute() would throw an exception on failure.
            $response['message'] = 'Failed to add person due to an unknown database issue.';
        }

    } catch (PDOException $e) {
        error_log("Admin Add Person PDO Error: " . $e->getMessage());
        // Check for MySQL duplicate entry error code (ER_DUP_ENTRY)
        if ($e->errorInfo[1] == 1062) {
            $response['message'] = 'A person with this name already exists (duplicate entry).';
        } else {
            $response['message'] = 'Database error while adding person. Please check logs.';
        }
        // http_response_code(500); // Or 400 for bad request if duplicate is considered client error
    } catch (Exception $e) {
        error_log("Admin Add Person General Error: " . $e->getMessage());
        $response['message'] = 'An unexpected error occurred. Please check logs.';
        // http_response_code(500);
    }
}

echo json_encode($response);
?>
