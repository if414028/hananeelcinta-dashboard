import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.data('parallaxBackground', () => ({
    offset: 0,
    scrollHandler: null,

    init() {
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

        this.scrollHandler = () => {
            const bounds = this.$el.getBoundingClientRect();
            const progress = (window.innerHeight - bounds.top) / (window.innerHeight + bounds.height) - 0.5;
            this.offset = Math.max(-1, Math.min(1, progress * 2)) * 48;
        };

        window.addEventListener('scroll', this.scrollHandler, { passive: true });
        this.scrollHandler();
    },

    destroy() {
        if (this.scrollHandler) window.removeEventListener('scroll', this.scrollHandler);
    },
}));

Alpine.start();
