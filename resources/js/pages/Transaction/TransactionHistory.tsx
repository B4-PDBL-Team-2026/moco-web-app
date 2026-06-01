import { Head, Link, router } from '@inertiajs/react';
import {
  Calendar,
  LayoutGrid,
  Search,
  ChevronDown,
  TrendingDown,
  TrendingUp,
} from 'lucide-react';
import { useState, useEffect, useCallback, useRef } from 'react';
import AppLayout from '@/layouts/AppLayout';
import DeleteConfirmDialog from '@/components/DeleteConfirmDialog';
import { api } from '@/lib/api';
import { CategoryPhosphorIcon } from '@/utils/phosphorIconMap';

interface TransactionCategory {
  id: number;
  name: string;
  icon: string | null;
}

interface Category {
  id: number;
  name: string;
  icon: string | null;
  type: string;
}

interface TransactionItem {
  feedType: 'single' | 'batch';
  id: number;
  name: string;
  amount: number;
  type: 'income' | 'expense';
  note: string | null;
  transactionAt: string;
  source: string;
  category: TransactionCategory | null;
}

interface PaginationMeta {
  currentPage: number;
  lastPage: number;
  perPage: number;
  total: number;
  hasMore: boolean;
}

interface TransactionResponse {
  success: boolean;
  message: string;
  data: TransactionItem[];
  meta: PaginationMeta;
}

type FilterType = 'all' | 'income' | 'expense';

const MONTH_ID = [
  'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
  'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];

function formatRp(value: string | number) {
  const num = typeof value === 'number' ? value : parseFloat(value);
  if (isNaN(num)) return 'Rp 0';
  return 'Rp ' + Math.round(num).toLocaleString('id-ID');
}

function formatDateKey(iso: string) {
  if (!iso) return '';
  const d = new Date(iso);
  if (isNaN(d.getTime())) return '';
  return `${d.getDate()} ${MONTH_ID[d.getMonth()]} ${d.getFullYear()}`;
}

export default function TransactionHistory({ categories }: { categories: Category[] }) {
  const now = new Date();
  const [transactions, setTransactions] = useState<TransactionItem[]>([]);
  const [meta, setMeta] = useState<PaginationMeta | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  // Active Filter States
  const [filterType, setFilterType] = useState<FilterType>('all');
  const [month, setMonth] = useState(now.getMonth() + 1);
  const [year, setYear] = useState(now.getFullYear());
  const [activeCategoryId, setActiveCategoryId] = useState<number | null>(null);
  const [search, setSearch] = useState('');
  const [page, setPage] = useState(1);

  // Modal Visibility States
  const [showDateModal, setShowDateModal] = useState(false);
  const [showCategoryModal, setShowCategoryModal] = useState(false);

  // Temporary Selector States (while modal is open)
  const [tempMonth, setTempMonth] = useState(now.getMonth() + 1);
  const [tempYear, setTempYear] = useState(now.getFullYear());
  const [tempCategoryTab, setTempCategoryTab] = useState<'expense' | 'income'>('expense');
  const [tempCategoryId, setTempCategoryId] = useState<number | null>(null);
  const [tempFilterType, setTempFilterType] = useState<FilterType>('all');

  // Refs for closing on click outside
  const dateRef = useRef<HTMLDivElement>(null);
  const categoryRef = useRef<HTMLDivElement>(null);

  const [deleteTarget, setDeleteTarget] = useState<TransactionItem | null>(null);
  const [deleting, setDeleting] = useState(false);

  const fetchTransactions = useCallback(async () => {
    setLoading(true);
    setError('');
    try {
      const params = new URLSearchParams();
      params.set('page', String(page));
      params.set('perPage', '20');
      params.set('month', String(month));
      params.set('year', String(year));
      if (filterType !== 'all') {
        params.set('transactionType', filterType);
      }
      if (activeCategoryId !== null) {
        params.set('categoryId', String(activeCategoryId));
      }
      if (search.trim()) {
        params.set('search', search.trim());
      }

      const res = await api.get<TransactionResponse>(`/transaction?${params}`);
      setTransactions(res.data);
      setMeta(res.meta);
    } catch (e: unknown) {
      setError(e instanceof Error ? e.message : 'Gagal memuat data');
    } finally {
      setLoading(false);
    }
  }, [page, month, year, filterType, activeCategoryId, search]);

  useEffect(() => {
    setPage(1);
  }, [month, year, filterType, activeCategoryId]);

  useEffect(() => {
    fetchTransactions();
  }, [fetchTransactions]);

  useEffect(() => {
    function handleClickOutside(event: MouseEvent) {
      if (dateRef.current && !dateRef.current.contains(event.target as Node)) {
        setShowDateModal(false);
      }
      if (categoryRef.current && !categoryRef.current.contains(event.target as Node)) {
        setShowCategoryModal(false);
      }
    }
    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, []);

  const openDateModal = () => {
    setTempMonth(month);
    setTempYear(year);
    setShowDateModal(true);
    setShowCategoryModal(false);
  };

  const openCategoryModal = () => {
    setTempCategoryId(activeCategoryId);
    if (activeCategoryId !== null) {
      const cat = categories.find((c) => c.id === activeCategoryId);
      if (cat) {
        setTempCategoryTab(cat.type as 'expense' | 'income');
        setTempFilterType(cat.type as FilterType);
      }
    } else {
      setTempCategoryTab(filterType === 'income' ? 'income' : 'expense');
      setTempFilterType(filterType);
    }
    setShowCategoryModal(true);
    setShowDateModal(false);
  };

  const applyDateFilter = () => {
    setMonth(tempMonth);
    setYear(tempYear);
    setShowDateModal(false);
  };

  const applyCategoryFilter = () => {
    setActiveCategoryId(tempCategoryId);
    setFilterType(tempFilterType);
    setShowCategoryModal(false);
  };

  const getCategoryButtonText = () => {
    if (activeCategoryId !== null) {
      const cat = categories.find((c) => c.id === activeCategoryId);
      return cat ? cat.name : 'Kategori';
    }
    if (filterType === 'expense') return 'Semua Pengeluaran';
    if (filterType === 'income') return 'Semua Pemasukan';
    return 'Semua';
  };

  const handleDelete = async () => {
    if (!deleteTarget) return;
    setDeleting(true);
    try {
      await api.delete(`/transaction/${deleteTarget.id}`);
      setDeleteTarget(null);
      fetchTransactions();
    } catch (e: unknown) {
      setError(e instanceof Error ? e.message : 'Gagal menghapus');
    } finally {
      setDeleting(false);
    }
  };

  const totalIncome = transactions
    .filter((t) => t.type === 'income')
    .reduce((sum, t) => sum + t.amount, 0);

  const totalExpense = transactions
    .filter((t) => t.type === 'expense')
    .reduce((sum, t) => sum + t.amount, 0);

  // Sort transactions in the frontend (newest date first, then highest ID first if dates are equal)
  const sortedTransactions = [...transactions].sort((a, b) => {
    const timeA = new Date(a.transactionAt).getTime();
    const timeB = new Date(b.transactionAt).getTime();
    const isValA = !isNaN(timeA);
    const isValB = !isNaN(timeB);

    if (isValA && isValB) {
      if (timeA !== timeB) {
        return timeB - timeA;
      }
    } else {
      if (a.transactionAt !== b.transactionAt) {
        return b.transactionAt.localeCompare(a.transactionAt);
      }
    }
    return b.id - a.id;
  });

  // Group transactions by date key (guarantees descending chronological order is preserved)
  const grouped: { dateKey: string; items: TransactionItem[] }[] = [];
  sortedTransactions.forEach((tx) => {
    const key = formatDateKey(tx.transactionAt);
    let group = grouped.find((g) => g.dateKey === key);
    if (!group) {
      group = { dateKey: key, items: [] };
      grouped.push(group);
    }
    group.items.push(tx);
  });

  return (
    <AppLayout>
      <Head title="Riwayat Transaksi - MOCO" />

      <div className="px-4 py-6 lg:px-8 lg:py-8 mx-auto max-w-4xl space-y-6">
        {/* Header */}
        <div className="flex items-center justify-between pb-2">
          <h1 className="text-2xl font-black text-[#2F5FBF]">
            Riwayat Transaksi
          </h1>
        </div>

        {/* Filters Card */}
        <div className="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm flex flex-col md:flex-row items-center justify-between gap-4">
          {/* Search Input */}
          <div className="relative w-full md:flex-1">
            <Search size={16} className="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400" />
            <input
              type="text"
              placeholder="Cari nama transaksi..."
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              className="w-full rounded-2xl border-none bg-gray-50/50 py-3 pl-11 pr-4 text-xs font-bold text-gray-800 outline-none transition focus:bg-gray-100/50 focus:ring-2 focus:ring-primary/20"
            />
          </div>

          {/* Buttons trigger popovers */}
          <div className="flex items-center gap-3 w-full md:w-auto">
            {/* Month Year Selector */}
            <div className="relative flex-1 md:flex-initial" ref={dateRef}>
              <button
                type="button"
                onClick={openDateModal}
                className="w-full md:w-auto flex items-center justify-between gap-2 rounded-xl bg-gray-50/50 hover:bg-gray-100/50 py-2.5 px-4 text-xs font-bold text-gray-750 transition duration-150 cursor-pointer focus:outline-none"
              >
                <Calendar size={14} className="text-gray-400 shrink-0" />
                <span className="whitespace-nowrap">{MONTH_ID[month - 1]} {year}</span>
                <ChevronDown size={14} className="text-gray-400 shrink-0" />
              </button>

              {showDateModal && (
                <div className="absolute right-0 mt-2 z-50 w-72 bg-white rounded-3xl border border-gray-100 p-5 shadow-2xl animate-in fade-in slide-in-from-top-2 duration-150">
                  <div className="flex items-center justify-between mb-4">
                    <span className="text-xs font-black text-gray-400 uppercase tracking-wider">
                      Pilih Bulan & Tahun
                    </span>
                  </div>

                  {/* Year Dropdown */}
                  <div className="mb-4">
                    <select
                      value={tempYear}
                      onChange={(e) => setTempYear(Number(e.target.value))}
                      className="w-full rounded-xl bg-gray-50 border border-gray-150 px-3 py-2 text-sm font-semibold text-gray-750 outline-none transition focus:bg-white focus:ring-2 focus:ring-primary/20"
                    >
                      {Array.from({ length: 5 }, (_, i) => now.getFullYear() - 3 + i).map((y) => (
                        <option key={y} value={y}>
                          {y}
                        </option>
                      ))}
                    </select>
                  </div>

                  {/* Months List */}
                  <div className="max-h-48 overflow-y-auto pr-1 space-y-1">
                    {MONTH_ID.map((name, idx) => {
                      const isSelected = tempMonth === idx + 1;
                      return (
                        <button
                          key={idx}
                          type="button"
                          onClick={() => setTempMonth(idx + 1)}
                          className={`w-full text-left px-4 py-2 text-xs font-bold rounded-xl transition duration-150 cursor-pointer ${
                            isSelected
                              ? 'bg-primary-light text-primary'
                              : 'text-gray-750 hover:bg-gray-50'
                          }`}
                        >
                          {name}
                        </button>
                      );
                    })}
                  </div>

                  {/* Footer Actions */}
                  <div className="flex items-center gap-3 justify-end mt-4 pt-3 border-t border-gray-100">
                    <button
                      type="button"
                      onClick={() => setShowDateModal(false)}
                      className="rounded-full border border-primary px-5 py-2 text-xs font-black text-primary transition hover:bg-gray-50 cursor-pointer"
                    >
                      Batal
                    </button>
                    <button
                      type="button"
                      onClick={applyDateFilter}
                      className="rounded-full bg-[#FF9C13] hover:bg-[#FF9C13]/90 px-5 py-2 text-xs font-black text-white transition shadow-sm cursor-pointer"
                    >
                      Pilih
                    </button>
                  </div>
                </div>
              )}
            </div>

            {/* Category / Type Selector */}
            <div className="relative flex-1 md:flex-initial" ref={categoryRef}>
              <button
                type="button"
                onClick={openCategoryModal}
                className="w-full md:w-auto flex items-center justify-between gap-2 rounded-xl bg-gray-50/50 hover:bg-gray-100/50 py-2.5 px-4 text-xs font-bold text-gray-750 transition duration-150 cursor-pointer focus:outline-none"
              >
                <LayoutGrid size={14} className="text-gray-400 shrink-0" />
                <span className="whitespace-nowrap">{getCategoryButtonText()}</span>
                <ChevronDown size={14} className="text-gray-400 shrink-0" />
              </button>

              {showCategoryModal && (
                <div className="absolute right-0 mt-2 z-50 w-80 bg-white rounded-3xl border border-gray-100 p-5 shadow-2xl animate-in fade-in slide-in-from-top-2 duration-150">
                  <div className="flex items-center justify-between mb-4">
                    <span className="text-xs font-black text-gray-400 uppercase tracking-wider">
                      Pilih Kategori
                    </span>
                    {(tempCategoryId !== null || tempFilterType !== 'all') && (
                      <button
                        type="button"
                        onClick={() => {
                          setTempCategoryId(null);
                          setTempFilterType('all');
                        }}
                        className="text-[10px] font-black text-red-500 hover:text-red-750 uppercase tracking-wider cursor-pointer"
                      >
                        Reset
                      </button>
                    )}
                  </div>

                  {/* Tab Buttons */}
                  <div className="flex bg-gray-50 p-1 rounded-xl mb-4">
                    <button
                      type="button"
                      onClick={() => setTempCategoryTab('expense')}
                      className={`flex-1 text-center py-2 text-xs font-bold rounded-lg transition duration-200 cursor-pointer ${
                        tempCategoryTab === 'expense'
                          ? 'bg-primary text-white shadow-sm'
                          : 'text-gray-550 hover:text-gray-850'
                      }`}
                    >
                      Pengeluaran
                    </button>
                    <button
                      type="button"
                      onClick={() => setTempCategoryTab('income')}
                      className={`flex-1 text-center py-2 text-xs font-bold rounded-lg transition duration-200 cursor-pointer ${
                        tempCategoryTab === 'income'
                          ? 'bg-primary text-white shadow-sm'
                          : 'text-gray-550 hover:text-gray-850'
                      }`}
                    >
                      Pemasukan
                    </button>
                  </div>

                  {/* Categories Grid */}
                  <div className="max-h-60 overflow-y-auto pr-1">
                    <div className="grid grid-cols-3 gap-2 pb-1">
                      {/* Virtual Card: Semua Tab */}
                      <button
                        type="button"
                        onClick={() => {
                          setTempCategoryId(null);
                          setTempFilterType(tempCategoryTab);
                        }}
                        className={`flex flex-col items-center justify-center p-3 rounded-xl border transition duration-150 cursor-pointer text-center ${
                          tempCategoryId === null && tempFilterType === tempCategoryTab
                            ? 'border-2 border-primary bg-primary-light/20'
                            : 'border-gray-100 hover:border-gray-200 bg-white'
                        }`}
                      >
                        <div className={`flex size-10 items-center justify-center rounded-xl mb-1.5 transition-colors ${
                          tempCategoryId === null && tempFilterType === tempCategoryTab
                            ? 'bg-primary/10 text-primary'
                            : 'bg-gray-50 text-gray-400'
                        }`}>
                          <LayoutGrid size={20} />
                        </div>
                        <span className={`text-[10px] font-black w-full truncate ${
                          tempCategoryId === null && tempFilterType === tempCategoryTab
                            ? 'text-primary'
                            : 'text-gray-600'
                        }`}>
                          Semua
                        </span>
                      </button>

                      {/* Real DB Categories */}
                      {categories
                        .filter((c) => c.type === tempCategoryTab)
                        .map((cat) => {
                          const isSelected = tempCategoryId === cat.id;
                          return (
                            <button
                              key={cat.id}
                              type="button"
                              onClick={() => {
                                setTempCategoryId(cat.id);
                                setTempFilterType(cat.type as FilterType);
                              }}
                              className={`flex flex-col items-center justify-center p-3 rounded-xl border transition duration-150 cursor-pointer text-center ${
                                isSelected
                                  ? 'border-2 border-primary bg-primary-light/20'
                                  : 'border-gray-100 hover:border-gray-200 bg-white'
                              }`}
                            >
                              <div className={`flex size-10 items-center justify-center rounded-xl mb-1.5 transition-colors ${
                                isSelected
                                  ? 'bg-primary/10 text-primary'
                                  : 'bg-gray-50 text-gray-500'
                              }`}>
                                <CategoryPhosphorIcon iconName={cat.icon} size={20} />
                              </div>
                              <span className={`text-[10px] font-bold w-full truncate ${
                                isSelected ? 'text-primary font-black' : 'text-gray-600'
                              }`}>
                                {cat.name}
                              </span>
                            </button>
                          );
                        })}
                    </div>
                  </div>

                  {/* Footer Actions */}
                  <div className="flex items-center gap-3 justify-end mt-4 pt-3 border-t border-gray-100">
                    <button
                      type="button"
                      onClick={() => setShowCategoryModal(false)}
                      className="rounded-full border border-primary px-5 py-2 text-xs font-black text-primary transition hover:bg-gray-50 cursor-pointer"
                    >
                      Batal
                    </button>
                    <button
                      type="button"
                      onClick={applyCategoryFilter}
                      className="rounded-full bg-[#FF9C13] hover:bg-[#FF9C13]/90 px-5 py-2 text-xs font-black text-white transition shadow-sm cursor-pointer"
                    >
                      Pilih
                    </button>
                  </div>
                </div>
              )}
            </div>
          </div>
        </div>

        {/* Stats */}
        <div className="grid grid-cols-2 gap-4">
          <div className="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
            <span className="text-xs font-semibold text-gray-400">Total Pengeluaran</span>
            <p className="mt-1.5 text-2xl font-black text-red-500">
              - {formatRp(totalExpense)}
            </p>
          </div>
          <div className="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
            <span className="text-xs font-semibold text-gray-400">Total Pemasukan</span>
            <p className="mt-1.5 text-2xl font-black text-[#2E8B57]">
              + {formatRp(totalIncome)}
            </p>
          </div>
        </div>

        {/* Error Message */}
        {error && (
          <div className="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm font-semibold text-red-600">
            {error}
          </div>
        )}

        {/* Loading Skeletons */}
        {loading && (
          <div className="space-y-3">
            {[1, 2, 3].map((i) => (
              <div key={i} className="h-24 animate-pulse rounded-2xl bg-gray-100" />
            ))}
          </div>
        )}

        {/* Empty State */}
        {!loading && !error && transactions.length === 0 && (
          <div className="rounded-2xl border border-dashed border-gray-200 py-12 text-center bg-white">
            <TrendingDown size={32} className="mx-auto text-gray-300" />
            <h3 className="mt-4 text-base font-bold text-gray-800">
              Belum ada transaksi
            </h3>
            <p className="mt-1 text-sm text-gray-400">
              {search || filterType !== 'all'
                ? 'Tidak ada transaksi yang cocok dengan filter.'
                : 'Belum ada catatan transaksi untuk periode ini.'}
            </p>
          </div>
        )}

        {/* Grouped Transaction List */}
        {!loading && transactions.length > 0 && (
          <div className="space-y-6">
            {grouped.map(({ dateKey, items }) => (
              <div key={dateKey} className="space-y-3">
                {/* Date Group Heading with Divider Line */}
                <div className="flex items-center gap-4 py-1">
                  <span className="text-xs font-black text-gray-400 whitespace-nowrap">
                    {dateKey}
                  </span>
                  <div className="h-px bg-gray-100 flex-1" />
                </div>

                {/* List Items */}
                {items.map((tx) => (
                  <div
                    key={`${tx.feedType}-${tx.id}`}
                    onClick={() => {
                      if (tx.feedType === 'single') {
                        router.visit(`/transaction/${tx.id}`);
                      }
                    }}
                    className="flex items-center justify-between rounded-2xl border border-gray-100 bg-white p-4 shadow-sm transition hover:shadow-md cursor-pointer"
                  >
                    <div className="flex items-center min-w-0">
                      {/* Icon */}
                      <div
                        className={`flex size-11 shrink-0 items-center justify-center rounded-xl transition-all duration-200 ${
                          tx.type === 'expense'
                            ? 'bg-red-50 text-red-500'
                            : 'bg-green-50 text-[#2E8B57]'
                        }`}
                      >
                        <CategoryPhosphorIcon iconName={tx.category?.icon} size={20} />
                      </div>

                      {/* Info */}
                      <div className="ml-4 min-w-0">
                        <p className="truncate text-sm font-bold text-gray-800">
                          {tx.name}
                        </p>
                        {tx.category && (
                          <span className={`inline-block mt-1 px-2.5 py-0.5 rounded-lg text-[9px] font-black uppercase tracking-wider ${
                            tx.type === 'expense'
                              ? 'bg-red-50 text-red-500'
                              : 'bg-green-50 text-[#2E8B57]'
                          }`}>
                            {tx.category.name.toUpperCase()}
                          </span>
                        )}
                      </div>
                    </div>

                    {/* Amount */}
                    <p
                      className={`text-sm font-bold whitespace-nowrap ${
                        tx.type === 'expense' ? 'text-red-500' : 'text-[#2E8B57]'
                      }`}
                    >
                      {tx.type === 'expense' ? '-' : '+'} {formatRp(tx.amount)}
                    </p>
                  </div>
                ))}
              </div>
            ))}
          </div>
        )}

        {/* Pagination */}
        {meta && meta.lastPage > 1 && (
          <div className="flex items-center justify-center gap-2 pt-4">
            <button
              onClick={() => setPage((p) => Math.max(1, p - 1))}
              disabled={page === 1}
              className="rounded-xl border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 transition hover:bg-gray-50 disabled:opacity-40 cursor-pointer"
            >
              Sebelumnya
            </button>
            <span className="text-sm font-medium text-gray-500">
              {meta.currentPage} / {meta.lastPage}
            </span>
            <button
              onClick={() => setPage((p) => p + 1)}
              disabled={!meta.hasMore}
              className="rounded-xl border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 transition hover:bg-gray-50 disabled:opacity-40 cursor-pointer"
            >
              Selanjutnya
            </button>
          </div>
        )}
      </div>

      {/* Delete Confirm */}
      <DeleteConfirmDialog
        open={deleteTarget !== null}
        onConfirm={handleDelete}
        onCancel={() => setDeleteTarget(null)}
        isLoading={deleting}
        title="Hapus Transaksi?"
        description={`Yakin ingin menghapus transaksi "${deleteTarget?.name}"? Tindakan ini tidak dapat dibatalkan.`}
      />
    </AppLayout>
  );
}
