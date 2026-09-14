let installPrompt;
window.addEventListener('beforeinstallprompt', event => {
    event.preventDefault();
    if (document.documentElement.classList.contains('installed-app')) return;
    installPrompt = event;
    const button = document.getElementById('puntual-install');
    if (button) button.hidden = false;
});
document.getElementById('puntual-install')?.addEventListener('click', async event => {
    if (!installPrompt) return;
    await installPrompt.prompt();
    installPrompt = null;
    event.target.hidden = true;
});

const registration = 'serviceWorker' in navigator && window.isSecureContext
    ? navigator.serviceWorker.register('/sw.js') : null;
registration?.catch(() => {});
const button = document.getElementById('puntual-push');
const feedback = document.getElementById('puntual-feedback');
const publicKey = document.getElementById('puntual')?.dataset.pushKey;
const supported = registration && 'PushManager' in window && 'Notification' in window;
const request = async (method, data) => {
    const response = await fetch('/puntual/suscripciones', {
        method, credentials: 'same-origin', headers: {
            'Content-Type': 'application/json', 'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }, body: JSON.stringify(data)
    });
    if (!response.ok) throw new Error('No se pudo guardar el permiso. Intenta de nuevo.');
};
if (button) {
    if (!supported || !publicKey) {
        button.disabled = true;
        feedback.textContent = !supported ? 'Avisos del dispositivo no disponibles en este navegador.' : 'Avisos del dispositivo pendientes de activación en el servidor.';
    } else {
        let enabled = false;
        navigator.serviceWorker.ready.then(async worker => {
            const sub = await worker.pushManager.getSubscription();
            if (sub && Notification.permission === 'granted') {
                await request('POST', sub.toJSON());
                enabled = true;
                button.textContent = 'Desactivar avisos';
            }
        }).catch(error => { feedback.textContent = error.message; });
        button.addEventListener('click', async () => {
            button.disabled = true;
            try {
                const worker = await navigator.serviceWorker.ready;
                let sub = await worker.pushManager.getSubscription();
                if (enabled && sub) {
                    await request('DELETE', { endpoint: sub.endpoint });
                    await sub.unsubscribe();
                    enabled = false;
                } else {
                    const permission = await Notification.requestPermission();
                    if (permission !== 'granted') throw new Error('Permiso de notificaciones no concedido.');
                    const base64 = publicKey.replace(/-/g, '+').replace(/_/g, '/');
                    const key = Uint8Array.from(atob(base64.padEnd(Math.ceil(base64.length / 4) * 4, '=')), c => c.charCodeAt(0));
                    sub = sub || await worker.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: key });
                    await request('POST', sub.toJSON());
                    enabled = true;
                }
                button.textContent = enabled ? 'Desactivar avisos' : 'Activar avisos';
                feedback.textContent = enabled ? 'Avisos activados en este dispositivo.' : 'Avisos desactivados en este dispositivo.';
            } catch (error) {
                feedback.textContent = error.message;
            } finally { button.disabled = false; }
        });
    }
}
