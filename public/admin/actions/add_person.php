<?php
// public/admin/actions/add_person.php
header('Content-Type: application/json');
require_once __DIR__ . '/../../src/includes/db.php';
require_once __DIR__ . '/../../src/includes/session_auth.php';

require_login(['Admin', 'Super-Admin']);

$response = ['success' => false, 'message' => 'Invalid request. Only POST method is allowed.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $sector_id = filter_input(INPUT_POST, 'sector_id', FILTER_VALIDATE_INT);

    if (empty($name)) {
        $response['message'] = 'Person name cannot be empty.';
        echo json_encode($response);
        exit;
    }
    if (empty($sector_id)) { // sector_id is now required
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

        // Optional: Check for duplicate person name if names should be unique (not specified in current schema for persons.name)
        // ...

        $stmt = $pdo->prepare("INSERT INTO persons (name, sector_id) VALUES (:name, :sector_id)");
        $stmt->bindParam(':name', $name, PDO::PARAM_STR);
        $stmt->bindParam(':sector_id', $sector_id, PDO::PARAM_INT);

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
