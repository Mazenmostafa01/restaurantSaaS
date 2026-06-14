import axios from 'axios';

const client = axios.create({
    baseURL: '/api/customer',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
    withCredentials: true,
});

/**
 * Get a CSRF cookie from Sanctum before making auth requests.
 */
export const getCsrfCookie = () => axios.get('/sanctum/csrf-cookie', { withCredentials: true });

export default client;
