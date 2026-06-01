import { ArrowLeft, Calendar, ChevronLeft, ChevronRight, X } from 'lucide-react';
import { useEffect, useState } from 'react';
import type { ReactNode } from 'react';
import { api, ApiError } from '@/lib/api';
import { CategoryPhosphorIcon } from '@/utils/phosphorIconMap';
import CategoryPickerModal from '@/components/CategoryPickerModal';
import type { Category } from '@/components/CategoryPickerModal';

interface TransactionFormData {
    id?: number;
    name: string;
    amount: number;
    type: 'income' | 'expense';
    note: string;
    transactionAt: string;
    categoryId: number | null;
}

interface TransactionFormDrawerProps {
    open: boolean;
    categories: Category[];
    transaction: TransactionFormData | null;
    onClose: () => void;
    onSaved: () => void;
}

/* ── Calendar helpers ─────────────────────────────────────── */
function getDaysInMonth(year: number, month: number) {
    return new Date(year, month + 1, 0).getDate();
}
function getFirstDayOfMonth(year: number, month: number) {
    return new Date(year, month, 1).getDay();
}

const MONTH_ID = [
    'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];
const DAY_SHORT = ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'];

function formatDateInput(iso: string) {
    if (!iso) return '';
    const d = new Date(iso);
    if (isNaN(d.getTime())) return '';
    return d.toISOString().split('T')[0];
}

function formatDateDisplay(dateStr: string) {
    if (!dateStr) return '';
    const [y, m, d] = dateStr.split('-').map(Number);
    return `${d} ${MONTH_ID[m - 1]} ${y}`;
}

export default function TransactionFormDrawer({
    open,
    categories,
    transaction,
    onClose,
    onSaved,
}: TransactionFormDrawerProps) {
    const isEdit = transaction !== null && transaction?.id !== undefined;

    // Form states
    const [name, setName] = useState('');
    const [amount, setAmount] = useState('');
    const [type, setType] = useState<'income' | 'expense'>('expense');
    const [note, setNote] = useState('');
    const [transactionAt, setTransactionAt] = useState('');
    const [categoryId, setCategoryId] = useState<number | null>(null);

    // UX states
    const [subView, setSubView] = useState<'form' | 'category' | 'date'>('form');
    const [tempCategoryId, setTempCategoryId] = useState<number | null>(null);
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [validationErrors, setValidationErrors] = useState<Record<string, string[]>>({});

    // Calendar state
    const [calYear, setCalYear] = useState(new Date().getFullYear());
    const [calMonth, setCalMonth] = useState(new Date().getMonth());
    const [calSelected, setCalSelected] = useState<string | null>(null);

    // Sync form with transaction prop on open
    useEffect(() => {
        if (!open) return;
        setSubView('form');
        setSaving(false);
        setError(null);
        setValidationErrors({});

        if (transaction) {
            setName(transaction.name);
            setAmount(transaction.amount.toString());
            setType(transaction.type);
            setNote(transaction.note || '');
            const fmtDate = formatDateInput(transaction.transactionAt);
            setTransactionAt(fmtDate);
            setCalSelected(fmtDate);
            setCategoryId(transaction.categoryId);
        } else {
            setName('');
            setAmount('');
            setType('expense');
            setNote('');
            const todayStr = new Date().toISOString().split('T')[0];
            setTransactionAt(todayStr);
            setCalSelected(todayStr);
            setCategoryId(null);
        }
    }, [open, transaction]);

    // Handle calendar initial month/year positioning on open
    useEffect(() => {
        if (calSelected) {
            const [y, m] = calSelected.split('-').map(Number);
            setCalYear(y);
            setCalMonth(m - 1);
        } else {
            const today = new Date();
            setCalYear(today.getFullYear());
            setCalMonth(today.getMonth());
        }
    }, [calSelected]);

    if (!open) return null;

    // Derived states
    const filteredCats = categories.filter((c) => c.type === type);
    const selectedCategory = categories.find((c) => c.id === categoryId);

    // Calendar handlers
    const handlePrevMonth = () => {
        setCalMonth((m) => {
            if (m === 0) {
                setCalYear((y) => y - 1);
                return 11;
            }
            return m - 1;
        });
    };

    const handleNextMonth = () => {
        setCalMonth((m) => {
            if (m === 11) {
                setCalYear((y) => y + 1);
                return 0;
            }
            return m + 1;
        });
    };

    const selectCalDay = (day: number) => {
        const mm = String(calMonth + 1).padStart(2, '0');
        const dd = String(day).padStart(2, '0');
        setCalSelected(`${calYear}-${mm}-${dd}`);
    };

    const confirmDateSelection = () => {
        if (calSelected) {
            setTransactionAt(calSelected);
        }
        setSubView('form');
    };

    // Save action
    const handleSave = async () => {
        setError(null);
        setValidationErrors({});

        // Simple client-side validation
        if (!name.trim()) {
            setValidationErrors(prev => ({ ...prev, name: ['Nama transaksi wajib diisi'] }));
            return;
        }
        if (!amount || parseFloat(amount) <= 0) {
            setValidationErrors(prev => ({ ...prev, amount: ['Nominal transaksi harus lebih dari 0'] }));
            return;
        }
        if (!categoryId) {
            setValidationErrors(prev => ({ ...prev, categoryId: ['Silakan pilih kategori terlebih dahulu'] }));
            return;
        }

        setSaving(true);
        const payload = {
            name,
            amount: parseFloat(amount),
            type,
            note,
            transactionAt: new Date(transactionAt).toISOString(),
            categoryId,
        };

        try {
            if (isEdit && transaction?.id) {
                await api.put(`/transaction/${transaction.id}`, payload);
            } else {
                await api.post('/transaction', payload);
            }
            onSaved();
        } catch (err) {
            if (err instanceof ApiError) {
                setError(err.message);
                if (err.errors) {
                    setValidationErrors(err.errors);
                }
            } else {
                setError('Gagal menyimpan transaksi. Silakan coba lagi.');
            }
        } finally {
            setSaving(false);
        }
    };

    // Render calendar days
    const renderCalendarGrid = () => {
        const daysCount = getDaysInMonth(calYear, calMonth);
        const firstDay = getFirstDayOfMonth(calYear, calMonth);

        const cells: (number | null)[] = [];
        for (let i = 0; i < firstDay; i++) {
            cells.push(null);
        }
        for (let d = 1; d <= daysCount; d++) {
            cells.push(d);
        }

        const rows: ReactNode[] = [];
        const itemsPerRow = 7;

        for (let i = 0; i < cells.length; i += itemsPerRow) {
            const rowCells = cells.slice(i, i + itemsPerRow);
            rows.push(
                <tr key={i}>
                    {rowCells.map((day, cellIndex) => {
                        if (day === null) {
                            return <td key={cellIndex} className="p-1"></td>;
                        }

                        const mm = String(calMonth + 1).padStart(2, '0');
                        const dd = String(day).padStart(2, '0');
                        const cellDateStr = `${calYear}-${mm}-${dd}`;
                        const isSelected = cellDateStr === calSelected;

                        return (
                            <td key={cellIndex} className="p-1 text-center">
                                <button
                                    type="button"
                                    onClick={() => selectCalDay(day)}
                                    className={`flex h-9 w-9 items-center justify-center rounded-xl text-xs font-bold transition-all duration-150 cursor-pointer ${
                                        isSelected
                                            ? 'bg-primary text-white shadow-md shadow-primary/20 scale-105'
                                            : 'text-gray-700 hover:bg-gray-100'
                                    }`}
                                >
                                    {day}
                                </button>
                            </td>
                        );
                    })}
                </tr>
            );
        }

        return rows;
    };

    return (
        <>
            {/* Backdrop */}
            <div className="fixed inset-0 z-40 bg-black/40 backdrop-blur-sm transition-opacity" onClick={onClose} />

            {/* Form Drawer Panel */}
            <div className="fixed inset-y-0 right-0 z-50 flex w-full max-w-md flex-col bg-gray-50 shadow-2xl overflow-hidden animate-slide-in">
                {/* VIEW: MAIN FORM */}
                {subView === 'form' && (
                    <div className="flex flex-col h-full">
                        {/* Header */}
                        <div className="flex items-center gap-3 bg-white px-6 py-5 border-b border-gray-100 shadow-sm shrink-0">
                            <button
                                onClick={onClose}
                                className="flex h-9 w-9 items-center justify-center rounded-full border border-gray-100 bg-white text-gray-500 shadow-sm hover:bg-gray-50 hover:text-primary transition active:scale-95 cursor-pointer"
                            >
                                <ArrowLeft size={16} strokeWidth={2.5} />
                            </button>
                            <div>
                                <h2 className="text-base font-black text-gray-900">
                                    {isEdit ? 'Edit Transaksi' : 'Tambah Transaksi'}
                                </h2>
                                <p className="text-xs font-semibold text-gray-400">
                                    Catat aliran dana keuangan Anda secara manual.
                                </p>
                            </div>
                        </div>

                        {/* Form Body */}
                        <div className="flex-1 overflow-y-auto px-6 py-6 space-y-5 pb-24">
                            {error && (
                                <div className="rounded-2xl bg-red-50 p-4 text-xs font-semibold text-red-500 border border-red-100">
                                    {error}
                                </div>
                            )}

                            {/* Transaction Type Toggle */}
                            <div>
                                <label className="mb-2 block text-[10px] font-black uppercase tracking-wider text-gray-400">
                                    Tipe Transaksi
                                </label>
                                <div className="grid grid-cols-2 gap-2 bg-gray-100 p-1.5 rounded-2xl">
                                    <button
                                        type="button"
                                        onClick={() => {
                                            setType('expense');
                                            setCategoryId(null);
                                        }}
                                        className={`rounded-xl py-3 text-xs font-bold transition-all duration-200 cursor-pointer ${
                                            type === 'expense'
                                                ? 'bg-red-500 text-white shadow-md shadow-red-500/20'
                                                : 'text-gray-500 hover:text-gray-900'
                                        }`}
                                    >
                                        Pengeluaran
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => {
                                            setType('income');
                                            setCategoryId(null);
                                        }}
                                        className={`rounded-xl py-3 text-xs font-bold transition-all duration-200 cursor-pointer ${
                                            type === 'income'
                                                ? 'bg-emerald-500 text-white shadow-md shadow-emerald-500/20'
                                                : 'text-gray-500 hover:text-gray-900'
                                        }`}
                                    >
                                        Pemasukan
                                    </button>
                                </div>
                            </div>

                            {/* Amount Input */}
                            <div>
                                <label className={`mb-1.5 block text-[10px] font-black uppercase tracking-wider ${
                                    validationErrors.amount ? 'text-red-500' : 'text-gray-400'
                                }`}>
                                    Nominal Transaksi
                                </label>
                                <div className={`flex items-center rounded-2xl border bg-white px-4 py-3.5 transition focus-within:ring-2 focus-within:ring-primary/20 ${
                                    validationErrors.amount ? 'border-red-400' : 'border-gray-200 focus-within:border-primary'
                                }`}>
                                    <span className="mr-2 text-sm font-black text-gray-400">Rp</span>
                                    <input
                                        type="number"
                                        placeholder="0"
                                        value={amount}
                                        onChange={(e) => setAmount(e.target.value)}
                                        className="w-full text-base font-bold text-gray-800 outline-none p-0 border-none bg-transparent"
                                    />
                                </div>
                                {validationErrors.amount && (
                                    <p className="mt-1 text-[10px] font-bold text-red-500">{validationErrors.amount[0]}</p>
                                )}
                            </div>

                            {/* Name Input */}
                            <div>
                                <label className={`mb-1.5 block text-[10px] font-black uppercase tracking-wider ${
                                    validationErrors.name ? 'text-red-500' : 'text-gray-400'
                                }`}>
                                    Judul / Nama Transaksi
                                </label>
                                <input
                                    type="text"
                                    placeholder="Contoh: Belanja Bulanan"
                                    value={name}
                                    onChange={(e) => setName(e.target.value)}
                                    className={`w-full rounded-2xl border bg-white px-4 py-3.5 text-sm font-bold text-gray-800 outline-none transition focus:ring-2 focus:ring-primary/20 ${
                                        validationErrors.name ? 'border-red-400 focus:border-red-500' : 'border-gray-200 focus:border-primary'
                                    }`}
                                />
                                {validationErrors.name && (
                                    <p className="mt-1 text-[10px] font-bold text-red-500">{validationErrors.name[0]}</p>
                                )}
                            </div>

                            {/* Category Selector */}
                            <div>
                                <label className={`mb-1.5 block text-[10px] font-black uppercase tracking-wider ${
                                    validationErrors.categoryId ? 'text-red-500' : 'text-gray-400'
                                }`}>
                                    Kategori
                                </label>
                                <button
                                    type="button"
                                    onClick={() => {
                                        setTempCategoryId(categoryId);
                                        setSubView('category');
                                    }}
                                    className={`flex w-full items-center justify-between rounded-2xl border bg-white px-4 py-3.5 text-left text-sm font-bold transition hover:bg-gray-50/50 cursor-pointer ${
                                        validationErrors.categoryId ? 'border-red-400' : 'border-gray-200 hover:border-primary'
                                    }`}
                                >
                                    <span className="flex items-center gap-2.5">
                                        {selectedCategory ? (
                                            <>
                                                <div className="flex h-6 w-6 items-center justify-center rounded-lg bg-primary-light text-primary">
                                                    <CategoryPhosphorIcon iconName={selectedCategory.icon} size={15} />
                                                </div>
                                                <span className="text-gray-800">{selectedCategory.name}</span>
                                            </>
                                        ) : (
                                            <span className="text-gray-400 font-medium">Pilih kategori transaksi</span>
                                        )}
                                    </span>
                                    <span className="text-xs font-black text-primary">Pilih</span>
                                </button>
                                {validationErrors.categoryId && (
                                    <p className="mt-1 text-[10px] font-bold text-red-500">{validationErrors.categoryId[0]}</p>
                                )}
                            </div>

                            {/* Date Selector */}
                            <div>
                                <label className="mb-1.5 block text-[10px] font-black uppercase tracking-wider text-gray-400">
                                    Tanggal Transaksi
                                </label>
                                <button
                                    type="button"
                                    onClick={() => setSubView('date')}
                                    className="flex w-full items-center justify-between rounded-2xl border border-gray-200 bg-white px-4 py-3.5 text-left text-sm font-bold text-gray-800 transition hover:bg-gray-50/50 cursor-pointer"
                                >
                                    <span>{formatDateDisplay(transactionAt) || 'Pilih tanggal'}</span>
                                    <Calendar size={16} className="text-gray-400" />
                                </button>
                            </div>

                            {/* Notes Textarea */}
                            <div>
                                <label className="mb-1.5 block text-[10px] font-black uppercase tracking-wider text-gray-400">
                                    Catatan (Opsional)
                                </label>
                                <textarea
                                    placeholder="Tambah catatan kecil di sini..."
                                    rows={4}
                                    value={note}
                                    onChange={(e) => setNote(e.target.value)}
                                    className="w-full rounded-2xl border border-gray-200 bg-white px-4 py-3.5 text-sm font-bold text-gray-800 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20 resize-none"
                                />
                            </div>
                        </div>

                        {/* Sticky Action Footer */}
                        <div className="absolute bottom-0 inset-x-0 bg-white p-6 border-t border-gray-100 flex gap-4 shrink-0">
                            <button
                                type="button"
                                onClick={onClose}
                                className="flex-1 rounded-2xl border border-red-500 bg-white py-4 text-xs font-black uppercase tracking-wider text-red-500 transition hover:bg-red-50 active:scale-98 cursor-pointer"
                            >
                                Batal
                            </button>
                            <button
                                type="button"
                                onClick={handleSave}
                                disabled={saving}
                                className="flex-1 rounded-2xl bg-[#FF9800] py-4 text-xs font-black uppercase tracking-wider text-white shadow-md shadow-orange-500/10 transition hover:bg-orange-600 active:scale-98 disabled:opacity-50 cursor-pointer"
                            >
                                {saving ? 'Menyimpan...' : 'Simpan'}
                            </button>
                        </div>
                    </div>
                )}

                {/* VIEW: CATEGORY PICKER */}
                {subView === 'category' && (
                    <CategoryPickerModal
                        open
                        categories={filteredCats}
                        selectedId={tempCategoryId}
                        onSelect={(cat) => setTempCategoryId(cat.id)}
                        onConfirm={() => {
                            setCategoryId(tempCategoryId);
                            setSubView('form');
                        }}
                        onBack={() => setSubView('form')}
                    />
                )}

                {/* VIEW: CALENDAR PICKER */}
                {subView === 'date' && (
                    <div className="flex flex-col h-full bg-white">
                        {/* Header */}
                        <div className="flex items-center gap-3 p-6 pb-4 border-b border-gray-100 shrink-0">
                            <button
                                onClick={() => setSubView('form')}
                                className="flex h-9 w-9 items-center justify-center rounded-full border border-gray-100 bg-white text-gray-500 shadow-sm hover:bg-gray-50 active:scale-95 cursor-pointer"
                            >
                                <ArrowLeft size={16} strokeWidth={2.5} />
                            </button>
                            <div>
                                <h2 className="text-base font-black text-gray-900">
                                    Pilih Tanggal
                                </h2>
                                <p className="text-xs font-semibold text-gray-400">
                                    Tentukan hari pencatatan transaksi Anda.
                                </p>
                            </div>
                        </div>

                        {/* Calendar Controls */}
                        <div className="flex items-center justify-between px-6 py-4">
                            <button
                                type="button"
                                onClick={handlePrevMonth}
                                className="flex h-9 w-9 items-center justify-center rounded-full border border-gray-100 text-gray-600 hover:bg-gray-50 active:scale-95 cursor-pointer"
                            >
                                <ChevronLeft size={16} strokeWidth={2.5} />
                            </button>
                            <span className="text-sm font-black text-gray-800">
                                {MONTH_ID[calMonth]} {calYear}
                            </span>
                            <button
                                type="button"
                                onClick={handleNextMonth}
                                className="flex h-9 w-9 items-center justify-center rounded-full border border-gray-100 text-gray-600 hover:bg-gray-50 active:scale-95 cursor-pointer"
                            >
                                <ChevronRight size={16} strokeWidth={2.5} />
                            </button>
                        </div>

                        {/* Calendar Grid */}
                        <div className="flex-1 overflow-y-auto px-6">
                            <table className="w-full table-fixed border-collapse">
                                <thead>
                                    <tr>
                                        {DAY_SHORT.map((day) => (
                                            <th key={day} className="pb-3 text-center text-[10px] font-black uppercase tracking-wider text-gray-400">
                                                {day}
                                            </th>
                                        ))}
                                    </tr>
                                </thead>
                                <tbody>
                                    {renderCalendarGrid()}
                                </tbody>
                            </table>
                        </div>

                        {/* Actions */}
                        <div className="p-6 border-t border-gray-100 flex gap-4 shrink-0 bg-gray-50/50">
                            <button
                                type="button"
                                onClick={() => setSubView('form')}
                                className="flex-1 rounded-2xl border border-gray-200 bg-white py-4 text-xs font-black uppercase tracking-wider text-gray-600 hover:bg-gray-50 active:scale-98 cursor-pointer"
                            >
                                Batal
                            </button>
                            <button
                                type="button"
                                onClick={confirmDateSelection}
                                disabled={!calSelected}
                                className="flex-1 rounded-2xl bg-secondary py-4 text-xs font-black uppercase tracking-wider text-white shadow-md shadow-secondary/15 hover:bg-secondary/95 active:scale-98 disabled:opacity-50 cursor-pointer"
                            >
                                Pilih
                            </button>
                        </div>
                    </div>
                )}
            </div>
        </>
    );
}
