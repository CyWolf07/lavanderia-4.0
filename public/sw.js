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
    event.waitUntil(self.clients.openWindow(url.href));
});
