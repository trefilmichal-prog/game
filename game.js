"use strict";

/**
 * Jednoduchá rebirth klikací hra.
 * Stav se ukládá do PHP session + localStorage pro rychlejší načítání.
 */

/**
 * @typedef {Object} GameState
 * @property {number} clicks
 * @property {number} energy
 * @property {number} coins
 * @property {number} clickPower
 * @property {number} energyPerClick
 * @property {number} rebirths
 * @property {number} rebirthMultiplier
 * @property {number} autoClickers
 * @property {number} autoClickerCost
 * @property {number} autoClickerPower
 * @property {number} autoClickerPowerCost
 * @property {number} globalMultiplier
 * @property {number} globalMultiplierCost
 * @property {number} upgradeClickCost
 * @property {number} upgradeEnergyCost
 * @property {number} rebirthCost
 */

function createDefaultState() {
    return {
        clicks: 0,
        energy: 0,
        coins: 0,
        clickPower: 1,
        energyPerClick: 1,
        rebirths: 0,
        rebirthMultiplier: 1.0,
        autoClickers: 0,
        autoClickerCost: 100,
        autoClickerPower: 1,
        autoClickerPowerCost: 200,
        globalMultiplier: 1.0,
        globalMultiplierCost: 500,
        upgradeClickCost: 10,
        upgradeEnergyCost: 25,
        rebirthCost: 1000
    };
}

/** @type {GameState} */
let state = createDefaultState();
let currentUser = null;

const dom = {};

function getLocalStorageKey() {
    return currentUser ? `rebirthGameState:${currentUser.username}` : "rebirthGameState";
}

function formatNumber(value) {
    if (!Number.isFinite(value)) {
        return "0";
    }
    if (value < 1000) {
        return value.toFixed(0);
    }
    const units = ["", "K", "M", "B", "T"];
    let unitIndex = 0;
    let v = value;
    while (v >= 1000 && unitIndex < units.length - 1) {
        v /= 1000;
        unitIndex += 1;
    }
    const digits = v < 10 ? 2 : (v < 100 ? 1 : 0);
    return v.toFixed(digits) + units[unitIndex];
}

function setAuthMessage(message, isError = false) {
    if (!dom.authMessage) return;
    dom.authMessage.textContent = message;
    dom.authMessage.style.color = isError ? "#fca5a5" : "#e2e8f0";
}

function setCurrentUser(user) {
    currentUser = user;
    if (!dom.authUser || !dom.authSummary || !dom.btnLogout) return;
    dom.authUser.textContent = user ? user.username : "nepřihlášen";
    dom.btnLogout.disabled = !user;
    dom.authSummary.textContent = user
        ? `Přihlášeno jako ${user.username}. Ukládám do SQLite.`
        : "Cloudové ukládání je dostupné po přihlášení.";
}

function updateUI() {
    dom.statClicks.textContent = formatNumber(state.clicks);
    dom.statEnergy.textContent = formatNumber(state.energy);
    dom.statCoins.textContent = formatNumber(state.coins);
    dom.statRebirths.textContent = state.rebirths.toString();
    dom.statClickPower.textContent = state.clickPower.toString();
    dom.statRebirthMult.textContent = state.rebirthMultiplier.toFixed(1) + "x";
    dom.statAutoClickers.textContent = state.autoClickers.toString();
    dom.statAutoPower.textContent = state.autoClickerPower.toFixed(1) + "x";
    dom.statGlobalBonus.textContent = state.globalMultiplier.toFixed(1) + "x";

    dom.costClickUpgrade.textContent = formatNumber(state.upgradeClickCost);
    dom.costEnergyUpgrade.textContent = formatNumber(state.upgradeEnergyCost);
    dom.costAutoClicker.textContent = formatNumber(state.autoClickerCost);
    dom.costAutoPower.textContent = formatNumber(state.autoClickerPowerCost);
    dom.costGlobalBonus.textContent = formatNumber(state.globalMultiplierCost);
    dom.rebirthCostText.textContent = formatNumber(state.rebirthCost);

    const progressFraction = Math.max(0, Math.min(1, state.coins / state.rebirthCost));
    dom.progressBar.style.width = String(progressFraction * 100) + "%";

    dom.btnRebirth.disabled = state.coins < state.rebirthCost;
    dom.btnBuyClick.disabled = state.coins < state.upgradeClickCost;
    dom.btnBuyEnergy.disabled = state.coins < state.upgradeEnergyCost;
    dom.btnBuyAutoClicker.disabled = state.coins < state.autoClickerCost;
    dom.btnBuyAutoPower.disabled = state.coins < state.autoClickerPowerCost;
    dom.btnBuyGlobalBonus.disabled = state.coins < state.globalMultiplierCost;
}

function getRebirthGain() {
    return 1 + state.rebirths * 0.5;
}

function handleClick() {
    applyClickGain(1);
}

function applyClickGain(clicksGenerated) {
    if (clicksGenerated <= 0) {
        return;
    }
    const baseEnergy = state.clickPower * state.energyPerClick * clicksGenerated;
    const gainMultiplier = state.rebirthMultiplier * getRebirthGain() * state.globalMultiplier;
    const gainedEnergy = baseEnergy * gainMultiplier;
    const gainedCoins = baseEnergy * gainMultiplier;

    state.clicks += clicksGenerated;
    state.energy += gainedEnergy;
    state.coins += gainedCoins;

    updateUI();
    scheduleSave();
}

function buyClickUpgrade() {
    if (state.coins < state.upgradeClickCost) {
        return;
    }
    state.coins -= state.upgradeClickCost;
    state.clickPower += 1;
    state.upgradeClickCost = Math.floor(state.upgradeClickCost * 1.75);
    updateUI();
    scheduleSave();
}

function buyEnergyUpgrade() {
    if (state.coins < state.upgradeEnergyCost) {
        return;
    }
    state.coins -= state.upgradeEnergyCost;
    state.energyPerClick += 1;
    state.upgradeEnergyCost = Math.floor(state.upgradeEnergyCost * 2.0);
    updateUI();
    scheduleSave();
}

function buyAutoClicker() {
    if (state.coins < state.autoClickerCost) {
        return;
    }
    state.coins -= state.autoClickerCost;
    state.autoClickers += 1;
    state.autoClickerCost = Math.floor(state.autoClickerCost * 2.5);
    updateUI();
    scheduleSave();
}

function buyAutoPowerUpgrade() {
    if (state.coins < state.autoClickerPowerCost) {
        return;
    }
    state.coins -= state.autoClickerPowerCost;
    state.autoClickerPower = parseFloat((state.autoClickerPower + 0.5).toFixed(1));
    state.autoClickerPowerCost = Math.floor(state.autoClickerPowerCost * 2.8);
    updateUI();
    scheduleSave();
}

function buyGlobalMultiplierUpgrade() {
    if (state.coins < state.globalMultiplierCost) {
        return;
    }
    state.coins -= state.globalMultiplierCost;
    state.globalMultiplier = parseFloat((state.globalMultiplier + 0.5).toFixed(1));
    state.globalMultiplierCost = Math.floor(state.globalMultiplierCost * 2.9);
    updateUI();
    scheduleSave();
}

function performRebirth() {
    if (state.coins < state.rebirthCost) {
        return;
    }
    state.rebirths += 1;
    state.rebirthMultiplier = 1.0 + state.rebirths * 0.5;
    state.rebirthCost = Math.floor(state.rebirthCost * 2.25);

    state.clicks = 0;
    state.energy = 0;
    state.coins = 0;

    updateUI();
    scheduleSave();
}

let saveTimeoutId = null;
let lastSaveSuccessful = true;

function cloneStateSafe(source) {
    if (typeof structuredClone === "function") {
        return structuredClone(source);
    }
    // Fallback for browsers without structuredClone support
    return JSON.parse(JSON.stringify(source));
}

function scheduleSave() {
    if (saveTimeoutId !== null) {
        window.clearTimeout(saveTimeoutId);
    }
    dom.saveStatus.textContent = "Ukládám…";
    saveTimeoutId = window.setTimeout(() => {
        saveTimeoutId = null;
        void saveState();
    }, 400);
}

async function saveState() {
    const stateClone = cloneStateSafe(state);
    try {
        window.localStorage.setItem(getLocalStorageKey(), JSON.stringify(stateClone));
    } catch (e) {
        console.warn("LocalStorage save failed:", e);
    }

    try {
        const response = await fetch("api.php?action=save", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify(stateClone)
        });

        if (response.status === 401) {
            setCurrentUser(null);
            setAuthMessage("Přihlášení vypršelo, přihlas se znovu.", true);
            lastSaveSuccessful = false;
            dom.saveStatus.textContent = "nepřihlášen";
            return;
        }

        if (!response.ok) {
            throw new Error("HTTP status " + response.status);
        }
        const json = await response.json();
        if (json && json.ok) {
            lastSaveSuccessful = true;
            dom.saveStatus.textContent = json.mode === "guest" ? "host / session" : "OK";
        } else {
            lastSaveSuccessful = false;
            dom.saveStatus.textContent = "chyba serveru";
        }
    } catch (err) {
        console.error("Save error:", err);
        lastSaveSuccessful = false;
        dom.saveStatus.textContent = "offline / chyba";
    }
}

async function loadState() {
    dom.statusText.textContent = "Načítám stav…";

    let loadedFromLocal = false;
    try {
        const cached = window.localStorage.getItem(getLocalStorageKey());
        if (cached) {
            const parsed = JSON.parse(cached);
            if (parsed && typeof parsed === "object") {
                Object.assign(state, parsed);
                loadedFromLocal = true;
            }
        }
    } catch (e) {
        console.warn("LocalStorage load failed:", e);
    }

    try {
        const response = await fetch("api.php?action=load", { method: "GET" });
        if (response.status === 401) {
            setCurrentUser(null);
            setAuthMessage("Přihlášení vypršelo, přihlas se znovu.", true);
            dom.statusText.textContent = loadedFromLocal
                ? "Přihlášení vypršelo, načetl jsem lokální stav."
                : "Přihlášení vypršelo.";
            return;
        }

        if (!response.ok) {
            throw new Error("HTTP status " + response.status);
        }
        const json = await response.json();
        if (json && json.ok && json.hasState && json.state) {
            Object.assign(state, json.state);
            dom.statusText.textContent = json.mode === "guest"
                ? "Stav načten ze session (bez přihlášení)."
                : "Stav načten ze serveru.";
        } else if (loadedFromLocal) {
            dom.statusText.textContent = "Stav načten z lokálního úložiště.";
        } else {
            dom.statusText.textContent = "Nová hra připravena.";
        }
    } catch (err) {
        console.error("Load error:", err);
        if (loadedFromLocal) {
            dom.statusText.textContent = "Server nedostupný, použit lokální stav.";
        } else {
            dom.statusText.textContent = "Nelze načíst ze serveru, start nové hry.";
        }
    }

    updateUI();
}

async function fetchCurrentUser() {
    try {
        const response = await fetch("api.php?action=me");
        if (!response.ok) {
            throw new Error("HTTP status " + response.status);
        }
        const json = await response.json();
        if (json && json.ok) {
            setCurrentUser(json.user);
            return json.user;
        }
        return null;
    } catch (e) {
        console.error("User check failed", e);
        setAuthMessage("Nepodařilo se ověřit přihlášení.", true);
        return null;
    }
}

async function handleAuthRequest(action, username, password) {
    const response = await fetch(`api.php?action=${action}`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ username, password })
    });

    const json = await response.json();
    if (!response.ok || !json.ok) {
        const message = json?.error || "Neznámá chyba.";
        setAuthMessage(message, true);
        return null;
    }

    setAuthMessage(action === "login" ? "Přihlášeno." : "Registrace hotova.");
    setCurrentUser(json.user);
    return json.user;
}

async function handleLogin(event) {
    event.preventDefault();
    const username = dom.loginUsername.value.trim();
    const password = dom.loginPassword.value;
    if (!username || !password) {
        setAuthMessage("Vyplň přihlašovací údaje.", true);
        return;
    }
    const user = await handleAuthRequest("login", username, password);
    if (user) {
        state = createDefaultState();
        await loadState();
    }
}

async function handleRegister(event) {
    event.preventDefault();
    const username = dom.registerUsername.value.trim();
    const password = dom.registerPassword.value;
    if (!username || !password) {
        setAuthMessage("Vyplň registrační údaje.", true);
        return;
    }
    const user = await handleAuthRequest("register", username, password);
    if (user) {
        state = createDefaultState();
        await loadState();
    }
}

async function handleLogout(event) {
    event.preventDefault();
    if (!currentUser) {
        return;
    }
    try {
        await fetch("api.php?action=logout", { method: "POST" });
    } catch (e) {
        console.warn("Logout failed", e);
    }
    setCurrentUser(null);
    setAuthMessage("Odhlášen.");
    dom.statusText.textContent = "Nepřihlášený režim.";
    dom.saveStatus.textContent = "nepřihlášen";
}

function initDom() {
    dom.statClicks = document.getElementById("stat-clicks");
    dom.statEnergy = document.getElementById("stat-energy");
    dom.statCoins = document.getElementById("stat-coins");
    dom.statRebirths = document.getElementById("stat-rebirths");
    dom.statClickPower = document.getElementById("stat-clickPower");
    dom.statRebirthMult = document.getElementById("stat-rebirthMult");
    dom.statAutoClickers = document.getElementById("stat-autoclickers");
    dom.costClickUpgrade = document.getElementById("cost-click-upgrade");
    dom.costEnergyUpgrade = document.getElementById("cost-energy-upgrade");
    dom.costAutoClicker = document.getElementById("cost-autoclicker");
    dom.costAutoPower = document.getElementById("cost-autopower");
    dom.costGlobalBonus = document.getElementById("cost-globalbonus");
    dom.rebirthCostText = document.getElementById("rebirth-cost-text");
    dom.progressBar = document.getElementById("progress-bar");
    dom.btnClick = document.getElementById("btn-click");
    dom.btnBuyClick = document.getElementById("btn-buy-click");
    dom.btnBuyEnergy = document.getElementById("btn-buy-energy");
    dom.btnBuyAutoClicker = document.getElementById("btn-buy-autoclicker");
    dom.btnBuyAutoPower = document.getElementById("btn-buy-autopower");
    dom.btnBuyGlobalBonus = document.getElementById("btn-buy-globalbonus");
    dom.btnRebirth = document.getElementById("btn-rebirth");
    dom.statusText = document.getElementById("status-text");
    dom.saveStatus = document.querySelector("#save-status span");
    dom.statAutoPower = document.getElementById("stat-autopower");
    dom.statGlobalBonus = document.getElementById("stat-globalbonus");
    dom.authUser = document.getElementById("auth-user");
    dom.authSummary = document.getElementById("auth-summary");
    dom.authMessage = document.getElementById("auth-message");
    dom.btnLogout = document.getElementById("btn-logout");
    dom.loginForm = document.getElementById("form-login");
    dom.registerForm = document.getElementById("form-register");
    dom.loginUsername = document.getElementById("login-username");
    dom.loginPassword = document.getElementById("login-password");
    dom.registerUsername = document.getElementById("register-username");
    dom.registerPassword = document.getElementById("register-password");
}

function initEvents() {
    dom.btnClick.addEventListener("click", handleClick);
    dom.btnBuyClick.addEventListener("click", buyClickUpgrade);
    dom.btnBuyEnergy.addEventListener("click", buyEnergyUpgrade);
    dom.btnBuyAutoClicker.addEventListener("click", buyAutoClicker);
    dom.btnBuyAutoPower.addEventListener("click", buyAutoPowerUpgrade);
    dom.btnBuyGlobalBonus.addEventListener("click", buyGlobalMultiplierUpgrade);
    dom.btnRebirth.addEventListener("click", performRebirth);
    dom.loginForm.addEventListener("submit", handleLogin);
    dom.registerForm.addEventListener("submit", handleRegister);
    dom.btnLogout.addEventListener("click", handleLogout);

    window.addEventListener("beforeunload", () => {
        if (!lastSaveSuccessful) {
            void saveState();
        }
    });
}

window.addEventListener("DOMContentLoaded", () => {
    initDom();
    initEvents();
    updateUI();

    (async () => {
        await fetchCurrentUser();
        await loadState();
        window.setInterval(() => {
            applyClickGain(state.autoClickers * state.autoClickerPower);
        }, 1000);
    })();
});
