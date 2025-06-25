<?php
// src/ajax/search_extensions.php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/db.php'; // Correct path from src/ajax to src/includes

$response = ['success' => false, 'data' => [], 'message' => ''];

// Get the search term from GET parameter, apply basic sanitization
// FILTER_SANITIZE_STRING is deprecated in PHP 8. Use htmlspecialchars or similar.
// For SQL LIKE, direct user input needs careful handling, though PDO prepares take care of injection.
// Let's use a simple trim and check for emptiness.
$term = isset($_GET['term']) ? trim($_GET['term']) : '';

if ($term === '') { // Changed to strict comparison for empty string
    $response['success'] = true; // Consistent with plan: empty term = successful empty search
    $response['data'] = [];
    // $response['message'] = 'Search term is required.'; // Or this, if preferred for empty term
    echo json_encode($response);
    exit;
}

// Prepare the search parameter for SQL LIKE query
$search_param = "%{$term}%";

try {
    $stmt = $pdo->prepare("
        SELECT
            e.id AS extension_id,       -- Added extension_id
            e.number AS extension_number,
            p.id AS person_id,          -- Added person_id
            p.name AS person_name,
            s.id AS sector_id,          -- Useful for frontend linking/grouping
            s.name AS sector_name,
            e.type AS extension_type    -- Added extension_type
        FROM extensions e
        INNER JOIN persons p ON e.person_id = p.id
        INNER JOIN sectors s ON e.sector_id = s.id
        WHERE e.status = 'Atribuído' AND (p.name LIKE :term OR e.number LIKE :term)
        ORDER BY s.name ASC, p.name ASC, e.number ASC -- Added third order by extension_number
    ");

    $stmt->bindParam(':term', $search_param, PDO::PARAM_STR);
    $stmt->execute();

    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $response['success'] = true;
    $response['data'] = $results; // Will be an empty array if no results found

    if (empty($results)) {
        // Optionally, provide a message if no results are found.
        $response['message'] = 'No extensions or persons found matching your search term.';
    }

} catch (PDOException $e) {
    error_log("Error searching extensions for term '{$term}' (PDOException): " . $e->getMessage());
    $response['message'] = 'A database error occurred during the search. Please try again later.';
    // http_response_code(500); // Optional: set server error status
} catch (Exception $e) {
    error_log("Error searching extensions for term '{$term}' (Exception): " . $e->getMessage());
    $response['message'] = 'An unexpected error occurred during the search. Please try again later.';
    // http_response_code(500); // Optional: set server error status
}

echo json_encode($response);
?>
