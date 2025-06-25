<?php require_once __DIR__ . '/src/includes/header_public.php'; ?>

<main class="container">
    <div class="search-container">
        <input type="text" id="search-input" class="form-control" placeholder="Buscar por nome, ramal ou setor...">
        <button id="clear-search-btn" class="btn btn-secondary">Limpar</button>
    </div>

    <div id="extension-list-area" class="accordion-container">
        <!-- Accordions will be dynamically inserted here by JavaScript -->
        <!-- Fallback message if JS is disabled or fails -->
        <noscript>
            <p>JavaScript é necessário para visualizar a lista de ramais.</p>
        </noscript>
        <div id="loading-message" style="display: none; text-align: center; padding: 20px;">Carregando ramais...</div>
        <div id="error-message" style="display: none; text-align: center; padding: 20px; color: red;"></div>
    </div>
</main>

<?php require_once __DIR__ . '/src/includes/footer_public.php'; ?>
