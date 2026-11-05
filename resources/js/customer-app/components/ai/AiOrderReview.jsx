import React, { useId } from "react";
import { useNavigate } from "react-router-dom";
import GroupBillSplitCard from "./GroupBillSplitCard";

export default function AiOrderReview({
    items,
    unmatchedItems,
    splits,
    kitchenNote,
    onKitchenNoteChange,
    onUpdateQuantity,
    onSelectCandidate,
    onEditPrompt,
    onAddToCart,
    onCheckout,
    onBrowseMenu,
}) {
    const noteInputId = useId();
    const navigate = useNavigate();

    const handleBrowseMenu = () => {
        if (onBrowseMenu) {
            onBrowseMenu();
        } else {
            navigate("/menu");
        }
    };

    const subtotal = items.reduce(
        (sum, dish) => sum + dish.price_raw * dish.quantity,
        0,
    );
    const tax = subtotal * 0.14;
    const total = subtotal + tax;
    const totalCount = items.reduce((sum, dish) => sum + dish.quantity, 0);

    return (
        <div className="ai-review-pane">
            <div className="ai-section">
                <div className="ai-section-head">
                    <h4>✅ Matched Dishes ({totalCount})</h4>
                    <button
                        type="button"
                        className="ai-link-btn"
                        onClick={onEditPrompt}
                    >
                        ← Edit Prompt
                    </button>
                </div>

                {items.length === 0 ? (
                    <div className="ai-empty-matched">
                        No menu dishes matched yet. Select a suggestion below or
                        edit your prompt.
                    </div>
                ) : (
                    <div className="ai-matched-list">
                        {items.map((dish) => {
                            const thumb =
                                (Array.isArray(dish.images) &&
                                    dish.images[0]) ||
                                dish.image;
                            return (
                                <div key={dish.id} className="ai-matched-card">
                                    <div className="ai-matched-info">
                                        {thumb ? (
                                            <img
                                                src={thumb}
                                                alt={dish.name}
                                                className="ai-matched-thumb"
                                            />
                                        ) : (
                                            <div className="ai-matched-thumb placeholder">
                                                🍽️
                                            </div>
                                        )}
                                        <div className="ai-matched-meta">
                                            <div className="ai-matched-name">
                                                {dish.name}
                                            </div>
                                            <div className="ai-matched-price">
                                                {(
                                                    dish.price_raw *
                                                    dish.quantity
                                                ).toFixed(2)}{" "}
                                                EGP{" "}
                                                <span>({dish.price} ea)</span>
                                            </div>
                                            {dish.ai_note && (
                                                <span
                                                    className="ai-item-note-pill"
                                                    dir="auto"
                                                >
                                                    📝 {dish.ai_note}
                                                </span>
                                            )}
                                        </div>
                                    </div>

                                    <div className="cart-item-controls">
                                        <button
                                            type="button"
                                            className="cart-qty-btn"
                                            onClick={() =>
                                                onUpdateQuantity(
                                                    dish.id,
                                                    dish.quantity - 1,
                                                )
                                            }
                                            aria-label={`Decrease ${dish.name}`}
                                        >
                                            −
                                        </button>
                                        <span className="cart-qty-value">
                                            {dish.quantity}
                                        </span>
                                        <button
                                            type="button"
                                            className="cart-qty-btn"
                                            onClick={() =>
                                                onUpdateQuantity(
                                                    dish.id,
                                                    dish.quantity + 1,
                                                )
                                            }
                                            aria-label={`Increase ${dish.name}`}
                                        >
                                            +
                                        </button>
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                )}
            </div>

            {unmatchedItems.length > 0 && (
                <div className="ai-unmatched-box">
                    <div className="ai-unmatched-title">
                        ⚠️ Needs Your Attention ({unmatchedItems.length})
                    </div>
                    <div className="ai-unmatched-list">
                        {unmatchedItems.map((unmatched, idx) => (
                            <div
                                key={`${unmatched.raw_text}-${idx}`}
                                className="ai-unmatched-item"
                            >
                                <div className="ai-unmatched-row">
                                    <span
                                        className="ai-unmatched-raw"
                                        dir="auto"
                                    >
                                        &ldquo;{unmatched.raw_text}&rdquo;
                                    </span>
                                    <span className="ai-unmatched-reason">
                                        {unmatched.reason === "ambiguous"
                                            ? "Multiple options"
                                            : "Not on menu"}
                                    </span>
                                </div>
                                {Array.isArray(unmatched.candidate_matches) &&
                                    unmatched.candidate_matches.length > 0 && (
                                        <div className="ai-candidates-row">
                                            <span>Tap to add:</span>
                                            {unmatched.candidate_matches.map(
                                                (candidateName) => (
                                                    <button
                                                        key={candidateName}
                                                        type="button"
                                                        className="ai-candidate-pill"
                                                        onClick={() =>
                                                            onSelectCandidate(
                                                                idx,
                                                                candidateName,
                                                            )
                                                        }
                                                    >
                                                        + {candidateName}
                                                    </button>
                                                ),
                                            )}
                                        </div>
                                    )}
                            </div>
                        ))}
                    </div>
                </div>
            )}

            <GroupBillSplitCard splits={splits} />

            <div className="form-group">
                <label htmlFor={noteInputId} className="form-label">
                    Kitchen Instructions (extracted by AI)
                </label>
                <input
                    id={noteInputId}
                    type="text"
                    dir="auto"
                    maxLength={255}
                    className="form-input"
                    placeholder="Any special requests for the kitchen..."
                    value={kitchenNote}
                    onChange={(e) => onKitchenNoteChange(e.target.value)}
                />
            </div>

            <div className="ai-review-totals">
                <div className="cart-summary-row">
                    <span>Subtotal</span>
                    <span>{subtotal.toFixed(2)} EGP</span>
                </div>
                <div className="cart-summary-row">
                    <span>Tax (14% VAT)</span>
                    <span>{tax.toFixed(2)} EGP</span>
                </div>
                <div className="cart-summary-row cart-summary-total">
                    <span>Total</span>
                    <span>{total.toFixed(2)} EGP</span>
                </div>
            </div>

            <div className="ai-review-actions">
                {items.length === 0 ? (
                    <button
                        type="button"
                        className="btn btn-primary"
                        style={{ gridColumn: "1 / -1" }}
                        onClick={handleBrowseMenu}
                    >
                        Browse Menu
                    </button>
                ) : (
                    <>
                        <button
                            type="button"
                            className="btn btn-outline"
                            onClick={onAddToCart}
                        >
                            Add to Cart ({totalCount})
                        </button>
                        <button
                            type="button"
                            className="btn btn-primary"
                            onClick={onCheckout}
                        >
                            <span>Checkout Now</span>
                            <span>•</span>
                            <span>{total.toFixed(2)} EGP</span>
                        </button>
                    </>
                )}
            </div>
        </div>
    );
}
