import React, { useState } from "react";
import { useNavigate } from "react-router-dom";
import { useAuth } from "../context/AuthContext";
import { useCart } from "../context/CartContext";
import client from "../api/client";

export default function CheckoutPage({ slug }) {
    const { customer } = useAuth();
    const { items, subtotal, tax, total, clearCart } = useCart();
    const navigate = useNavigate();

    const [type, setType] = useState("take_away");
    const [note, setNote] = useState("");
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState(null);

    if (!customer) {
        return (
            <div className="checkout-page">
                <div className="checkout-auth-prompt">
                    <h2>Sign in to place your order</h2>
                    <p>You need an account to complete your order.</p>
                    <button
                        className="btn btn-primary"
                        onClick={() => navigate("/login")}
                    >
                        Sign In
                    </button>
                    <button
                        className="btn btn-outline"
                        onClick={() => navigate("/register")}
                    >
                        Create Account
                    </button>
                </div>
            </div>
        );
    }

    if (items.length === 0) {
        navigate("/");
        return null;
    }

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

            const res = await client.post(`/${slug}/orders`, payload);
            clearCart();
            navigate(`/orders/${res.data.order.id}`);
        } catch (err) {
            setError(err.response?.data?.message || "Failed to place order.");
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <div className="checkout-page">
            <h2>Checkout</h2>

            <div className="checkout-summary">
                <h3>Order Summary</h3>
                {items.map((item) => (
                    <div key={item.id} className="checkout-item">
                        <span>
                            {item.quantity}× {item.name}
                        </span>
                        <span>
                            {(item.price_raw * item.quantity).toFixed(2)} EGP
                        </span>
                    </div>
                ))}
                <div className="checkout-total">
                    <span>Total</span>
                    <span>{total.toFixed(2)} EGP</span>
                </div>
            </div>

            <form onSubmit={handleSubmit} className="checkout-form">
                <div className="form-group">
                    <label className="form-label">Order Type</label>
                    <div className="order-type-selector">
                        <button
                            type="button"
                            className={`order-type-btn ${type === "take_away" ? "active" : ""}`}
                            onClick={() => setType("take_away")}
                        >
                            🥡 Take Away
                        </button>
                        <button
                            type="button"
                            className={`order-type-btn ${type === "delivery" ? "active" : ""}`}
                            onClick={() => setType("delivery")}
                        >
                            🚗 Delivery
                        </button>
                    </div>
                </div>

                <div className="form-group">
                    <label htmlFor="note" className="form-label">
                        Special Instructions
                    </label>
                    <textarea
                        id="note"
                        className="form-textarea"
                        value={note}
                        onChange={(e) => setNote(e.target.value)}
                        placeholder="Any special requests? (optional)"
                        rows={3}
                    />
                </div>

                {error && <div className="form-error">{error}</div>}

                <button
                    type="submit"
                    className="btn btn-primary btn-block"
                    disabled={submitting}
                >
                    {submitting
                        ? "Placing Order..."
                        : `Place Order — ${total.toFixed(2)} EGP`}
                </button>
            </form>
        </div>
    );
}
