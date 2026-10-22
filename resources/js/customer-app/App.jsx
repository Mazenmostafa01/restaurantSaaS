import React from "react";
import { Routes, Route, Navigate } from "react-router-dom";
import { RestaurantProvider } from "./context/RestaurantContext";
import { AuthProvider } from "./context/AuthContext";
import { CartProvider } from "./context/CartContext";
import AppShell from "./components/layout/AppShell";
import MenuPage from "./pages/MenuPage";
import CartPage from "./pages/CartPage";
import CheckoutPage from "./pages/CheckoutPage";
import OrdersPage from "./pages/OrdersPage";
import OrderDetailPage from "./pages/OrderDetailPage";
import LoginPage from "./pages/LoginPage";
import RegisterPage from "./pages/RegisterPage";
import ProfilePage from "./pages/ProfilePage";

export default function App({ restaurant }) {
    return (
        <RestaurantProvider restaurant={restaurant}>
            <AuthProvider>
                <CartProvider slug={restaurant.slug}>
                    <Routes>
                        <Route element={<AppShell restaurant={restaurant} />}>
                            <Route index element={<MenuPage />} />
                            <Route path="menu" element={<MenuPage />} />
                            <Route path="cart" element={<CartPage />} />
                            <Route path="checkout" element={<CheckoutPage />} />
                            <Route path="orders" element={<OrdersPage />} />
                            <Route
                                path="orders/:id"
                                element={<OrderDetailPage />}
                            />
                            <Route path="login" element={<LoginPage />} />
                            <Route path="register" element={<RegisterPage />} />
                            <Route path="profile" element={<ProfilePage />} />
                            <Route
                                path="*"
                                element={<Navigate to="/" replace />}
                            />
                        </Route>
                    </Routes>
                </CartProvider>
            </AuthProvider>
        </RestaurantProvider>
    );
}
