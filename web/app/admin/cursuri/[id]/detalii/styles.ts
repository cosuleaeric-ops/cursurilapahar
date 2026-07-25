// Copiat verbatim din <style> din admin/statistici/cursuri/view.php.
export const VIEW_CSS = `
.course-wrap { max-width: 800px; margin: 0 auto; }
    .course-hero { margin-bottom: 28px; }
    .course-hero h2 { font-family:'Crimson Pro',Georgia,serif; font-size:28px; font-weight:600; letter-spacing:-.3px; }
    .course-hero .meta { font-size:14px; color:var(--muted); margin-top:4px; }
    .section-card { background:var(--card); border:1px solid var(--border); border-radius:var(--radius); padding:24px; box-shadow:var(--shadow); margin-bottom:20px; }
    .section-card h3 { font-family:'Crimson Pro',Georgia,serif; font-size:17px; font-weight:600; margin-bottom:16px; }
    .dist-total { font-family:'Crimson Pro',Georgia,serif; font-size:26px; font-weight:600; margin-bottom:4px; }
    .dist-sub { font-size:13px; color:var(--muted); margin-bottom:16px; }
    .dist-list { list-style:none; display:flex; flex-direction:column; gap:10px; }
    .dist-list li { display:flex; align-items:center; gap:12px; font-size:15px; }
    .dist-bullet { width:8px; height:8px; border-radius:50%; background:var(--green); flex-shrink:0; }
    .viza-file { display:flex; align-items:center; justify-content:space-between; background:var(--bg); border:1px solid var(--border); border-radius:var(--radius-sm); padding:10px 14px; margin-bottom:10px; }
    .viza-name { font-size:13px; color:var(--text); text-decoration:none; font-weight:500; }
    .viza-name:hover { color:var(--green); }
    .viza-date { font-size:12px; color:var(--muted); }
    .upload-zone { border:2px dashed var(--border); border-radius:var(--radius-sm); padding:20px; text-align:center; position:relative; cursor:pointer; transition:all .15s; }
    .upload-zone:hover { border-color:var(--green); background:var(--green-light); }
    .upload-zone input { position:absolute; inset:0; opacity:0; cursor:pointer; width:100%; height:100%; }
    .upload-zone p { font-size:13px; color:var(--muted); margin:0; }
    .participants-list { columns:2; column-gap:24px; list-style:none; }
    .participants-list li { font-size:13px; padding:3px 0; break-inside:avoid; }
    .participants-list li span { font-size:11px; color:var(--muted); margin-left:4px; }
    .danger-zone { border-top:1px solid var(--border); padding-top:16px; margin-top:8px; }
    @media(max-width:600px) { .participants-list { columns:1; } }
    /* Update participants */
    .update-drop { border:2px dashed var(--border); border-radius:var(--radius-sm); padding:24px; text-align:center; position:relative; cursor:pointer; transition:all .15s; }
    .update-drop:hover, .update-drop.dragover { border-color:var(--green); background:var(--green-light); }
    .update-drop input { position:absolute; inset:0; opacity:0; cursor:pointer; width:100%; height:100%; }
    .update-drop p { font-size:13px; color:var(--muted); margin:0; }
    .update-col-picker { display:none; margin-top:12px; }
    .update-col-picker label { font-size:11px; font-weight:700; letter-spacing:.6px; text-transform:uppercase; color:var(--muted); display:block; margin-bottom:6px; }
    .update-col-picker select { width:100%; padding:8px 12px; border:1px solid var(--border); border-radius:var(--radius-sm); font-size:14px; background:var(--bg); }
    .update-preview { display:none; margin-top:12px; background:var(--green-light); border:1px solid #b2d9c0; border-radius:var(--radius-sm); padding:14px 16px; font-size:13px; }
    .update-preview strong { font-family:'Crimson Pro',Georgia,serif; font-size:18px; }
    .update-submit { display:none; margin-top:12px; }
    /* Returning participants */
    .returning-list { list-style:none; display:flex; flex-direction:column; gap:8px; }
    .returning-list li { font-size:14px; display:flex; align-items:baseline; gap:8px; flex-wrap:wrap; }
    .returning-badge { background:var(--green-light); color:var(--green); border:1px solid #b2d9c0; border-radius:12px; font-size:11px; font-weight:700; padding:2px 9px; white-space:nowrap; }
    .returning-courses { font-size:12px; color:var(--muted); }
    /* Viză subtipuri */
    .subtip-table { width:100%; border-collapse:collapse; font-size:14px; margin-top:12px; }
    .subtip-table th { font-size:11px; font-weight:700; letter-spacing:.5px; text-transform:uppercase; color:var(--muted); padding:6px 10px; text-align:left; border-bottom:1px solid var(--border); }
    .subtip-table td { padding:10px 10px; border-bottom:1px solid var(--border); }
    .subtip-table tr:last-child td { border-bottom:none; }
    .subtip-table td.num { font-variant-numeric:tabular-nums; text-align:right; }
    .seria-badge { font-family:monospace; font-size:13px; font-weight:700; background:var(--bg); border:1px solid var(--border); border-radius:6px; padding:2px 8px; }
    .sold-match { color:var(--green); font-weight:600; }
    .no-match { color:var(--muted); font-style:italic; }
    .reprocess-btn { font-size:12px; color:var(--muted); background:none; border:none; cursor:pointer; padding:0; text-decoration:underline; }
    /* Raport financiar */
    .raport-grid { display:grid; grid-template-columns:1fr 1fr 1fr; gap:16px; margin-bottom:20px; }
    .raport-stat { background:var(--bg); border:1px solid var(--border); border-radius:12px; padding:16px; }
    .raport-stat .label { font-size:11px; font-weight:700; letter-spacing:.6px; text-transform:uppercase; color:var(--muted); margin-bottom:6px; }
    .raport-stat .value { font-family:'Crimson Pro',Georgia,serif; font-size:26px; font-weight:600; }
    .raport-stat .value.ditl { color:#c0392b; }
    .raport-meta { font-size:12px; color:var(--muted); margin-bottom:16px; }
    .raport-drop { border:2px dashed var(--border); border-radius:var(--radius-sm); padding:20px; text-align:center; position:relative; cursor:pointer; transition:all .15s; }
    .raport-drop:hover, .raport-drop.dragover { border-color:var(--green); background:var(--green-light); }
    .raport-drop input { position:absolute; inset:0; opacity:0; cursor:pointer; width:100%; height:100%; }
    .raport-drop p { font-size:13px; color:var(--muted); margin:0; }
    .raport-preview { display:none; margin-top:12px; background:var(--green-light); border:1px solid #b2d9c0; border-radius:var(--radius-sm); padding:12px 16px; font-size:14px; }
    .raport-submit { display:none; margin-top:10px; }
    @media(max-width:600px) { .raport-grid { grid-template-columns:1fr; } }
    .actions-grid { display:grid; grid-template-columns:repeat(2,1fr); gap:16px; margin-bottom:16px; }
    .actions-grid .section-card { margin-bottom:0; display:flex; flex-direction:column; }
    .actions-grid .section-card > form,
    .actions-grid .section-card .raport-form { flex:1; display:flex; flex-direction:column; }
    .actions-grid .raport-drop,
    .actions-grid .update-drop,
    .actions-grid .upload-zone { flex:1; }
`;
