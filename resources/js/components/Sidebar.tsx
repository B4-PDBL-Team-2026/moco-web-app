import { Link, usePage, router } from '@inertiajs/react';
import {
    LayoutDashboard,
    ScrollText,
    CalendarClock,
    Tag,
    Settings,
    LogOut,
    X,
} from 'lucide-react';
import React, { useState } from 'react';

const NAV_ITEMS = [
    {
        label: 'Dashboard',
        href: '/dashboard',
        icon: LayoutDashboard,
        routeName: 'dashboard',
    },
    {
        label: 'Riwayat Transaksi',
        href: '/history',
        icon: ScrollText,
        routeName: 'history',
    },
    {
        label: 'Biaya Tetap',
        href: '/fixed-costs/occurrences',
        icon: CalendarClock,
        routeName: 'fixed-costs',
    },
    {
        label: 'Kategori',
        href: '/categories',
        icon: Tag,
        routeName: 'categories',
    },
];

const BOTTOM_ITEMS = [
    {
        label: 'Pengaturan',
        href: '/settings',
        icon: Settings,
        routeName: 'settings',
    },
    {
        label: 'Keluar',
        action: 'logout', 
        icon: LogOut,
    },
];

interface SidebarProps {
    open: boolean;
    onClose: () => void;
    onLogout?: () => void; // 
}
function NavLink({
    item,
    onClick,
}: {
    item: (typeof NAV_ITEMS)[0];
    onClick?: () => void;
}) {
    const { url } = usePage();
    const isActive = url.startsWith(item.href);
    const Icon = item.icon;

    return (
        <Link
            href={item.href}
            onClick={onClick}
            className={`flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium transition-all duration-150 ${
                isActive
                    ? 'bg-primary text-white shadow-sm'
                    : 'text-gray-500 hover:bg-primary-light hover:text-primary'
            }`}
        >
            <Icon size={18} strokeWidth={isActive ? 2.5 : 1.8} />
            {item.label}
        </Link>
    );
}

export default function Sidebar({ open, onClose }: SidebarProps) {
    const { url } = usePage();
    
    const [isLogoutModalOpen, setIsLogoutModalOpen] = useState(false);

    const handleLogout = () => {
        router.delete('/auth/logout', {
            onSuccess: () => setIsLogoutModalOpen(false),
        });
    };

    return (
        <>
            {/* Mobile overlay */}
            {open && (
                <div
                    className="fixed inset-0 z-30 bg-black/40 backdrop-blur-sm lg:hidden"
                    onClick={onClose}
                />
            )}

            {/* Sidebar panel */}
            <aside
                className={`fixed inset-y-0 left-0 z-40 flex w-64 flex-col bg-white shadow-xl transition-transform duration-300 ease-in-out lg:static lg:z-auto lg:translate-x-0 lg:border-r lg:border-gray-100 lg:shadow-none ${
                    open ? 'translate-x-0' : '-translate-x-full'
                }`}
            >
                {/* Logo */}
                <div className="flex items-center justify-between border-b border-gray-100 px-5 py-5">
                    <div className="flex items-center gap-2.5">
                        <div className="flex h-12 w-12 items-center justify-center rounded-lg shadow-md">
                            <img src="/logo.png" alt="MOCO logo" />
                        </div>
                        <div>
                            <p className="text-2xl leading-none font-bold text-primary">
                                MOCO
                            </p>
                            <p className="mt-0.5 text-xs font-bold leading-none text-gray-400">
                                Money Control
                            </p>
                        </div>
                    </div>
                    <button
                        onClick={onClose}
                        className="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 lg:hidden"
                    >
                        <X size={18} />
                    </button>
                </div>

                {/* Main nav */}
                <nav className="flex-1 space-y-1 overflow-y-auto px-3 py-4">
                    {NAV_ITEMS.map((item) => (
                        <NavLink
                            key={item.href}
                            item={item}
                            onClick={onClose}
                        />
                    ))}
                </nav>

                {/* Bottom nav */}
                <div className="space-y-1 border-t border-gray-100 px-3 py-4">
                    {BOTTOM_ITEMS.map((item) => {
                        const Icon = item.icon;

                        // Jika item adalah tombol Keluar, gunakan <button> untuk membuka modal
                        if (item.action === 'logout') {
                            return (
                                <button
                                    key="logout"
                                    onClick={() => {
                                        setIsLogoutModalOpen(true);
                                        onClose(); // Tutup sidebar di versi mobile saat modal terbuka
                                    }}
                                    className="flex w-full items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium text-gray-500 transition hover:bg-red-50 hover:text-red-500"
                                >
                                    <Icon size={18} strokeWidth={1.8} />
                                    {item.label}
                                </button>
                            );
                        }

                        const isActive = item.href && url.startsWith(item.href);
                        return (
                            <Link
                                key={item.href}
                                href={item.href}
                                onClick={onClose}
                                className={`flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium transition ${
                                    isActive
                                        ? 'bg-primary text-white'
                                        : 'text-gray-500 hover:bg-primary-light hover:text-primary'
                                }`}
                            >
                                <Icon size={18} strokeWidth={1.8} />
                                {item.label}
                            </Link>
                        );
                    })}
                </div>
            </aside>

            {/* MODAL KONFIRMASI KELUAR */}
            {isLogoutModalOpen && (
                <div className="fixed inset-0 z-[60] flex items-center justify-center bg-black/50">
                    <div className="w-full max-w-sm rounded-[24px] bg-white p-8 text-center shadow-2xl animate-in fade-in zoom-in-95 duration-200">
                        <h2 className="mb-3 text-xl font-bold text-[#101010]">Keluar dari akun?</h2>
                        <p className="mb-8 text-sm leading-relaxed text-[#595D62]">
                            Sesi login akan dihapus dari perangkat ini.
                        </p>
                        <div className="flex gap-4">
                            <button
                                type="button"
                                onClick={() => setIsLogoutModalOpen(false)}
                                className="flex-1 rounded-xl border border-[#2E5AA7] py-3 font-semibold text-[#2E5AA7] transition hover:bg-blue-50"
                            >
                                Batal
                            </button>
                            <button
                                type="button"
                                onClick={handleLogout}
                                className="flex-1 rounded-xl bg-[#FF4E64] py-3 font-semibold text-white shadow-md shadow-red-100 transition hover:bg-[#e04455]"
                            >
                                Keluar
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </>
    );
}