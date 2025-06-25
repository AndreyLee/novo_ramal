<?php
// public/admin/actions/list_all_extensions_admin.php
header('Content-Type: application/json');
require_once __DIR__ . '/../../src/includes/db.php';
require_once __DIR__ . '/../../src/includes/session_auth.php';

require_login(['Super-Admin']); // Restricted to Super-Admins

$response = [
    'success' => false,
    'data' => [],
    'message' => '',
    'debug_query' => '', // For debugging the generated SQL
    'debug_params' => []  // For debugging the parameters passed
];

try {
    $base_sql = "
        SELECT
            e.id,
            e.number,
            e.type,
            e.status,
            s.name AS sector_name,
            s.id AS sector_id,     -- Added sector_id
            p.name AS person_name,
            p.id AS person_id      -- Added person_id
        FROM extensions e
        LEFT JOIN persons p ON e.person_id = p.id
        LEFT JOIN sectors s ON e.sector_id = s.id
    ";

    $where_clauses = [];
    $params = [];

    // --- Search term processing ---
    if (isset($_GET['search_term']) && trim($_GET['search_term']) !== '') {
        $search_term = trim($_GET['search_term']);
        $response['debug_params']['search_term'] = $search_term;
        // Search in extension number, person's name, or sector's name
        $where_clauses[] = "(e.number LIKE :search_term OR p.name LIKE :search_term OR s.name LIKE :search_term)";
        $params[':search_term'] = "%{$search_term}%";
    }

    // --- Filter by extension type ---
    if (isset($_GET['filter_type']) && in_array($_GET['filter_type'], ['Interno', 'Externo'], true)) {
        $filter_type = $_GET['filter_type'];
        $response['debug_params']['filter_type'] = $filter_type;
        $where_clauses[] = "e.type = :filter_type";
        $params[':filter_type'] = $filter_type;
    }

    // --- Filter by extension status ---
    if (isset($_GET['filter_status']) && in_array($_GET['filter_status'], ['Vago', 'Atribuído'], true)) {
        $filter_status = $_GET['filter_status'];
        $response['debug_params']['filter_status'] = $filter_status;
        $where_clauses[] = "e.status = :filter_status";
        $params[':filter_status'] = $filter_status;
    }

    // --- Construct final SQL query ---
    $sql = $base_sql;
    if (!empty($where_clauses)) {
        $sql .= " WHERE " . implode(" AND ", $where_clauses);
    }

    // --- Ordering ---
    // Default order, can be made configurable
    $sql .= " ORDER BY e.number ASC";

    // --- Pagination (Example, not fully implemented for this request) ---
    // if (isset($_GET['page']) && isset($_GET['limit'])) {
    //     $limit = filter_input(INPUT_GET, 'limit', FILTER_VALIDATE_INT, ['options' => ['default' => 10, 'min_range' => 1]]);
    //     $page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]);
    //     $offset = ($page - 1) * $limit;
    //     $sql .= " LIMIT :limit OFFSET :offset";
    //     $params[':limit'] = $limit;
    //     $params[':offset'] = $offset;
    // }

    $response['debug_query'] = $sql; // Store the generated SQL for debugging

    $stmt = $pdo->prepare($sql);

    // Bind parameters
    foreach ($params as $key => $value) {
        // Determine type for binding if necessary, though PDO often handles it.
        // For LIMIT/OFFSET, ensure PDO::PARAM_INT if implementing pagination.
        $stmt->bindValue($key, $value);
    }

    $stmt->execute();
    $extensions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $response['success'] = true;
    $response['data'] = $extensions;
    if (empty($extensions)) {
        $response['message'] = 'No extensions found matching your criteria.';
    } else {
        $response['message'] = count($extensions) . ' extension(s) found.';
    }

} catch (PDOException $e) {
    error_log("Super-Admin List All Extensions PDOError: " . $e->getMessage() . " SQL: " . $sql . " Params: " . json_encode($params));
    $response['message'] = 'Database error while listing extensions. Please check server logs. SQLSTATE[' . $e->getCode() . ']';
    // http_response_code(500);
} catch (Exception $e) {
    error_log("Super-Admin List All Extensions General Error: " . $e->getMessage());
    $response['message'] = 'An unexpected error occurred while listing extensions. Please check server logs.';
    // http_response_code(500);
}

// For production, you might want to remove or conditionally include debug_query and debug_params.
// if (ENVIRONMENT === 'development') { ... }
echo json_encode($response);
?>
