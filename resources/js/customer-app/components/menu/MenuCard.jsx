import React from 'react';
import { useCart } from '../../context/CartContext';

export default function MenuCard({ item, onCardClick }) {
    const { addItem, updateQuantity, getItemQuantity } = useCart();
    const currentQty = getItemQuantity(item.id);

    // Support both images array and legacy image string
    const images = Array.isArray(item.images) && item.images.length > 0
        ? item.images
        : (item.image ? [item.image] : []);
    const primaryImage = images[0] || null;
    const imageCount = images.length;

    const handleAdd = (e) => {
        e.stopPropagation();
        addItem(item, 1);
    };

    const handleIncrement = (e) => {
        e.stopPropagation();
        updateQuantity(item.id, currentQty + 1);
    };

    const handleDecrement = (e) => {
        e.stopPropagation();
        updateQuantity(item.id, currentQty - 1);
    };

    return (
        <div
            className="menu-card"
            onClick={() => onCardClick && onCardClick(item)}
            role="button"
            tabIndex={0}
            onKeyDown={(e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    onCardClick && onCardClick(item);
                }
            }}
        >
            <div className="menu-card-image">
                {item.category && (
                    <span className="menu-card-category-badge">{item.category}</span>
                )}
                {imageCount > 1 && (
                    <span className="menu-card-photos-badge" title={`${imageCount} photos available`}>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" width="12" height="12">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2" />
                            <circle cx="8.5" cy="8.5" r="1.5" />
                            <polyline points="21 15 16 10 5 21" />
                        </svg>
                        <span>{imageCount}</span>
                    </span>
                )}
                {primaryImage ? (
                    <img src={primaryImage} alt={item.name} loading="lazy" />
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
                    <div className="menu-card-price-box">
                        <span className="menu-card-price">{item.price}</span>
                        <span className="menu-card-currency">EGP</span>
                    </div>

                    <div className="menu-card-actions">
                        {currentQty > 0 ? (
                            <div className="menu-card-stepper" onClick={(e) => e.stopPropagation()}>
                                <button
                                    type="button"
                                    className="card-stepper-btn"
                                    onClick={handleDecrement}
                                    aria-label={`Reduce ${item.name}`}
                                >
                                    −
                                </button>
                                <span className="card-stepper-count">{currentQty}</span>
                                <button
                                    type="button"
                                    className="card-stepper-btn"
                                    onClick={handleIncrement}
                                    aria-label={`Add another ${item.name}`}
                                >
                                    +
                                </button>
                            </div>
                        ) : (
                            <button
                                type="button"
                                className="menu-card-add-btn"
                                onClick={handleAdd}
                                aria-label={`Add ${item.name} to order`}
                            >
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5">
                                    <line x1="12" y1="5" x2="12" y2="19" />
                                    <line x1="5" y1="12" x2="19" y2="12" />
                                </svg>
                            </button>
                        )}
                    </div>
                </div>
            </div>
        </div>
    );
}
