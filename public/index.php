<?php require_once __DIR__ . '/src/includes/header_public.php'; ?>

<main class="container">
    <div class="search-container public-search-container">
        <input type="text" id="public-search-input" class="form-control" placeholder="Buscar por nome, ramal ou setor...">
        <button id="public-clear-search-btn" class="btn btn-secondary">Limpar</button>
    </div>
    <h2>Lista de Ramais por Setor</h2>
    <div id="sectors-container" class="sectors-grid-container">
        <!-- Colunas de setores serão inseridas aqui pelo JavaScript -->
        <p id="loading-sectors" style="text-align:center; width:100%;">Carregando dados...</p>
        <p id="error-sectors" class="error-message" style="display:none; width:100%;"></p>
    </div>
</main>

<script src="/js/main.js"></script>
<?php require_once __DIR__ . '/src/includes/footer_public.php'; ?>
