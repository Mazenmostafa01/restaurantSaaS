import { createCustomerClient } from "./client";

export function createCustomerApi(slug) {
    const client = createCustomerClient(slug);

    return {
        client,
        fetchMenu: async () => {
            const response = await client.get("/menu");
            return response.data.menu || [];
        },
        fetchOrders: async () => {
            const response = await client.get("/orders");
            return response.data.orders?.data || [];
        },
        fetchOrderById: async (orderId) => {
            const response = await client.get(`/orders/${orderId}`);
            return response.data.order;
        },
        placeOrder: async (payload) => {
            const response = await client.post("/orders", payload);
            return response.data;
        },
        parseNaturalLanguageOrder: async ({
            text = "",
            audioBlob = null,
            audioFilename = "voice-order.webm",
            preferredLanguage = null,
        }) => {
            if (audioBlob) {
                const formData = new FormData();
                formData.append("audio", audioBlob, audioFilename);
                if (text && text.trim()) {
                    formData.append("text", text.trim());
                }
                if (preferredLanguage) {
                    formData.append("preferred_language", preferredLanguage);
                }
                const response = await client.post("/orders/parse", formData, {
                    headers: {
                        "Content-Type": "multipart/form-data",
                    },
                });
                return response.data;
            }

            const payload = { text: text.trim() };
            if (preferredLanguage) {
                payload.preferred_language = preferredLanguage;
            }
            const response = await client.post("/orders/parse", payload);
            return response.data;
        },
        fetchCurrentCustomer: async () => {
            const response = await client.get("/user");
            return response.data.customer;
        },
        loginCustomer: async (email, password) => {
            const response = await client.post("/login", { email, password });
            return response.data;
        },
        registerCustomer: async (payload) => {
            const response = await client.post("/register", payload);
            return response.data;
        },
        logoutCustomer: async () => {
            await client.post("/logout");
        },
        updateCustomerProfile: async (payload) => {
            const response = await client.put("/profile", payload);
            return response.data;
        },
    };
}
