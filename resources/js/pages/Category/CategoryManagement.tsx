import { Head } from '@inertiajs/react';
import {
    Plus,
    Pencil,
    ShieldCheck,
    Tag,
    Search,
    TrendingDown,
    TrendingUp,
    Layers,
    X,
} from 'lucide-react';
import { useState } from 'react';
import CategoryFormDrawer from '@/components/CategoryFormDrawer';
import type { Category } from '@/components/CategoryFormDrawer';
import AppLayout from '@/layouts/AppLayout';
import { getIconColorTheme } from '@/utils/categoryColors';
import { CategoryPhosphorIcon } from '@/utils/phosphorIconMap';

interface Props {
    systemCategories: Category[];
    customCategories: Category[];
    status?: 'stabil' | 'defisit' | 'kritis' | 'surplus';
}

type TabType = 'all' | 'expense' | 'income';

export default function CategoryManagement({
    systemCategories,
    customCategories,
    status,
}: Props) {
    const [activeTab, setActiveTab] = useState<TabType>('all');
    const [searchQuery, setSearchQuery] = useState('');
    const [drawerOpen, setDrawerOpen] = useState(false);
    const [editingCategory, setEditingCategory] = useState<Category | null>(null);

    const openCreate = () => {
        setEditingCategory(null);
        setDrawerOpen(true);
    };

    const openEdit = (category: Category) => {
        setEditingCategory(category);
        setDrawerOpen(true);
    };

    const closeDrawer = () => {
        setDrawerOpen(false);
        setEditingCategory(null);
    };

    // Filter categories by type tab and search query
    const filterFn = (cat: Category) => {
        const matchesTab = activeTab === 'all' || cat.type === activeTab;
        const matchesSearch = cat.name.toLowerCase().includes(searchQuery.trim().toLowerCase());
        return matchesTab && matchesSearch;
    };

    const filteredSystem = systemCategories.filter(filterFn);
    const filteredCustom = customCategories.filter(filterFn);

    // Calculate dynamic stats
    const totalCustomCount = customCategories.length;
    const totalSystemCount = systemCategories.length;
    const expenseCount = [...systemCategories, ...customCategories].filter(c => c.type === 'expense').length;
    const incomeCount = [...systemCategories, ...customCategories].filter(c => c.type === 'income').length;

    return (
        <AppLayout status={status}>
            <Head title="Kelola Kategori - MOCO" />

            <div className="px-4 py-6 lg:px-8 lg:py-8 max-w-7xl mx-auto space-y-6">
                {/* Page header */}
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between border-b border-gray-100 pb-6">
                    <div className="flex items-center gap-3">
                        <div>
                            <h1 className="text-xl font-bold text-gray-800 lg:text-2xl">
                                Kelola Kategori
                            </h1>
                            <p className="text-sm text-gray-550">
                                Kustomisasi kategori transaksi sesuai gaya finansial personal Anda.
                            </p>
                        </div>
                    </div>

                    <button
                        onClick={openCreate}
                        className="flex items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary/90 cursor-pointer"
                    >
                        <Plus size={15} strokeWidth={2.5} />
                        <span>Kategori Baru</span>
                    </button>
                </div>

                {/* Clean Stats Panel */}
                <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    {/* Stat 1: Kustom */}
                    <div className="bg-white border border-gray-100 p-5 rounded-2xl shadow-sm transition hover:shadow-md duration-300 flex items-center gap-4">
                        <div className="h-11 w-11 rounded-xl bg-amber-50 border border-amber-100 flex items-center justify-center text-amber-600 shrink-0">
                            <Tag size={18} className="stroke-[2.5]" />
                        </div>
                        <div>
                            <p className="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Kategori Kustom</p>
                            <p className="text-lg font-bold text-gray-800 mt-0.5">{totalCustomCount}</p>
                        </div>
                    </div>

                    {/* Stat 2: Sistem */}
                    <div className="bg-white border border-gray-100 p-5 rounded-2xl shadow-sm transition hover:shadow-md duration-300 flex items-center gap-4">
                        <div className="h-11 w-11 rounded-xl bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-600 shrink-0">
                            <ShieldCheck size={18} className="stroke-[2.5]" />
                        </div>
                        <div>
                            <p className="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Bawaan Sistem</p>
                            <p className="text-lg font-bold text-gray-800 mt-0.5">{totalSystemCount}</p>
                        </div>
                    </div>

                    {/* Stat 3: Pengeluaran */}
                    <div className="bg-white border border-gray-100 p-5 rounded-2xl shadow-sm transition hover:shadow-md duration-300 flex items-center gap-4">
                        <div className="h-11 w-11 rounded-xl bg-rose-50 border border-rose-100 flex items-center justify-center text-rose-500 shrink-0">
                            <TrendingDown size={18} className="stroke-[2.5]" />
                        </div>
                        <div>
                            <p className="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Tipe Pengeluaran</p>
                            <p className="text-lg font-bold text-gray-800 mt-0.5">{expenseCount}</p>
                        </div>
                    </div>

                    {/* Stat 4: Pemasukan */}
                    <div className="bg-white border border-gray-100 p-5 rounded-2xl shadow-sm transition hover:shadow-md duration-300 flex items-center gap-4">
                        <div className="h-11 w-11 rounded-xl bg-emerald-50 border border-emerald-100 flex items-center justify-center text-emerald-500 shrink-0">
                            <TrendingUp size={18} className="stroke-[2.5]" />
                        </div>
                        <div>
                            <p className="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Tipe Pemasukan</p>
                            <p className="text-lg font-bold text-gray-800 mt-0.5">{incomeCount}</p>
                        </div>
                    </div>
                </div>

                {/* Filters and Search Bar */}
                <div className="flex flex-col sm:flex-row gap-4 justify-between items-center bg-white p-3 rounded-2xl border border-gray-100 shadow-sm">
                    {/* Filter Tabs */}
                    <div className="flex bg-gray-50 p-1.5 rounded-xl w-full sm:w-auto">
                        <button
                            onClick={() => setActiveTab('all')}
                            className={`flex-1 sm:flex-initial px-4 py-2 text-xs font-extrabold rounded-lg transition-all duration-200 cursor-pointer ${
                                activeTab === 'all'
                                    ? 'bg-primary text-white shadow-sm'
                                    : 'text-gray-500 hover:text-gray-850'
                            }`}
                        >
                            Semua Kategori
                        </button>
                        <button
                            onClick={() => setActiveTab('expense')}
                            className={`flex-1 sm:flex-initial px-4 py-2 text-xs font-extrabold rounded-lg transition-all duration-200 cursor-pointer ${
                                activeTab === 'expense'
                                    ? 'bg-primary text-white shadow-sm'
                                    : 'text-gray-500 hover:text-gray-850'
                            }`}
                        >
                            Pengeluaran
                        </button>
                        <button
                            onClick={() => setActiveTab('income')}
                            className={`flex-1 sm:flex-initial px-4 py-2 text-xs font-extrabold rounded-lg transition-all duration-200 cursor-pointer ${
                                activeTab === 'income'
                                    ? 'bg-primary text-white shadow-sm'
                                    : 'text-gray-500 hover:text-gray-850'
                            }`}
                        >
                            Pemasukan
                        </button>
                    </div>

                    {/* Live Search */}
                    <div className="relative w-full sm:w-72">
                        <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                            <Search size={16} />
                        </div>
                        <input
                            type="text"
                            placeholder="Cari kategori..."
                            value={searchQuery}
                            onChange={(e) => setSearchQuery(e.target.value)}
                            className="w-full pl-9 pr-8 py-2.5 bg-gray-50/50 hover:bg-gray-50 border border-gray-100 rounded-xl text-xs font-semibold text-gray-800 outline-none transition focus:border-primary focus:bg-white focus:ring-4 focus:ring-primary-light"
                        />
                        {searchQuery && (
                            <button
                                onClick={() => setSearchQuery('')}
                                className="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600"
                            >
                                <X size={14} />
                            </button>
                        )}
                    </div>
                </div>

                {/* Custom Categories Section */}
                <div className="space-y-4">
                    <div className="flex items-center justify-between border-b border-gray-50 pb-2">
                        <h2 className="text-base font-black text-gray-800 flex items-center gap-2">
                            <Layers size={18} className="text-primary" />
                            Kategori Kustom Anda
                        </h2>
                        <span className="rounded-full bg-primary-light px-3 py-1 text-xs font-black text-primary">
                            {filteredCustom.length} Kategori
                        </span>
                    </div>

                    {filteredCustom.length > 0 ? (
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                            {filteredCustom.map((cat) => {
                                const colors = getIconColorTheme(cat.icon);
                                const totalUsage = (cat.transactionsCount ?? 0) + (cat.fixedCostsCount ?? 0);
                                return (
                                    <div
                                        key={cat.id}
                                        className="group relative flex items-center gap-4 rounded-2xl border bg-white p-4 shadow-sm transition-all duration-250 hover:shadow-md hover:border-gray-200 border-gray-100"
                                    >
                                        {/* Soft vertical border */}
                                        <div className={`absolute left-0 top-3 bottom-3 w-1 rounded-r bg-linear-to-b ${colors.gradient} opacity-80`} />

                                        {/* Icon Container */}
                                        <div className={`flex h-11 w-11 shrink-0 items-center justify-center rounded-xl ${colors.bgLight} transition-all duration-200 shadow-sm`}>
                                            <CategoryPhosphorIcon
                                                iconName={cat.icon}
                                                size={20}
                                                className={colors.text}
                                                weight="fill"
                                            />
                                        </div>

                                        {/* Content info */}
                                        <div className="flex-1 min-w-0 pr-8">
                                            <p className="truncate text-sm font-semibold text-gray-850 transition-colors duration-200">
                                                {cat.name}
                                            </p>
                                            <span
                                                className={`inline-flex items-center mt-1 rounded-full px-2.5 py-0.5 text-[9px] font-bold uppercase tracking-wider ${
                                                    cat.type === 'expense'
                                                        ? 'bg-rose-50 text-rose-600 border border-rose-100/50'
                                                        : 'bg-emerald-50 text-emerald-600 border border-emerald-100/50'
                                                }`}
                                            >
                                                {cat.type === 'expense' ? 'Pengeluaran' : 'Pemasukan'}
                                            </span>

                                            {/* Subtle usage metrics */}
                                            <div className="flex flex-wrap items-center gap-1.5 mt-2">
                                                {totalUsage > 0 ? (
                                                    <span className="inline-flex items-center gap-0.5 rounded bg-indigo-50 px-1.5 py-0.5 text-[9px] font-bold text-indigo-600 border border-indigo-100/30">
                                                        Aktif • {totalUsage}x
                                                    </span>
                                                ) : (
                                                    <span className="inline-flex items-center gap-0.5 rounded bg-emerald-50 px-1.5 py-0.5 text-[9px] font-bold text-emerald-650 border border-emerald-100/30">
                                                        Aman Hapus
                                                    </span>
                                                )}
                                                <span className="text-[9px] font-medium text-gray-400">
                                                    {cat.transactionsCount ?? 0} Tx • {cat.fixedCostsCount ?? 0} Rutin
                                                </span>
                                            </div>
                                        </div>

                                        {/* Edit Action Button */}
                                        <button
                                            onClick={() => openEdit(cat)}
                                            className="absolute top-1/2 -translate-y-1/2 right-4 flex h-8 w-8 items-center justify-center rounded-xl bg-gray-50 border border-gray-100 text-gray-450 opacity-80 sm:opacity-0 group-hover:opacity-100 transition-all duration-200 hover:bg-primary hover:text-white hover:border-primary hover:scale-105 active:scale-95 cursor-pointer"
                                            title="Edit Kategori"
                                        >
                                            <Pencil size={13} strokeWidth={2.5} />
                                        </button>
                                    </div>
                                );
                            })}
                        </div>
                    ) : (
                        <div className="rounded-2xl border-2 border-dashed border-gray-200 bg-white py-12 px-4 text-center shadow-sm">
                            <div className="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-gray-50 text-gray-400 border border-gray-100">
                                <Tag size={24} className="stroke-[1.5]" />
                            </div>
                            <h3 className="text-base font-black text-gray-800">
                                Belum ada kategori kustom
                            </h3>
                            <p className="mt-2 text-xs text-gray-400 max-w-sm mx-auto font-medium">
                                Buat kategori kustom baru untuk melacak pengeluaran dan pemasukan dengan lebih akurat.
                            </p>
                            <button
                                onClick={openCreate}
                                className="mt-5 inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-xs font-extrabold text-white shadow-md shadow-primary/20 transition-all hover:bg-primary/95 hover:shadow-lg active:scale-98 cursor-pointer"
                            >
                                <Plus size={14} strokeWidth={2.5} />
                                Buat Kategori Pertama
                            </button>
                        </div>
                    )}
                </div>

                {/* System Categories Section */}
                <div className="space-y-4 pt-4">
                    <div className="flex items-center justify-between border-b border-gray-50 pb-2">
                        <h2 className="text-base font-black text-gray-650 flex items-center gap-2">
                            <ShieldCheck size={18} className="text-gray-400" />
                            Kategori Bawaan Sistem
                        </h2>
                        <span className="rounded-full bg-gray-100 px-3 py-1 text-xs font-black text-gray-500 border border-gray-200/50">
                            {filteredSystem.length} Kategori
                        </span>
                    </div>

                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                        {filteredSystem.map((cat) => {
                            return (
                                <div
                                    key={cat.id}
                                    className="relative flex items-center gap-4 rounded-2xl border border-gray-100 bg-gray-50/50 p-4 transition-colors duration-200 hover:bg-gray-50"
                                >
                                    {/* Icon Container */}
                                    <div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white border border-gray-100/70 text-gray-400 shadow-sm">
                                        <CategoryPhosphorIcon
                                            iconName={cat.icon}
                                            size={20}
                                            className="text-gray-400"
                                        />
                                    </div>

                                    {/* Content info */}
                                    <div className="flex-1 min-w-0">
                                        <p className="truncate text-sm font-semibold text-gray-750">
                                            {cat.name}
                                        </p>
                                        <div className="flex items-center gap-2 mt-1">
                                            <span
                                                className={`inline-block rounded-full px-2 py-0.5 text-[9px] font-bold uppercase tracking-wider ${
                                                    cat.type === 'expense'
                                                        ? 'bg-rose-50/50 text-rose-450 border border-rose-100/30'
                                                        : 'bg-emerald-50/50 text-emerald-450 border border-emerald-100/30'
                                                }`}
                                            >
                                                {cat.type === 'expense' ? 'Pengeluaran' : 'Pemasukan'}
                                            </span>
                                            <span className="flex items-center gap-0.5 text-[9px] font-bold text-gray-400 tracking-wider uppercase">
                                                <ShieldCheck size={10} />
                                                Sistem
                                            </span>
                                        </div>

                                        {/* Dynamic usage metrics for system categories */}
                                        <div className="mt-2 text-[9px] font-semibold text-gray-400">
                                            {cat.transactionsCount ?? 0} Transaksi • {cat.fixedCostsCount ?? 0} Rutin
                                        </div>
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                </div>
            </div>

            {/* Custom Category Form Drawer */}
            <CategoryFormDrawer
                open={drawerOpen}
                category={editingCategory}
                onClose={closeDrawer}
            />
        </AppLayout>
    );
}
