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

Alpine.data('eventFieldBuilder', (initialFields = []) => ({
    fields: initialFields.map((field, index) => ({
        ...field,
        options: Array.isArray(field.options) ? field.options : [],
        optionsText: Array.isArray(field.options) ? field.options.join(', ') : '',
        required: field.key === 'name' ? true : Boolean(field.required),
        autoKey: field.key !== 'name',
        uid: `${Date.now()}-${index}`,
    })),

    addField() {
        this.fields.push({ key: '', label: '', type: 'text', required: false, options: [], optionsText: '', autoKey: true, uid: `${Date.now()}-${Math.random()}` });
    },

    removeField(index) {
        if (this.fields[index].key !== 'name') this.fields.splice(index, 1);
    },

    setKey(field) {
        field.key = field.label.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '').slice(0, 50);
    },

    parsedOptions(field) {
        return field.optionsText.split(',').map(value => value.trim()).filter(Boolean);
    },
}));

Alpine.data('copyLink', (url) => ({
    copied: false,
    async copy() {
        await navigator.clipboard.writeText(url);
        this.copied = true;
        window.setTimeout(() => { this.copied = false; }, 2000);
    },
}));

Alpine.data('eventScanner', (baseUrl) => ({
    active: false,
    stream: null,
    detector: null,
    frame: null,
    message: '',
    manualCode: '',

    async start() {
        this.message = '';
        if (!window.isSecureContext || !navigator.mediaDevices?.getUserMedia) {
            this.message = 'Kamera hanya tersedia melalui HTTPS atau localhost. Buka halaman ini dari koneksi aman.';
            return;
        }
        if (!('BarcodeDetector' in window)) {
            this.message = 'Browser ini belum mendukung scan QR lewat kamera. Gunakan Chrome terbaru atau masukkan kode tiket secara manual.';
            return;
        }
        try {
            this.detector = new BarcodeDetector({ formats: ['qr_code'] });
            this.stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: { ideal: 'environment' } }, audio: false });
            this.$refs.video.srcObject = this.stream;
            await this.$refs.video.play();
            this.active = true;
            this.scan();
        } catch (error) {
            this.message = error?.name === 'NotAllowedError'
                ? 'Izin kamera ditolak. Izinkan kamera dari ikon di address bar, lalu coba lagi.'
                : error?.name === 'NotFoundError'
                    ? 'Kamera tidak ditemukan pada perangkat ini.'
                    : 'Kamera tidak dapat diakses. Tutup aplikasi lain yang memakai kamera, lalu coba lagi.';
            this.stop();
        }
    },

    async scan() {
        if (!this.active) return;
        try {
            const codes = await this.detector.detect(this.$refs.video);
            if (codes.length) {
                this.stop();
                const value = codes[0].rawValue;
                window.location.href = value.startsWith('http') ? value : `${baseUrl}/${encodeURIComponent(value)}/verify`;
                return;
            }
        } catch (_) {}
        this.frame = requestAnimationFrame(() => this.scan());
    },

    openCode() {
        if (!this.manualCode) return;
        window.location.href = `${baseUrl}/${encodeURIComponent(this.manualCode)}/verify`;
    },

    stop() {
        this.active = false;
        if (this.frame) cancelAnimationFrame(this.frame);
        if (this.stream) this.stream.getTracks().forEach(track => track.stop());
        this.stream = null;
    },

    destroy() { this.stop(); },
}));

Alpine.start();
