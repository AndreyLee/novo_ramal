<?php
// public/admin/actions/list_persons.php
// Renamed to list_persons_with_extensions.php in JS, ensure filename matches or JS endpoint is updated.
// For this step, keeping filename as list_persons.php and assuming JS will call this.
header('Content-Type: application/json');
require_once __DIR__ . '/../../src/includes/db.php';
require_once __DIR__ . '/../../src/includes/session_auth.php';

require_login(['Admin', 'Super-Admin']);

$response = ['success' => false, 'data' => [], 'message' => ''];

try {
    $sql = "
        SELECT
            p.id,
            p.name,
            p.sector_id,
            s.name AS sector_name,
            e.id AS assigned_extension_id,
            e.number AS assigned_extension_number
        FROM persons p
        JOIN sectors s ON p.sector_id = s.id
        LEFT JOIN extensions e ON p.id = e.person_id AND e.status = 'Atribuído'
        ORDER BY p.name ASC
    ";
    $stmt = $pdo->query($sql);
    $persons = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $response['success'] = true;
    $response['data'] = $persons;
    if (empty($persons)) {
        $response['message'] = 'No persons found in the database.';
    } else {
        $response['message'] = count($persons) . ' person(s) fetched successfully.';
    }

} catch (PDOException $e) {
    error_log("Admin List Persons (with extensions) PDO Error: " . $e->getMessage());
    $response['message'] = 'Database error while fetching persons and their extensions. Please check logs.';
} catch (Exception $e) {
    error_log("Admin List Persons (with extensions) General Error: " . $e->getMessage());
    $response['message'] = 'An unexpected error occurred. Please check logs.';
}

echo json_encode($response);
?>
