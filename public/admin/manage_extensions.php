<?php
require_once __DIR__ . '/../src/includes/session_auth.php';
require_login(['Super-Admin']); // Only Super-Admins can access this page
$page_title = "Gerenciar Todos os Ramais";
require_once __DIR__ . '/../src/includes/header_admin.php';
?>

<h2><?php echo htmlspecialchars($page_title); ?></h2>

<!-- Filters and Add Button -->
<div class="admin-card controls-bar">
    <div class="filters">
        <input type="text" id="ext-search-term" class="form-control" placeholder="Buscar ramal, pessoa, setor...">
        <select id="ext-filter-type" class="form-control">
            <option value="">Todos os Tipos</option>
            <option value="Interno">Interno</option>
            <option value="Externo">Externo</option>
        </select>
        <select id="ext-filter-status" class="form-control">
            <option value="">Todos os Status</option>
            <option value="Vago">Vago</option>
            <option value="Atribuído">Atribuído</option>
        </select>
        <button id="apply-ext-filters-btn" class="btn btn-primary">Buscar/Filtrar</button>
        <button id="reset-ext-filters-btn" class="btn btn-secondary">Limpar Filtros</button>
    </div>
    <button id="open-add-extension-modal-btn" class="btn btn-success">Adicionar Novo Ramal</button>
</div>

<!-- Extensions Table -->
<div class="admin-card">
    <h3>Lista de Ramais</h3>
    <p id="extensions-loading" style="text-align:center;">Carregando ramais...</p>
    <p id="extensions-error" class="error-message" style="display:none;"></p>
    <div class="table-responsive">
        <table id="extensions-table" class="admin-table" style="display:none;"> <!-- Initially hidden -->
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Número</th>
                    <th>Tipo</th>
                    <th>Setor</th>
                    <th>Status</th>
                    <th>Pessoa Atribuída</th>
                    <th class="actions">Ações</th>
                </tr>
            </thead>
            <tbody>
                <!-- JS will populate this -->
            </tbody>
        </table>
    </div>
</div>

<!-- Add/Edit Extension Modal -->
<div id="extension-modal" class="modal" style="display:none;">
    <div class="modal-content">
        <span class="close-btn" data-modal-id="extension-modal">&times;</span>
        <h3 id="extension-modal-title">Adicionar Novo Ramal</h3>
        <form id="extension-form" class="admin-form">
            <input type="hidden" id="extension-id" name="id">
            <div class="form-group">
                <label for="extension-number">Número do Ramal:</label>
                <input type="text" id="extension-number" name="number" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="extension-type">Tipo:</label>
                <select id="extension-type" name="type" class="form-control" required>
                    <option value="Interno">Interno</option>
                    <option value="Externo">Externo</option>
                </select>
            </div>
            <div class="form-group" id="status-form-group">
                <label for="extension-status">Status:</label>
                <select id="extension-status" name="status" class="form-control" required>
                    <option value="Vago">Vago</option>
                    <option value="Atribuído">Atribuído</option>
                </select>
            </div>
            <div class="form-group" id="person-assignment-group" style="display:none;"> <!-- Show only if status is 'Atribuído' -->
                <label for="extension-person-id">Pessoa Atribuída:</label>
                <select id="extension-person-id" name="person_id" class="form-control">
                    <option value="">-- Nenhuma --</option>
                    <!-- Options for persons will be populated by JS -->
                </select>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Salvar Ramal</button>
                <button type="button" class="btn btn-secondary close-btn-action" data-modal-id="extension-modal">Cancelar</button>
            </div>
            <div id="extension-form-message" class="form-message" style="display:none; margin-top:10px;"></div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../src/includes/footer_admin.php'; ?>
