<?php
declare(strict_types=1);
// Simple Rebirth clicker game
// Backend: PHP 8.3
// Frontend: Vanilla JS

// You can extend this file with your own authentication / user system.
?>
<!doctype html>
<html lang="cs">
<head>
    <meta charset="utf-8">
    <title>Rebirth Clicker</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Inter", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: radial-gradient(circle at 10% 10%, rgba(59,130,246,0.12), transparent 30%),
                        radial-gradient(circle at 90% 20%, rgba(236,72,153,0.14), transparent 30%),
                        radial-gradient(circle at 30% 80%, rgba(52,211,153,0.16), transparent 30%),
                        #030712;
            color: #f9fafb;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            padding: 32px 20px 48px;
        }
        .app-shell {
            width: 100%;
            max-width: 1200px;
            position: relative;
        }
        .halo {
            position: absolute;
            inset: 0;
            pointer-events: none;
            background: radial-gradient(circle at 60% 0%, rgba(37,99,235,0.15), transparent 45%),
                        radial-gradient(circle at 0% 60%, rgba(16,185,129,0.12), transparent 35%);
            filter: blur(30px);
            z-index: 0;
        }
        .app {
            position: relative;
            z-index: 1;
            background: rgba(2,6,23,0.75);
            border: 1px solid rgba(148,163,184,0.24);
            border-radius: 18px;
            box-shadow: 0 25px 70px rgba(0,0,0,0.55);
            padding: 28px;
            display: grid;
            grid-template-columns: 1.35fr 1fr;
            gap: 22px;
            backdrop-filter: blur(12px);
        }
        @media (max-width: 960px) {
            .app {
                grid-template-columns: 1fr;
            }
        }
        .card {
            background: linear-gradient(145deg, rgba(15,23,42,0.9), rgba(2,6,23,0.92));
            border-radius: 18px;
            padding: 18px 20px;
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.05), 0 14px 40px rgba(0,0,0,0.45);
            border: 1px solid rgba(148,163,184,0.22);
        }
        .masthead {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            align-items: center;
            padding: 0 6px 6px;
            border-bottom: 1px solid rgba(148,163,184,0.16);
            margin-bottom: 6px;
        }
        .title-block h1 {
            margin: 0;
            letter-spacing: 0.04em;
            font-size: 1.45rem;
        }
        .title-block p {
            margin: 6px 0 0;
            color: #cbd5e1;
            font-size: 0.95rem;
        }
        .pill-row {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }
        .pill {
            padding: 8px 12px;
            border-radius: 999px;
            background: rgba(59,130,246,0.12);
            border: 1px solid rgba(59,130,246,0.3);
            color: #bfdbfe;
            font-size: 0.8rem;
        }
        .pill.success { background: rgba(34,197,94,0.12); border-color: rgba(34,197,94,0.4); color: #bbf7d0; }
        .pill.warning { background: rgba(234,179,8,0.12); border-color: rgba(234,179,8,0.4); color: #fef08a; }
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }
        .card-header h2 {
            margin: 0;
            font-size: 1.1rem;
            letter-spacing: 0.03em;
            text-transform: uppercase;
            color: #e5e7eb;
        }
        .badge {
            padding: 2px 8px;
            border-radius: 999px;
            font-size: 0.7rem;
            border: 1px solid rgba(148,163,184,0.5);
            color: #9ca3af;
        }
        .badge.success {
            color: #22c55e;
            border-color: rgba(34,197,94,0.5);
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 12px;
            margin: 12px 0 16px;
        }
        .stat {
            padding: 8px 10px;
            border-radius: 12px;
            background: linear-gradient(135deg, rgba(30,41,59,0.9), rgba(15,23,42,0.95));
            border: 1px solid rgba(148,163,184,0.25);
            position: relative;
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.03);
        }
        .stat .mini {
            position: absolute;
            top: 8px;
            right: 10px;
            padding: 2px 6px;
            border-radius: 999px;
            background: rgba(59,130,246,0.16);
            color: #bfdbfe;
            font-size: 0.65rem;
            letter-spacing: 0.05em;
        }
        .stat-icon {
            width: 28px;
            height: 28px;
            border-radius: 10px;
            background: rgba(59,130,246,0.14);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #bfdbfe;
            margin-bottom: 6px;
            font-size: 0.85rem;
        }
        .stat-label {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #9ca3af;
        }
        .stat-value {
            margin-top: 3px;
            font-size: 1.1rem;
            font-weight: 600;
        }
        .primary-button {
            width: 100%;
            padding: 18px;
            border-radius: 14px;
            border: none;
            cursor: pointer;
            font-size: 1.05rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            margin: 12px 0 4px;
            background: linear-gradient(120deg, #10b981, #22c55e, #16a34a);
            color: #022c22;
            transition: transform 80ms ease-out, box-shadow 80ms ease-out, filter 80ms ease-out;
            box-shadow: 0 15px 42px rgba(34,197,94,0.55);
        }
        .primary-button:hover {
            transform: translateY(-1px);
            filter: brightness(1.06);
            box-shadow: 0 16px 45px rgba(34,197,94,0.6);
        }
        .primary-button:active {
            transform: translateY(0);
            filter: brightness(0.96);
            box-shadow: 0 10px 28px rgba(34,197,94,0.45);
        }
        .primary-button[disabled] {
            opacity: 0.6;
            cursor: default;
            box-shadow: none;
        }
        .sub {
            font-size: 0.8rem;
            color: #9ca3af;
            text-align: center;
        }
        .progress-outer {
            margin-top: 10px;
            width: 100%;
            height: 8px;
            border-radius: 999px;
            background: rgba(15,23,42,0.9);
            overflow: hidden;
            border: 1px solid rgba(30,64,175,0.7);
        }
        .progress-inner {
            height: 100%;
            border-radius: inherit;
            background: linear-gradient(90deg, #22c55e, #3b82f6);
            width: 0%;
            transition: width 120ms linear;
        }
        .column-title {
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 0.14em;
            color: #a5b4fc;
            margin: 6px 0 12px;
        }
        .shop-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .shop-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 14px;
            border-radius: 14px;
            background: linear-gradient(145deg, rgba(23,37,84,0.9), rgba(15,23,42,0.95));
            border: 1px solid rgba(99,102,241,0.25);
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.04);
        }
        .shop-main {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        .shop-title { font-size: 0.95rem; letter-spacing: 0.01em; }
        .shop-subtitle {
            font-size: 0.75rem;
            color: #94a3b8;
        }
        .shop-meta {
            font-size: 0.8rem;
            color: #e5e7eb;
        }
        .shop-flavor { font-size: 0.72rem; color: #a5b4fc; }
        .shop-button {
            padding: 8px 14px;
            border-radius: 999px;
            border: none;
            cursor: pointer;
            font-size: 0.8rem;
            font-weight: 600;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            background: linear-gradient(130deg, #2563eb, #4f46e5);
            color: #e5e7eb;
            transition: opacity 80ms ease-out, transform 80ms ease-out;
        }
        .shop-button:hover {
            transform: translateY(-1px);
        }
        .shop-button:active {
            transform: translateY(0);
        }
        .shop-button[disabled] {
            opacity: 0.4;
            cursor: default;
        }
        .rebirth-block {
            margin-top: 14px;
            padding-top: 10px;
            border-top: 1px solid rgba(148,163,184,0.25);
        }
        .rebirth-title {
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.16em;
            color: #facc15;
            margin-bottom: 4px;
        }
        .rebirth-text {
            font-size: 0.8rem;
            color: #e5e7eb;
        }
        .rebirth-text strong {
            color: #facc15;
        }
        .footer {
            margin-top: 12px;
            font-size: 0.75rem;
            color: #6b7280;
        }
        .status {
            font-size: 0.75rem;
            color: #9ca3af;
            text-align: right;
        }
        .status span {
            color: #22c55e;
        }
        .auth-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 12px;
        }
        .input-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .input-group label {
            font-size: 0.85rem;
            color: #cbd5e1;
        }
        .text-input {
            width: 100%;
            padding: 10px 12px;
            border-radius: 10px;
            border: 1px solid rgba(148,163,184,0.4);
            background: rgba(15,23,42,0.9);
            color: #e2e8f0;
        }
        .secondary-button {
            padding: 10px 12px;
            border-radius: 12px;
            border: 1px solid rgba(99,102,241,0.45);
            background: linear-gradient(130deg, rgba(79,70,229,0.15), rgba(59,130,246,0.22));
            color: #e5e7eb;
            cursor: pointer;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }
        .secondary-button[disabled] {
            opacity: 0.5;
            cursor: default;
        }
        .text-button {
            background: none;
            border: none;
            color: #93c5fd;
            cursor: pointer;
            padding: 0;
            text-decoration: underline;
        }
        .auth-note {
            font-size: 0.82rem;
            color: #e2e8f0;
            margin-top: 10px;
        }
    </style>
</head>
<body>
<div class="app-shell">
    <div class="halo" aria-hidden="true"></div>
    <div class="masthead">
        <div class="title-block">
            <h1>Rebirth Clicker 2.0</h1>
            <p>Vytěž energii, utrácej chytrě a rebirthuj dřív, než se tempo zastaví.</p>
        </div>
        <div class="pill-row">
            <div class="pill success">Ukládání serverem</div>
            <div class="pill">Smyčka: klik → upgrade → rebirth</div>
        </div>
    </div>
    <div class="app">
        <section class="card">
            <div class="card-header">
                <h2>Výroba energie</h2>
                <div class="badge">PHP 8.3 · Vanilla JS</div>
            </div>
            <div class="stats-grid">
                <div class="stat">
                    <div class="stat-icon" aria-hidden="true">🖱️</div>
                    <div class="stat-label">Kliknutí</div>
                    <div class="stat-value" id="stat-clicks">0</div>
                </div>
                <div class="stat">
                    <div class="stat-icon" aria-hidden="true">⚡</div>
                    <div class="stat-label">Energie</div>
                    <div class="stat-value" id="stat-energy">0</div>
                </div>
                <div class="stat">
                    <div class="stat-icon" aria-hidden="true">💰</div>
                    <div class="stat-label">Coiny</div>
                    <div class="stat-value" id="stat-coins">0</div>
                </div>
                <div class="stat">
                    <div class="stat-icon" aria-hidden="true">🔁</div>
                    <div class="stat-label">Rebirthy</div>
                    <div class="stat-value" id="stat-rebirths">0</div>
                </div>
                <div class="stat">
                    <div class="stat-icon" aria-hidden="true">✊</div>
                    <div class="stat-label">Síla kliknutí</div>
                    <div class="stat-value" id="stat-clickPower">1</div>
                </div>
                <div class="stat">
                    <div class="stat-icon" aria-hidden="true">⏫</div>
                    <div class="stat-label">Rebirth násobitel</div>
                    <div class="stat-value" id="stat-rebirthMult">1.0x</div>
                </div>
                <div class="stat">
                    <div class="stat-icon" aria-hidden="true">🤖</div>
                    <div class="stat-label">Autoclickery</div>
                    <div class="stat-value" id="stat-autoclickers">0</div>
                </div>
                <div class="stat">
                    <div class="stat-icon" aria-hidden="true">🔧</div>
                    <div class="stat-label">Síla autoclicku</div>
                    <div class="stat-value" id="stat-autopower">1.0x</div>
                    <div class="mini">Tuning</div>
                </div>
                <div class="stat">
                    <div class="stat-icon" aria-hidden="true">🚀</div>
                    <div class="stat-label">Globální bonus</div>
                    <div class="stat-value" id="stat-globalbonus">1.0x</div>
                    <div class="mini">Motivátor</div>
                </div>
            </div>
            <button id="btn-click" class="primary-button">
                Klikni pro energii
            </button>
            <div class="sub" id="click-info">
                Kliky generují energii i coiny. Rebirth resetuje progres, ale trvale buffuje všechny výdělky.
            </div>
            <div class="progress-outer" aria-hidden="true">
                <div class="progress-inner" id="progress-bar"></div>
            </div>
            <div class="footer">
                Stav: <span id="status-text">Načítám hru…</span>
            </div>
        </section>
        <section class="card">
            <div class="card-header">
                <h2>Upgrady &amp; Rebirth</h2>
                <div class="badge">Persistentní progres</div>
            </div>
            <div class="column-title">Upgrady</div>
            <div class="shop-list">
                <div class="shop-item">
                    <div class="shop-main">
                        <div class="shop-title">Síla kliknutí</div>
                        <div class="shop-subtitle">+1 základní energie za kliknutí</div>
                        <div class="shop-meta">
                            Cena: <span id="cost-click-upgrade">10</span> coinů
                        </div>
                    </div>
                    <button class="shop-button" id="btn-buy-click">
                        Koupit
                    </button>
                </div>
                <div class="shop-item">
                    <div class="shop-main">
                        <div class="shop-title">Generátor energie</div>
                        <div class="shop-subtitle">+1 coin za kliknutí</div>
                        <div class="shop-meta">
                            Cena: <span id="cost-energy-upgrade">25</span> coinů
                        </div>
                    </div>
                    <button class="shop-button" id="btn-buy-energy">
                        Koupit
                    </button>
                </div>
                <div class="shop-item">
                    <div class="shop-main">
                        <div class="shop-title">Autoclicker</div>
                        <div class="shop-subtitle">Automaticky provede 1 kliknutí za sekundu</div>
                        <div class="shop-meta">
                            Cena: <span id="cost-autoclicker">100</span> coinů
                        </div>
                    </div>
                    <button class="shop-button" id="btn-buy-autoclicker">
                        Koupit
                    </button>
                </div>
                <div class="shop-item">
                    <div class="shop-main">
                        <div class="shop-title">Tuning autoclickerů</div>
                        <div class="shop-subtitle">+0,5 násobič pro automatické kliky</div>
                        <div class="shop-meta">
                            Cena: <span id="cost-autopower">200</span> coinů
                        </div>
                        <div class="shop-flavor">Inženýři v továrně dolaďují každé ozubené kolečko.</div>
                    </div>
                    <button class="shop-button" id="btn-buy-autopower">
                        Vylepšit
                    </button>
                </div>
                <div class="shop-item">
                    <div class="shop-main">
                        <div class="shop-title">Motivátor posádky</div>
                        <div class="shop-subtitle">+0,5× globální výdělek</div>
                        <div class="shop-meta">
                            Cena: <span id="cost-globalbonus">500</span> coinů
                        </div>
                        <div class="shop-flavor">Hymna, káva a plakáty motivují posádku k vyšším výkonům.</div>
                    </div>
                    <button class="shop-button" id="btn-buy-globalbonus">
                        Inspiruj!
                    </button>
                </div>
            </div>
            <div class="rebirth-block">
                <div class="rebirth-title">Rebirth</div>
                <p class="rebirth-text">
                    Rebirth <strong>resetuje energii, coiny a kliky</strong>,
                    ale přidá ti <strong>+1 rebirth</strong> a trvalý
                    násobitel výdělku. Každý rebirth zvýší násobitel o +50&nbsp;%.
                </p>
                <p class="rebirth-text">
                    Aktuální cena rebirthu: <strong id="rebirth-cost-text">1&nbsp;000</strong> coinů.
                </p>
                <button class="shop-button" id="btn-rebirth">
                    Rebirth
                </button>
                <div class="status" id="save-status">
                    Auto-save: <span>čekám…</span>
                </div>
            </div>
        </section>
        <section class="card auth-card">
            <div class="card-header">
                <h2>Profil</h2>
                <div class="badge success">SQLite</div>
            </div>
            <p class="auth-note" id="auth-summary">Cloudové ukládání je dostupné po přihlášení.</p>
            <div class="auth-grid">
                <form id="form-login" class="card" style="padding: 14px;">
                    <div class="card-header" style="margin-bottom: 10px;">
                        <h2 style="font-size: 1rem; margin: 0;">Přihlášení</h2>
                        <div class="badge">Hráč</div>
                    </div>
                    <div class="input-group">
                        <label for="login-username">Uživatelské jméno</label>
                        <input id="login-username" name="username" class="text-input" required>
                    </div>
                    <div class="input-group">
                        <label for="login-password">Heslo</label>
                        <input id="login-password" name="password" type="password" class="text-input" required>
                    </div>
                    <button type="submit" class="secondary-button" style="margin-top: 10px;">Přihlásit</button>
                </form>
                <form id="form-register" class="card" style="padding: 14px;">
                    <div class="card-header" style="margin-bottom: 10px;">
                        <h2 style="font-size: 1rem; margin: 0;">Registrace</h2>
                        <div class="badge">Nový hráč</div>
                    </div>
                    <div class="input-group">
                        <label for="register-username">Uživatelské jméno</label>
                        <input id="register-username" name="username" class="text-input" required>
                    </div>
                    <div class="input-group">
                        <label for="register-password">Heslo</label>
                        <input id="register-password" name="password" type="password" class="text-input" required>
                    </div>
                    <button type="submit" class="secondary-button" style="margin-top: 10px;">Registrovat</button>
                </form>
            </div>
            <div class="auth-note" id="auth-message">
                Nemáš účet? Vytvoř si ho a tvůj postup se uloží na serveru.
            </div>
            <div class="status" style="margin-top: 8px; text-align: left;">
                Uživatel: <span id="auth-user">nepřihlášen</span>
                <button id="btn-logout" class="text-button" style="margin-left: 6px;">Odhlásit</button>
            </div>
        </section>
    </div>
</div>

<script src="game.js"></script>
</body>
</html>
