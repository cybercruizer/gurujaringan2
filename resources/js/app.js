

import Alpine from 'alpinejs';
import { initializeMediaPickers } from './media-picker.js';
import registerSiteSearch from './site-search.js';

window.Alpine = Alpine;

registerSiteSearch(Alpine);
Alpine.start();
initializeMediaPickers();

document.querySelectorAll('[data-auto-close-navigation]').forEach((navigation) => {
    navigation.querySelectorAll('details').forEach((menu) => {
        menu.addEventListener('toggle', () => {
            if (!menu.open) {
                return;
            }

            navigation.querySelectorAll('details[open]').forEach((otherMenu) => {
                if (otherMenu !== menu && !otherMenu.contains(menu) && !menu.contains(otherMenu)) {
                    otherMenu.open = false;
                }
            });
        });
    });
});

document.addEventListener('click', (event) => {
    document.querySelectorAll('[data-auto-close-navigation]').forEach((navigation) => {
        const target = event.target;

        navigation.querySelectorAll('details[open]').forEach((menu) => {
            if (!menu.contains(target)) {
                menu.open = false;
            }
        });
    });
});

const loadPostEditor = async () => {
    const editors = document.querySelectorAll('[data-rich-text-editor]');

    if (editors.length === 0) {
        return;
    }

    const { initializePostEditor } = await import('./post-editor.js');

    initializePostEditor(editors);
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        loadPostEditor().catch((error) => console.error('Unable to initialize the post editor.', error));
    }, { once: true });
} else {
    loadPostEditor().catch((error) => console.error('Unable to initialize the post editor.', error));
}
