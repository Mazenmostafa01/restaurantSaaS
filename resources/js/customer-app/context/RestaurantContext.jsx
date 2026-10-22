import React, { createContext, useContext, useMemo } from "react";
import { createCustomerApi } from "../api/customerApi";

const RestaurantContext = createContext(null);

export function RestaurantProvider({ restaurant, children }) {
    const slug = restaurant?.slug;

    const value = useMemo(() => {
        const api = createCustomerApi(slug);
        return {
            restaurant: restaurant || {},
            slug,
            api,
        };
    }, [restaurant, slug]);

    return (
        <RestaurantContext.Provider value={value}>
            {children}
        </RestaurantContext.Provider>
    );
}

export function useRestaurant() {
    const context = useContext(RestaurantContext);
    if (!context) {
        throw new Error("useRestaurant must be used within RestaurantProvider");
    }
    return context;
}
