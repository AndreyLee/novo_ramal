<?php
// public/admin/actions/add_sector.php
header('Content-Type: application/json');
require_once __DIR__ . '/../../src/includes/db.php';
require_once __DIR__ . '/../../src/includes/session_auth.php';

// Ensure only Super-Admins can access this script
require_login(['Super-Admin']);

$response = ['success' => false, 'message' => 'Invalid request. Only POST method is allowed for Super-Admins.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');

    if (empty($name)) {
        $response['message'] = 'Sector name cannot be empty.';
        echo json_encode($response);
        exit;
    }

    // Basic validation for name length or characters can be added here if desired
    // Example: if (strlen($name) > 255) { /* error */ }

    try {
        // Check if a sector with the same name already exists (case-sensitive check)
        // For a case-insensitive check, you might use: SELECT id FROM sectors WHERE LOWER(name) = LOWER(:name)
        $checkStmt = $pdo->prepare("SELECT id FROM sectors WHERE name = :name");
        $checkStmt->bindParam(':name', $name, PDO::PARAM_STR);
        $checkStmt->execute();

        if ($checkStmt->fetchColumn()) { // fetchColumn() is efficient for checking existence
            $response['message'] = 'A sector with this exact name already exists.';
            // $response['success'] remains false
            echo json_encode($response);
            exit;
        }

        // Proceed to insert the new sector
        $stmt = $pdo->prepare("INSERT INTO sectors (name) VALUES (:name)");
        $stmt->bindParam(':name', $name, PDO::PARAM_STR);

        $stmt->execute(); // Will throw PDOException on error if ATTR_ERRMODE is set

        // $stmt->rowCount() should be 1 for a successful insert.
        if ($stmt->rowCount() > 0) {
            $response['success'] = true;
            $response['message'] = 'Sector added successfully.';
            $response['sector_id'] = $pdo->lastInsertId(); // Get the ID of the newly inserted sector
        } else {
            // This case should ideally not be reached if execute() succeeds without an exception
            // and an actual insert happens. If it is, it indicates an issue.
            $response['message'] = 'Failed to add sector. The sector may not have been saved.';
        }

    } catch (PDOException $e) {
        error_log("Super-Admin Add Sector PDO Error: " . $e->getMessage());
        // The 'name' column in 'sectors' table has a UNIQUE constraint from init.sql.
        // So, a duplicate entry attempt will throw a PDOException with SQLSTATE[23000] (Integrity constraint violation)
        // and errorInfo[1] == 1062 for MySQL (ER_DUP_ENTRY).
        if ($e->errorInfo[1] == 1062) {
            $response['message'] = 'A sector with this name already exists (database unique constraint violated).';
        } else {
            $response['message'] = 'Database error while adding sector. Please check logs.';
        }
        // http_response_code(500); // Or 409 for Conflict if duplicate
    } catch (Exception $e) {
        error_log("Super-Admin Add Sector General Error: " . $e->getMessage());
        $response['message'] = 'An unexpected error occurred. Please check logs.';
        // http_response_code(500);
    }
}

echo json_encode($response);
?>
