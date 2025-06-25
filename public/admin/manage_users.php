<?php
require_once __DIR__ . '/../src/includes/session_auth.php';
require_login(['Super-Admin']); // Only Super-Admins can manage users
$page_title = "Gerenciar Usuários do Sistema";
require_once __DIR__ . '/../src/includes/header_admin.php';
?>

<h2><?php echo htmlspecialchars($page_title); ?></h2>

<div class="admin-card controls-bar">
    <button id="open-add-user-modal-btn" class="btn btn-success">Adicionar Novo Usuário</button>
</div>

<!-- Users Table -->
<div class="admin-card">
    <h3>Lista de Usuários</h3>
    <p id="users-loading" style="text-align:center;">Carregando usuários...</p>
    <p id="users-error" class="error-message" style="display:none;"></p>
    <div class="table-responsive">
        <table id="users-table" class="admin-table" style="display:none;"> <!-- Initially hidden -->
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Usuário (Username)</th>
                    <th>Perfil</th>
                    <th class="actions">Ações</th>
                </tr>
            </thead>
            <tbody>
                <!-- JS will populate this -->
            </tbody>
        </table>
    </div>
</div>

<!-- Add/Edit User Modal -->
<div id="user-modal" class="modal" style="display:none;">
    <div class="modal-content">
        <span class="close-btn" data-modal-id="user-modal">&times;</span>
        <h3 id="user-modal-title">Adicionar Novo Usuário</h3>
        <form id="user-form" class="admin-form">
            <input type="hidden" id="user-id" name="id">
            <div class="form-group">
                <label for="user-username">Usuário (Username):</label>
                <input type="text" id="user-username" name="username" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="user-password">Senha:</label>
                <input type="password" id="user-password" name="password" class="form-control">
                <small id="password-help" class="form-text text-muted">Deixe em branco para não alterar a senha existente (ao editar). Mínimo 8 caracteres para nova senha.</small>
            </div>
            <div class="form-group">
                <label for="user-profile">Perfil:</label>
                <select id="user-profile" name="profile" class="form-control" required>
                    <option value="Admin">Admin</option>
                    <option value="Super-Admin">Super-Admin</option>
                </select>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Salvar Usuário</button>
                <button type="button" class="btn btn-secondary close-btn-action" data-modal-id="user-modal">Cancelar</button>
            </div>
            <div id="user-form-message" class="form-message" style="display:none; margin-top:10px;"></div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../src/includes/footer_admin.php'; ?>
