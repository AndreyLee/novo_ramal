<?php
// public/admin/actions/get_sectors_list.php
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');

$response = ['success' => false, 'data' => [], 'message' => 'An unknown error occurred initializing.'];

// Usando dirname(__DIR__, 2) conforme instrução do usuário.
// Se __DIR__ é /var/www/html/RamaisPin/public/admin/actions/
// dirname(__DIR__, 2) é /var/www/html/RamaisPin/public/
// $db_path se torna /var/www/html/RamaisPin/public/src/includes/db.php
// Isso implica que 'src' está dentro de 'public'.
$db_path = dirname(__DIR__, 2) . '/src/includes/db.php';
error_log("get_sectors_list.php: Attempting to include db.php from (user specified 2 levels): " . $db_path);

try {
    if (!file_exists($db_path)) {
        error_log("get_sectors_list.php: db.php not found at calculated path: " . $db_path . ". Re-checking __DIR__: " . __DIR__);
        // Fallback para 3 níveis, que parecia corresponder ao erro anterior se __DIR__ fosse /var/www/public/admin/actions
        $db_path_fallback = dirname(__DIR__, 3) . '/src/includes/db.php';
        error_log("get_sectors_list.php: Fallback attempt for db.php from: " . $db_path_fallback);
        if (!file_exists($db_path_fallback)) {
            error_log("get_sectors_list.php: db.php also not found at fallback path: " . $db_path_fallback);
            $response['message'] = 'Error: Database configuration file not found. Main tried: ' . $db_path . ' Fallback tried: ' . $db_path_fallback;
            echo json_encode($response);
            exit;
        }
        $db_path = $db_path_fallback; // Usar o fallback se ele existir
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
