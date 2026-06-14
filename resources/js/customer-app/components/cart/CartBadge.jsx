import React from 'react';
import { Link } from 'react-router-dom';
import { useCart } from '../../context/CartContext';

export default function CartBadge() {
    const { totalItems } = useCart();

    return (
        <Link to="/cart" className="cart-badge-link" aria-label="View cart">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" className="cart-badge-icon">
                <circle cx="9" cy="21" r="1" />
                <circle cx="20" cy="21" r="1" />
                <path d="M1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 002-1.61L23 6H6" />
            </svg>
            {totalItems > 0 && (
                <span className="cart-badge-count">{totalItems}</span>
            )}
        </Link>
    );
}
