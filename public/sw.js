// Never cache authenticated pages or client/order data.
self.addEventListener('install', () => self.skipWaiting());
self.addEventListener('activate', event => event.waitUntil(self.clients.claim()));
self.addEventListener('fetch', event => {
    if (event.request.mode === 'navigate') {
        event.respondWith(fetch(event.request).catch(() => new Response(
            '<!doctype html><html lang="es"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Sin conexión</title><body style="font-family:system-ui;padding:32px"><h1>Sin conexión</h1><p>Vuelve a conectarte para consultar tus entregas.</p><button onclick="location.reload()">Reintentar</button></body></html>',
            { headers: { 'Content-Type': 'text/html; charset=utf-8', 'Cache-Control': 'no-store' } }
        )));
    }
});
self.addEventListener('push', event => {
    let data;
    try { data = event.data.json(); } catch { return; }
    event.waitUntil(self.registration.showNotification(data.title || 'Puntual', {
        body: data.body, tag: data.tag, icon: '/images/logo-lavanderia-exclusiva.png',
        data: { url: data.url || '/puntual' }
    }));
});
self.addEventListener('notificationclick', event => {
    event.notification.close();
    const url = new URL(event.notification.data?.url || '/puntual', self.location.origin);
    if (url.origin !== self.location.origin) return;
    event.waitUntil((async () => {
        const windows = await self.clients.matchAll({ type: 'window', includeUncontrolled: true });
        const candidates = windows.filter(client => new URL(client.url).origin === url.origin);
        candidates.sort((a, b) => Number(b.focused) - Number(a.focused)
            || Number(b.visibilityState === 'visible') - Number(a.visibilityState === 'visible'));
        for (const client of candidates) {
            try {
                const current = client.url === url.href ? client : await client.navigate(url.href);
                if (current) { await current.focus(); return; }
            } catch { /* A window may close while handling the notification. */ }
        }
        await self.clients.openWindow(url.href);
    })());
});
