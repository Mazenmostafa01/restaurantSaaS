import React, { useState } from "react";
import { Navigate } from "react-router-dom";
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
            setMessage("Profile updated!");
        } catch (err) {
            setMessage(err.response?.data?.message || "Update failed.");
        } finally {
            setSaving(false);
        }
    };

    return (
        <div className="profile-page">
            <h2>Your Profile</h2>

            {message && <div className="profile-message">{message}</div>}

            {!editing ? (
                <div className="profile-card">
                    <div className="profile-avatar">
                        {customer.name.charAt(0).toUpperCase()}
                    </div>
                    <div className="profile-info">
                        <div className="profile-row">
                            <span className="profile-label">Name</span>
                            <span className="profile-value">
                                {customer.name}
                            </span>
                        </div>
                        <div className="profile-row">
                            <span className="profile-label">Email</span>
                            <span className="profile-value">
                                {customer.email}
                            </span>
                        </div>
                        <div className="profile-row">
                            <span className="profile-label">Phone</span>
                            <span className="profile-value">
                                {customer.phone_number}
                            </span>
                        </div>
                        <div className="profile-row">
                            <span className="profile-label">Address</span>
                            <span className="profile-value">
                                {customer.address || "—"}
                            </span>
                        </div>
                    </div>

                    <div className="profile-actions">
                        <button className="btn btn-outline" onClick={startEdit}>
                            Edit Profile
                        </button>
                        <button className="btn btn-danger" onClick={logout}>
                            Sign Out
                        </button>
                    </div>
                </div>
            ) : (
                <form onSubmit={handleSave} className="profile-edit-form">
                    <div className="form-group">
                        <label className="form-label">Name</label>
                        <input
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
                        <label className="form-label">Phone</label>
                        <input
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
                        <label className="form-label">Address</label>
                        <input
                            type="text"
                            className="form-input"
                            value={form.address}
                            onChange={(e) =>
                                setForm((p) => ({
                                    ...p,
                                    address: e.target.value,
                                }))
                            }
                        />
                    </div>
                    <div className="profile-edit-actions">
                        <button
                            type="submit"
                            className="btn btn-primary"
                            disabled={saving}
                        >
                            {saving ? "Saving..." : "Save"}
                        </button>
                        <button
                            type="button"
                            className="btn btn-outline"
                            onClick={() => setEditing(false)}
                        >
                            Cancel
                        </button>
                    </div>
                </form>
            )}
        </div>
    );
}
