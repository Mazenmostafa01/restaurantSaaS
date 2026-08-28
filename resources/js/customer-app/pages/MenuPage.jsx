import React, { useState, useEffect, useMemo } from "react";
import { createCustomerClient } from "../api/client";
import { useCart } from "../context/CartContext";
import MenuCard from "../components/menu/MenuCard";
import CategoryTabs from "../components/menu/CategoryTabs";
import DishDetailModal from "../components/menu/DishDetailModal";

export default function MenuPage({ slug }) {
    const [menu, setMenu] = useState([]);
    const [loading, setLoading] = useState(true);
    const [activeCategory, setActiveCategory] = useState("all");
    const [searchQuery, setSearchQuery] = useState("");
    const [error, setError] = useState(null);
    const [selectedDish, setSelectedDish] = useState(null);
    const [isModalOpen, setIsModalOpen] = useState(false);

    const client = createCustomerClient(slug);
    const { addItem } = useCart();
    const restaurant = window.__RESTAURANT__ || {};

    useEffect(() => {
        setLoading(true);
        setError(null);
        client
            .get('/menu')
            .then((res) => setMenu(res.data.menu || []))
            .catch(() => setError("Unable to load menu. Please refresh or try again later."))
            .finally(() => setLoading(false));
    }, [slug]);

    const categories = useMemo(() => menu.map((g) => g.category), [menu]);

    const allItems = useMemo(() => menu.flatMap((g) => g.items), [menu]);

    const categoryCounts = useMemo(() => {
        const counts = {};
        menu.forEach((g) => {
            counts[g.category] = g.items.length;
        });
        return counts;
    }, [menu]);

    const filteredItems = useMemo(() => {
        let items =
            activeCategory === "all"
                ? allItems
                : menu.find((g) => g.category === activeCategory)?.items || [];

        if (searchQuery.trim()) {
            const q = searchQuery.toLowerCase();
            items = items.filter(
                (item) =>
                    item.name.toLowerCase().includes(q) ||
                    (item.description && item.description.toLowerCase().includes(q)),
            );
        }

        return items;
    }, [activeCategory, menu, allItems, searchQuery]);

    const handleOpenDishModal = (dish) => {
        setSelectedDish(dish);
        setIsModalOpen(true);
    };

    const handleCloseDishModal = () => {
        setIsModalOpen(false);
        setSelectedDish(null);
    };

    const handleAddToCartFromModal = (dish, quantity) => {
        addItem(dish, quantity);
    };

    if (loading) {
        return (
            <div className="page-loader">
                <div className="spinner" />
                <p style={{ color: "var(--c-text-muted)", fontSize: "14px", marginTop: "8px" }}>
                    Preparing fresh menu...
                </p>
            </div>
        );
    }

    if (error) {
        return (
            <div className="page-error">
                <p>{error}</p>
                <button
                    className="btn btn-primary"
                    style={{ marginTop: "16px" }}
                    onClick={() => window.location.reload()}
                >
                    Retry
                </button>
            </div>
        );
    }

    return (
        <div className="menu-page">
            {/* Restaurant Hero Section */}
            <div className="restaurant-hero">
                <div className="hero-main">
                    <div>
                        <div className="hero-title-row">
                            <h2 className="hero-restaurant-name">{restaurant.name || "Restaurant Menu"}</h2>
                        </div>
                        <div className="hero-tags" style={{ marginTop: "10px" }}>
                            <span className="hero-tag tag-accent">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" />
                                </svg>
                                Freshly Crafted
                            </span>
                            <span className="hero-tag">
                                🥡 Take Away
                            </span>
                            <span className="hero-tag">
                                🚗 Delivery
                            </span>
                        </div>
                    </div>

                    <div className="hero-details">
                        {restaurant.address && (
                            <div className="hero-detail-item">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z" />
                                    <circle cx="12" cy="10" r="3" />
                                </svg>
                                <span>{restaurant.address}</span>
                            </div>
                        )}
                        {restaurant.phone && (
                            <div className="hero-detail-item">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                    <path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 16.92z" />
                                </svg>
                                <a href={`tel:${restaurant.phone}`}>{restaurant.phone}</a>
                            </div>
                        )}
                    </div>
                </div>
            </div>

            {/* Instant Search Bar */}
            <div className="search-bar-wrapper">
                <div className="search-input-box">
                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        strokeWidth="2"
                        className="search-icon"
                    >
                        <circle cx="11" cy="11" r="8" />
                        <line x1="21" y1="21" x2="16.65" y2="16.65" />
                    </svg>
                    <input
                        type="text"
                        className="search-input"
                        placeholder="Search delicious dishes, ingredients..."
                        value={searchQuery}
                        onChange={(e) => setSearchQuery(e.target.value)}
                        aria-label="Search menu items"
                    />
                    {searchQuery && (
                        <button
                            type="button"
                            className="search-clear-btn"
                            onClick={() => setSearchQuery("")}
                            aria-label="Clear search"
                        >
                            ✕
                        </button>
                    )}
                </div>
            </div>

            {/* Sticky Category Tabs */}
            <CategoryTabs
                categories={categories}
                active={activeCategory}
                onSelect={setActiveCategory}
                counts={categoryCounts}
                totalCount={allItems.length}
            />

            {/* Menu Dish Grid */}
            <div className="menu-grid">
                {filteredItems.map((item) => (
                    <MenuCard
                        key={item.id}
                        item={item}
                        onCardClick={handleOpenDishModal}
                    />
                ))}
            </div>

            {/* Empty States */}
            {filteredItems.length === 0 && (
                <div className="cart-empty" style={{ marginTop: "24px" }}>
                    <div className="cart-empty-icon-box">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5">
                            <circle cx="11" cy="11" r="8" />
                            <line x1="21" y1="21" x2="16.65" y2="16.65" />
                        </svg>
                    </div>
                    <h3>No dishes found</h3>
                    <p>
                        {searchQuery
                            ? `We couldn't find anything matching "${searchQuery}".`
                            : "There are currently no items in this category."}
                    </p>
                    {searchQuery ? (
                        <button
                            type="button"
                            className="btn btn-outline"
                            onClick={() => setSearchQuery("")}
                        >
                            Clear Search
                        </button>
                    ) : (
                        <button
                            type="button"
                            className="btn btn-outline"
                            onClick={() => setActiveCategory("all")}
                        >
                            Show All Dishes
                        </button>
                    )}
                </div>
            )}

            {/* Dish Detail Modal */}
            <DishDetailModal
                item={selectedDish}
                isOpen={isModalOpen}
                onClose={handleCloseDishModal}
                onAddToCart={handleAddToCartFromModal}
            />
        </div>
    );
}
