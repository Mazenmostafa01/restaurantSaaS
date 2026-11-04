import React, { useEffect, useRef, useId } from "react";

export default function NativeModal({
    isOpen,
    onClose,
    title,
    subtitle,
    icon = null,
    className = "",
    children,
}) {
    const titleId = useId();
    const dialogRef = useRef(null);
    const pressedOutsideRef = useRef(false);

    useEffect(() => {
        const dialog = dialogRef.current;
        if (!dialog) return;

        if (isOpen) {
            if (!dialog.open) {
                dialog.showModal();
            }
        } else {
            if (dialog.open) {
                dialog.close();
            }
        }
    }, [isOpen]);

    useEffect(() => {
        const dialog = dialogRef.current;
        if (!dialog) return;

        const handleNativeClose = () => {
            dialog.returnValue = "";
            if (isOpen) {
                onClose();
            }
        };

        dialog.addEventListener("close", handleNativeClose);
        return () => dialog.removeEventListener("close", handleNativeClose);
    }, [isOpen, onClose]);

    const isPointerOutsideDialog = (event) => {
        const dialog = dialogRef.current;
        if (!dialog) return false;
        const rect = dialog.getBoundingClientRect();
        return (
            event.clientX < rect.left ||
            event.clientX > rect.right ||
            event.clientY < rect.top ||
            event.clientY > rect.bottom
        );
    };

    const handlePointerDown = (event) => {
        pressedOutsideRef.current = isPointerOutsideDialog(event);
    };

    const handlePointerUp = (event) => {
        if (pressedOutsideRef.current && isPointerOutsideDialog(event)) {
            onClose();
        }
        pressedOutsideRef.current = false;
    };

    return (
        <dialog
            ref={dialogRef}
            className={`ai-modal-dialog ${className}`.trim()}
            aria-labelledby={title ? titleId : undefined}
            onPointerDown={handlePointerDown}
            onPointerUp={handlePointerUp}
        >
            <div className="ai-modal-inner">
                {(title || icon) && (
                    <div className="ai-modal-header">
                        <div className="ai-modal-title-group">
                            {icon && (
                                <span
                                    className="ai-sparkle-badge"
                                    aria-hidden="true"
                                >
                                    {icon}
                                </span>
                            )}
                            <div>
                                {title && (
                                    <h2 id={titleId} className="ai-modal-title">
                                        {title}
                                    </h2>
                                )}
                                {subtitle && (
                                    <p className="ai-modal-subtitle">
                                        {subtitle}
                                    </p>
                                )}
                            </div>
                        </div>
                        <button
                            type="button"
                            className="dish-modal-close-btn ai-modal-close"
                            onClick={onClose}
                            aria-label="Close dialog"
                        >
                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                strokeWidth="2.5"
                                width="18"
                                height="18"
                            >
                                <line x1="18" y1="6" x2="6" y2="18" />
                                <line x1="6" y1="6" x2="18" y2="18" />
                            </svg>
                        </button>
                    </div>
                )}
                <div className="ai-modal-body">{children}</div>
            </div>
        </dialog>
    );
}
