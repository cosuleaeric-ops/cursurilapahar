// Continuă stilul din statistici/cursuri/view.php (.section-card, .subtip-table).
export const BILETE_CSS = `
.bilete-table { width:100%; border-collapse:collapse; font-size:14px; margin-bottom:20px; }
    .bilete-table th { font-size:11px; font-weight:700; letter-spacing:.5px; text-transform:uppercase; color:var(--muted); padding:6px 8px; text-align:left; border-bottom:1px solid var(--border); }
    .bilete-table th.num, .bilete-table td.num { text-align:right; font-variant-numeric:tabular-nums; }
    .bilete-table td { padding:10px 8px; border-bottom:1px solid var(--border); vertical-align:top; }
    .bilete-table tr:last-child td { border-bottom:none; }
    .serie-range { font-family:monospace; font-size:11px; color:var(--muted); margin-top:4px; }
    .casate-tag { display:block; font-size:11px; color:var(--muted); margin-top:3px; }
    .row-form { display:flex; gap:5px; align-items:center; margin:0; flex-wrap:wrap; }
    .row-form input { padding:4px 7px; border:1px solid var(--border); border-radius:6px; font-size:13px; background:var(--bg); }
    .row-form .in-name { width:150px; }
    .row-form .in-num { width:66px; text-align:right; font-variant-numeric:tabular-nums; }
    .mini-btn { font-size:11px; padding:4px 9px; border:1px solid var(--border); background:var(--bg); border-radius:6px; cursor:pointer; color:var(--muted); }
    .mini-btn:hover { border-color:var(--green); color:var(--green); }
    .x-btn { background:none; border:none; cursor:pointer; color:var(--muted); font-size:15px; padding:2px 6px; }
    .x-btn:hover { color:#c0392b; }
    .add-form { display:flex; gap:10px; flex-wrap:wrap; align-items:flex-end; }
    .add-form .f { display:flex; flex-direction:column; gap:3px; }
    .add-form label { font-size:11px; color:var(--muted); }
    .add-form input { padding:6px 9px; border:1px solid var(--border); border-radius:6px; font-size:13px; background:var(--bg); }
    .add-btn { padding:7px 15px; background:var(--accent); color:#fff; border:none; border-radius:6px; font-size:13px; cursor:pointer; }
    .hint { font-size:12px; color:var(--muted); margin-top:12px; line-height:1.5; }
    .doc-list { display:flex; flex-direction:column; gap:8px; }
    .doc { display:flex; flex-direction:column; gap:2px; padding:12px 14px; border:1px solid var(--border); border-radius:var(--radius-sm); background:var(--bg); text-decoration:none; }
    .doc:hover { border-color:var(--green); background:var(--green-light); }
    .doc strong { font-size:14px; color:var(--text); font-weight:600; }
    .doc span { font-size:12px; color:var(--muted); }
    .doc-actions { display:flex; gap:10px; flex-wrap:wrap; margin-top:16px; padding-top:16px; border-top:1px solid var(--border); }
    .state-btn { font-size:12px; padding:6px 13px; border:1px solid var(--border); background:var(--bg); border-radius:6px; cursor:pointer; color:var(--muted); }
    .state-btn:hover { border-color:var(--green); color:var(--green); }
    .state-btn.on { border-color:#b2d9c0; background:var(--green-light); color:var(--green); font-weight:600; }
    @media(max-width:700px) { .bilete-table { display:block; overflow-x:auto; } }
`;
export const COD_CSS = `
.cod-tag { display:inline-block; margin-top:5px; font-size:11px; font-weight:600; color:var(--accent); background:var(--bg); border:1px solid var(--border); border-radius:10px; padding:2px 8px; }
`;
