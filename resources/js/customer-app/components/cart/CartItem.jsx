import React from 'react';
import { useCart } from '../../context/CartContext';

export default function CartItem({ item }) {
    const { updateQuantity, removeItem } = useCart();
    const itemTotal = (item.price_raw * item.quantity).toFixed(2);
    const thumb = (Array.isArray(item.images) && item.images[0]) || item.image;

    return (
        <div className="cart-item">
            <div className="cart-item-info">
                {thumb ? (
                    <img src={thumb} alt={item.name} className="cart-item-thumb" />
                ) : (
                    <div className="cart-item-thumb" style={{ display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" width="22" height="22" style={{ color: 'var(--c-text-subtle)' }}>
                            <path d="M18 8h1a4 4 0 010 8h-1M2 8h16v9a4 4 0 01-4 4H6a4 4 0 01-4-4V8zM6 1v3M10 1v3M14 1v3" />
                        </svg>
                    </div>
                )}
                <div className="cart-item-meta">
                    <h4 className="cart-item-name">{item.name}</h4>
                    <span className="cart-item-price">
                        {itemTotal} EGP <span style={{ fontSize: '11px', color: 'var(--c-text-subtle)', fontWeight: 400 }}>({item.price} ea)</span>
                    </span>
                </div>
            </div>
            <div className="cart-item-controls">
                <button
                    type="button"
                    className="cart-qty-btn"
                    onClick={() => updateQuantity(item.id, item.quantity - 1)}
                    aria-label="Decrease quantity"
                >
                    −
                </button>
                <span className="cart-qty-value">{item.quantity}</span>
                <button
                    type="button"
                    className="cart-qty-btn"
                    onClick={() => updateQuantity(item.id, item.quantity + 1)}
                    aria-label="Increase quantity"
                >
                    +
                </button>
                <button
                    type="button"
                    className="cart-remove-btn"
                    onClick={() => removeItem(item.id)}
                    aria-label={`Remove ${item.name}`}
                    title="Remove item"
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
