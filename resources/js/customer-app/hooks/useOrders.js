import { useState, useEffect } from "react";
import { useRestaurant } from "../context/RestaurantContext";

export function useOrders(customer) {
    const { slug, api } = useRestaurant();
    const [orders, setOrders] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    useEffect(() => {
        if (!customer) {
            setLoading(false);
            return;
        }

        let isMounted = true;
        setLoading(true);
        setError(null);

        api.fetchOrders()
            .then((orderList) => {
                if (isMounted) setOrders(orderList);
            })
            .catch(() => {
                if (isMounted) {
                    setOrders([]);
                    setError("Unable to load order history. Please try again.");
                }
            })
            .finally(() => {
                if (isMounted) setLoading(false);
            });

        return () => {
            isMounted = false;
        };
    }, [slug, customer, api]);

    return { orders, loading, error };
}

export function useOrderDetail(orderId, customer) {
    const { slug, api } = useRestaurant();
    const [order, setOrder] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    useEffect(() => {
        if (!customer) {
            setLoading(false);
            return;
        }

        let isMounted = true;
        setLoading(true);
        setError(null);

        api.fetchOrderById(orderId)
            .then((fetchedOrder) => {
                if (isMounted) setOrder(fetchedOrder);
            })
            .catch(() => {
                if (isMounted) setError("Order not found or access restricted.");
            })
            .finally(() => {
                if (isMounted) setLoading(false);
            });

        return () => {
            isMounted = false;
        };
    }, [slug, orderId, customer, api]);

    return { order, loading, error };
}
