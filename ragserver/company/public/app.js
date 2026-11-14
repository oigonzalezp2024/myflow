document.addEventListener('DOMContentLoaded', () => {
    const mensajeInput = document.getElementById('mensajeInput');
    const btnVoz = document.getElementById('btnVoz');
    const btnEnviar = document.getElementById('btnEnviar');
    const btnToggleVoz = document.getElementById('btnToggleVoz');
    const voiceSelectorContainer = document.getElementById('voiceSelectorContainer');
    const vozSelect = document.getElementById('vozSelect');
    const estado = document.getElementById('estado');
    const resultadoContainer = document.getElementById('resultadoContainer');
    const resultadoContenido = document.getElementById('resultadoContenido');

    let vocesDisponibles = [];

    // Mostrar u ocultar el selector de voz con el botón de configuración (⚙️)
    btnToggleVoz.addEventListener('click', () => {
        voiceSelectorContainer.classList.toggle('hidden');
    });

    // Cargar voces del navegador priorizando Google Español / Google Español Estados Unidos
    function cargarVoces() {
        if (!window.speechSynthesis) return;
        
        vocesDisponibles = window.speechSynthesis.getVoices();
        
        vozSelect.innerHTML = '';
        
        if (vocesDisponibles.length === 0) {
            const option = document.createElement('option');
            option.value = '';
            option.textContent = 'No hay voces disponibles';
            vozSelect.appendChild(option);
            return;
        }

        // Ordenar o rellenar asegurando que aparezcan todas, buscando Google Español o es-US / es-CO primero
        vocesDisponibles.forEach((voz, index) => {
            const option = document.createElement('option');
            option.value = index;
            option.textContent = `${voz.name} (${voz.lang})`;
            
            // Auto seleccionar Google Español, Google Español Estados Unidos o cualquier variante en español preferida
            if (
                voz.name.toLowerCase().includes('google español') || 
                voz.name.toLowerCase().includes('google spanish') ||
                voz.lang === 'es-US' || 
                voz.lang === 'es-ES' || 
                voz.lang === 'es-CO'
            ) {
                option.selected = true;
            }
            
            vozSelect.appendChild(option);
        });
    }

    cargarVoces();
    if (window.speechSynthesis && window.speechSynthesis.onvoiceschanged !== undefined) {
        window.speechSynthesis.onvoiceschanged = cargarVoces;
    }

    // Ocultar automáticamente el selector de voz una vez que el usuario elija una voz
    vozSelect.addEventListener('change', () => {
        voiceSelectorContainer.classList.add('hidden');
    });

    // Reconocimiento de voz (Speech Recognition)
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;

    if (!SpeechRecognition) {
        estado.textContent = 'Tu navegador no soporta reconocimiento de voz.';
        btnVoz.disabled = true;
        return;
    }

    const recognition = new SpeechRecognition();
    recognition.lang = 'es-CO';
    recognition.interimResults = true;
    recognition.continuous = true;

    let grabando = false;

    btnVoz.addEventListener('click', () => {
        if (grabando) {
            recognition.stop();
            return;
        }
        recognition.start();
    });

    recognition.onstart = () => {
        grabando = true;
        btnVoz.classList.add('grabando');
        estado.textContent = 'Escuchando...';
    };

    recognition.onresult = (event) => {
        let transcripcion = '';
        for (let i = event.resultIndex; i < event.results.length; i++) {
            transcripcion += event.results[i][0].transcript;
        }
        mensajeInput.value = transcripcion;
    };

    recognition.onerror = (event) => {
        estado.textContent = `Error de micrófono: ${event.error}`;
    };

    recognition.onend = () => {
        grabando = false;
        btnVoz.classList.remove('grabando');
        estado.textContent = 'Dictado finalizado.';
    };

    // Función para reproducir el texto usando la voz elegida
    function hablarTexto(texto) {
        if (!window.speechSynthesis) return;

        window.speechSynthesis.cancel();

        const utterance = new SpeechSynthesisUtterance(texto);
        const selectedIndex = vozSelect.value;

        if (vocesDisponibles[selectedIndex]) {
            utterance.voice = vocesDisponibles[selectedIndex];
        }

        utterance.rate = 1.0; 
        window.speechSynthesis.speak(utterance);
    }

    // Envío en formato application/json hacia el script PHP
    btnEnviar.addEventListener('click', async () => {
        const texto = mensajeInput.value.trim();

        if (!texto) {
            estado.textContent = 'El campo está vacío.';
            return;
        }

        const payload = {
            mensaje: texto,
            timestamp: new Date().toISOString()
        };

        try {
            estado.textContent = 'Enviando al servidor...';
            resultadoContainer.classList.add('hidden');
            
            const response = await fetch('api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            estado.textContent = '¡Respuesta recibida y reproducida en voz alta!';

            let respuestaTexto = '';
            if (data.respuesta_rag) {
                respuestaTexto = typeof data.respuesta_rag === 'object' 
                    ? JSON.stringify(data.respuesta_rag, null, 2) 
                    : data.respuesta_rag;
            } else {
                respuestaTexto = JSON.stringify(data, null, 2);
            }

            resultadoContenido.textContent = respuestaTexto;
            resultadoContainer.classList.remove('hidden');

            const textoParaVoz = typeof data.respuesta_rag === 'string' 
                ? data.respuesta_rag 
                : 'Respuesta recibida del servidor.';
                
            hablarTexto(textoParaVoz);

            mensajeInput.value = '';
        } catch (error) {
            estado.textContent = `Error de comunicación: ${error.message}`;
        }
    });
});
