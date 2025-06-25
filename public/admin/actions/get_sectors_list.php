<?php
// public/admin/actions/get_sectors_list.php
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');

$response = ['success' => false, 'data' => [], 'message' => 'An unknown error occurred initializing.'];

// Usando o caminho /src/includes/db.php conforme instrução do usuário.
$db_path = '/src/includes/db.php';
error_log("get_sectors_list.php: Attempting to include db.php from user-specified path: " . $db_path);

try {
    if (!file_exists($db_path)) {
        error_log("get_sectors_list.php: db.php not found at path (file_exists check): " . $db_path . ". Current include_path: " . ini_get('include_path'));
        $response['message'] = 'Error: Database configuration file not found at specified path: ' . $db_path;
        echo json_encode($response);
        exit;
    }

    require_once $db_path;

    if (!isset($pdo) || !$pdo instanceof PDO) {
        error_log("get_sectors_list.php: PDO object not available after including db.php from " . $db_path);
        $response['message'] = 'Error: Database connection object not available. Please check server logs. Path used: ' . $db_path;
        echo json_encode($response);
        exit;
    }

    $stmt = $pdo->query("SELECT id, name FROM sectors ORDER BY name ASC");
    $sectors = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $response['success'] = true;
    $response['data'] = $sectors;
    if (empty($sectors)) {
        $response['message'] = 'No sectors found.';
    } else {
        $response['message'] = 'Sectors fetched successfully.';
    }

} catch (PDOException $e) {
    error_log("get_sectors_list.php (PDOException): " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine() . ". DB Path: " . $db_path);
    $response['message'] = 'Database error: ' . $e->getMessage();
} catch (Throwable $t) {
    error_log("get_sectors_list.php (Throwable): " . $t->getMessage() . " in " . $t->getFile() . " on line " . $t->getLine() . ". DB Path: " . $db_path);
    $response['message'] = 'Unexpected error: ' . $t->getMessage();
}

echo json_encode($response);
exit;
?>
