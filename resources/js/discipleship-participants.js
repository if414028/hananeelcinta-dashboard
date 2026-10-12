import Alpine from 'alpinejs';

Alpine.data('discipleshipParticipants', (endpoint, initial) => ({
    selected: initial,
    members: [],
    search: '',
    nextPage: null,
    loading: false,
    error: '',
    controller: null,
    requestId: 0,

    init() {
        this.load();
    },

    isSelected(id) {
        return this.selected.some((member) => member.id === id);
    },

    initials(name) {
        return name.trim().split(/\s+/).slice(0, 2)
            .map((part) => Array.from(part)[0] || '').join('').toLocaleUpperCase('id-ID') || '?';
    },

    toggle(member) {
        if (this.isSelected(member.id)) {
            this.selected = this.selected.filter((item) => item.id !== member.id);
        } else {
            this.selected.push(member);
        }
    },

    async load(page = 1) {
        this.controller?.abort();
        this.controller = new AbortController();
        const requestId = ++this.requestId;
        this.loading = true;
        this.error = '';
        if (page === 1) {
            this.members = [];
            this.nextPage = null;
        }
        try {
            const url = new URL(endpoint, window.location.origin);
            url.searchParams.set('search', this.search);
            url.searchParams.set('page', page);
            const response = await fetch(url, {
                credentials: 'same-origin',
                headers: { Accept: 'application/json' },
                signal: this.controller.signal,
            });
            if (!response.ok) throw new Error('Daftar jemaat tidak dapat dimuat. Coba lagi.');
            const body = await response.json();
            if (requestId !== this.requestId) return;
            this.members = page === 1 ? body.data : [...this.members, ...body.data];
            this.nextPage = body.next_page;
        } catch (error) {
            if (error.name !== 'AbortError' && requestId === this.requestId) {
                this.error = 'Daftar jemaat tidak dapat dimuat. Periksa koneksi lalu coba lagi.';
            }
        } finally {
            if (requestId === this.requestId) this.loading = false;
        }
    },

    destroy() {
        this.controller?.abort();
    },
}));
