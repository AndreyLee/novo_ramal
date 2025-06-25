<?php
// public/admin/login.php
require_once __DIR__ . '/../src/includes/session_auth.php';

if (is_logged_in()) {
    header('Location: index.php'); // Redirect to admin dashboard
    exit;
}

$error_message = '';
$message_type = 'error'; // 'error' or 'info'

if (isset($_GET['error'])) {
    $message_type = 'error';
    switch ($_GET['error']) {
        case 'not_logged_in': $error_message = 'Por favor, faça login para acessar esta página.'; break;
        case 'unauthorized': $error_message = 'Você não tem autorização para visualizar esta página.'; break;
        case 'invalid_credentials': $error_message = 'Usuário ou senha inválidos.'; break;
        case 'missing_fields': $error_message = 'Por favor, preencha usuário e senha.'; break;
        case 'db_error': $error_message = 'Erro no banco de dados. Tente novamente mais tarde.'; break;
        default: $error_message = 'Ocorreu um erro desconhecido.'; break;
    }
}
if (isset($_GET['status']) && $_GET['status'] == 'logged_out') {
    $message_type = 'info';
    $error_message = 'Você foi desconectado com sucesso.';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Lista de Ramais</title>
    <link rel="stylesheet" href="/css/style.css"> <!-- Using global style -->
    <link rel="stylesheet" href="/css/admin_login_style.css"> <!-- Specific styles for admin login -->
</head>
<body class="admin-login-page">
    <div class="login-container">
        <h2>Login Administrativo</h2>
        <?php if ($error_message): ?>
            <p class="message <?php echo $message_type === 'info' ? 'info-message' : 'error-message'; ?>">
                <?php echo htmlspecialchars($error_message); ?>
            </p>
        <?php endif; ?>
        <form action="handle_login.php" method="POST">
            <div class="form-group">
                <label for="username">Usuário:</label>
                <input type="text" id="username" name="username" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="password">Senha:</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Login</button>
        </form>
         <p class="back-to-site"><a href="/">Voltar para o site principal</a></p>
    </div>
    <!-- Minimal footer for login page -->
    <footer class="main-footer login-footer">
        <p>&copy; <?php echo date('Y'); ?> Lista de Ramais</p>
    </footer>
</body>
</html>
