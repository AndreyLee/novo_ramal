// public/js/admin_main.js
document.addEventListener('DOMContentLoaded', function() {
    console.log('Admin JavaScript loaded successfully.');

    if (document.getElementById('persons-table')) {
        initPersonManagement();
    }
    if (document.getElementById('extensions-table')) {
        initExtensionManagement();
    }
    if (document.getElementById('sectors-table')) {
        initSectorManagement();
    }
    if (document.getElementById('users-table')) {
        initUserManagement();
    }
    if (document.getElementById('extensionsByTypeChart') && document.getElementById('extensionsByStatusChart')) {
        initDashboardCharts();
    }
});

// --- Dashboard Charts --- //
let dashboardChartsInitialized = false;
let extensionsByTypeChartInstance = null;
let extensionsByStatusChartInstance = null;

function initDashboardCharts() {
    console.log("initDashboardCharts chamada. Initialized:", dashboardChartsInitialized);
    if (dashboardChartsInitialized) {
        console.log("Dashboard já inicializado, retornando.");
        return;
    }

    const ctxByTypeElement = document.getElementById('extensionsByTypeChart');
    const ctxByStatusElement = document.getElementById('extensionsByStatusChart');

    if (!ctxByTypeElement || !ctxByStatusElement) {
        console.error("Dashboard chart canvas elements not found! Saindo de initDashboardCharts.");
        // Não resetar a flag aqui, se o DOMContentLoaded disparar de novo com os elementos, ok.
        // Se for chamado manualmente, quem chama deve garantir que os elementos existam.
        return;
    }

    console.log("Canvas elements encontrados. Setando dashboardChartsInitialized = true.");
    dashboardChartsInitialized = true;

    const ctxByType = ctxByTypeElement.getContext('2d');
    const ctxByStatus = ctxByStatusElement.getContext('2d');

    async function fetchChartData() {
        console.log("fetchChartData chamada.");
        try {
            const response = await fetch('/admin/actions/list_all_extensions_admin.php');
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            const result = await response.json();

            if (result.success && result.data) {
                console.log("Dados para gráficos recebidos, chamando processAndRenderCharts.");
                processAndRenderCharts(result.data);
            } else {
                console.error('Falha ao carregar dados para gráficos:', result.message);
                renderChartError(ctxByType, 'Falha ao carregar dados para gráfico de tipos.');
                renderChartError(ctxByStatus, 'Falha ao carregar dados para gráfico de status.');
            }
        } catch (error) {
            console.error('Erro ao buscar dados para gráficos:', error);
            renderChartError(ctxByType, 'Erro de conexão ao carregar dados (tipos).');
            renderChartError(ctxByStatus, 'Erro de conexão ao carregar dados (status).');
        }
    }

    function processAndRenderCharts(extensions) {
        console.log("processAndRenderCharts chamada.");
        // Processar dados para Ramais por Tipo
        const typeCounts = extensions.reduce((acc, ext) => {
            acc[ext.type] = (acc[ext.type] || 0) + 1;
            return acc;
        }, {});
        const typeLabels = Object.keys(typeCounts);
        const typeData = Object.values(typeCounts);
        const totalByType = typeData.reduce((sum, current) => sum + current, 0);
        const totalByTypeElement = document.getElementById('totalByType');
        if (totalByTypeElement) {
            totalByTypeElement.textContent = `(Total: ${totalByType})`;
        }

        if (extensionsByTypeChartInstance) {
            console.log("Destruindo instância anterior do gráfico de tipos.");
            extensionsByTypeChartInstance.destroy();
        }
        console.log("Criando novo gráfico de tipos.");
        extensionsByTypeChartInstance = new Chart(ctxByType, {
            type: 'pie',
            data: {
                labels: typeLabels,
                datasets: [{
                    label: 'Ramais por Tipo',
                    data: typeData,
                    backgroundColor: [
                        'rgba(54, 162, 235, 0.7)', // Azul
                        'rgba(255, 206, 86, 0.7)' // Amarelo
                        // Adicione mais cores se houver mais tipos
                    ],
                    borderColor: [
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 206, 86, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed !== null) {
                                    label += context.parsed;
                                }
                                return label;
                            }
                        }
                    },
                    datalabels: {
                        formatter: (value, context) => {
                            return value;
                        },
                        color: '#fff',
                        font: {
                            weight: 'bold'
                        }
                    }
                }
            }
        });

        // Processar dados para Status dos Ramais
        const statusCounts = extensions.reduce((acc, ext) => {
            const currentStatus = ext.status === 'Atribuído' ? 'Em Uso' : 'Vago';
            acc[currentStatus] = (acc[currentStatus] || 0) + 1;
            return acc;
        }, {});
        const statusLabels = Object.keys(statusCounts);
        const statusData = Object.values(statusCounts);
        const totalByStatus = statusData.reduce((sum, current) => sum + current, 0);
        const totalByStatusElement = document.getElementById('totalByStatus');
        if (totalByStatusElement) {
            totalByStatusElement.textContent = `(Total: ${totalByStatus})`;
        }

        if (extensionsByStatusChartInstance) {
            console.log("Destruindo instância anterior do gráfico de status.");
            extensionsByStatusChartInstance.destroy();
        }
        console.log("Criando novo gráfico de status.");
        extensionsByStatusChartInstance = new Chart(ctxByStatus, {
            type: 'pie',
            data: {
                labels: statusLabels,
                datasets: [{
                    label: 'Status dos Ramais',
                    data: statusData,
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.7)', // Vermelho (Em Uso)
                        'rgba(75, 192, 192, 0.7)'  // Verde (Vago)
                        // Adicione mais cores se houver mais status
                    ],
                    borderColor: [
                        'rgba(255, 99, 132, 1)',
                        'rgba(75, 192, 192, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                 plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed !== null) {
                                    label += context.parsed;
                                }
                                return label;
                            }
                        }
                    },
                    datalabels: {
                        formatter: (value, context) => {
                            return value;
                        },
                        color: '#fff',
                        font: {
                            weight: 'bold'
                        }
                    }
                }
            }
        });
    }

    function renderChartError(context, message) {
        // Exibe uma mensagem de erro simples no lugar do gráfico
        const canvas = context.canvas;
        context.font = "16px Arial";
        context.fillStyle = "red";
        context.textAlign = "center";
        context.fillText(message, canvas.width / 2, canvas.height / 2);
    }

    fetchChartData();
}

// --- Person Management --- //
function initPersonManagement() {
    const addPersonForm = document.getElementById('add-person-form');
    const personSectorSelect = document.getElementById('person-sector-id'); // Novo select
    const personsTableBody = document.querySelector('#persons-table tbody');
    const personsTable = document.getElementById('persons-table');
    const personsLoading = document.getElementById('persons-loading');
    const personsError = document.getElementById('persons-error');
    const addPersonMessage = document.getElementById('add-person-message');

    const editPersonModal = document.getElementById('edit-person-modal');
    const editPersonForm = document.getElementById('edit-person-form');
    const editPersonIdInput = document.getElementById('edit-person-id');
    const editPersonNameInput = document.getElementById('edit-person-name');
    const editPersonSectorSelect = document.getElementById('edit-person-sector-id'); // Novo select para edição
    const editPersonMessage = document.getElementById('edit-person-message');

    const assignExtensionModal = document.getElementById('assign-extension-modal');
    const assignExtensionForm = document.getElementById('assign-extension-form');
    const assignPersonIdInput = document.getElementById('assign-person-id');
    const assignPersonNameDisplay = document.getElementById('assign-person-name-display');
    const assignCurrentExtensionDisplay = document.getElementById('assign-current-extension-display');
    const assignExtensionSelect = document.getElementById('assign-extension-id');
    const unassignExtensionBtn = document.getElementById('unassign-extension-btn');
    const assignModalTitle = document.getElementById('assign-modal-title');
    const assignExtensionMessage = document.getElementById('assign-extension-message');

    function showMessage(element, message, isSuccess) {
        if (!element) return;
        element.textContent = message;
        element.className = 'form-message ' + (isSuccess ? 'success' : 'error');
        element.style.display = 'block';
        setTimeout(() => { element.style.display = 'none'; }, 5000);
    }

    function displayPersonsError(message) {
        if(personsError) personsError.textContent = message;
        if(personsError) personsError.style.display = 'block';
        if(personsLoading) personsLoading.style.display = 'none';
        if(personsTable) personsTable.style.display = 'none';
    }

    async function fetchPersons() {
        if(personsLoading) personsLoading.style.display = 'block';
        if(personsError) personsError.style.display = 'none';
        if(personsTable) personsTable.style.display = 'none';

        try {
            const response = await fetch('/admin/actions/list_persons_with_extensions.php');
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            const result = await response.json();

            if(personsLoading) personsLoading.style.display = 'none';
            if (result.success) {
                if(personsTable) personsTable.style.display = 'table';
                renderPersonsTable(result.data);
            } else {
                displayPersonsError(result.message || 'Falha ao carregar lista de pessoas.');
            }
        } catch (error) {
            console.error('Error fetching persons:', error);
            displayPersonsError('Erro de conexão ao carregar pessoas. Verifique o console para mais detalhes.');
        }
    }

    function renderPersonsTable(persons) {
        if(!personsTableBody) return;
        personsTableBody.innerHTML = '';
        if (!persons || persons.length === 0) {
            personsTableBody.innerHTML = '<tr><td colspan="5" style="text-align:center;">Nenhuma pessoa encontrada.</td></tr>'; // Colspan atualizado para 5
            return;
        }
        persons.forEach(person => {
            const row = personsTableBody.insertRow();
            const currentExtNum = person.assigned_extension_number || 'Nenhum';
            const currentExtId = person.assigned_extension_id || '';
            const buttonText = currentExtId ? 'Alterar/Desatribuir Ramal' : 'Atribuir Ramal';

            row.innerHTML = `
                <td>${escapeJSHTML(person.id)}</td>
                <td>${escapeJSHTML(person.name)}</td>
                <td>${escapeJSHTML(person.sector_name || 'N/A')}</td>
                <td data-extension-id="${escapeJSHTML(currentExtId)}">
                    ${escapeJSHTML(currentExtNum)}
                </td>
                <td class="actions">
                    <button class="btn btn-sm btn-warning edit-person-btn"
                            data-id="${person.id}"
                            data-name="${escapeJSHTML(person.name)}"
                            data-sector-id="${escapeJSHTML(person.sector_id || '')}">Editar</button>
                    <button class="btn btn-sm btn-danger delete-person-btn" data-id="${person.id}" data-name="${escapeJSHTML(person.name)}">Excluir</button>
                    <button class="btn btn-sm btn-info assign-extension-btn"
                            data-person-id="${person.id}"
                            data-person-name="${escapeJSHTML(person.name)}"
                            data-current-ext-id="${escapeJSHTML(currentExtId)}"
                            data-current-ext-num="${escapeJSHTML(currentExtNum)}">
                        ${buttonText}
                    </button>
                </td>
            `;
        });
    }

    if(addPersonForm) {
        addPersonForm.addEventListener('submit', async function(event) {
            event.preventDefault();
            const formData = new FormData(addPersonForm);
            const name = formData.get('name').trim();
            if (!name) {
                showMessage(addPersonMessage, 'Nome da pessoa não pode ser vazio.', false);
                return;
            }

            try {
                const response = await fetch('/admin/actions/add_person.php', { method: 'POST', body: formData });
                const result = await response.json();
                showMessage(addPersonMessage, result.message, result.success);
                if (result.success) {
                    addPersonForm.reset();
                    fetchPersons();
                }
            } catch (error) {
                console.error('Error adding person:', error);
                showMessage(addPersonMessage, 'Erro de conexão ao adicionar pessoa.', false);
            }
        });
    }

    if (personsTableBody) {
        personsTableBody.addEventListener('click', function(event) {
            const target = event.target;
            if (target.classList.contains('edit-person-btn')) {
                openEditPersonModal(target.dataset.id, target.dataset.name, target.dataset.sectorId);
            } else if (target.classList.contains('delete-person-btn')) {
                if (confirm(`Tem certeza que deseja excluir "${escapeJSHTML(target.dataset.name)}" (ID: ${target.dataset.id})? Esta ação não pode ser desfeita.`)) {
                    deletePerson(target.dataset.id);
                }
            } else if (target.classList.contains('assign-extension-btn')) {
                openAssignExtensionModal(target.dataset.personId, target.dataset.personName, target.dataset.currentExtId, target.dataset.currentExtNum);
            }
        });
    }

    async function openEditPersonModal(id, name, currentSectorId) {
        if (!editPersonModal || !editPersonSectorSelect) return;
        editPersonIdInput.value = id;
        editPersonNameInput.value = name;
        if(editPersonMessage) editPersonMessage.style.display = 'none';

        // Popular o select de setores para edição
        editPersonSectorSelect.innerHTML = '<option value="">Carregando setores...</option>';
        try {
            const response = await fetch('/admin/actions/get_sectors_list.php?t=' + new Date().getTime());
            const result = await response.json();
            editPersonSectorSelect.innerHTML = '<option value="">-- Selecione um Setor --</option>';
            if (result.success && result.data) {
                if (result.data.length > 0) {
                    result.data.forEach(sector => {
                        const option = document.createElement('option');
                        option.value = sector.id;
                        option.textContent = escapeJSHTML(sector.name);
                        if (sector.id == currentSectorId) { // Pré-selecionar o setor atual
                            option.selected = true;
                        }
                        editPersonSectorSelect.appendChild(option);
                    });
                } else {
                    editPersonSectorSelect.innerHTML = '<option value="">Nenhum setor cadastrado</option>';
                }
            } else {
                editPersonSectorSelect.innerHTML = `<option value="">Falha ao carregar: ${escapeJSHTML(result.message || 'Erro')}</option>`;
            }
        } catch (error) {
            console.error('Error fetching sectors for edit person modal:', error);
            editPersonSectorSelect.innerHTML = '<option value="">Erro ao carregar setores</option>';
        }

        editPersonModal.style.display = 'block';
    }

    if (editPersonForm) {
        editPersonForm.addEventListener('submit', async function(event) {
            event.preventDefault();
            const formData = new FormData(editPersonForm);
            const name = formData.get('name').trim();
            if (!name) {
                showMessage(editPersonMessage, 'Nome da pessoa não pode ser vazio.', false);
                return;
            }

            try {
                const response = await fetch('/admin/actions/update_person.php', { method: 'POST', body: formData });
                const result = await response.json();
                showMessage(editPersonMessage, result.message, result.success);
                if (result.success) {
                    setTimeout(() => closeModal('edit-person-modal'), 1000);
                    fetchPersons();
                }
            } catch (error) {
                console.error('Error updating person:', error);
                showMessage(editPersonMessage, 'Erro de conexão ao atualizar pessoa.', false);
            }
        });
    }

    async function deletePerson(id) {
        try {
            const formData = new FormData();
            formData.append('id', id);
            const response = await fetch('/admin/actions/delete_person.php', { method: 'POST', body: formData });
            const result = await response.json();
            alert(result.message);
            if (result.success) {
                fetchPersons();
            }
        } catch (error) {
            console.error('Error deleting person:', error);
            alert('Erro de conexão ao excluir pessoa.');
        }
    }

    async function openAssignExtensionModal(personId, personName, currentExtensionId, currentExtensionNumber) {
        if (!assignExtensionModal) return;
        assignPersonIdInput.value = personId;
        assignPersonNameDisplay.textContent = personName;
        assignCurrentExtensionDisplay.textContent = currentExtensionNumber || 'Nenhum';
        assignModalTitle.textContent = (currentExtensionId && currentExtensionId !== "null" && currentExtensionId !== "") ? `Alterar/Desatribuir Ramal de ${personName}` : `Atribuir Ramal para ${personName}`;
        if(assignExtensionMessage) assignExtensionMessage.style.display = 'none';

        if (currentExtensionId && currentExtensionId !== "null" && currentExtensionId !== "") {
            unassignExtensionBtn.style.display = 'inline-block';
            unassignExtensionBtn.dataset.extensionIdToUnassign = currentExtensionId;
        } else {
            unassignExtensionBtn.style.display = 'none';
        }

        assignExtensionSelect.innerHTML = '<option value="">Carregando ramais vagos...</option>';
        try {
            const response = await fetch('/admin/actions/get_unassigned_extensions.php');
            const result = await response.json();
            assignExtensionSelect.innerHTML = '<option value="">-- Sem Alteração / Escolha um Ramal Vago --</option>';
            if (result.success && result.data) {
                if (result.data.length > 0) {
                    result.data.forEach(ext => {
                        const option = document.createElement('option');
                        option.value = ext.id;
                        option.textContent = `${ext.number} (${ext.type})`;
                        assignExtensionSelect.appendChild(option);
                    });
                } else {
                     assignExtensionSelect.innerHTML = '<option value="">Nenhum ramal vago disponível</option>';
                }
            } else {
                assignExtensionSelect.innerHTML = `<option value="">Falha ao carregar: ${escapeJSHTML(result.message || 'Erro desconhecido')}</option>`;
            }
        } catch (error) {
            console.error('Error fetching unassigned extensions:', error);
            assignExtensionSelect.innerHTML = '<option value="">Erro ao carregar ramais</option>';
        }
        assignExtensionModal.style.display = 'block';
    }

    if (assignExtensionForm) {
        assignExtensionForm.addEventListener('submit', async function(event) {
            event.preventDefault();
            const personId = assignPersonIdInput.value;
            const newExtensionId = assignExtensionSelect.value;

            if (!newExtensionId) {
                showMessage(assignExtensionMessage, 'Por favor, selecione um ramal para atribuir ou cancele.', false);
                return;
            }

            const formData = new FormData();
            formData.append('person_id', personId);
            formData.append('extension_id', newExtensionId);

            try {
                const response = await fetch('/admin/actions/assign_extension.php', { method: 'POST', body: formData });
                const result = await response.json();
                showMessage(assignExtensionMessage, result.message, result.success);
                if (result.success) {
                    setTimeout(() => closeModal('assign-extension-modal'), 1000);
                    fetchPersons();
                }
            } catch (error) {
                console.error('Error assigning extension:', error);
                showMessage(assignExtensionMessage, 'Erro de conexão ao atribuir ramal.', false);
            }
        });
    }

    if(unassignExtensionBtn) {
        unassignExtensionBtn.addEventListener('click', async function() {
            const extensionIdToUnassign = this.dataset.extensionIdToUnassign;
            const personName = assignPersonNameDisplay.textContent;

            if (!extensionIdToUnassign) return;

            if (confirm(`Tem certeza que deseja desatribuir o ramal atual de "${escapeJSHTML(personName)}"?`)) {
                const formData = new FormData();
                formData.append('extension_id', extensionIdToUnassign);
                try {
                    const response = await fetch('/admin/actions/unassign_extension.php', { method: 'POST', body: formData });
                    const result = await response.json();
                    showMessage(assignExtensionMessage, result.message, result.success);
                    if (result.success) {
                         setTimeout(() => closeModal('assign-extension-modal'), 1000);
                        fetchPersons();
                    }
                } catch (error) {
                    console.error('Error unassigning extension:', error);
                    showMessage(assignExtensionMessage, 'Erro de conexão ao desatribuir ramal.', false);
                }
            }
        });
    }

    async function loadSectorsForPersonForm() {
        if (!personSectorSelect) return;
        try {
            const response = await fetch('/admin/actions/get_sectors_list.php?t=' + new Date().getTime());
            const result = await response.json();
            personSectorSelect.innerHTML = '<option value="">-- Selecione um Setor --</option>';
            if (result.success && result.data) {
                if (result.data.length > 0) {
                    result.data.forEach(sector => {
                        const option = document.createElement('option');
                        option.value = sector.id;
                        option.textContent = escapeJSHTML(sector.name);
                        personSectorSelect.appendChild(option);
                    });
                } else {
                    personSectorSelect.innerHTML = '<option value="">Nenhum setor cadastrado</option>';
                }
            } else {
                personSectorSelect.innerHTML = `<option value="">Falha ao carregar setores: ${escapeJSHTML(result.message || 'Erro')}</option>`;
            }
        } catch (error) {
            console.error('Error fetching sectors for person form:', error);
            personSectorSelect.innerHTML = '<option value="">Erro ao carregar setores</option>';
        }
    }

    if(addPersonForm && personSectorSelect) { // Garante que o select existe antes de tentar popular
        loadSectorsForPersonForm();
    }

    if(personsTableBody) fetchPersons();
}

// --- Extension Management (Super-Admin) --- //
function initExtensionManagement() {
    const extensionsTableBody = document.querySelector('#extensions-table tbody');
    const extensionsTable = document.getElementById('extensions-table');
    const extensionsLoading = document.getElementById('extensions-loading');
    const extensionsError = document.getElementById('extensions-error');

    const openAddModalBtn = document.getElementById('open-add-extension-modal-btn');
    const modal = document.getElementById('extension-modal');
    const modalTitle = document.getElementById('extension-modal-title');
    const form = document.getElementById('extension-form');
    const extensionIdInput = document.getElementById('extension-id');
    const numberInput = document.getElementById('extension-number');
    const typeSelect = document.getElementById('extension-type');
    // const sectorSelect = document.getElementById('extension-sector-id'); // Removido
    const statusSelect = document.getElementById('extension-status');
    const statusFormGroup = document.getElementById('status-form-group'); // Adicionado
    const personGroup = document.getElementById('person-assignment-group');
    const personSelect = document.getElementById('extension-person-id');
    const formMessage = document.getElementById('extension-form-message');


    const searchTermInput = document.getElementById('ext-search-term');
    const filterTypeSelect = document.getElementById('ext-filter-type');
    const filterStatusSelect = document.getElementById('ext-filter-status');
    const applyFiltersBtn = document.getElementById('apply-ext-filters-btn');
    const resetFiltersBtn = document.getElementById('reset-ext-filters-btn');

    // let sectorsCache = []; // Removido
    let personsCache = [];

    function showExtMessage(message, isSuccess) {
        if (!formMessage) return;
        formMessage.textContent = message;
        formMessage.className = 'form-message ' + (isSuccess ? 'success' : 'error');
        formMessage.style.display = 'block';
        setTimeout(() => { formMessage.style.display = 'none'; }, 5000);
    }

    function displayExtListError(message) {
        if(extensionsError) extensionsError.textContent = message;
        if(extensionsError) extensionsError.style.display = 'block';
        if(extensionsLoading) extensionsLoading.style.display = 'none';
        if(extensionsTable) extensionsTable.style.display = 'none';
    }

    async function fetchAndRenderExtensions() {
        if(extensionsLoading) extensionsLoading.style.display = 'block';
        if(extensionsError) extensionsError.style.display = 'none';
        if(extensionsTable) extensionsTable.style.display = 'none';

        const searchTerm = searchTermInput.value.trim();
        const filterType = filterTypeSelect.value;
        const filterStatus = filterStatusSelect.value;

        let queryParams = new URLSearchParams();
        if (searchTerm) queryParams.append('search_term', searchTerm);
        if (filterType) queryParams.append('filter_type', filterType);
        if (filterStatus) queryParams.append('filter_status', filterStatus);

        try {
            const response = await fetch(`/admin/actions/list_all_extensions_admin.php?${queryParams.toString()}`);
            if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
            const result = await response.json();

            if(extensionsLoading) extensionsLoading.style.display = 'none';
            if (result.success) {
                if(extensionsTable) extensionsTable.style.display = 'table';
                renderExtensionsTable(result.data);
            } else {
                displayExtListError(result.message || 'Falha ao carregar ramais.');
            }
        } catch (error) {
            console.error('Error fetching extensions:', error);
            displayExtListError('Erro de conexão ao carregar ramais: ' + error.message);
        }
    }

    function renderExtensionsTable(extensions) {
        if(!extensionsTableBody) return;
        extensionsTableBody.innerHTML = '';
        if (!extensions || extensions.length === 0) {
            extensionsTableBody.innerHTML = '<tr><td colspan="7" style="text-align:center;">Nenhum ramal encontrado com os filtros atuais.</td></tr>';
            return;
        }
        extensions.forEach(ext => {
            const row = extensionsTableBody.insertRow();
            row.innerHTML = `
                <td>${escapeJSHTML(ext.id)}</td>
                <td>${escapeJSHTML(ext.number)}</td>
                <td>${escapeJSHTML(ext.type)}</td>
                <td>${escapeJSHTML(ext.sector_name || 'N/A')}</td>
                <td>${escapeJSHTML(ext.status)}</td>
                <td>${escapeJSHTML(ext.person_name || (ext.status === 'Atribuído' ? '' : 'N/A'))}</td>
                <td class="actions">
                    <button class="btn btn-sm btn-warning edit-extension-btn" data-id="${ext.id}">Editar</button>
                    <button class="btn btn-sm btn-danger delete-extension-btn" data-id="${ext.id}" data-number="${escapeJSHTML(ext.number)}">Excluir</button>
                </td>
            `;
            row.querySelector('.edit-extension-btn').dataset.extensionData = JSON.stringify(ext);
        });
    }

    async function loadPersonsForModal() { // Renomeada e removida lógica de setores
        if (personsCache.length === 0) {
            try {
                const response = await fetch('/admin/actions/list_persons_with_extensions.php');
                const result = await response.json();
                if (result.success && result.data) personsCache = result.data;
                else console.warn("Failed to load persons for modal:", result.message);
            } catch (e) { console.error("Error loading persons for modal", e); }
        }
        personSelect.innerHTML = '<option value="">-- Nenhuma --</option>';
        personsCache.forEach(p => personSelect.add(new Option(escapeJSHTML(p.name), p.id)));
    }

    if(statusSelect) {
        statusSelect.addEventListener('change', function() {
            if(personGroup) personGroup.style.display = this.value === 'Atribuído' ? 'block' : 'none';
            if (this.value === 'Vago' && personSelect) {
                personSelect.value = "";
            }
        });
    }

    if(openAddModalBtn) {
        openAddModalBtn.addEventListener('click', async () => {
            if(form) form.reset();
            if(extensionIdInput) extensionIdInput.value = '';
            if(modalTitle) modalTitle.textContent = 'Adicionar Novo Ramal';
            if(personGroup) personGroup.style.display = 'none';
            if(statusFormGroup) statusFormGroup.style.display = 'none'; // Oculta o campo Status na adição
            if(statusSelect) statusSelect.value = 'Vago'; // Garante status Vago por padrão (valor ainda será enviado)
            if(personSelect) personSelect.value = '';
            // personGroup já é display:none por padrão, e status Vago não o mostrará
            await loadPersonsForModal(); // Carrega apenas pessoas
            showExtMessage('', true);
            if(modal) modal.style.display = 'block';
        });
    }

    if(extensionsTableBody) {
        extensionsTableBody.addEventListener('click', async function(event) {
            const target = event.target;
            if (target.classList.contains('edit-extension-btn')) {
                const data = JSON.parse(target.dataset.extensionData);
                if(form) form.reset();
                if(modalTitle) modalTitle.textContent = `Editar Ramal #${escapeJSHTML(data.number)}`;
                if(extensionIdInput) extensionIdInput.value = data.id;
                if(numberInput) numberInput.value = data.number;
                if(typeSelect) typeSelect.value = data.type;
                if(statusFormGroup) statusFormGroup.style.display = 'block'; // Mostra o campo Status na edição
                if(statusSelect) statusSelect.value = data.status;

                await loadPersonsForModal(); // Carrega apenas pessoas
                // if(sectorSelect) sectorSelect.value = data.sector_id || ''; // Removido - sectorSelect não existe mais

                if (data.status === 'Atribuído') {
                    if(personGroup) personGroup.style.display = 'block';
                    if(personSelect) personSelect.value = data.person_id || '';
                } else {
                    if(personGroup) personGroup.style.display = 'none';
                    if(personSelect) personSelect.value = '';
                }
                showExtMessage('', true);
                if(modal) modal.style.display = 'block';
            } else if (target.classList.contains('delete-extension-btn')) {
                const id = target.dataset.id;
                const number = target.dataset.number;
                if (confirm(`Tem certeza que deseja excluir o ramal ${number} (ID: ${id})? Esta ação não pode ser desfeita.`)) {
                    deleteExtension(id);
                }
            }
        });
    }

    if(form) {
        form.addEventListener('submit', async function(event) {
            event.preventDefault();
            const formData = new FormData(form);
            const id = extensionIdInput.value;
            const url = id ? '/admin/actions/update_extension.php' : '/admin/actions/add_extension.php';

            if (formData.get('status') === 'Vago') {
                formData.set('person_id', '');
            } else if (formData.get('status') === 'Atribuído' && !formData.get('person_id')) {
                showExtMessage('Para status "Atribuído", uma pessoa deve ser selecionada.', false);
                return;
            }

            try {
                const response = await fetch(url, { method: 'POST', body: formData });
                const result = await response.json();
                showExtMessage(result.message, result.success);
                if (result.success) {
                    setTimeout(() => closeModal('extension-modal'), 1000);
                    fetchAndRenderExtensions();
                }
            } catch (error) {
                console.error('Error saving extension:', error);
                showExtMessage('Erro de conexão ao salvar ramal.', false);
            }
        });
    }

    async function deleteExtension(id) {
        const formData = new FormData();
        formData.append('id', id);
        try {
            const response = await fetch('/admin/actions/delete_extension.php', { method: 'POST', body: formData });
            const result = await response.json();
            alert(result.message);
            if (result.success) {
                fetchAndRenderExtensions();
            }
        } catch (error) {
            console.error('Error deleting extension:', error);
            alert('Erro de conexão ao excluir ramal.');
        }
    }

    if(applyFiltersBtn) applyFiltersBtn.addEventListener('click', fetchAndRenderExtensions);
    if(resetFiltersBtn) {
        resetFiltersBtn.addEventListener('click', () => {
            if(searchTermInput) searchTermInput.value = '';
            if(filterTypeSelect) filterTypeSelect.value = '';
            if(filterStatusSelect) filterStatusSelect.value = '';
            fetchAndRenderExtensions();
        });
    }

    if(extensionsTableBody) fetchAndRenderExtensions(); // Initial load
}

// --- Sector Management (Super-Admin) --- //
function initSectorManagement() {
    const addSectorForm = document.getElementById('add-sector-form');
    const sectorsTableBody = document.querySelector('#sectors-table tbody');
    const sectorsTable = document.getElementById('sectors-table');
    const sectorsLoading = document.getElementById('sectors-loading');
    const sectorsError = document.getElementById('sectors-error');
    const addSectorMessage = document.getElementById('add-sector-message');


    const editSectorModal = document.getElementById('edit-sector-modal');
    const editSectorForm = document.getElementById('edit-sector-form');
    const editSectorIdInput = document.getElementById('edit-sector-id');
    const editSectorNameInput = document.getElementById('edit-sector-name');
    const editSectorMessage = document.getElementById('edit-sector-message');


    function showSectorsMessage(element, message, isSuccess) {
        if (!element) return;
        element.textContent = message;
        element.className = 'form-message ' + (isSuccess ? 'success' : 'error');
        element.style.display = 'block';
        setTimeout(() => { element.style.display = 'none'; }, 3000);
    }

    function displaySectorsError(message) {
        if(sectorsError) sectorsError.textContent = message;
        if(sectorsError) sectorsError.style.display = 'block';
        if(sectorsLoading) sectorsLoading.style.display = 'none';
        if(sectorsTable) sectorsTable.style.display = 'none';
    }

    async function fetchAndRenderSectors() {
        if(sectorsLoading) sectorsLoading.style.display = 'block';
        if(sectorsError) sectorsError.style.display = 'none';
        if(sectorsTable) sectorsTable.style.display = 'none';

        try {
            const response = await fetch('/admin/actions/get_sectors_list.php?t=' + new Date().getTime());
            if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
            const result = await response.json();

            if(sectorsLoading) sectorsLoading.style.display = 'none';
            if (result.success) {
                if(sectorsTable) sectorsTable.style.display = 'table';
                renderSectorsTable(result.data);
            } else {
                displaySectorsError(result.message || 'Falha ao carregar setores.');
            }
        } catch (error) {
            console.error('Error fetching sectors:', error);
            displaySectorsError('Erro de conexão ao carregar setores: ' + error.message);
        }
    }

    function renderSectorsTable(sectors) {
        if(!sectorsTableBody) return;
        sectorsTableBody.innerHTML = '';
        if (!sectors || sectors.length === 0) {
            sectorsTableBody.innerHTML = '<tr><td colspan="3" style="text-align:center;">Nenhum setor encontrado.</td></tr>';
            return;
        }
        sectors.forEach(sector => {
            const row = sectorsTableBody.insertRow();
            row.innerHTML = `
                <td>${escapeJSHTML(sector.id)}</td>
                <td>${escapeJSHTML(sector.name)}</td>
                <td class="actions">
                    <button class="btn btn-sm btn-warning edit-sector-btn" data-id="${sector.id}" data-name="${escapeJSHTML(sector.name)}">Editar</button>
                    <button class="btn btn-sm btn-danger delete-sector-btn" data-id="${sector.id}" data-name="${escapeJSHTML(sector.name)}">Excluir</button>
                </td>
            `;
        });
    }

    if(addSectorForm) {
        addSectorForm.addEventListener('submit', async function(event) {
            event.preventDefault();
            const formData = new FormData(addSectorForm);
            const name = formData.get('name').trim();
            if (!name) {
                showSectorsMessage(addSectorMessage, 'Nome do setor não pode ser vazio.', false);
                return;
            }

            try {
                const response = await fetch('/admin/actions/add_sector.php', { method: 'POST', body: formData });
                const result = await response.json();
                showSectorsMessage(addSectorMessage, result.message, result.success);
                if (result.success) {
                    addSectorForm.reset();
                    fetchAndRenderSectors();
                }
            } catch (error) {
                console.error('Error adding sector:', error);
                showSectorsMessage(addSectorMessage, 'Erro de conexão ao adicionar setor.', false);
            }
        });
    }

    if (sectorsTableBody) {
        sectorsTableBody.addEventListener('click', function(event) {
            const target = event.target;
            if (target.classList.contains('edit-sector-btn')) {
                openEditSectorModal(target.dataset.id, target.dataset.name);
            } else if (target.classList.contains('delete-sector-btn')) {
                if (confirm(`Tem certeza que deseja excluir o setor "${escapeJSHTML(target.dataset.name)}" (ID: ${target.dataset.id})? Se houver ramais associados, a exclusão será impedida.`)) {
                    deleteSector(target.dataset.id);
                }
            }
        });
    }

    function openEditSectorModal(id, name) {
        if (!editSectorModal) return;
        if(editSectorIdInput) editSectorIdInput.value = id;
        if(editSectorNameInput) editSectorNameInput.value = name;
        if(editSectorMessage) editSectorMessage.style.display = 'none';
        editSectorModal.style.display = 'block';
    }

    if (editSectorForm) {
        editSectorForm.addEventListener('submit', async function(event) {
            event.preventDefault();
            const formData = new FormData(editSectorForm);
            const name = formData.get('name').trim();
            if (!name) {
                showSectorsMessage(editSectorMessage, 'Nome do setor não pode ser vazio.', false);
                return;
            }

            try {
                const response = await fetch('/admin/actions/update_sector.php', { method: 'POST', body: formData });
                const result = await response.json();
                showSectorsMessage(editSectorMessage, result.message, result.success);
                if (result.success) {
                    setTimeout(() => closeModal('edit-sector-modal'), 1000);
                    fetchAndRenderSectors();
                }
            } catch (error) {
                console.error('Error updating sector:', error);
                showSectorsMessage(editSectorMessage, 'Erro de conexão ao atualizar setor.', false);
            }
        });
    }

    async function deleteSector(id) {
        const formData = new FormData();
        formData.append('id', id);
        try {
            const response = await fetch('/admin/actions/delete_sector.php', { method: 'POST', body: formData });
            const result = await response.json();
            alert(result.message);
            if (result.success) {
                fetchAndRenderSectors();
            }
        } catch (error) {
            console.error('Error deleting sector:', error);
            alert('Erro de conexão ao excluir setor.');
        }
    }

    if(sectorsTableBody) fetchAndRenderSectors();
}

// --- User Management (Super-Admin) --- //
function initUserManagement() {
    const usersTableBody = document.querySelector('#users-table tbody');
    const usersTable = document.getElementById('users-table');
    const usersLoading = document.getElementById('users-loading');
    const usersError = document.getElementById('users-error');

    const openAddUserModalBtn = document.getElementById('open-add-user-modal-btn');
    const userModal = document.getElementById('user-modal');
    const userModalTitle = document.getElementById('user-modal-title');
    const userForm = document.getElementById('user-form');
    const userIdInput = document.getElementById('user-id');
    const usernameInput = document.getElementById('user-username');
    const passwordInput = document.getElementById('user-password');
    const passwordHelpText = document.getElementById('password-help');
    const profileSelect = document.getElementById('user-profile');
    const userFormMessage = document.getElementById('user-form-message'); // Assuming it exists

    function showUserMessage(message, isSuccess) {
        if (!userFormMessage) { // Fallback to alert if specific message element doesn't exist
            alert(message);
            return;
        }
        userFormMessage.textContent = message;
        userFormMessage.className = 'form-message ' + (isSuccess ? 'success' : 'error');
        userFormMessage.style.display = 'block';
        setTimeout(() => { userFormMessage.style.display = 'none'; }, 5000);
    }

    function displayUsersError(message) {
        if(usersError) usersError.textContent = message;
        if(usersError) usersError.style.display = 'block';
        if(usersLoading) usersLoading.style.display = 'none';
        if(usersTable) usersTable.style.display = 'none';
    }

    async function fetchAndRenderUsers() {
        if(usersLoading) usersLoading.style.display = 'block';
        if(usersError) usersError.style.display = 'none';
        if(usersTable) usersTable.style.display = 'none';

        try {
            const response = await fetch('/admin/actions/list_users.php');
            if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
            const result = await response.json();

            if(usersLoading) usersLoading.style.display = 'none';
            if (result.success) {
                if(usersTable) usersTable.style.display = 'table';
                renderUsersTable(result.data);
            } else {
                displayUsersError(result.message || 'Falha ao carregar usuários.');
            }
        } catch (error) {
            console.error('Error fetching users:', error);
            displayUsersError('Erro de conexão ao carregar usuários: ' + error.message);
        }
    }

    function renderUsersTable(users) {
        if(!usersTableBody) return;
        usersTableBody.innerHTML = '';
        if (!users || users.length === 0) {
            usersTableBody.innerHTML = '<tr><td colspan="4" style="text-align:center;">Nenhum usuário encontrado.</td></tr>';
            return;
        }
        users.forEach(user => {
            const row = usersTableBody.insertRow();
            row.innerHTML = `
                <td>${escapeJSHTML(user.id)}</td>
                <td>${escapeJSHTML(user.username)}</td>
                <td>${escapeJSHTML(user.profile)}</td>
                <td class="actions">
                    <button class="btn btn-sm btn-warning edit-user-btn" data-id="${user.id}">Editar</button>
                    <button class="btn btn-sm btn-danger delete-user-btn" data-id="${user.id}" data-username="${escapeJSHTML(user.username)}">Excluir</button>
                </td>
            `;
            row.querySelector('.edit-user-btn').dataset.userData = JSON.stringify(user);
        });
    }

    if(openAddUserModalBtn) {
        openAddUserModalBtn.addEventListener('click', () => {
            if(userForm) userForm.reset();
            if(userIdInput) userIdInput.value = '';
            if(userModalTitle) userModalTitle.textContent = 'Adicionar Novo Usuário';
            if(passwordInput) passwordInput.required = true;
            if(passwordInput) passwordInput.placeholder = "Mínimo 8 caracteres";
            if(passwordHelpText) passwordHelpText.textContent = 'Mínimo 8 caracteres.';
            if(userFormMessage) userFormMessage.style.display = 'none';
            if(userModal) userModal.style.display = 'block';
        });
    }

    if (usersTableBody) {
        usersTableBody.addEventListener('click', async function(event) {
            const target = event.target;
            if (target.classList.contains('edit-user-btn')) {
                const userData = JSON.parse(target.dataset.userData);
                if(userForm) userForm.reset();
                if(userModalTitle) userModalTitle.textContent = `Editar Usuário: ${escapeJSHTML(userData.username)}`;
                if(userIdInput) userIdInput.value = userData.id;
                if(usernameInput) usernameInput.value = userData.username;
                if(profileSelect) profileSelect.value = userData.profile;
                if(passwordInput) passwordInput.required = false;
                if(passwordInput) passwordInput.placeholder = "Deixe em branco para não alterar";
                if(passwordHelpText) passwordHelpText.textContent = 'Deixe em branco para não alterar a senha. Mínimo 8 caracteres para nova senha.';
                if(userFormMessage) userFormMessage.style.display = 'none';
                if(userModal) userModal.style.display = 'block';

            } else if (target.classList.contains('delete-user-btn')) {
                const id = target.dataset.id;
                const username = target.dataset.username;
                if (confirm(`Tem certeza que deseja excluir o usuário "${escapeJSHTML(username)}" (ID: ${id})? Esta ação não pode ser desfeita.`)) {
                    deleteUser(id);
                }
            }
        });
    }

    if (userForm) {
        userForm.addEventListener('submit', async function(event) {
            event.preventDefault();
            const formData = new FormData(userForm);
            const id = userIdInput.value;
            const url = id ? '/admin/actions/update_user.php' : '/admin/actions/add_user.php';

            const password = formData.get('password');
            if (!id && !password) {
                showUserMessage('Senha é obrigatória para novos usuários.', false);
                return;
            }
            if (password && password.length < 8) {
                 showUserMessage('Senha deve ter no mínimo 8 caracteres.', false);
                 return;
            }
            if (id && !password) {
                formData.delete('password');
            }

            try {
                const response = await fetch(url, { method: 'POST', body: formData });
                const result = await response.json();
                showUserMessage(result.message, result.success);
                if (result.success) {
                    setTimeout(() => { if(userModal) closeModal('user-modal'); }, 1000);
                    fetchAndRenderUsers();
                }
            } catch (error) {
                console.error('Error saving user:', error);
                showUserMessage('Erro de conexão ao salvar usuário.', false);
            }
        });
    }

    async function deleteUser(id) {
        const formData = new FormData();
        formData.append('id', id);
        try {
            const response = await fetch('/admin/actions/delete_user.php', { method: 'POST', body: formData });
            const result = await response.json();
            alert(result.message);
            if (result.success) {
                fetchAndRenderUsers();
            }
        } catch (error) {
            console.error('Error deleting user:', error);
            alert('Erro de conexão ao excluir usuário.');
        }
    }

    if(usersTableBody) fetchAndRenderUsers(); // Initial load
}


// --- Global Helper Functions (ensure they are defined once) --- //
if (!window.closeModal) {
    window.closeModal = function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'none';
        }
    };
    document.querySelectorAll('.close-btn, .close-btn-action').forEach(btn => {
        if (!btn.dataset.closeListenerAttached) {
            btn.addEventListener('click', function() {
                const modalId = this.dataset.modalId || this.closest('.modal')?.id;
                if (modalId) {
                    closeModal(modalId);
                }
            });
            btn.dataset.closeListenerAttached = 'true';
        }
    });
}

if (!window.escapeJSHTML) {
    window.escapeJSHTML = function(str) {
        if (str === null || str === undefined) return '';
        return String(str).replace(/[&<>"']/g, function (match) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[match];
        });
    };
}
