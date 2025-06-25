<?php
// src/includes/header_admin.php
// This header assumes that session_auth.php (which calls session_start())
// and require_login() have already been called by the parent admin page.

$current_page = basename($_SERVER['PHP_SELF']);
$username = isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Admin';
$profile = isset($_SESSION['user_profile']) ? htmlspecialchars($_SESSION['user_profile']) : 'Unknown';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Administrativo - Lista de Ramais</title>
    <link rel="stylesheet" href="/css/style.css"> <!-- Global styles -->
    <link rel="stylesheet" href="/css/admin_style.css"> <!-- Admin area specific styles -->
</head>
<body class="admin-area">
    <header class="admin-main-header">
        <div class="header-logo">
            <a href="/admin/index.php">Painel Admin</a>
        </div>
        <div class="header-user-info">
            <span>Bem-vindo, <?php echo $username; ?> (<?php echo $profile; ?>)</span>
            <a href="/admin/logout.php" class="btn btn-secondary btn-sm">Logout</a>
        </div>
    </header>
    <nav class="admin-main-nav">
        <ul>
            <li><a href="/" class="<?php echo $current_page == 'index.php' && strpos($_SERVER['REQUEST_URI'], '/admin/') === false ? 'active' : ''; ?>">Página Pública</a></li>
            <li><a href="/admin/index.php" class="<?php echo $current_page == 'index.php' && strpos($_SERVER['REQUEST_URI'], '/admin/') !== false ? 'active' : ''; ?>">Dashboard</a></li>
            <!-- Placeholder links - actual pages need to be created -->
            <li><a href="/admin/manage_persons.php" class="<?php echo $current_page == 'manage_persons.php' ? 'active' : ''; ?>">Pessoas</a></li>
            <?php if ($profile === 'Super-Admin'): ?>
                <li><a href="/admin/manage_extensions.php" class="<?php echo $current_page == 'manage_extensions.php' ? 'active' : ''; ?>">Ramais</a></li>
                <li><a href="/admin/manage_sectors.php" class="<?php echo $current_page == 'manage_sectors.php' ? 'active' : ''; ?>">Setores</a></li>
                <li><a href="/admin/manage_users.php" class="<?php echo $current_page == 'manage_users.php' ? 'active' : ''; ?>">Usuários</a></li>
            <?php endif; ?>
        </ul>
    </nav>
    <main class="admin-container">
    <!-- Main content of specific admin page starts here -->
