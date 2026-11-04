import React, { useState } from "react";

export default function GroupBillSplitCard({
    splits,
    onDismiss = null,
    allowVatToggle = true,
    style = undefined,
}) {
    const [includeVat, setIncludeVat] = useState(true);

    const splitsEntries =
        splits && typeof splits === "object" ? Object.entries(splits) : [];

    if (splitsEntries.length === 0) {
        return null;
    }

    return (
        <div className="ai-splits-box" style={style}>
            <div className="ai-splits-head">
                <h4>👥 Group Bill Split (حساب الشلة)</h4>
                <div style={{ display: "flex", alignItems: "center", gap: "8px" }}>
                    {allowVatToggle && (
                        <button
                            type="button"
                            className="ai-vat-toggle"
                            onClick={() => setIncludeVat((prev) => !prev)}
                        >
                            {includeVat ? "Incl. 14% VAT" : "Subtotal only"}
                        </button>
                    )}
                    {onDismiss && (
                        <button
                            type="button"
                            className="ai-link-btn"
                            onClick={onDismiss}
                        >
                            Dismiss
                        </button>
                    )}
                </div>
            </div>
            <div className="ai-splits-grid">
                {splitsEntries.map(([personName, personSplit]) => {
                    const personSubtotal = Number(personSplit.subtotal || 0);
                    const displayAmount = includeVat
                        ? personSubtotal * 1.14
                        : personSubtotal;

                    return (
                        <div key={personName} className="ai-split-card">
                            <div className="ai-split-person-row">
                                <span className="ai-split-person" dir="auto">
                                    👤 {personName}
                                </span>
                                <span className="ai-split-amount">
                                    {displayAmount.toFixed(2)} EGP{" "}
                                    <small>
                                        {includeVat ? "(incl. VAT)" : "(excl. VAT)"}
                                    </small>
                                </span>
                            </div>
                            <div className="ai-split-items">
                                {(personSplit.items || []).map(
                                    (splitItem, idx) => (
                                        <span
                                            key={`${splitItem.id}-${idx}`}
                                            className="ai-split-item-tag"
                                        >
                                            {splitItem.quantity}×{" "}
                                            {splitItem.name}
                                        </span>
                                    ),
                                )}
                            </div>
                        </div>
                    );
                })}
            </div>
        </div>
    );
}
