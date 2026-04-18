<?php
declare(strict_types=1);
require __DIR__ . '/../../auth_check.php';
if (!is_authenticated()) { header('Location: /admin/'); exit; }
$csrf = csrf_token();
header('X-Robots-Tag: noindex, nofollow');
?>
<?php
$__page_title = 'P&L — Cursuri la Pahar';
include __DIR__ . '/../layout_header.php';
?>
<link rel="stylesheet" href="/admin/statistici/style.css?v=2">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    window.PNL = {
      csrf: <?php echo json_encode($csrf); ?>,
      api:  '/admin/statistici/pnl/api.php'
    };
</script>
<?php include __DIR__ . '/../layout_nav.php'; ?>

<div style="max-width:1200px;margin:0 auto">

<a href="/admin/statistici/" style="font-size:12px;color:var(--text-muted);text-decoration:none;display:inline-block;margin-bottom:12px">&larr; Statistici</a>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
    <h1 class="wp-page-title" style="margin-bottom:0">P&amp;L Cursuri</h1>
    <div style="display:flex;align-items:center;gap:10px">
        <button class="btn-hide" id="btnHide" title="Ascunde valorile">&#128065;</button>
        <span class="last-entry-badge" id="lastEntryBadge"></span>
        <div style="display:flex;align-items:center;gap:6px">
            <button class="nav-arrow" id="btnPrevMonth">&#8249;</button>
            <select class="year-select" id="yearSelect"></select>
            <button class="nav-arrow" id="btnNextMonth">&#8250;</button>
        </div>
    </div>
</div>

  <!-- Quick Add Bar -->
  <div class="quick-add-bar">
    <button class="quick-add-btn quick-add-cheltuiala" id="topBtnCheltuiala">
      <span class="qab-icon">&#8722;</span>
      <span class="qab-text">Adaug&#x103; cheltuial&#x103;</span>
    </button>
    <button class="quick-add-btn quick-add-venit" id="topBtnVenit">
      <span class="qab-icon">+</span>
      <span class="qab-text">Adaug&#x103; venit</span>
    </button>
  </div>

  <!-- Stats -->
  <div class="stats-grid">
    <div class="stat-card accent-green">
      <div class="label">Venituri totale</div>
      <div class="value green" id="statVenituri">—</div>
      <div class="sub" id="statVenituriSub"></div>
    </div>
    <div class="stat-card accent-red">
      <div class="label">Cheltuieli totale</div>
      <div class="value red" id="statCheltuieli">—</div>
      <div class="sub" id="statCheltuieliSub"></div>
    </div>
    <div class="stat-card accent-gold">
      <div class="label">Profit net</div>
      <div class="value" id="statProfit">—</div>
      <div class="sub" id="statProfitSub"></div>
    </div>
    <div class="stat-card accent-blue">
      <div class="label">Marj&#x103; profit</div>
      <div class="value" id="statMarja">—</div>
      <div class="sub">din venituri</div>
    </div>
  </div>

  <!-- Charts row -->
  <div class="charts-row">
    <div class="chart-card">
      <h3>Venituri vs Cheltuieli</h3>
      <div class="chart-wrap">
        <canvas id="chartMonthly"></canvas>
      </div>
    </div>
    <div class="chart-card">
      <h3>Structura cheltuielilor</h3>
      <div class="chart-wrap-donut">
        <canvas id="chartDonut"></canvas>
      </div>
    </div>
  </div>

  <!-- Cumulative chart -->
  <div class="chart-card cumulative-card">
    <h3>Profit cumulativ</h3>
    <div class="cumulative-wrap">
      <canvas id="chartCumulative"></canvas>
    </div>
  </div>

  <!-- Top Categories -->
  <div class="chart-card" id="topCatCard" style="display:none;margin-bottom:28px">
    <h3>Top categorii</h3>
    <div id="topCatWrap" style="position:relative">
      <canvas id="chartTopCat"></canvas>
    </div>
  </div>

  <!-- Transactions -->
  <div class="tx-section">
  <div class="section-header">
    <h2>Tranzac&#x21B;ii</h2>
    <div class="tab-group">
      <button class="tab-btn active" data-tab="toate">Toate</button>
      <button class="tab-btn" data-tab="venituri">Venituri</button>
      <button class="tab-btn" data-tab="cheltuieli">Cheltuieli</button>
    </div>
    <div class="add-btns">
      <button class="btn btn-green" id="btnAddVenit">+ Venit</button>
      <button class="btn btn-red"   id="btnAddCheltuiala">+ Cheltuiala</button>
    </div>
  </div>

  <div class="table-card">
    <div class="table-scroll">
      <table>
        <thead>
          <tr>
            <th style="width:110px">Data</th>
            <th>Categorie</th>
            <th class="right">Sum&#x103; (lei)</th>
            <th style="width:80px"></th>
          </tr>
        </thead>
        <tbody id="txBody"></tbody>
      </table>
    </div>
  </div>
  </div><!-- /tx-section -->

<!-- Modal: Adaug&#x103; / Editeaz&#x103; Venit -->
<div class="modal-overlay" id="modalVenit">
  <div class="modal">
    <button class="modal-close" data-close="modalVenit">&times;</button>
    <h2 id="modalVenitTitle">Adaug&#x103; venit</h2>
    <div class="error-msg" id="errorVenit"></div>
    <form id="formVenit">
      <input type="hidden" name="id" id="venitId" />
      <div class="form-group">
        <label>Data</label>
        <input type="date" name="data" id="venitData" required />
        <div class="date-nav">
          <button type="button" class="nav-arrow" id="venitDataPrev">&#8249;</button>
          <button type="button" class="nav-arrow" id="venitDataNext">&#8250;</button>
        </div>
      </div>
      <div class="form-group">
        <label>Categorie</label>
        <select id="venitCategorieSelect"></select>
        <input type="text" id="venitCategorieNoua" placeholder="Nume categorie nou&#x103;"
               style="display:none; margin-top:8px; width:100%; padding:10px 12px; border:1px solid var(--border); border-radius:var(--radius-sm); font-size:14px; background:var(--bg);" />
      </div>
      <div class="form-group">
        <label>Sum&#x103; (lei)</label>
        <input type="number" name="suma" id="venitSuma" step="0.01" min="0.01" required />
      </div>
      <div class="modal-actions">
        <button type="button" class="btn btn-ghost" data-close="modalVenit">Anuleaz&#x103;</button>
        <button type="submit" class="btn btn-green" id="venitSubmit">Salveaz&#x103;</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal: Adaug&#x103; / Editeaz&#x103; Cheltuiala -->
<div class="modal-overlay" id="modalCheltuiala">
  <div class="modal">
    <button class="modal-close" data-close="modalCheltuiala">&times;</button>
    <h2 id="modalCheltuialaTitle">Adaug&#x103; cheltuiala</h2>
    <div class="error-msg" id="errorCheltuiala"></div>
    <form id="formCheltuiala">
      <input type="hidden" name="id" id="cheltuialaId" />
      <div class="form-group">
        <label>Data</label>
        <input type="date" name="data" id="cheltuialaData" required />
        <div class="date-nav">
          <button type="button" class="nav-arrow" id="cheltuialaDataPrev">&#8249;</button>
          <button type="button" class="nav-arrow" id="cheltuialaDataNext">&#8250;</button>
        </div>
      </div>
      <div class="form-group">
        <label>Categorie</label>
        <select id="cheltuialaCategorieSelect"></select>
        <input type="text" id="cheltuialaCategorieNoua" placeholder="Nume categorie nou&#x103;"
               style="display:none; margin-top:8px; width:100%; padding:10px 12px; border:1px solid var(--border); border-radius:var(--radius-sm); font-size:14px; background:var(--bg);" />
      </div>
      <div class="form-group">
        <label>Sum&#x103; (lei)</label>
        <input type="number" name="suma" id="cheltuialaSuma" step="0.01" min="0.01" required />
      </div>
      <div class="form-group" id="serviceFeeGroup">
        <label>Service fee</label>
        <input type="number" id="cheltuialaServiceFee" step="0.01" min="0.01" placeholder="ex: 0,45" />
      </div>
      <div class="modal-actions">
        <button type="button" class="btn btn-ghost" data-close="modalCheltuiala">Anuleaz&#x103;</button>
        <button type="submit" class="btn btn-red" id="cheltuialaSubmit">Salveaz&#x103;</button>
      </div>
    </form>
  </div>
</div>

<script>
// ── Helpers ─────────────────────────────────────────────────────────────────
const api = (action, params = '') =>
  fetch(`${window.PNL.api}?action=${action}${params ? '&' + params : ''}`).then(r => r.json());

const post = (action, body) => {
  body.csrf_token = window.PNL.csrf;
  return fetch(`${window.PNL.api}?action=${action}`, {
    method: 'POST',
    body: new URLSearchParams(body),
  }).then(r => r.json());
};

const fmt = n => new Intl.NumberFormat('ro-RO', {
  minimumFractionDigits: 2, maximumFractionDigits: 2
}).format(n);

const fmtDate = s => {
  if (!s) return '';
  const [y, m, d] = s.split('-');
  return `${d}.${m}.${y}`;
};

const monthLabel = s => {
  if (!s) return '';
  const months = ['ian','feb','mar','apr','mai','iun','iul','aug','sep','oct','nov','dec'];
  const [, m] = s.split('-');
  return months[parseInt(m) - 1];
};

// ── State ────────────────────────────────────────────────────────────────────
let currentYear  = new Date().getFullYear();
let currentMonth = new Date().getMonth() + 1; // luna curenta
let currentTab   = 'toate';
let allVenituri = [];
let allCheltuieli = [];
let chartMonthly, chartDonut, chartCumulative, chartTopCat;
let lastStats = null;
let amountsHidden = false;
const rowStore = new Map(); // 'venit-id' / 'cheltuiala-id' → row object
const lastDateKey = 'pnl_last_date';
const getLastDate = () => localStorage.getItem(lastDateKey) || todayStr();
const setLastDate = d => localStorage.setItem(lastDateKey, d);

// ── Categories ───────────────────────────────────────────────────────────────
async function loadCategories() {
  const [vc, cc] = await Promise.all([
    api('categorii_venituri'),
    api('categorii_cheltuieli'),
  ]);
  populateSelect('venitCategorieSelect',      vc, 'add_categorie_venit');
  populateSelect('cheltuialaCategorieSelect', cc, 'add_categorie_cheltuiala');
}

function populateSelect(selectId, cats, addAction) {
  const sel = document.getElementById(selectId);
  const current = sel.value;
  sel.innerHTML = '';

  cats.forEach(c => {
    const opt = document.createElement('option');
    opt.value = c; opt.textContent = c;
    sel.appendChild(opt);
  });

  const newOpt = document.createElement('option');
  newOpt.value = '__new__';
  newOpt.textContent = '+ Categorie nouă...';
  sel.appendChild(newOpt);

  if (current && current !== '__new__') sel.value = current;

  // Wire up the "new category" input toggle
  const inputId = selectId.replace('Select', 'Noua');
  const inp = document.getElementById(inputId);
  sel.onchange = () => {
    inp.style.display = sel.value === '__new__' ? 'block' : 'none';
    if (sel.value === '__new__') inp.focus();
  };
}

async function resolveCategorie(selectId, inputId, addAction) {
  const sel = document.getElementById(selectId);
  const inp = document.getElementById(inputId);

  if (sel.value === '__new__') {
    const nome = inp.value.trim();
    if (!nome) return null;
    await post(addAction, { nume: nome });
    await loadCategories();
    document.getElementById(selectId).value = nome;
    return nome;
  }
  return sel.value || null;
}

// ── Last entry badge ─────────────────────────────────────────────────────────
async function loadLastEntry() {
  const res = await api('last_entry');
  if (!res || !res.data) return;

  const badge  = document.getElementById('lastEntryBadge');
  const parts  = res.data.split('-');
  const entryDt = new Date(parseInt(parts[0]), parseInt(parts[1]) - 1, parseInt(parts[2]));
  const today  = new Date(); today.setHours(0,0,0,0);
  const diffZ  = Math.round((today - entryDt) / 86400000);

  let when;
  if (diffZ === 0)      when = 'azi';
  else if (diffZ === 1) when = 'ieri';
  else                  when = `acum ${diffZ} zile`;

  badge.textContent = `Ultima cheltuială: ${fmtDate(res.data)} (${when})`;
}

// ── Init ─────────────────────────────────────────────────────────────────────
async function init() {
  const periods = await api('periods');
  const sel = document.getElementById('yearSelect');

  periods.forEach(p => {
    const opt = document.createElement('option');
    opt.value = p.value;
    opt.textContent = p.month ? '\u00A0\u00A0' + p.label : p.label;
    if (p.month) opt.style.color = 'var(--muted)';
    sel.appendChild(opt);
  });

  // Default to current year-month
  sel.value = currentYear + '-' + currentMonth;
  if (!sel.value) sel.value = String(currentYear);
  if (!sel.value && periods.length) sel.value = periods[0].value;

  sel.addEventListener('change', () => {
    const parts = sel.value.split('-');
    currentYear  = parseInt(parts[0]);
    currentMonth = parts[1] ? parseInt(parts[1]) : null;
    refresh();
  });

  await loadCategories();
  await Promise.all([refresh(), loadLastEntry()]);
}

async function refresh() {
  const mParam = currentMonth ? `&month=${currentMonth}` : '';
  const [stats, venituri, cheltuieli] = await Promise.all([
    api('stats',      `year=${currentYear}${mParam}`),
    api('venituri',   `year=${currentYear}${mParam}`),
    api('cheltuieli', `year=${currentYear}${mParam}`),
  ]);

  allVenituri   = venituri;
  allCheltuieli = cheltuieli;

  renderStats(stats);
  renderCharts(stats);
  renderTable();
}

// ── Stats ────────────────────────────────────────────────────────────────────
function renderStats(s) {
  lastStats = s;
  const mask = '• • •';
  const profitColor = s.profit_net >= 0 ? 'green' : 'red';
  const marjaColor  = s.marja >= 0 ? 'green' : 'red';

  document.getElementById('statVenituri').textContent   = amountsHidden ? mask : fmt(s.total_venituri) + ' lei';
  document.getElementById('statCheltuieli').textContent = amountsHidden ? mask : fmt(s.total_cheltuieli) + ' lei';

  const profitEl = document.getElementById('statProfit');
  profitEl.textContent = amountsHidden ? mask : (s.profit_net >= 0 ? '+' : '') + fmt(s.profit_net) + ' lei';
  profitEl.className = 'value ' + profitColor;

  const marjaEl = document.getElementById('statMarja');
  marjaEl.textContent = amountsHidden ? mask : (s.marja >= 0 ? '+' : '') + s.marja + '%';
  marjaEl.className = 'value ' + marjaColor;

  document.getElementById('statVenituriSub').textContent =
    `${allVenituri.length} tranzacții`;
  document.getElementById('statCheltuieliSub').textContent =
    `${allCheltuieli.length} tranzacții`;
  document.getElementById('statProfitSub').textContent = '';
}

// ── Charts ───────────────────────────────────────────────────────────────────
const CAT_COLORS = [
  '#4A90D9','#E8704A','#2A7D4F','#C1444A','#7B5EA7',
  '#D4A017','#E8A87C','#85C1E9','#A9DFBF','#F1948A',
];

function renderCharts(s) {
  const labels = s.monthly.map(m => currentMonth ? m.luna.slice(8) + '.' : monthLabel(m.luna));

  if (chartMonthly) chartMonthly.destroy();
  chartMonthly = new Chart(document.getElementById('chartMonthly'), {
    type: 'bar',
    data: {
      labels,
      datasets: [
        {
          label: 'Venituri',
          data: s.monthly.map(m => m.venituri),
          backgroundColor: 'rgba(42,125,79,0.8)',
          borderRadius: 5,
          borderSkipped: false,
        },
        {
          label: 'Cheltuieli',
          data: s.monthly.map(m => m.cheltuieli),
          backgroundColor: 'rgba(193,68,74,0.75)',
          borderRadius: 5,
          borderSkipped: false,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 12 } } },
        tooltip: { callbacks: { label: ctx => ` ${fmt(ctx.parsed.y)} lei` } },
      },
      scales: {
        y: {
          beginAtZero: true,
          grid: { color: '#e0e0e0' },
          ticks: { callback: v => fmt(v) + ' lei', font: { size: 11 } },
        },
        x: { grid: { display: false }, ticks: { font: { size: 12 } } },
      },
    },
  });

  if (chartDonut) chartDonut.destroy();
  if (s.categorii_cheltuieli.length) {
    chartDonut = new Chart(document.getElementById('chartDonut'), {
      type: 'doughnut',
      data: {
        labels: s.categorii_cheltuieli.map(c => c.categorie),
        datasets: [{
          data: s.categorii_cheltuieli.map(c => c.suma),
          backgroundColor: CAT_COLORS.slice(0, s.categorii_cheltuieli.length),
          borderWidth: 2,
          borderColor: '#fff',
        }],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '62%',
        plugins: {
          legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 }, padding: 8 } },
          tooltip: { callbacks: { label: ctx => ` ${fmt(ctx.parsed)} lei` } },
        },
      },
    });
  }

  if (chartTopCat) chartTopCat.destroy();
  const topCatCard = document.getElementById('topCatCard');
  if (s.categorii_cheltuieli.length) {
    topCatCard.style.display = '';
    const topData = s.categorii_cheltuieli.slice(0, 10);
    const topCatWrap = document.getElementById('topCatWrap');
    topCatWrap.style.height = (topData.length * 40 + 40) + 'px';
    chartTopCat = new Chart(document.getElementById('chartTopCat'), {
      type: 'bar',
      data: {
        labels: topData.map(c => c.categorie),
        datasets: [{ data: topData.map(c => c.suma), backgroundColor: CAT_COLORS.slice(0, topData.length) }],
      },
      options: {
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: { callbacks: { label: ctx => ` ${fmt(ctx.parsed.x)} lei` } },
        },
        scales: {
          x: { beginAtZero: true, grid: { color: '#e0e0e0' }, ticks: { callback: v => fmt(v) + ' lei', font: { size: 11 } } },
          y: { grid: { display: false }, ticks: { font: { size: 12 } } },
        },
      },
    });
  } else {
    topCatCard.style.display = 'none';
  }

  if (chartCumulative) chartCumulative.destroy();
  chartCumulative = new Chart(document.getElementById('chartCumulative'), {
    type: 'line',
    data: {
      labels,
      datasets: [{
        label: 'Profit cumulativ',
        data: s.monthly.map(m => m.cumulative),
        borderColor: '#4A90D9',
        backgroundColor: 'rgba(74,144,217,0.1)',
        borderWidth: 2.5,
        fill: true,
        tension: 0.35,
        pointBackgroundColor: s.monthly.map(m => m.cumulative >= 0 ? '#2A7D4F' : '#C1444A'),
        pointRadius: 5,
      }],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: { callbacks: { label: ctx => ` ${fmt(ctx.parsed.y)} lei` } },
      },
      scales: {
        y: {
          grid: { color: '#e0e0e0' },
          ticks: { callback: v => fmt(v) + ' lei', font: { size: 11 } },
        },
        x: { grid: { display: false }, ticks: { font: { size: 12 } } },
      },
    },
  });
}

// ── Table ────────────────────────────────────────────────────────────────────
function renderTable() {
  const body = document.getElementById('txBody');
  body.innerHTML = '';
  body.closest('table').classList.toggle('amounts-hidden', amountsHidden);

  let rows = [];
  if (currentTab === 'toate') {
    const v = allVenituri.map(r   => ({ ...r, _type: 'venit' }));
    const c = allCheltuieli.map(r => ({ ...r, _type: 'cheltuiala' }));
    rows = [...v, ...c].sort((a, b) => b.data.localeCompare(a.data) || b.id - a.id);
  } else if (currentTab === 'venituri') {
    rows = allVenituri.map(r => ({ ...r, _type: 'venit' }));
  } else {
    rows = allCheltuieli.map(r => ({ ...r, _type: 'cheltuiala' }));
  }

  if (!rows.length) {
    const periodLabel = document.getElementById('yearSelect').options[document.getElementById('yearSelect').selectedIndex]?.textContent.trim() || currentYear;
    body.innerHTML = `<tr><td colspan="4"><div class="empty-state">Nicio tranzacție în ${periodLabel}</div></td></tr>`;
    return;
  }

  rows.forEach(r => {
    const isVenit = r._type === 'venit';
    const key = `${r._type}-${r.id}`;
    rowStore.set(key, r);

    const cat = isVenit ? r.descriere : r.categorie;

    let catHtml;
    if (currentTab === 'toate') {
      const dot = isVenit
        ? '<span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:#2A7D4F;margin-right:7px;vertical-align:middle"></span>'
        : '<span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:#C1444A;margin-right:7px;vertical-align:middle"></span>';
      catHtml = dot + esc(cat);
    } else {
      catHtml = esc(cat);
    }

    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td>${fmtDate(r.data)}</td>
      <td>${catHtml}</td>
      <td class="right ${isVenit ? 'suma-green' : 'suma-red'}">
        ${isVenit ? '+' : '−'} ${fmt(r.suma)}
      </td>
      <td>
        <div class="actions-cell">
          <button class="icon-btn" title="Editează"
            data-key="${key}">✎</button>
          <button class="icon-btn danger" title="Șterge"
            data-type="${r._type}" data-id="${r.id}">✕</button>
        </div>
      </td>`;
    body.appendChild(tr);
  });
}

function esc(s) {
  return String(s)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

// ── Tabs ─────────────────────────────────────────────────────────────────────
document.querySelectorAll('.tab-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    currentTab = btn.dataset.tab;
    renderTable();
  });
});

// ── Modals ────────────────────────────────────────────────────────────────────
function openModal(id)  { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

document.querySelectorAll('[data-close]').forEach(el => {
  el.addEventListener('click', () => closeModal(el.dataset.close));
});

document.querySelectorAll('.modal-overlay').forEach(overlay => {
  overlay.addEventListener('click', e => {
    if (e.target === overlay) closeModal(overlay.id);
  });
});

// ── Add buttons ───────────────────────────────────────────────────────────────
document.getElementById('btnAddVenit').addEventListener('click', () => {
  document.getElementById('modalVenitTitle').textContent = 'Adaugă venit';
  document.getElementById('venitSubmit').textContent = 'Adaugă';
  document.getElementById('formVenit').reset();
  document.getElementById('venitId').value = '';
  document.getElementById('venitData').value = getLastDate();
  document.getElementById('venitCategorieNoua').style.display = 'none';
  document.getElementById('errorVenit').style.display = 'none';
  openModal('modalVenit');
});

document.getElementById('btnAddCheltuiala').addEventListener('click', () => {
  document.getElementById('modalCheltuialaTitle').textContent = 'Adaugă cheltuiala';
  document.getElementById('cheltuialaSubmit').textContent = 'Adaugă';
  document.getElementById('formCheltuiala').reset();
  document.getElementById('cheltuialaId').value = '';
  document.getElementById('cheltuialaData').value = getLastDate();
  document.getElementById('cheltuialaCategorieNoua').style.display = 'none';
  document.getElementById('cheltuialaServiceFee').value = '';
  document.getElementById('serviceFeeGroup').style.display = '';
  document.getElementById('errorCheltuiala').style.display = 'none';
  openModal('modalCheltuiala');
});

function todayStr() {
  return new Date().toISOString().split('T')[0];
}

// ── Edit ──────────────────────────────────────────────────────────────────────
function openEdit(row) {
  if (row._type === 'venit') {
    document.getElementById('modalVenitTitle').textContent = 'Editează venit';
    document.getElementById('venitSubmit').textContent = 'Salvează';
    document.getElementById('venitId').value   = row.id;
    document.getElementById('venitData').value = row.data;
    document.getElementById('venitSuma').value = row.suma;
    document.getElementById('venitCategorieNoua').style.display = 'none';
    document.getElementById('errorVenit').style.display = 'none';
    document.getElementById('venitCategorieSelect').value = row.descriere;
    openModal('modalVenit');
  } else {
    document.getElementById('modalCheltuialaTitle').textContent = 'Editează cheltuiala';
    document.getElementById('cheltuialaSubmit').textContent = 'Salvează';
    document.getElementById('cheltuialaId').value   = row.id;
    document.getElementById('cheltuialaData').value = row.data;
    document.getElementById('cheltuialaSuma').value = row.suma;
    document.getElementById('cheltuialaCategorieNoua').style.display = 'none';
    document.getElementById('cheltuialaServiceFee').value = '';
    document.getElementById('serviceFeeGroup').style.display = 'none';
    document.getElementById('errorCheltuiala').style.display = 'none';
    document.getElementById('cheltuialaCategorieSelect').value = row.categorie;
    openModal('modalCheltuiala');
  }
}

// ── Table click delegation ────────────────────────────────────────────────────
document.getElementById('txBody').addEventListener('click', async e => {
  const editBtn  = e.target.closest('[data-key]');
  const deleteBtn = e.target.closest('[data-type][data-id]');

  if (editBtn) {
    const row = rowStore.get(editBtn.dataset.key);
    if (row) openEdit(row);
  } else if (deleteBtn) {
    const { type, id } = deleteBtn.dataset;
    if (!confirm('Ștergi această tranzacție?')) return;
    const action = type === 'venit' ? 'delete_venit' : 'delete_cheltuiala';
    const res = await post(action, { id });
    if (res.success) refresh();
    else alert(res.error || 'Eroare la ștergere');
  }
});

// ── Form submit: Venit ────────────────────────────────────────────────────────
document.getElementById('formVenit').addEventListener('submit', async e => {
  e.preventDefault();
  const errEl = document.getElementById('errorVenit');
  errEl.style.display = 'none';

  const categorie = await resolveCategorie('venitCategorieSelect', 'venitCategorieNoua', 'add_categorie_venit');
  if (!categorie) {
    errEl.textContent = 'Selectează sau creează o categorie.';
    errEl.style.display = 'block';
    return;
  }

  const id = document.getElementById('venitId').value;
  const body = {
    data:      document.getElementById('venitData').value,
    categorie,
    suma:      document.getElementById('venitSuma').value,
  };
  if (id) body.id = id;

  const res = await post(id ? 'edit_venit' : 'add_venit', body);

  if (res.success || res.id) {
    if (id) {
      closeModal('modalVenit');
    } else {
      setLastDate(body.data);
      document.getElementById('venitSuma').value = '';
      document.getElementById('venitSuma').focus();
    }
    refresh();
  } else {
    errEl.textContent = res.error || 'Eroare';
    errEl.style.display = 'block';
  }
});

// ── Form submit: Cheltuiala ───────────────────────────────────────────────────
document.getElementById('formCheltuiala').addEventListener('submit', async e => {
  e.preventDefault();
  const errEl = document.getElementById('errorCheltuiala');
  errEl.style.display = 'none';

  const categorie = await resolveCategorie('cheltuialaCategorieSelect', 'cheltuialaCategorieNoua', 'add_categorie_cheltuiala');
  if (!categorie) {
    errEl.textContent = 'Selectează sau creează o categorie.';
    errEl.style.display = 'block';
    return;
  }

  const id = document.getElementById('cheltuialaId').value;
  const body = {
    data:      document.getElementById('cheltuialaData').value,
    categorie,
    suma:      document.getElementById('cheltuialaSuma').value,
  };
  if (id) body.id = id;

  const res = await post(id ? 'edit_cheltuiala' : 'add_cheltuiala', body);

  if (res.success || res.id) {
    if (id) {
      closeModal('modalCheltuiala');
    } else {
      setLastDate(body.data);
      const fee = parseFloat(document.getElementById('cheltuialaServiceFee').value);
      if (fee > 0) {
        await post('add_cheltuiala', { data: body.data, categorie: 'Service fee', suma: fee });
      }
      document.getElementById('cheltuialaSuma').value = '';
      document.getElementById('cheltuialaServiceFee').value = '';
      document.getElementById('cheltuialaSuma').focus();
    }
    refresh();
  } else {
    errEl.textContent = res.error || 'Eroare';
    errEl.style.display = 'block';
  }
});

// ── Quick Add Bar ─────────────────────────────────────────────────────────────
document.getElementById('topBtnCheltuiala').addEventListener('click', () => {
  document.getElementById('btnAddCheltuiala').click();
});
document.getElementById('topBtnVenit').addEventListener('click', () => {
  document.getElementById('btnAddVenit').click();
});

// ── Keyboard shortcuts (C = cheltuiala, V = venit) ───────────────────────────
document.addEventListener('keydown', e => {
  if (['INPUT','SELECT','TEXTAREA'].includes(e.target.tagName)) return;
  if (document.querySelector('.modal-overlay.open')) return;
  if (e.key === 'c' || e.key === 'C') document.getElementById('btnAddCheltuiala').click();
  if (e.key === 'v' || e.key === 'V') document.getElementById('btnAddVenit').click();
});

// ── Hide amounts toggle ───────────────────────────────────────────────────────
document.getElementById('btnHide').addEventListener('click', () => {
  amountsHidden = !amountsHidden;
  document.getElementById('btnHide').textContent = amountsHidden ? '🙈' : '👁';
  if (lastStats) renderStats(lastStats);
  renderTable();
});

// ── Month navigation arrows ───────────────────────────────────────────────────
function navigateMonth(delta) {
  let m = currentMonth || 1;
  let y = currentYear;
  m += delta;
  if (m < 1) { m = 12; y--; }
  if (m > 12) { m = 1;  y++; }
  const val = `${y}-${m}`;
  const sel = document.getElementById('yearSelect');
  const opt = Array.from(sel.options).find(o => o.value === val);
  if (opt) { sel.value = val; sel.dispatchEvent(new Event('change')); }
}
document.getElementById('btnPrevMonth').addEventListener('click', () => navigateMonth(-1));
document.getElementById('btnNextMonth').addEventListener('click', () => navigateMonth(+1));

// ── Date navigation arrows in modals ─────────────────────────────────────────
function shiftDate(inputId, delta) {
  const inp = document.getElementById(inputId);
  const d = new Date(inp.value || todayStr());
  d.setDate(d.getDate() + delta);
  inp.value = d.toISOString().split('T')[0];
}
document.getElementById('cheltuialaDataPrev').addEventListener('click', () => shiftDate('cheltuialaData', -1));
document.getElementById('cheltuialaDataNext').addEventListener('click', () => shiftDate('cheltuialaData', +1));
document.getElementById('venitDataPrev').addEventListener('click', () => shiftDate('venitData', -1));
document.getElementById('venitDataNext').addEventListener('click', () => shiftDate('venitData', +1));

// ── Boot ──────────────────────────────────────────────────────────────────────
init();
</script>

    </div><!-- /max-width -->
    </main>
</div>
</body>
</html>
