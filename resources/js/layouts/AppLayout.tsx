import React, { useState } from 'react';
import Sidebar from '@/components/Sidebar';
import Topbar from '@/components/Topbar';

type BudgetStatus = 'stabil' | 'defisit' | 'kritis' | 'surplus';

interface AppLayoutProps {
    children: React.ReactNode;
    status?: BudgetStatus;
}

export default function AppLayout({ children, status }: AppLayoutProps) {
    const [sidebarOpen, setSidebarOpen] = useState(false);

    return (
        <div className="flex h-screen overflow-hidden bg-gray-50">
            <Sidebar open={sidebarOpen} onClose={() => setSidebarOpen(false)} />

            <div className="flex flex-1 flex-col overflow-hidden">
                <Topbar
                    onMenuClick={() => setSidebarOpen(true)}
                    status={status}
                />

                <main className="flex-1 overflow-y-auto">{children}</main>
            </div>
        </div>
    );
}
