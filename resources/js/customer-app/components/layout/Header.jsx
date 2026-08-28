import React from 'react';
import { Link } from 'react-router-dom';
import { useAuth } from '../../context/AuthContext';
import CartBadge from '../cart/CartBadge';

export default function Header({ restaurant }) {
    const { customer } = useAuth();

    return (
        <header className="app-header">
            <div className="header-inner">
                <Link to="/" className="header-brand" title={`Home - ${restaurant.name}`}>
                    <div className="header-logo-wrapper">
                        {restaurant.logo ? (
                            <img src={restaurant.logo} alt={restaurant.name} className="header-logo" />
                        ) : (
                            <span className="header-logo-placeholder">
                                {restaurant.name.charAt(0)}
                            </span>
                        )}
                    </div>
                    <div className="header-brand-info">
                        <h1 className="header-title">{restaurant.name}</h1>
                        <span className="header-badge-open">
                            <span className="status-dot" />
                            Open for Orders
                        </span>
                    </div>
                </Link>

                <div className="header-actions">
                    {restaurant.phone && (
                        <a
                            href={`tel:${restaurant.phone}`}
                            className="header-tel-btn"
                            title={`Call ${restaurant.name} (${restaurant.phone})`}
                            aria-label="Call Restaurant"
                        >
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" width="17" height="17">
                                <path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 16.92z" />
                            </svg>
                        </a>
                    )}

                    <CartBadge />

                    {customer ? (
                        <Link to="/profile" className="header-avatar" title={`Account (${customer.name})`}>
                            {customer.name.charAt(0).toUpperCase()}
                        </Link>
                    ) : (
                        <Link to="/login" className="header-login-btn">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" width="15" height="15">
                                <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2" />
                                <circle cx="12" cy="7" r="4" />
                            </svg>
                            <span>Sign In</span>
                        </Link>
                    )}
                </div>
            </div>
        </header>
    );
}
