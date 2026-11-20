import './bootstrap';
import Alpine from 'alpinejs';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Alpine = Alpine;
Alpine.start();

// Suscripción opcional al WebSocket público de Reverb para actualizaciones en vivo
const reverbKey = import.meta.env.VITE_REVERB_APP_KEY;
const channelName = document.documentElement.dataset.broadcastChannel || 'beacon.news';

const emitWsStatus = (state) => {
    window.dispatchEvent(new CustomEvent('beacon:ws-status', {
        detail: {
            state,
            connected: state === 'connected',
        },
    }));
};

if (reverbKey) {
    window.Pusher = Pusher;
    emitWsStatus('connecting');

    const echo = new Echo({
        broadcaster: 'reverb',
        key: reverbKey,
        wsHost: import.meta.env.VITE_REVERB_HOST || window.location.hostname,
        wsPort: Number(import.meta.env.VITE_REVERB_PORT || 8080),
        wssPort: Number(import.meta.env.VITE_REVERB_PORT || 443),
        forceTLS: (import.meta.env.VITE_REVERB_SCHEME || window.location.protocol.replace(':', '')) === 'https',
        enabledTransports: ['ws', 'wss'],
    });

    window.Echo = echo;

    const pusherConnection = echo.connector?.pusher?.connection;
    if (pusherConnection) {
        pusherConnection.bind('connecting', () => emitWsStatus('connecting'));
        pusherConnection.bind('connected', () => emitWsStatus('connected'));
        pusherConnection.bind('disconnected', () => emitWsStatus('offline'));
        pusherConnection.bind('unavailable', () => emitWsStatus('offline'));
        pusherConnection.bind('failed', () => emitWsStatus('offline'));
    }

    echo.channel(channelName).listen('.news.created', (payload) => {
        if (payload && payload.item) {
            window.dispatchEvent(new CustomEvent('beacon:news-created', { detail: payload.item }));
        }
    });
} else {
    emitWsStatus('offline');
}
