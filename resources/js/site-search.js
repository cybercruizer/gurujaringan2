export default function registerSiteSearch(Alpine) {
    Alpine.data('siteSearch', (searchUrl) => ({
        searchUrl,
        isOpen: false,
        query: '',
        results: [],
        loading: false,
        searched: false,
        error: '',
        debounceTimer: null,
        activeRequest: null,

        open() {
            this.isOpen = true;
            this.$nextTick(() => this.$refs.searchInput.focus());
        },

        close() {
            this.isOpen = false;
            window.clearTimeout(this.debounceTimer);
            this.activeRequest?.abort();
            this.query = '';
            this.results = [];
            this.loading = false;
            this.searched = false;
            this.error = '';
        },

        scheduleSearch() {
            window.clearTimeout(this.debounceTimer);
            this.activeRequest?.abort();
            this.activeRequest = null;
            this.results = [];
            this.loading = false;
            this.searched = false;
            this.error = '';

            if (this.query.trim().length < 3) {
                return;
            }

            this.debounceTimer = window.setTimeout(() => this.search(), 250);
        },

        async search() {
            this.activeRequest?.abort();

            const controller = new AbortController();
            this.activeRequest = controller;
            this.loading = true;
            this.error = '';

            try {
                const url = new URL(this.searchUrl, window.location.origin);
                url.searchParams.set('q', this.query.trim());

                const response = await fetch(url, {
                    headers: { Accept: 'application/json' },
                    signal: controller.signal,
                });

                if (!response.ok) {
                    throw new Error(`Search request failed with status ${response.status} at ${response.url}.`);
                }

                const data = await response.json();

                if (this.activeRequest === controller) {
                    this.results = data.results;
                    this.searched = true;
                }
            } catch (error) {
                if (error.name !== 'AbortError' && this.activeRequest === controller) {
                    console.error('Site search failed.', error);
                    this.error = 'Pencarian tidak dapat dilakukan. Silakan coba lagi.';
                    this.results = [];
                    this.searched = false;
                }
            } finally {
                if (this.activeRequest === controller) {
                    this.loading = false;
                    this.activeRequest = null;
                }
            }
        },
    }));
}
