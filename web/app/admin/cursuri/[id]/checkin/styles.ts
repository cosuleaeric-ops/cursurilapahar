// Pagina se folosește pe telefon, la ușă: camera mare, rezultat citibil de la
// distanță, fără nimic altceva pe ecran.
export const CHECKIN_CSS = `
.checkin-count { font-size:14px; color:var(--muted); margin-bottom:16px; }
    .checkin-count strong { font-family:'Crimson Pro',Georgia,serif; font-size:24px; color:var(--text); }
    .scan-box { position:relative; aspect-ratio:1; background:#1a1a1a; border-radius:var(--radius-sm); overflow:hidden; display:flex; align-items:center; justify-content:center; }
    .scan-box video { width:100%; height:100%; object-fit:cover; display:none; }
    .scan-box.on video { display:block; }
    .scan-start { padding:12px 22px; font-size:15px; background:#fff; border:none; border-radius:10px; cursor:pointer; font-weight:600; }
    .scan-err { font-size:13px; color:#c0392b; margin:12px 0 0; }
    .scan-rez { margin-top:14px; padding:16px 18px; border-radius:var(--radius-sm); display:flex; flex-direction:column; gap:4px; }
    .scan-rez strong { font-size:20px; font-family:'Crimson Pro',Georgia,serif; }
    .scan-rez span { font-size:13px; opacity:.85; }
    .scan-rez.ok { background:var(--green-light); border:1px solid #b2d9c0; color:var(--green); }
    .scan-rez.nu { background:#fdecea; border:1px solid #f5c6c7; color:#a3282a; }
    .scan-manual { display:flex; gap:10px; align-items:flex-end; margin-top:20px; padding-top:16px; border-top:1px solid var(--border); }
    .scan-manual .f { display:flex; flex-direction:column; gap:3px; }
    .scan-manual label { font-size:11px; color:var(--muted); }
    .scan-manual input { padding:7px 9px; border:1px solid var(--border); border-radius:6px; font-size:14px; background:var(--bg); }
    .scan-manual button { padding:8px 16px; background:var(--accent); color:#fff; border:none; border-radius:6px; font-size:13px; cursor:pointer; }
`;
