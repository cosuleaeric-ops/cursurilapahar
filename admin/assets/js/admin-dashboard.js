(function () {
    const cfg = window.DASH_CAL;
    if (!cfg) return;

    const DOW = ['Lu', 'Ma', 'Mi', 'Jo', 'Vi', 'Sâ', 'Du'];
    const grid = document.getElementById('dashMiniCal');
    const btnPrev = document.getElementById('dashCalPrev');
    const btnNext = document.getElementById('dashCalNext');
    if (!grid) return;

    let monthOffset = 0;
    const today = cfg.today;
    const coursesByDay = cfg.coursesByDay || {};

    function addDays(date, n) {
        const d = new Date(date);
        d.setDate(d.getDate() + n);
        return d;
    }

    function fmtYmd(d) {
        const y = d.getFullYear();
        const m = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return `${y}-${m}-${day}`;
    }

    function esc(s) {
        return String(s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function renderCal() {
        const base = new Date(today + 'T12:00:00');
        const view = new Date(base.getFullYear(), base.getMonth() + monthOffset, 1);
        const y = view.getFullYear();
        const m = view.getMonth();

        const first = new Date(y, m, 1);
        const lastDate = new Date(y, m + 1, 0).getDate();
        const firstDow = first.getDay() === 0 ? 7 : first.getDay();
        const gridStart = addDays(first, -(firstDow - 1));
        const weeks = Math.ceil((firstDow - 1 + lastDate) / 7);
        const totalCells = weeks * 7;

        let html = DOW.map(d => `<div class="mini-cal-dow">${d}</div>`).join('');
        let cur = new Date(gridStart);
        for (let i = 0; i < totalCells; i++) {
            const ds = fmtYmd(cur);
            const isToday = ds === today;
            const isPast = ds < today;
            const cellCls = isToday ? 'today' : (isPast ? 'past' : '');
            const events = coursesByDay[ds] || [];
            let evHtml = events.map(ev => {
                const cls = isToday ? 'today-ev' : (isPast ? 'past' : 'future');
                return `<div class="mini-cal-event ${cls}" title="${esc(ev.title)}">${esc(ev.title)}</div>`;
            }).join('');
            html += `<div class="mini-cal-cell ${cellCls}">
                <div class="mini-cal-day">${cur.getDate()}</div>${evHtml}
            </div>`;
            cur = addDays(cur, 1);
        }
        grid.innerHTML = html;
    }

    btnPrev?.addEventListener('click', () => { monthOffset -= 1; renderCal(); });
    btnNext?.addEventListener('click', () => { monthOffset += 1; renderCal(); });
    renderCal();
})();
