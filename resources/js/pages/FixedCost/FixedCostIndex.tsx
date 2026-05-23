import { Head, router } from '@inertiajs/react';
import { Pencil, Loader2 } from 'lucide-react';
import { useState, useCallback } from 'react';
import FilterTabs from '@/components/FilterTabs';
import type { OccurrenceTab } from '@/components/FilterTabs';
import MonthYearPicker from '@/components/MonthYearPicker';
import OccurrenceCard from '@/components/OccurrenceCard';
import type { Occurrence } from '@/components/OccurrenceCard';
import AppLayout from '@/layouts/AppLayout';

interface Props {
    occurrences: Occurrence[];
    status?: 'stabil' | 'defisit' | 'kritis' | 'surplus';
    filters: {
        month: number;
        year: number;
        tab: OccurrenceTab;
    };
    counts: {
        pending: number;
        paid: number;
        skipped: number;
    };
}

function EmptyState({ tab }: { tab: OccurrenceTab }) {
    const messages: Record<OccurrenceTab, { title: string; sub: string }> = {
        pending: {
            title: 'Tidak ada tagihan tertunda',
            sub: 'Semua tagihan sudah dibayar atau dilewati.',
        },
        paid: {
            title: 'Belum ada pembayaran',
            sub: 'Tagihan yang sudah dibayar akan muncul di sini.',
        },
        skipped: {
            title: 'Tidak ada yang dilewati',
            sub: 'Tagihan yang dilewati akan muncul di sini.',
        },
    };

    const { title, sub } = messages[tab];

    return (
        <div className="flex flex-col items-center justify-center py-20 text-center">
            <div className="mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-primary-light">
                <svg
                    className="h-8 w-8 text-primary"
                    viewBox="0 0 32 32"
                    fill="none"
                    xmlns="http://www.w3.org/2000/svg"
                >
                    <rect
                        x="5"
                        y="3"
                        width="22"
                        height="28"
                        rx="3"
                        stroke="currentColor"
                        strokeWidth="2"
                    />
                    <path
                        d="M5 28l3-3 3 3 3-3 3 3 3-3 3 3"
                        stroke="currentColor"
                        strokeWidth="2"
                        strokeLinecap="round"
                        strokeLinejoin="round"
                    />
                    <line
                        x1="11"
                        y1="12"
                        x2="21"
                        y2="12"
                        stroke="currentColor"
                        strokeWidth="2"
                        strokeLinecap="round"
                    />
                    <line
                        x1="11"
                        y1="17"
                        x2="21"
                        y2="17"
                        stroke="currentColor"
                        strokeWidth="2"
                        strokeLinecap="round"
                    />
                    <line
                        x1="11"
                        y1="22"
                        x2="16"
                        y2="22"
                        stroke="currentColor"
                        strokeWidth="2"
                        strokeLinecap="round"
                    />
                </svg>
            </div>
            <p className="text-base font-semibold text-gray-700">{title}</p>
            <p className="mt-1 text-sm text-gray-400">{sub}</p>
        </div>
    );
}

export default function FixedCostIndex({
    occurrences,
    status,
    filters,
    counts,
}: Props) {
    const [loadingId, setLoadingId] = useState<number | null>(null);
    const [activeTab, setActiveTab] = useState<OccurrenceTab>(
        filters.tab ?? 'pending',
    );
    const [month, setMonth] = useState(
        filters.month ?? new Date().getMonth() + 1,
    );
    const [year, setYear] = useState(filters.year ?? new Date().getFullYear());

    const reloadWithFilters = useCallback(
        (newTab: OccurrenceTab, newMonth: number, newYear: number) => {
            router.get(
                '/fixed-costs/occurrences',
                { tab: newTab, month: newMonth, year: newYear },
                { preserveState: true, preserveScroll: true },
            );
        },
        [],
    );

    const handleTabChange = (tab: OccurrenceTab) => {
        setActiveTab(tab);
        reloadWithFilters(tab, month, year);
    };

    const handleMonthYearChange = (newMonth: number, newYear: number) => {
        setMonth(newMonth);
        setYear(newYear);
        reloadWithFilters(activeTab, newMonth, newYear);
    };

    const handlePay = (id: number) => {
        setLoadingId(id);
        router.post(
            `/fixed-costs/occurrences/${id}/confirm-payment`,
            {},
            {
                preserveScroll: true,
                onFinish: () => setLoadingId(null),
            },
        );
    };

    const handleSkip = (id: number) => {
        setLoadingId(id);
        router.post(
            `/fixed-costs/occurrences/${id}/skip`,
            {},
            {
                preserveScroll: true,
                onFinish: () => setLoadingId(null),
            },
        );
    };

    const handleCancelPayment = (id: number) => {
        setLoadingId(id);
        router.post(
            `/fixed-costs/occurrences/${id}/cancel-payment`,
            {},
            {
                preserveScroll: true,
                onFinish: () => setLoadingId(null),
            },
        );
    };

    return (
        <AppLayout status={status}>
            <Head title="Biaya Tetap" />

            <div className="px-4 py-6 lg:px-8 lg:py-8">
                {/* Page header */}
                <div className="mb-6 flex items-center justify-between">
                    <h1 className="text-2xl font-bold text-primary lg:text-3xl">
                        Biaya Tetap
                    </h1>

                    <a
                        href="/fixed-costs/manage"
                        className="flex items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary/90"
                    >
                        <Pencil size={15} strokeWidth={2.5} />
                        <span className="hidden sm:inline">
                            Kelola Biaya Tetap
                        </span>
                        <span className="sm:hidden">Kelola</span>
                    </a>
                </div>

                {/* Section header + filters */}
                <div className="mb-5 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h2 className="text-lg font-bold text-gray-800">
                            Deadline Pembayaran
                        </h2>
                        <p className="text-sm text-gray-500">
                            Tagihan mendatang yang perlu segera diselesaikan
                        </p>
                    </div>

                    <MonthYearPicker
                        month={month}
                        year={year}
                        onChange={handleMonthYearChange}
                    />
                </div>

                {/* Tabs */}
                <div className="mb-6">
                    <FilterTabs
                        active={activeTab}
                        onChange={handleTabChange}
                        counts={counts}
                    />
                </div>

                {/* Cards grid */}
                {occurrences.length === 0 ? (
                    <EmptyState tab={activeTab} />
                ) : (
                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                        {occurrences.map((occurrence) => (
                            <OccurrenceCard
                                key={occurrence.id}
                                occurrence={occurrence}
                                activeTab={activeTab}
                                onPay={handlePay}
                                onSkip={handleSkip}
                                onCancelPayment={handleCancelPayment}
                                isLoading={loadingId === occurrence.id}
                            />
                        ))}
                    </div>
                )}

                {/* Global loading overlay for active card */}
                {loadingId !== null && (
                    <div className="pointer-events-none fixed right-6 bottom-6 z-50 flex items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-white shadow-lg">
                        <Loader2 size={16} className="animate-spin" />
                        Memproses...
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
