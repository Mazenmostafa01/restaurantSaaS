import React from "react";
import { useNavigate } from "react-router-dom";

export default function AiAuthGate({
    title = "Sign in to use AI Voice & Text Ordering",
    description = 'Speak naturally in Egyptian Arabic or type your group\'s order ("El-Shilla") and let AI build your cart and split the bill automatically.',
    icon = "🎙️",
    onDismiss = null,
}) {
    const navigate = useNavigate();

    const handleNavigate = (path) => {
        if (onDismiss) {
            onDismiss();
        }
        navigate(path);
    };

    return (
        <div className="ai-auth-gate">
            <div className="ai-auth-icon">{icon}</div>
            <h3>{title}</h3>
            <p>{description}</p>
            <div className="checkout-auth-actions">
                <button
                    type="button"
                    className="btn btn-primary"
                    onClick={() => handleNavigate("/login")}
                >
                    Sign In
                </button>
                <button
                    type="button"
                    className="btn btn-outline"
                    onClick={() => handleNavigate("/register")}
                >
                    Create Account
                </button>
            </div>
        </div>
    );
}
