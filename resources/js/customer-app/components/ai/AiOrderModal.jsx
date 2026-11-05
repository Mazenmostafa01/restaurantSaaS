import React, { useState, useMemo } from "react";
import { useNavigate } from "react-router-dom";
import { useAuth } from "../../context/AuthContext";
import { useCart } from "../../context/CartContext";
import { useRestaurant } from "../../context/RestaurantContext";
import { useVoiceRecorder } from "../../hooks/useVoiceRecorder";
import NativeModal from "../common/NativeModal";
import AiAuthGate from "./AiAuthGate";
import AiOrderPromptForm from "./AiOrderPromptForm";
import AiOrderReview from "./AiOrderReview";

function hydrateMatchedDishes(parsedItems, menuItemsById) {
    return (parsedItems || []).map((parsedDish) => {
        const fullMenuDish = menuItemsById.get(Number(parsedDish.id));
        const priceRaw = parseFloat(
            parsedDish.price || fullMenuDish?.price_raw || 0,
        );
        return {
            id: Number(parsedDish.id),
            name: parsedDish.name || fullMenuDish?.name || "Dish",
            price: priceRaw.toFixed(2),
            price_raw: priceRaw,
            quantity: Math.max(1, Number(parsedDish.quantity || 1)),
            ai_note: parsedDish.note || null,
            image: fullMenuDish?.image || null,
            images: fullMenuDish?.images || [],
            category: fullMenuDish?.category || null,
        };
    });
}

function resolveParseErrorMessage(err) {
    const status = err.response?.status;
    if (status === 429) {
        return "You have reached the AI order limit (10 requests/minute). Please wait a moment and try again.";
    }
    if (status === 401) {
        return "Please sign in to use AI Smart Ordering.";
    }
    return (
        err.response?.data?.message ||
        "Could not parse your order right now. Please try again."
    );
}

export default function AiOrderModal({ isOpen, onClose, allItems = [] }) {
    const { customer } = useAuth();
    const { addItemsBatch } = useCart();
    const { api } = useRestaurant();
    const navigate = useNavigate();

    const [activeTab, setActiveTab] = useState("text");
    const [textPrompt, setTextPrompt] = useState("");
    const [loading, setLoading] = useState(false);
    const [apiError, setApiError] = useState(null);

    const [parsedResult, setParsedResult] = useState(null);
    const [reviewItems, setReviewItems] = useState([]);
    const [unmatchedItems, setUnmatchedItems] = useState([]);
    const [reviewNote, setReviewNote] = useState("");

    const recorder = useVoiceRecorder();

    const menuItemsById = useMemo(() => {
        const map = new Map();
        for (const menuDish of allItems) {
            map.set(Number(menuDish.id), menuDish);
        }
        return map;
    }, [allItems]);

    const examplePrompts = useMemo(() => {
        const firstDish = allItems[0]?.name;
        const secondDish = allItems[1]?.name;
        if (firstDish && secondDish) {
            return [
                {
                    label: "🇪🇬 Egyptian Arabic",
                    text: `عايز اتنين ${firstDish} واحد بدون بصل، وواحد ${secondDish}`,
                },
                {
                    label: "💬 Franco-Arabic",
                    text: `2 ${firstDish} w 1 ${secondDish} extra sauce`,
                },
                {
                    label: "👥 El-Shilla Split",
                    text: `أحمد: ١ ${firstDish}، سارة: ٢ ${secondDish}`,
                },
            ];
        }
        return [
            {
                label: "🇪🇬 Egyptian Arabic",
                text: "عايز اتنين كشري كبير واحد بدون بصل و كتر الشطة",
            },
            {
                label: "💬 Franco-Arabic",
                text: "2 burger w 1 batates w 2 pepsi",
            },
            {
                label: "👥 El-Shilla Split",
                text: "أحمد: ١ شاورما فراخ، سارة: ٢ كريب نوتيلا",
            },
        ];
    }, [allItems]);

    const handleClose = () => {
        recorder.resetRecording();
        onClose();
    };

    const submitOrderParse = async (event) => {
        if (event) event.preventDefault();
        if (loading) return;

        const isVoiceMode = activeTab === "voice";
        if (isVoiceMode && !recorder.audioBlob) {
            setApiError("Please record a voice note first before analyzing.");
            return;
        }
        if (!isVoiceMode && !textPrompt.trim()) {
            setApiError("Please type what you would like to order.");
            return;
        }

        setLoading(true);
        setApiError(null);

        try {
            const parsedOrderResponse = await api.parseNaturalLanguageOrder({
                text: isVoiceMode ? "" : textPrompt,
                audioBlob: isVoiceMode ? recorder.audioBlob : null,
                audioFilename: recorder.audioFilename,
            });

            setParsedResult(parsedOrderResponse);
            setReviewItems(
                hydrateMatchedDishes(
                    parsedOrderResponse.items,
                    menuItemsById,
                ),
            );
            setUnmatchedItems(parsedOrderResponse.unmatched || []);
            setReviewNote(parsedOrderResponse.note || "");
        } catch (err) {
            setApiError(resolveParseErrorMessage(err));
        } finally {
            setLoading(false);
        }
    };

    const updateReviewDishQuantity = (dishId, nextQuantity) => {
        if (nextQuantity <= 0) {
            setReviewItems((prev) =>
                prev.filter((reviewDish) => reviewDish.id !== dishId),
            );
            return;
        }
        setReviewItems((prev) =>
            prev.map((reviewDish) =>
                reviewDish.id === dishId
                    ? { ...reviewDish, quantity: Math.min(99, nextQuantity) }
                    : reviewDish,
            ),
        );
    };

    const selectCandidateDish = (unmatchedIndex, candidateName) => {
        const normalizedCandidate = candidateName.trim().toLowerCase();
        const matchedMenuDish = allItems.find(
            (menuDish) =>
                menuDish.name.toLowerCase() === normalizedCandidate ||
                menuDish.name.toLowerCase().includes(normalizedCandidate),
        );

        if (!matchedMenuDish) return;

        const priceRaw = parseFloat(
            matchedMenuDish.price_raw || matchedMenuDish.price || 0,
        );

        setReviewItems((prev) => {
            const existing = prev.find(
                (reviewDish) => reviewDish.id === matchedMenuDish.id,
            );
            if (existing) {
                return prev.map((reviewDish) =>
                    reviewDish.id === matchedMenuDish.id
                        ? { ...reviewDish, quantity: reviewDish.quantity + 1 }
                        : reviewDish,
                );
            }
            return [
                ...prev,
                {
                    id: matchedMenuDish.id,
                    name: matchedMenuDish.name,
                    price: priceRaw.toFixed(2),
                    price_raw: priceRaw,
                    quantity: 1,
                    ai_note: null,
                    image: matchedMenuDish.image || null,
                    images: matchedMenuDish.images || [],
                    category: matchedMenuDish.category || null,
                },
            ];
        });

        setUnmatchedItems((prev) =>
            prev.filter((_, idx) => idx !== unmatchedIndex),
        );
    };

    const commitReviewedOrder = (navigateToCheckout) => {
        if (reviewItems.length === 0) return;

        addItemsBatch(reviewItems, {
            note: reviewNote,
            splits: parsedResult?.splits || null,
        });

        setParsedResult(null);
        setReviewItems([]);
        setUnmatchedItems([]);
        setTextPrompt("");
        recorder.resetRecording();
        onClose();

        if (navigateToCheckout) {
            navigate("/checkout");
        }
    };

    const handleBrowseMenu = () => {
        handleClose();
        navigate("/menu");
    };

    return (
        <NativeModal
            isOpen={isOpen}
            onClose={handleClose}
            title="AI Smart Order"
            subtitle="Order in Arabic, Franco, or Voice — with instant group bill split"
            icon="✨"
        >
            {!customer ? (
                <AiAuthGate onDismiss={handleClose} />
            ) : !parsedResult ? (
                <AiOrderPromptForm
                    activeTab={activeTab}
                    onTabChange={(tab) => {
                        setActiveTab(tab);
                        setApiError(null);
                    }}
                    textPrompt={textPrompt}
                    onTextPromptChange={(text) => {
                        setTextPrompt(text);
                        setApiError(null);
                    }}
                    onSubmit={submitOrderParse}
                    examplePrompts={examplePrompts}
                    recorder={recorder}
                    loading={loading}
                    apiError={apiError}
                />
            ) : (
                <AiOrderReview
                    items={reviewItems}
                    unmatchedItems={unmatchedItems}
                    splits={parsedResult?.splits}
                    kitchenNote={reviewNote}
                    onKitchenNoteChange={setReviewNote}
                    onUpdateQuantity={updateReviewDishQuantity}
                    onSelectCandidate={selectCandidateDish}
                    onEditPrompt={() => {
                        setParsedResult(null);
                        setApiError(null);
                    }}
                    onAddToCart={() => commitReviewedOrder(false)}
                    onCheckout={() => commitReviewedOrder(true)}
                    onBrowseMenu={handleBrowseMenu}
                />
            )}
        </NativeModal>
    );
}
