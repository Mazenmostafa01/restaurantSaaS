import React from 'react';
import { useCart } from '../../context/CartContext';

export default function CartItem({ item }) {
    const { updateQuantity, removeItem } = useCart();

    return (
        <div className="cart-item">
            <div className="cart-item-info">
                <h4 className="cart-item-name">{item.name}</h4>
                <span className="cart-item-price">{(item.price_raw * item.quantity).toFixed(2)} EGP</span>
            </div>
            <div className="cart-item-controls">
                <button
                    className="cart-qty-btn"
                    onClick={() => updateQuantity(item.id, item.quantity - 1)}
                    aria-label="Decrease quantity"
                >
                    −
                </button>
                <span className="cart-qty-value">{item.quantity}</span>
                <button
                    className="cart-qty-btn"
                    onClick={() => updateQuantity(item.id, item.quantity + 1)}
                    aria-label="Increase quantity"
                >
                    +
                </button>
                <button
                    className="cart-remove-btn"
                    onClick={() => removeItem(item.id)}
                    aria-label={`Remove ${item.name}`}
                >
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                        <polyline points="3,6 5,6 21,6" />
                        <path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2" />
                    </svg>
                </button>
            </div>
        </div>
    );
}
