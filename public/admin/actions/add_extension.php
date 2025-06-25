<?php
// public/admin/actions/add_extension.php
header('Content-Type: application/json');
require_once __DIR__ . '/../../src/includes/db.php';
require_once __DIR__ . '/../../src/includes/session_auth.php';

require_login(['Super-Admin']); // Only Super-Admins can add new extensions

$response = ['success' => false, 'message' => 'Invalid request. Only POST method is allowed for Super-Admins.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $number = trim($_POST['number'] ?? '');
    $type = trim($_POST['type'] ?? ''); // Should be 'Interno' or 'Externo'
    // $sector_id = filter_input(INPUT_POST, 'sector_id', FILTER_VALIDATE_INT); // Removido

    // --- Input Validations ---
    if (empty($number)) {
        $response['message'] = 'Extension number cannot be empty.';
        echo json_encode($response);
        exit;
    }
    // Basic validation for extension number format (e.g., only digits, specific length) could be added here
    // Example: if (!preg_match('/^[0-9]{3,10}$/', $number)) { $response['message'] = 'Invalid extension number format.'; ... }


    if (!in_array($type, ['Interno', 'Externo'], true)) { // Strict comparison
        $response['message'] = 'Invalid extension type. Must be "Interno" or "Externo".';
        echo json_encode($response);
        exit;
    }
    // Validação de sector_id removida

    try {
        // Verificação de existência de sector_id removida

        // Check if an extension with the same number already exists (extension numbers must be unique)
        $checkStmt = $pdo->prepare("SELECT id FROM extensions WHERE number = :number");
        $checkStmt->bindParam(':number', $number, PDO::PARAM_STR);
        $checkStmt->execute();
        if ($checkStmt->fetchColumn()) {
            $response['message'] = 'An extension with this number (' . htmlspecialchars($number) . ') already exists.';
            echo json_encode($response);
            exit;
        }

        // Insert the new extension.
        // `status` defaults to 'Vago' and `person_id` defaults to NULL as per table schema.
        // sector_id será NULL por padrão no banco de dados, já que não está sendo fornecido.
        // O status será 'Vago' por padrão, conforme definido no esquema do banco de dados.
        // Se quisermos garantir explicitamente 'Vago' aqui, poderíamos adicionar status = 'Vago' na query.
        // Mas como o default da tabela já é 'Vago', não é estritamente necessário.
        $stmt = $pdo->prepare("INSERT INTO extensions (number, type, status) VALUES (:number, :type, 'Vago')");
        $stmt->bindParam(':number', $number, PDO::PARAM_STR);
        $stmt->bindParam(':type', $type, PDO::PARAM_STR); // ENUMs are handled as strings by PDO
        // $stmt->bindParam(':sector_id', $sector_id, PDO::PARAM_INT); // Removido

        $stmt->execute(); // Will throw PDOException on error

        if ($stmt->rowCount() > 0) {
            $response['success'] = true;
            $response['message'] = 'Extension ' . htmlspecialchars($number) . ' added successfully and set to Vago.';
            $response['extension_id'] = $pdo->lastInsertId(); // Get ID of the new extension
        } else {
            // Should not be reached if execute() is successful and PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            $response['message'] = 'Failed to add extension. No rows were affected.';
        }

    } catch (PDOException $e) {
        error_log("Super-Admin Add Extension PDO Error: " . $e->getMessage());
        // The 'number' column in 'extensions' has a UNIQUE constraint.
        if ($e->errorInfo[1] == 1062) { // MySQL ER_DUP_ENTRY
            $response['message'] = 'An extension with this number (' . htmlspecialchars($number) . ') already exists (database unique constraint violated).';
        }
        // The 'sector_id' in 'extensions' is a foreign key to 'sectors.id'.
        // The explicit check for sector existence above should catch invalid sector_id before this.
        // However, if it somehow gets here, 1452 is ER_NO_REFERENCED_ROW_2.
        elseif ($e->errorInfo[1] == 1452) {
             $response['message'] = 'Invalid Sector ID provided (database foreign key constraint failed).';
        }
        else {
            $response['message'] = 'Database error while adding extension. Please check logs.';
        }
        // http_response_code(500); // Or 409 for conflict
    } catch (Exception $e) {
        error_log("Super-Admin Add Extension General Error: " . $e->getMessage());
        $response['message'] = 'An unexpected error occurred. Please check logs.';
        // http_response_code(500);
    }
}

echo json_encode($response);
?>
