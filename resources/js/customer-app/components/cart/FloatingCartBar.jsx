import React from 'react';
import { Link, useLocation } from 'react-router-dom';
import { useCart } from '../../context/CartContext';

export default function FloatingCartBar() {
    const { totalItems, total } = useCart();
    const location = useLocation();

    // Do not show floating bar if already on cart or checkout page
    if (totalItems === 0 || location.pathname === '/cart' || location.pathname === '/checkout') {
        return null;
    }

    return (
        <Link to="/cart" className="floating-cart-bar" aria-label={`View Cart - ${totalItems} items, Total: ${total.toFixed(2)} EGP`}>
            <div className="floating-cart-info">
                <span className="floating-cart-pill">
                    {totalItems} {totalItems === 1 ? 'item' : 'items'}
                </span>
                <span className="floating-cart-price">
                    {total.toFixed(2)} EGP
                </span>
            </div>
            <div className="floating-cart-cta">
                <span>View Order</span>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5">
                    <line x1="5" y1="12" x2="19" y2="12" />
                    <polyline points="12 5 19 12 12 19" />
                </svg>
            </div>
        </Link>
    );
}
