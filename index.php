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
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: #050816;
            color: #f9fafb;
            display: flex;
            justify-content: center;
            align-items: stretch;
            min-height: 100vh;
        }
        .app {
            max-width: 1120px;
            width: 100%;
            padding: 24px;
            display: grid;
            grid-template-columns: 2fr 1.5fr;
            gap: 24px;
        }
        @media (max-width: 900px) {
            .app {
                grid-template-columns: 1fr;
            }
        }
        .card {
            background: radial-gradient(circle at top left, #1e293b, #020617);
            border-radius: 16px;
            padding: 16px 20px;
            box-shadow: 0 18px 45px rgba(15,23,42,0.8);
            border: 1px solid rgba(148,163,184,0.25);
        }
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
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 10px;
            margin-bottom: 12px;
        }
        .stat {
            padding: 8px 10px;
            border-radius: 12px;
            background: rgba(15,23,42,0.85);
            border: 1px solid rgba(30,64,175,0.7);
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
            border-radius: 999px;
            border: none;
            cursor: pointer;
            font-size: 1.1rem;
            font-weight: 600;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            margin: 12px 0 4px;
            background: radial-gradient(circle at top, #22c55e, #16a34a);
            color: #022c22;
            transition: transform 80ms ease-out, box-shadow 80ms ease-out, filter 80ms ease-out;
            box-shadow: 0 12px 35px rgba(34,197,94,0.5);
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
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.16em;
            color: #64748b;
            margin-bottom: 8px;
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
            padding: 10px 12px;
            border-radius: 12px;
            background: rgba(15,23,42,0.9);
            border: 1px solid rgba(30,64,175,0.6);
        }
        .shop-main {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        .shop-title {
            font-size: 0.9rem;
        }
        .shop-subtitle {
            font-size: 0.75rem;
            color: #94a3b8;
        }
        .shop-meta {
            font-size: 0.8rem;
            color: #e5e7eb;
        }
        .shop-button {
            padding: 8px 14px;
            border-radius: 999px;
            border: none;
            cursor: pointer;
            font-size: 0.8rem;
            font-weight: 600;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            background: #1d4ed8;
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
    </style>
</head>
<body>
<div class="app">
    <section class="card">
        <div class="card-header">
            <h2>Rebirth Clicker</h2>
            <div class="badge">PHP 8.3 · Vanilla JS</div>
        </div>
        <div class="stats-grid">
            <div class="stat">
                <div class="stat-label">Kliknutí</div>
                <div class="stat-value" id="stat-clicks">0</div>
            </div>
            <div class="stat">
                <div class="stat-label">Energie</div>
                <div class="stat-value" id="stat-energy">0</div>
            </div>
            <div class="stat">
                <div class="stat-label">Coiny</div>
                <div class="stat-value" id="stat-coins">0</div>
            </div>
            <div class="stat">
                <div class="stat-label">Rebirthy</div>
                <div class="stat-value" id="stat-rebirths">0</div>
            </div>
            <div class="stat">
                <div class="stat-label">Síla kliknutí</div>
                <div class="stat-value" id="stat-clickPower">1</div>
            </div>
            <div class="stat">
                <div class="stat-label">Rebirth násobitel</div>
                <div class="stat-value" id="stat-rebirthMult">1.0x</div>
            </div>
            <div class="stat">
                <div class="stat-label">Autoclickery</div>
                <div class="stat-value" id="stat-autoclickers">0</div>
            </div>
        </div>
        <button id="btn-click" class="primary-button">
            Klikni pro energii
        </button>
        <div class="sub" id="click-info">
            Každé kliknutí generuje energii a coiny. Rebirth ti resetne progres, ale trvale buffne všechny výdělky.
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
</div>

<script src="game.js"></script>
</body>
</html>
