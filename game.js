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

    dom.costClickUpgrade.textContent = formatNumber(state.upgradeClickCost);
    dom.costEnergyUpgrade.textContent = formatNumber(state.upgradeEnergyCost);
    dom.rebirthCostText.textContent = formatNumber(state.rebirthCost);

    const progressFraction = Math.max(0, Math.min(1, state.coins / state.rebirthCost));
    dom.progressBar.style.width = String(progressFraction * 100) + "%";

    dom.btnRebirth.disabled = state.coins < state.rebirthCost;
    dom.btnBuyClick.disabled = state.coins < state.upgradeClickCost;
    dom.btnBuyEnergy.disabled = state.coins < state.upgradeEnergyCost;
}

function getRebirthGain() {
    return 1 + state.rebirths * 0.5;
}

function handleClick() {
    const baseEnergy = state.clickPower * state.energyPerClick;
    const gainMultiplier = state.rebirthMultiplier * getRebirthGain();
    const gainedEnergy = baseEnergy * gainMultiplier;
    const gainedCoins = baseEnergy * gainMultiplier;

    state.clicks += 1;
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
    const stateClone = structuredClone(state);
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
            dom.saveStatus.innerHTML = 'Auto-save: <span>OK</span>';
        } else {
            lastSaveSuccessful = false;
            dom.saveStatus.textContent = "Auto-save: chyba serveru";
        }
    } catch (err) {
        console.error("Save error:", err);
        lastSaveSuccessful = false;
        dom.saveStatus.textContent = "Auto-save: offline / chyba";
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
    dom.costClickUpgrade = document.getElementById("cost-click-upgrade");
    dom.costEnergyUpgrade = document.getElementById("cost-energy-upgrade");
    dom.rebirthCostText = document.getElementById("rebirth-cost-text");
    dom.progressBar = document.getElementById("progress-bar");
    dom.btnClick = document.getElementById("btn-click");
    dom.btnBuyClick = document.getElementById("btn-buy-click");
    dom.btnBuyEnergy = document.getElementById("btn-buy-energy");
    dom.btnRebirth = document.getElementById("btn-rebirth");
    dom.statusText = document.getElementById("status-text");
    dom.saveStatus = document.querySelector("#save-status span");
}

function initEvents() {
    dom.btnClick.addEventListener("click", handleClick);
    dom.btnBuyClick.addEventListener("click", buyClickUpgrade);
    dom.btnBuyEnergy.addEventListener("click", buyEnergyUpgrade);
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
});
