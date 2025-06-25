<?php
// src/ajax/get_sectors.php
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache'); // HTTP 1.0.
header('Expires: 0'); // Proxies.

require_once __DIR__ . '/../includes/db.php'; // Correct path from src/ajax to src/includes

$response = ['success' => false, 'data' => [], 'message' => ''];

try {
    $stmt = $pdo->query("SELECT id, name FROM sectors ORDER BY name ASC");
    // fetchAll will return an empty array if no rows are found, which is fine.
    $sectors = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $response['success'] = true;
    $response['data'] = $sectors;
    if (empty($sectors)) {
        // If you want to send a specific message when no sectors are found
        // $response['message'] = 'No sectors found.';
    }

} catch (PDOException $e) {
    // Log the error to the server's error log.
    error_log("Error fetching sectors (PDOException): " . $e->getMessage());

    // Prepare a generic error message for the client.
    // Avoid sending detailed SQL errors to the client in production.
    $response['message'] = 'A database error occurred while fetching sectors. Please try again later.';

    // Optionally, you could set a specific HTTP status code for server errors,
    // though for AJAX handling, a 200 OK with error details in JSON is common.
    // http_response_code(500);
} catch (Exception $e) {
    // Catch any other unexpected errors
    error_log("Error fetching sectors (Exception): " . $e->getMessage());
    $response['message'] = 'An unexpected error occurred. Please try again later.';
    // http_response_code(500);
}

echo json_encode($response);
?>
