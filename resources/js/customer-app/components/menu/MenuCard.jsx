import React from 'react';
import { useCart } from '../../context/CartContext';

export default function MenuCard({ item }) {
    const { addItem } = useCart();

    return (
        <div className="menu-card">
            <div className="menu-card-image">
                {item.image ? (
                    <img src={item.image} alt={item.name} loading="lazy" />
                ) : (
                    <div className="menu-card-image-placeholder">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5">
                            <path d="M18 8h1a4 4 0 010 8h-1M2 8h16v9a4 4 0 01-4 4H6a4 4 0 01-4-4V8zM6 1v3M10 1v3M14 1v3" />
                        </svg>
                    </div>
                )}
            </div>
            <div className="menu-card-body">
                <h3 className="menu-card-name">{item.name}</h3>
                {item.description && (
                    <p className="menu-card-desc">{item.description}</p>
                )}
                <div className="menu-card-footer">
                    <span className="menu-card-price">{item.price} EGP</span>
                    <button
                        className="menu-card-add-btn"
                        onClick={() => addItem(item)}
                        aria-label={`Add ${item.name} to cart`}
                    >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5">
                            <line x1="12" y1="5" x2="12" y2="19" />
                            <line x1="5" y1="12" x2="19" y2="12" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    );
}
