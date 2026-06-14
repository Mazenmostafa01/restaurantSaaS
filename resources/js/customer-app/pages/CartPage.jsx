import React from "react";
import { Link } from "react-router-dom";
import { useCart } from "../context/CartContext";
import CartItem from "../components/cart/CartItem";

export default function CartPage() {
    const { items, subtotal, tax, total, clearCart } = useCart();

    if (items.length === 0) {
        return (
            <div className="cart-page">
                <div className="cart-empty">
                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        strokeWidth="1.5"
                        className="cart-empty-icon"
                    >
                        <circle cx="9" cy="21" r="1" />
                        <circle cx="20" cy="21" r="1" />
                        <path d="M1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 002-1.61L23 6H6" />
                    </svg>
                    <h3>Your cart is empty</h3>
                    <p>Browse the menu and add some delicious items!</p>
                    <Link to="/menu" className="btn btn-primary">
                        Browse Menu
                    </Link>
                </div>
            </div>
        );
    }

    return (
        <div className="cart-page">
            <div className="cart-page-header">
                <h2>Your Cart</h2>
                <button className="cart-clear-btn" onClick={clearCart}>
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
                    <span>Subtotal</span>
                    <span>{subtotal.toFixed(2)} EGP</span>
                </div>
                <div className="cart-summary-row">
                    <span>Tax (14%)</span>
                    <span>{tax.toFixed(2)} EGP</span>
                </div>
                <div className="cart-summary-row cart-summary-total">
                    <span>Total</span>
                    <span>{total.toFixed(2)} EGP</span>
                </div>
            </div>

            <Link to="/checkout" className="btn btn-primary btn-block">
                Proceed to Checkout
            </Link>
        </div>
    );
}
