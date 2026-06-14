import React from 'react';

export default function CategoryTabs({ categories, active, onSelect }) {
    return (
        <div className="category-tabs">
            <button
                className={`category-tab ${active === 'all' ? 'active' : ''}`}
                onClick={() => onSelect('all')}
            >
                All
            </button>
            {categories.map(cat => (
                <button
                    key={cat}
                    className={`category-tab ${active === cat ? 'active' : ''}`}
                    onClick={() => onSelect(cat)}
                >
                    {cat.charAt(0).toUpperCase() + cat.slice(1)}
                </button>
            ))}
        </div>
    );
}
