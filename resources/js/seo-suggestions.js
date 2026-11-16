const SEO_LIMITS = {
    title: { minimum: 50, maximum: 60 },
    description: { minimum: 120, maximum: 160 },
};

const getPlainText = (html) => {
    const document = new DOMParser().parseFromString(html, 'text/html');

    document.querySelectorAll('script, style').forEach((element) => element.remove());

    return (document.body.textContent || '').replace(/\s+/g, ' ').trim();
};

const createDescriptionSuggestion = (text) => {
    const normalizedText = text.replace(/\s+/g, ' ').trim();
    const characters = Array.from(normalizedText);
    const maximum = SEO_LIMITS.description.maximum;

    if (characters.length <= maximum) {
        return normalizedText;
    }

    const clippedText = characters.slice(0, maximum - 3).join('');
    const wordBoundary = clippedText.lastIndexOf(' ');
    const description = wordBoundary >= SEO_LIMITS.description.minimum - 3
        ? clippedText.slice(0, wordBoundary)
        : clippedText;

    return `${description.trimEnd()}...`;
};

const initializeCounter = (field, counter, limits) => {
    if (!field || !counter) {
        return;
    }

    const update = () => {
        const characterCount = Array.from(field.value).length;
        const inRange = characterCount >= limits.minimum && characterCount <= limits.maximum;

        counter.textContent = `${characterCount} characters (ideal: ${limits.minimum}–${limits.maximum}).`;
        counter.classList.remove('text-stone-500');
        counter.classList.toggle('text-emerald-700', inRange);
        counter.classList.toggle('text-amber-700', !inRange);
    };

    field.addEventListener('input', update);
    update();
};

const connectSuggestion = (source, target, createSuggestion) => {
    if (!source || !target) {
        return;
    }

    let lastSuggestion = null;

    const update = () => {
        if (target.value.trim() !== '' && target.value !== lastSuggestion) {
            return;
        }

        const suggestion = createSuggestion(source.value);

        if (suggestion === '') {
            if (target.value === lastSuggestion) {
                target.value = '';
                target.dispatchEvent(new Event('input', { bubbles: true }));
            }

            lastSuggestion = null;

            return;
        }

        target.value = suggestion;
        lastSuggestion = suggestion;
        target.dispatchEvent(new Event('input', { bubbles: true }));
    };

    source.addEventListener('input', update);
    update();
};

export const initializeSeoSuggestions = () => {
    const title = document.querySelector('#title');
    const excerpt = document.querySelector('#excerpt');
    const searchTitle = document.querySelector('#seo_title');
    const searchDescription = document.querySelector('#seo_description');
    const content = document.querySelector('#content');

    initializeCounter(title, document.querySelector('[data-seo-counter="post-title"]'), SEO_LIMITS.title);
    initializeCounter(searchTitle, document.querySelector('[data-seo-counter="title"]'), SEO_LIMITS.title);
    initializeCounter(searchDescription, document.querySelector('[data-seo-counter="description"]'), SEO_LIMITS.description);
    initializeCounter(excerpt, document.querySelector('[data-seo-counter="excerpt"]'), SEO_LIMITS.description);

    connectSuggestion(title, searchTitle, (value) => value.trim());
    connectSuggestion(excerpt, searchDescription, (value) => createDescriptionSuggestion(value));

    if (content && excerpt) {
        connectSuggestion(content, excerpt, (value) => createDescriptionSuggestion(getPlainText(value)));
    }
};
