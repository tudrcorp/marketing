document.addEventListener('DOMContentLoaded', () => {
    const toggle = document.querySelector('.tdg-home__nav-toggle');
    const button = document.querySelector('.tdg-home__nav-button');
    const nav = document.querySelector('.tdg-home__nav');

    if (! (toggle instanceof HTMLInputElement) || ! (button instanceof HTMLElement) || ! (nav instanceof HTMLElement)) {
        return;
    }

    const desktopQuery = window.matchMedia('(min-width: 64rem)');
    const closeThreshold = 80;
    let dragStartY = 0;
    let dragOffset = 0;
    let dragging = false;

    const resetSheetTransform = () => {
        nav.style.transform = '';
        nav.style.transition = '';
    };

    const syncMenu = () => {
        const open = toggle.checked && ! desktopQuery.matches;
        button.setAttribute('aria-expanded', open ? 'true' : 'false');
        nav.setAttribute('aria-modal', open ? 'true' : 'false');
        document.body.classList.toggle('tdg-home-nav-open', open);

        if (! open) {
            resetSheetTransform();
        }
    };

    const closeMenu = () => {
        toggle.checked = false;
        syncMenu();
    };

    toggle.addEventListener('change', syncMenu);
    nav.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', closeMenu);
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && toggle.checked) {
            closeMenu();
            toggle.focus();
        }
    });
    desktopQuery.addEventListener('change', (event) => {
        if (event.matches) {
            closeMenu();
        }
    });

    nav.addEventListener('touchstart', (event) => {
        if (desktopQuery.matches || ! toggle.checked || event.touches.length !== 1) {
            return;
        }

        dragStartY = event.touches[0].clientY;
        dragOffset = 0;
        dragging = true;
        nav.style.transition = 'none';
    }, { passive: true });

    nav.addEventListener('touchmove', (event) => {
        if (! dragging || event.touches.length !== 1) {
            return;
        }

        dragOffset = Math.max(0, event.touches[0].clientY - dragStartY);
        nav.style.transform = `translate3d(0, ${dragOffset}px, 0)`;
    }, { passive: true });

    nav.addEventListener('touchend', () => {
        if (! dragging) {
            return;
        }

        dragging = false;
        nav.style.transition = '';

        if (dragOffset > closeThreshold) {
            closeMenu();
        } else {
            resetSheetTransform();
        }

        dragOffset = 0;
    });

    syncMenu();
});
