import { redirect } from "next/navigation";
import { getSession } from "@/lib/auth";
import { CHELTUIALA_EMOJI, CHELTUIALA_EMOJI_KEYWORDS } from "./emoji";

export const dynamic = "force-dynamic";

// Port 1:1 din admin/statistici/pnl/index.php — același markup, același
// stylesheet (public/admin/statistici/style.css) și același script
// (public/admin/statistici/pnl/app.js), care vorbește cu /api/pnl.
export default async function PnlPage() {
  const session = await getSession();
  if (!session) redirect("/login");

  const cfg = JSON.stringify({
    csrf: "",
    api: "/api/pnl",
    cheltuialaEmoji: CHELTUIALA_EMOJI,
    cheltuialaEmojiKeywords: CHELTUIALA_EMOJI_KEYWORDS,
  });

  return (
    <>
      <link rel="stylesheet" href="/admin/statistici/style.css" />
      <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js" />
      <script dangerouslySetInnerHTML={{ __html: `window.PNL = ${cfg};` }} />

      <div style={{maxWidth: "1200px", margin: "0 auto"}}>

      <a href="/admin/" style={{fontSize: "12px", color: "var(--text-muted)", textDecoration: "none", display: "inline-block", marginBottom: "12px"}}>← Dashboard</a>

      <div style={{display: "flex", alignItems: "center", justifyContent: "space-between", marginBottom: "20px"}}>
          <h1 className="wp-page-title" style={{marginBottom: "0"}}>P&L Cursuri</h1>
          <div style={{display: "flex", alignItems: "center", gap: "10px"}}>
              <a href="/api/pnl/export" download style={{display: "inline-flex", alignItems: "center", gap: "6px", padding: "8px 14px", background: "#2271b1", color: "#fff", borderRadius: "6px", fontSize: "13px", fontWeight: "600", textDecoration: "none"}} title="Exportă toate datele pentru Claude">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                  Export
              </a>
              <button className="btn-hide" id="btnHide" title="Ascunde valorile">👁</button>
              <span className="last-entry-badge" id="lastEntryBadge"></span>
              <div style={{display: "flex", alignItems: "center", gap: "6px"}}>
                  <button className="nav-arrow" id="btnPrevMonth">‹</button>
                  <select className="year-select" id="yearSelect"></select>
                  <button className="nav-arrow" id="btnNextMonth">›</button>
              </div>
          </div>
      </div>

        {/* Quick Add Bar */}
        <div className="quick-add-bar">
          <button type="button" className="quick-add-btn quick-add-cheltuiala" id="topBtnCheltuiala">
            <span className="qab-icon">−</span>
            <span className="qab-text">Adaugă cheltuială</span>
          </button>
          <button type="button" className="quick-add-btn quick-add-venit" id="topBtnVenit">
            <span className="qab-icon">+</span>
            <span className="qab-text">Adaugă venit</span>
          </button>
        </div>

        {/* Stats */}
        <div className="stats-grid">
          <div className="stat-card accent-green">
            <div className="label">Venituri totale</div>
            <div className="value green" id="statVenituri">—</div>
            <div className="sub" id="statVenituriSub"></div>
          </div>
          <div className="stat-card accent-red">
            <div className="label">Cheltuieli totale</div>
            <div className="value red" id="statCheltuieli">—</div>
            <div className="sub" id="statCheltuieliSub"></div>
          </div>
          <div className="stat-card accent-gold">
            <div className="label">Profit net</div>
            <div className="value" id="statProfit">—</div>
            <div className="sub" id="statProfitSub"></div>
          </div>
          <div className="stat-card accent-blue">
            <div className="label">Marjă profit</div>
            <div className="value" id="statMarja">—</div>
            <div className="sub">din venituri</div>
          </div>
        </div>

        {/* Charts row */}
        <div className="chart-card" style={{marginBottom: "16px"}}>
          <h3>Venituri vs Cheltuieli</h3>
          <div className="chart-wrap">
            <canvas id="chartMonthly"></canvas>
          </div>
        </div>

        {/* Top Categories */}
        <div className="chart-card" id="topCatCard" style={{display: "none", marginBottom: "28px"}}>
          <h3>Top categorii</h3>
          <div id="topCatWrap" style={{position: "relative"}}>
            <canvas id="chartTopCat"></canvas>
          </div>
          <div className="chart-card-footer" id="topCatFooter" style={{display: "none"}}>
            <button type="button" className="chart-toggle-link" id="btnAllCategories">▼ Vezi toate</button>
          </div>
        </div>

        {/* Transactions */}
        <div className="tx-section">
        <div className="section-header">
          <h2>Tranzacții</h2>
          <div className="tab-group">
            <button className="tab-btn active" data-tab="toate">Toate</button>
            <button className="tab-btn" data-tab="venituri">Venituri</button>
            <button className="tab-btn" data-tab="cheltuieli">Cheltuieli</button>
          </div>
          <div className="add-btns">
            <button className="btn btn-green" id="btnAddVenit">+ Venit</button>
            <button className="btn btn-red"   id="btnAddCheltuiala">+ Cheltuiala</button>
          </div>
        </div>

        <div className="tx-cat-filters" id="txCatFilters" style={{display: "none"}}>
          <div className="tx-cat-filter-list" id="txCatFilterList"></div>
          <div className="tx-cat-filter-footer" id="txCatFilterFooter" style={{display: "none"}}>
            <button type="button" className="chart-toggle-link" id="btnTxCatToggle">▼ Vezi toate</button>
          </div>
        </div>

        <div className="table-card">
          <div className="table-scroll">
            <table className="tx-table">
              <thead>
                <tr>
                  <th className="col-date">Data</th>
                  <th>Categorie</th>
                  <th className="right col-sum">Sumă (lei)</th>
                  <th className="col-actions"></th>
                </tr>
              </thead>
              <tbody id="txBody"></tbody>
            </table>
          </div>
        </div>
        </div>{/* /tx-section */}

      {/* Modal: Adaugă / Editează Venit */}
      <div className="pnl-modal-overlay" id="modalVenit">
        <div className="pnl-modal">
          <button type="button" className="pnl-modal-close" data-close="modalVenit">×</button>
          <h2 id="modalVenitTitle">Adaugă venit</h2>
          <div className="error-msg" id="errorVenit"></div>
          <form id="formVenit">
            <input type="hidden" name="id" id="venitId" />
            <div className="form-group">
              <label>Data</label>
              <input type="date" name="data" id="venitData" required />
              <div className="date-nav">
                <button type="button" className="nav-arrow" id="venitDataPrev">‹</button>
                <button type="button" className="nav-arrow" id="venitDataNext">›</button>
              </div>
            </div>
            <div className="form-group">
              <label>Categorie</label>
              <div className="categorie-combobox">
                <input type="text" id="venitCategorie" autoComplete="off" placeholder="Scrie sau alege categoria..." />
                <button type="button" className="cat-combobox-arrow" tabIndex={-1} aria-label="Arată categoriile">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                </button>
                <div id="venitCategorieSuggestions" className="categorie-suggestions" hidden></div>
              </div>
            </div>
            <div className="form-group">
              <label>Sumă (lei)</label>
              <input type="number" name="suma" id="venitSuma" step="0.01" min="0.01" required />
            </div>
            <div className="pnl-modal-actions">
              <button type="button" className="btn btn-ghost" data-close="modalVenit">Anulează</button>
              <button type="submit" className="btn btn-green" id="venitSubmit">Salvează</button>
            </div>
          </form>
        </div>
      </div>

      {/* Modal: Adaugă / Editează Cheltuiala */}
      <div className="pnl-modal-overlay" id="modalCheltuiala">
        <div className="pnl-modal">
          <button type="button" className="pnl-modal-close" data-close="modalCheltuiala">×</button>
          <h2 id="modalCheltuialaTitle">Adaugă cheltuiala</h2>
          <div className="error-msg" id="errorCheltuiala"></div>
          <form id="formCheltuiala">
            <input type="hidden" name="id" id="cheltuialaId" />
            <div className="form-group">
              <label>Data</label>
              <input type="date" name="data" id="cheltuialaData" required />
              <div className="date-nav">
                <button type="button" className="nav-arrow" id="cheltuialaDataPrev">‹</button>
                <button type="button" className="nav-arrow" id="cheltuialaDataNext">›</button>
              </div>
            </div>
            <div className="form-group">
              <label>Categorie</label>
              <div className="categorie-combobox">
                <input type="text" id="cheltuialaCategorie" autoComplete="off" placeholder="Scrie sau alege categoria..." />
                <button type="button" className="cat-combobox-arrow" tabIndex={-1} aria-label="Arată categoriile">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                </button>
                <div id="cheltuialaCategorieSuggestions" className="categorie-suggestions" hidden></div>
              </div>
            </div>
            <div className="form-group">
              <label>Sumă (lei)</label>
              <input type="number" name="suma" id="cheltuialaSuma" step="0.01" min="0.01" required />
            </div>
            <div className="form-group" id="serviceFeeGroup">
              <label>Banca</label>
              <input type="number" id="cheltuialaServiceFee" step="0.01" min="0.01" placeholder="ex: 0,45" />
            </div>
            <div className="form-group">
              <label>Detalii</label>
              <input type="text" id="cheltuialaDetalii" autoComplete="off" />
            </div>
            <div className="pnl-modal-actions">
              <button type="button" className="btn btn-ghost" data-close="modalCheltuiala">Anulează</button>
              <button type="submit" className="btn btn-red" id="cheltuialaSubmit">Salvează</button>
            </div>
          </form>
        </div>
      </div>
      </div>{/* /max-width */}

      <script src="/admin/statistici/pnl/app.js" defer />
    </>
  );
}
