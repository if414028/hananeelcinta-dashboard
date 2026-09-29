import './chart-tooltips';
import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.data('richText', () => ({
    selection: null,
    active: {},
    block: 'p',
    characters: 0,
    words: 0,
    linkOpen: false,
    linkUrl: '',
    linkError: false,
    init() {
        this.$refs.editor.innerHTML = this.$refs.input.value;
        this.sync();
    },
    sync() {
        this.$refs.input.value = this.$refs.editor.innerHTML;
        const text = this.$refs.editor.innerText.trim();
        this.characters = Array.from(text).length;
        this.words = text ? text.split(/\s+/u).length : 0;
        this.saveSelection();
    },
    saveSelection() {
        const selected = window.getSelection();
        if (selected.rangeCount && this.$refs.editor.contains(selected.anchorNode)) {
            this.selection = selected.getRangeAt(0).cloneRange();
            for (const name of ['bold', 'italic', 'underline', 'strikeThrough', 'justifyLeft', 'justifyCenter', 'justifyRight', 'justifyFull', 'insertUnorderedList', 'insertOrderedList']) {
                this.active[name] = document.queryCommandState(name);
            }
            this.block = String(document.queryCommandValue('formatBlock') || 'p').toLowerCase().replace(/[<>]/g, '');
        }
    },
    command(command, value = null) {
        this.$refs.editor.focus();
        if (this.selection) {
            const selected = window.getSelection();
            selected.removeAllRanges();
            selected.addRange(this.selection);
        }
        document.execCommand(command, false, value);
        this.sync();
    },
    paste(event) {
        this.command('insertText', event.clipboardData.getData('text/plain'));
    },
    openLink() {
        this.saveSelection();
        this.linkUrl = '';
        this.linkError = false;
        this.linkOpen = true;
        this.$nextTick(() => this.$refs.linkInput.focus());
    },
    insertLink() {
        let url;
        try {
            url = new URL(this.linkUrl.trim());
            if (!['https:', 'http:'].includes(url.protocol)) throw new Error();
        } catch (_) {
            this.linkError = true;
            return;
        }
        this.command('createLink', url.href);
        this.linkOpen = false;
    },
}));

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
    copying: false,
    error: false,
    async copy() {
        this.copying = true;
        this.copied = false;
        this.error = false;
        try {
            if (!navigator.clipboard?.writeText) throw new Error('Clipboard API unavailable');
            await navigator.clipboard.writeText(url);
            this.copied = true;
            window.setTimeout(() => { this.copied = false; }, 3000);
        } catch (_) {
            this.error = true;
        } finally {
            this.copying = false;
        }
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
