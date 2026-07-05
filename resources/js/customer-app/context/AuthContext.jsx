import React, {
    createContext,
    useContext,
    useState,
    useEffect,
    useCallback,
    useMemo,
} from 'react';
import { createCustomerClient, getToken, saveToken, removeToken } from '../api/client';

const AuthContext = createContext(null);

export function AuthProvider({ children, slug }) {
    const [customer, setCustomer] = useState(null);
    const [loading, setLoading] = useState(true);
    const client = useMemo(() => createCustomerClient(slug), [slug]);

    // Check if customer is already logged in on mount by verifying token
    useEffect(() => {
        const token = getToken(slug);
        if (token) {
            client
                .get('/user')
                .then((res) => setCustomer(res.data.customer))
                .catch(() => {
                    removeToken(slug);
                    setCustomer(null);
                })
                .finally(() => setLoading(false));
        } else {
            setLoading(false);
        }
    }, [slug, client]);

    const login = useCallback(
        async (email, password) => {
            const res = await client.post('/login', {
                email,
                password,
            });
            saveToken(slug, res.data.token);
            setCustomer(res.data.customer);
            return res.data;
        },
        [slug, client],
    );

    const register = useCallback(
        async (data) => {
            const res = await client.post('/register', data);
            saveToken(slug, res.data.token);
            setCustomer(res.data.customer);
            return res.data;
        },
        [slug, client],
    );

    const logout = useCallback(async () => {
        try {
            await client.post('/logout');
        } finally {
            removeToken(slug);
            setCustomer(null);
        }
    }, [slug, client]);

    const updateProfile = useCallback(
        async (data) => {
            const res = await client.put('/profile', data);
            setCustomer(res.data.customer);
            return res.data;
        },
        [slug, client],
    );

    return (
        <AuthContext.Provider
            value={{
                customer,
                loading,
                login,
                register,
                logout,
                updateProfile,
            }}
        >
            {children}
        </AuthContext.Provider>
    );
}

export function useAuth() {
    const ctx = useContext(AuthContext);
    if (!ctx) throw new Error('useAuth must be used within AuthProvider');
    return ctx;
}
