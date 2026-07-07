// Admin → Imagini: management Hero + Galerie cu drag-to-reorder și toggle din bibliotecă.
(function () {
    const form = document.getElementById('img-form');
    if (!form) return;

    const NAMES = window.CLP_NAMES || {};
    const THUMBS = window.CLP_THUMBS || {};
    const state = {
        hero:    (window.CLP_HERO || []).slice(),
        gallery: (window.CLP_GALLERY || []).slice(),
        transforms: Object.assign({}, window.CLP_TRANSFORMS || {}),
    };
    const strips = {
        hero:    document.getElementById('hero-strip'),
        gallery: document.getElementById('gallery-strip'),
    };
    const dirtyEl = document.getElementById('img-dirty');
    let dirty = false;

    function nameFor(url) {
        return NAMES[url] || url.split('/').pop();
    }

    function thumbFor(url) {
        return THUMBS[url] || url;
    }

    function transformFor(url) {
        const t = state.transforms[url] || {};
        return { x: t.x ?? 50, y: t.y ?? 50, zoom: t.zoom ?? 100 };
    }

    // Aplică poziția/zoom-ul pe thumbnail-ul din bandă (mini-preview live)
    function applyThumbTransform(img, url) {
        const t = transformFor(url);
        img.style.objectPosition = t.x + '% ' + t.y + '%';
        img.style.transform = 'scale(' + (t.zoom / 100) + ')';
        img.style.transformOrigin = t.x + '% ' + t.y + '%';
    }

    function markDirty() {
        if (dirty) return;
        dirty = true;
        if (dirtyEl) dirtyEl.hidden = false;
    }

    function renderStrip(target) {
        const strip = strips[target];
        const list = state[target];
        strip.innerHTML = '';
        if (!list.length) {
            const empty = document.createElement('div');
            empty.className = 'img-strip-empty';
            empty.textContent = target === 'hero'
                ? 'Nicio imagine în slideshow. Adaug-o din Bibliotecă.'
                : 'Nicio imagine în galerie. Adaug-o din Bibliotecă.';
            strip.appendChild(empty);
            return;
        }
        list.forEach((url, i) => {
            const item = document.createElement('div');
            item.className = 'img-strip-item';
            item.draggable = true;
            item.dataset.url = url;

            const badge = document.createElement('span');
            badge.className = 'img-strip-badge';
            badge.textContent = i + 1;

            const img = document.createElement('img');
            img.src = thumbFor(url);
            img.alt = nameFor(url);
            img.loading = 'lazy';
            img.decoding = 'async';
            if (target === 'hero') applyThumbTransform(img, url);

            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'img-strip-remove';
            remove.title = 'Scoate';
            remove.textContent = '✕';
            remove.addEventListener('click', () => toggle(target, url));

            item.append(badge, img, remove);

            if (target === 'hero') {
                const cog = document.createElement('button');
                cog.type = 'button';
                cog.className = 'img-strip-cog';
                cog.title = 'Poziție & zoom';
                cog.textContent = '⚙';
                cog.addEventListener('click', () => openEditor(url));
                item.appendChild(cog);
            }

            strip.appendChild(item);
        });
        wireDrag(strip, target);
    }

    function wireDrag(strip, target) {
        let dragEl = null;
        strip.querySelectorAll('.img-strip-item').forEach((item) => {
            item.addEventListener('dragstart', (e) => {
                dragEl = item;
                item.classList.add('dragging');
                e.dataTransfer.effectAllowed = 'move';
            });
            item.addEventListener('dragend', () => {
                item.classList.remove('dragging');
                dragEl = null;
            });
        });
        strip.addEventListener('dragover', (e) => {
            e.preventDefault();
            if (!dragEl) return;
            const after = getDragAfter(strip, e.clientX);
            if (after == null) strip.appendChild(dragEl);
            else strip.insertBefore(dragEl, after);
        });
        strip.addEventListener('drop', (e) => {
            e.preventDefault();
            // Doar re-citim ordinea și re-numerotăm badge-urile — fără rebuild (zero re-decode al imaginilor)
            const items = [...strip.querySelectorAll('.img-strip-item')];
            state[target] = items.map((el) => el.dataset.url);
            items.forEach((el, i) => { el.querySelector('.img-strip-badge').textContent = i + 1; });
            markDirty();
        });
    }

    function getDragAfter(strip, x) {
        const items = [...strip.querySelectorAll('.img-strip-item:not(.dragging)')];
        let closest = { offset: -Infinity, el: null };
        for (const el of items) {
            const box = el.getBoundingClientRect();
            const offset = x - box.left - box.width / 2;
            if (offset < 0 && offset > closest.offset) closest = { offset, el };
        }
        return closest.el;
    }

    function toggle(target, url) {
        const list = state[target];
        const idx = list.indexOf(url);
        if (idx === -1) list.push(url);
        else {
            list.splice(idx, 1);
            if (target === 'hero') {
                delete state.transforms[url];
                if (editor.url === url) closeEditor();
            }
        }
        markDirty();
        renderStrip(target);
        syncChips();
    }

    function syncChips() {
        document.querySelectorAll('.img-chip').forEach((chip) => {
            const active = state[chip.dataset.role].includes(chip.dataset.url);
            chip.classList.toggle('is-active', active);
        });
    }

    // ── Editor poziție & zoom hero ──
    const editor = {
        url: null,
        box:    document.getElementById('hero-editor'),
        bg:     document.getElementById('hero-editor-bg'),
        name:   document.getElementById('hero-editor-name'),
        x:      document.getElementById('he-x'),
        y:      document.getElementById('he-y'),
        zoom:   document.getElementById('he-zoom'),
        valX:   document.getElementById('val-x'),
        valY:   document.getElementById('val-y'),
        valZoom:document.getElementById('val-zoom'),
    };

    function openEditor(url) {
        editor.url = url;
        const t = transformFor(url);
        editor.x.value = t.x;
        editor.y.value = t.y;
        editor.zoom.value = t.zoom;
        editor.name.textContent = nameFor(url);
        editor.bg.style.backgroundImage = "url('" + thumbFor(url) + "')";
        editor.box.hidden = false;
        applyEditorPreview();
        editor.box.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function closeEditor() {
        editor.url = null;
        editor.box.hidden = true;
    }

    function applyEditorPreview() {
        const x = +editor.x.value, y = +editor.y.value, z = +editor.zoom.value;
        editor.valX.textContent = x + '%';
        editor.valY.textContent = y + '%';
        editor.valZoom.textContent = z + '%';
        editor.bg.style.backgroundPosition = x + '% ' + y + '%';
        editor.bg.style.transform = 'scale(' + (z / 100) + ')';
        editor.bg.style.transformOrigin = x + '% ' + y + '%';
    }

    function onEditorInput() {
        if (!editor.url) return;
        const x = +editor.x.value, y = +editor.y.value, z = +editor.zoom.value;
        state.transforms[editor.url] = { x: x, y: y, zoom: z };
        applyEditorPreview();
        // reflectă live pe thumbnail-ul din bandă
        const item = strips.hero.querySelector('.img-strip-item[data-url="' + cssEsc(editor.url) + '"] img');
        if (item) applyThumbTransform(item, editor.url);
        markDirty();
    }

    function cssEsc(s) {
        return (window.CSS && CSS.escape) ? CSS.escape(s) : s.replace(/["\\]/g, '\\$&');
    }

    [editor.x, editor.y, editor.zoom].forEach((el) => el && el.addEventListener('input', onEditorInput));
    document.getElementById('hero-editor-close')?.addEventListener('click', closeEditor);
    document.getElementById('hero-editor-reset')?.addEventListener('click', () => {
        editor.x.value = 50; editor.y.value = 50; editor.zoom.value = 100;
        onEditorInput();
    });

    // Chips din bibliotecă
    document.querySelectorAll('.img-chip').forEach((chip) => {
        chip.addEventListener('click', () => toggle(chip.dataset.role, chip.dataset.url));
    });

    // Ștergere din bibliotecă
    document.querySelectorAll('.img-tile-del').forEach((btn) => {
        btn.addEventListener('click', () => deleteImage(btn.dataset.del));
    });

    // Serializează selecția ordonată la submit
    form.addEventListener('submit', () => {
        const hidden = document.getElementById('img-hidden');
        hidden.innerHTML = '';
        const add = (name, url) => {
            const i = document.createElement('input');
            i.type = 'hidden'; i.name = name; i.value = url;
            hidden.appendChild(i);
        };
        state.hero.forEach((u) => add('hero_images[]', u));
        state.gallery.forEach((u) => add('gallery_featured[]', u));
        add('hero_transforms', JSON.stringify(state.transforms));
    });

    renderStrip('hero');
    renderStrip('gallery');
    syncChips();
})();

function deleteImage(filename) {
    if (!confirm('Ștergi imaginea „' + filename + '"?')) return;
    const fd = new FormData();
    fd.append('action', 'delete_image');
    fd.append('filename', filename);
    fetch('/admin/?tab=imagini', { method: 'POST', body: fd })
        .then(() => location.reload());
}
