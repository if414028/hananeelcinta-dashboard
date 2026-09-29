@props(['name', 'label', 'value' => ''])
<div x-data="richText" class="space-y-2">
    <label id="{{ $name }}-label" for="{{ $name }}-editor" class="block text-sm font-semibold">{{ $label }} <span aria-hidden="true" class="text-signal">*</span></label>
    <div class="overflow-hidden rounded-xl border border-ink/20 bg-white">
        <div role="toolbar" aria-label="Format konten" class="flex flex-wrap items-center gap-1.5 border-b border-ink/10 p-2.5">
            <label class="sr-only" for="{{ $name }}-style">Gaya paragraf</label>
            <select id="{{ $name }}-style" :value="block" @change="command('formatBlock', $event.target.value)" class="mr-2 min-h-9 w-36 rounded-lg border border-ink/10 bg-canvas px-3 text-sm">
                <option value="p">Paragraf</option><option value="h2">Heading 2</option><option value="h3">Heading 3</option><option value="blockquote">Kutipan</option>
            </select>
            @foreach([['bold', 'Bold', 'bold'], ['italic', 'Italic', 'italic'], ['underline', 'Underline', 'underline'], ['strikeThrough', 'Coret', 'strike'], ['justifyLeft', 'Rata kiri', 'align-left'], ['justifyCenter', 'Rata tengah', 'align-center'], ['justifyRight', 'Rata kanan', 'align-right'], ['justifyFull', 'Rata kiri kanan', 'align-justify'], ['insertUnorderedList', 'Daftar bullet', 'list'], ['insertOrderedList', 'Daftar bernomor', 'list-ordered']] as [$command, $title, $icon])
                <button type="button" aria-label="{{ $title }}" title="{{ $title }}" :aria-pressed="Boolean(active['{{ $command }}'])" @mousedown.prevent @click="command('{{ $command }}')" class="rich-text-button" :class="active['{{ $command }}'] && 'is-active'"><x-icon :name="$icon" :size="17" /></button>
            @endforeach
            <button type="button" aria-label="Tambahkan tautan" title="Tambahkan tautan" @mousedown.prevent @click="openLink()" class="rich-text-button"><x-icon name="link" :size="17" /></button>
            <div class="ml-auto flex items-center gap-1.5">
                <button type="button" aria-label="Urungkan" title="Urungkan" @mousedown.prevent @click="command('undo')" class="rich-text-button"><x-icon name="undo" :size="17" /></button>
                <button type="button" aria-label="Ulangi" title="Ulangi" @mousedown.prevent @click="command('redo')" class="rich-text-button"><x-icon name="redo" :size="17" /></button>
                <button type="button" @mousedown.prevent @click="command('removeFormat')" class="rich-text-button !w-auto px-3 text-xs">Hapus format</button>
            </div>
        </div>
        <div x-cloak x-show="linkOpen" class="flex flex-wrap items-center gap-2 border-b border-ink/10 bg-canvas p-3">
            <label for="{{ $name }}-link" class="text-sm">URL tautan</label>
            <input id="{{ $name }}-link" x-ref="linkInput" x-model="linkUrl" type="url" placeholder="https://..." @keydown.enter.prevent="insertLink()" class="min-h-10 min-w-0 flex-1 rounded-lg border border-ink/20 bg-white px-3 text-sm">
            <button type="button" @click="insertLink()" class="button-primary text-sm">Terapkan</button>
            <button type="button" @click="command('unlink'); linkOpen = false" class="button-secondary text-sm">Hapus tautan</button>
            <button type="button" @click="linkOpen = false" class="button-secondary text-sm">Batal</button>
            <p x-show="linkError" class="w-full text-xs text-signal">Masukkan URL yang diawali https:// atau http://.</p>
        </div>
        <textarea x-ref="input" name="{{ $name }}" hidden>{{ app(\App\Services\HtmlSanitizer::class)->sanitize($value ?? '') }}</textarea>
        <div id="{{ $name }}-editor" x-ref="editor" contenteditable="true" role="textbox" aria-multiline="true" aria-required="true" aria-labelledby="{{ $name }}-label" aria-describedby="{{ $name }}-hint" @input="sync()" @blur="saveSelection()" @keyup="saveSelection()" @mouseup="saveSelection()" @paste.prevent="paste($event)" class="rich-text-content min-h-80 p-5 focus:outline-none focus:ring-3 focus:ring-inset focus:ring-link/30"></div>
        <div class="flex justify-end border-t border-ink/10 px-4 py-3 text-xs text-slate"><span x-text="`${characters} karakter · ${words} kata`">0 karakter · 0 kata</span></div>
    </div>
    <p id="{{ $name }}-hint" class="text-xs text-slate">Gunakan toolbar untuk memformat tulisan. Ctrl/Cmd + B, I, atau U juga tersedia.</p>
    @error($name)<p class="text-sm text-signal">{{ $message }}</p>@enderror
</div>
