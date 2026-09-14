import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

const source = readFileSync(new URL('../../public/app-context.js', import.meta.url), 'utf8');
function page({ standalone = false, referrer = '', stored = null, blocked = false } = {}) {
    const events = {}, windowEvents = {}, classes = new Set();
    vm.runInNewContext(source, {
        window: {
            matchMedia: query => ({ matches: standalone && query === '(display-mode: standalone)', addEventListener() {} }),
            addEventListener: (name, fn) => { windowEvents[name] = fn; },
        },
        navigator: {}, URL, location: { href: 'https://example.test/puntual', origin: 'https://example.test' },
        sessionStorage: {
            getItem: () => { if (blocked) throw Error(); return stored; },
            setItem: (_, value) => { stored = value; },
        },
        document: {
            referrer,
            documentElement: { classList: { toggle: (name, enabled) => enabled ? classes.add(name) : classes.delete(name) } },
            addEventListener: (name, fn) => { events[name] = fn; },
        },
    });
    return { events, windowEvents, classes, stored: () => stored };
}
test('detects installed app and keeps context through login and print navigation', () => {
    for (const options of [{ standalone: true }, { referrer: 'android-app://co.lavanderia.exclusiva' }]) {
        const current = page(options);
        assert.ok(current.classes.has('installed-app'));
        assert.ok(page({ stored: current.stored() }).classes.has('installed-app'));
    }
});
test('ordinary browser still offers APK; installation updates the current page', () => {
    const current = page();
    assert.equal(current.classes.has('installed-app'), false);
    current.windowEvents.appinstalled();
    assert.ok(current.classes.has('installed-app'));
});
test('blocked storage does not break installed detection', () => {
    assert.ok(page({ standalone: true, blocked: true }).classes.has('installed-app'));
});
test('internal links stay in app; external links and downloads are unchanged', () => {
    for (const [app, href, download, expected] of [
        [true, 'https://example.test/print', false, '_self'],
        [true, 'https://wa.me/123', false, '_blank'],
        [true, 'https://example.test/file.pdf', true, '_blank'],
        [false, 'https://example.test/print', false, '_blank'],
    ]) {
        const link = { href, target: '_blank', hasAttribute: () => download };
        page({ standalone: app }).events.click({ target: { closest: () => link } });
        assert.equal(link.target, expected);
    }
});
test('login and logout forms submit in the visible app window', () => {
    const form = { action: 'https://example.test/login', target: '_blank' };
    const submitter = { hasAttribute: () => true, formTarget: '_blank' };
    page({ standalone: true }).events.submit({ target: form, submitter });
    assert.equal(form.target, '_self');
    assert.equal(submitter.formTarget, '_self');
});

const workerSource = readFileSync(new URL('../../public/sw.js', import.meta.url), 'utf8');
async function notification(windows, url = '/puntual') {
    const events = {}, opened = [];
    vm.runInNewContext(workerSource, {
        URL, self: {
            location: { origin: 'https://example.test' },
            addEventListener: (name, fn) => { events[name] = fn; },
            clients: { matchAll: async () => windows, openWindow: async href => opened.push(href) },
        },
    });
    let pending;
    events.notificationclick({ notification: { close() {}, data: { url } }, waitUntil: value => { pending = value; } });
    await pending;
    return opened;
}
test('notification reuses and focuses existing window instead of opening login underneath', async () => {
    let focused = false, navigated;
    const client = {
        url: 'https://example.test/login', focused: true,
        navigate: async href => { navigated = href; return client; },
        focus: async () => { focused = true; },
    };
    assert.deepEqual(await notification([client]), []);
    assert.equal(navigated, 'https://example.test/puntual');
    assert.ok(focused);
});
test('notification opens only if no reusable window remains and rejects external destinations', async () => {
    assert.deepEqual(await notification([]), ['https://example.test/puntual']);
    assert.deepEqual(await notification([], 'https://evil.test'), []);
    assert.deepEqual(await notification([{ url: 'https://example.test/', navigate: async () => null }]), ['https://example.test/puntual']);
});
