<?php
// src/ajax/get_dashboard_data.php
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache'); // HTTP 1.0.
header('Expires: 0'); // Proxies.

require_once __DIR__ . '/../includes/db.php';

$response = ['success' => false, 'data' => [], 'message' => 'An error occurred.'];

try {
    // 1. Fetch up to 9 sectors, ordered by name
    $stmt_sectors = $pdo->query("SELECT id, name FROM sectors ORDER BY name ASC LIMIT 9");
    $sectors = $stmt_sectors->fetchAll(PDO::FETCH_ASSOC);

    $dashboard_data = [];

    if ($sectors) {
        $stmt_extensions = $pdo->prepare("
            SELECT
                p.name AS person_name,
                e.number AS extension_number
            FROM extensions e
            INNER JOIN persons p ON e.person_id = p.id
            WHERE e.sector_id = :sector_id AND e.status = 'Atribuído'
            ORDER BY p.name ASC
        ");

        foreach ($sectors as $sector) {
            $stmt_extensions->bindParam(':sector_id', $sector['id'], PDO::PARAM_INT);
            $stmt_extensions->execute();
            $extensions_in_sector = $stmt_extensions->fetchAll(PDO::FETCH_ASSOC);

            $dashboard_data[] = [
                'sector_id' => (int)$sector['id'],
                'sector_name' => $sector['name'],
                'extensions' => $extensions_in_sector ?: [] // Ensure extensions is always an array
            ];
        }
    }

    $response['success'] = true;
    $response['data'] = $dashboard_data;
    if (empty($dashboard_data)) {
        $response['message'] = 'No sectors found to display.';
    } else {
        $response['message'] = 'Dashboard data fetched successfully.';
    }

} catch (PDOException $e) {
    error_log("Error fetching dashboard data (PDOException): " . $e->getMessage() . " SQLSTATE: " . $e->getCode());
    $response['message'] = 'A database error occurred while fetching dashboard data. Please try again later.';
    // http_response_code(500); // Optional
} catch (Exception $e) {
    error_log("Error fetching dashboard data (Exception): " . $e->getMessage());
    $response['message'] = 'An unexpected error occurred while fetching dashboard data. Please try again later.';
    // http_response_code(500); // Optional
}

echo json_encode($response);
?>
