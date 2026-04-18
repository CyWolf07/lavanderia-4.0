import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

const syncDeviceContext = () => {
    const coarsePointer = window.matchMedia('(pointer: coarse)').matches;
    const touchPoints = navigator.maxTouchPoints || 0;
    const touchDevice = coarsePointer || touchPoints > 0;
    const mobileViewport = window.innerWidth < 1024;

    document.documentElement.dataset.deviceMode = touchDevice
        ? (mobileViewport ? 'mobile-touch' : 'touch')
        : (mobileViewport ? 'compact' : 'desktop');

    document.documentElement.classList.toggle('device-touch', touchDevice);
    document.documentElement.classList.toggle('device-mobile', mobileViewport);
};

syncDeviceContext();
window.addEventListener('resize', syncDeviceContext);
