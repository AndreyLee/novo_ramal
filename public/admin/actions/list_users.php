<?php
// public/admin/actions/list_users.php
header('Content-Type: application/json');
require_once __DIR__ . '/../../src/includes/db.php';
require_once __DIR__ . '/../../src/includes/session_auth.php';

require_login(['Super-Admin']); // Only Super-Admins can list all users

$response = ['success' => false, 'data' => [], 'message' => ''];

try {
    // Select user details, excluding password_hash for security
    $stmt = $pdo->query("SELECT id, username, profile FROM users ORDER BY username ASC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $response['success'] = true;
    $response['data'] = $users; // Will be an empty array if no users are found
    if (empty($users)) {
        $response['message'] = 'No users found in the system.';
    } else {
        $response['message'] = count($users) . ' user(s) fetched successfully.';
    }

} catch (PDOException $e) {
    error_log("Super-Admin List Users PDOError: " . $e->getMessage());
    $response['message'] = 'Database error while listing users. Please check server logs. SQLSTATE[' . $e->getCode() . ']';
    // http_response_code(500);
} catch (Exception $e) {
    error_log("Super-Admin List Users General Error: " . $e->getMessage());
    $response['message'] = 'An unexpected error occurred while listing users. Please check server logs.';
    // http_response_code(500);
}

echo json_encode($response);
?>
