import React from 'react';
import { Outlet } from 'react-router-dom';
import Header from './Header';
import BottomNav from './BottomNav';
import FloatingCartBar from '../cart/FloatingCartBar';

export default function AppShell({ restaurant }) {
    return (
        <div className="app-shell">
            <Header restaurant={restaurant} />
            <main className="app-main">
                <Outlet />
            </main>
            <FloatingCartBar />
            <BottomNav />
        </div>
    );
}
