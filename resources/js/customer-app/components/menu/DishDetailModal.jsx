import React, { useState, useEffect } from 'react';

export default function DishDetailModal({ item, isOpen, onClose, onAddToCart }) {
    const [quantity, setQuantity] = useState(1);
    const [activeImageIndex, setActiveImageIndex] = useState(0);

    const images = item
        ? (Array.isArray(item.images) && item.images.length > 0
            ? item.images
            : (item.image ? [item.image] : []))
        : [];

    useEffect(() => {
        if (isOpen) {
            setQuantity(1);
            setActiveImageIndex(0);
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = '';
        }
        return () => {
            document.body.style.overflow = '';
        };
    }, [isOpen, item]);

    if (!isOpen || !item) return null;

    const handleIncrement = () => setQuantity((q) => Math.min(99, q + 1));
    const handleDecrement = () => setQuantity((q) => Math.max(1, q - 1));

    const handlePrevImage = (e) => {
        e.stopPropagation();
        setActiveImageIndex((prev) => (prev > 0 ? prev - 1 : images.length - 1));
    };

    const handleNextImage = (e) => {
        e.stopPropagation();
        setActiveImageIndex((prev) => (prev < images.length - 1 ? prev + 1 : 0));
    };

    const handleAdd = () => {
        onAddToCart(item, quantity);
        onClose();
    };

    const itemPrice = parseFloat(item.price_raw || item.price || 0);
    const calculatedSubtotal = (itemPrice * quantity).toFixed(2);
    const currentPhoto = images[activeImageIndex] || null;

    return (
        <div className="dish-modal-backdrop" onClick={onClose}>
            <div
                className="dish-modal-content"
                onClick={(e) => e.stopPropagation()}
                role="dialog"
                aria-modal="true"
                aria-labelledby="dish-modal-title"
            >
                <button
                    className="dish-modal-close-btn"
                    onClick={onClose}
                    aria-label="Close dish details"
                >
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" width="18" height="18">
                        <line x1="18" y1="6" x2="6" y2="18" />
                        <line x1="6" y1="6" x2="18" y2="18" />
                    </svg>
                </button>

                {/* Dish Photo Box / Multi-Image Gallery Slider */}
                <div className="dish-modal-image-box">
                    {currentPhoto ? (
                        <img
                            key={currentPhoto}
                            src={currentPhoto}
                            alt={`${item.name} (${activeImageIndex + 1})`}
                        />
                    ) : (
                        <div className="menu-card-image-placeholder">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5">
                                <path d="M18 8h1a4 4 0 010 8h-1M2 8h16v9a4 4 0 01-4 4H6a4 4 0 01-4-4V8zM6 1v3M10 1v3M14 1v3" />
                            </svg>
                        </div>
                    )}

                    {images.length > 1 && (
                        <>
                            <button
                                type="button"
                                className="dish-gallery-nav-btn prev"
                                onClick={handlePrevImage}
                                aria-label="Previous photo"
                            >
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" width="16" height="16">
                                    <polyline points="15 18 9 12 15 6" />
                                </svg>
                            </button>

                            <button
                                type="button"
                                className="dish-gallery-nav-btn next"
                                onClick={handleNextImage}
                                aria-label="Next photo"
                            >
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" width="16" height="16">
                                    <polyline points="9 18 15 12 9 6" />
                                </svg>
                            </button>

                            <span className="dish-gallery-counter">
                                {activeImageIndex + 1} / {images.length}
                            </span>

                            <div className="dish-gallery-dots">
                                {images.map((img, idx) => (
                                    <button
                                        key={img + idx}
                                        type="button"
                                        className={`dish-gallery-dot ${idx === activeImageIndex ? 'active' : ''}`}
                                        onClick={(e) => {
                                            e.stopPropagation();
                                            setActiveImageIndex(idx);
                                        }}
                                        aria-label={`View photo ${idx + 1}`}
                                    />
                                ))}
                            </div>
                        </>
                    )}
                </div>

                <div className="dish-modal-body">
                    <div className="dish-modal-header">
                        {item.category && (
                            <span className="dish-modal-tag">{item.category}</span>
                        )}
                        <h2 id="dish-modal-title" className="dish-modal-title">
                            {item.name}
                        </h2>
                        <div className="dish-modal-price">
                            {item.price} <span className="menu-card-currency">EGP</span>
                        </div>
                    </div>

                    {item.description && (
                        <p className="dish-modal-desc">{item.description}</p>
                    )}

                    <div className="dish-modal-controls">
                        <div className="modal-stepper">
                            <button
                                type="button"
                                className="modal-stepper-btn"
                                onClick={handleDecrement}
                                disabled={quantity <= 1}
                                aria-label="Decrease quantity"
                            >
                                −
                            </button>
                            <span className="modal-stepper-value">{quantity}</span>
                            <button
                                type="button"
                                className="modal-stepper-btn"
                                onClick={handleIncrement}
                                aria-label="Increase quantity"
                            >
                                +
                            </button>
                        </div>

                        <button
                            type="button"
                            className="dish-modal-submit-btn"
                            onClick={handleAdd}
                        >
                            <span>Add to Order</span>
                            <span>{calculatedSubtotal} EGP</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    );
}
