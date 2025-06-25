<?php
// src/ajax/get_extensions_by_sector.php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/db.php'; // Correct path from src/ajax to src/includes

$response = ['success' => false, 'data' => [], 'message' => ''];

// Validate sector_id from GET parameters
$sector_id = filter_input(INPUT_GET, 'sector_id', FILTER_VALIDATE_INT);

if ($sector_id === false || $sector_id === null) { // filter_input returns false on failure, null if var not set
    $response['message'] = 'Sector ID is required and must be a valid integer.';
    echo json_encode($response);
    exit;
}

try {
    // Query to fetch assigned extensions for a given sector, joining with persons table
    // It's assumed that only extensions with an assigned person (status = 'Atribuído') are relevant here.
    $stmt = $pdo->prepare("
        SELECT
            e.id AS extension_id,
            e.number AS extension_number,
            p.name AS person_name,
            e.status,
            e.type AS extension_type
        FROM extensions e
        INNER JOIN persons p ON e.person_id = p.id
        WHERE e.sector_id = :sector_id AND e.status = 'Atribuído'
        ORDER BY p.name ASC
    ");
    // The JOIN (INNER JOIN) implicitly filters out extensions where person_id is NULL.
    // The condition e.status = 'Atribuído' is an explicit filter.

    $stmt->bindParam(':sector_id', $sector_id, PDO::PARAM_INT);
    $stmt->execute();

    $extensions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $response['success'] = true;
    $response['data'] = $extensions; // Will be an empty array if no extensions found

    if (empty($extensions)) {
        // Optionally, provide a message if no extensions are found.
        // This is not an error, so success remains true.
        $response['message'] = 'No assigned extensions found for this sector.';
    }

} catch (PDOException $e) {
    error_log("Error fetching extensions for sector_id {$sector_id} (PDOException): " . $e->getMessage());
    $response['message'] = 'A database error occurred while fetching extensions. Please try again later.';
    // http_response_code(500); // Optional: set server error status
} catch (Exception $e) {
    error_log("Error fetching extensions for sector_id {$sector_id} (Exception): " . $e->getMessage());
    $response['message'] = 'An unexpected error occurred. Please try again later.';
    // http_response_code(500); // Optional: set server error status
}

echo json_encode($response);
?>
