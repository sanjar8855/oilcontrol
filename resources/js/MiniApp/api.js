import axios from 'axios';

const tg = window.Telegram?.WebApp;

const api = axios.create({
    baseURL: '/api/miniapp',
    headers: {
        'X-Telegram-Init-Data': tg?.initData ?? '',
    },
});

export default api;
export { tg };
