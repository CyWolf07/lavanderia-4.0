(() => {
    const modes = ['standalone', 'fullscreen', 'minimal-ui'];
    const installed = () => modes.some(mode => window.matchMedia(`(display-mode: ${mode})`).matches)
        || navigator.standalone === true
        || document.referrer.startsWith('android-app://co.lavanderia.exclusiva');
    let app = installed();
    // Keep context through login and print pages, without marking ordinary browser sessions.
    try {
        app = app || sessionStorage.getItem('lavanderia-app') === '1';
        if (app) sessionStorage.setItem('lavanderia-app', '1');
    } catch { /* Storage can be unavailable in restricted browsers. */ }
    const update = () => {
        app = app || installed();
        document.documentElement.classList.toggle('installed-app', app);
    };
    update();
    window.addEventListener('appinstalled', () => { app = true; update(); });
    modes.forEach(mode => window.matchMedia(`(display-mode: ${mode})`).addEventListener('change', update));
    document.addEventListener('click', event => {
        if (!app || event.defaultPrevented) return;
        const link = event.target.closest('a[href]');
        if (!link || link.hasAttribute('download')) return;
        const url = new URL(link.href, location.href);
        if (url.origin === location.origin) link.target = '_self';
    }, true);
    document.addEventListener('submit', event => {
        if (!app) return;
        const form = event.target;
        if (new URL(form.action, location.href).origin === location.origin) {
            form.target = '_self';
            if (event.submitter?.hasAttribute('formtarget')) event.submitter.formTarget = '_self';
        }
    }, true);
})();
