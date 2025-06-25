<?php
// public/admin/actions/get_unassigned_extensions.php
header('Content-Type: application/json');
require_once __DIR__ . '/../../src/includes/db.php';
require_once __DIR__ . '/../../src/includes/session_auth.php';

require_login(['Admin', 'Super-Admin']);

$response = ['success' => false, 'data' => [], 'message' => ''];

try {
    $stmt = $pdo->query("SELECT id, number, type FROM extensions WHERE status = 'Vago' ORDER BY number ASC");
    $extensions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $response['success'] = true;
    $response['data'] = $extensions; // Will be an empty array if none are found
    if (empty($extensions)) {
        $response['message'] = 'No unassigned (Vago) extensions found.';
    } else {
        $response['message'] = 'Unassigned extensions fetched successfully.';
    }

} catch (PDOException $e) {
    error_log("Admin Get Unassigned Extensions PDO Error: " . $e->getMessage());
    $response['message'] = 'Database error while fetching unassigned extensions. Please check logs.';
    // http_response_code(500); // Consider for server-side errors
} catch (Exception $e) {
    error_log("Admin Get Unassigned Extensions General Error: " . $e->getMessage());
    $response['message'] = 'An unexpected error occurred. Please check logs.';
    // http_response_code(500);
}

echo json_encode($response);
?>
