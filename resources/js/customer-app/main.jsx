import React from "react";
import { createRoot } from "react-dom/client";
import { BrowserRouter } from "react-router-dom";
import App from "./App";
import "./styles/customer.css";

const container = document.getElementById("customer-app");
const restaurant = window.__RESTAURANT__;

if (container && restaurant) {
    const root = createRoot(container);
    root.render(
        <BrowserRouter basename={`/${restaurant.slug}`}>
            <App restaurant={restaurant} />
        </BrowserRouter>,
    );
}
