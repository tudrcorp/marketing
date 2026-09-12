import Sortable from 'sortablejs';

/**
 * Drag & drop del tablero Kanban de contenido multimarca.
 *
 * No se apoya en `alpine:init` ni en directivas de Alpine a propósito: en el
 * panel SPA, Livewire inyecta este script al navegar (mergeNewHead), es decir
 * mucho después de que Alpine haya arrancado, así que un listener de ese evento
 * jamás se dispararía. En su lugar se inicializa de forma idempotente en cada
 * momento en que pueden aparecer columnas nuevas: carga directa, navegación SPA
 * y cualquier re-render del componente Livewire.
 */

const COLUMN_SELECTOR = '[data-kanban-column="true"]';

const booted = new WeakSet();

let hooksRegistered = false;

/**
 * Identificadores de las tarjetas de una columna, en el orden actual del DOM.
 */
function orderedIds(column) {
    return Array.from(column.querySelectorAll('[data-post-id]')).map((card) => card.dataset.postId);
}

/**
 * Componente Livewire que contiene la columna.
 */
function componentOf(element) {
    const root = element.closest('[wire\\:id]');

    if (! root || ! window.Livewire) {
        return null;
    }

    return window.Livewire.find(root.getAttribute('wire:id'));
}

function bootColumn(column) {
    if (booted.has(column)) {
        return;
    }

    booted.add(column);

    Sortable.create(column, {
        group: 'content-kanban',
        animation: 160,
        easing: 'cubic-bezier(0.22, 1, 0.36, 1)',
        ghostClass: 'content-card--ghost',
        chosenClass: 'content-card--chosen',
        dragClass: 'content-card--drag',
        // La tarjeta entera arrastra; los controles siguen siendo clicables.
        filter: 'button, a, input, select, textarea, [data-no-drag]',
        preventOnFilter: false,
        fallbackOnBody: true,
        swapThreshold: 0.65,
        // El retardo solo aplica al táctil, para no bloquear el scroll del móvil.
        delay: 120,
        delayOnTouchOnly: true,
        touchStartThreshold: 6,
        onEnd(event) {
            const component = componentOf(event.to) ?? componentOf(event.from);
            const postId = event.item.dataset.postId;
            const target = event.to.dataset.status;

            if (! component || ! postId || ! target) {
                return;
            }

            if (target === event.from.dataset.status) {
                component.call('reorderColumn', target, orderedIds(event.to));

                return;
            }

            component.call('movePost', postId, target, orderedIds(event.to));
        },
    });
}

function boot(root = document) {
    if (typeof root.querySelectorAll !== 'function') {
        return;
    }

    if (typeof root.matches === 'function' && root.matches(COLUMN_SELECTOR)) {
        bootColumn(root);
    }

    root.querySelectorAll(COLUMN_SELECTOR).forEach(bootColumn);
}

/**
 * Tras cada petición de Livewire el morph puede reemplazar columnas enteras.
 */
function registerLivewireHooks() {
    if (hooksRegistered || ! window.Livewire) {
        return;
    }

    hooksRegistered = true;

    window.Livewire.hook('commit', ({ respond }) => {
        respond(() => queueMicrotask(() => boot()));
    });

    window.Livewire.hook('morph.added', ({ el }) => boot(el));
}

boot();
registerLivewireHooks();

document.addEventListener('DOMContentLoaded', () => boot());

document.addEventListener('livewire:init', registerLivewireHooks);

document.addEventListener('livewire:navigated', () => {
    registerLivewireHooks();
    boot();
});
