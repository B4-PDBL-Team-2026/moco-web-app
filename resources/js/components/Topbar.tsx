import { Link } from '@inertiajs/react';
import { Menu, Bell, User } from 'lucide-react';

type BudgetStatus = 'stabil' | 'defisit' | 'kritis' | 'surplus';

interface TopbarProps {
    onMenuClick: () => void;
    status?: BudgetStatus;
}

const STATUS_CONFIG: Record<
    NonNullable<BudgetStatus>,
    { label: string; className: string }
> = {
    stabil: {
        label: 'Stabil',
        className: 'bg-primary text-white',
    },
    defisit: {
        label: 'Defisit',
        className: 'bg-red-500 text-white',
    },
    kritis: {
        label: 'Kritis',
        className: 'bg-orange-500 text-white',
    },
    surplus: {
        label: 'Surplus',
        className: 'bg-green-500 text-white',
    },
};

export default function Topbar({ onMenuClick, status }: TopbarProps) {
    const statusConfig = status ? STATUS_CONFIG[status] : null;

    return (
        <header className="sticky top-0 z-20 flex h-16 items-center justify-between border-b border-gray-100 bg-white px-4 lg:px-6">
            {/* Left: hamburger (mobile) */}
            <button
                onClick={onMenuClick}
                className="rounded-lg p-2 text-gray-500 hover:bg-gray-100 lg:hidden"
                aria-label="Open menu"
            >
                <Menu size={20} />
            </button>

            {/* Desktop: spacer */}
            <div className="hidden lg:block" />

            {/* Right: status badge + icons */}
            <div className="flex items-center gap-3">
                {statusConfig && (
                    <span
                        className={`rounded-full px-4 py-1.5 text-sm font-bold ${statusConfig.className}`}
                    >
                        {statusConfig.label}
                    </span>
                )}

                <Link
                    href="/notifications"
                    className="relative flex h-9 w-9 items-center justify-center rounded-full border border-gray-200 text-gray-500 transition hover:bg-gray-50"
                >
                    <Bell size={18} />
                </Link>

                <Link
                    href="/profile"
                    className="flex h-9 w-9 items-center justify-center rounded-full border border-gray-200 text-gray-500 transition hover:bg-gray-50"
                >
                    <User size={18} />
                </Link>
            </div>
        </header>
    );
}
