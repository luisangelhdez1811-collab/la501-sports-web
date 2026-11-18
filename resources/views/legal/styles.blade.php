<style>
    .lg-page {
        --lg-bg: #0C0B0A; --lg-card: #151311; --lg-txt: #F5F5F4; --lg-sub: #A8A29E; --lg-bdr: rgba(255,255,255,.08);
        --lg-accent: #F97316; --lg-row: rgba(255,255,255,.03);
        background: var(--lg-bg); color: var(--lg-txt); font-family: 'Inter', system-ui, sans-serif; min-height: 100vh;
    }
    html:not(.dark) .lg-page {
        --lg-bg: #F5F3EF; --lg-card: #FFFFFF; --lg-txt: #1C1917; --lg-sub: #57534E; --lg-bdr: rgba(0,0,0,.09);
        --lg-accent: #C2410C; --lg-row: rgba(0,0,0,.025);
    }
    .lg-hero { border-bottom: 1px solid var(--lg-bdr); background: var(--lg-card); padding: 56px 24px 40px; text-align: center; }
    .lg-eyebrow { font-size: 12px; font-weight: 700; letter-spacing: .18em; text-transform: uppercase; color: var(--lg-accent); margin: 0 0 12px; }
    .lg-hero h1 { font-family: 'Oswald', 'Inter', sans-serif; font-size: clamp(30px, 5vw, 46px); font-weight: 700; line-height: 1.1; margin: 0; letter-spacing: .01em; }
    .lg-meta { margin: 14px 0 0; font-size: 13px; color: var(--lg-sub); }
    .lg-wrap { max-width: 1080px; margin: 0 auto; padding: 36px 20px 80px; display: grid; gap: 28px; grid-template-columns: 1fr; }
    @media (min-width: 960px) { .lg-wrap { grid-template-columns: 240px 1fr; } }
    .lg-toc { align-self: start; }
    @media (min-width: 960px) { .lg-toc { position: sticky; top: 96px; } }
    .lg-toc p { font-size: 11px; font-weight: 700; letter-spacing: .14em; text-transform: uppercase; color: var(--lg-sub); margin: 0 0 10px; }
    .lg-toc ol { list-style: none; margin: 0; padding: 0; border-left: 1px solid var(--lg-bdr); }
    .lg-toc a { display: block; padding: 5px 0 5px 14px; font-size: 13px; line-height: 1.4; color: var(--lg-sub); text-decoration: none; }
    .lg-toc a:hover, .lg-toc a:focus-visible { color: var(--lg-txt); }
    @media (max-width: 959px) { .lg-toc details { background: var(--lg-card); border: 1px solid var(--lg-bdr); border-radius: 12px; padding: 12px 16px; } }
    .lg-card { background: var(--lg-card); border: 1px solid var(--lg-bdr); border-radius: 16px; padding: 32px clamp(20px, 4vw, 44px); min-width: 0; }
    .lg-h2 { font-family: 'Oswald', 'Inter', sans-serif; font-size: 20px; font-weight: 600; margin: 34px 0 12px; padding-bottom: 8px; border-bottom: 1px solid var(--lg-bdr); scroll-margin-top: 96px; }
    .lg-h2:first-child { margin-top: 0; }
    .lg-h3 { font-size: 15px; font-weight: 700; margin: 22px 0 8px; }
    .lg-p { font-size: 15px; line-height: 1.7; color: var(--lg-sub); margin: 0 0 14px; }
    .lg-p strong { color: var(--lg-txt); }
    .lg-list { margin: 0 0 16px; padding-left: 20px; color: var(--lg-sub); font-size: 15px; line-height: 1.65; }
    .lg-list li { margin-bottom: 6px; }
    .lg-list li::marker { color: var(--lg-accent); }
    .lg-table-wrap { overflow-x: auto; margin: 4px 0 18px; border: 1px solid var(--lg-bdr); border-radius: 12px; }
    .lg-table { width: 100%; border-collapse: collapse; font-size: 14px; }
    .lg-table th, .lg-table td { padding: 12px 16px; text-align: left; vertical-align: top; border-bottom: 1px solid var(--lg-bdr); line-height: 1.55; }
    .lg-table tr:last-child th, .lg-table tr:last-child td { border-bottom: 0; }
    .lg-table th { width: 34%; font-weight: 600; color: var(--lg-txt); background: var(--lg-row); }
    .lg-table td { color: var(--lg-sub); }
    @media (max-width: 560px) {
        .lg-table th, .lg-table td { display: block; width: auto; }
        .lg-table th { border-bottom: 0; padding-bottom: 2px; }
    }
</style>
