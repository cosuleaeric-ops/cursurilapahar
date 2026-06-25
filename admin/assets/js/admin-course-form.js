(function () {
    const CLP_RO_MONTHS = ['', 'ianuarie', 'februarie', 'martie', 'aprilie', 'mai', 'iunie', 'iulie', 'august', 'septembrie', 'octombrie', 'noiembrie', 'decembrie'];
    const CLP_ALLOWED_TIMES = ['17:00', '17:30', '18:00', '18:30', '19:00'];
    let ltFetchTimer = null;

    function clpFormatDateRo(ymd) {
        if (!ymd) return '';
        const p = ymd.split('-');
        if (p.length !== 3) return ymd;
        const d = parseInt(p[2], 10);
        const m = parseInt(p[1], 10);
        return d + ' ' + (CLP_RO_MONTHS[m] ? CLP_RO_MONTHS[m].charAt(0).toUpperCase() + CLP_RO_MONTHS[m].slice(1) : '') + ' ' + p[0];
    }

    function clpSpeakerDisplayName() {
        const id = document.getElementById('f_speaker_id')?.value || '';
        const hit = (window.CLP_SPEAKERS_PICKER || []).find(s => s.id === id);
        return hit ? hit.name : (document.getElementById('f_speaker_input')?.value.trim() || '');
    }

    function clpResolveSpeakerFromInput() {
        const input = document.getElementById('f_speaker_input');
        const hidden = document.getElementById('f_speaker_id');
        if (!input || !hidden) return false;
        const q = input.value.trim().toLowerCase();
        if (!q) { hidden.value = ''; return false; }
        const list = window.CLP_SPEAKERS_PICKER || [];
        const exact = list.find(s => s.name.toLowerCase() === q);
        if (exact) { hidden.value = exact.id; return true; }
        const partial = list.filter(s => s.name.toLowerCase().includes(q));
        if (partial.length === 1) {
            hidden.value = partial[0].id;
            input.value = partial[0].name;
            return true;
        }
        hidden.value = '';
        return false;
    }

    function clpRenderSpeakerSuggestions(filter) {
        const box = document.getElementById('f_speaker_suggestions');
        if (!box) return;
        const q = (filter || '').trim().toLowerCase();
        const list = (window.CLP_SPEAKERS_PICKER || []).filter(s => !q || s.name.toLowerCase().includes(q));
        if (!list.length) {
            box.hidden = true;
            box.innerHTML = '';
            return;
        }
        box.innerHTML = '';
        list.forEach(s => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.textContent = s.name + (s.status ? ' (' + s.status + ')' : '');
            btn.addEventListener('mousedown', e => {
                e.preventDefault();
                document.getElementById('f_speaker_id').value = s.id;
                document.getElementById('f_speaker_input').value = s.name;
                box.hidden = true;
                updateCoursePreview();
            });
            box.appendChild(btn);
        });
        box.hidden = false;
    }

    function clpRenderLocationSuggestions(filter) {
        const box = document.getElementById('f_location_suggestions');
        if (!box) return;
        const q = (filter || '').trim().toLowerCase();
        const list = (window.CLP_LOCATIONS_PICKER || []).filter(l => !q || l.name.toLowerCase().includes(q));
        if (!list.length) {
            box.hidden = true;
            box.innerHTML = '';
            return;
        }
        box.innerHTML = '';
        list.forEach(l => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.textContent = l.name;
            btn.addEventListener('mousedown', e => {
                e.preventDefault();
                document.getElementById('f_location_input').value = l.name;
                box.hidden = true;
                updateCoursePreview();
            });
            box.appendChild(btn);
        });
        box.hidden = false;
    }

    function clpInitLocationCombobox() {
        const input = document.getElementById('f_location_input');
        const box = document.getElementById('f_location_suggestions');
        if (!input || !box) return;
        input.addEventListener('focus', () => clpRenderLocationSuggestions(input.value));
        input.addEventListener('input', () => {
            clpRenderLocationSuggestions(input.value);
            updateCoursePreview();
        });
        input.addEventListener('blur', () => {
            setTimeout(() => { box.hidden = true; }, 150);
        });
    }

    function clpInitSpeakerCombobox() {
        const input = document.getElementById('f_speaker_input');
        const box = document.getElementById('f_speaker_suggestions');
        if (!input || !box) return;
        input.addEventListener('focus', () => clpRenderSpeakerSuggestions(input.value));
        input.addEventListener('input', () => {
            document.getElementById('f_speaker_id').value = '';
            clpRenderSpeakerSuggestions(input.value);
            updateCoursePreview();
        });
        input.addEventListener('blur', () => {
            setTimeout(() => {
                box.hidden = true;
                clpResolveSpeakerFromInput();
                updateCoursePreview();
            }, 150);
        });
    }

    function updateCoursePreview() {
        const title = document.getElementById('f_title')?.value.trim() || '';
        const dateRaw = document.getElementById('f_date_raw')?.value || '';
        const time = document.getElementById('f_time')?.value || '';
        const speaker = clpSpeakerDisplayName();
        const preview = document.getElementById('coursePreview');
        if (!preview || !title) {
            if (preview) preview.style.display = 'none';
            return;
        }
        document.getElementById('prev_title').textContent = title;
        const location = document.getElementById('f_location_input')?.value.trim() || '';
        document.getElementById('prev_meta').textContent =
            [clpFormatDateRo(dateRaw), time, speaker, location].filter(Boolean).join(' · ');
        preview.style.display = 'flex';
    }

    window.validateCourseForm = function () {
        clpResolveSpeakerFromInput();
        const speaker = document.getElementById('f_speaker_id')?.value || '';
        const time = document.getElementById('f_time')?.value || '';
        if (!speaker) {
            alert('Alege un speaker din lista de pe tab-ul Speakeri (nume exact).');
            return false;
        }
        if (!CLP_ALLOWED_TIMES.includes(time)) {
            alert('Alege ora din listă (17:00, 17:30, 18:00, 18:30 sau 19:00).');
            return false;
        }
        return true;
    };

    window.fetchLTImage = async function () {
        const urlInput = document.getElementById('f_lt_url');
        const msg = document.getElementById('importMsg');
        if (!urlInput || !msg) return;

        const url = urlInput.value.trim();
        const img = document.getElementById('prev_img');

        if (!url) {
            document.getElementById('f_image_url').value = '';
            if (img) { img.src = ''; img.style.display = 'none'; }
            msg.textContent = '';
            updateCoursePreview();
            return;
        }

        clearTimeout(ltFetchTimer);
        ltFetchTimer = setTimeout(async () => {
            msg.style.cssText = 'color:var(--text-muted);margin-top:8px;font-size:13px';
            msg.textContent = 'Se preia imaginea de pe LiveTickets…';

            try {
                const res = await fetch('/api/livetickets.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ url })
                });
                const data = await res.json();

                if (data.success && data.data) {
                    const d = data.data;
                    document.getElementById('f_image_url').value = d.image_url || '';
                    if (d.location) {
                        const locIn = document.getElementById('f_location_input');
                        if (locIn && !locIn.value.trim()) locIn.value = d.location;
                    }
                    if (d.image_url && img) {
                        img.src = d.image_url;
                        img.style.display = 'block';
                    }
                    msg.style.color = 'var(--success)';
                    msg.textContent = d.image_url ? '✓ Imagine preluată de pe LiveTickets.' : 'Link valid, dar nu s-a găsit imagine.';
                } else {
                    msg.style.color = 'var(--danger)';
                    msg.textContent = data.message || 'Eroare la preluarea imaginii.';
                }
            } catch (err) {
                msg.style.color = 'var(--danger)';
                msg.textContent = 'Eroare: ' + err.message;
            }
            updateCoursePreview();
        }, 400);
    };

    document.getElementById('f_title')?.addEventListener('input', updateCoursePreview);
    clpInitSpeakerCombobox();
    clpInitLocationCombobox();

    const ltUrl = document.getElementById('f_lt_url')?.value.trim();
    const imgUrl = document.getElementById('f_image_url')?.value.trim();
    if (document.getElementById('f_course_id')?.value) {
        updateCoursePreview();
        const prev = document.getElementById('prev_img');
        if (imgUrl && prev) { prev.src = imgUrl; prev.style.display = 'block'; }
        document.getElementById('coursePreview')?.style.setProperty('display', 'flex');
        document.getElementById('course-form-card')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
    if (ltUrl && !imgUrl) {
        fetchLTImage();
    }
})();
