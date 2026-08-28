import React, { useState } from "react";
import { Link, useNavigate, Navigate } from "react-router-dom";
import { useAuth } from "../context/AuthContext";

export default function RegisterPage() {
    const { customer, register } = useAuth();
    const navigate = useNavigate();

    const [form, setForm] = useState({
        name: "",
        email: "",
        phone_number: "",
        password: "",
        password_confirmation: "",
        address: "",
    });
    const [showPassword, setShowPassword] = useState(false);
    const [errors, setErrors] = useState({});
    const [submitting, setSubmitting] = useState(false);

    if (customer) {
        return <Navigate to="/" replace />;
    }

    const handleChange = (e) => {
        setForm((prev) => ({ ...prev, [e.target.name]: e.target.value }));
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        setSubmitting(true);
        setErrors({});

        try {
            await register(form);
            navigate("/");
        } catch (err) {
            if (err.response?.status === 422 && err.response?.data?.errors) {
                setErrors(err.response.data.errors);
            } else {
                setErrors({
                    general: [
                        err.response?.data?.message || "Registration failed. Please verify your details.",
                    ],
                });
            }
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <div className="auth-page">
            <div className="auth-card" style={{ maxWidth: "480px" }}>
                <h2>Create Account</h2>
                <p className="auth-subtitle">Join us to enjoy fast delivery and order tracking</p>

                <form onSubmit={handleSubmit} className="auth-form">
                    <div className="form-group">
                        <label htmlFor="name" className="form-label">
                            Full Name
                        </label>
                        <input
                            id="name"
                            name="name"
                            type="text"
                            className="form-input"
                            value={form.name}
                            onChange={handleChange}
                            placeholder="e.g. John Smith"
                            required
                        />
                        {errors.name && (
                            <span className="form-field-error">
                                {errors.name[0]}
                            </span>
                        )}
                    </div>

                    <div className="form-group">
                        <label htmlFor="reg-email" className="form-label">
                            Email Address
                        </label>
                        <input
                            id="reg-email"
                            name="email"
                            type="email"
                            className="form-input"
                            value={form.email}
                            onChange={handleChange}
                            placeholder="you@example.com"
                            autoComplete="email"
                            required
                        />
                        {errors.email && (
                            <span className="form-field-error">
                                {errors.email[0]}
                            </span>
                        )}
                    </div>

                    <div className="form-group">
                        <label htmlFor="phone_number" className="form-label">
                            Phone Number
                        </label>
                        <input
                            id="phone_number"
                            name="phone_number"
                            type="tel"
                            className="form-input"
                            value={form.phone_number}
                            onChange={handleChange}
                            placeholder="+20 1xx xxx xxxx"
                            required
                        />
                        {errors.phone_number && (
                            <span className="form-field-error">
                                {errors.phone_number[0]}
                            </span>
                        )}
                    </div>

                    <div className="form-group">
                        <label htmlFor="address" className="form-label">
                            Delivery Address
                        </label>
                        <input
                            id="address"
                            name="address"
                            type="text"
                            className="form-input"
                            value={form.address}
                            onChange={handleChange}
                            placeholder="Street name, building, apartment / office"
                            required
                        />
                        {errors.address && (
                            <span className="form-field-error">
                                {errors.address[0]}
                            </span>
                        )}
                    </div>

                    <div className="form-group">
                        <label htmlFor="reg-password" className="form-label">
                            Password
                        </label>
                        <div className="password-input-wrapper">
                            <input
                                id="reg-password"
                                name="password"
                                type={showPassword ? "text" : "password"}
                                className="form-input"
                                value={form.password}
                                onChange={handleChange}
                                placeholder="Minimum 6 characters"
                                required
                            />
                            <button
                                type="button"
                                className="password-toggle-btn"
                                onClick={() => setShowPassword(!showPassword)}
                                aria-label={showPassword ? "Hide password" : "Show password"}
                            >
                                {showPassword ? "Hide" : "Show"}
                            </button>
                        </div>
                        {errors.password && (
                            <span className="form-field-error">
                                {errors.password[0]}
                            </span>
                        )}
                    </div>

                    <div className="form-group">
                        <label
                            htmlFor="password_confirmation"
                            className="form-label"
                        >
                            Confirm Password
                        </label>
                        <input
                            id="password_confirmation"
                            name="password_confirmation"
                            type={showPassword ? "text" : "password"}
                            className="form-input"
                            value={form.password_confirmation}
                            onChange={handleChange}
                            placeholder="Re-enter your password"
                            required
                        />
                    </div>

                    {errors.general && (
                        <div className="form-error">{errors.general[0]}</div>
                    )}

                    <button
                        type="submit"
                        className="btn btn-primary btn-block"
                        disabled={submitting}
                        style={{ padding: "14px 20px", marginTop: "4px" }}
                    >
                        {submitting ? "Creating Account..." : "Complete Registration"}
                    </button>
                </form>

                <p className="auth-footer">
                    Already have an account?{" "}
                    <Link to="/login" style={{ color: "var(--c-primary)" }}>
                        Sign in here
                    </Link>
                </p>
            </div>
        </div>
    );
}
