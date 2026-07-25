export const SP_CSS = `
/* ── Parteneri — layout TLDR, stil Morning Brew ─────────
       Paletă: alb, text #231F20, pastele #BDE1FF / #F1E8D5 /
       #FFCFFA / #BCF46E, colțuri 16-20px, umbre moi */
    .sp-wrap {
        /* re-scopez variabilele site-ului pe tema deschisă,
           ca galeria să se adapteze singură */
        --text: #231F20;
        --text-muted: #55504c;
        --text-faint: rgba(0,0,0,.3);
        --surface: #FAF9F5;
        --accent: #231F20;
        background: #fff; color: #231F20; overflow-x: clip;
    }
    .sp-wrap .container { max-width: 1140px; }
    .sp-wrap .section-title { color: #231F20; letter-spacing: -.02em; }
    .sp-lead { color: #6f6a66; text-align: center; max-width: 60ch; margin: 0 auto 42px; line-height: 1.65; }
    .sp-demo { display: flex; flex-wrap: wrap; justify-content: center; gap: 10px; max-width: 760px; margin: -28px auto 42px; }
    .sp-demo span {
        background: #FAF9F5; border: 1px solid rgba(0,0,0,.1); border-radius: 999px;
        padding: 9px 18px; font-weight: 600; font-size: .92rem; color: #231F20;
    }

    /* Hero: text stânga + formular dreapta */
    .sp-hero { padding: 72px 0; }
    .sp-hero-grid {
        display: grid; grid-template-columns: 1.05fr .95fr;
        gap: 56px; align-items: start;
    }
    .sp-hero h1 {
        font-family: var(--font-serif);
        font-size: clamp(1.7rem, 3.1vw, 2.5rem);
        line-height: 1.32; letter-spacing: -.02em;
        margin: 0 0 22px; color: #231F20;
    }
    .sp-hero h1 span {
        background: #FFE86B; border-radius: 10px;
        padding: .04em .18em; box-decoration-break: clone; -webkit-box-decoration-break: clone;
    }
    .sp-hero-sub { color: #55504c; font-size: 1.05rem; line-height: 1.7; margin: 0 0 18px; }
    .sp-hero-sub strong { color: #231F20; }

    /* Card formular — off-white, rotunjit, umbră moale */
    .sp-form-card {
        background: #FAF9F5;
        border: 1px solid rgba(0,0,0,.08);
        border-radius: 20px;
        box-shadow: 0 15px 27px -4px rgba(0,0,0,.09), 0 5px 9px -3px rgba(0,0,0,.05);
        padding: 32px 30px;
    }
    .sp-form-card label {
        display: block; font-weight: 700; font-size: .92rem;
        margin: 0 0 6px; color: #231F20;
    }
    .sp-form-card label em { color: #d0454c; font-style: normal; }
    .sp-form-card input,
    .sp-form-card select,
    .sp-form-card textarea {
        width: 100%; background: #fff; color: #231F20;
        border: 1px solid rgba(0,0,0,.16); border-radius: 10px;
        padding: 12px 13px; font-size: .95rem; margin-bottom: 18px;
    }
    .sp-form-card textarea { resize: vertical; }
    .sp-form-card input:focus,
    .sp-form-card select:focus,
    .sp-form-card textarea:focus { outline: 2px solid #231F20; outline-offset: 1px; }
    .sp-btn {
        display: inline-block; background: #231F20; color: #fff !important;
        font-weight: 700; font-size: 1rem; border-radius: 999px;
        padding: 13px 30px; border: none; cursor: pointer; text-decoration: none;
        transition: transform .15s, background .15s, box-shadow .15s;
    }
    .sp-btn:hover { transform: translateY(-2px); background: #000; box-shadow: 0 10px 18px -8px rgba(0,0,0,.4); }

    /* Secțiuni */
    .sp-sec { padding: 64px 0; }
    .sp-sec .section-title { margin-bottom: 10px; }

    /* Carduri de canale — blocuri pastel rotunjite */
    .sp-aud { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; }
    .sp-aud-card {
        border-radius: 18px; padding: 26px 24px; color: #231F20;
        transition: transform .15s, box-shadow .15s;
    }
    .sp-aud-card:nth-child(1) { background: #BDE1FF; }
    .sp-aud-card:nth-child(2) { background: #F1E8D5; }
    .sp-aud-card:nth-child(3) { background: #FFCFFA; }
    .sp-aud-card:nth-child(4) { background: #BCF46E; }
    .sp-aud-card:hover { transform: translateY(-6px) rotate(-.6deg); box-shadow: 0 22px 36px -12px rgba(0,0,0,.22); }
    .sp-aud-card .ic {
        width: 46px; height: 46px; border-radius: 14px;
        background: rgba(255,255,255,.6); display: flex; align-items: center; justify-content: center;
        font-size: 24px; margin-bottom: 18px;
    }
    .sp-aud-card h3 { margin: 0 0 16px; font-size: .82rem; font-weight: 800; text-transform: uppercase; letter-spacing: .06em; color: #231F20; }
    .sp-aud-card .big { font-family: var(--font-serif); font-size: 2.5rem; line-height: 1; letter-spacing: -.025em; color: #231F20; }
    .sp-aud-card .biglbl { font-size: .82rem; font-weight: 700; color: #3f3a37; margin: 5px 0 14px; }
    .sp-aud-card p { margin: 0; color: #3f3a37; font-size: .88rem; line-height: 1.5; min-height: 3.6em; }

    /* Galerie */
    .sp-wrap .gallery-item img { border-radius: 14px; }
    .sp-wrap .gslider-btn { background: #FAF9F5; border: 1px solid rgba(0,0,0,.12); color: #231F20; }
    .sp-wrap .gslider-btn:hover { background: #F1E8D5; }

    /* CTA final */
    .sp-cta { text-align: center; padding: 72px 0 84px; background: #FAF9F5; }
    .sp-cta h2 {
        font-family: var(--font-serif); letter-spacing: -.02em;
        font-size: clamp(1.8rem, 4vw, 2.7rem); margin: 0 0 14px; color: #231F20;
    }
    .sp-cta p { color: #6f6a66; margin: 0 0 28px; }

    @media (max-width: 920px) {
        .sp-hero-grid { grid-template-columns: 1fr; }
        .sp-aud { grid-template-columns: 1fr 1fr; }
    }
    @media (max-width: 620px) {
        .sp-aud { grid-template-columns: 1fr; }
        .sp-aud-card p { min-height: 0; }
    }
`;
