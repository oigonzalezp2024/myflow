// public/assets/js/master_replica_node.js

export function initMasterReplicaModule({ getNodesData, saveWorkflowToLocalStorage, renderWorkflow, hideAllContextMenus, addConnection }) {
    if (!document.getElementById('masterReplicaModal')) {
        const modalHTML = `
        <div id="masterReplicaModal" class="modal-overlay">
            <div class="modal-content" style="max-width: 680px;">
                <div class="modal-header">
                    <h2><i class="fas fa-server"></i> Configuración y Credenciales MySQL (Docker)</h2>
                    <button class="close-modal-btn" id="closeMasterReplicaModalBtn">&times;</button>
                </div>
                <div class="modal-body" style="max-height: 75vh; overflow-y: auto; padding-right: 5px;">
                    <input type="hidden" id="mrModalNodeId">
                    
                    <fieldset style="margin-bottom: 15px;">
                        <legend>Tipo de Nodo</legend>
                        <div class="input-group">
                            <label for="mrNodeType">Rol del Contenedor:</label>
                            <select id="mrNodeType" style="width: 100%; padding: 10px; background-color: #3a3a3a; border: 1px solid #555; border-radius: 4px; color: #e0e0e0;">
                                <option value="master">Nodo Master (Principal con Binlog)</option>
                                <option value="replica">Nodo Réplica (Esclavo)</option>
                            </select>
                        </div>
                    </fieldset>

                    <fieldset id="replicaConfigFieldset" style="display: none; margin-bottom: 15px;">
                        <legend>Asociación de Réplica</legend>
                        <div class="input-group">
                            <label for="mrMasterSelect">Vincular al Master:</label>
                            <select id="mrMasterSelect" style="width: 100%; padding: 10px; background-color: #3a3a3a; border: 1px solid #555; border-radius: 4px; color: #e0e0e0;">
                            </select>
                            <small style="color: #aaa; display: block; margin-top: 4px;">La réplica se conectará automáticamente a este Master.</small>
                        </div>
                        <div class="input-group" style="margin-top: 10px;">
                            <label for="mrMasterContainerName">Nombre del Contenedor Master:</label>
                            <input type="text" id="mrMasterContainerName" value="mysql-master" style="width: 100%; padding: 8px; background-color: #3a3a3a; border: 1px solid #555; border-radius: 4px; color: #e0e0e0;">
                        </div>
                    </fieldset>

                    <fieldset style="margin-bottom: 15px;">
                        <legend>Parámetros de Despliegue (Docker)</legend>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                            <div class="input-group">
                                <label for="mrContainerName">Nombre del Contenedor:</label>
                                <input type="text" id="mrContainerName" style="width: 100%; padding: 8px; background-color: #3a3a3a; border: 1px solid #555; border-radius: 4px; color: #e0e0e0;" placeholder="Ej. mysql-master">
                            </div>
                            <div class="input-group">
                                <label for="mrPort">Puerto Host:</label>
                                <input type="text" id="mrPort" style="width: 100%; padding: 8px; background-color: #3a3a3a; border: 1px solid #555; border-radius: 4px; color: #e0e0e0;" placeholder="Ej. 3306">
                            </div>
                            <div class="input-group">
                                <label for="mrDbName">Base de Datos Inicial:</label>
                                <input type="text" id="mrDbName" style="width: 100%; padding: 8px; background-color: #3a3a3a; border: 1px solid #555; border-radius: 4px; color: #e0e0e0;" value="testdb">
                            </div>
                            <div class="input-group">
                                <label for="mrServerId">Server ID:</label>
                                <input type="number" id="mrServerId" style="width: 100%; padding: 8px; background-color: #3a3a3a; border: 1px solid #555; border-radius: 4px; color: #e0e0e0;" value="1">
                            </div>
                            <div class="input-group">
                                <label for="mrRootPassword">Contraseña Root:</label>
                                <input type="password" id="mrRootPassword" style="width: 100%; padding: 8px; background-color: #3a3a3a; border: 1px solid #555; border-radius: 4px; color: #e0e0e0;" value="rootpassword">
                            </div>
                            <div class="input-group">
                                <label for="mrReplUser">Usuario Replicación:</label>
                                <input type="text" id="mrReplUser" style="width: 100%; padding: 8px; background-color: #3a3a3a; border: 1px solid #555; border-radius: 4px; color: #e0e0e0;" value="repl">
                            </div>
                            <div class="input-group" style="grid-column: span 2;">
                                <label for="mrReplPassword">Contraseña Replicación:</label>
                                <input type="password" id="mrReplPassword" style="width: 100%; padding: 8px; background-color: #3a3a3a; border: 1px solid #555; border-radius: 4px; color: #e0e0e0;" value="replpassword">
                            </div>
                        </div>
                    </fieldset>

                    <div id="mrConnectionInfoContainer" style="display: none; margin-bottom: 15px;">
                        <fieldset style="border: 1px solid #28a745; padding: 12px; border-radius: 6px; background: rgba(40, 167, 69, 0.05);">
                            <legend style="color: #28a745; font-weight: bold;"><i class="fas fa-key"></i> Credenciales y Puertos de Acceso</legend>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; font-size: 13px; color: #e0e0e0;">
                                <div><strong>Host:</strong> <span id="infoHost">localhost</span></div>
                                <div><strong>Puerto (Port):</strong> <span id="infoPort" style="color: #4da6ff; font-weight: bold;">-</span></div>
                                <div><strong>Usuario:</strong> <span id="infoUser">root</span></div>
                                <div><strong>Contrase&ntilde;a:</strong> <span id="infoPass" style="color: #ff9999;">(Vacía / Configurada)</span></div>
                                <div style="grid-column: span 2;"><strong>Base de Datos:</strong> <span id="infoDbName">-</span></div>
                            </div>
                        </fieldset>
                    </div>

                    <div id="mrResultContainer" style="display:none; margin-top: 15px;">
                        <fieldset style="margin-bottom: 15px;">
                            <legend>Estado del Despliegue (PUT)</legend>
                            <label><strong>Respuesta del Servidor / Metadatos:</strong></label>
                            <textarea id="mrResultOutput" rows="5" style="width: 100%; font-family: monospace; background: #1e1e1e; color: #a9b7c6; padding: 10px; border-radius: 4px; border: 1px solid #444;" readonly></textarea>
                        </fieldset>
                    </div>
                </div>
                <div class="modal-footer">
                    <button id="executeMasterReplicaBtn" class="action-btn" style="background-color: #28a745;">
                        <i class="fas fa-play"></i> Desplegar Infraestructura (PUT)
                    </button>
                </div>
            </div>
        </div>`;
        document.body.insertAdjacentHTML('beforeend', modalHTML);
    }

    const modal = document.getElementById('masterReplicaModal');
    const closeModalBtn = document.getElementById('closeMasterReplicaModalBtn');
    const modalNodeIdInput = document.getElementById('mrModalNodeId');
    const nodeTypeSelect = document.getElementById('mrNodeType');
    const replicaConfigFieldset = document.getElementById('replicaConfigFieldset');
    const masterSelect = document.getElementById('mrMasterSelect');
    
    // Nuevos campos de entrada
    const inputContainerName = document.getElementById('mrContainerName');
    const inputMasterContainerName = document.getElementById('mrMasterContainerName');
    const inputPort = document.getElementById('mrPort');
    const inputDbName = document.getElementById('mrDbName');
    const inputServerId = document.getElementById('mrServerId');
    const inputRootPassword = document.getElementById('mrRootPassword');
    const inputReplUser = document.getElementById('mrReplUser');
    const inputReplPassword = document.getElementById('mrReplPassword');

    const executeBtn = document.getElementById('executeMasterReplicaBtn');
    const resultContainer = document.getElementById('mrResultContainer');
    const resultOutput = document.getElementById('mrResultOutput');
    
    const connectionInfoContainer = document.getElementById('mrConnectionInfoContainer');
    const infoPort = document.getElementById('infoPort');
    const infoUser = document.getElementById('infoUser');
    const infoPass = document.getElementById('infoPass');
    const infoDbName = document.getElementById('infoDbName');

    nodeTypeSelect.addEventListener('change', (e) => {
        const isReplica = e.target.value === 'replica';
        replicaConfigFieldset.style.display = isReplica ? 'block' : 'none';
        if (isReplica) {
            populateMastersDropdown();
            inputContainerName.value = `mysql-replica-${Math.floor(Date.now() / 1000) % 10000}`;
            inputPort.value = '3307';
            inputServerId.value = Math.floor(Math.random() * 100) + 10;
        } else {
            inputContainerName.value = 'mysql-master';
            inputPort.value = '3306';
            inputServerId.value = '1';
        }
    });

    function populateMastersDropdown(selectedMasterId = null) {
        masterSelect.innerHTML = '';
        const nodesData = getNodesData();
        const currentNodeId = modalNodeIdInput.value;
        
        const masters = nodesData.filter(n => (n.type === 'mysql-master' || n.icon?.includes('fa-hdd')) && n.id !== currentNodeId);

        if (masters.length === 0) {
            const opt = document.createElement('option');
            opt.value = '';
            opt.textContent = '-- No hay otros nodos Master disponibles --';
            masterSelect.appendChild(opt);
            return;
        }

        masters.forEach(m => {
            const opt = document.createElement('option');
            opt.value = m.id;
            opt.textContent = `${m.title} (${m.id.substring(0, 6)})`;
            if (selectedMasterId && m.id === selectedMasterId) {
                opt.selected = true;
            }
            masterSelect.appendChild(opt);
        });
    }

    function showModal(nodeId) {
        if (hideAllContextMenus) hideAllContextMenus();
        const nodesData = getNodesData();
        const node = nodesData.find(n => n.id === nodeId);
        if (!node) return;

        modalNodeIdInput.value = nodeId;
        const config = node.mrConfig || { type: node.type === 'mysql-replica' ? 'replica' : 'master' };

        nodeTypeSelect.value = config.type || 'master';
        const isReplica = nodeTypeSelect.value === 'replica';
        
        replicaConfigFieldset.style.display = isReplica ? 'block' : 'none';
        if (isReplica) {
            populateMastersDropdown(config.masterId);
            if (config.masterContainerName) inputMasterContainerName.value = config.masterContainerName;
        }

        // Cargar valores guardados o defaults
        inputContainerName.value = config.containerName || (isReplica ? `mysql-replica-${Math.floor(Date.now() / 1000) % 10000}` : 'mysql-master');
        inputPort.value = config.port || (isReplica ? '3307' : '3306');
        inputDbName.value = config.dbName || 'testdb';
        inputServerId.value = config.serverId || (isReplica ? Math.floor(Math.random() * 100) + 10 : 1);
        inputRootPassword.value = config.rootPassword || 'rootpassword';
        inputReplUser.value = config.replUser || 'repl';
        inputReplPassword.value = config.replPassword || 'replpassword';

        if (config.credentials) {
            connectionInfoContainer.style.display = 'block';
            infoPort.textContent = config.credentials.port || inputPort.value;
            infoUser.textContent = config.credentials.username || 'root';
            infoPass.textContent = config.credentials.password ? '********' : '(Sin contraseña / Vacía)';
            infoDbName.textContent = config.credentials.dbName || inputDbName.value;
        } else {
            connectionInfoContainer.style.display = 'none';
        }

        if (config.lastResult) {
            resultContainer.style.display = 'block';
            resultOutput.value = JSON.stringify(config.lastResult, null, 2);
        } else {
            resultContainer.style.display = 'none';
            resultOutput.value = '';
        }

        modal.classList.add('show');
    }

    function hideModal() {
        if (modal) modal.classList.remove('show');
    }

    closeModalBtn.addEventListener('click', hideModal);

    executeBtn.addEventListener('click', async () => {
        const nodeId = modalNodeIdInput.value;
        const nodeType = nodeTypeSelect.value;
        const masterId = nodeType === 'replica' ? masterSelect.value : null;

        if (nodeType === 'replica' && (!masterId || masterId === '')) {
            alert('Por favor selecciona un nodo Master válido al cual vincular la réplica.');
            return;
        }

        const endpointUrl = nodeType === 'master'
            ? 'http://localhost:8080/replication/master'
            : 'http://localhost:8080/replication/replica';

        const requestBody = {
            container_name: inputContainerName.value,
            root_password: inputRootPassword.value,
            db_name: inputDbName.value,
            port: inputPort.value,
            server_id: parseInt(inputServerId.value) || 1,
            repl_user: inputReplUser.value,
            repl_password: inputReplPassword.value
        };

        if (nodeType === 'replica') {
            requestBody.master_name = inputMasterContainerName.value || 'mysql-master';
        }

        const originalText = executeBtn.innerHTML;
        executeBtn.disabled = true;
        executeBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando en Docker...';

        try {
            const response = await fetch(endpointUrl, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(requestBody)
            });

            const result = await response.json();
            const binlogFile = response.headers.get('X-Binlog-File') || 'N/A';
            const binlogPos = response.headers.get('X-Binlog-Pos') || 'N/A';

            const assignedPort = requestBody.port;
            const assignedUser = "root";
            const assignedPass = requestBody.root_password;
            const assignedDb = requestBody.db_name;

            const responsePayload = {
                success: response.ok,
                message: result.message || 'Operación completada',
                port: assignedPort,
                username: assignedUser,
                db_name: assignedDb,
                details: nodeType === 'replica' ? { binlogFile, binlogPos } : 'Master activo y listo'
            };

            connectionInfoContainer.style.display = 'block';
            infoPort.textContent = assignedPort;
            infoUser.textContent = assignedUser;
            infoPass.textContent = assignedPass ? '********' : '(Sin contraseña / Vacía)';
            infoDbName.textContent = assignedDb;

            resultContainer.style.display = 'block';
            resultOutput.value = JSON.stringify(responsePayload, null, 2);

            const nodesData = getNodesData();
            const nodeIndex = nodesData.findIndex(n => n.id === nodeId);

            if (nodeIndex !== -1) {
                nodesData[nodeIndex].type = nodeType === 'master' ? 'mysql-master' : 'mysql-replica';
                nodesData[nodeIndex].title = nodeType === 'master' ? 'MySQL Master' : 'MySQL Réplica';
                nodesData[nodeIndex].subtitle = nodeType === 'master' ? `Port: ${assignedPort} (Master)` : `Port: ${assignedPort} (Réplica)`;
                
                nodesData[nodeIndex].icon = nodeType === 'master' ? 'fas fa-hdd' : 'fas fa-server';
                
                nodesData[nodeIndex].mrConfig = {
                    type: nodeType,
                    masterId: masterId,
                    masterContainerName: requestBody.master_name,
                    containerName: requestBody.container_name,
                    port: requestBody.port,
                    dbName: requestBody.db_name,
                    serverId: requestBody.server_id,
                    rootPassword: requestBody.root_password,
                    replUser: requestBody.repl_user,
                    replPassword: requestBody.repl_password,
                    credentials: {
                        port: assignedPort,
                        username: assignedUser,
                        password: assignedPass,
                        dbName: assignedDb
                    },
                    lastResult: responsePayload
                };

                if (nodeType === 'replica' && masterId && addConnection) {
                    addConnection(masterId, nodeId);
                }

                renderWorkflow();
                saveWorkflowToLocalStorage();
            }

            alert('¡Contenedor configurado y operativo con éxito!');
        } catch (err) {
            console.error('Error al conectar con Go:', err);
            alert('No se pudo conectar con el endpoint local en el puerto 8080.');
        } finally {
            executeBtn.disabled = false;
            executeBtn.innerHTML = originalText;
        }
    });

    return {
        showModal,
        hideModal
    };
}
