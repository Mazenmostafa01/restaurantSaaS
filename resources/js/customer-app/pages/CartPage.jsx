import React from "react";
import { Link, useNavigate } from "react-router-dom";
import { useCart } from "../context/CartContext";
import CartItem from "../components/cart/CartItem";

export default function CartPage() {
    const { items, subtotal, tax, total, clearCart, totalItems } = useCart();
    const navigate = useNavigate();

    if (items.length === 0) {
        return (
            <div className="cart-page">
                <div className="cart-empty">
                    <div className="cart-empty-icon-box">
                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            strokeWidth="1.5"
                        >
                            <circle cx="9" cy="21" r="1" />
                            <circle cx="20" cy="21" r="1" />
                            <path d="M1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 002-1.61L23 6H6" />
                        </svg>
                    </div>
                    <h3>Your cart is empty</h3>
                    <p>Explore our kitchen's specialties and add your favorite dishes!</p>
                    <Link to="/" className="btn btn-primary" style={{ marginTop: "8px" }}>
                        Explore Menu
                    </Link>
                </div>
            </div>
        );
    }

    return (
        <div className="cart-page">
            <Link to="/" className="back-link">
                ← Back to Menu
            </Link>

            <div className="cart-page-header">
                <div>
                    <h2 className="page-title">Your Order</h2>
                    <span style={{ fontSize: "13px", color: "var(--c-text-muted)" }}>
                        {totalItems} {totalItems === 1 ? "item" : "items"} in cart
                    </span>
                </div>
                <button
                    type="button"
                    className="cart-clear-btn"
                    onClick={clearCart}
                    aria-label="Clear all items from cart"
                >
                    Clear All
                </button>
            </div>

            <div className="cart-items-list">
                {items.map((item) => (
                    <CartItem key={item.id} item={item} />
                ))}
            </div>

            <div className="cart-summary">
                <div className="cart-summary-row">
                    <span>Items Subtotal</span>
                    <span>{subtotal.toFixed(2)} EGP</span>
                </div>
                <div className="cart-summary-row">
                    <span>Tax (14% VAT)</span>
                    <span>{tax.toFixed(2)} EGP</span>
                </div>
                <div className="cart-summary-row cart-summary-total">
                    <span>Grand Total</span>
                    <span>{total.toFixed(2)} EGP</span>
                </div>
            </div>

            <button
                type="button"
                className="btn btn-primary btn-block"
                onClick={() => navigate("/checkout")}
                style={{ padding: "15px 24px", fontSize: "15px" }}
            >
                <span>Proceed to Checkout</span>
                <span>•</span>
                <span>{total.toFixed(2)} EGP</span>
            </button>
        </div>
    );
}
