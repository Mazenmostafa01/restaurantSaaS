import React, { useState, useEffect } from "react";
import client from "../api/client";
import MenuCard from "../components/menu/MenuCard";
import CategoryTabs from "../components/menu/CategoryTabs";

export default function MenuPage({ slug }) {
    const [menu, setMenu] = useState([]);
    const [loading, setLoading] = useState(true);
    const [activeCategory, setActiveCategory] = useState("all");
    const [error, setError] = useState(null);

    useEffect(() => {
        setLoading(true);
        setError(null);
        client
            .get(`/${slug}/menu`)
            .then((res) => setMenu(res.data.menu))
            .catch(() => setError("Failed to load menu."))
            .finally(() => setLoading(false));
    }, [slug]);

    if (loading) {
        return (
            <div className="page-loader">
                <div className="spinner" />
            </div>
        );
    }

    if (error) {
        return <div className="page-error">{error}</div>;
    }

    const categories = menu.map((g) => g.category);
    const allItems = menu.flatMap((g) => g.items);
    const filteredItems =
        activeCategory === "all"
            ? allItems
            : menu.find((g) => g.category === activeCategory)?.items || [];

    return (
        <div className="menu-page">
            <div className="menu-page-header">
                <h2>Our Menu</h2>
                <p className="menu-page-subtitle">
                    {allItems.length} items available
                </p>
            </div>

            <CategoryTabs
                categories={categories}
                active={activeCategory}
                onSelect={setActiveCategory}
            />

            <div className="menu-grid">
                {filteredItems.map((item) => (
                    <MenuCard key={item.id} item={item} />
                ))}
            </div>

            {filteredItems.length === 0 && (
                <div className="menu-empty">No items in this category.</div>
            )}
        </div>
    );
}
