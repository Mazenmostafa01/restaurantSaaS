import React, { useState, useEffect } from "react";
import { useParams, Navigate, Link } from "react-router-dom";
import { useAuth } from "../context/AuthContext";
import client from "../api/client";

export default function OrderDetailPage({ slug }) {
    const { id } = useParams();
    const { customer, loading: authLoading } = useAuth();
    const [order, setOrder] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    useEffect(() => {
        if (!customer) return;
        client
            .get(`/${slug}/orders/${id}`)
            .then((res) => setOrder(res.data.order))
            .catch(() => setError("Order not found."))
            .finally(() => setLoading(false));
    }, [slug, id, customer]);

    if (!authLoading && !customer) {
        return <Navigate to="/login" replace />;
    }

    if (loading || authLoading) {
        return (
            <div className="page-loader">
                <div className="spinner" />
            </div>
        );
    }

    if (error) {
        return <div className="page-error">{error}</div>;
    }

    return (
        <div className="order-detail-page">
            <Link to="/orders" className="back-link">
                ← Back to Orders
            </Link>

            <div className="order-detail-header">
                <h2>Order #{order.order_number}</h2>
                <span className="order-detail-type">
                    {order.type === "take_away"
                        ? "🥡 Take Away"
                        : "🚗 Delivery"}
                </span>
            </div>

            <div className="order-detail-date">
                Placed on {new Date(order.created_at).toLocaleString()}
            </div>

            <div className="order-detail-items">
                <h3>Items</h3>
                {order.items.map((item) => (
                    <div key={item.id} className="order-detail-item">
                        <div className="order-detail-item-info">
                            <span className="order-detail-item-qty">
                                {item.quantity}×
                            </span>
                            <span className="order-detail-item-name">
                                {item.name}
                            </span>
                        </div>
                        <span className="order-detail-item-price">
                            {(parseFloat(item.price) * item.quantity).toFixed(
                                2,
                            )}{" "}
                            EGP
                        </span>
                    </div>
                ))}
            </div>

            <div className="order-detail-summary">
                <div className="order-detail-row">
                    <span>Subtotal</span>
                    <span>{order.price} EGP</span>
                </div>
                <div className="order-detail-row">
                    <span>Tax</span>
                    <span>{order.tax} EGP</span>
                </div>
                <div className="order-detail-row order-detail-total">
                    <span>Total</span>
                    <span>{order.net} EGP</span>
                </div>
            </div>

            {order.note && (
                <div className="order-detail-note">
                    <h3>Note</h3>
                    <p>{order.note}</p>
                </div>
            )}
        </div>
    );
}
