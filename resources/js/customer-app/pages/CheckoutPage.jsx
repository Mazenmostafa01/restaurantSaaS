import React, { useState } from "react";
import { useNavigate, Link } from "react-router-dom";
import { useAuth } from "../context/AuthContext";
import { useCart } from "../context/CartContext";
import { createCustomerClient } from "../api/client";

const QUICK_NOTES = [
    "No onions 🧅",
    "Extra napkins 🧻",
    "Utensils please 🍴",
    "Call upon arrival 📞",
    "Extra sauce 🥫",
    "Spicy please 🌶️",
];

export default function CheckoutPage({ slug }) {
    const { customer } = useAuth();
    const { items, subtotal, tax, total, clearCart } = useCart();
    const navigate = useNavigate();
    const client = createCustomerClient(slug);

    const [type, setType] = useState("take_away");
    const [note, setNote] = useState("");
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState(null);

    if (!customer) {
        return (
            <div className="checkout-page">
                <Link to="/cart" className="back-link">
                    ← Back to Cart
                </Link>
                <div className="checkout-auth-prompt">
                    <div className="cart-empty-icon-box">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5">
                            <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2" />
                            <circle cx="12" cy="7" r="4" />
                        </svg>
                    </div>
                    <h2>Sign in to complete your order</h2>
                    <p>Create an account or sign in to track your order in real-time.</p>
                    <div className="checkout-auth-actions">
                        <button
                            type="button"
                            className="btn btn-primary"
                            onClick={() => navigate("/login")}
                        >
                            Sign In
                        </button>
                        <button
                            type="button"
                            className="btn btn-outline"
                            onClick={() => navigate("/register")}
                        >
                            Create Account
                        </button>
                    </div>
                </div>
            </div>
        );
    }

    if (items.length === 0) {
        navigate("/");
        return null;
    }

    const handleChipClick = (chipText) => {
        setNote((prev) => {
            if (!prev) return chipText;
            if (prev.includes(chipText)) return prev;
            return `${prev}, ${chipText}`;
        });
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        setSubmitting(true);
        setError(null);

        try {
            const payload = {
                type,
                note: note || null,
                items: items.map((i) => ({ id: i.id, quantity: i.quantity })),
            };

            const res = await client.post('/orders', payload);
            clearCart();
            navigate(`/orders/${res.data.order.id}`);
        } catch (err) {
            setError(err.response?.data?.message || "Failed to place order. Please try again.");
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <div className="checkout-page">
            <Link to="/cart" className="back-link">
                ← Back to Cart
            </Link>

            <h2 className="page-title">Finalize Order</h2>

            {/* Order Items Summary */}
            <div className="checkout-summary">
                <h3>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" width="18" height="18" style={{ color: "var(--c-primary)" }}>
                        <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z" />
                        <polyline points="14 2 14 8 20 8" />
                    </svg>
                    Order Summary
                </h3>
                <div className="checkout-items-list">
                    {items.map((item) => (
                        <div key={item.id} className="checkout-item">
                            <span className="checkout-item-name">
                                {item.quantity}× {item.name}
                            </span>
                            <span>
                                {(item.price_raw * item.quantity).toFixed(2)} EGP
                            </span>
                        </div>
                    ))}
                </div>

                <div className="cart-summary-row" style={{ paddingTop: "8px", borderTop: "1px solid var(--c-border)" }}>
                    <span>Subtotal</span>
                    <span>{subtotal.toFixed(2)} EGP</span>
                </div>
                <div className="cart-summary-row">
                    <span>Tax (14% VAT)</span>
                    <span>{tax.toFixed(2)} EGP</span>
                </div>

                <div className="checkout-total">
                    <span>Total Amount</span>
                    <span className="checkout-total-value">{total.toFixed(2)} EGP</span>
                </div>
            </div>

            {/* Checkout Form */}
            <form onSubmit={handleSubmit} className="checkout-form">
                {/* Order Type */}
                <div className="form-group">
                    <label className="form-label">Delivery or Take Away?</label>
                    <div className="order-type-selector">
                        <button
                            type="button"
                            className={`order-type-btn ${type === "take_away" ? "active" : ""}`}
                            onClick={() => setType("take_away")}
                        >
                            <span className="type-icon">🥡</span>
                            <span className="type-label">Take Away</span>
                            <span className="type-sub">Pick up at restaurant counter</span>
                        </button>
                        <button
                            type="button"
                            className={`order-type-btn ${type === "delivery" ? "active" : ""}`}
                            onClick={() => setType("delivery")}
                        >
                            <span className="type-icon">🚗</span>
                            <span className="type-label">Delivery</span>
                            <span className="type-sub">Delivered to your address</span>
                        </button>
                    </div>
                </div>

                {/* Customer Details Verification */}
                <div className="checkout-customer-card">
                    <div className="checkout-customer-title">
                        👤 Ordering as {customer.name}
                    </div>
                    <div className="checkout-customer-details">
                        <div>📞 Phone: <strong style={{ color: "var(--c-text)" }}>{customer.phone_number}</strong></div>
                        {type === "delivery" && (
                            <div>
                                📍 Delivery Address:{" "}
                                <strong style={{ color: "var(--c-text)" }}>
                                    {customer.address || "Please edit in Profile if needed"}
                                </strong>
                            </div>
                        )}
                    </div>
                </div>

                {/* Special Instructions & Chips */}
                <div className="form-group">
                    <label htmlFor="note" className="form-label">
                        Special Instructions <span style={{ fontWeight: 400, color: "var(--c-text-subtle)" }}>(optional)</span>
                    </label>
                    <textarea
                        id="note"
                        className="form-textarea"
                        value={note}
                        onChange={(e) => setNote(e.target.value)}
                        placeholder="e.g. Please pack sauces separately, ring bell upon arrival..."
                        rows={3}
                    />
                    <div className="quick-notes-chips">
                        {QUICK_NOTES.map((chip) => (
                            <button
                                key={chip}
                                type="button"
                                className="quick-note-chip"
                                onClick={() => handleChipClick(chip)}
                            >
                                {chip}
                            </button>
                        ))}
                    </div>
                </div>

                {error && <div className="form-error">{error}</div>}

                <div className="checkout-submit-wrapper">
                    <button
                        type="submit"
                        className="btn btn-primary btn-block"
                        disabled={submitting}
                        style={{ padding: "16px 24px", fontSize: "15px" }}
                    >
                        {submitting ? (
                            <>
                                <div className="spinner" style={{ width: "20px", height: "20px", borderWidth: "2px" }} />
                                <span>Confirming Order...</span>
                            </>
                        ) : (
                            <span>Confirm & Place Order — {total.toFixed(2)} EGP</span>
                        )}
                    </button>
                </div>
            </form>
        </div>
    );
}

