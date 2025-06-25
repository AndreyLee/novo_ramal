<?php
// public/admin/actions/delete_sector.php
header('Content-Type: application/json');
require_once __DIR__ . '/../../src/includes/db.php';
require_once __DIR__ . '/../../src/includes/session_auth.php';

require_login(['Super-Admin']); // Restricted to Super-Admins

$response = ['success' => false, 'message' => 'Invalid request. Only POST method is allowed for Super-Admins.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

    if (!$id) {
        $response['message'] = 'Sector ID is required for deletion and must be an integer.';
        echo json_encode($response);
        exit;
    }

    try {
        // First, check if the sector actually exists to provide a clear message if not.
        $sectorExistsStmt = $pdo->prepare("SELECT name FROM sectors WHERE id = :id");
        $sectorExistsStmt->bindParam(':id', $id, PDO::PARAM_INT);
        $sectorExistsStmt->execute();
        $sectorName = $sectorExistsStmt->fetchColumn();

        if (!$sectorName) {
            $response['message'] = 'Sector not found. It may have already been deleted.';
            // $response['success'] remains false
            echo json_encode($response);
            exit;
        }

        // Check if the sector has any extensions associated with it
        // This includes both 'Vago' and 'Atribuído' extensions.
        // If the requirement is to only check for 'Atribuído' extensions, add "AND status = 'Atribuído'"
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM extensions WHERE sector_id = :sector_id");
        $checkStmt->bindParam(':sector_id', $id, PDO::PARAM_INT);
        $checkStmt->execute();
        $extension_count = $checkStmt->fetchColumn();

        if ($extension_count > 0) {
            $response['message'] = 'Sector "' . htmlspecialchars($sectorName) . '" cannot be deleted because it has ' . $extension_count . ' extension(s) associated with it. Please reassign or remove these extensions first.';
            // $response['success'] remains false
            echo json_encode($response);
            exit;
        }

        // Proceed with deletion if no extensions are associated
        $stmt = $pdo->prepare("DELETE FROM sectors WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        $stmt->execute(); // Will throw PDOException on error if ATTR_ERRMODE is set

        if ($stmt->rowCount() > 0) {
            $response['success'] = true;
            $response['message'] = 'Sector "' . htmlspecialchars($sectorName) . '" deleted successfully.';
        } else {
            // This case should ideally be caught by the initial existence check.
            // If reached, it implies a race condition or an unexpected state.
            $response['message'] = 'Failed to delete sector "' . htmlspecialchars($sectorName) . '". It might have been deleted by another process just now, or an error occurred.';
            // $response['success'] remains false
        }

    } catch (PDOException $e) {
        error_log("Super-Admin Delete Sector PDO Error (ID: {$id}): " . $e->getMessage());
        // The `extensions` table has `sector_id` with ON DELETE SET NULL.
        // So, a PDOException for foreign key constraint during DELETE here is unlikely for extensions.
        // However, if other tables referenced `sectors.id` with RESTRICT, it could happen.
        $response['message'] = 'Database error while deleting sector. Please check logs.';
        // http_response_code(500);
    } catch (Exception $e) {
        error_log("Super-Admin Delete Sector General Error (ID: {$id}): " . $e->getMessage());
        $response['message'] = 'An unexpected error occurred. Please check logs.';
        // http_response_code(500);
    }
}

echo json_encode($response);
?>
