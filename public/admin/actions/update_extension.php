<?php
// public/admin/actions/update_extension.php
header('Content-Type: application/json');
require_once __DIR__ . '/../../src/includes/db.php';
require_once __DIR__ . '/../../src/includes/session_auth.php';

require_login(['Super-Admin']); // Restricted to Super-Admins

$response = ['success' => false, 'message' => 'Invalid request. Only POST method is allowed.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

    if (!$id) {
        $response['message'] = 'Extension ID is required for update and must be an integer.';
        echo json_encode($response);
        exit;
    }

    try {
        $pdo->beginTransaction();

        // Fetch and lock the current extension row to prevent race conditions
        $currentExtStmt = $pdo->prepare("SELECT * FROM extensions WHERE id = :id FOR UPDATE");
        $currentExtStmt->bindParam(':id', $id, PDO::PARAM_INT);
        $currentExtStmt->execute();
        $currentExtension = $currentExtStmt->fetch(PDO::FETCH_ASSOC);

        if (!$currentExtension) {
            $response['message'] = 'Extension not found with ID ' . htmlspecialchars($id) . '.';
            $pdo->rollBack();
            echo json_encode($response);
            exit;
        }

        $update_fields_sql = [];
        $params_to_bind = [];

        // --- Process 'number' field ---
        if (isset($_POST['number'])) {
            $number = trim($_POST['number']);
            if ($number !== $currentExtension['number']) {
                if (empty($number)) {
                    $response['message'] = 'Extension number cannot be empty.'; $pdo->rollBack(); echo json_encode($response); exit;
                }
                // Add any other validation for 'number' format here (e.g., regex)
                $checkNumStmt = $pdo->prepare("SELECT id FROM extensions WHERE number = :number AND id != :current_id");
                $checkNumStmt->bindParam(':number', $number, PDO::PARAM_STR);
                $checkNumStmt->bindParam(':current_id', $id, PDO::PARAM_INT);
                $checkNumStmt->execute();
                if ($checkNumStmt->fetch()) {
                    $response['message'] = 'Another extension with this number (' . htmlspecialchars($number) . ') already exists.'; $pdo->rollBack(); echo json_encode($response); exit;
                }
                $update_fields_sql[] = "number = :number"; $params_to_bind[':number'] = $number;
            }
        }

        // --- Process 'type' field ---
        if (isset($_POST['type'])) {
            $type = trim($_POST['type']);
            if ($type !== $currentExtension['type']) {
                if (!in_array($type, ['Interno', 'Externo'], true)) {
                    $response['message'] = 'Invalid extension type. Must be "Interno" or "Externo".'; $pdo->rollBack(); echo json_encode($response); exit;
                }
                $update_fields_sql[] = "type = :type"; $params_to_bind[':type'] = $type;
            }
        }

        // --- Process 'sector_id' field --- (REMOVIDO)
        // O campo sector_id não será mais enviado pelo formulário de edição.
        // Se quiséssemos permitir desvincular um setor (setar para NULL) através de outro mecanismo,
        // a lógica seria diferente. Por ora, simplesmente não processamos sector_id.
        // Ramais existentes manterão seu sector_id atual.

        // --- Determine final 'status' and 'person_id' based on inputs and logic ---
        $final_status = $currentExtension['status']; // Default to current
        if (isset($_POST['status'])) {
            $final_status = trim($_POST['status']);
            if (!in_array($final_status, ['Vago', 'Atribuído'], true)) {
                $response['message'] = 'Invalid status value. Must be "Vago" or "Atribuído".'; $pdo->rollBack(); echo json_encode($response); exit;
            }
        }

        $final_person_id = $currentExtension['person_id'] === null ? null : (int)$currentExtension['person_id']; // Default to current
        if (array_key_exists('person_id', $_POST)) { // Check if 'person_id' was part of the submitted data
            $person_id_input = trim($_POST['person_id']);
            if (strtolower($person_id_input) === 'null' || $person_id_input === '') {
                $final_person_id = null;
            } else {
                $posted_person_id = filter_var($person_id_input, FILTER_VALIDATE_INT);
                if ($posted_person_id === false) {
                    $response['message'] = 'Invalid Person ID format: must be an integer or an empty/null string.'; $pdo->rollBack(); echo json_encode($response); exit;
                }
                $final_person_id = $posted_person_id;
            }
        }

        // --- Apply consistency logic for status and person_id ---
        if ($final_status === 'Vago') {
            $final_person_id = null; // If status is Vago, person_id must be NULL
        } elseif ($final_status === 'Atribuído') {
            if ($final_person_id === null) {
                // If an attempt is made to set status to 'Atribuído' without a person_id
                // (or if person_id was explicitly set to null but status to 'Atribuído')
                $response['message'] = 'An extension with status "Atribuído" must have a person assigned.'; $pdo->rollBack(); echo json_encode($response); exit;
            }
            // If person_id is not null and status is 'Atribuído', validate the person_id
            $personCheckStmt = $pdo->prepare("SELECT id FROM persons WHERE id = :person_id");
            $personCheckStmt->bindParam(':person_id', $final_person_id, PDO::PARAM_INT); $personCheckStmt->execute();
            if (!$personCheckStmt->fetch()) {
                $response['message'] = 'Person not found for Person ID ' . htmlspecialchars($final_person_id) . '.'; $pdo->rollBack(); echo json_encode($response); exit;
            }

            // Check if this person is already assigned to another 'Atribuído' extension
            $personExtStmt = $pdo->prepare("SELECT e.number FROM extensions e WHERE e.person_id = :person_id AND e.status = 'Atribuído' AND e.id != :current_extension_id");
            $personExtStmt->bindParam(':person_id', $final_person_id, PDO::PARAM_INT);
            $personExtStmt->bindParam(':current_extension_id', $id, PDO::PARAM_INT);
            $personExtStmt->execute();
            if ($assigned_ext = $personExtStmt->fetch(PDO::FETCH_ASSOC)) {
                $response['message'] = 'Person ID ' . htmlspecialchars($final_person_id) . ' is already assigned to extension ' . htmlspecialchars($assigned_ext['number']) . '. Unassign them first or choose a different person.';
                $pdo->rollBack(); echo json_encode($response); exit;
            }
        }

        // Add person_id to update_fields_sql if it has changed
        $current_db_person_id_val = $currentExtension['person_id'] === null ? null : (int)$currentExtension['person_id'];
        if ($final_person_id !== $current_db_person_id_val) {
            $update_fields_sql[] = "person_id = :person_id"; $params_to_bind[':person_id'] = $final_person_id;
        }

        // Add status to update_fields_sql if it has changed
        if ($final_status !== $currentExtension['status']) {
            $update_fields_sql[] = "status = :status"; $params_to_bind[':status'] = $final_status;
        }

        // --- Execute update if there are changes ---
        if (empty($update_fields_sql)) {
            $response['success'] = true; $response['message'] = 'No changes detected or applied to the extension.';
            $pdo->commit(); echo json_encode($response); exit;
        }

        $sql = "UPDATE extensions SET " . implode(', ', $update_fields_sql) . " WHERE id = :id_where";
        $params_to_bind[':id_where'] = $id; // Add the ID for the WHERE clause

        $stmt = $pdo->prepare($sql);

        // Bind all parameters dynamically
        foreach ($params_to_bind as $placeholder => $value) {
            if ($placeholder === ':person_id' && $value === null) {
                $stmt->bindValue($placeholder, null, PDO::PARAM_NULL);
            } elseif (is_int($value) || in_array($placeholder, [':id_where', ':sector_id', ':person_id'])) { // person_id could be int here
                 $stmt->bindValue($placeholder, $value, PDO::PARAM_INT);
            } else { // For strings like :number, :type, :status
                $stmt->bindValue($placeholder, $value, PDO::PARAM_STR);
            }
        }

        $stmt->execute(); // Will throw PDOException on error

        // rowCount() is reliable for UPDATE in MySQL for actual changes.
        if ($stmt->rowCount() > 0) {
             $response['message'] = 'Extension updated successfully.';
        } else {
             // This might mean values submitted were same as current, or ID not found (though checked).
             // Or, for some DBs, rowCount is not reliable for "no change" updates.
             // Given our logic, if it reaches here without throwing error and rowCount is 0, it likely means values were identical.
             $response['message'] = 'Update executed, but no actual data changes were made to the extension (values might be the same).';
        }
        $response['success'] = true;
        $pdo->commit();

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log("Super-Admin Update Extension PDOError (ID: {$id}): " . $e->getMessage() . " SQLSTATE: " . $e->getCode());
        if ($e->errorInfo[1] == 1062) { // ER_DUP_ENTRY for unique constraint violation (e.g., duplicate extension number)
            $response['message'] = 'Database constraint violation: The extension number might already exist for another extension.';
        } else {
            $response['message'] = 'Database error during update. Please check server logs. SQLSTATE[' . $e->getCode() . ']';
        }
        // http_response_code(500); // Or 400/409 depending on error
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log("Super-Admin Update Extension General Error (ID: {$id}): " . $e->getMessage());
        $response['message'] = 'An unexpected error occurred: ' . $e->getMessage();
        // http_response_code(500);
    }
}
echo json_encode($response);
?>
