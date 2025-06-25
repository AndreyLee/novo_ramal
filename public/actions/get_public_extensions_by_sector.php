<?php
// public/actions/get_public_extensions_by_sector.php
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');

$response = ['success' => false, 'data' => [], 'message' => 'An unknown error occurred initializing.'];

// Usando o caminho /src/includes/db.php conforme instrução do usuário.
$db_path = '/src/includes/db.php';
error_log("get_public_extensions_by_sector.php: Attempting to include db.php from user-specified path: " . $db_path);

try {
    if (!file_exists($db_path)) {
        error_log("get_public_extensions_by_sector.php: db.php not found at path (file_exists check): " . $db_path . ". Current include_path: " . ini_get('include_path'));
        $response['message'] = 'Error: Database configuration file not found at specified path: ' . $db_path;
        echo json_encode($response);
        exit;
    }

    require_once $db_path;

    if (!isset($pdo) || !$pdo instanceof PDO) {
        error_log("get_public_extensions_by_sector.php: PDO object not available after including db.php from " . $db_path);
        $response['message'] = 'Error: Database connection object not available. Please check server logs. Path used: ' . $db_path;
        echo json_encode($response);
        exit;
    }

    // 1. Buscar todos os setores, ordenados alfabeticamente
    $sectorsStmt = $pdo->query("SELECT id, name FROM sectors ORDER BY name ASC");
    $sectors = $sectorsStmt->fetchAll(PDO::FETCH_ASSOC);

    $result_data = [];

    foreach ($sectors as $sector) {
        // 2. Para cada setor, buscar todas as pessoas associadas, ordenadas alfabeticamente
        // 3. Para cada pessoa, buscar o ramal atribuído
        $membersStmt = $pdo->prepare("
            SELECT
                p.id AS person_id,
                p.name AS person_name,
                e.number AS extension_number
            FROM persons p
            LEFT JOIN extensions e ON p.id = e.person_id AND e.status = 'Atribuído'
            WHERE p.sector_id = :sector_id
            ORDER BY p.name ASC
        ");
        $membersStmt->bindParam(':sector_id', $sector['id'], PDO::PARAM_INT);
        $membersStmt->execute();
        $members = $membersStmt->fetchAll(PDO::FETCH_ASSOC);

        $result_data[] = [
            'sector_id' => $sector['id'],
            'sector_name' => $sector['name'],
            'members' => $members
        ];
    }

    $response['success'] = true;
    $response['data'] = $result_data;
    $response['message'] = 'Data fetched successfully.';

} catch (PDOException $e) {
    error_log("Get Public Extensions by Sector (PDOException): " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine() . ". DB Path: " . $db_path);
    $response['message'] = 'Database error: ' . $e->getMessage();
} catch (Throwable $t) {
    error_log("Get Public Extensions by Sector (Throwable): " . $t->getMessage() . " in " . $t->getFile() . " on line " . $t->getLine() . ". DB Path: " . $db_path);
    $response['message'] = 'Unexpected error: ' . $t->getMessage();
}

echo json_encode($response);
exit;
?>
