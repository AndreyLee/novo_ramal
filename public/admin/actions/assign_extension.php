<?php
// public/admin/actions/assign_extension.php
header('Content-Type: application/json');
require_once __DIR__ . '/../../src/includes/db.php';
require_once __DIR__ . '/../../src/includes/session_auth.php';

require_login(['Admin', 'Super-Admin']);

$response = ['success' => false, 'message' => 'Invalid request. Only POST method is allowed.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $person_id = filter_input(INPUT_POST, 'person_id', FILTER_VALIDATE_INT);
    $extension_id = filter_input(INPUT_POST, 'extension_id', FILTER_VALIDATE_INT);

    if (!$person_id || !$extension_id) {
        $response['message'] = 'Person ID and Extension ID are required and must be valid integers.';
        echo json_encode($response);
        exit;
    }

    try {
        $pdo->beginTransaction();

        // 1. Check if the selected extension is 'Vago'
        $extStmt = $pdo->prepare("SELECT status, number FROM extensions WHERE id = :extension_id FOR UPDATE"); // Lock row
        $extStmt->bindParam(':extension_id', $extension_id, PDO::PARAM_INT);
        $extStmt->execute();
        $extension = $extStmt->fetch(PDO::FETCH_ASSOC);

        if (!$extension) {
            $response['message'] = 'Selected extension not found.';
            $pdo->rollBack();
            echo json_encode($response);
            exit;
        }
        if ($extension['status'] !== 'Vago') {
            $response['message'] = 'Selected extension (' . htmlspecialchars($extension['number']) . ') is not Vago. It may have been recently assigned.';
            $pdo->rollBack();
            echo json_encode($response);
            exit;
        }

        // 2. Check if the selected person is already assigned to another 'Atribuído' extension
        // This enforces the "one extension per person" rule.
        $personExtStmt = $pdo->prepare("SELECT e.number FROM extensions e WHERE e.person_id = :person_id AND e.status = 'Atribuído'");
        $personExtStmt->bindParam(':person_id', $person_id, PDO::PARAM_INT);
        $personExtStmt->execute();
        $assigned_extension = $personExtStmt->fetch(PDO::FETCH_ASSOC);

        if ($assigned_extension) {
            $response['message'] = 'This person is already assigned to extension ' . htmlspecialchars($assigned_extension['number']) . '. A person can only be assigned to one active extension at a time.';
            $pdo->rollBack();
            echo json_encode($response);
            exit;
        }

        // 3. Update the extension to assign it to the person
        $new_status = 'Atribuído'; // Definir explicitamente o valor esperado do ENUM

        // Log para depuração
        error_log("Attempting to assign extension: PersonID={$person_id}, ExtensionID={$extension_id}, NewStatus={$new_status}");

        // Restaurar a condição "AND status = 'Vago'"
        $updateStmt = $pdo->prepare("UPDATE extensions SET person_id = :person_id, status = :new_status WHERE id = :extension_id AND status = 'Vago'");
        $updateStmt->bindParam(':person_id', $person_id, PDO::PARAM_INT);
        $updateStmt->bindParam(':new_status', $new_status, PDO::PARAM_STR);
        $updateStmt->bindParam(':extension_id', $extension_id, PDO::PARAM_INT);

        $updateSuccess = $updateStmt->execute();

        if ($updateSuccess && $updateStmt->rowCount() > 0) {
            $pdo->commit();
            $response['success'] = true;
            $response['message'] = 'Extension assigned successfully to person.';
        } else {
            // This condition could be met if the extension's status changed between the SELECT and UPDATE (concurrency)
            // or if the extension_id somehow didn't match. The FOR UPDATE in the select should mitigate this.
            $pdo->rollBack();
            $response['message'] = 'Failed to assign extension. The extension status might have changed, or an error occurred. Please try again.';
        }

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Admin Assign Extension PDO Error: PersonID={$person_id}, ExtensionID={$extension_id} - " . $e->getMessage());
        // 1062 is ER_DUP_ENTRY. This might happen if there's a unique constraint that wasn't anticipated,
        // e.g. if (person_id, status='Atribuido') was made unique.
        // Para depuração, envie a mensagem de erro PDO detalhada para o cliente.
        // Em produção, use uma mensagem genérica e confie nos logs do servidor.
        $response['message'] = 'Database error during extension assignment: ' . $e->getMessage();
        // Adicionar código de erro SQLSTATE também pode ser útil para depuração
        $response['sqlstate'] = $e->getCode(); // ou $e->errorInfo[0]
        // http_response_code(500); // Ou 409 para conflito
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Admin Assign Extension General Error: PersonID={$person_id}, ExtensionID={$extension_id} - " . $e->getMessage());
        // Para depuração, pode ser útil enviar esta mensagem também, mas geralmente é menos específica que a PDOException.
        $response['message'] = 'An unexpected error occurred during extension assignment: ' . $e->getMessage();
        // http_response_code(500);
    }
}

echo json_encode($response);
?>
