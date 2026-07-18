(function () {
    "use strict";

    const CONSENT_VERSION = 1;
    const COOKIE_NAME = "csf_cookie_consent";
    const MAX_AGE = 60 * 60 * 24 * 180;
    const panel = document.querySelector("[data-cookie-consent]");
    if (!panel) {
        return;
    }

    const summary = panel.querySelector("[data-cookie-summary]");
    const settings = panel.querySelector("[data-cookie-settings-panel]");
    const form = panel.querySelector("[data-cookie-form]");
    const settingsLinks = document.querySelectorAll("[data-cookie-settings]");
    const optionalNames = ["preferences", "analytics", "advertising"];

    let hasExistingChoice = false;

    function readCookie(name) {
        return document.cookie
            .split("; ")
            .find((row) => row.startsWith(`${name}=`))
            ?.split("=")
            .slice(1)
            .join("=") || "";
    }

    function readConsent() {
        const raw = readCookie(COOKIE_NAME);
        if (!raw) {
            return null;
        }
        try {
            const consent = JSON.parse(decodeURIComponent(raw));
            return consent && consent.version === CONSENT_VERSION ? consent : null;
        } catch (error) {
            return null;
        }
    }

    function writeConsent(consent) {
        const value = encodeURIComponent(JSON.stringify(consent));
        document.cookie = `${COOKIE_NAME}=${value}; Max-Age=${MAX_AGE}; Path=/; SameSite=Lax`;
    }

    function buildConsent(overrides) {
        const consent = {
            version: CONSENT_VERSION,
            necessary: true,
            preferences: false,
            analytics: false,
            advertising: false,
            accepted: [],
            rejected: [],
            date: new Date().toISOString()
        };
        Object.assign(consent, overrides);
        consent.accepted = Object.keys(consent).filter((key) => optionalNames.includes(key) && consent[key]);
        consent.rejected = optionalNames.filter((key) => !consent[key]);
        return consent;
    }

    function syncForm(consent) {
        if (!form) {
            return;
        }
        optionalNames.forEach((name) => {
            const input = form.elements[name];
            if (input) {
                input.checked = Boolean(consent?.[name]);
            }
        });
    }

    function hidePanel() {
        panel.hidden = true;
        document.body.classList.remove("cookie-open");
    }

    function showSummary() {
        panel.hidden = false;
        summary.hidden = false;
        settings.hidden = true;
        document.body.classList.add("cookie-open");
    }

    function showSettings() {
        const current = readConsent();
        hasExistingChoice = Boolean(current);
        syncForm(current || buildConsent({}));
        panel.hidden = false;
        summary.hidden = true;
        settings.hidden = false;
        document.body.classList.add("cookie-open");
        settings.querySelector("button, input")?.focus();
    }

    function applyConsent(consent) {
        if (consent.preferences) {
            loadPreferenceServices();
        }
        if (consent.analytics) {
            loadAnalyticsServices();
        }
        if (consent.advertising) {
            loadAdvertisingServices();
        }
    }

    function saveConsent(consent) {
        writeConsent(consent);
        applyConsent(consent);
        hidePanel();
    }

    function acceptAll() {
        saveConsent(buildConsent({ preferences: true, analytics: true, advertising: true }));
    }

    function rejectOptional() {
        saveConsent(buildConsent({ preferences: false, analytics: false, advertising: false }));
    }

    function loadPreferenceServices() {
        window.CSFCookieServices = window.CSFCookieServices || {};
        window.CSFCookieServices.preferences = true;
    }

    function loadAnalyticsServices() {
        window.CSFCookieServices = window.CSFCookieServices || {};
        if (window.CSFCookieServices.analytics) {
            return;
        }
        window.CSFCookieServices.analytics = true;
    }

    function loadAdvertisingServices() {
        window.CSFCookieServices = window.CSFCookieServices || {};
        if (window.CSFCookieServices.advertising) {
            return;
        }
        window.CSFCookieServices.advertising = true;
    }

    panel.addEventListener("click", (event) => {
        if (event.target.closest("[data-cookie-accept]")) {
            acceptAll();
        } else if (event.target.closest("[data-cookie-reject]")) {
            rejectOptional();
        } else if (event.target.closest("[data-cookie-configure]")) {
            showSettings();
        } else if (event.target.closest("[data-cookie-cancel]")) {
            if (hasExistingChoice) {
                hidePanel();
            } else {
                showSummary();
            }
        }
    });

    settingsLinks.forEach((link) => {
        link.addEventListener("click", (event) => {
            event.preventDefault();
            showSettings();
        });
    });

    form?.addEventListener("submit", (event) => {
        event.preventDefault();
        saveConsent(buildConsent({
            preferences: Boolean(form.elements.preferences?.checked),
            analytics: Boolean(form.elements.analytics?.checked),
            advertising: Boolean(form.elements.advertising?.checked)
        }));
    });

    const stored = readConsent();
    if (stored) {
        hasExistingChoice = true;
        applyConsent(stored);
    } else {
        showSummary();
    }

    window.CSFCookieConsent = {
        read: readConsent,
        configure: showSettings,
        acceptAll,
        rejectOptional,
        version: CONSENT_VERSION
    };
}());
