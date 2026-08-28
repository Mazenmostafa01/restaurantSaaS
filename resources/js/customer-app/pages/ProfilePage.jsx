import React, { useState } from "react";
import { Navigate, Link } from "react-router-dom";
import { useAuth } from "../context/AuthContext";

export default function ProfilePage() {
    const { customer, loading, logout, updateProfile } = useAuth();
    const [editing, setEditing] = useState(false);
    const [form, setForm] = useState({});
    const [saving, setSaving] = useState(false);
    const [message, setMessage] = useState(null);

    if (!loading && !customer) {
        return <Navigate to="/login" replace />;
    }

    if (loading) {
        return (
            <div className="page-loader">
                <div className="spinner" />
                <p style={{ color: "var(--c-text-muted)", fontSize: "14px", marginTop: "8px" }}>
                    Loading your profile...
                </p>
            </div>
        );
    }

    const startEdit = () => {
        setForm({
            name: customer.name,
            phone_number: customer.phone_number,
            address: customer.address || "",
        });
        setEditing(true);
        setMessage(null);
    };

    const handleSave = async (e) => {
        e.preventDefault();
        setSaving(true);
        setMessage(null);
        try {
            await updateProfile(form);
            setEditing(false);
            setMessage("Profile updated successfully!");
        } catch (err) {
            setMessage(err.response?.data?.message || "Failed to update profile. Please try again.");
        } finally {
            setSaving(false);
        }
    };

    return (
        <div className="profile-page">
            <h2 className="page-title">Your Profile</h2>

            {message && <div className="profile-message">{message}</div>}

            {!editing ? (
                <div className="profile-card">
                    <div className="profile-avatar-large">
                        {customer.name.charAt(0).toUpperCase()}
                    </div>
                    <div className="profile-name-title">{customer.name}</div>
                    <div className="profile-email-sub">{customer.email}</div>

                    <div className="profile-details-table">
                        <div className="profile-detail-row">
                            <span className="profile-detail-label">Phone Number</span>
                            <span className="profile-detail-value">{customer.phone_number}</span>
                        </div>
                        <div className="profile-detail-row">
                            <span className="profile-detail-label">Default Address</span>
                            <span className="profile-detail-value">{customer.address || "Not specified"}</span>
                        </div>
                    </div>

                    <div className="profile-actions">
                        <button
                            type="button"
                            className="btn btn-outline"
                            onClick={startEdit}
                            style={{ flex: 1 }}
                        >
                            Edit Profile
                        </button>
                        <button
                            type="button"
                            className="btn btn-danger"
                            onClick={logout}
                            style={{ flex: 1 }}
                        >
                            Sign Out
                        </button>
                    </div>

                    <div style={{ marginTop: "20px", width: "100%", textAlign: "center" }}>
                        <Link
                            to="/orders"
                            style={{ fontSize: "13px", color: "var(--c-primary)", fontWeight: 600 }}
                        >
                            View Your Order History →
                        </Link>
                    </div>
                </div>
            ) : (
                <form onSubmit={handleSave} className="profile-edit-form">
                    <h3 style={{ fontSize: "18px", fontWeight: 700 }}>Edit Information</h3>

                    <div className="form-group">
                        <label htmlFor="prof-name" className="form-label">
                            Full Name
                        </label>
                        <input
                            id="prof-name"
                            type="text"
                            className="form-input"
                            value={form.name}
                            onChange={(e) =>
                                setForm((p) => ({ ...p, name: e.target.value }))
                            }
                            required
                        />
                    </div>

                    <div className="form-group">
                        <label htmlFor="prof-phone" className="form-label">
                            Phone Number
                        </label>
                        <input
                            id="prof-phone"
                            type="tel"
                            className="form-input"
                            value={form.phone_number}
                            onChange={(e) =>
                                setForm((p) => ({
                                    ...p,
                                    phone_number: e.target.value,
                                }))
                            }
                            required
                        />
                    </div>

                    <div className="form-group">
                        <label htmlFor="prof-address" className="form-label">
                            Delivery Address
                        </label>
                        <textarea
                            id="prof-address"
                            className="form-textarea"
                            value={form.address}
                            onChange={(e) =>
                                setForm((p) => ({
                                    ...p,
                                    address: e.target.value,
                                }))
                            }
                            rows={3}
                            placeholder="Street, building, apartment/floor details..."
                        />
                    </div>

                    <div className="profile-actions" style={{ marginTop: "8px" }}>
                        <button
                            type="submit"
                            className="btn btn-primary"
                            disabled={saving}
                            style={{ flex: 1 }}
                        >
                            {saving ? "Saving Changes..." : "Save Changes"}
                        </button>
                        <button
                            type="button"
                            className="btn btn-outline"
                            onClick={() => setEditing(false)}
                            style={{ flex: 1 }}
                        >
                            Cancel
                        </button>
                    </div>
                </form>
            )}
        </div>
    );
}
