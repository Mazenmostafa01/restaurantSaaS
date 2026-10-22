import React, {
    createContext,
    useContext,
    useState,
    useEffect,
    useCallback,
    useMemo,
} from "react";
import { getToken, saveToken, removeToken } from "../api/client";
import { useRestaurant } from "./RestaurantContext";

const AuthContext = createContext(null);

export function AuthProvider({ children }) {
    const { slug, api } = useRestaurant();
    const [customer, setCustomer] = useState(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        const token = getToken(slug);
        if (token) {
            api.fetchCurrentCustomer()
                .then((currentCustomer) => setCustomer(currentCustomer))
                .catch(() => {
                    removeToken(slug);
                    setCustomer(null);
                })
                .finally(() => setLoading(false));
        } else {
            setLoading(false);
        }
    }, [slug, api]);

    const login = useCallback(
        async (email, password) => {
            const authResponse = await api.loginCustomer(email, password);
            saveToken(slug, authResponse.token);
            setCustomer(authResponse.customer);
            return authResponse;
        },
        [slug, api],
    );

    const register = useCallback(
        async (payload) => {
            const authResponse = await api.registerCustomer(payload);
            saveToken(slug, authResponse.token);
            setCustomer(authResponse.customer);
            return authResponse;
        },
        [slug, api],
    );

    const logout = useCallback(async () => {
        try {
            await api.logoutCustomer();
        } finally {
            removeToken(slug);
            setCustomer(null);
        }
    }, [slug, api]);

    const updateProfile = useCallback(
        async (payload) => {
            const profileResponse = await api.updateCustomerProfile(payload);
            setCustomer(profileResponse.customer);
            return profileResponse;
        },
        [api],
    );

    const contextValue = useMemo(
        () => ({
            customer,
            loading,
            login,
            register,
            logout,
            updateProfile,
        }),
        [customer, loading, login, register, logout, updateProfile],
    );

    return (
        <AuthContext.Provider value={contextValue}>
            {children}
        </AuthContext.Provider>
    );
}

export function useAuth() {
    const ctx = useContext(AuthContext);
    if (!ctx) throw new Error("useAuth must be used within AuthProvider");
    return ctx;
}
