<?php
// public/admin/actions/get_user.php
header('Content-Type: application/json');
require_once __DIR__ . '/../../src/includes/db.php';
require_once __DIR__ . '/../../src/includes/session_auth.php';

require_login(['Super-Admin']); // Only Super-Admins can get user details

$response = ['success' => false, 'data' => null, 'message' => 'Invalid request. Only GET method is allowed.'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $user_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

    if (!$user_id) {
        $response['message'] = 'User ID is required and must be a valid integer.';
        echo json_encode($response);
        exit;
    }

    try {
        // Select user details, excluding password_hash for security
        $stmt = $pdo->prepare("SELECT id, username, profile FROM users WHERE id = :id");
        $stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $response['success'] = true;
            $response['data'] = $user;
            $response['message'] = 'User details fetched successfully.';
        } else {
            $response['success'] = false; // Explicitly false if user not found
            $response['message'] = 'User not found with ID ' . htmlspecialchars($user_id) . '.';
            // http_response_code(404); // Optional: Not Found
        }
    } catch (PDOException $e) {
        error_log("Super-Admin Get User PDOError (ID: {$user_id}): " . $e->getMessage() . " SQLSTATE: " . $e->getCode());
        $response['message'] = 'Database error while fetching user details. Please check server logs. SQLSTATE[' . $e->getCode() . ']';
        // http_response_code(500);
    } catch (Exception $e) {
        error_log("Super-Admin Get User General Error (ID: {$user_id}): " . $e->getMessage());
        $response['message'] = 'An unexpected error occurred while fetching user details. Please check server logs.';
        // http_response_code(500);
    }
}
echo json_encode($response);
?>
