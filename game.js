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

/** @type {GameState} */
let state = {
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

const dom = {};

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
        window.localStorage.setItem("rebirthGameState", JSON.stringify(stateClone));
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

        if (!response.ok) {
            throw new Error("HTTP status " + response.status);
        }
        const json = await response.json();
        if (json && json.ok) {
            lastSaveSuccessful = true;
            dom.saveStatus.textContent = "OK";
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
        const cached = window.localStorage.getItem("rebirthGameState");
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
        if (!response.ok) {
            throw new Error("HTTP status " + response.status);
        }
        const json = await response.json();
        if (json && json.ok && json.hasState && json.state) {
            Object.assign(state, json.state);
            dom.statusText.textContent = "Stav načten ze serveru.";
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
}

function initEvents() {
    dom.btnClick.addEventListener("click", handleClick);
    dom.btnBuyClick.addEventListener("click", buyClickUpgrade);
    dom.btnBuyEnergy.addEventListener("click", buyEnergyUpgrade);
    dom.btnBuyAutoClicker.addEventListener("click", buyAutoClicker);
    dom.btnBuyAutoPower.addEventListener("click", buyAutoPowerUpgrade);
    dom.btnBuyGlobalBonus.addEventListener("click", buyGlobalMultiplierUpgrade);
    dom.btnRebirth.addEventListener("click", performRebirth);

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
    void loadState();
    window.setInterval(() => {
        applyClickGain(state.autoClickers * state.autoClickerPower);
    }, 1000);
});
