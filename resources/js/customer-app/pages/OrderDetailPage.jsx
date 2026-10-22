import React, { useState } from "react";
import { useParams, Navigate, Link } from "react-router-dom";
import { useAuth } from "../context/AuthContext";
import { useRestaurant } from "../context/RestaurantContext";
import { useOrderDetail } from "../hooks/useOrders";

export default function OrderDetailPage() {
    const { id } = useParams();
    const { customer, loading: authLoading } = useAuth();
    const { restaurant } = useRestaurant();
    const { order, loading, error } = useOrderDetail(id, customer);
    const [copied, setCopied] = useState(false);

    const handleCopy = () => {
        if (order?.order_number) {
            navigator.clipboard.writeText(order.order_number);
            setCopied(true);
            setTimeout(() => setCopied(false), 2000);
        }
    };

    if (!authLoading && !customer) {
        return <Navigate to="/login" replace />;
    }

    if (loading || authLoading) {
        return (
            <div className="page-loader">
                <div className="spinner" />
                <p style={{ color: "var(--c-text-muted)", fontSize: "14px", marginTop: "8px" }}>
                    Loading order receipt...
                </p>
            </div>
        );
    }

    if (error || !order) {
        return (
            <div className="page-error">
                <p>{error || "Order not found."}</p>
                <Link to="/orders" className="btn btn-outline" style={{ marginTop: "16px" }}>
                    View All Orders
                </Link>
            </div>
        );
    }

    return (
        <div className="order-detail-page">
            <Link to="/orders" className="back-link">
                ← Back to All Orders
            </Link>

            <div className="order-receipt-card">
                {/* Header info */}
                <div className="order-detail-head">
                    <div className="order-detail-num-box">
                        <div className="order-detail-num-row">
                            <h2 className="page-title" style={{ fontSize: "20px" }}>
                                #{order.order_number}
                            </h2>
                            <button
                                type="button"
                                className="copy-btn"
                                onClick={handleCopy}
                                title="Copy order number"
                            >
                                {copied ? "✓ Copied" : "Copy"}
                            </button>
                        </div>
                        <span className="order-detail-date">
                            Placed on {new Date(order.created_at).toLocaleString()}
                        </span>
                    </div>

                    <span className={`order-type-badge ${order.type === 'take_away' ? 'takeaway' : 'delivery'}`}>
                        {order.type === "take_away" ? "🥡 Take Away" : "🚗 Delivery"}
                    </span>
                </div>

                {/* Items Breakdown */}
                <div>
                    <h3 style={{ fontSize: "15px", fontWeight: 700, marginBottom: "10px", color: "var(--c-text)" }}>
                        Ordered Items
                    </h3>
                    <div className="receipt-items-table">
                        {order.items.map((item) => {
                            const itemPrice = parseFloat(item.price);
                            const itemLineTotal = (itemPrice * item.quantity).toFixed(2);
                            return (
                                <div key={item.id} className="receipt-item-row">
                                    <div className="receipt-item-name-group">
                                        <span className="receipt-item-qty">{item.quantity}×</span>
                                        <span style={{ color: "var(--c-text)", fontWeight: 500 }}>{item.name}</span>
                                    </div>
                                    <span className="receipt-item-price">{itemLineTotal} EGP</span>
                                </div>
                            );
                        })}
                    </div>
                </div>

                {/* Financials Breakdown */}
                <div className="order-summary-block">
                    <div className="cart-summary-row">
                        <span>Items Subtotal</span>
                        <span>{order.price} EGP</span>
                    </div>
                    <div className="cart-summary-row">
                        <span>Tax (14% VAT)</span>
                        <span>{order.tax} EGP</span>
                    </div>
                    <div className="cart-summary-row cart-summary-total">
                        <span>Grand Total Paid</span>
                        <span>{order.net} EGP</span>
                    </div>
                </div>

                {/* Special Request Note */}
                {order.note && (
                    <div style={{ background: "var(--c-surface-elevated)", padding: "14px 16px", borderRadius: "var(--radius-sm)", border: "1px solid var(--c-border)" }}>
                        <div style={{ fontSize: "12px", fontWeight: 700, textTransform: "uppercase", letterSpacing: "0.05em", color: "var(--c-primary)", marginBottom: "4px" }}>
                            Special Note
                        </div>
                        <p style={{ fontSize: "14px", color: "var(--c-text-muted)", margin: 0 }}>
                            {order.note}
                        </p>
                    </div>
                )}

                {/* Contact Restaurant Button */}
                {restaurant.phone && (
                    <a
                        href={`tel:${restaurant.phone}`}
                        className="btn btn-outline btn-block"
                        style={{ marginTop: "4px" }}
                    >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" width="16" height="16">
                            <path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 16.92z" />
                        </svg>
                        <span>Need Help? Call Restaurant ({restaurant.phone})</span>
                    </a>
                )}
            </div>
        </div>
    );
}
