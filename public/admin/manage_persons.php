<?php
require_once __DIR__ . '/../src/includes/session_auth.php';
require_login(['Admin', 'Super-Admin']);
$page_title = "Gerenciar Pessoas e Atribuição de Ramais"; // Used in header_admin.php if we modify it to accept titles
require_once __DIR__ . '/../src/includes/header_admin.php';
?>

<h2><?php echo htmlspecialchars($page_title); ?></h2>

<!-- Add Person Form -->
<div class="admin-card">
    <h3>Adicionar Nova Pessoa</h3>
    <form id="add-person-form" class="admin-form">
        <div class="form-row-flex">
            <div class="form-group">
                <label for="person-name">Nome:</label>
                <input type="text" id="person-name" name="name" class="form-control" required placeholder="Ex: João Silva">
            </div>
            <div class="form-group">
                <label for="person-sector-id">Setor:</label>
                <select id="person-sector-id" name="sector_id" class="form-control" required>
                    <option value="">Carregando setores...</option>
                    <!-- Options for sectors will be populated by JS -->
                </select>
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Adicionar Pessoa</button>
        </div>
        <div id="add-person-message" class="form-message" style="display:none; margin-top:10px;"></div>
    </form>
</div>

<!-- List of Persons -->
<div class="admin-card">
    <h3>Lista de Pessoas</h3>
    <p id="persons-loading" style="text-align:center;">Carregando pessoas...</p>
    <p id="persons-error" class="error-message" style="display:none;"></p>
    <div class="table-responsive"> <!-- For better responsiveness on small screens -->
        <table id="persons-table" class="admin-table" style="display:none;"> <!-- Initially hidden until data loads -->
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>Setor</th>
                    <th>Ramal Atribuído</th>
                    <th class="actions">Ações</th>
                </tr>
            </thead>
            <tbody>
                <!-- Rows will be populated by JavaScript -->
            </tbody>
        </table>
    </div>
</div>

<!-- Edit Person Modal -->
<div id="edit-person-modal" class="modal" style="display:none;">
    <div class="modal-content">
        <span class="close-btn" data-modal-id="edit-person-modal">&times;</span>
        <h3>Editar Pessoa</h3>
        <form id="edit-person-form" class="admin-form">
            <input type="hidden" id="edit-person-id" name="id">
            <div class="form-row-flex">
                <div class="form-group">
                    <label for="edit-person-name">Nome:</label>
                    <input type="text" id="edit-person-name" name="name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="edit-person-sector-id">Setor:</label>
                    <select id="edit-person-sector-id" name="sector_id" class="form-control" required>
                        <option value="">Carregando setores...</option>
                        <!-- Options for sectors will be populated by JS -->
                    </select>
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                <button type="button" class="btn btn-secondary close-btn-action" data-modal-id="edit-person-modal">Cancelar</button>
            </div>
            <div id="edit-person-message" class="form-message" style="display:none; margin-top:10px;"></div>
        </form>
    </div>
</div>

<!-- Assign/Edit Extension Modal -->
<div id="assign-extension-modal" class="modal" style="display:none;">
    <div class="modal-content">
        <span class="close-btn" data-modal-id="assign-extension-modal">&times;</span>
        <h3 id="assign-modal-title">Atribuir/Editar Ramal</h3>
        <form id="assign-extension-form" class="admin-form">
            <input type="hidden" id="assign-person-id" name="person_id">
            <p>Pessoa: <strong id="assign-person-name-display"></strong></p>
            <p>Ramal Atual: <strong id="assign-current-extension-display">Nenhum</strong></p>

            <div class="form-group">
                <label for="assign-extension-id">Selecionar Novo Ramal (Vago):</label>
                <select id="assign-extension-id" name="extension_id" class="form-control">
                    <option value="">-- Sem Alteração / Escolha um Ramal --</option>
                    <!-- Options will be populated by JavaScript -->
                </select>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Salvar Novo Ramal</button>
                <button type="button" id="unassign-extension-btn" class="btn btn-warning" style="display:none;">Desatribuir Ramal Atual</button>
                <button type="button" class="btn btn-secondary close-btn-action" data-modal-id="assign-extension-modal">Cancelar</button>
            </div>
            <div id="assign-extension-message" class="form-message" style="display:none; margin-top:10px;"></div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../src/includes/footer_admin.php'; ?>
