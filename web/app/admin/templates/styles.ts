export const TPL_CSS = `
.tpl-intro { font-size:13px; color:var(--text-muted); margin-bottom:18px; }
.tpl-card { border:1px solid var(--border); border-radius:12px; background:var(--surface); margin-bottom:10px; overflow:hidden; transition:border-color .15s, box-shadow .15s; }
.tpl-card.open { border-color:var(--accent); box-shadow:0 2px 10px rgba(0,0,0,.05); }
.tpl-view { display:flex; align-items:center; gap:12px; padding:14px 16px; cursor:pointer; user-select:none; }
.tpl-view:hover { background:var(--accent-soft); }
.tpl-chevron { color:var(--text-muted); font-size:12px; transition:transform .15s; flex-shrink:0; }
.tpl-card.open .tpl-chevron { transform:rotate(90deg); }
.tpl-view-icon { font-size:20px; line-height:1; flex-shrink:0; }
.tpl-view-main { flex:1; min-width:0; }
.tpl-view-title { font-weight:600; font-size:14px; color:var(--text); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.tpl-view-preview { font-size:12.5px; color:var(--text-muted); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; margin-top:3px; }
.tpl-copy-btn { background:none; border:1px solid var(--border); border-radius:8px; padding:6px 11px; cursor:pointer; color:var(--text-muted); display:inline-flex; align-items:center; gap:6px; font-size:12px; font-weight:500; flex-shrink:0; transition:color .15s, border-color .15s, background .15s; }
.tpl-copy-btn:hover { color:#fff; background:var(--accent); border-color:var(--accent); }
.tpl-edit { padding:4px 16px 18px; border-top:1px solid var(--border); }
.tpl-lbl { display:block; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.04em; color:var(--text-muted); margin:16px 0 7px; }
.tpl-edit input, .tpl-edit textarea { width:100%; }
.tpl-edit textarea { font-family:inherit; resize:vertical; line-height:1.6; }
.tpl-edit-actions { display:flex; gap:8px; margin-top:16px; }
.tpl-edit-actions .tpl-del { margin-left:auto; }
`;
