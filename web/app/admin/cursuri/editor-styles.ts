// Editorul de curs: cuprins fix în stânga, secțiuni în dreapta, bară de salvare
// lipită jos. Aceleași variabile ca restul adminului.
export const EDITOR_CSS = `
.ce { display:grid; grid-template-columns:240px 1fr; gap:34px; max-width:1080px; margin:0 auto; padding-bottom:90px; align-items:start; }
    .ce-nav { position:sticky; top:24px; }
    .ce-nav-title { font-size:11px; font-weight:700; letter-spacing:.7px; text-transform:uppercase; color:var(--muted); margin-bottom:14px; }
    .ce-nav ol { list-style:none; margin:0; padding:0; display:flex; flex-direction:column; gap:2px; }
    .ce-nav a { display:flex; gap:11px; padding:10px 12px; border-radius:10px; text-decoration:none; color:var(--text); border:1px solid transparent; }
    .ce-nav a:hover { background:var(--bg); }
    .ce-nav a.on { background:var(--green-light); border-color:#b2d9c0; }
    .ce-nav a strong { display:block; font-size:13px; font-weight:600; }
    .ce-nav a em { display:block; font-style:normal; font-size:11px; color:var(--muted); margin-top:1px; }
    .ce-nr { font-family:ui-monospace,Menlo,monospace; font-size:11px; font-weight:700; color:var(--muted); padding-top:2px; }
    .ce-sec { background:var(--card); border:1px solid var(--border); border-radius:var(--radius); padding:26px 28px; margin-bottom:18px; scroll-margin-top:20px; }
    .ce-sec h2 { display:flex; align-items:baseline; gap:10px; font-family:'Crimson Pro',Georgia,serif; font-size:20px; font-weight:600; margin:0 0 20px; }
    .ce-sec h2 .ce-nr { font-size:12px; }
    .ce-field { display:flex; flex-direction:column; gap:5px; margin-bottom:18px; }
    .ce-field > label { font-size:11px; font-weight:700; letter-spacing:.4px; text-transform:uppercase; color:var(--muted); }
    .ce-field input, .ce-field select, .ce-field textarea { padding:10px 12px; border:1px solid var(--border); border-radius:8px; font-size:14px; font-family:inherit; background:var(--bg); width:100%; }
    .ce-field input:focus, .ce-field select:focus, .ce-field textarea:focus { outline:none; border-color:var(--green); }
    .ce-field textarea { resize:vertical; line-height:1.6; }
    .ce-field small { font-size:11px; color:var(--muted); }
    .ce-field--mic { max-width:110px; }
    .ce-row { display:flex; gap:14px; align-items:flex-start; }
    .ce-row > .ce-field { flex:1; }
    .ce-combo { position:relative; }
    .ce-combo--open { z-index:60; }
    .ce-combo-anchor { position:relative; }
    .ce-suggest { position:absolute; top:calc(100% + 4px); left:0; right:0; z-index:70; background:#fff; border:1px solid var(--border); border-radius:10px; box-shadow:0 10px 30px rgba(0,0,0,.16); overflow-y:auto; max-height:280px; padding:4px; }
    .ce-suggest button { display:block; width:100%; text-align:left; padding:9px 11px; border:none; background:none; font-size:13px; cursor:pointer; font-family:inherit; border-radius:7px; color:var(--text); }
    .ce-suggest button:hover { background:var(--green-light); color:var(--green); }
    .ce-imgs { display:grid; grid-template-columns:1fr 1.6fr; gap:18px; }
    .ce-img-head { margin-bottom:8px; }
    .ce-img-head strong { display:block; font-size:13px; }
    .ce-img-head span { font-size:11px; color:var(--muted); }
    .ce-drop { display:block; position:relative; border:2px dashed var(--border); border-radius:12px; overflow:hidden; cursor:pointer; background:var(--bg); transition:border-color .15s; }
    .ce-drop:hover { border-color:var(--green); }
    .ce-drop input { position:absolute; inset:0; opacity:0; cursor:pointer; width:100%; height:100%; }
    .ce-drop img { display:block; width:100%; height:100%; object-fit:cover; }
    .ce-img--portret .ce-drop { aspect-ratio:3/4; }
    .ce-img--landscape .ce-drop { aspect-ratio:16/9; }
    .ce-drop-gol { display:flex; align-items:center; justify-content:center; height:100%; font-size:13px; color:var(--muted); }
    .ce-note { font-size:13px; color:var(--muted); margin:0 0 16px; line-height:1.6; }
    .ce-tipuri { display:flex; flex-direction:column; gap:12px; margin-bottom:14px; }
    .ce-tip { border:1px solid var(--border); border-radius:10px; padding:14px 15px 2px; background:var(--bg); }
    .ce-tip .ce-field { margin-bottom:14px; }
    .ce-x { align-self:center; margin-top:14px; width:30px; height:30px; border:1px solid var(--border); background:var(--card); border-radius:8px; cursor:pointer; color:var(--muted); font-size:15px; }
    .ce-x:hover { color:#c0392b; border-color:#c0392b; }
    .ce-add { font-size:13px; padding:8px 14px; border:1px dashed var(--border); background:none; border-radius:8px; cursor:pointer; color:var(--muted); font-family:inherit; }
    .ce-add:hover { border-color:var(--green); color:var(--green); }
    .ce-check { display:flex; gap:10px; align-items:flex-start; margin-bottom:20px; cursor:pointer; }
    .ce-check strong { display:block; font-size:14px; }
    .ce-check em { display:block; font-style:normal; font-size:12px; color:var(--muted); margin-top:2px; }
    .ce-link { font-size:13px; color:var(--green); text-decoration:none; font-weight:500; }
    .ce-link:hover { text-decoration:underline; }
    .ce-alert { padding:11px 15px; border-radius:8px; font-size:13px; margin-bottom:16px; }
    .ce-alert--err { background:#fdecea; border:1px solid #f5c6c7; color:#a3282a; }
    .ce-alert--ok { background:var(--green-light); border:1px solid #b2d9c0; color:var(--green); }
    .ce-bar { position:fixed; left:0; right:0; bottom:0; z-index:30; display:flex; align-items:center; justify-content:space-between; gap:16px; padding:12px 24px; background:var(--card); border-top:1px solid var(--border); box-shadow:0 -2px 16px rgba(0,0,0,.06); }
    .ce-bar > span { font-size:13px; color:var(--muted); overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .ce-bar > div { display:flex; align-items:center; gap:12px; flex-shrink:0; }
    .ce-cancel { font-size:13px; color:var(--muted); text-decoration:none; }
    .ce-save { padding:10px 22px; background:var(--accent); color:#fff; border:none; border-radius:8px; font-size:14px; font-weight:600; cursor:pointer; font-family:inherit; }
    @media(max-width:900px) {
      .ce { grid-template-columns:1fr; gap:16px; }
      .ce-nav { position:static; }
      .ce-nav ol { flex-direction:row; overflow-x:auto; }
      .ce-nav a em { display:none; }
      .ce-imgs { grid-template-columns:1fr; }
    }
`;
