// public/js/main.js
document.addEventListener('DOMContentLoaded', function() {
    const extensionListArea = document.getElementById('extension-list-area');
    const searchInput = document.getElementById('search-input');
    const clearSearchBtn = document.getElementById('clear-search-btn');
    const loadingMessage = document.getElementById('loading-message');
    const errorMessageDiv = document.getElementById('error-message');

    let allSectorsData = []; // To store sectors for search fallback

    function displayError(message) {
        errorMessageDiv.textContent = message;
        errorMessageDiv.style.display = 'block';
        if (loadingMessage) loadingMessage.style.display = 'none';
        extensionListArea.innerHTML = ''; // Clear previous content if error occurs
    }

    function showLoading(show = true) {
        if (loadingMessage) loadingMessage.style.display = show ? 'block' : 'none';
        if (show) {
             extensionListArea.innerHTML = ''; // Clear before showing loading for new list
             errorMessageDiv.style.display = 'none'; // Hide error message
        }
    }

    async function fetchSectors() {
        showLoading(true);
        try {
            const response = await fetch('/ajax/get_sectors.php');
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const result = await response.json();
            showLoading(false);

            if (result.success && result.data) { // Check result.data as well
                allSectorsData = result.data;
                if (allSectorsData.length > 0) {
                    renderSectors(allSectorsData);
                } else {
                     extensionListArea.innerHTML = '<p>Nenhum setor cadastrado no momento.</p>';
                }
            } else {
                displayError(result.message || 'Falha ao carregar setores. A resposta do servidor não foi bem-sucedida.');
            }
        } catch (error) {
            console.error('Error fetching sectors:', error);
            showLoading(false);
            displayError('Erro de conexão ao tentar carregar os setores. Verifique sua internet ou tente mais tarde.');
        }
    }

    function renderSectors(sectors) {
        extensionListArea.innerHTML = ''; // Clear previous content or loading message
        errorMessageDiv.style.display = 'none'; // Clear error messages
        sectors.forEach(sector => {
            const accordionItem = document.createElement('div');
            accordionItem.classList.add('accordion-item');
            accordionItem.innerHTML = `
                <div class="accordion-header" data-sector-id="${sector.id}">
                    <span>${escapeHTML(sector.name)}</span>
                    <span class="arrow">&#9654;</span> <!-- Right pointing triangle -->
                </div>
                <div class="accordion-content">
                    <div class="loading-extensions" style="padding:15px 20px;">Carregando ramais do setor...</div>
                </div>
            `;
            extensionListArea.appendChild(accordionItem);
        });
    }

    async function fetchExtensionsForSector(sectorId, contentDiv) {
        try {
            const response = await fetch(`/ajax/get_extensions_by_sector.php?sector_id=${sectorId}`);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const result = await response.json();

            if (result.success && result.data) {
                if (result.data.length > 0) {
                    const ul = document.createElement('ul');
                    result.data.forEach(ext => {
                        const li = document.createElement('li');
                        li.innerHTML = `<span class="name">${escapeHTML(ext.person_name)}</span> <span class="extension">${escapeHTML(ext.extension_number)}</span>`;
                        ul.appendChild(li);
                    });
                    contentDiv.innerHTML = ''; // Clear loading message
                    contentDiv.appendChild(ul);
                } else {
                    contentDiv.innerHTML = '<p class="no-extensions">Nenhum ramal atribuído neste setor.</p>';
                }
            } else {
                 contentDiv.innerHTML = `<p class="no-extensions">${escapeHTML(result.message) || 'Falha ao carregar ramais para este setor.'}</p>`;
            }
        } catch (error) {
            console.error('Error fetching extensions for sector:', error);
            contentDiv.innerHTML = '<p class="no-extensions">Erro ao carregar ramais. Tente novamente.</p>';
        }
    }

    extensionListArea.addEventListener('click', function(event) {
        const header = event.target.closest('.accordion-header');
        if (header) {
            const item = header.parentElement;
            const content = item.querySelector('.accordion-content');
            const sectorId = header.dataset.sectorId;

            // Do not toggle if the click was on something that is not the header itself (e.g. if header had buttons)
            if (!event.target.matches('.accordion-header') && !event.target.parentElement.matches('.accordion-header')) {
                return;
            }

            item.classList.toggle('active');
            if (item.classList.contains('active')) {
                content.style.display = 'block';
                header.querySelector('.arrow').innerHTML = '&#9660;'; // Down arrow
                // Fetch extensions only if content hasn't been loaded yet
                if (!content.dataset.loaded) {
                    fetchExtensionsForSector(sectorId, content);
                    content.dataset.loaded = 'true';
                }
            } else {
                content.style.display = 'none';
                header.querySelector('.arrow').innerHTML = '&#9654;'; // Right arrow
            }
        }
    });

    let searchTimeout = null;
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            performSearch(searchInput.value.trim());
        }, 300); // Debounce search: wait 300ms after user stops typing
    });

    clearSearchBtn.addEventListener('click', function() {
        searchInput.value = '';
        errorMessageDiv.style.display = 'none'; // Clear any previous search error
        if (allSectorsData.length > 0) {
            renderSectors(allSectorsData); // Re-render all sectors
        } else {
            fetchSectors(); // Or fetch if initial load failed or was empty
        }
    });

    async function performSearch(term) {
        if (!term) {
            // If search term is empty, show all sectors (collapsed)
            errorMessageDiv.style.display = 'none';
            if (allSectorsData.length > 0) {
                renderSectors(allSectorsData);
            } else {
                fetchSectors(); // Fetch if not already loaded
            }
            return;
        }

        showLoading(true);
        try {
            const response = await fetch(`/ajax/search_extensions.php?term=${encodeURIComponent(term)}`);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const result = await response.json();
            showLoading(false);

            if (result.success && result.data) {
                renderSearchResults(result.data, term);
            } else {
                 // Prefer message from server if available and meaningful
                const message = result.message || `Nenhum resultado encontrado para "${escapeHTML(term)}".`;
                displayError(message); // Use displayError to show this message where extensionListArea is
                extensionListArea.innerHTML = `<p>${escapeHTML(message)}</p>`; // Also put it in the list area
            }
        } catch (error) {
            console.error('Search error:', error);
            showLoading(false);
            displayError('Erro ao realizar a busca. Verifique sua conexão ou tente mais tarde.');
        }
    }

    function renderSearchResults(results, term) {
        extensionListArea.innerHTML = ''; // Clear previous list or loading message
        errorMessageDiv.style.display = 'none'; // Clear error messages

        if (results.length === 0) {
            extensionListArea.innerHTML = `<p>Nenhum resultado encontrado para "<strong>${escapeHTML(term)}</strong>".</p>`;
            return;
        }

        // Group results by sector for better readability
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
            htmlContent += `<div class="accordion-item search-result-sector">`;
            htmlContent += `<div class="accordion-header static"><span>${escapeHTML(sectorName)}</span></div>`; // Static, not clickable for now
            htmlContent += `<div class="accordion-content" style="display: block;"><ul>`; // Display content directly for search

            groupedResults[sectorName].forEach(item => {
                htmlContent += `
                    <li>
                        <span class="name">${escapeHTML(item.person_name)}</span>
                        <span class="extension">${escapeHTML(item.extension_number)}</span>
                    </li>`;
            });
            htmlContent += `</ul></div></div>`;
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

    // Initial load of all sectors
    fetchSectors();
});
