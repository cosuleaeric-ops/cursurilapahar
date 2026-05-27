(function () {
    const cfg = window.CLP_STATS || {};
    const clpRoMonths = ['', 'ianuarie', 'februarie', 'martie', 'aprilie', 'mai', 'iunie', 'iulie', 'august', 'septembrie', 'octombrie', 'noiembrie', 'decembrie'];
    const calRoMonths = ['', 'Ianuarie', 'Februarie', 'Martie', 'Aprilie', 'Mai', 'Iunie', 'Iulie', 'August', 'Septembrie', 'Octombrie', 'Noiembrie', 'Decembrie'];
    const calDow = ['Lu', 'Ma', 'Mi', 'Jo', 'Vi', 'Sâ', 'Du'];
    const calCourses = cfg.calCourses || [];

    let clpYear = cfg.year || new Date().getFullYear();
    let clpMonth = cfg.month || (new Date().getMonth() + 1);
    let calYear = cfg.calYear || clpYear;
    let calMonth = cfg.calMonth || clpMonth;
    const calToday = new Date().toISOString().slice(0, 10);

    window.clpSwitchTab = function (e, t) {
        document.querySelectorAll('.clp-tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.clp-tab-panel').forEach(p => p.classList.remove('active'));
        document.getElementById('clp-panel-' + t).classList.add('active');
        e.currentTarget.classList.add('active');
        if (t === 'calendar') calRender();
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

    function esc(s) {
        return String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
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
        let html = sumInc > 0 ? `<div class="clp-summary-grid" style="margin-bottom:16px">
            <div class="clp-stat-box"><div class="lbl">Total încasări</div><div class="val">${fmtRON(sumInc)} <small style="font-size:14px;font-weight:400">RON</small></div></div>
            <div class="clp-stat-box"><div class="lbl">Taxă DITL (2%)</div><div class="val ditl">${fmtRON(sumInc * 0.02)} <small style="font-size:14px;font-weight:400">RON</small></div></div>
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
            const ditl = dr ? `<span class="clp-ditl-cell">${fmtRON(dr.total_incasari * 0.02)} RON</span>` : '<span style="color:#d1d5db">—</span>';
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
            if (subs.length) {
                html += `<tr class="clp-viza-row" id="${rid}"><td colspan="7" style="padding:0;background:#f8fafc">
                <div style="padding:6px 16px 12px 32px"><table style="width:100%;border-collapse:collapse;font-size:12px">
                <thead><tr>${['Seria', 'De la', 'Până la', 'Total', 'Tarif'].map(h => `<th style="padding:5px 10px;font-size:10px;font-weight:700;text-transform:uppercase;color:var(--text-muted);border-bottom:1px solid var(--border);text-align:${h === 'Seria' ? 'left' : 'right'}">${h}</th>`).join('')}</tr></thead>
                <tbody>${subs.map(s => `<tr>
                    <td style="padding:5px 10px;border-bottom:1px solid #f1f5f9"><span class="clp-seria">${esc(s.seria)}</span></td>
                    <td style="padding:5px 10px;text-align:right;border-bottom:1px solid #f1f5f9">${esc(s.de_la)}</td>
                    <td style="padding:5px 10px;text-align:right;border-bottom:1px solid #f1f5f9">${esc(s.pana_la)}</td>
                    <td style="padding:5px 10px;text-align:right;border-bottom:1px solid #f1f5f9">${s.nr_unitati}</td>
                    <td style="padding:5px 10px;text-align:right;border-bottom:1px solid #f1f5f9">${Number(s.tarif).toLocaleString('ro-RO', { maximumFractionDigits: 0 })} RON</td>
                </tr>`).join('')}</tbody>
                </table></div></td></tr>`;
            }
        });

        html += '</tbody></table>';
        cursPanel.innerHTML = html;
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
            html += `<div class="cal-cell${isToday ? ' today' : ''}">`;
            html += isToday ? `<div class="cal-day-num"><span class="cal-circle">${day}</span></div>`
                : `<div class="cal-day-num">${day}</div>`;
            (byDay[ds] || []).forEach(title => {
                const cls = isToday ? 'today-ev' : ds < calToday ? 'past' : 'future';
                html += `<div class="cal-event ${cls}" title="${esc(title)}">${esc(title)}</div>`;
            });
            html += '</div>';
        }
        const trailing = (7 - ((firstDow + daysInMonth) % 7)) % 7;
        for (let i = 0; i < trailing; i++) html += '<div class="cal-cell other-month"></div>';
        grid.innerHTML = html;
    }

    window.clpFilter = function () {
        const q = document.getElementById('clpSearch').value.toLowerCase();
        document.querySelectorAll('#clpParticipantsTable tbody tr').forEach(tr => {
            tr.style.display = (tr.querySelector('strong')?.textContent.toLowerCase() || '').includes(q) ? '' : 'none';
        });
    };

    if (cfg.initCalendar) {
        document.addEventListener('DOMContentLoaded', calRender);
    }
    if (cfg.scrollToStats) {
        document.addEventListener('DOMContentLoaded', function () {
            document.getElementById('clp-stats-card')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    }
})();
