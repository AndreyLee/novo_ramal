<?php
// public/admin/actions/list_persons.php
header('Content-Type: application/json');
require_once __DIR__ . '/../../../src/includes/db.php';
require_once __DIR__ . '/../../../src/includes/session_auth.php';

require_login(['Admin', 'Super-Admin']);

$response = ['success' => false, 'data' => [], 'message' => ''];

try {
    // Optional: Add search/pagination later if needed
    $stmt = $pdo->query("SELECT id, name FROM persons ORDER BY name ASC");
    $persons = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $response['success'] = true;
    $response['data'] = $persons; // Will be an empty array if no persons found
    if (empty($persons)) {
        // This message is for the case where the query is successful but returns no rows.
        $response['message'] = 'No persons found in the database.';
    }

} catch (PDOException $e) {
    error_log("Admin List Persons PDO Error: " . $e->getMessage());
    $response['message'] = 'Database error while fetching persons. Please check logs.';
    // http_response_code(500); // Consider for server-side errors
} catch (Exception $e) {
    error_log("Admin List Persons General Error: " . $e->getMessage());
    $response['message'] = 'An unexpected error occurred. Please check logs.';
    // http_response_code(500);
}

echo json_encode($response);
?>
