const createElement = (tag, className, text) => {
    const element = document.createElement(tag);
    element.className = className;
    element.textContent = text;

    return element;
};

export const initializeMediaPickers = () => {
    document.querySelectorAll('form').forEach((form) => {
        const dialog = form.querySelector('[data-media-dialog]');
        const items = form.querySelector('[data-media-items]');
        const status = form.querySelector('[data-media-status]');
        const searchInput = form.querySelector('[data-media-search]');
        const nextButton = form.querySelector('[data-media-next]');

        if (!dialog || !items || !status || !searchInput || !nextButton) {
            return;
        }

        const libraryUrl = dialog.closest('[data-media-picker]')?.dataset.libraryUrl
            ?? form.querySelector('[data-library-url]')?.dataset.libraryUrl;
        const mediaById = new Map();
        let nextPageUrl = null;
        let onSelect = null;

        const renderMedia = (media) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.dataset.mediaItem = String(media.id);
            button.className = 'flex min-w-0 flex-col gap-2 rounded-xl border border-stone-200 p-2 text-left transition hover:border-stone-500 hover:bg-stone-50 focus:outline-none focus:ring-2 focus:ring-stone-500';

            const image = document.createElement('img');
            image.src = media.thumbnail_url;
            image.alt = media.alt_text || media.name;
            image.loading = 'lazy';
            image.className = 'aspect-square w-full rounded-lg bg-stone-100 object-cover';
            button.append(image, createElement('span', 'truncate text-xs font-medium text-stone-800', media.name));
            items.append(button);
            mediaById.set(String(media.id), media);
        };

        const loadMedia = async (url, append = false) => {
            status.textContent = 'Loading images...';
            nextButton.hidden = true;

            const requestUrl = new URL(url, window.location.origin);
            if (!append) {
                requestUrl.searchParams.set('search', searchInput.value.trim());
            }

            const response = await fetch(requestUrl, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                throw new Error('The media library could not be loaded.');
            }

            const result = await response.json();
            if (!append) {
                items.replaceChildren();
                mediaById.clear();
            }

            result.data.forEach(renderMedia);
            nextPageUrl = result.next_page_url;
            nextButton.hidden = !nextPageUrl;
            status.textContent = result.data.length === 0 && !append
                ? 'No images found.'
                : 'Select an image to use it.';
        };

        const openDialog = () => {
            if (!libraryUrl) {
                status.textContent = 'The media library URL is missing.';
                dialog.showModal();

                return;
            }

            dialog.showModal();
            loadMedia(libraryUrl).catch((error) => {
                status.textContent = error.message;
            });
        };

        form.addEventListener('media-gallery:open', (event) => {
            onSelect = typeof event.detail?.onSelect === 'function' ? event.detail.onSelect : null;
            openDialog();
        });

        form.querySelectorAll('[data-media-open]').forEach((button) => {
            button.addEventListener('click', () => {
                form.dispatchEvent(new CustomEvent('media-gallery:open'));
            });
        });

        form.querySelectorAll('[data-media-close]').forEach((button) => {
            button.addEventListener('click', () => dialog.close());
        });

        dialog.addEventListener('click', (event) => {
            if (event.target === dialog) {
                dialog.close();
            }

            const button = event.target.closest('[data-media-item]');
            if (!button) {
                return;
            }

            const media = mediaById.get(button.dataset.mediaItem);
            if (!media) {
                return;
            }

            if (onSelect) {
                onSelect(media);
            } else {
                const selectedInput = form.querySelector('[data-featured-media-id]');
                const preview = form.querySelector('[data-featured-media-preview]');

                if (selectedInput && preview) {
                    selectedInput.value = String(media.id);
                    const image = document.createElement('img');
                    image.src = media.thumbnail_url;
                    image.alt = media.alt_text || media.name;
                    image.className = 'mt-2 aspect-[16/10] w-full max-w-sm rounded-xl object-cover';
                    preview.replaceChildren(
                        image,
                        createElement('p', 'text-sm text-stone-600', `Using ${media.name} from the library.`),
                    );
                    const removeCheckbox = form.querySelector('[name="remove_featured_image"]');
                    if (removeCheckbox) {
                        removeCheckbox.checked = false;
                    }
                }
            }

            dialog.close();
        });

        form.querySelector('[name="remove_featured_image"]')?.addEventListener('change', (event) => {
            if (event.target.checked) {
                const selectedInput = form.querySelector('[data-featured-media-id]');
                if (selectedInput) {
                    selectedInput.value = '';
                }
            }
        });

        form.querySelector('[name="featured_image"]')?.addEventListener('change', () => {
            const removeCheckbox = form.querySelector('[name="remove_featured_image"]');
            if (removeCheckbox) {
                removeCheckbox.checked = false;
            }
        });

        form.querySelector('[data-media-search-submit]')?.addEventListener('click', () => {
            loadMedia(libraryUrl).catch((error) => {
                status.textContent = error.message;
            });
        });

        searchInput.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                loadMedia(libraryUrl).catch((error) => {
                    status.textContent = error.message;
                });
            }
        });

        nextButton.addEventListener('click', () => {
            if (nextPageUrl) {
                loadMedia(nextPageUrl, true).catch((error) => {
                    status.textContent = error.message;
                });
            }
        });
    });
};
