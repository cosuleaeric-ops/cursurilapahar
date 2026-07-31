// Biletul se deschide cel mai des pe telefon, din email — deci o singură coloană,
// QR mare, fără nimic de derulat înainte de cod.
export const BILET_CSS = `
body { margin:0; background:#f2f1ee; font-family:system-ui,-apple-system,"Segoe UI",sans-serif; color:#1a1a1a; }
    .bilet-wrap { min-height:100vh; display:flex; align-items:center; justify-content:center; padding:20px; }
    .bilet { background:#fff; width:100%; max-width:380px; border-radius:18px; padding:26px 24px 20px; box-shadow:0 2px 24px rgba(0,0,0,.09); }
    .bilet.invalid { opacity:.75; }
    .b-head { display:flex; justify-content:space-between; align-items:baseline; gap:12px; padding-bottom:14px; border-bottom:1px dashed #d8d5cf; }
    .b-label { font-size:10px; font-weight:700; letter-spacing:1.2px; text-transform:uppercase; color:#8a8781; }
    .b-serie { font-family:ui-monospace,SFMono-Regular,Menlo,monospace; font-size:14px; font-weight:700; letter-spacing:.5px; }
    h1 { font-family:'Crimson Pro',Georgia,serif; font-size:23px; font-weight:600; line-height:1.25; margin:18px 0 8px; }
    .b-when { font-size:14px; color:#3d3a35; }
    .b-where { font-size:13px; color:#8a8781; margin-top:2px; }
    .b-qr { margin:22px auto 18px; width:216px; }
    .b-qr svg { width:100%; height:auto; display:block; }
    .b-warn { background:#fdecea; border:1px solid #f5c6c7; color:#a3282a; border-radius:10px; padding:10px 12px; font-size:13px; text-align:center; margin-bottom:14px; }
    .b-used { background:#fff6e5; border:1px solid #f0dcae; color:#8a6412; border-radius:10px; padding:10px 12px; font-size:13px; text-align:center; margin-bottom:14px; }
    .b-rows { margin:0 0 18px; padding-top:16px; border-top:1px dashed #d8d5cf; display:flex; flex-direction:column; gap:9px; }
    .b-rows > div { display:flex; justify-content:space-between; gap:14px; }
    .b-rows dt { font-size:12px; color:#8a8781; }
    .b-rows dd { margin:0; font-size:13px; font-weight:600; text-align:right; }
    .b-org { font-size:11px; color:#8a8781; line-height:1.5; padding-top:14px; border-top:1px solid #eeece8; }
    .b-foot { font-size:10px; font-weight:700; letter-spacing:.8px; text-transform:uppercase; color:#8a8781; text-align:center; margin-top:14px; }
    @media print {
      body { background:#fff; }
      .bilet-wrap { min-height:0; padding:0; }
      .bilet { box-shadow:none; border:1px solid #ccc; max-width:none; }
    }
`;
