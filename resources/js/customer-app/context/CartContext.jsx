import React, {
    createContext,
    useContext,
    useState,
    useCallback,
    useEffect,
} from "react";

const CartContext = createContext(null);

function getStorageKey(slug) {
    return `cart_${slug}`;
}

function loadCart(slug) {
    try {
        const data = localStorage.getItem(getStorageKey(slug));
        return data ? JSON.parse(data) : [];
    } catch {
        return [];
    }
}

function saveCart(slug, items) {
    try {
        localStorage.setItem(getStorageKey(slug), JSON.stringify(items));
    } catch {
        // ignore storage write failures (quota/private mode)
    }
}

export function CartProvider({ children, slug }) {
    const [items, setItems] = useState(() => loadCart(slug));

    // Persist to localStorage whenever items change
    useEffect(() => {
        saveCart(slug, items);
    }, [items, slug]);

    const addItem = useCallback((item) => {
        setItems((prev) => {
            const existing = prev.find((i) => i.id === item.id);
            if (existing) {
                return prev.map((i) =>
                    i.id === item.id ? { ...i, quantity: i.quantity + 1 } : i,
                );
            }
            return [...prev, { ...item, quantity: 1 }];
        });
    }, []);

    const removeItem = useCallback((itemId) => {
        setItems((prev) => prev.filter((i) => i.id !== itemId));
    }, []);

    const updateQuantity = useCallback((itemId, quantity) => {
        if (quantity <= 0) {
            setItems((prev) => prev.filter((i) => i.id !== itemId));
            return;
        }
        setItems((prev) =>
            prev.map((i) => (i.id === itemId ? { ...i, quantity } : i)),
        );
    }, []);

    const clearCart = useCallback(() => {
        setItems([]);
    }, []);

    const totalItems = items.reduce((sum, i) => sum + i.quantity, 0);
    const subtotal = items.reduce(
        (sum, i) => sum + i.price_raw * i.quantity,
        0,
    );
    const tax = subtotal * 0.14;
    const total = subtotal + tax;

    return (
        <CartContext.Provider
            value={{
                items,
                addItem,
                removeItem,
                updateQuantity,
                clearCart,
                totalItems,
                subtotal,
                tax,
                total,
            }}
        >
            {children}
        </CartContext.Provider>
    );
}

export function useCart() {
    const ctx = useContext(CartContext);
    if (!ctx) throw new Error("useCart must be used within CartProvider");
    return ctx;
}
