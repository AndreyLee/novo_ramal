<?php
require_once __DIR__ . '/../src/includes/session_auth.php';
require_login(['Super-Admin']); // Only Super-Admins can manage sectors
$page_title = "Gerenciar Setores";
require_once __DIR__ . '/../src/includes/header_admin.php';
?>

<h2><?php echo htmlspecialchars($page_title); ?></h2>

<!-- Add Sector Form -->
<div class="admin-card">
    <h3>Adicionar Novo Setor</h3>
    <form id="add-sector-form" class="admin-form">
        <div class="form-group">
            <label for="sector-name">Nome do Setor:</label>
            <input type="text" id="sector-name" name="name" class="form-control" required placeholder="Ex: Financeiro">
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Adicionar Setor</button>
        </div>
        <div id="add-sector-message" class="form-message" style="display:none; margin-top:10px;"></div>
    </form>
</div>

<!-- List of Sectors -->
<div class="admin-card">
    <h3>Lista de Setores</h3>
    <p id="sectors-loading" style="text-align:center;">Carregando setores...</p>
    <p id="sectors-error" class="error-message" style="display:none;"></p>
    <div class="table-responsive">
        <table id="sectors-table" class="admin-table" style="display:none;"> <!-- Initially hidden -->
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th class="actions">Ações</th>
                </tr>
            </thead>
            <tbody>
                <!-- JS will populate this -->
            </tbody>
        </table>
    </div>
</div>

<!-- Edit Sector Modal -->
<div id="edit-sector-modal" class="modal" style="display:none;">
    <div class="modal-content">
        <span class="close-btn" data-modal-id="edit-sector-modal">&times;</span>
        <h3>Editar Setor</h3>
        <form id="edit-sector-form" class="admin-form">
            <input type="hidden" id="edit-sector-id" name="id">
            <div class="form-group">
                <label for="edit-sector-name">Novo Nome do Setor:</label>
                <input type="text" id="edit-sector-name" name="name" class="form-control" required>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                <button type="button" class="btn btn-secondary close-btn-action" data-modal-id="edit-sector-modal">Cancelar</button>
            </div>
            <div id="edit-sector-message" class="form-message" style="display:none; margin-top:10px;"></div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../src/includes/footer_admin.php'; ?>
