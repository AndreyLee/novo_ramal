// public/js/main.js
document.addEventListener('DOMContentLoaded', function() {
    const extensionListArea = document.getElementById('extension-list-area');
    const searchInput = document.getElementById('search-input');
    const clearSearchBtn = document.getElementById('clear-search-btn');
    const loadingMessage = document.getElementById('loading-message');
    const errorMessageDiv = document.getElementById('error-message');

    // let allSectorsData = []; // Potentially keep for search fallback if search is not table-based

    function displayError(message) {
        errorMessageDiv.textContent = message;
        errorMessageDiv.style.display = 'block';
        if (loadingMessage) loadingMessage.style.display = 'none';
        extensionListArea.innerHTML = `<p class="error-message">${escapeHTML(message)}</p>`; // Clear previous content and show error
    }

    function showLoading(show = true) {
        if (loadingMessage) loadingMessage.style.display = show ? 'block' : 'none';
        if (show) {
             extensionListArea.innerHTML = ''; // Clear before showing loading for new list
             errorMessageDiv.style.display = 'none'; // Hide error message
        }
    }

    async function fetchAndRenderDashboardTables() {
        showLoading(true);
        try {
            const response = await fetch('/ajax/get_dashboard_data.php');
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const result = await response.json();
            showLoading(false);

            if (result.success && result.data) {
                if (result.data.length > 0) {
                    renderDashboardTables(result.data);
                } else {
                    extensionListArea.innerHTML = `<p>${escapeHTML(result.message) || 'Nenhum setor para exibir no momento.'}</p>`;
                }
            } else {
                displayError(result.message || 'Falha ao carregar dados do dashboard.');
            }
        } catch (error) {
            console.error('Error fetching dashboard data:', error);
            showLoading(false);
            displayError('Erro de conexão ao tentar carregar os dados. Verifique sua internet ou tente mais tarde.');
        }
    }

    function renderDashboardTables(sectorsData) {
        extensionListArea.innerHTML = ''; // Clear previous content or loading message
        errorMessageDiv.style.display = 'none'; // Clear error messages

        sectorsData.forEach(sector => {
            const sectorContainer = document.createElement('div');
            sectorContainer.classList.add('sector-table-container'); // For styling

            const title = document.createElement('h3'); // Or h4
            title.classList.add('sector-title');
            title.textContent = escapeHTML(sector.sector_name);
            sectorContainer.appendChild(title);

            if (sector.extensions && sector.extensions.length > 0) {
                const table = document.createElement('table');
                table.classList.add('extension-table'); // For styling

                const thead = table.createTHead();
                const headerRow = thead.insertRow();
                const thPerson = document.createElement('th');
                thPerson.textContent = 'Pessoa';
                headerRow.appendChild(thPerson);
                const thExtension = document.createElement('th');
                thExtension.textContent = 'Ramal';
                headerRow.appendChild(thExtension);

                const tbody = table.createTBody();
                sector.extensions.forEach(ext => {
                    const row = tbody.insertRow();
                    const cellPerson = row.insertCell();
                    cellPerson.textContent = escapeHTML(ext.person_name);
                    const cellExtension = row.insertCell();
                    cellExtension.textContent = escapeHTML(ext.extension_number);
                });
                sectorContainer.appendChild(table);
            } else {
                const noExtensionsMessage = document.createElement('p');
                noExtensionsMessage.classList.add('no-extensions-message');
                noExtensionsMessage.textContent = 'Nenhum ramal atribuído neste setor.';
                sectorContainer.appendChild(noExtensionsMessage);
            }
            extensionListArea.appendChild(sectorContainer);
        });
    }

    // Comment out or remove original accordion logic if tables replace it completely on initial load
    /*
    async function fetchSectors() { ... }
    function renderSectors(sectors) { ... }
    async function fetchExtensionsForSector(sectorId, contentDiv) { ... }
    extensionListArea.addEventListener('click', function(event) { ... accordion logic ... });
    */

    let searchTimeout = null;
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            performSearch(searchInput.value.trim());
        }, 300);
    });

    clearSearchBtn.addEventListener('click', function() {
        searchInput.value = '';
        errorMessageDiv.style.display = 'none';
        fetchAndRenderDashboardTables(); // Reload dashboard tables
    });

    async function performSearch(term) {
        // If search term is empty, restore dashboard tables
        if (!term) {
            fetchAndRenderDashboardTables();
            return;
        }

        showLoading(true);
        try {
            // Assuming search_extensions.php returns data in a suitable format for renderSearchResults
            // or renderSearchResults might need adjustment if search results format changes.
            const response = await fetch(`/ajax/search_extensions.php?term=${encodeURIComponent(term)}`);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const result = await response.json();
            showLoading(false);

            if (result.success && result.data) {
                renderSearchResults(result.data, term);
            } else {
                const message = result.message || `Nenhum resultado encontrado para "${escapeHTML(term)}".`;
                // Display error within the list area itself for search
                extensionListArea.innerHTML = `<p>${escapeHTML(message)}</p>`;
                errorMessageDiv.style.display = 'none'; // Hide the global error div if showing message in list
            }
        } catch (error) {
            console.error('Search error:', error);
            showLoading(false);
            displayError('Erro ao realizar a busca. Verifique sua conexão ou tente mais tarde.');
        }
    }

    function renderSearchResults(results, term) {
        extensionListArea.innerHTML = ''; // Clear previous list or loading message
        errorMessageDiv.style.display = 'none';

        if (results.length === 0) {
            extensionListArea.innerHTML = `<p>Nenhum resultado encontrado para "<strong>${escapeHTML(term)}</strong>".</p>`;
            return;
        }

        // Current search result rendering (groups by sector, uses ul/li)
        // This might need to be adapted if the desired search result display is also tables.
        // For now, keeping the existing search result structure.
        const groupedResults = results.reduce((acc, item) => {
            const sector = item.sector_name || 'Setor não especificado';
            if (!acc[sector]) {
                acc[sector] = [];
            }
            acc[sector].push(item);
            return acc;
        }, {});

        let htmlContent = `<h3>Resultados da busca por "${escapeHTML(term)}":</h3>`;

        for (const sectorName in groupedResults) {
            // Using a simpler div structure for search results instead of full tables for now
            // to distinguish from the main dashboard view.
            htmlContent += `<div class="search-result-sector-group">`;
            htmlContent += `<h4>${escapeHTML(sectorName)}</h4>`;
            htmlContent += `<ul class="search-result-list">`;

            groupedResults[sectorName].forEach(item => {
                htmlContent += `
                    <li>
                        <span class="name">${escapeHTML(item.person_name)}</span>
                        <span class="extension">${escapeHTML(item.extension_number)}</span>
                    </li>`;
            });
            htmlContent += `</ul></div>`;
        }
        extensionListArea.innerHTML = htmlContent;
    }

    function escapeHTML(str) {
        if (str === null || str === undefined) return '';
        return String(str).replace(/[&<>"']/g, function (match) {
            return {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;'
            }[match];
        });
    }

    // Initial load: fetch and render dashboard tables
    fetchAndRenderDashboardTables();
});
