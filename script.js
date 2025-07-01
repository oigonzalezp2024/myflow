document.addEventListener('DOMContentLoaded', () => {
    const workflowCanvas = document.getElementById('workflowCanvas');
    const linesSvg = document.getElementById('linesSvg');
    const addNodeBtn = document.getElementById('addNodeBtn');

    const downloadWorkflowBtn = document.getElementById('downloadWorkflowBtn');
    const uploadWorkflowInput = document.getElementById('uploadWorkflowInput');
    const uploadWorkflowBtn = document.getElementById('uploadWorkflowBtn');

    // --- Database Config Modal Elements (NEW) ---
    const databaseConfigModal = document.getElementById('databaseConfigModal');
    const modalNodeIdInput = document.getElementById('modalNodeId');
    const closeDbModalBtn = databaseConfigModal.querySelector('.close-modal-btn');
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

    let activeNode = null;
    let offsetX, offsetY;
    let nodesData = [];
    let connectionsData = [];

    // --- Variables para la creación de conexiones ---
    let isDrawingConnection = false;
    let startNodeId = null;
    let startPortType = null; // 'input' or 'output'
    let tempLine = null; // La línea SVG temporal que se dibuja
    let targetPortElement = null; // Referencia al puerto resaltado

    // --- Context Menu (for connections) ---
    const connectionContextMenu = document.createElement('ul'); // Renombrado para evitar conflicto
    connectionContextMenu.classList.add('context-menu');
    document.body.appendChild(connectionContextMenu);

    // --- Icon Selection Menu ---
    const iconSelectionMenu = document.createElement('div'); // Usamos div para flexbox
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


    // Función para generar un ID único para nodos y líneas (UUID v4 like)
    function generateUniqueId() {
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
            var r = Math.random() * 16 | 0, v = c == 'x' ? r : (r & 0x3 | 0x8);
            return v.toString(16);
        });
    }

    // --- Funciones de Renderizado ---

    /**
     * Crea un elemento de nodo HTML y lo añade al DOM.
     * @param {object} nodeConfig - Objeto con la configuración del nodo (id, type, title, subtitle, x, y, icon, dbConfig).
     * @returns {HTMLElement} El elemento HTML del nodo creado.
     */
    function createNodeElement(nodeConfig) {
        const nodeDiv = document.createElement('div');
        nodeDiv.classList.add('node', 'draggable-node');
        nodeDiv.id = nodeConfig.id;
        nodeDiv.style.left = `${nodeConfig.x}px`;
        nodeDiv.style.top = `${nodeConfig.y}px`;

        let nodeTypeClass = '';
        let iconClass = nodeConfig.icon || 'fas fa-cogs'; // Default icon
        let headerContent = '';
        let subtitleContent = nodeConfig.subtitle ? `<div class="node-subtitle">${nodeConfig.subtitle}</div>` : '';

        switch(nodeConfig.type) {
            case 'start':
                nodeTypeClass = 'node-start';
                headerContent = 'de chat';
                break;
            case 'code':
                nodeTypeClass = 'node-process';
                break;
            case 'api':
                nodeTypeClass = 'node-process';
                break;
            case 'edit-fields':
                nodeTypeClass = 'node-process';
                break;
            case 'disk':
                nodeTypeClass = 'node-end';
                break;
            case 'database': // NEW: Specific type for database nodes
                nodeTypeClass = 'node-process';
                headerContent = 'Database';
                break;
            default: // Generic or new node
                nodeTypeClass = 'node-process';
                headerContent = ''; 
                break;
        }

        let iconColorClass = '';
        if (nodeConfig.type === 'start') iconColorClass = 'type-chat-message';
        else if (nodeConfig.type === 'code') iconColorClass = 'type-code';
        else if (nodeConfig.type === 'api') iconColorClass = 'type-api';
        else if (nodeConfig.type === 'edit-fields') iconColorClass = 'type-edit-fields';
        else if (nodeConfig.type === 'disk') iconColorClass = 'type-disk';
        else if (nodeConfig.type === 'database') iconColorClass = 'type-database'; // Assign specific color for DB icon
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

        // --- NEW: Double-click listener for nodes (especially for DB nodes) ---
        nodeDiv.addEventListener('dblclick', (e) => {
            // Check if the double-clicked element is the node itself, not a port or icon
            if (!e.target.classList.contains('node-port') && !e.target.closest('.node-icon')) {
                // Check if this node has a database icon or type (or both)
                if (nodeConfig.icon && nodeConfig.icon.includes('fas fa-database')) {
                    showDatabaseConfigModal(nodeConfig.id);
                }
            }
        });
        // --- END NEW ---
       
        const deleteButton = nodeDiv.querySelector('.delete-node-btn');
        if (deleteButton) {
             deleteButton.addEventListener('click', (e) => {
                e.stopPropagation();
                deleteNode(nodeConfig.id);
            });
        }
       
        // Añadir listeners para puertos
        const outputPort = nodeDiv.querySelector('.output-port');
        if (outputPort) {
            outputPort.addEventListener('mousedown', startConnectionDrawing);
        }
        const inputPort = nodeDiv.querySelector('.input-port');
        if (inputPort) {
            inputPort.addEventListener('mousedown', startConnectionDrawing);
        }

        // Listener para el clic derecho en el icono del nodo
        const nodeIconElement = nodeDiv.querySelector('.node-icon');
        if (nodeIconElement) {
            nodeIconElement.addEventListener('contextmenu', (e) => {
                e.preventDefault(); // Evitar el menú contextual del navegador
                e.stopPropagation(); // Evitar que el evento llegue al canvas

                showIconSelectionMenu(e, nodeConfig.id);
            });
        }


        workflowCanvas.appendChild(nodeDiv);
        return nodeDiv;
    }

    /**
     * Crea un elemento <line> SVG para una conexión y lo añade al DOM.
     * @param {object} connectionConfig - Objeto con la configuración de la conexión (id, fromNodeId, toNodeId).
     * @returns {SVGLineElement} El elemento SVGLineElement creado.
     */
    function createLineElement(connectionConfig) {
        const line = document.createElementNS('http://www.w3.org/2000/svg', 'line');
        line.id = connectionConfig.lineId;
        line.classList.add('connection-line'); // Para estilos y eventos
        line.setAttribute('data-from-node', connectionConfig.fromNodeId);
        line.setAttribute('data-to-node', connectionConfig.toNodeId);

        line.addEventListener('contextmenu', (e) => {
            e.preventDefault(); // Evitar el menú contextual del navegador
            showConnectionContextMenu(e, connectionConfig.lineId);
        });

        linesSvg.appendChild(line);
        return line;
    }

    /**
     * Renderiza todos los nodos y líneas a partir de los datos `nodesData` y `connectionsData`.
     * Primero limpia el canvas, luego renderiza los nodos y finalmente las líneas.
     */
    function renderWorkflow() {
        workflowCanvas.querySelectorAll('.node').forEach(node => node.remove());
        linesSvg.querySelectorAll('line').forEach(line => line.remove());

        nodesData.forEach(nodeConfig => {
            createNodeElement(nodeConfig);
        });

        connectionsData.forEach(conn => {
            createLineElement(conn);
        });

        // Este bucle es crucial para posicionar las líneas después de que los nodos estén en el DOM
        nodesData.forEach(nodeConfig => {
            const nodeElement = document.getElementById(nodeConfig.id);
            if (nodeElement) {
                updateConnectedLines(nodeElement);
            }
        });
    }

    // --- Funciones de Gestión de Datos (CRUD) ---

    /**
     * Añade un nuevo nodo a `nodesData` y lo renderiza.
     * @param {string} type - Tipo de nodo (ej: 'code', 'api', 'generic', 'database').
     * @param {string} title - Título del nodo.
     * @param {string} [subtitle=''] - Subtítulo opcional.
     * @param {number} x - Posición X inicial.
     * @param {number} y - Posición Y inicial.
     * @param {string} [icon='fas fa-cogs'] - Icono inicial para el nodo (Font Awesome class).
     */
    function addNode(type, title, subtitle = '', x, y, icon = 'fas fa-cogs') {
        const newNode = {
            id: generateUniqueId(),
            type: type,
            title: title,
            subtitle: subtitle,
            x: x,
            y: y,
            icon: icon, // Store the icon class
            dbConfig: type === 'database' ? {} : undefined // Initialize empty dbConfig for database nodes
        };
        nodesData.push(newNode);
        createNodeElement(newNode);
        saveWorkflowToLocalStorage();
    }

    /**
     * Actualiza la posición de un nodo en `nodesData`.
     * Se llama durante el arrastre.
     * @param {string} nodeId - ID del nodo a actualizar.
     * @param {number} newX - Nueva posición X.
     * @param {number} newY - Nueva posición Y.
     */
    function updateNodePosition(nodeId, newX, newY) {
        const nodeIndex = nodesData.findIndex(node => node.id === nodeId);
        if (nodeIndex !== -1) {
            nodesData[nodeIndex].x = newX;
            nodesData[nodeIndex].y = newY;
        }
    }

    /**
     * Actualiza el icono de un nodo en `nodesData` y lo renderiza.
     * @param {string} nodeId - ID del nodo a actualizar.
     * @param {string} newIconClass - La nueva clase de Font Awesome para el icono.
     */
    function updateNodeIcon(nodeId, newIconClass) {
        const nodeIndex = nodesData.findIndex(node => node.id === nodeId);
        if (nodeIndex !== -1) {
            nodesData[nodeIndex].icon = newIconClass;
            // Update node type if icon implies a specific functional type
            if (newIconClass.includes('fas fa-database')) {
                nodesData[nodeIndex].type = 'database';
                // Initialize dbConfig if not present and changing to database type
                if (!nodesData[nodeIndex].dbConfig) {
                    nodesData[nodeIndex].dbConfig = {};
                }
            } else if (nodesData[nodeIndex].type === 'database' && !newIconClass.includes('fas fa-database')) {
                // If changing away from database icon, revert type (or to 'generic')
                nodesData[nodeIndex].type = 'generic'; // Or a more appropriate default type
                delete nodesData[nodeIndex].dbConfig; // Remove dbConfig if no longer a DB node
            }
            
            // Actualizar el DOM directamente para no recrear todo el nodo
            const nodeElement = document.getElementById(nodeId);
            if (nodeElement) {
                const iconElement = nodeElement.querySelector('.node-icon i');
                if (iconElement) {
                    iconElement.className = ''; 
                    iconElement.classList.add(...newIconClass.split(' ')); 

                    // Also update the node-icon's color class based on the new type
                    const nodeIconContainer = nodeElement.querySelector('.node-icon');
                    nodeIconContainer.classList.remove('type-chat-message', 'type-code', 'type-api', 'type-edit-fields', 'type-disk', 'type-database', 'type-generic');
                    
                    let newIconColorClass = '';
                    if (nodesData[nodeIndex].type === 'start') newIconColorClass = 'type-chat-message';
                    else if (nodesData[nodeIndex].type === 'code') newIconColorClass = 'type-code';
                    else if (nodesData[nodeIndex].type === 'api') newIconColorClass = 'type-api';
                    else if (nodesData[nodeIndex].type === 'edit-fields') newIconColorClass = 'type-edit-fields';
                    else if (nodesData[nodeIndex].type === 'disk') newIconColorClass = 'type-disk';
                    else if (nodesData[nodeIndex].type === 'database') newIconColorClass = 'type-database';
                    else newIconColorClass = 'type-generic';

                    nodeIconContainer.classList.add(newIconColorClass);
                }
            }
            saveWorkflowToLocalStorage();
            console.log(`Icono del nodo ${nodeId} actualizado a: "${newIconClass}", tipo a: "${nodesData[nodeIndex].type}"`);
        }
    }


    /**
     * Elimina un nodo y sus conexiones de `nodesData` y `connectionsData`, y lo quita del DOM.
     * @param {string} nodeId - ID del nodo a eliminar.
     */
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
        alert(`Nodo ${nodeId} eliminado y datos actualizados.`);
    }

    /**
     * Añade una nueva conexión a `connectionsData` y la renderiza.
     * @param {string} fromNodeId - ID del nodo de origen.
     * @param {string} toNodeId - ID del nodo de destino.
     */
    function addConnection(fromNodeId, toNodeId) {
        // Evitar conexiones a sí mismo
        if (fromNodeId === toNodeId) {
            alert('No se puede conectar un nodo consigo mismo.');
            return;
        }
        // Evitar duplicados (misma conexión de A a B)
        const exists = connectionsData.some(conn =>
            (conn.fromNodeId === fromNodeId && conn.toNodeId === toNodeId)
        );
        if (exists) {
            alert('Esta conexión ya existe.');
            return;
        }

        const newConnection = {
            lineId: `line-${fromNodeId}-${toNodeId}-${generateUniqueId().substring(0, 4)}`, // ID más descriptivo
            fromNodeId: fromNodeId,
            toNodeId: toNodeId
        };
        connectionsData.push(newConnection);
        
        // Renderizar la nueva línea y actualizar todas las líneas conectadas
        createLineElement(newConnection);
        updateConnectedLines(document.getElementById(fromNodeId)); // Actualizar la línea que acaba de crearse
        updateConnectedLines(document.getElementById(toNodeId)); // Asegurarse de que el destino también se actualice si ya tenía líneas

        saveWorkflowToLocalStorage();
        console.log(`Conexión añadida: ${fromNodeId} -> ${toNodeId}`);
    }

    /**
     * Elimina una conexión específica.
     * @param {string} lineId - El ID de la línea a eliminar.
     */
    function deleteConnection(lineId) {
        connectionsData = connectionsData.filter(conn => conn.lineId !== lineId);
        const lineElement = document.getElementById(lineId);
        if (lineElement) {
            lineElement.remove();
            saveWorkflowToLocalStorage();
            console.log(`Conexión ${lineId} eliminada.`);
        }
    }


    // --- Funciones de Persistencia en localStorage ---

    const LOCAL_STORAGE_KEY = 'n8nWorkflowData';

    /**
     * Guarda el estado actual de `nodesData` y `connectionsData` en `localStorage`.
     */
    function saveWorkflowToLocalStorage() {
        const dataToSave = {
            nodes: nodesData,
            connections: connectionsData
        };
        try {
            localStorage.setItem(LOCAL_STORAGE_KEY, JSON.stringify(dataToSave));
            console.log('Workflow guardado automáticamente en el navegador.');
        } catch (e) {
            console.error('Error al guardar el workflow en localStorage:', e);
            alert('Error al guardar el workflow automáticamente. El almacenamiento podría estar lleno.');
        }
    }

    /**
     * Carga el estado del workflow desde `localStorage` y lo renderiza.
     */
    function loadWorkflowFromLocalStorage() {
        try {
            const storedData = localStorage.getItem(LOCAL_STORAGE_KEY);
            if (storedData) {
                const parsedData = JSON.parse(storedData);
                nodesData = parsedData.nodes || [];
                connectionsData = parsedData.connections || [];
                renderWorkflow();
                console.log('Workflow cargado exitosamente desde localStorage.');
            } else {
                console.log('No hay datos de workflow guardados en localStorage.');
            }
        } catch (e) {
            console.error('Error al cargar el workflow desde localStorage:', e);
            alert('Error al cargar el workflow desde localStorage. Datos corruptos o no válidos.');
        }
    }

    // --- Funciones de Persistencia en Archivo JSON ---

    /**
     * Descarga el workflow actual como un archivo JSON.
     */
    function downloadWorkflowAsJson() {
        const dataToDownload = {
            nodes: nodesData,
            connections: connectionsData
        };
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
        alert('Workflow descargado como workflow.json');
    }

    /**
     * Carga un workflow desde un archivo JSON seleccionado por el usuario.
     * @param {Event} event - El evento change del input file.
     */
    function uploadWorkflowFromJsonFile(event) {
        const file = event.target.files[0];
        if (!file) {
            return;
        }

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
                    alert('Workflow cargado exitosamente desde el archivo JSON.');
                } else {
                    alert('El archivo JSON no parece ser un formato de workflow válido.');
                }
            } catch (error) {
                console.error('Error al parsear el archivo JSON:', error);
                alert('Error al cargar el archivo: No es un JSON válido o el formato es incorrecto.');
            } finally {
                event.target.value = '';
            }
        };
        reader.onerror = (e) => {
            console.error('Error al leer el archivo:', e);
            alert('Error al leer el archivo.');
        };
        reader.readAsText(file);
    }


    // --- Funciones de Drag & Drop de Nodos ---

    function addDragListeners(nodeElement) {
        nodeElement.addEventListener('mousedown', (e) => {
            // No iniciar arrastre si el clic es en un puerto o el botón de eliminar
            // O si el clic es en el título editable que está en modo edición
            // O si es el icono del nodo (para permitirle el clic derecho para el menú de iconos)
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
            hideAllContextMenus(); // Ocultar cualquier menú contextual abierto
        });
    }

    document.addEventListener('mousemove', (e) => {
        // Mover nodo si está en modo de arrastre de nodo
        if (activeNode) {
            const canvasRect = workflowCanvas.getBoundingClientRect();

            let newXAbsolute = e.clientX - offsetX;
            let newYAbsolute = e.clientY - offsetY;

            let newX = newXAbsolute - canvasRect.left;
            let newY = newYAbsolute - canvasRect.top; 

            const nodeRect = activeNode.getBoundingClientRect();

            // Limitar el nodo para que no se salga del canvas
            if (newX < 0) newX = 0;
            if (newY < 0) newY = 0;
            if (newX + nodeRect.width > canvasRect.width) newX = canvasRect.width - nodeRect.width;
            if (newY + nodeRect.height > canvasRect.height) newY = canvasRect.height - nodeRect.height;

            activeNode.style.left = `${newX}px`;
            activeNode.style.top = `${newY}px`;

            updateNodePosition(activeNode.id, newX, newY);
            updateConnectedLines(activeNode);
        }

        // Dibujar línea temporal si está en modo de dibujo de conexión
        if (isDrawingConnection && tempLine) {
            const canvasRect = workflowCanvas.getBoundingClientRect();
            const currentX = e.clientX - canvasRect.left;
            const currentY = e.clientY - canvasRect.top;

            // Obtener coordenadas del puerto de inicio
            const startNode = document.getElementById(startNodeId);
            if (!startNode) { // Seguridad en caso de que el nodo de inicio se elimine antes de soltar
                console.error("Start node not found for connection drawing.");
                resetConnectionDrawing();
                return;
            }
            const startCoords = getPortCoordinates(startNode, startPortType);

            tempLine.setAttribute('x1', startCoords.x);
            tempLine.setAttribute('y1', startCoords.y);
            tempLine.setAttribute('x2', currentX);
            tempLine.setAttribute('y2', currentY);

            // Resaltar puertos válidos como destino
            const target = e.target;
            if (target.classList.contains('node-port') &&
                target.dataset.nodeId !== startNodeId && // No el mismo nodo
                ((startPortType === 'output' && target.dataset.portType === 'input') ||
                 (startPortType === 'input' && target.dataset.portType === 'output')) // Puerto opuesto
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

    document.addEventListener('mouseup', (e) => {
        // Terminar arrastre de nodo
        if (activeNode) {
            activeNode.classList.remove('is-dragging');
            activeNode = null;
            saveWorkflowToLocalStorage();
        }

        // Terminar dibujo de conexión
        if (isDrawingConnection) {
            if (tempLine) {
                tempLine.remove(); // Eliminar la línea temporal
                tempLine = null;
            }

            if (targetPortElement) {
                targetPortElement.classList.remove('highlight');
                const endNodeId = targetPortElement.dataset.nodeId;
                const endPortType = targetPortElement.dataset.portType;

                // Determinar fromNodeId y toNodeId basados en los tipos de puerto de inicio y fin
                let finalFromNodeId, finalToNodeId;
                if (startPortType === 'output' && endPortType === 'input') {
                    finalFromNodeId = startNodeId;
                    finalToNodeId = endNodeId;
                } else if (startPortType === 'input' && endPortType === 'output') {
                    // Si se arrastró desde un input a un output, invertir la conexión
                    finalFromNodeId = endNodeId;
                    finalToNodeId = startNodeId;
                } else {
                    // Tipos de puertos incompatibles para una conexión (e.g., input a input, output a output)
                    console.log('Tipos de puertos incompatibles o arrastre inválido.');
                    resetConnectionDrawing();
                    return;
                }

                addConnection(finalFromNodeId, finalToNodeId);
            }
            resetConnectionDrawing();
        }
    });


    // --- Funciones de Conexión de Nodos ---

    /**
     * Inicia el proceso de dibujo de una conexión desde un puerto.
     * @param {MouseEvent} e - El evento de mouse.
     */
    function startConnectionDrawing(e) {
        // Asegurarse de que el botón izquierdo del mouse fue presionado y no estamos ya dibujando
        if (e.button !== 0 || isDrawingConnection) return;

        const portElement = e.target.closest('.node-port');
        if (!portElement) return;

        e.stopPropagation(); // Prevenir que el evento llegue al canvas y active el drag de nodo

        isDrawingConnection = true;
        startNodeId = portElement.dataset.nodeId;
        startPortType = portElement.dataset.portType;

        const canvasRect = workflowCanvas.getBoundingClientRect();
        const startCoords = getPortCoordinates(document.getElementById(startNodeId), startPortType);

        tempLine = document.createElementNS('http://www.w3.org/2000/svg', 'line');
        tempLine.classList.add('temp-line');
        tempLine.setAttribute('x1', startCoords.x);
        tempLine.setAttribute('y1', startCoords.y);
        tempLine.setAttribute('x2', e.clientX - canvasRect.left); // Posición inicial del cursor
        tempLine.setAttribute('y2', e.clientY - canvasRect.top);
        linesSvg.appendChild(tempLine);

        // Ocultar cualquier menú contextual abierto
        hideAllContextMenus();
    }

    /**
     * Reinicia las variables de estado de dibujo de conexión.
     */
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


    /**
     * Calcula las coordenadas de los puertos de conexión de un nodo.
     * @param {HTMLElement} node - El elemento DOM del nodo.
     * @param {'input'|'output'} type - El tipo de puerto.
     * @returns {{x: number, y: number}} Las coordenadas X e Y del puerto relativas al canvas.
     */
    function getPortCoordinates(node, type) {
        const nodeRect = node.getBoundingClientRect();
        const canvasRect = workflowCanvas.getBoundingClientRect();

        let x, y;
        if (type === 'output') {
            x = nodeRect.right - canvasRect.left;
        } else { // input
            x = nodeRect.left - canvasRect.left;
        }
        y = nodeRect.top - canvasRect.top + nodeRect.height / 2;

        return { x: x, y: y };
    }

    /**
     * Actualiza las posiciones de todas las líneas SVG conectadas a un nodo movido.
     * @param {HTMLElement} movedNode - El nodo HTML que se acaba de mover.
     */
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

    // --- Funciones del Menú Contextual (Clic Derecho en Conexión) ---

    /**
     * Muestra el menú contextual para una conexión.
     * @param {MouseEvent} e - El evento de clic derecho.
     * @param {string} lineId - El ID de la línea en la que se hizo clic.
     */
    function showConnectionContextMenu(e, lineId) {
        hideAllContextMenus(); // Ocultar todos los menús

        connectionContextMenu.innerHTML = ''; // Limpiar elementos anteriores
        const deleteItem = document.createElement('li');
        deleteItem.textContent = 'Eliminar conexión';
        deleteItem.addEventListener('click', () => {
            deleteConnection(lineId);
            hideConnectionContextMenu();
        });
        connectionContextMenu.appendChild(deleteItem);

        // Posicionar el menú cerca del cursor, ajustando para evitar desbordamiento
        let x = e.clientX;
        let y = e.clientY;
        const menuWidth = 160; // Ancho aproximado del menú
        const menuHeight = 50; // Alto aproximado del menú (para un solo elemento)

        if (x + menuWidth > window.innerWidth) {
            x = window.innerWidth - menuWidth - 10; // 10px de margen
        }
        if (y + menuHeight > window.innerHeight) {
            y = window.innerHeight - menuHeight - 10;
        }

        connectionContextMenu.style.left = `${x}px`;
        connectionContextMenu.style.top = `${y}px`;
        connectionContextMenu.classList.add('show');
    }

    /**
     * Oculta el menú contextual de la conexión.
     */
    function hideConnectionContextMenu() {
        connectionContextMenu.classList.remove('show');
    }

    // --- Funciones del Menú de Selección de Iconos ---

    /**
     * Muestra el menú de selección de iconos para un nodo.
     * @param {MouseEvent} e - El evento de clic derecho.
     * @param {string} nodeId - El ID del nodo al que pertenece el icono.
     */
    function showIconSelectionMenu(e, nodeId) {
        hideAllContextMenus(); // Ocultar todos los menús

        iconSelectionMenu.innerHTML = ''; // Limpiar iconos anteriores

        availableIcons.forEach(iconClass => {
            const iconItem = document.createElement('div');
            iconItem.classList.add('icon-item');
            iconItem.innerHTML = `<i class="${iconClass}"></i>`;
            iconItem.title = iconClass.replace('fas fa-', ''); // Mostrar nombre del icono en tooltip

            iconItem.addEventListener('click', () => {
                updateNodeIcon(nodeId, iconClass);
                hideIconSelectionMenu();
            });
            iconSelectionMenu.appendChild(iconItem);
        });

        // Posicionar el menú cerca del icono
        let x = e.clientX;
        let y = e.clientY;
        // Ajustar la posición para que el menú aparezca "justo al lado" del icono.
        // Se puede ajustar el offset (ej. 20) si se prefiere que no cubra el icono.
        x = e.clientX + 10; 
        y = e.clientY + 10;

        const menuRect = iconSelectionMenu.getBoundingClientRect(); 

        // Ajustar posición si se sale de la ventana
        if (x + menuRect.width > window.innerWidth) {
            x = window.innerWidth - menuRect.width - 10;
        }
        if (y + menuRect.height > window.innerHeight) {
            y = window.innerHeight - menuRect.height - 10;
        }
        if (x < 0) x = 10; // Evitar que se salga por la izquierda
        if (y < 0) y = 10; // Evitar que se salga por arriba


        iconSelectionMenu.style.left = `${x}px`;
        iconSelectionMenu.style.top = `${y}px`;
        iconSelectionMenu.classList.add('show');
    }

    /**
     * Oculta el menú de selección de iconos.
     */
    function hideIconSelectionMenu() {
        iconSelectionMenu.classList.remove('show');
    }

    /**
     * Oculta todos los menús contextuales.
     */
    function hideAllContextMenus() {
        hideConnectionContextMenu();
        hideIconSelectionMenu();
        hideDatabaseConfigModal(); // NEW: Also hide the DB config modal
    }

    // --- Funciones para el Modal de Configuración de Base de Datos (NEW) ---

    /**
     * Muestra el modal de configuración de base de datos para un nodo específico.
     * @param {string} nodeId - El ID del nodo a configurar.
     */
    function showDatabaseConfigModal(nodeId) {
        hideAllContextMenus(); // Asegurarse de que no haya otros menús abiertos

        const node = nodesData.find(n => n.id === nodeId);
        if (!node) {
            console.error('Nodo no encontrado para configurar la base de datos:', nodeId);
            return;
        }

        modalNodeIdInput.value = nodeId;

        // Populate fields from node's dbConfig or set defaults
        const dbConfig = node.dbConfig || {};
        dbHostInput.value = dbConfig.host || '';
        dbPortInput.value = dbConfig.port || '';
        dbUsernameInput.value = dbConfig.username || '';
        dbPasswordInput.value = dbConfig.password || '';
        dbNameInput.value = dbConfig.dbName || '';
        dbTableNameInput.value = dbConfig.tableName || '';

        // Populate dynamic fields
        dbFieldsContainer.innerHTML = ''; // Clear previous fields
        (dbConfig.fields || []).forEach(field => {
            addDynamicField(field.key, field.value);
        });
        // If no fields, add one empty row by default
        if ((dbConfig.fields || []).length === 0) {
             addDynamicField('', '');
        }

        databaseConfigModal.classList.add('show');
    }

    /**
     * Oculta el modal de configuración de base de datos.
     */
    function hideDatabaseConfigModal() {
        databaseConfigModal.classList.remove('show');
        // Clear inputs when closing if desired, or leave them for next time
        // dbHostInput.value = ''; // Uncomment to clear on close
        // ...
    }

    /**
     * Añade un par de campos de entrada (clave-valor) para los campos a diligenciar.
     * @param {string} key - Valor inicial para la clave.
     * @param {string} value - Valor inicial para el valor.
     */
    function addDynamicField(key = '', value = '') {
        const row = document.createElement('div');
        row.classList.add('dynamic-field-row');
        row.innerHTML = `
            <input type="text" class="field-key" placeholder="Nombre del Campo" value="${key}">
            <input type="text" class="field-value" placeholder="Valor del Campo" value="${value}">
            <button class="remove-field-btn"><i class="fas fa-trash-alt"></i></button>
        `;
        dbFieldsContainer.appendChild(row);

        row.querySelector('.remove-field-btn').addEventListener('click', () => {
            row.remove();
        });
    }

    /**
     * Guarda la configuración de la base de datos desde el modal al nodo.
     * Envía la petición al backend PHP para crear/verificar la BD y la tabla,
     * y recuperar los datos.
     */
    async function saveDatabaseConfig() { // Asegúrate de que es una función asíncrona
        const nodeId = modalNodeIdInput.value;
        const nodeIndex = nodesData.findIndex(n => n.id === nodeId);

        if (nodeIndex === -1) {
            console.error('Nodo no encontrado al intentar guardar config DB:', nodeId);
            return;
        }

        const fields = [];
        dbFieldsContainer.querySelectorAll('.dynamic-field-row').forEach(row => {
            const key = row.querySelector('.field-key').value.trim();
            const value = row.querySelector('.field-value').value.trim();
            if (key && value) {
                // Ambos clave (nombre de columna) y valor (tipo SQL y restricciones) deben existir
                fields.push({ key, value });
            }
        });

        // Recopilar todos los datos del formulario
        const dbConfigToSave = {
            host: dbHostInput.value.trim(),
            port: dbPortInput.value.trim(),
            username: dbUsernameInput.value.trim(),
            password: dbPasswordInput.value.trim(),
            dbName: dbNameInput.value.trim(),
            tableName: dbTableNameInput.value.trim(),
            fields: fields // Array de objetos {key: 'columna', value: 'VARCHAR(255) NOT NULL'}
        };

        // --- Envío al Backend PHP para creación/verificación de BD/Tabla y recuperación de datos ---
        try {
            // !!! MUY IMPORTANTE: Reemplaza esta URL con la ubicación real de tu archivo PHP en tu servidor web !!!
            // Ejemplos:
            // 'http://localhost/nombre_carpeta_backend/create_db_table.php'
            // 'http://localhost:8080/create_db_table.php' (si tu servidor Apache/Nginx usa otro puerto)
            const response = await fetch('http://localhost/n8n-project/replica4/create_db_table.php', { // <--- CAMBIA ESTA URL
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(dbConfigToSave)
            });

            const result = await response.json(); // Parsea la respuesta JSON del backend

            if (response.ok) { // Si la respuesta HTTP es 2xx (ej. 200 OK)
                alert(`Configuración de base de datos guardada y (backend) ${result.message}`);
                console.log('Respuesta del backend:', result.message);
                
                // Si hay datos de tabla, puedes hacer algo con ellos aquí
                if (result.tableData && result.tableData.length > 0) {
                    console.log('Datos de la tabla recuperados:', result.tableData);
                    // Aquí podrías mostrar estos datos en alguna parte de tu interfaz
                    // Por ejemplo, poblar una tabla HTML en el frontend.
                } else {
                    console.log('No hay datos en la tabla o no se recuperaron.');
                }

                // Guarda la configuración en el frontend solo si el backend fue exitoso
                nodesData[nodeIndex].dbConfig = dbConfigToSave;
                saveWorkflowToLocalStorage();
            } else {
                // Si la respuesta no es 2xx, el backend envió un error (ej. 400, 500)
                alert(`Error del backend: ${result.message || 'Error desconocido'}`);
                console.error('Error del backend:', result.message);
            }

        } catch (error) {
            console.error('Error al comunicarse con el servidor backend:', error);
            alert('Error de conexión con el servidor backend. Asegúrate de que esté corriendo y la URL sea correcta.');
        } finally {
            hideDatabaseConfigModal(); // Ocultar el modal siempre al finalizar
        }
    }

    // Ocultar cualquier menú contextual si se hace clic en cualquier otro lugar
    document.addEventListener('click', (e) => {
        // Only hide if not clicking inside any context menu or modal
        if (!e.target.closest('.context-menu') && !e.target.closest('.icon-selection-menu') && !e.target.closest('.modal-content')) {
            hideAllContextMenus();
        }
    });

    workflowCanvas.addEventListener('contextmenu', (e) => {
        // Prevenir el menú por defecto en el canvas si no se hizo clic en una línea o un icono
        // y ocultar los menús si están abiertos
        if (!e.target.classList.contains('connection-line') && !e.target.closest('.node-icon')) {
            e.preventDefault();
            hideAllContextMenus();
        }
    });

    // --- Edición de Título de Nodo ---
    workflowCanvas.addEventListener('focusout', (e) => {
        // Escuchar cuando un elemento editable pierde el foco
        if (e.target.classList.contains('node-title') && e.target.getAttribute('contenteditable') === 'true') {
            const nodeId = e.target.dataset.nodeId;
            const newTitle = e.target.textContent.trim(); // Obtener el nuevo texto y limpiar espacios

            const nodeIndex = nodesData.findIndex(node => node.id === nodeId);
            if (nodeIndex !== -1 && nodesData[nodeIndex].title !== newTitle) {
                nodesData[nodeIndex].title = newTitle;
                saveWorkflowToLocalStorage();
                console.log(`Título del nodo ${nodeId} actualizado a: "${newTitle}"`);
            }
        }
    });

    workflowCanvas.addEventListener('keydown', (e) => {
        // Escuchar la tecla Enter para finalizar la edición y guardar
        if (e.target.classList.contains('node-title') && e.target.getAttribute('contenteditable') === 'true' && e.key === 'Enter') {
            e.preventDefault(); // Evitar un salto de línea en el campo editable
            e.target.blur(); // Quitar el foco del elemento para disparar el 'focusout' y guardar
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
        { id: 'node-db-example', type: 'database', title: 'Database Op', subtitle: 'MySQL users', x: 450, y: 400, icon: 'fas fa-database', dbConfig: {host: 'my.db.server', port: '3306', username: 'user', password: 'pass', dbName: 'my_app_db', tableName: 'customers', fields: [{key: 'id', value: '123'}, {key: 'status', value: 'active'}]} }, // Example DB node
        { id: 'node-end', type: 'disk', title: 'Read/Write Files From Disk', subtitle: 'Write File to Disk', x: 1600, y: 150, icon: 'fas fa-file-invoice' },
    ];

    const initialConnections = [
        { lineId: 'line-node-start-node-aiquery4-0001', fromNodeId: 'node-start', toNodeId: 'node-aiquery4' },
        { lineId: 'line-node-aiquery4-node-aiquery1-0002', fromNodeId: 'node-aiquery4', toNodeId: 'node-aiquery1' },
        { lineId: 'line-node-aiquery1-node-aiexecute-0003', fromNodeId: 'node-aiquery1', toNodeId: 'node-aiexecute' },
        { lineId: 'line-node-aiexecute-node-code-0004', fromNodeId: 'node-aiexecute', toNodeId: 'node-code' },
        { lineId: 'line-node-code-node-editfields-0005', fromNodeId: 'node-code', toNodeId: 'node-editfields' },
        { lineId: 'line-node-editfields-node-end-0006', fromNodeId: 'node-editfields', toNodeId: 'node-end' },
    ];

    addNodeBtn.addEventListener('click', () => {
        const randomX = Math.floor(Math.random() * (workflowCanvas.offsetWidth - 200));
        const randomY = Math.floor(Math.random() * (workflowCanvas.offsetHeight - 300));
        // Provide a default icon for new nodes, and a generic type
        addNode('generic', 'New Node', '', randomX, randomY, 'fas fa-cogs');
    });

    downloadWorkflowBtn.addEventListener('click', downloadWorkflowAsJson);

    uploadWorkflowBtn.addEventListener('click', () => {
        uploadWorkflowInput.click();
    });
    uploadWorkflowInput.addEventListener('change', uploadWorkflowFromJsonFile);

    // --- Event Listeners para el Modal de Configuración de Base de Datos (NEW) ---
    closeDbModalBtn.addEventListener('click', hideDatabaseConfigModal);
    saveDbConfigBtn.addEventListener('click', saveDatabaseConfig);
    addFieldBtn.addEventListener('click', () => addDynamicField('', '')); // Add empty field
    databaseConfigModal.addEventListener('click', (e) => {
        // Close modal if clicking on the overlay itself, not inside the content
        if (e.target === databaseConfigModal) {
            hideDatabaseConfigModal();
        }
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && databaseConfigModal.classList.contains('show')) {
            hideDatabaseConfigModal();
        }
    });


    const storedData = localStorage.getItem(LOCAL_STORAGE_KEY);
    if (storedData) {
        loadWorkflowFromLocalStorage();
    } else {
        nodesData = [...initialNodes];
        connectionsData = [...initialConnections];
        renderWorkflow();
        saveWorkflowToLocalStorage();
    }
});