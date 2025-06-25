<?php
// public/admin/index.php
require_once __DIR__ . '/../src/includes/session_auth.php';
require_login(['Admin', 'Super-Admin']); // Allow both for dashboard, specific pages might restrict further

// The header_admin.php will access $_SESSION['username'] and $_SESSION['user_profile']
require_once __DIR__ . '/../src/includes/header_admin.php';
?>

<h2>Dashboard Administrativo</h2>
<p>Bem-vindo ao painel de administração, <strong><?php echo htmlspecialchars($_SESSION['username'] ?? 'Usuário'); ?></strong>. Utilize o menu acima para gerenciar as diferentes seções do sistema.</p>

<div class="dashboard-charts">
    <div class="chart-container">
        <h3>Ramais por Tipo <span id="totalByType" class="chart-total"></span></h3>
        <canvas id="extensionsByTypeChart"></canvas>
    </div>
    <div class="chart-container">
        <h3>Status dos Ramais <span id="totalByStatus" class="chart-total"></span></h3>
        <canvas id="extensionsByStatusChart"></canvas>
    </div>
</div>

<?php require_once __DIR__ . '/../src/includes/footer_admin.php'; ?>
