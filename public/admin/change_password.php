<?php
require_once __DIR__ . '/../../src/includes/session_auth.php';
require_login(['Admin', 'Super-Admin']); // Accessible by both Admin and Super-Admin
$page_title = "Alterar Minha Senha";
require_once __DIR__ . '/../../src/includes/header_admin.php'; // Ajustado o caminho
?>

<h2><?php echo htmlspecialchars($page_title); ?></h2>

<div class="admin-card">
    <form id="change-password-form" class="admin-form">
        <p>Use o formulário abaixo para alterar sua senha de acesso ao painel administrativo.</p>

        <div class="form-group">
            <label for="current-password">Senha Atual:</label>
            <input type="password" id="current-password" name="current_password" class="form-control" required>
        </div>

        <div class="form-group">
            <label for="new-password">Nova Senha:</label>
            <input type="password" id="new-password" name="new_password" class="form-control" required minlength="8">
            <small class="form-text text-muted">Mínimo 8 caracteres.</small>
        </div>

        <div class="form-group">
            <label for="confirm-new-password">Confirmar Nova Senha:</label>
            <input type="password" id="confirm-new-password" name="confirm_new_password" class="form-control" required minlength="8">
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Alterar Senha</button>
        </div>

        <div id="change-password-form-message" class="form-message" style="display:none; margin-top:15px;"></div>
    </form>
</div>

<?php
// Incluir um script JS específico ou adicionar ao admin_main.js
// Por simplicidade, vou adicionar um <script> tag aqui, mas idealmente seria um arquivo separado.
?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('change-password-form');
    const messageDiv = document.getElementById('change-password-form-message');

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        messageDiv.style.display = 'none';
        messageDiv.textContent = '';
        messageDiv.className = 'form-message'; // Reset classes

        const currentPassword = document.getElementById('current-password').value;
        const newPassword = document.getElementById('new-password').value;
        const confirmNewPassword = document.getElementById('confirm-new-password').value;

        // Client-side validation
        if (!currentPassword || !newPassword || !confirmNewPassword) {
            showMessage('Todos os campos são obrigatórios.', 'error');
            return;
        }
        if (newPassword.length < 8) {
            showMessage('A nova senha deve ter pelo menos 8 caracteres.', 'error');
            return;
        }
        if (newPassword !== confirmNewPassword) {
            showMessage('A nova senha e a confirmação da nova senha não coincidem.', 'error');
            return;
        }
        if (newPassword === currentPassword) {
            showMessage('A nova senha não pode ser igual à senha atual.', 'error');
            return;
        }

        const formData = new FormData(form);

        fetch('/admin/actions/change_own_password.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showMessage(data.message, 'success');
                form.reset(); // Clear form on success
            } else {
                showMessage(data.message || 'Ocorreu um erro ao alterar a senha.', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showMessage('Erro de comunicação com o servidor. Tente novamente.', 'error');
        });
    });

    function showMessage(message, type) {
        messageDiv.textContent = message;
        messageDiv.className = 'form-message ' + (type === 'success' ? 'success-message' : 'error-message');
        messageDiv.style.display = 'block';
    }
});
</script>

<?php require_once __DIR__ . '/../../src/includes/footer_admin.php'; // Ajustado o caminho ?>
