import React from 'react';

export default function CategoryTabs({ categories, active, onSelect, counts = {}, totalCount = 0 }) {
    return (
        <div className="category-tabs-container">
            <div className="category-tabs" role="tablist" aria-label="Menu categories">
                <button
                    type="button"
                    role="tab"
                    aria-selected={active === 'all'}
                    className={`category-tab ${active === 'all' ? 'active' : ''}`}
                    onClick={() => onSelect('all')}
                >
                    <span>All Dishes</span>
                    {totalCount > 0 && <span className="category-count">{totalCount}</span>}
                </button>
                {categories.map((cat) => {
                    const count = counts[cat] || 0;
                    return (
                        <button
                            key={cat}
                            type="button"
                            role="tab"
                            aria-selected={active === cat}
                            className={`category-tab ${active === cat ? 'active' : ''}`}
                            onClick={() => onSelect(cat)}
                        >
                            <span>{cat.charAt(0).toUpperCase() + cat.slice(1)}</span>
                            {count > 0 && <span className="category-count">{count}</span>}
                        </button>
                    );
                })}
            </div>
        </div>
    );
}
