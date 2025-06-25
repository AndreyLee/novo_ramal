<?php
// public/admin/actions/get_person.php
header('Content-Type: application/json');
require_once __DIR__ . '/../../src/includes/db.php';
require_once __DIR__ . '/../../src/includes/session_auth.php';

require_login(['Admin', 'Super-Admin']);

$response = ['success' => false, 'data' => null, 'message' => 'Invalid request. Only GET method is allowed.'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $person_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

    if (!$person_id) {
        $response['message'] = 'Person ID is required and must be an integer.';
        echo json_encode($response);
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT id, name FROM persons WHERE id = :id");
        $stmt->bindParam(':id', $person_id, PDO::PARAM_INT);
        $stmt->execute();
        $person = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($person) {
            $response['success'] = true;
            $response['data'] = $person;
            $response['message'] = 'Person details fetched successfully.';
        } else {
            $response['success'] = false; // Keep success false if person not found
            $response['message'] = 'Person not found.';
            // http_response_code(404); // Optionally set Not Found status
        }

    } catch (PDOException $e) {
        error_log("Admin Get Person PDO Error (ID: {$person_id}): " . $e->getMessage());
        $response['message'] = 'Database error while fetching person details. Please check logs.';
        // http_response_code(500);
    } catch (Exception $e) {
        error_log("Admin Get Person General Error (ID: {$person_id}): " . $e->getMessage());
        $response['message'] = 'An unexpected error occurred. Please check logs.';
        // http_response_code(500);
    }
}

echo json_encode($response);
?>
