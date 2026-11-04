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

function getMetaStorageKey(slug) {
    return `cart_meta_${slug}`;
}

function loadCart(slug) {
    try {
        const serializedCart = localStorage.getItem(getStorageKey(slug));
        return serializedCart ? JSON.parse(serializedCart) : [];
    } catch {
        return [];
    }
}

function saveCart(slug, cartItems) {
    try {
        localStorage.setItem(getStorageKey(slug), JSON.stringify(cartItems));
    } catch {
        // Storage quota or private browsing restrictions should not crash the UI
    }
}

function loadCartMeta(slug) {
    try {
        const serializedMeta = localStorage.getItem(getMetaStorageKey(slug));
        if (!serializedMeta) {
            return { orderNote: "", orderSplits: null };
        }
        const parsedMeta = JSON.parse(serializedMeta);
        return {
            orderNote:
                typeof parsedMeta.orderNote === "string"
                    ? parsedMeta.orderNote
                    : "",
            orderSplits:
                parsedMeta.orderSplits &&
                typeof parsedMeta.orderSplits === "object"
                    ? parsedMeta.orderSplits
                    : null,
        };
    } catch {
        return { orderNote: "", orderSplits: null };
    }
}

function saveCartMeta(slug, meta) {
    try {
        localStorage.setItem(getMetaStorageKey(slug), JSON.stringify(meta));
    } catch {
        // Storage quota or private browsing restrictions should not crash the UI
    }
}

function normalizeCartItem(dish, quantityToAdd) {
    const priceRaw =
        typeof dish.price_raw === "number"
            ? dish.price_raw
            : parseFloat(dish.price || 0);
    const priceFormatted =
        typeof dish.price === "string" ? dish.price : priceRaw.toFixed(2);

    return {
        ...dish,
        price: priceFormatted,
        price_raw: priceRaw,
        quantity: Math.max(1, quantityToAdd || 1),
        ai_note: dish.ai_note || null,
    };
}

function mergeItemNote(existingNote, incomingNote) {
    if (incomingNote && existingNote) {
        return existingNote.includes(incomingNote)
            ? existingNote
            : `${existingNote}, ${incomingNote}`;
    }
    return incomingNote || existingNote || null;
}

export function CartProvider({ children, slug }) {
    const [items, setItems] = useState(() => loadCart(slug));
    const [orderNote, setOrderNote] = useState(
        () => loadCartMeta(slug).orderNote,
    );
    const [orderSplits, setOrderSplits] = useState(
        () => loadCartMeta(slug).orderSplits,
    );

    useEffect(() => {
        saveCart(slug, items);
    }, [items, slug]);

    useEffect(() => {
        saveCartMeta(slug, { orderNote, orderSplits });
    }, [orderNote, orderSplits, slug]);

    const addItem = useCallback((dish, quantity = 1) => {
        const normalized = normalizeCartItem(dish, quantity);
        setItems((prev) => {
            const existing = prev.find((cartItem) => cartItem.id === dish.id);
            if (existing) {
                return prev.map((cartItem) =>
                    cartItem.id === dish.id
                        ? {
                              ...cartItem,
                              quantity:
                                  cartItem.quantity + normalized.quantity,
                              ai_note: mergeItemNote(
                                  cartItem.ai_note,
                                  normalized.ai_note,
                              ),
                          }
                        : cartItem,
                );
            }
            return [...prev, normalized];
        });
    }, []);

    const addItemsBatch = useCallback(
        (batchItems, { note = null, splits = null } = {}) => {
            if (Array.isArray(batchItems) && batchItems.length > 0) {
                setItems((prev) => {
                    const next = [...prev];
                    for (const incoming of batchItems) {
                        const normalized = normalizeCartItem(
                            incoming,
                            incoming.quantity,
                        );
                        const existingIdx = next.findIndex(
                            (cartItem) => cartItem.id === normalized.id,
                        );
                        if (existingIdx !== -1) {
                            const existing = next[existingIdx];
                            next[existingIdx] = {
                                ...existing,
                                quantity:
                                    existing.quantity + normalized.quantity,
                                ai_note: mergeItemNote(
                                    existing.ai_note,
                                    normalized.ai_note,
                                ),
                            };
                        } else {
                            next.push(normalized);
                        }
                    }
                    return next;
                });
            }

            if (note && typeof note === "string" && note.trim()) {
                const cleanNote = note.trim();
                setOrderNote((prev) => {
                    if (!prev) return cleanNote.slice(0, 255);
                    if (prev.includes(cleanNote)) return prev;
                    return `${prev} | ${cleanNote}`.slice(0, 255);
                });
            }

            if (
                splits &&
                typeof splits === "object" &&
                Object.keys(splits).length > 0
            ) {
                setOrderSplits((prev) => ({
                    ...(prev || {}),
                    ...splits,
                }));
            }
        },
        [],
    );

    const getItemQuantity = useCallback(
        (itemId) => {
            const found = items.find((cartItem) => cartItem.id === itemId);
            return found ? found.quantity : 0;
        },
        [items],
    );

    const removeItem = useCallback((itemId) => {
        setItems((prev) => prev.filter((cartItem) => cartItem.id !== itemId));
    }, []);

    const updateQuantity = useCallback((itemId, quantity) => {
        if (quantity <= 0) {
            setItems((prev) =>
                prev.filter((cartItem) => cartItem.id !== itemId),
            );
            return;
        }
        setItems((prev) =>
            prev.map((cartItem) =>
                cartItem.id === itemId ? { ...cartItem, quantity } : cartItem,
            ),
        );
    }, []);

    const clearCart = useCallback(() => {
        setItems([]);
        setOrderNote("");
        setOrderSplits(null);
    }, []);

    const totalItems = items.reduce(
        (sum, cartItem) => sum + cartItem.quantity,
        0,
    );
    const subtotal = items.reduce(
        (sum, cartItem) => sum + cartItem.price_raw * cartItem.quantity,
        0,
    );
    const tax = subtotal * 0.14;
    const total = subtotal + tax;

    return (
        <CartContext.Provider
            value={{
                items,
                addItem,
                addItemsBatch,
                removeItem,
                updateQuantity,
                getItemQuantity,
                clearCart,
                orderNote,
                setOrderNote,
                orderSplits,
                setOrderSplits,
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
