// public/assets/js/rag_node.js

export function initRagNodeModule({ getNodesData, saveWorkflowToLocalStorage, renderWorkflow, hideAllContextMenus }) {
    const ragServerModal = document.getElementById('ragServerModal');
    const ragModalNodeIdInput = document.getElementById('ragModalNodeId');
    const closeRagModalBtn = document.getElementById('closeRagModalBtn');
    const createRagServerBtn = document.getElementById('createRagServerBtn');
    const ragDestinationFolderInput = document.getElementById('ragDestinationFolder');
    const ragResultContainer = document.getElementById('ragResultContainer');
    const ragResultOutput = document.getElementById('ragResultOutput');

    function showRagServerModal(nodeId) {
        if (hideAllContextMenus) hideAllContextMenus();
        const nodesData = getNodesData();
        const node = nodesData.find(n => n.id === nodeId);
        if (!node || !ragServerModal) return;

        ragModalNodeIdInput.value = nodeId;
        const config = node.ragConfig || {};

        if (ragDestinationFolderInput) {
            ragDestinationFolderInput.value = config.destination_folder || '';
        }

        if (ragResultContainer) {
            ragResultContainer.style.display = config.lastResult ? 'block' : 'none';
        }
        if (ragResultOutput) {
            ragResultOutput.value = config.lastResult ? JSON.stringify(config.lastResult, null, 2) : '';
        }

        ragServerModal.classList.add('show');
    }

    function hideRagServerModal() {
        if (ragServerModal) {
            ragServerModal.classList.remove('show');
        }
    }

    if (closeRagModalBtn) {
        closeRagModalBtn.addEventListener('click', hideRagServerModal);
    }

    if (createRagServerBtn) {
        createRagServerBtn.addEventListener('click', async () => {
            const nodeId = ragModalNodeIdInput.value;
            const destinationFolder = ragDestinationFolderInput ? ragDestinationFolderInput.value.trim() : '';

            if (!destinationFolder) {
                alert('Por favor ingresa el nombre de la carpeta de destino (destination_folder).');
                return;
            }

            const originalBtnHtml = createRagServerBtn.innerHTML;
            createRagServerBtn.disabled = true;
            createRagServerBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Desplegando RAG y Docker...';

            try {
                const response = await fetch('./controllers_develop/create_ragserver/index.php', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        destination_folder: destinationFolder
                    })
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    if (ragResultOutput) {
                        ragResultOutput.value = JSON.stringify(result, null, 2);
                    }
                    if (ragResultContainer) {
                        ragResultContainer.style.display = 'block';
                    }

                    const nodesData = getNodesData();
                    const nodeIndex = nodesData.findIndex(n => n.id === nodeId);
                    if (nodeIndex !== -1) {
                        nodesData[nodeIndex].ragConfig = {
                            destination_folder: destinationFolder,
                            ports: result.data?.ports || {},
                            index_url: result.data?.index_url || '',
                            lastResult: result
                        };
                        nodesData[nodeIndex].subtitle = `RAG: ${destinationFolder} (HTTP:${result.data?.ports?.http || '?'})`;
                        renderWorkflow();
                        saveWorkflowToLocalStorage();
                    }

                    alert(`¡Éxito! ${result.message}`);
                } else {
                    const errorMsg = result.error?.message || result.message || 'Error desconocido al desplegar RAG.';
                    alert(`Error del servidor: ${errorMsg}`);
                }
            } catch (err) {
                console.error('Error al comunicarse con create_ragserver:', err);
                alert('Error de conexión con el controlador PHP.');
            } finally {
                createRagServerBtn.disabled = false;
                createRagServerBtn.innerHTML = originalBtnHtml;
            }
        });
    }

    return {
        showRagServerModal,
        hideRagServerModal
    };
}
