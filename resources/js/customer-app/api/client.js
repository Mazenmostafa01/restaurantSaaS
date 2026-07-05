import axios from 'axios';

export const getTokenKey = (slug) => `customer_token_${slug}`;

export const getToken = (slug) => {
    return localStorage.getItem(getTokenKey(slug));
};

export const saveToken = (slug, token) => {
    localStorage.setItem(getTokenKey(slug), token);
};

export const removeToken = (slug) => {
    localStorage.removeItem(getTokenKey(slug));
};

export const createCustomerClient = (slug) => {
    const client = axios.create({
        baseURL: `/api/customer/${slug}`,
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
    });

    client.interceptors.request.use((config) => {
        const token = getToken(slug);
        if (token) {
            config.headers = config.headers ?? {};
            config.headers.Authorization = 'Bearer ' + token;
        }
        return config;
    });

    return client;
};

