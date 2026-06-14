import React, {
    createContext,
    useContext,
    useState,
    useEffect,
    useCallback,
} from "react";
import client, { getCsrfCookie } from "../api/client";

const AuthContext = createContext(null);

export function AuthProvider({ children, slug }) {
    const [customer, setCustomer] = useState(null);
    const [loading, setLoading] = useState(true);

    // Check if customer is already logged in on mount
    useEffect(() => {
        client
            .get(`/${slug}/user`)
            .then((res) => setCustomer(res.data.customer))
            .catch(() => setCustomer(null))
            .finally(() => setLoading(false));
    }, [slug]);

    const login = useCallback(
        async (email, password) => {
            await getCsrfCookie();
            const res = await client.post(`/${slug}/login`, {
                email,
                password,
            });
            setCustomer(res.data.customer);
            return res.data;
        },
        [slug],
    );

    const register = useCallback(
        async (data) => {
            await getCsrfCookie();
            const res = await client.post(`/${slug}/register`, data);
            setCustomer(res.data.customer);
            return res.data;
        },
        [slug],
    );

    const logout = useCallback(async () => {
        await client.post(`/${slug}/logout`);
        setCustomer(null);
    }, [slug]);

    const updateProfile = useCallback(
        async (data) => {
            const res = await client.put(`/${slug}/profile`, data);
            setCustomer(res.data.customer);
            return res.data;
        },
        [slug],
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
    if (!ctx) throw new Error("useAuth must be used within AuthProvider");
    return ctx;
}
