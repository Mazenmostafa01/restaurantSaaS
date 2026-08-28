import React, { useState, useEffect } from "react";
import { Link, Navigate } from "react-router-dom";
import { useAuth } from "../context/AuthContext";
import { createCustomerClient } from "../api/client";

export default function OrdersPage({ slug }) {
    const { customer, loading: authLoading } = useAuth();
    const [orders, setOrders] = useState([]);
    const [loading, setLoading] = useState(true);
    const client = createCustomerClient(slug);

    useEffect(() => {
        if (!customer) return;
        client
            .get('/orders')
            .then((res) => setOrders(res.data.orders.data || []))
            .catch(() => {})
            .finally(() => setLoading(false));
    }, [slug, customer]);

    if (!authLoading && !customer) {
        return <Navigate to="/login" replace />;
    }

    if (loading || authLoading) {
        return (
            <div className="page-loader">
                <div className="spinner" />
                <p style={{ color: "var(--c-text-muted)", fontSize: "14px", marginTop: "8px" }}>
                    Loading order history...
                </p>
            </div>
        );
    }

    return (
        <div className="orders-page">
            <h2 className="page-title">Order History</h2>

            {orders.length === 0 ? (
                <div className="orders-empty">
                    <div className="cart-empty-icon-box">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5">
                            <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z" />
                            <polyline points="14 2 14 8 20 8" />
                            <line x1="16" y1="13" x2="8" y2="13" />
                            <line x1="16" y1="17" x2="8" y2="17" />
                        </svg>
                    </div>
                    <h3>No past orders</h3>
                    <p>You haven't placed any orders with us yet. Ready to try something fresh?</p>
                    <Link to="/" className="btn btn-primary" style={{ marginTop: "8px" }}>
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
                                <span className={`order-type-badge ${order.type === 'take_away' ? 'takeaway' : 'delivery'}`}>
                                    {order.type === "take_away"
                                        ? "🥡 Take Away"
                                        : "🚗 Delivery"}
                                </span>
                            </div>

                            <div className="order-card-body">
                                <span className="order-card-items">
                                    {order.items_count} {order.items_count === 1 ? 'dish' : 'dishes'} ordered
                                </span>
                                <span className="order-card-total">
                                    {order.net} EGP
                                </span>
                            </div>

                            <div className="order-card-footer">
                                <span>
                                    {new Date(order.created_at).toLocaleDateString(undefined, {
                                        year: 'numeric',
                                        month: 'short',
                                        day: 'numeric',
                                    })}
                                    {' at '}
                                    {new Date(order.created_at).toLocaleTimeString([], {
                                        hour: '2-digit',
                                        minute: '2-digit',
                                    })}
                                </span>
                                <span style={{ color: 'var(--c-primary)', fontWeight: 600 }}>
                                    View Receipt →
                                </span>
                            </div>
                        </Link>
                    ))}
                </div>
            )}
        </div>
    );
}
