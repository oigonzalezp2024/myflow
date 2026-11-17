# Guía de Integración: Unificación de Growth-Engine-Manager en MyFlow

Lineamiento técnico para la integración controlada, modular y con trazabilidad de [growth-engine-manager](https://github.com/oigonzalezp2024/growth-engine-manager) dentro de la arquitectura base de **myflow** en una rama de larga duración.

## 1. Objetivo del Proceso

Consolidar la lógica comercial, de pauta y de evaluación de Matriz de Marco Lógico (MML) proveniente de [growth-engine-manager](https://github.com/oigonzalezp2024/growth-engine-manager) como un módulo encapsulado dentro de **myflow**, desacoplando la presentación frontend de las APIs puras en PHP 8+ y asegurando que ningún recurso quede desaprovechado. Se mantienen intactos su licencia (`modules/growth-engine-manager/LICENSE`) y documentación original (`modules/growth-engine-manager/README.md`).

## 2. Protocolo Paso a Paso en Git

### Paso 2.1: Creación de la rama de integración aislada en MyFlow

Desde la raíz del repositorio maestro **myflow**, actualiza tu rama base de desarrollo y crea la rama dedicada exclusivamente a esta integración:

```bash
git checkout develop
git pull origin develop
git checkout -b feature/integrate-growth-engine-manager

```

### Paso 2.2: Registro inicial del documento guía (Primer Commit)

Crea el archivo `INTEGRATION.md` en la raíz de **myflow** con el contenido de esta guía y regístralo como el primer commit de la rama:

```bash
git add INTEGRATION.md
git commit -m "docs(integration): agrega guía oficial para unificación de growth-engine-manager en myflow"

```

### Paso 2.3: Incorporación mediante Git Subtree

Estando posicionado dentro de `feature/integrate-growth-engine-manager`, extrae el contenido desde la rama `main` del repositorio remoto [growth-engine-manager](https://github.com/oigonzalezp2024/growth-engine-manager) e insértalo en `modules/growth-engine-manager`:

```bash
git subtree add --prefix=modules/growth-engine-manager https://github.com/oigonzalezp2024/growth-engine-manager.git main --squash

```

### Paso 2.4: Verificación de inventario completo y limpieza inicial

Confirma que todos los recursos originales se hayan posicionado correctamente en `modules/growth-engine-manager/`:

* **Datos y configuración:** `data/`, `config.php`, `.env.example`, `.htaccess`
* **Backend y servicios IA/Pauta:** `GeminiService.php`, `ai_evaluator.php`, `meta_ads.php`, `evaluador_mml/`, `api-test.php`
* **Frontend y vistas:** `index.html`, `evaluador.html`, `app.js`, `app_ai_addon.js`, `eval_app.js`, `ai_evaluator.js`, `styles.css`
* **Legales y documentación:** `LICENSE`, `README.md`

**Acciones inmediatas de auditoría tras la descarga:**

1. Revisar `modules/growth-engine-manager/.htaccess` para evitar colisiones de enrutamiento con el `.htaccess` raíz de **myflow**.
2. Aislar o eliminar `api-test.php` una vez validadas las conexiones para no exponer endpoints de prueba.

### Paso 2.5: Consolidación de variables de entorno

Modifica `modules/growth-engine-manager/config.php` para que consuma directamente el archivo maestro `.env` de la raíz de **myflow** (`GEMINI_API_KEY`, `GEMINI_API_MODEL`, credenciales de Meta), eliminando la necesidad de archivos `.env` duplicados:

```php
$rootEnvPath = dirname(__DIR__, 2);
if (file_exists($rootEnvPath . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable($rootEnvPath);
    $dotenv->safeLoad();
}

```

### Paso 2.6: Respaldo inicial en el repositorio remoto

Sube la nueva rama de integración y su estructura base al repositorio remoto de **myflow**:

```bash
git push -u origin feature/integrate-growth-engine-manager

```

## 3. Fase de Acoplamiento Arquitectónico

Antes del cierre, refactoriza los componentes importados para alinearlos con los estándares de **myflow**:

* **Aislamiento de Backend:** Transformar `ai_evaluator.php`, `meta_ads.php` y los scripts de `evaluador_mml/` en endpoints JSON puros bajo Clean/Hexagonal Architecture, manteniendo las claves API estrictamente del lado del servidor.
* **Desacoplamiento de Vistas:** Integrar las interfaces (`index.html`, `evaluador.html`) y controladores ES (`app.js`, `eval_app.js`, `ai_evaluator.js`, `app_ai_addon.js`) como vistas independientes que consuman las APIs de PHP vía `fetch`, encapsulando estilos en `styles.css` con especificidad por ID para evitar fugas hacia el diseño principal de **myflow**.
* **Esquema MML:** Preservar intacta la estructura JSON raíz `matriz_marco_logico` (`titulo`, `periodo_ejecucion`, `niveles`) y los flujos de aprobación manual de prompts y salidas de IA.

## 4. Criterios de Cierre y Fusión Final

La rama `feature/integrate-growth-engine-manager` solo se fusionará hacia `develop` cuando:

1. Todos los endpoints y servicios operen bajo la arquitectura limpia de **myflow**.
2. Se hayan validado las pruebas funcionales de evaluación MML y gestión de pauta.
3. No quede ningún recurso o script aislado sin conectar al ecosistema principal.
4. Se elimine este documento guía (`git rm INTEGRATION.md` con su commit correspondiente) para mantener limpio el árbol de la rama destino preservando la trazabilidad en el historial de Git.
