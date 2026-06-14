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
                        err.response?.data?.message || "Registration failed.",
                    ],
                });
            }
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <div className="auth-page">
            <div className="auth-card">
                <h2>Create Account</h2>
                <p className="auth-subtitle">Join us and start ordering</p>

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
                            placeholder="John Doe"
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
                            Email
                        </label>
                        <input
                            id="reg-email"
                            name="email"
                            type="email"
                            className="form-input"
                            value={form.email}
                            onChange={handleChange}
                            placeholder="you@example.com"
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
                            Address
                        </label>
                        <input
                            id="address"
                            name="address"
                            type="text"
                            className="form-input"
                            value={form.address}
                            onChange={handleChange}
                            placeholder="Your delivery address"
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
                        <input
                            id="reg-password"
                            name="password"
                            type="password"
                            className="form-input"
                            value={form.password}
                            onChange={handleChange}
                            placeholder="••••••••"
                            required
                        />
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
                            type="password"
                            className="form-input"
                            value={form.password_confirmation}
                            onChange={handleChange}
                            placeholder="••••••••"
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
                    >
                        {submitting ? "Creating Account..." : "Create Account"}
                    </button>
                </form>

                <p className="auth-footer">
                    Already have an account? <Link to="/login">Sign in</Link>
                </p>
            </div>
        </div>
    );
}
