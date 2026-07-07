(function () {
    const cfg = window.CLP_STATS || {};
    const clpRoMonths = ['', 'ianuarie', 'februarie', 'martie', 'aprilie', 'mai', 'iunie', 'iulie', 'august', 'septembrie', 'octombrie', 'noiembrie', 'decembrie'];
    const calRoMonths = ['', 'Ianuarie', 'Februarie', 'Martie', 'Aprilie', 'Mai', 'Iunie', 'Iulie', 'August', 'Septembrie', 'Octombrie', 'Noiembrie', 'Decembrie'];
    const calDow = ['Lu', 'Ma', 'Mi', 'Jo', 'Vi', 'Sâ', 'Du'];
    const calCourses = cfg.calCourses || [];
    const igPostTypes = cfg.igPostTypes || {};
    let igPosts = cfg.igPosts || {};
    const vizaHeaders = ['Seria', 'De la', 'Până la', 'Vândute', 'Total', 'Tarif'];

    let clpYear = cfg.year || new Date().getFullYear();
    let clpMonth = cfg.month || (new Date().getMonth() + 1);
    let calYear = cfg.calYear || clpYear;
    let calMonth = cfg.calMonth || clpMonth;
    const calToday = new Date().toISOString().slice(0, 10);
    let participantsLoaded = false;

    function esc(s) {
        return String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function fmtCourseTag(raw) {
        const parts = String(raw).split(' (');
        const name = esc(parts[0].trim());
        if (parts.length < 2) return name;
        const datePart = parts[1].replace(/\)$/, '').slice(0, 7);
        return name + ' <span style="opacity:.6">(' + esc(datePart) + ')</span>';
    }

    window.clpSwitchTab = function (e, t) {
        document.querySelectorAll('.clp-tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.clp-tab-panel').forEach(p => p.classList.remove('active'));
        document.getElementById('clp-panel-' + t).classList.add('active');
        e.currentTarget.classList.add('active');
        if (t === 'calendar') calRender();
        if (t === 'participanti' && !participantsLoaded) clpLoadParticipants();
    };

    window.clpToggleViza = function (id) {
        document.getElementById(id).classList.toggle('open');
    };

    window.clpNav = function (dir) {
        clpMonth += dir;
        if (clpMonth < 1) { clpMonth = 12; clpYear--; }
        if (clpMonth > 12) { clpMonth = 1; clpYear++; }
        calYear = clpYear;
        calMonth = clpMonth;
        clpLoadMonth();
        if (document.getElementById('clp-panel-calendar')?.classList.contains('active')) calRender();
    };

    function renderVizaRows(subs, rid) {
        if (!subs.length) return '';
        const head = vizaHeaders.map(h =>
            `<th style="padding:5px 10px;font-size:10px;font-weight:700;text-transform:uppercase;color:var(--text-muted);border-bottom:1px solid var(--border);text-align:${h === 'Seria' ? 'left' : 'right'}">${h}</th>`
        ).join('');
        const body = subs.map(s => {
            const vandute = s.vandute != null ? `<strong>${s.vandute}</strong>` : '—';
            return `<tr>
                <td style="padding:5px 10px;border-bottom:1px solid #f1f5f9"><span class="clp-seria">${esc(s.seria)}</span></td>
                <td style="padding:5px 10px;text-align:right;border-bottom:1px solid #f1f5f9">${esc(s.de_la)}</td>
                <td style="padding:5px 10px;text-align:right;border-bottom:1px solid #f1f5f9">${esc(s.pana_la)}</td>
                <td style="padding:5px 10px;text-align:right;border-bottom:1px solid #f1f5f9">${vandute}</td>
                <td style="padding:5px 10px;text-align:right;border-bottom:1px solid #f1f5f9">${s.nr_unitati}</td>
                <td style="padding:5px 10px;text-align:right;border-bottom:1px solid #f1f5f9">${Number(s.tarif).toLocaleString('ro-RO', { maximumFractionDigits: 0 })} RON</td>
            </tr>`;
        }).join('');
        return `<tr class="clp-viza-row" id="${rid}"><td colspan="7" style="padding:0;background:#f8fafc">
            <div style="padding:6px 16px 12px 32px"><table style="width:100%;border-collapse:collapse;font-size:12px">
            <thead><tr>${head}</tr></thead><tbody>${body}</tbody>
            </table></div></td></tr>`;
    }

    async function clpLoadMonth() {
        const res = await fetch('/api/cursuri_month.php?year=' + clpYear + '&month=' + clpMonth);
        const data = await res.json();
        const fmtRON = v => Number(v).toLocaleString('ro-RO', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        const label = document.getElementById('clpMonthLabel');
        if (label) {
            label.textContent = clpRoMonths[data.month].charAt(0).toUpperCase() + clpRoMonths[data.month].slice(1) + ' ' + data.year;
        }

        const cursPanel = document.getElementById('clp-panel-cursuri');
        if (!cursPanel) return;
        if (!data.courses.length) {
            cursPanel.innerHTML = '<p style="color:var(--text-muted)">Niciun curs pentru perioada selectată.</p>';
            return;
        }

        const ditlById = {};
        data.by_month.forEach(grp => grp.rows.forEach(r => { ditlById[r.id] = r; }));

        const sumInc = data.sum_incasari;
        const sumDitlBase = data.sum_ditl_base || 0; // pret × vandute − retururi
        let html = sumInc > 0 ? `<div class="clp-summary-grid" style="margin-bottom:16px">
            <div class="clp-stat-box"><div class="lbl">Total încasări</div><div class="val">${fmtRON(sumInc)} <small style="font-size:14px;font-weight:400">RON</small></div></div>
            <div class="clp-stat-box"><div class="lbl">Taxă DITL (2%)</div><div class="val ditl">${fmtRON(sumDitlBase * 0.02)} <small style="font-size:14px;font-weight:400">RON</small></div></div>
        </div>` : '';

        html += `<table class="wp-table"><thead><tr>
            <th>Curs</th><th>Dată</th>
            <th style="text-align:right">Bilete</th>
            <th style="text-align:center">Raport</th>
            <th style="text-align:center">Viză</th>
            <th style="text-align:right">Încasări</th>
            <th style="text-align:right">DITL (2%)</th>
        </tr></thead><tbody>`;

        data.courses.forEach(c => {
            const dr = ditlById[c.id];
            const rid = 'clpv-' + c.id;
            const subs = dr && dr.subtips && dr.subtips.length ? dr.subtips : [];
            const inc = dr ? fmtRON(dr.total_incasari) + ' RON' : '<span style="color:#d1d5db">—</span>';
            const ditl = dr ? `<span class="clp-ditl-cell">${fmtRON((dr.ditl_base || 0) * 0.02)} RON</span>` : '<span style="color:#d1d5db">—</span>';
            const name = subs.length
                ? `<span class="clp-toggle" onclick="event.stopPropagation();clpToggleViza('${rid}')">${esc(c.name)}</span>`
                : esc(c.name);
            html += `<tr style="cursor:pointer" onclick="location.href='/admin/statistici/cursuri/view.php?id=${c.id}'">
                <td style="font-weight:600">${name}</td>
                <td style="color:var(--text-muted);white-space:nowrap">${esc(c.date_ro)}</td>
                <td style="text-align:right">${c.total_tickets}</td>
                <td style="text-align:center">${c.has_report ? '<span style="color:#16a34a;font-size:16px">✓</span>' : '<span style="color:#d1d5db;font-size:16px">—</span>'}</td>
                <td style="text-align:center">${c.has_viza ? '<span style="color:#16a34a;font-size:16px">✓</span>' : '<span style="color:#d1d5db;font-size:16px">—</span>'}</td>
                <td style="text-align:right;font-variant-numeric:tabular-nums">${inc}</td>
                <td style="text-align:right;font-variant-numeric:tabular-nums">${ditl}</td>
            </tr>`;
            html += renderVizaRows(subs, rid);
        });

        html += '</tbody></table>';
        cursPanel.innerHTML = html;
    }

    async function clpLoadParticipants() {
        const panel = document.getElementById('clp-panel-participanti');
        if (!panel) return;
        try {
            const res = await fetch('/api/participanti.php');
            const data = await res.json();
            participantsLoaded = true;
            const stats = data.stats || {};
            const list = data.participants || [];
            if (!list.length) {
                panel.innerHTML = '<p style="color:var(--text-muted)">Niciun participant înregistrat încă.</p>';
                return;
            }
            const evo = data.evolution || [];
            let evoHtml = '';
            if (evo.length) {
                evoHtml = `<div class="dash-section" style="margin-bottom:20px">
                    <div class="dash-section-title"><span>Evoluție participanți</span></div>
                    <table class="dash-table">
                        <tr style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted)">
                            <td>Luna</td><td style="text-align:right">Unici</td><td style="text-align:right">Bilete</td>
                        </tr>`;
                evo.forEach(e => {
                    const mi = parseInt((e.m || '').slice(5, 7), 10);
                    const yr = (e.m || '').slice(0, 4);
                    const mn = (clpRoMonths[mi] || '').charAt(0).toUpperCase() + (clpRoMonths[mi] || '').slice(1);
                    evoHtml += `<tr><td>${mn} ${yr}</td><td style="text-align:right;font-weight:600">${e.unici}</td><td style="text-align:right" class="muted">${e.bilete}</td></tr>`;
                });
                evoHtml += '</table></div>';
            }

            let html = `<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:20px">
                <div class="clp-stat-box"><div class="lbl">Participanți unici</div><div class="val">${stats.unique || 0}</div></div>
                <div class="clp-stat-box"><div class="lbl">Revin la 2+ cursuri</div><div class="val" style="color:#16a34a">${stats.returning || 0}</div></div>
                <div class="clp-stat-box"><div class="lbl">Total bilete vândute</div><div class="val">${stats.tickets || 0}</div></div>
            </div>
            ${evoHtml}
            <div style="margin-bottom:12px">
                <input type="text" id="clpSearch" placeholder="Caută participant…" oninput="clpFilter()" style="width:100%;padding:9px 12px;border:1px solid var(--border);border-radius:8px;font-size:13px;background:#fff">
            </div>
            <table class="wp-table" id="clpParticipantsTable"><thead><tr>
                <th>Participant</th>
                <th style="text-align:right;width:90px"># Cursuri</th>
                <th style="text-align:right;width:90px"># Bilete</th>
                <th>Cursuri</th>
            </tr></thead><tbody>`;
            list.forEach(p => {
                const badge = (p.num_courses || 0) > 1
                    ? ' <span style="background:#dcfce7;color:#16a34a;padding:2px 8px;border-radius:20px;font-size:11px;font-weight:600">revine</span>'
                    : '';
                const tags = (p.courses || []).filter(Boolean).map(c =>
                    `<span style="background:#f1f5f9;border:1px solid var(--border);border-radius:4px;font-size:11px;color:var(--text-muted);padding:2px 6px">${fmtCourseTag(c)}</span>`
                ).join('');
                html += `<tr>
                    <td><strong>${esc(p.participant_name)}</strong>${badge}</td>
                    <td style="text-align:right">${p.num_courses}</td>
                    <td style="text-align:right">${p.total_tickets}</td>
                    <td><div style="display:flex;flex-wrap:wrap;gap:4px">${tags}</div></td>
                </tr>`;
            });
            html += '</tbody></table>';

            panel.innerHTML = html;
        } catch (err) {
            panel.innerHTML = '<p style="color:var(--danger)">Eroare la încărcarea participanților.</p>';
        }
    }

    function igChipsHtml(ds) {
        return (igPosts[ds] || []).map(t => {
            const label = (igPostTypes[t] && igPostTypes[t].label) || t;
            return `<div class="cal-event ig-post" title="${esc(label)}">${esc(label)}</div>`;
        }).join('');
    }

    function calRender() {
        const label = document.getElementById('clpMonthLabel');
        if (label) {
            label.textContent = calRoMonths[calMonth].charAt(0).toUpperCase() + calRoMonths[calMonth].slice(1) + ' ' + calYear;
        }
        const grid = document.getElementById('calGrid');
        if (!grid) return;

        const firstDow = (new Date(calYear, calMonth - 1, 1).getDay() + 6) % 7;
        const daysInMonth = new Date(calYear, calMonth, 0).getDate();
        const numRows = Math.ceil((firstDow + daysInMonth) / 7);

        const byDay = {};
        calCourses.forEach(c => {
            if (c.date) {
                if (!byDay[c.date]) byDay[c.date] = [];
                byDay[c.date].push(c.title);
            }
        });

        let html = calDow.map(d => `<div class="cal-dow">${d}</div>`).join('');
        grid.style.height = `calc(36px + ${numRows} * 120px)`;
        grid.style.gridTemplateRows = `36px repeat(${numRows}, 1fr)`;

        for (let i = 0; i < firstDow; i++) html += '<div class="cal-cell other-month"></div>';
        for (let day = 1; day <= daysInMonth; day++) {
            const ds = `${calYear}-${String(calMonth).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            const isToday = ds === calToday;
            html += `<div class="cal-cell cal-cell--pick${isToday ? ' today' : ''}" data-date="${ds}">`;
            html += isToday ? `<div class="cal-day-num"><span class="cal-circle">${day}</span></div>`
                : `<div class="cal-day-num">${day}</div>`;
            (byDay[ds] || []).forEach(title => {
                const cls = isToday ? 'today-ev' : ds < calToday ? 'past' : 'future';
                html += `<div class="cal-event ${cls}" title="${esc(title)}">${esc(title)}</div>`;
            });
            html += igChipsHtml(ds);
            html += '</div>';
        }
        const trailing = (7 - ((firstDow + daysInMonth) % 7)) % 7;
        for (let i = 0; i < trailing; i++) html += '<div class="cal-cell other-month"></div>';
        grid.innerHTML = html;

        if (!grid.dataset.igBound) {
            grid.dataset.igBound = '1';
            grid.addEventListener('click', e => {
                const cell = e.target.closest('.cal-cell--pick');
                if (cell) openDayMenu(cell, cell.dataset.date);
            });
        }
    }

    // ── Day dropdown: confirm Instagram posts (e.g. POSTARE CURSURI) ──────
    let dayMenuEl = null;

    function roDateLabel(ds) {
        const [y, m, d] = ds.split('-').map(Number);
        return d + ' ' + clpRoMonths[m] + ' ' + y;
    }

    function onMenuKey(e) { if (e.key === 'Escape') closeDayMenu(); }
    function onDocClickAway(e) {
        if (dayMenuEl && !dayMenuEl.contains(e.target) && !e.target.closest('.cal-cell--pick')) closeDayMenu();
    }

    function closeDayMenu() {
        if (dayMenuEl) { dayMenuEl.remove(); dayMenuEl = null; }
        document.removeEventListener('click', onDocClickAway, true);
        document.removeEventListener('keydown', onMenuKey);
    }

    function dayMenuInner(ds) {
        const active = igPosts[ds] || [];
        const opts = Object.keys(igPostTypes).map(t => {
            const on = active.includes(t);
            return `<button type="button" class="cal-daymenu-opt${on ? ' on' : ''}" data-type="${esc(t)}">
                <span class="cal-daymenu-check">${on ? '✓' : ''}</span>${esc(igPostTypes[t].label)}
            </button>`;
        }).join('');
        return `<div class="cal-daymenu-title">${esc(roDateLabel(ds))}</div>${opts}`;
    }

    function wireMenuOpts(ds) {
        dayMenuEl.querySelectorAll('.cal-daymenu-opt').forEach(btn => {
            btn.addEventListener('click', () => toggleIgPost(ds, btn.dataset.type));
        });
    }

    function openDayMenu(cell, ds) {
        const wasOpenFor = dayMenuEl && dayMenuEl.dataset.date === ds;
        closeDayMenu();
        if (wasOpenFor) return; // second click on same day closes it

        dayMenuEl = document.createElement('div');
        dayMenuEl.className = 'cal-daymenu';
        dayMenuEl.dataset.date = ds;
        dayMenuEl.innerHTML = dayMenuInner(ds);
        document.body.appendChild(dayMenuEl);
        wireMenuOpts(ds);

        const r = cell.getBoundingClientRect();
        let left = r.left + window.scrollX;
        if (left + dayMenuEl.offsetWidth > window.scrollX + document.documentElement.clientWidth - 8) {
            left = r.right + window.scrollX - dayMenuEl.offsetWidth;
        }
        dayMenuEl.style.top = (r.bottom + window.scrollY + 4) + 'px';
        dayMenuEl.style.left = Math.max(8, left) + 'px';

        setTimeout(() => {
            document.addEventListener('click', onDocClickAway, true);
            document.addEventListener('keydown', onMenuKey);
        }, 0);
    }

    async function toggleIgPost(ds, type) {
        const on = !(igPosts[ds] || []).includes(type);
        try {
            const res = await fetch('/api/instagram_posts.php', {
                method: 'POST',
                body: new URLSearchParams({ date: ds, type, on: on ? '1' : '0' }),
            });
            const data = await res.json();
            if (!data.ok) throw new Error(data.error || 'fail');
            if (data.types && data.types.length) igPosts[ds] = data.types;
            else delete igPosts[ds];
        } catch (err) {
            alert('Nu am putut salva. Încearcă din nou.');
            return;
        }
        if (dayMenuEl && dayMenuEl.dataset.date === ds) {
            dayMenuEl.innerHTML = dayMenuInner(ds);
            wireMenuOpts(ds);
        }
        calRender();
    }

    window.clpFilter = function () {
        const q = document.getElementById('clpSearch')?.value.toLowerCase() || '';
        document.querySelectorAll('#clpParticipantsTable tbody tr').forEach(tr => {
            tr.style.display = (tr.querySelector('strong')?.textContent.toLowerCase() || '').includes(q) ? '' : 'none';
        });
    };

    document.addEventListener('DOMContentLoaded', function () {
        clpLoadMonth();
        if (cfg.activeTab === 'participanti') clpLoadParticipants();
        if (cfg.initCalendar) calRender();
        if (cfg.scrollToStats) {
            document.getElementById('clp-stats-card')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });
})();
