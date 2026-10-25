document.addEventListener('DOMContentLoaded', () => {
    const workflowCanvas = document.getElementById('workflowCanvas');
    const linesSvg = document.getElementById('linesSvg');
    const addNodeBtn = document.getElementById('addNodeBtn');

    const downloadWorkflowBtn = document.getElementById('downloadWorkflowBtn');
    const uploadWorkflowInput = document.getElementById('uploadWorkflowInput');
    const uploadWorkflowBtn = document.getElementById('uploadWorkflowBtn');

    // --- Database Config Modal Elements ---
    const databaseConfigModal = document.getElementById('databaseConfigModal');
    const modalNodeIdInput = document.getElementById('modalNodeId');
    const closeDbModalBtn = databaseConfigModal ? databaseConfigModal.querySelector('.close-modal-btn') : null;
    const saveDbConfigBtn = document.getElementById('saveDbConfigBtn');
    const addFieldBtn = document.getElementById('addFieldBtn');
    const dbFieldsContainer = document.getElementById('dbFieldsContainer');

    // Input fields for DB credentials
    const dbHostInput = document.getElementById('dbHost');
    const dbPortInput = document.getElementById('dbPort');
    const dbUsernameInput = document.getElementById('dbUsername');
    const dbPasswordInput = document.getElementById('dbPassword');
    const dbNameInput = document.getElementById('dbName');
    const dbTableNameInput = document.getElementById('dbTableName');

    // --- API Generator Modal Elements ---
    const apiGeneratorModal = document.getElementById('apiGeneratorModal');
    const apiModalNodeIdInput = document.getElementById('apiModalNodeId');
    const closeApiModalBtn = document.getElementById('closeApiModalBtn');
    const generateCrudBtn = document.getElementById('generateCrudBtn');
    const copyPhpCodeBtn = document.getElementById('copyPhpCodeBtn');
    const saveFileToDiskBtn = document.getElementById('saveFileToDiskBtn');

    const apiHostInput = document.getElementById('apiHost');
    const apiPortInput = document.getElementById('apiPort');
    const apiUsernameInput = document.getElementById('apiUsername');
    const apiPasswordInput = document.getElementById('apiPassword');
    const apiDbNameInput = document.getElementById('apiDbName');
    const apiTableNameInput = document.getElementById('apiTableName');

    const apiFilePathInput = document.getElementById('apiFilePath');
    const apiFileNameInput = document.getElementById('apiFileName');
    const generatedPhpCodeArea = document.getElementById('generatedPhpCode');
    const apiResultContainer = document.getElementById('apiResultContainer');

    // --- Client Generator Modal Elements ---
    const clientGeneratorModal = document.getElementById('clientGeneratorModal');
    const clientModalNodeIdInput = document.getElementById('clientModalNodeId');
    const closeClientModalBtn = document.getElementById('closeClientModalBtn');
    const generateClientBtn = document.getElementById('generateClientBtn');
    const copyClientCodeBtn = document.getElementById('copyClientCodeBtn');
    const saveClientFileBtn = document.getElementById('saveClientFileBtn');

    const clientEndpointUrlInput = document.getElementById('clientEndpointUrl');
    const clientTokenInput = document.getElementById('clientToken');
    const clientPrimaryKeyInput = document.getElementById('clientPrimaryKey');
    const clientLanguageSelect = document.getElementById('clientLanguage');

    const clientFilePathInput = document.getElementById('clientFilePath');
    const clientFileNameInput = document.getElementById('clientFileName');
    const generatedClientCodeArea = document.getElementById('generatedClientCode');
    const clientResultContainer = document.getElementById('clientResultContainer');

    let activeNode = null;
    let offsetX, offsetY;
    let nodesData = [];
    let connectionsData = [];

    // --- Variables para la creación de conexiones ---
    let isDrawingConnection = false;
    let startNodeId = null;
    let startPortType = null; // 'input' or 'output'
    let tempLine = null;
    let targetPortElement = null;

    // --- Context Menu (for connections) ---
    const connectionContextMenu = document.createElement('ul');
    connectionContextMenu.classList.add('context-menu');
    document.body.appendChild(connectionContextMenu);

    // --- Icon Selection Menu ---
    const iconSelectionMenu = document.createElement('div');
    iconSelectionMenu.classList.add('icon-selection-menu');
    document.body.appendChild(iconSelectionMenu);

    // List of available Font Awesome icons
    const availableIcons = [
        'fas fa-cogs', 'fas fa-comment-dots', 'fas fa-code', 'fas fa-globe',
        'fas fa-pencil-alt', 'fas fa-file-invoice', 'fas fa-database', 'fas fa-cloud',
        'fas fa-robot', 'fas fa-envelope', 'fas fa-bell', 'fas fa-terminal',
        'fas fa-hdd', 'fas fa-sitemap', 'fas fa-chart-bar', 'fas fa-server',
        'fas fa-users', 'fas fa-credit-card', 'fas fa-laptop-code', 'fas fa-plug',
        'fas fa-wifi', 'fas fa-shield-alt', 'fas fa-truck', 'fas fa-shopping-cart'
    ];

    function generateUniqueId() {
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
            var r = Math.random() * 16 | 0, v = c == 'x' ? r : (r & 0x3 | 0x8);
            return v.toString(16);
        });
    }

    // --- Funciones de Renderizado ---

    function createNodeElement(nodeConfig) {
        const nodeDiv = document.createElement('div');
        nodeDiv.classList.add('node', 'draggable-node');
        nodeDiv.id = nodeConfig.id;
        nodeDiv.style.left = `${nodeConfig.x}px`;
        nodeDiv.style.top = `${nodeConfig.y}px`;

        let nodeTypeClass = '';
        let iconClass = nodeConfig.icon || 'fas fa-cogs';
        let headerContent = '';
        let subtitleContent = nodeConfig.subtitle ? `<div class="node-subtitle">${nodeConfig.subtitle}</div>` : '';

        switch (nodeConfig.type) {
            case 'start':
                nodeTypeClass = 'node-start';
                headerContent = 'de chat';
                break;
            case 'code':
            case 'api':
            case 'edit-fields':
                nodeTypeClass = 'node-process';
                break;
            case 'disk':
                nodeTypeClass = 'node-end';
                break;
            case 'database':
                nodeTypeClass = 'node-process';
                headerContent = 'Database';
                break;
            case 'crud-api':
                nodeTypeClass = 'node-process';
                headerContent = 'API Generator';
                break;
            case 'crud-client':
                nodeTypeClass = 'node-process';
                headerContent = 'API Client';
                break;
            default:
                nodeTypeClass = 'node-process';
                headerContent = '';
                break;
        }

        let iconColorClass = '';
        if (nodeConfig.type === 'start') iconColorClass = 'type-chat-message';
        else if (nodeConfig.type === 'code') iconColorClass = 'type-code';
        else if (nodeConfig.type === 'api' || nodeConfig.type === 'crud-api' || nodeConfig.type === 'crud-client') iconColorClass = 'type-api';
        else if (nodeConfig.type === 'edit-fields') iconColorClass = 'type-edit-fields';
        else if (nodeConfig.type === 'disk') iconColorClass = 'type-disk';
        else if (nodeConfig.type === 'database') iconColorClass = 'type-database';
        else iconColorClass = 'type-generic';

        nodeDiv.innerHTML = `
            <button class="delete-node-btn" data-node-id="${nodeConfig.id}"><i class="fas fa-times"></i></button>
            <div class="node-icon ${iconColorClass}" data-node-id="${nodeConfig.id}"><i class="${iconClass}"></i></div>
            <div class="node-header">${headerContent}</div>
            <div class="node-title" contenteditable="true" data-node-id="${nodeConfig.id}">${nodeConfig.title}</div>
            ${subtitleContent}
            ${nodeConfig.type !== 'disk' ? '<div class="node-port output-port" data-port-type="output" data-node-id="' + nodeConfig.id + '"></div>' : ''}
            ${nodeConfig.type !== 'start' ? '<div class="node-port input-port" data-port-type="input" data-node-id="' + nodeConfig.id + '"></div>' : ''}
        `;
        nodeDiv.classList.add(nodeTypeClass);

        addDragListeners(nodeDiv);

        // Listener de doble clic para abrir configuraciones
        nodeDiv.addEventListener('dblclick', (e) => {
            if (!e.target.classList.contains('node-port') && !e.target.closest('.node-icon')) {
                if (nodeConfig.type === 'crud-api' || (nodeConfig.icon && nodeConfig.icon.includes('fa-laptop-code'))) {
                    showApiGeneratorModal(nodeConfig.id);
                } else if (nodeConfig.type === 'crud-client' || (nodeConfig.icon && nodeConfig.icon.includes('fa-plug'))) {
                    showClientGeneratorModal(nodeConfig.id);
                } else if (nodeConfig.type === 'database' || (nodeConfig.icon && nodeConfig.icon.includes('fas fa-database'))) {
                    showDatabaseConfigModal(nodeConfig.id);
                }
            }
        });

        const deleteButton = nodeDiv.querySelector('.delete-node-btn');
        if (deleteButton) {
            deleteButton.addEventListener('click', (e) => {
                e.stopPropagation();
                deleteNode(nodeConfig.id);
            });
        }

        const outputPort = nodeDiv.querySelector('.output-port');
        if (outputPort) outputPort.addEventListener('mousedown', startConnectionDrawing);

        const inputPort = nodeDiv.querySelector('.input-port');
        if (inputPort) inputPort.addEventListener('mousedown', startConnectionDrawing);

        const nodeIconElement = nodeDiv.querySelector('.node-icon');
        if (nodeIconElement) {
            nodeIconElement.addEventListener('contextmenu', (e) => {
                e.preventDefault();
                e.stopPropagation();
                showIconSelectionMenu(e, nodeConfig.id);
            });
        }

        workflowCanvas.appendChild(nodeDiv);
        return nodeDiv;
    }

    function createLineElement(connectionConfig) {
        const line = document.createElementNS('http://www.w3.org/2000/svg', 'line');
        line.id = connectionConfig.lineId;
        line.classList.add('connection-line');
        line.setAttribute('data-from-node', connectionConfig.fromNodeId);
        line.setAttribute('data-to-node', connectionConfig.toNodeId);

        line.addEventListener('contextmenu', (e) => {
            e.preventDefault();
            showConnectionContextMenu(e, connectionConfig.lineId);
        });

        linesSvg.appendChild(line);
        return line;
    }

    function renderWorkflow() {
        workflowCanvas.querySelectorAll('.node').forEach(node => node.remove());
        linesSvg.querySelectorAll('line').forEach(line => line.remove());

        nodesData.forEach(nodeConfig => createNodeElement(nodeConfig));
        connectionsData.forEach(conn => createLineElement(conn));

        nodesData.forEach(nodeConfig => {
            const nodeElement = document.getElementById(nodeConfig.id);
            if (nodeElement) updateConnectedLines(nodeElement);
        });
    }

    // --- CRUD de Datos ---

    function addNode(type, title, subtitle = '', x, y, icon = 'fas fa-cogs') {
        const newNode = {
            id: generateUniqueId(),
            type: type,
            title: title,
            subtitle: subtitle,
            x: x,
            y: y,
            icon: icon,
            dbConfig: type === 'database' ? {} : undefined,
            apiConfig: type === 'crud-api' ? {} : undefined,
            clientConfig: type === 'crud-client' ? {} : undefined
        };
        nodesData.push(newNode);
        createNodeElement(newNode);
        saveWorkflowToLocalStorage();
    }

    function updateNodePosition(nodeId, newX, newY) {
        const nodeIndex = nodesData.findIndex(node => node.id === nodeId);
        if (nodeIndex !== -1) {
            nodesData[nodeIndex].x = newX;
            nodesData[nodeIndex].y = newY;
        }
    }

    function updateNodeIcon(nodeId, newIconClass) {
        const nodeIndex = nodesData.findIndex(node => node.id === nodeId);
        if (nodeIndex !== -1) {
            nodesData[nodeIndex].icon = newIconClass;

            if (newIconClass.includes('fas fa-database')) {
                nodesData[nodeIndex].type = 'database';
                if (!nodesData[nodeIndex].dbConfig) nodesData[nodeIndex].dbConfig = {};
            } else if (newIconClass.includes('fa-laptop-code')) {
                nodesData[nodeIndex].type = 'crud-api';
                if (!nodesData[nodeIndex].apiConfig) nodesData[nodeIndex].apiConfig = {};
            } else if (newIconClass.includes('fa-plug')) {
                nodesData[nodeIndex].type = 'crud-client';
                if (!nodesData[nodeIndex].clientConfig) nodesData[nodeIndex].clientConfig = {};
            } else if (nodesData[nodeIndex].type === 'database' || nodesData[nodeIndex].type === 'crud-api' || nodesData[nodeIndex].type === 'crud-client') {
                nodesData[nodeIndex].type = 'generic';
            }

            const nodeElement = document.getElementById(nodeId);
            if (nodeElement) {
                const iconElement = nodeElement.querySelector('.node-icon i');
                if (iconElement) {
                    iconElement.className = '';
                    iconElement.classList.add(...newIconClass.split(' '));

                    const nodeIconContainer = nodeElement.querySelector('.node-icon');
                    nodeIconContainer.classList.remove('type-chat-message', 'type-code', 'type-api', 'type-edit-fields', 'type-disk', 'type-database', 'type-generic');

                    let newIconColorClass = '';
                    if (nodesData[nodeIndex].type === 'start') newIconColorClass = 'type-chat-message';
                    else if (nodesData[nodeIndex].type === 'code') newIconColorClass = 'type-code';
                    else if (nodesData[nodeIndex].type === 'api' || nodesData[nodeIndex].type === 'crud-api' || nodesData[nodeIndex].type === 'crud-client') newIconColorClass = 'type-api';
                    else if (nodesData[nodeIndex].type === 'edit-fields') newIconColorClass = 'type-edit-fields';
                    else if (nodesData[nodeIndex].type === 'disk') newIconColorClass = 'type-disk';
                    else if (nodesData[nodeIndex].type === 'database') newIconColorClass = 'type-database';
                    else newIconColorClass = 'type-generic';

                    nodeIconContainer.classList.add(newIconColorClass);
                }
            }
            saveWorkflowToLocalStorage();
        }
    }

    function deleteNode(nodeId) {
        nodesData = nodesData.filter(node => node.id !== nodeId);
        connectionsData = connectionsData.filter(conn => {
            if (conn.fromNodeId === nodeId || conn.toNodeId === nodeId) {
                const lineElement = document.getElementById(conn.lineId);
                if (lineElement) lineElement.remove();
                return false;
            }
            return true;
        });

        const nodeElement = document.getElementById(nodeId);
        if (nodeElement) nodeElement.remove();

        saveWorkflowToLocalStorage();
    }

    function addConnection(fromNodeId, toNodeId) {
        if (fromNodeId === toNodeId) {
            alert('No se puede conectar un nodo consigo mismo.');
            return;
        }
        const exists = connectionsData.some(conn => (conn.fromNodeId === fromNodeId && conn.toNodeId === toNodeId));
        if (exists) {
            alert('Esta conexión ya existe.');
            return;
        }

        const newConnection = {
            lineId: `line-${fromNodeId}-${toNodeId}-${generateUniqueId().substring(0, 4)}`,
            fromNodeId: fromNodeId,
            toNodeId: toNodeId
        };
        connectionsData.push(newConnection);

        createLineElement(newConnection);
        updateConnectedLines(document.getElementById(fromNodeId));
        updateConnectedLines(document.getElementById(toNodeId));

        saveWorkflowToLocalStorage();
    }

    function deleteConnection(lineId) {
        connectionsData = connectionsData.filter(conn => conn.lineId !== lineId);
        const lineElement = document.getElementById(lineId);
        if (lineElement) {
            lineElement.remove();
            saveWorkflowToLocalStorage();
        }
    }

    // --- Persistencia ---

    const LOCAL_STORAGE_KEY = 'n8nWorkflowData';

    function saveWorkflowToLocalStorage() {
        const dataToSave = { nodes: nodesData, connections: connectionsData };
        try {
            localStorage.setItem(LOCAL_STORAGE_KEY, JSON.stringify(dataToSave));
        } catch (e) {
            console.error('Error al guardar el workflow en localStorage:', e);
        }
    }

    function loadWorkflowFromLocalStorage() {
        try {
            const storedData = localStorage.getItem(LOCAL_STORAGE_KEY);
            if (storedData) {
                const parsedData = JSON.parse(storedData);
                nodesData = parsedData.nodes || [];
                connectionsData = parsedData.connections || [];
                renderWorkflow();
            }
        } catch (e) {
            console.error('Error al cargar el workflow desde localStorage:', e);
        }
    }

    function downloadWorkflowAsJson() {
        const dataToDownload = { nodes: nodesData, connections: connectionsData };
        const jsonString = JSON.stringify(dataToDownload, null, 2);
        const blob = new Blob([jsonString], { type: 'application/json' });
        const url = URL.createObjectURL(blob);

        const a = document.createElement('a');
        a.href = url;
        a.download = 'workflow.json';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }

    function uploadWorkflowFromJsonFile(event) {
        const file = event.target.files[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = (e) => {
            try {
                const loadedData = JSON.parse(e.target.result);
                if (loadedData.nodes && Array.isArray(loadedData.nodes) &&
                    loadedData.connections && Array.isArray(loadedData.connections)) {
                    nodesData = loadedData.nodes;
                    connectionsData = loadedData.connections;
                    renderWorkflow();
                    saveWorkflowToLocalStorage();
                    alert('Workflow cargado exitosamente.');
                } else {
                    alert('Formato de archivo JSON no válido.');
                }
            } catch (error) {
                alert('Error al procesar el archivo JSON.');
            } finally {
                event.target.value = '';
            }
        };
        reader.readAsText(file);
    }

    // --- Drag & Drop ---

    function addDragListeners(nodeElement) {
        nodeElement.addEventListener('mousedown', (e) => {
            if (e.target.classList.contains('node-port') ||
                e.target.classList.contains('delete-node-btn') ||
                e.target.closest('.delete-node-btn') ||
                e.target.closest('.node-icon') ||
                (e.target.classList.contains('node-title') && e.target.getAttribute('contenteditable') === 'true')) {
                return;
            }
            activeNode = nodeElement;
            offsetX = e.clientX - nodeElement.getBoundingClientRect().left;
            offsetY = e.clientY - nodeElement.getBoundingClientRect().top;
            activeNode.classList.add('is-dragging');
            e.preventDefault();
            hideAllContextMenus();
        });
    }

    document.addEventListener('mousemove', (e) => {
        if (activeNode) {
            const canvasRect = workflowCanvas.getBoundingClientRect();
            let newX = e.clientX - offsetX - canvasRect.left;
            let newY = e.clientY - offsetY - canvasRect.top;

            const nodeRect = activeNode.getBoundingClientRect();

            if (newX < 0) newX = 0;
            if (newY < 0) newY = 0;
            if (newX + nodeRect.width > canvasRect.width) newX = canvasRect.width - nodeRect.width;
            if (newY + nodeRect.height > canvasRect.height) newY = canvasRect.height - nodeRect.height;

            activeNode.style.left = `${newX}px`;
            activeNode.style.top = `${newY}px`;

            updateNodePosition(activeNode.id, newX, newY);
            updateConnectedLines(activeNode);
        }

        if (isDrawingConnection && tempLine) {
            const canvasRect = workflowCanvas.getBoundingClientRect();
            const currentX = e.clientX - canvasRect.left;
            const currentY = e.clientY - canvasRect.top;

            const startNode = document.getElementById(startNodeId);
            if (!startNode) {
                resetConnectionDrawing();
                return;
            }
            const startCoords = getPortCoordinates(startNode, startPortType);

            tempLine.setAttribute('x1', startCoords.x);
            tempLine.setAttribute('y1', startCoords.y);
            tempLine.setAttribute('x2', currentX);
            tempLine.setAttribute('y2', currentY);

            const target = e.target;
            if (target.classList.contains('node-port') &&
                target.dataset.nodeId !== startNodeId &&
                ((startPortType === 'output' && target.dataset.portType === 'input') ||
                    (startPortType === 'input' && target.dataset.portType === 'output'))
            ) {
                if (targetPortElement && targetPortElement !== target) {
                    targetPortElement.classList.remove('highlight');
                }
                target.classList.add('highlight');
                targetPortElement = target;
            } else if (targetPortElement) {
                targetPortElement.classList.remove('highlight');
                targetPortElement = null;
            }
        }
    });

    document.addEventListener('mouseup', () => {
        if (activeNode) {
            activeNode.classList.remove('is-dragging');
            activeNode = null;
            saveWorkflowToLocalStorage();
        }

        if (isDrawingConnection) {
            if (tempLine) {
                tempLine.remove();
                tempLine = null;
            }

            if (targetPortElement) {
                targetPortElement.classList.remove('highlight');
                const endNodeId = targetPortElement.dataset.nodeId;
                const endPortType = targetPortElement.dataset.portType;

                let finalFromNodeId, finalToNodeId;
                if (startPortType === 'output' && endPortType === 'input') {
                    finalFromNodeId = startNodeId;
                    finalToNodeId = endNodeId;
                } else if (startPortType === 'input' && endPortType === 'output') {
                    finalFromNodeId = endNodeId;
                    finalToNodeId = startNodeId;
                } else {
                    resetConnectionDrawing();
                    return;
                }

                addConnection(finalFromNodeId, finalToNodeId);
            }
            resetConnectionDrawing();
        }
    });

    // --- Conexiones ---

    function startConnectionDrawing(e) {
        if (e.button !== 0 || isDrawingConnection) return;

        const portElement = e.target.closest('.node-port');
        if (!portElement) return;

        e.stopPropagation();

        isDrawingConnection = true;
        startNodeId = portElement.dataset.nodeId;
        startPortType = portElement.dataset.portType;

        const canvasRect = workflowCanvas.getBoundingClientRect();
        const startCoords = getPortCoordinates(document.getElementById(startNodeId), startPortType);

        tempLine = document.createElementNS('http://www.w3.org/2000/svg', 'line');
        tempLine.classList.add('temp-line');
        tempLine.setAttribute('x1', startCoords.x);
        tempLine.setAttribute('y1', startCoords.y);
        tempLine.setAttribute('x2', e.clientX - canvasRect.left);
        tempLine.setAttribute('y2', e.clientY - canvasRect.top);
        linesSvg.appendChild(tempLine);

        hideAllContextMenus();
    }

    function resetConnectionDrawing() {
        isDrawingConnection = false;
        startNodeId = null;
        startPortType = null;
        if (tempLine) {
            tempLine.remove();
            tempLine = null;
        }
        if (targetPortElement) {
            targetPortElement.classList.remove('highlight');
            targetPortElement = null;
        }
    }

    function getPortCoordinates(node, type) {
        const nodeRect = node.getBoundingClientRect();
        const canvasRect = workflowCanvas.getBoundingClientRect();

        let x = (type === 'output') ? (nodeRect.right - canvasRect.left) : (nodeRect.left - canvasRect.left);
        let y = nodeRect.top - canvasRect.top + nodeRect.height / 2;

        return { x: x, y: y };
    }

    function updateConnectedLines(movedNode) {
        const nodeId = movedNode.id;

        connectionsData.forEach(conn => {
            const line = document.getElementById(conn.lineId);
            if (!line) return;

            const fromNode = document.getElementById(conn.fromNodeId);
            const toNode = document.getElementById(conn.toNodeId);

            if (fromNode && toNode && (fromNode.id === nodeId || toNode.id === nodeId)) {
                const fromCoords = getPortCoordinates(fromNode, 'output');
                const toCoords = getPortCoordinates(toNode, 'input');

                line.setAttribute('x1', fromCoords.x);
                line.setAttribute('y1', fromCoords.y);
                line.setAttribute('x2', toCoords.x);
                line.setAttribute('y2', toCoords.y);
            }
        });
    }

    // --- Menús Contextuales ---

    function showConnectionContextMenu(e, lineId) {
        hideAllContextMenus();
        connectionContextMenu.innerHTML = '';
        const deleteItem = document.createElement('li');
        deleteItem.textContent = 'Eliminar conexión';
        deleteItem.addEventListener('click', () => {
            deleteConnection(lineId);
            hideConnectionContextMenu();
        });
        connectionContextMenu.appendChild(deleteItem);

        connectionContextMenu.style.left = `${e.clientX}px`;
        connectionContextMenu.style.top = `${e.clientY}px`;
        connectionContextMenu.classList.add('show');
    }

    function hideConnectionContextMenu() {
        connectionContextMenu.classList.remove('show');
    }

    function showIconSelectionMenu(e, nodeId) {
        hideAllContextMenus();
        iconSelectionMenu.innerHTML = '';

        availableIcons.forEach(iconClass => {
            const iconItem = document.createElement('div');
            iconItem.classList.add('icon-item');
            iconItem.innerHTML = `<i class="${iconClass}"></i>`;
            iconItem.title = iconClass.replace('fas fa-', '');

            iconItem.addEventListener('click', () => {
                updateNodeIcon(nodeId, iconClass);
                hideIconSelectionMenu();
            });
            iconSelectionMenu.appendChild(iconItem);
        });

        iconSelectionMenu.style.left = `${e.clientX + 10}px`;
        iconSelectionMenu.style.top = `${e.clientY + 10}px`;
        iconSelectionMenu.classList.add('show');
    }

    function hideIconSelectionMenu() {
        iconSelectionMenu.classList.remove('show');
    }

    function hideAllContextMenus() {
        hideConnectionContextMenu();
        hideIconSelectionMenu();
        hideDatabaseConfigModal();
        hideApiGeneratorModal();
        hideClientGeneratorModal();
    }

    // --- Modal Configuración Base de Datos ---

    function showDatabaseConfigModal(nodeId) {
        hideAllContextMenus();
        const node = nodesData.find(n => n.id === nodeId);
        if (!node || !databaseConfigModal) return;

        modalNodeIdInput.value = nodeId;
        const dbConfig = node.dbConfig || {};

        dbHostInput.value = dbConfig.host || '';
        dbPortInput.value = dbConfig.port || '';
        dbUsernameInput.value = dbConfig.username || '';
        dbPasswordInput.value = dbConfig.password || '';
        dbNameInput.value = dbConfig.dbName || '';
        dbTableNameInput.value = dbConfig.tableName || '';

        dbFieldsContainer.innerHTML = '';
        (dbConfig.fields || []).forEach(field => {
            addDynamicField(field.key, field.value);
        });
        if ((dbConfig.fields || []).length === 0) {
            addDynamicField('', '');
        }

        databaseConfigModal.classList.add('show');
    }

    function hideDatabaseConfigModal() {
        if (databaseConfigModal) databaseConfigModal.classList.remove('show');
    }

    function addDynamicField(key = '', value = '') {
        if (!dbFieldsContainer) return;
        const row = document.createElement('div');
        row.classList.add('dynamic-field-row');
        row.innerHTML = `
            <input type="text" class="field-key" placeholder="Nombre del Campo" value="${key}">
            <input type="text" class="field-value" placeholder="Valor del Campo" value="${value}">
            <button class="remove-field-btn"><i class="fas fa-trash-alt"></i></button>
        `;
        dbFieldsContainer.appendChild(row);
        row.querySelector('.remove-field-btn').addEventListener('click', () => row.remove());
    }

    async function saveDatabaseConfig() {
        const nodeId = modalNodeIdInput.value;
        const nodeIndex = nodesData.findIndex(n => n.id === nodeId);
        if (nodeIndex === -1) return;

        const fields = [];
        dbFieldsContainer.querySelectorAll('.dynamic-field-row').forEach(row => {
            const key = row.querySelector('.field-key').value.trim();
            const value = row.querySelector('.field-value').value.trim();
            if (key && value) fields.push({ key, value });
        });

        const dbConfigToSave = {
            host: dbHostInput.value.trim(),
            port: dbPortInput.value.trim(),
            username: dbUsernameInput.value.trim(),
            password: dbPasswordInput.value.trim(),
            dbName: dbNameInput.value.trim(),
            tableName: dbTableNameInput.value.trim(),
            fields: fields
        };

        try {
            const response = await fetch('create_db_table.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(dbConfigToSave)
            });

            const result = await response.json();

            if (response.ok) {
                alert(`Configuración guardada: ${result.message}`);
                nodesData[nodeIndex].dbConfig = dbConfigToSave;
                saveWorkflowToLocalStorage();
            } else {
                alert(`Error del backend: ${result.message || 'Error desconocido'}`);
            }
        } catch (error) {
            console.error('Error al comunicarse con el servidor:', error);
            alert('Error de conexión con el servidor backend.');
        } finally {
            hideDatabaseConfigModal();
        }
    }

    // --- Modal Generador API CRUD PHP ---

    function showApiGeneratorModal(nodeId) {
        hideAllContextMenus();
        const node = nodesData.find(n => n.id === nodeId);
        if (!node || !apiGeneratorModal) return;

        apiModalNodeIdInput.value = nodeId;
        const config = node.apiConfig || {};

        if (apiHostInput) apiHostInput.value = config.host || 'localhost';
        if (apiPortInput) apiPortInput.value = config.port || '3306';
        if (apiUsernameInput) apiUsernameInput.value = config.username || 'root';
        if (apiPasswordInput) apiPasswordInput.value = config.password || '';
        if (apiDbNameInput) apiDbNameInput.value = config.dbName || '';
        if (apiTableNameInput) apiTableNameInput.value = config.tableName || '';

        if (apiResultContainer) apiResultContainer.style.display = node.generatedCode ? 'block' : 'none';
        if (generatedPhpCodeArea) generatedPhpCodeArea.value = node.generatedCode || '';

        apiGeneratorModal.classList.add('show');
    }

    function hideApiGeneratorModal() {
        if (apiGeneratorModal) apiGeneratorModal.classList.remove('show');
    }

    if (closeApiModalBtn) closeApiModalBtn.addEventListener('click', hideApiGeneratorModal);

    if (generateCrudBtn) {
        generateCrudBtn.addEventListener('click', async () => {
            const nodeId = apiModalNodeIdInput.value;
            const payload = {
                action: 'generate',
                host: apiHostInput.value.trim(),
                port: apiPortInput.value.trim(),
                username: apiUsernameInput.value.trim(),
                password: apiPasswordInput.value,
                dbName: apiDbNameInput.value.trim(),
                tableName: apiTableNameInput.value.trim()
            };

            try {
                const response = await fetch('generate_crud_api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    if (generatedPhpCodeArea) generatedPhpCodeArea.value = result.generatedCode;
                    if (apiResultContainer) apiResultContainer.style.display = 'block';

                    if (apiFilePathInput && !apiFilePathInput.value) apiFilePathInput.value = 'api';
                    if (apiFileNameInput && result.defaultFileName) apiFileNameInput.value = result.defaultFileName;

                    const nodeIndex = nodesData.findIndex(n => n.id === nodeId);
                    if (nodeIndex !== -1) {
                        nodesData[nodeIndex].apiConfig = payload;
                        nodesData[nodeIndex].generatedCode = result.generatedCode;
                        saveWorkflowToLocalStorage();
                    }
                } else {
                    alert(`Error: ${result.message}`);
                }
            } catch (err) {
                console.error('Error al generar la API CRUD:', err);
                alert('Error al conectar con el servidor backend generate_crud_api.php.');
            }
        });
    }

    if (copyPhpCodeBtn) {
        copyPhpCodeBtn.addEventListener('click', () => {
            if (generatedPhpCodeArea) {
                generatedPhpCodeArea.select();
                navigator.clipboard.writeText(generatedPhpCodeArea.value);
                alert('Código PHP copiado al portapapeles.');
            }
        });
    }

    if (saveFileToDiskBtn) {
        saveFileToDiskBtn.addEventListener('click', async () => {
            const code = generatedPhpCodeArea ? generatedPhpCodeArea.value : '';
            const filePath = apiFilePathInput ? apiFilePathInput.value.trim() : '';
            const fileName = apiFileNameInput ? apiFileNameInput.value.trim() : '';

            if (!code) {
                alert('Primero debes generar el código antes de guardarlo.');
                return;
            }

            if (!fileName) {
                alert('Por favor especifica un nombre para el archivo PHP.');
                return;
            }

            const payload = {
                action: 'save',
                code: code,
                filePath: filePath,
                fileName: fileName
            };

            try {
                const response = await fetch('generate_crud_api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    alert(`¡Éxito! ${result.message}`);
                } else {
                    alert(`Error al guardar: ${result.message}`);
                }
            } catch (err) {
                console.error('Error al guardar archivo:', err);
                alert('Error de conexión al intentar crear el archivo.');
            }
        });
    }

    // --- Modal Generador de Cliente API ---

    function showClientGeneratorModal(nodeId) {
        hideAllContextMenus();
        const node = nodesData.find(n => n.id === nodeId);
        if (!node || !clientGeneratorModal) return;

        clientModalNodeIdInput.value = nodeId;
        const config = node.clientConfig || {};

        if (clientEndpointUrlInput) clientEndpointUrlInput.value = config.endpointUrl || 'http://localhost/api/UsuariosApi.php';
        if (clientTokenInput) clientTokenInput.value = config.token || '';
        if (clientPrimaryKeyInput) clientPrimaryKeyInput.value = config.primaryKey || 'id';
        if (clientLanguageSelect) clientLanguageSelect.value = config.language || 'js';

        if (clientResultContainer) clientResultContainer.style.display = node.generatedClientCode ? 'block' : 'none';
        if (generatedClientCodeArea) generatedClientCodeArea.value = node.generatedClientCode || '';

        clientGeneratorModal.classList.add('show');
    }

    function hideClientGeneratorModal() {
        if (clientGeneratorModal) clientGeneratorModal.classList.remove('show');
    }

    if (closeClientModalBtn) closeClientModalBtn.addEventListener('click', hideClientGeneratorModal);

    if (generateClientBtn) {
        generateClientBtn.addEventListener('click', async () => {
            const nodeId = clientModalNodeIdInput.value;
            const payload = {
                action: 'generate_client',
                endpointUrl: clientEndpointUrlInput.value.trim(),
                token: clientTokenInput.value.trim(),
                primaryKey: clientPrimaryKeyInput.value.trim(),
                clientLanguage: clientLanguageSelect.value
            };

            try {
                const response = await fetch('generate_crud_api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    if (generatedClientCodeArea) generatedClientCodeArea.value = result.generatedCode;
                    if (clientResultContainer) clientResultContainer.style.display = 'block';

                    if (clientFilePathInput && !clientFilePathInput.value) clientFilePathInput.value = 'clients';
                    if (clientFileNameInput && result.defaultFileName) clientFileNameInput.value = result.defaultFileName;

                    const nodeIndex = nodesData.findIndex(n => n.id === nodeId);
                    if (nodeIndex !== -1) {
                        nodesData[nodeIndex].clientConfig = payload;
                        nodesData[nodeIndex].generatedClientCode = result.generatedCode;
                        saveWorkflowToLocalStorage();
                    }
                } else {
                    alert(`Error: ${result.message}`);
                }
            } catch (err) {
                console.error('Error al generar cliente API:', err);
                alert('Error de conexión con el backend.');
            }
        });
    }

    if (copyClientCodeBtn) {
        copyClientCodeBtn.addEventListener('click', () => {
            if (generatedClientCodeArea) {
                generatedClientCodeArea.select();
                navigator.clipboard.writeText(generatedClientCodeArea.value);
                alert('Código de cliente copiado al portapapeles.');
            }
        });
    }

    if (saveClientFileBtn) {
        saveClientFileBtn.addEventListener('click', async () => {
            const code = generatedClientCodeArea ? generatedClientCodeArea.value : '';
            const filePath = clientFilePathInput ? clientFilePathInput.value.trim() : '';
            const fileName = clientFileNameInput ? clientFileNameInput.value.trim() : '';

            if (!code || !fileName) {
                alert('Faltan el código o el nombre del archivo.');
                return;
            }

            const payload = {
                action: 'save',
                code: code,
                filePath: filePath,
                fileName: fileName
            };

            try {
                const response = await fetch('generate_crud_api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });

                const result = await response.json();
                if (response.ok && result.success) {
                    alert(`¡Éxito! ${result.message}`);
                } else {
                    alert(`Error al guardar: ${result.message}`);
                }
            } catch (err) {
                console.error('Error al guardar archivo en disco:', err);
                alert('Error de red al intentar guardar.');
            }
        });
    }

    // --- Global Click & Key Listeners ---

    document.addEventListener('click', (e) => {
        if (!e.target.closest('.context-menu') && !e.target.closest('.icon-selection-menu') && !e.target.closest('.modal-content') && !e.target.closest('.floating-show-chat-btn')) {
            hideAllContextMenus();
        }
    });

    workflowCanvas.addEventListener('contextmenu', (e) => {
        if (!e.target.classList.contains('connection-line') && !e.target.closest('.node-icon')) {
            e.preventDefault();
            hideAllContextMenus();
        }
    });

    workflowCanvas.addEventListener('focusout', (e) => {
        if (e.target.classList.contains('node-title') && e.target.getAttribute('contenteditable') === 'true') {
            const nodeId = e.target.dataset.nodeId;
            const newTitle = e.target.textContent.trim();
            const nodeIndex = nodesData.findIndex(node => node.id === nodeId);
            if (nodeIndex !== -1 && nodesData[nodeIndex].title !== newTitle) {
                nodesData[nodeIndex].title = newTitle;
                saveWorkflowToLocalStorage();
            }
        }
    });

    workflowCanvas.addEventListener('keydown', (e) => {
        if (e.target.classList.contains('node-title') && e.target.getAttribute('contenteditable') === 'true' && e.key === 'Enter') {
            e.preventDefault();
            e.target.blur();
        }
    });

    // --- Inicialización del Workflow ---

    const initialNodes = [
        { id: 'node-start', type: 'start', title: 'When chat message received', x: 100, y: 150, icon: 'fas fa-comment-dots' },
        { id: 'node-aiquery4', type: 'code', title: 'AIQuery4', x: 350, y: 150, icon: 'fas fa-code' },
        { id: 'node-aiquery1', type: 'code', title: 'AIQuery1', x: 600, y: 150, icon: 'fas fa-code' },
        { id: 'node-aiexecute', type: 'api', title: 'AIExecute', subtitle: 'POST https://localho.st:5678/...', x: 850, y: 150, icon: 'fas fa-globe' },
        { id: 'node-code', type: 'code', title: 'Code', x: 1100, y: 150, icon: 'fas fa-code' },
        { id: 'node-editfields', type: 'edit-fields', title: 'Edit fields', subtitle: 'manual', x: 1350, y: 150, icon: 'fas fa-pencil-alt' },
        { id: 'node-db-example', type: 'database', title: 'Database Op', subtitle: 'MySQL users', x: 450, y: 400, icon: 'fas fa-database', dbConfig: { host: 'localhost', port: '3306', username: 'root', password: '', dbName: 'myapp', tableName: 'users', fields: [{ key: 'id_usuario', value: 'INT AUTO_INCREMENT PRIMARY KEY' }, { key: 'nombre', value: 'VARCHAR(100)' }] } },
        { id: 'node-crud-api', type: 'crud-api', title: 'Generar API CRUD PHP', subtitle: 'PHP REST Controller', x: 750, y: 400, icon: 'fas fa-laptop-code', apiConfig: { host: 'localhost', port: '3306', username: 'root', password: '', dbName: 'myapp', tableName: 'users' } },
        { id: 'node-end', type: 'disk', title: 'Read/Write Files From Disk', subtitle: 'Write File to Disk', x: 1600, y: 150, icon: 'fas fa-file-invoice' },
    ];

    const initialConnections = [
        { lineId: 'line-node-start-node-aiquery4-0001', fromNodeId: 'node-start', toNodeId: 'node-aiquery4' },
        { lineId: 'line-node-aiquery4-node-aiquery1-0002', fromNodeId: 'node-aiquery4', toNodeId: 'node-aiquery1' },
        { lineId: 'line-node-aiquery1-node-aiexecute-0003', fromNodeId: 'node-aiquery1', toNodeId: 'node-aiexecute' },
        { lineId: 'line-node-aiexecute-node-code-0004', fromNodeId: 'node-aiexecute', toNodeId: 'node-code' },
        { lineId: 'line-node-code-node-editfields-0005', fromNodeId: 'node-code', toNodeId: 'node-editfields' },
        { lineId: 'line-node-editfields-node-end-0006', fromNodeId: 'node-editfields', toNodeId: 'node-end' },
        { lineId: 'line-node-db-example-node-crud-api-0007', fromNodeId: 'node-db-example', toNodeId: 'node-crud-api' }
    ];

    addNodeBtn.addEventListener('click', () => {
        const randomX = Math.floor(Math.random() * (workflowCanvas.offsetWidth - 200));
        const randomY = Math.floor(Math.random() * (workflowCanvas.offsetHeight - 300));
        addNode('generic', 'New Node', '', randomX, randomY, 'fas fa-cogs');
    });

    downloadWorkflowBtn.addEventListener('click', downloadWorkflowAsJson);
    uploadWorkflowBtn.addEventListener('click', () => uploadWorkflowInput.click());
    uploadWorkflowInput.addEventListener('change', uploadWorkflowFromJsonFile);

    if (closeDbModalBtn) closeDbModalBtn.addEventListener('click', hideDatabaseConfigModal);
    if (saveDbConfigBtn) saveDbConfigBtn.addEventListener('click', saveDatabaseConfig);
    if (addFieldBtn) addFieldBtn.addEventListener('click', () => addDynamicField('', ''));

    const storedData = localStorage.getItem(LOCAL_STORAGE_KEY);
    if (storedData) {
        loadWorkflowFromLocalStorage();
    } else {
        nodesData = [...initialNodes];
        connectionsData = [...initialConnections];
        renderWorkflow();
        saveWorkflowToLocalStorage();
    }
    
    // --- Lógica para colapsar/aplastar la barra inferior de chat con botón flotante ---
    
    const bottomPanel = document.querySelector('.bottom-panel');
    const hideChatBtn = document.querySelector('.hide-chat-btn');
    const floatingShowChatBtn = document.getElementById('floatingShowChatBtn');

    function toggleChat() {
        bottomPanel.classList.toggle('collapsed');
        const isCollapsed = bottomPanel.classList.contains('collapsed');

        if (isCollapsed) {
            hideChatBtn.innerHTML = '<i class="fas fa-eye"></i> Show chat';
            if (floatingShowChatBtn) floatingShowChatBtn.classList.add('show');
        } else {
            hideChatBtn.innerHTML = '<i class="fas fa-eye-slash"></i> Hide chat';
            if (floatingShowChatBtn) floatingShowChatBtn.classList.remove('show');
        }
    }

    if (hideChatBtn && bottomPanel) {
        hideChatBtn.addEventListener('click', toggleChat);
    }

    if (floatingShowChatBtn) {
        floatingShowChatBtn.addEventListener('click', toggleChat);
    }
});
