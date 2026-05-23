import { Head, router } from '@inertiajs/react';
import { Pencil, Loader2, Search, X } from 'lucide-react';
import { useState, useCallback } from 'react';
import FilterTabs from '@/components/FilterTabs';
import type { OccurrenceTab } from '@/components/FilterTabs';
import OccurrenceCard from '@/components/OccurrenceCard';
import type { Occurrence } from '@/components/OccurrenceCard';
import AppLayout from '@/layouts/AppLayout';

// Tab → backend status value mapping
const TAB_STATUS: Record<OccurrenceTab, string> = {
    pending: 'pending',
    paid: 'paid',
    skipped: 'skipped',
};

interface Props {
    occurrences: Occurrence[];
    status?: 'stabil' | 'defisit' | 'kritis' | 'surplus';
    filters: {
        tab: OccurrenceTab;
        keyword: string | null;
        startDate: string | null;
        endDate: string | null;
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
                    <rect x="5" y="3" width="22" height="28" rx="3" stroke="currentColor" strokeWidth="2" />
                    <path d="M5 28l3-3 3 3 3-3 3 3 3-3 3 3" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
                    <line x1="11" y1="12" x2="21" y2="12" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
                    <line x1="11" y1="17" x2="21" y2="17" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
                    <line x1="11" y1="22" x2="16" y2="22" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
                </svg>
            </div>
            <p className="text-base font-semibold text-gray-700">{title}</p>
            <p className="mt-1 text-sm text-gray-400">{sub}</p>
        </div>
    );
}

export default function FixedCostIndex({ occurrences, status, filters, counts }: Props) {
    const [loadingId, setLoadingId] = useState<number | null>(null);
    const [activeTab, setActiveTab] = useState<OccurrenceTab>(filters.tab ?? 'pending');

    // Local state for filter inputs (controlled, only submitted on apply)
    const [keyword, setKeyword] = useState(filters.keyword ?? '');
    const [startDate, setStartDate] = useState(filters.startDate ?? '');
    const [endDate, setEndDate] = useState(filters.endDate ?? '');

    const reload = useCallback(
        (params: {
            tab?: OccurrenceTab;
            keyword?: string;
            startDate?: string;
            endDate?: string;
        }) => {
            const tab = params.tab ?? activeTab;

            // Build query — omit empty values so backend sees null via $request->has()
            const query: Record<string, string> = {
                tab,
                status: TAB_STATUS[tab],
            };

            if (params.keyword) query.keyword = params.keyword;
            if (params.startDate) query.startDate = params.startDate;
            if (params.endDate) query.endDate = params.endDate;

            router.get('/fixed-costs/occurrences', query, {
                preserveState: true,
                preserveScroll: true,
            });
        },
        [activeTab],
    );

    const handleTabChange = (tab: OccurrenceTab) => {
        setActiveTab(tab);
        reload({ tab, keyword, startDate, endDate });
    };

    const handleApplyFilters = () => {
        reload({ keyword, startDate, endDate });
    };

    const handleClearFilters = () => {
        setKeyword('');
        setStartDate('');
        setEndDate('');
        reload({ keyword: '', startDate: '', endDate: '' });
    };

    const hasActiveFilters = keyword || startDate || endDate;

    const handlePay = (id: number) => {
        setLoadingId(id);
        router.post(`/fixed-costs/occurrences/${id}/confirm-payment`, {}, {
            preserveScroll: true,
            onFinish: () => setLoadingId(null),
        });
    };

    const handleSkip = (id: number) => {
        setLoadingId(id);
        router.post(`/fixed-costs/occurrences/${id}/skip`, {}, {
            preserveScroll: true,
            onFinish: () => setLoadingId(null),
        });
    };

    const handleCancelPayment = (id: number) => {
        setLoadingId(id);
        router.post(`/fixed-costs/occurrences/${id}/cancel-payment`, {}, {
            preserveScroll: true,
            onFinish: () => setLoadingId(null),
        });
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
                        <span className="hidden sm:inline">Kelola Biaya Tetap</span>
                        <span className="sm:hidden">Kelola</span>
                    </a>
                </div>

                {/* Section title */}
                <div className="mb-4">
                    <h2 className="text-lg font-bold text-gray-800">Deadline Pembayaran</h2>
                    <p className="text-sm text-gray-500">
                        Tagihan mendatang yang perlu segera diselesaikan
                    </p>
                </div>

                {/* Filter bar */}
                <div className="mb-5 flex flex-col gap-3 rounded-2xl border border-gray-100 bg-white p-4 shadow-sm sm:flex-row sm:items-end">
                    {/* Keyword */}
                    <div className="flex-1">
                        <label className="mb-1 block text-xs font-medium text-gray-500">
                            Cari nama
                        </label>
                        <div className="relative">
                            <Search size={15} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
                            <input
                                type="text"
                                placeholder="Cicilan motor, listrik..."
                                value={keyword}
                                onChange={(e) => setKeyword(e.target.value)}
                                onKeyDown={(e) => e.key === 'Enter' && handleApplyFilters()}
                                className="w-full rounded-xl border border-gray-200 py-2.5 pl-9 pr-4 text-sm text-gray-700 placeholder-gray-300 outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                            />
                        </div>
                    </div>

                    {/* Start date */}
                    <div>
                        <label className="mb-1 block text-xs font-medium text-gray-500">
                            Dari tanggal
                        </label>
                        <input
                            type="date"
                            value={startDate}
                            onChange={(e) => setStartDate(e.target.value)}
                            className="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm text-gray-700 outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 sm:w-auto"
                        />
                    </div>

                    {/* End date */}
                    <div>
                        <label className="mb-1 block text-xs font-medium text-gray-500">
                            Sampai tanggal
                        </label>
                        <input
                            type="date"
                            value={endDate}
                            min={startDate || undefined}
                            onChange={(e) => setEndDate(e.target.value)}
                            className="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm text-gray-700 outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 sm:w-auto"
                        />
                    </div>

                    {/* Action buttons */}
                    <div className="flex shrink-0 gap-2">
                        {hasActiveFilters && (
                            <button
                                onClick={handleClearFilters}
                                className="flex items-center gap-1.5 rounded-xl border border-gray-200 px-3 py-2.5 text-sm font-medium text-gray-500 transition hover:bg-gray-50"
                            >
                                <X size={14} />
                                Reset
                            </button>
                        )}
                        <button
                            onClick={handleApplyFilters}
                            className="rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-primary/90"
                        >
                            Terapkan
                        </button>
                    </div>
                </div>

                {/* Tabs */}
                <div className="mb-6">
                    <FilterTabs active={activeTab} onChange={handleTabChange} counts={counts} />
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
            </div>

            {/* Processing toast */}
            {loadingId !== null && (
                <div className="pointer-events-none fixed bottom-6 right-6 z-50 flex items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-white shadow-lg">
                    <Loader2 size={16} className="animate-spin" />
                    Memproses...
                </div>
            )}
        </AppLayout>
    );
}
