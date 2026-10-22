import { useState, useEffect, useMemo } from "react";
import { useRestaurant } from "../context/RestaurantContext";

export function useMenu() {
    const { slug, api } = useRestaurant();
    const [menu, setMenu] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [activeCategory, setActiveCategory] = useState("all");
    const [searchQuery, setSearchQuery] = useState("");

    useEffect(() => {
        let isMounted = true;
        setLoading(true);
        setError(null);

        api.fetchMenu()
            .then((menuGroups) => {
                if (isMounted) setMenu(menuGroups);
            })
            .catch(() => {
                if (isMounted) {
                    setError("Unable to load menu. Please refresh or try again later.");
                }
            })
            .finally(() => {
                if (isMounted) setLoading(false);
            });

        return () => {
            isMounted = false;
        };
    }, [slug, api]);

    const categories = useMemo(() => menu.map((group) => group.category), [menu]);
    const allItems = useMemo(() => menu.flatMap((group) => group.items), [menu]);

    const categoryCounts = useMemo(() => {
        const counts = {};
        menu.forEach((group) => {
            counts[group.category] = group.items.length;
        });
        return counts;
    }, [menu]);

    const filteredItems = useMemo(() => {
        let items =
            activeCategory === "all"
                ? allItems
                : menu.find((group) => group.category === activeCategory)?.items || [];

        if (searchQuery.trim()) {
            const query = searchQuery.toLowerCase();
            items = items.filter(
                (item) =>
                    item.name.toLowerCase().includes(query) ||
                    (item.description && item.description.toLowerCase().includes(query)),
            );
        }

        return items;
    }, [activeCategory, menu, allItems, searchQuery]);

    return {
        loading,
        error,
        categories,
        allItems,
        categoryCounts,
        filteredItems,
        activeCategory,
        setActiveCategory,
        searchQuery,
        setSearchQuery,
    };
}
