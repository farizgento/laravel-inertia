/**
 * We'll load the axios HTTP library which allows us to easily issue requests
 * to our Laravel back-end. This library automatically handles sending the
 * CSRF token as a header based on the value of the "XSRF" token cookie.
 */

import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
const AUTH_TOKEN_COOKIE = 'auth_token';

const getStoredToken = () => {
    try {
        return window.localStorage.getItem('auth_token');
    } catch (err) {
        return null;
    }
};

const setTokenCookie = (token) => {
    if (!token) {
        return;
    }

    document.cookie = `${AUTH_TOKEN_COOKIE}=${encodeURIComponent(token)}; path=/; SameSite=Lax`;
};

const clearTokenCookie = () => {
    document.cookie = `${AUTH_TOKEN_COOKIE}=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT; SameSite=Lax`;
};

window.setAuthToken = (token, user = null) => {
    window.localStorage.setItem('auth_token', token);
    if (user) {
        window.localStorage.setItem('auth_user', JSON.stringify(user));
    }
    setTokenCookie(token);
    window.axios.defaults.headers.common.Authorization = `Bearer ${token}`;
};

window.clearAuthToken = () => {
    try {
        window.localStorage.removeItem('auth_token');
        window.localStorage.removeItem('auth_user');
    } catch (err) {
        // Ignore storage failures.
    }

    clearTokenCookie();
    delete window.axios.defaults.headers.common.Authorization;
};

const storedToken = getStoredToken();
if (storedToken) {
    setTokenCookie(storedToken);
    window.axios.defaults.headers.common.Authorization = `Bearer ${storedToken}`;
}

window.axios.interceptors.request.use((config) => {
    const token = getStoredToken();
    if (token && !config.headers?.Authorization) {
        config.headers = config.headers ?? {};
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
});

window.axios.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error?.response?.status === 401) {
            window.clearAuthToken();
            if (window.location?.pathname !== '/login') {
                window.location.href = '/login';
            }
        }
        return Promise.reject(error);
    }
);
/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allows your team to easily build robust real-time web applications.
 */

// import Echo from 'laravel-echo';

// import Pusher from 'pusher-js';
// window.Pusher = Pusher;

// window.Echo = new Echo({
//     broadcaster: 'pusher',
//     key: import.meta.env.VITE_PUSHER_APP_KEY,
//     cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER ?? 'mt1',
//     wsHost: import.meta.env.VITE_PUSHER_HOST ? import.meta.env.VITE_PUSHER_HOST : `ws-${import.meta.env.VITE_PUSHER_APP_CLUSTER}.pusher.com`,
//     wsPort: import.meta.env.VITE_PUSHER_PORT ?? 80,
//     wssPort: import.meta.env.VITE_PUSHER_PORT ?? 443,
//     forceTLS: (import.meta.env.VITE_PUSHER_SCHEME ?? 'https') === 'https',
//     enabledTransports: ['ws', 'wss'],
// });
