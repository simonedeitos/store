/**
 * AirDirector Client - Language Manager
 * Auto-discovery & application of translations
 */
const LanguageManager = (() => {
    let _translations = {};
    let _currentLang = (typeof CLIENT_CONFIG !== 'undefined' ? CLIENT_CONFIG.lang : null)
        || localStorage.getItem('adc_lang') || 'it';

    function get(key, fallback) {
        const parts = key.split('.');
        let val = _translations;
        for (const p of parts) {
            if (val && typeof val === 'object' && p in val) { val = val[p]; }
            else { return fallback !== undefined ? fallback : key; }
        }
        return typeof val === 'string' ? val : (fallback !== undefined ? fallback : key);
    }

    function apply() {
        document.querySelectorAll('[data-lang]').forEach(el => {
            const key = el.getAttribute('data-lang');
            const translation = get(key);
            if (translation !== key) el.textContent = translation;
        });
        document.querySelectorAll('[data-lang-placeholder]').forEach(el => {
            const key = el.getAttribute('data-lang-placeholder');
            const translation = get(key);
            if (translation !== key) el.placeholder = translation;
        });
        document.querySelectorAll('[data-lang-title]').forEach(el => {
            const key = el.getAttribute('data-lang-title');
            const translation = get(key);
            if (translation !== key) el.title = translation;
        });
    }

    async function load(lang) {
        if (!lang) lang = _currentLang;
        try {
            const siteUrl = (typeof CLIENT_CONFIG !== 'undefined' ? CLIENT_CONFIG.siteUrl : '') || '';
            const res = await fetch(`${siteUrl}/language/${lang}.json?v=${Date.now()}`);
            if (!res.ok) throw new Error('Lang file not found');
            _translations = await res.json();
            _currentLang = lang;
            localStorage.setItem('adc_lang', lang);
            apply();
            document.dispatchEvent(new CustomEvent('langLoaded', { detail: { lang } }));
        } catch (e) {
            if (lang !== 'it') {
                console.warn(`Language ${lang} not found, falling back to 'it'`);
                await load('it');
            }
        }
    }

    function getCurrent() { return _currentLang; }
    function getAll() { return _translations; }

    return { load, get, apply, getCurrent, getAll };
})();

window.LanguageManager = LanguageManager;
