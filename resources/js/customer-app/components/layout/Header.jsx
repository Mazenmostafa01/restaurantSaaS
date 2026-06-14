import React from 'react';
import { Link } from 'react-router-dom';
import { useAuth } from '../../context/AuthContext';
import CartBadge from '../cart/CartBadge';

export default function Header({ restaurant }) {
    const { customer } = useAuth();

    return (
        <header className="app-header">
            <div className="header-inner">
                <Link to="/" className="header-brand">
                    {restaurant.logo ? (
                        <img src={restaurant.logo} alt={restaurant.name} className="header-logo" />
                    ) : (
                        <span className="header-logo-placeholder">
                            {restaurant.name.charAt(0)}
                        </span>
                    )}
                    <h1 className="header-title">{restaurant.name}</h1>
                </Link>

                <div className="header-actions">
                    <CartBadge />
                    {customer ? (
                        <Link to="/profile" className="header-avatar" title={customer.name}>
                            {customer.name.charAt(0).toUpperCase()}
                        </Link>
                    ) : (
                        <Link to="/login" className="header-login-btn">Sign In</Link>
                    )}
                </div>
            </div>
        </header>
    );
}
