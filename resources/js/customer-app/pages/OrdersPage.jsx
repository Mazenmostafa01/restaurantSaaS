import React, { useState, useEffect } from "react";
import { Link, Navigate } from "react-router-dom";
import { useAuth } from "../context/AuthContext";
import client from "../api/client";

export default function OrdersPage({ slug }) {
    const { customer, loading: authLoading } = useAuth();
    const [orders, setOrders] = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        if (!customer) return;
        client
            .get(`/${slug}/orders`)
            .then((res) => setOrders(res.data.orders.data || []))
            .catch(() => {})
            .finally(() => setLoading(false));
    }, [slug, customer]);

    if (!authLoading && !customer) {
        return <Navigate to="login" replace />;
    }

    if (loading || authLoading) {
        return (
            <div className="page-loader">
                <div className="spinner" />
            </div>
        );
    }

    return (
        <div className="orders-page">
            <h2>Your Orders</h2>

            {orders.length === 0 ? (
                <div className="orders-empty">
                    <p>You haven't placed any orders yet.</p>
                    <Link to="/menu" className="btn btn-primary">
                        Browse Menu
                    </Link>
                </div>
            ) : (
                <div className="orders-list">
                    {orders.map((order) => (
                        <Link
                            key={order.id}
                            to={`${order.id}`}
                            className="order-card"
                        >
                            <div className="order-card-header">
                                <span className="order-card-number">
                                    #{order.order_number}
                                </span>
                                <span className="order-card-type">
                                    {order.type === "take_away"
                                        ? "🥡 Take Away"
                                        : "🚗 Delivery"}
                                </span>
                            </div>
                            <div className="order-card-body">
                                <span className="order-card-items">
                                    {order.items_count} item(s)
                                </span>
                                <span className="order-card-total">
                                    {order.net} EGP
                                </span>
                            </div>
                            <div className="order-card-footer">
                                <span className="order-card-date">
                                    {new Date(
                                        order.created_at,
                                    ).toLocaleDateString()}
                                </span>
                            </div>
                        </Link>
                    ))}
                </div>
            )}
        </div>
    );
}
