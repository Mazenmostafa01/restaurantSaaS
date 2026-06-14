import React from "react";
import { Routes, Route, Navigate } from "react-router-dom";
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
        <AuthProvider slug={restaurant.slug}>
            <CartProvider slug={restaurant.slug}>
                <Routes>
                    <Route element={<AppShell restaurant={restaurant} />}>
                        <Route
                            index
                            element={<MenuPage slug={restaurant.slug} />}
                        />
                        <Route
                            path="menu"
                            element={<MenuPage slug={restaurant.slug} />}
                        />
                        <Route path="cart" element={<CartPage />} />
                        <Route
                            path="checkout"
                            element={<CheckoutPage slug={restaurant.slug} />}
                        />
                        <Route
                            path="orders"
                            element={<OrdersPage slug={restaurant.slug} />}
                        />
                        <Route
                            path="orders/:id"
                            element={<OrderDetailPage slug={restaurant.slug} />}
                        />
                        <Route path="login" element={<LoginPage />} />
                        <Route path="register" element={<RegisterPage />} />
                        <Route path="profile" element={<ProfilePage />} />
                        <Route path="*" element={<Navigate to="" replace />} />
                    </Route>
                </Routes>
            </CartProvider>
        </AuthProvider>
    );
}
