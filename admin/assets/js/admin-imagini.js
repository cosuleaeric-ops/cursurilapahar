// Admin → Imagini: management Hero + Galerie cu drag-to-reorder și toggle din bibliotecă.
(function () {
    const form = document.getElementById('img-form');
    if (!form) return;

    const NAMES = window.CLP_NAMES || {};
    const state = {
        hero:    (window.CLP_HERO || []).slice(),
        gallery: (window.CLP_GALLERY || []).slice(),
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
            img.src = url;
            img.alt = nameFor(url);
            img.loading = 'lazy';

            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'img-strip-remove';
            remove.title = 'Scoate';
            remove.textContent = '✕';
            remove.addEventListener('click', () => toggle(target, url));

            item.append(badge, img, remove);
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
            state[target] = [...strip.querySelectorAll('.img-strip-item')].map((el) => el.dataset.url);
            markDirty();
            renderStrip(target);
            syncChips();
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
        else list.splice(idx, 1);
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

    // Chips din bibliotecă
    document.querySelectorAll('.img-chip').forEach((chip) => {
        chip.addEventListener('click', () => toggle(chip.dataset.role, chip.dataset.url));
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
