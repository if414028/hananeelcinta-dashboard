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

Alpine.data('prayerBoard', (csrfToken) => ({
    dragged: null,
    dragTarget: null,
    ignoreCardClick: false,
    boardOverflows: false,
    canScrollLeft: false,
    canScrollRight: false,
    showScrollDock: false,
    scrollDockLeft: 0,
    scrollDockWidth: 0,
    scrollMax: 0,
    scrollPosition: 0,
    scrollThumbWidth: 0,
    scrollThumbLeft: 0,
    dockDragging: false,
    dockGrabOffset: 0,
    boardResizeObserver: null,
    pending: false,
    error: '',
    pointerActive: false,
    pointerDragging: false,
    pointerStartX: 0,
    pointerStartY: 0,
    pointerX: 0,
    pointerY: 0,
    dragSource: null,
    dragGhost: null,
    grabOffsetX: 0,
    grabOffsetY: 0,
    detail: null,
    detailLoading: false,
    detailError: '',
    completionCard: null,
    saving: false,
    saveError: '',
    saveSuccess: '',

    init() {
        this.$nextTick(() => {
            this.updateBoardScroll();
            if (typeof ResizeObserver !== 'undefined') {
                this.boardResizeObserver = new ResizeObserver(() => this.updateBoardScroll());
                this.boardResizeObserver.observe(this.$refs.columns);
            }
            const prayerId = this.$el.dataset.openPrayer;
            if (/^\d+$/.test(prayerId || '')) {
                this.openDetail(`${this.$el.dataset.detailBase}/${prayerId}`);
            }
        });
    },

    updateBoardScroll() {
        const columns = this.$refs.columns;
        if (!columns) return;
        const bounds = columns.getBoundingClientRect();
        const wasVisible = this.showScrollDock;
        this.scrollMax = Math.max(0, columns.scrollWidth - columns.clientWidth);
        this.scrollPosition = Math.round(columns.scrollLeft);
        this.boardOverflows = this.scrollMax > 2;
        this.canScrollLeft = this.boardOverflows && columns.scrollLeft > 2;
        this.canScrollRight = this.boardOverflows && columns.scrollLeft + columns.clientWidth < columns.scrollWidth - 2;
        this.showScrollDock = this.boardOverflows && bounds.top < window.innerHeight - 56 && bounds.bottom > 64;
        this.scrollDockLeft = Math.max(8, bounds.left);
        this.scrollDockWidth = Math.max(0, Math.min(bounds.width, window.innerWidth - this.scrollDockLeft - 8));
        if (this.showScrollDock && !wasVisible) this.$nextTick(() => this.updateScrollThumb());
        else this.updateScrollThumb();
    },

    updateScrollThumb() {
        const track = this.$refs.dockTrack;
        const columns = this.$refs.columns;
        if (!this.showScrollDock || !track || !columns || !track.clientWidth) return;
        this.scrollThumbWidth = Math.min(track.clientWidth, Math.max(44, track.clientWidth * columns.clientWidth / columns.scrollWidth));
        this.scrollThumbLeft = this.scrollMax > 0
            ? (columns.scrollLeft / this.scrollMax) * (track.clientWidth - this.scrollThumbWidth)
            : 0;
    },

    scrollDockTo(thumbLeft) {
        const track = this.$refs.dockTrack;
        const columns = this.$refs.columns;
        if (!track || !columns) return;
        const travel = track.clientWidth - this.scrollThumbWidth;
        columns.scrollLeft = travel > 0 ? Math.max(0, Math.min(thumbLeft, travel)) / travel * this.scrollMax : 0;
        this.updateBoardScroll();
    },

    dockPointerStart(event) {
        if (event.pointerType === 'mouse' && event.button !== 0) return;
        const track = event.currentTarget;
        const thumb = track.querySelector('[data-scroll-thumb]');
        this.dockDragging = true;
        this.dockGrabOffset = event.target === thumb
            ? event.clientX - thumb.getBoundingClientRect().left
            : this.scrollThumbWidth / 2;
        this.$refs.columns.style.scrollSnapType = 'none';
        track.setPointerCapture(event.pointerId);
        this.scrollDockTo(event.clientX - track.getBoundingClientRect().left - this.dockGrabOffset);
        event.preventDefault();
    },

    dockPointerMove(event) {
        if (!this.dockDragging) return;
        this.scrollDockTo(event.clientX - event.currentTarget.getBoundingClientRect().left - this.dockGrabOffset);
        event.preventDefault();
    },

    dockPointerEnd() {
        if (!this.dockDragging) return;
        this.dockDragging = false;
        this.$refs.columns.style.removeProperty('scroll-snap-type');
        this.updateBoardScroll();
    },

    dockKeydown(event) {
        const columns = this.$refs.columns;
        if (!columns) return;
        if (event.key === 'Home' || event.key === 'End') {
            columns.scrollTo({ left: event.key === 'Home' ? 0 : this.scrollMax, behavior: 'smooth' });
        } else if (event.key === 'ArrowLeft' || event.key === 'ArrowRight') {
            columns.scrollBy({ left: (event.key === 'ArrowLeft' ? -1 : 1) * Math.max(80, columns.clientWidth * .75), behavior: 'smooth' });
        } else return;
        event.preventDefault();
    },

    scrollColumns(direction) {
        const columns = this.$refs.columns;
        const firstColumn = columns?.querySelector('[data-board-status]');
        if (!columns || !firstColumn) return;
        const gap = parseFloat(window.getComputedStyle(columns).columnGap) || 16;
        columns.scrollBy({ left: direction * (firstColumn.getBoundingClientRect().width + gap), behavior: 'smooth' });
    },

    cardFrom(element) {
        return {
            url: element.dataset.url,
            detailUrl: element.dataset.detailUrl,
            current: element.dataset.current,
            next: element.dataset.next,
            reference: element.dataset.reference,
        };
    },

    openCardDetail(event, url) {
        if (this.ignoreCardClick || this.pending || event.target.closest('[role="button"]') !== event.currentTarget) return;
        this.openDetail(url);
    },

    pointerStart(event) {
        if (this.pointerActive || this.pending || (event.pointerType === 'mouse' && event.button !== 0)) return;
        if (event.pointerType !== 'mouse' && !event.currentTarget.hasAttribute('data-drag-handle')) return;
        const card = event.currentTarget.closest('article[data-url]');
        if (!card) return;
        this.dragged = this.cardFrom(card);
        this.pointerActive = true;
        this.pointerDragging = false;
        this.pointerStartX = event.clientX;
        this.pointerStartY = event.clientY;
        this.pointerX = event.clientX;
        this.pointerY = event.clientY;
        this.dragSource = card;
        const bounds = card.getBoundingClientRect();
        this.grabOffsetX = event.clientX - bounds.left;
        this.grabOffsetY = event.clientY - bounds.top;
        this.error = '';
        event.currentTarget.setPointerCapture?.(event.pointerId);
        if (event.pointerType !== 'mouse') event.preventDefault();
    },

    pointerMove(event) {
        if (!this.pointerActive || !this.dragged) return;
        if (!this.pointerDragging && (Math.abs(event.clientX - this.pointerStartX) > 7 || Math.abs(event.clientY - this.pointerStartY) > 7)) {
            this.pointerDragging = true;
            this.ignoreCardClick = true;
            this.createDragVisual();
        }
        if (!this.pointerDragging) return;
        event.preventDefault();
        this.pointerX = event.clientX;
        this.pointerY = event.clientY;
        this.positionDragVisual(event.clientX, event.clientY);
        const bounds = this.$refs.columns.getBoundingClientRect();
        if (event.clientX > bounds.right - 36) this.$refs.columns.scrollLeft += 16;
        if (event.clientX < bounds.left + 36) this.$refs.columns.scrollLeft -= 16;
        this.dragTarget = this.targetStatusAt(event.clientX, event.clientY);
    },

    createDragVisual() {
        if (!this.dragSource) return;
        const bounds = this.dragSource.getBoundingClientRect();
        const ghost = this.dragSource.cloneNode(true);
        for (const element of [ghost, ...ghost.querySelectorAll('*')]) {
            for (const attribute of [...element.attributes]) {
                if (attribute.name.startsWith('@') || attribute.name.startsWith('x-') || attribute.name.startsWith(':') || attribute.name === 'id' || attribute.name === 'role' || attribute.name === 'tabindex' || attribute.name.startsWith('aria-') || attribute.name.startsWith('data-')) {
                    element.removeAttribute(attribute.name);
                }
            }
        }
        ghost.classList.add('prayer-card-ghost');
        ghost.setAttribute('aria-hidden', 'true');
        ghost.style.width = `${bounds.width}px`;
        ghost.style.height = `${bounds.height}px`;
        document.body.appendChild(ghost);
        this.dragGhost = ghost;
        this.dragSource.classList.add('prayer-card-source');
        this.positionDragVisual(this.pointerX, this.pointerY);
    },

    positionDragVisual(x, y) {
        if (!this.dragGhost) return;
        this.dragGhost.style.transform = `translate3d(${x - this.grabOffsetX}px, ${y - this.grabOffsetY}px, 0) rotate(-1deg) scale(1.02)`;
    },

    settleDragVisual(status) {
        const ghost = this.dragGhost;
        const source = this.dragSource;
        this.dragGhost = null;
        if (!ghost) {
            if (!status) this.dragSource = null;
            return Promise.resolve();
        }

        const from = ghost.style.transform;
        const bounds = source?.getBoundingClientRect();
        const to = status
            ? `translate3d(${this.pointerX - this.grabOffsetX}px, ${this.pointerY - this.grabOffsetY}px, 0) rotate(0deg) scale(.97)`
            : `translate3d(${bounds?.left ?? 0}px, ${bounds?.top ?? 0}px, 0) rotate(0deg) scale(1)`;
        const cleanup = () => {
            ghost.remove();
            if (!status) {
                source?.classList.remove('prayer-card-source');
                if (this.dragSource === source) this.dragSource = null;
            }
        };
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches || !ghost.animate) {
            cleanup();
            return Promise.resolve();
        }
        const animation = ghost.animate([
            { transform: from, opacity: 1 },
            { transform: to, opacity: status ? 0 : 1 },
        ], { duration: status ? 150 : 180, easing: 'cubic-bezier(.2,.8,.2,1)', fill: 'forwards' });
        return animation.finished.catch(() => {}).then(cleanup);
    },

    targetStatusAt(x, y) {
        const column = document.elementFromPoint(x, y)?.closest('[data-board-status]');
        return column?.dataset.boardStatus === this.dragged?.next ? this.dragged.next : null;
    },

    pointerEnd(event) {
        if (!this.pointerActive) return;
        const card = this.dragged;
        const status = this.pointerDragging ? this.targetStatusAt(event.clientX, event.clientY) : null;
        const visual = this.settleDragVisual(status);
        this.resetPointer();
        if (status) this.move(card, status, visual);
    },

    pointerCancel() {
        if (!this.pointerActive) return;
        this.settleDragVisual(null);
        this.resetPointer();
    },

    resetPointer() {
        const wasDragging = this.pointerDragging;
        this.pointerActive = false;
        this.pointerDragging = false;
        this.dragged = null;
        this.dragTarget = null;
        if (wasDragging) {
            this.ignoreCardClick = true;
            window.setTimeout(() => { this.ignoreCardClick = false; }, 200);
        }
    },

    keyboardMove(event) {
        const card = event.currentTarget.closest('article[data-url]');
        if (card) this.move(this.cardFrom(card), card.dataset.next);
    },

    async move(card, status, visual = Promise.resolve()) {
        if (!card || this.pending) return;
        if (card.next !== status) {
            this.dragSource?.classList.remove('prayer-card-source');
            this.dragSource = null;
            this.error = 'Kartu hanya dapat dipindah ke tahap yang tersedia.';
            return;
        }

        if (status === 'closed') {
            await visual;
            this.dragSource?.classList.remove('prayer-card-source');
            this.dragSource = null;
            this.openDetail(card.detailUrl, card);
            return;
        }

        this.pending = true;
        try {
            const response = await fetch(card.url, {
                method: 'PATCH',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ status, expected_status: card.current }),
            });
            if (!response.ok) {
                const body = await response.json().catch(() => ({}));
                throw new Error(body.message || 'Status tidak dapat diubah. Muat ulang papan.');
            }
            await visual;
            window.location.reload();
        } catch (error) {
            this.error = error.message || 'Koneksi terputus. Coba lagi.';
        } finally {
            this.dragSource?.classList.remove('prayer-card-source');
            this.dragSource = null;
            this.pending = false;
        }
    },

    destroy() {
        this.boardResizeObserver?.disconnect();
        this.dragGhost?.remove();
        this.dragSource?.classList.remove('prayer-card-source');
    },

    async openDetail(url, completionCard = null) {
        this.completionCard = completionCard;
        this.detail = null;
        this.detailError = '';
        this.saveError = '';
        this.saveSuccess = '';
        this.detailLoading = true;
        if (!this.$refs.detailDialog.open) this.$refs.detailDialog.showModal();
        try {
            const response = await fetch(url, { credentials: 'same-origin', headers: { Accept: 'application/json' } });
            const body = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(body.message || 'Detail tidak dapat dibuka. Muat ulang papan.');
            if (completionCard && !body.data.can_update) throw new Error('Hanya yang mendoakan dapat menyelesaikan permohonan ini. Muat ulang papan.');
            this.detail = {
                ...body.data,
                prayer_result: body.data.prayer_result || '',
            };
        } catch (error) {
            this.detailError = error.message || 'Koneksi terputus. Coba lagi.';
        } finally {
            this.detailLoading = false;
            if (this.completionCard && this.detail) this.$nextTick(() => this.$refs.prayerResult?.focus());
        }
    },

    closeDetail() {
        if (this.saving) return;
        if (this.$refs.detailDialog.open) this.$refs.detailDialog.close();
    },

    clearDetail() {
        this.detail = null;
        this.detailError = '';
        this.completionCard = null;
        const url = new URL(window.location.href);
        if (url.searchParams.has('prayer')) {
            url.searchParams.delete('prayer');
            window.history.replaceState({}, '', url);
        }
    },

    async saveDetail() {
        if (!this.detail || !this.detail.can_update || this.saving) return;
        const completionCard = this.completionCard;
        const prayerResult = (this.detail.prayer_result || '').trim();
        if (completionCard && !prayerResult) {
            this.saveError = 'Isi hasil doa sebelum memindahkan permohonan ke Selesai.';
            this.$refs.prayerResult?.focus();
            return;
        }
        this.saving = true;
        this.saveError = '';
        this.saveSuccess = '';
        try {
            const response = await fetch(completionCard ? completionCard.url : this.detail.update_url, {
                method: completionCard ? 'PATCH' : 'PUT',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify(completionCard
                    ? { status: 'closed', expected_status: completionCard.current, prayer_result: prayerResult }
                    : { prayer_result: this.detail.prayer_result }),
            });
            const body = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(body.message || 'Hasil doa tidak dapat disimpan.');
            if (completionCard) {
                window.location.reload();
                return;
            }
            this.saveSuccess = body.message || 'Hasil doa tersimpan.';
        } catch (error) {
            this.saveError = error.message || 'Koneksi terputus. Coba lagi.';
        } finally {
            this.saving = false;
        }
    },
}));

Alpine.start();
