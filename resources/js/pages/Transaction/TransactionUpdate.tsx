import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Calendar, Sparkles } from 'lucide-react';
import { useState, useEffect } from 'react';
import CategoryPickerModal from '@/components/CategoryPickerModal';
import type { Category } from '@/components/CategoryPickerModal';
import AppLayout from '@/layouts/AppLayout';
import { api, ApiError } from '@/lib/api';
import DatePickerModal from '@/pages/Transaction/DatePickerModal';
import { CategoryPhosphorIcon } from '@/utils/phosphorIconMap';

interface TransactionData {
  id: number;
  name: string;
  amount: number;
  type: 'income' | 'expense';
  source: string;
  note: string | null;
  transactionAt: string;
  categoryId: number | null;
}

interface Props {
  transaction: TransactionData;
  categories: Category[];
}

const MONTH_ID = [
  'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
  'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];

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

export default function TransactionUpdate({ transaction, categories }: Props) {
  const [name, setName] = useState('');
  const [amount, setAmount] = useState('');
  const [type, setType] = useState<'income' | 'expense'>('expense');
  const [note, setNote] = useState('');
  const [transactionAt, setTransactionAt] = useState('');
  const [categoryId, setCategoryId] = useState<number | null>(null);

  // Modals visibility
  const [showCategoryModal, setShowCategoryModal] = useState(false);
  const [showDatePicker, setShowDatePicker] = useState(false);

  // API loading & error states
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [validationErrors, setValidationErrors] = useState<Record<string, string[]>>({});

  // Prefill state with transaction prop
  useEffect(() => {
    if (transaction) {
      setName(transaction.name);
      setAmount(transaction.amount.toString());
      setType(transaction.type);
      setNote(transaction.note || '');
      setTransactionAt(formatDateInput(transaction.transactionAt));
      setCategoryId(transaction.categoryId);
    }
  }, [transaction]);

  const filteredCats = categories.filter((c) => c.type === type);
  const selectedCategory = categories.find((c) => c.id === categoryId);

  // Take the first 3 categories for quick selection, 4th is "Lihat Semua"
  const quickCategories = filteredCats.slice(0, 3);
  const isSelectedInQuick = quickCategories.some(c => c.id === categoryId);

  const handleSave = async () => {
    setError(null);
    setValidationErrors({});

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
      await api.put(`/transaction/${transaction.id}`, payload);
      router.visit(`/transaction/${transaction.id}`);
    } catch (err) {
      if (err instanceof ApiError) {
        setError(err.message);
        if (err.errors) {
          setValidationErrors(err.errors);
        }
      } else {
        setError('Gagal memperbarui transaksi. Silakan coba lagi.');
      }
    } finally {
      setSaving(false);
    }
  };

  return (
    <AppLayout>
      <Head title="Edit Transaksi" />

      <div className="px-4 py-6 lg:px-8 lg:py-8 mx-auto max-w-4xl space-y-6">
        {/* Header */}
        <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between border-b border-gray-100 pb-6">
          <div className="flex items-center gap-3">
            <Link
              href={`/transaction/${transaction.id}`}
              className="flex h-9 w-9 items-center justify-center rounded-full text-gray-500 transition hover:bg-gray-100 cursor-pointer"
            >
              <ArrowLeft size={20} />
            </Link>
            <div>
              <h1 className="text-xl font-bold text-gray-800 lg:text-2xl">
                Edit Transaksi
              </h1>
              <p className="text-sm text-gray-500">
                Perbarui catatan transaksi keuangan Anda yang sudah ada
              </p>
            </div>
          </div>
        </div>

        {/* Main Card Container */}
        <div className="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm space-y-6">
          {error && (
            <div className="rounded-2xl bg-red-50 p-4 text-xs font-semibold text-red-500 border border-red-100">
              {error}
            </div>
          )}

          {/* Type Selector (Pengeluaran vs Pemasukan) */}
          <div>
            <label className="mb-2 block text-[10px] font-black uppercase tracking-wider text-gray-400">
              Tipe Transaksi
            </label>
            <div className="grid grid-cols-2 gap-3 bg-gray-50 p-1.5 rounded-xl border border-gray-100">
              <button
                type="button"
                onClick={() => {
                  setType('expense');
                  setCategoryId(null);
                }}
                className={`rounded-lg py-3 text-xs font-bold transition-all duration-200 cursor-pointer ${
                  type === 'expense'
                    ? 'bg-red-500 text-white shadow-md shadow-red-500/20'
                    : 'text-gray-500 hover:text-gray-900 hover:bg-gray-100/50'
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
                className={`rounded-lg py-3 text-xs font-bold transition-all duration-200 cursor-pointer ${
                  type === 'income'
                    ? 'bg-emerald-500 text-white shadow-md shadow-emerald-500/20'
                    : 'text-gray-500 hover:text-gray-900 hover:bg-gray-100/50'
                }`}
              >
                Pemasukan
              </button>
            </div>
          </div>

          {/* Nominal Input */}
          <div>
            <label className={`mb-1.5 block text-[10px] font-black uppercase tracking-wider ${
              validationErrors.amount ? 'text-red-500' : 'text-gray-400'
            }`}>
              Nominal Transaksi
            </label>
            <div className={`flex items-center rounded-xl border bg-white px-4 py-3 transition focus-within:ring-2 focus-within:ring-primary/20 ${
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
              placeholder="Contoh: Belanja Bulanan / Gaji Utama"
              value={name}
              onChange={(e) => setName(e.target.value)}
              className={`w-full rounded-xl border bg-white px-4 py-3 text-sm font-semibold text-gray-800 outline-none transition focus:ring-2 focus:ring-primary/20 ${
                validationErrors.name ? 'border-red-400 focus:border-red-500' : 'border-gray-200 focus:border-primary'
              }`}
            />
            {validationErrors.name && (
              <p className="mt-1 text-[10px] font-bold text-red-500">{validationErrors.name[0]}</p>
            )}
          </div>

          {/* Category Quick Picker & Lihat Semua */}
          <div>
            <label className={`mb-2.5 block text-[10px] font-black uppercase tracking-wider ${
              validationErrors.categoryId ? 'text-red-500' : 'text-gray-400'
            }`}>
              Pilih Kategori
            </label>
            <div className="grid grid-cols-4 gap-3">
              {quickCategories.map((cat) => {
                const isSelected = cat.id === categoryId;
                return (
                  <button
                    key={cat.id}
                    type="button"
                    onClick={() => setCategoryId(cat.id)}
                    className={`flex flex-col items-center justify-center gap-1.5 rounded-xl p-2.5 border transition duration-200 cursor-pointer ${
                      isSelected
                        ? 'border-primary bg-primary-light/20 font-bold scale-102 ring-2 ring-primary ring-offset-1'
                        : 'border-gray-200 hover:bg-gray-50'
                    }`}
                  >
                    <div className={`flex h-9 w-9 items-center justify-center rounded-lg bg-gray-50 text-gray-600 ${
                      isSelected ? 'bg-primary text-white shadow-sm' : ''
                    }`}>
                      <CategoryPhosphorIcon iconName={cat.icon} size={16} />
                    </div>
                    <span className="text-[10px] text-gray-600 truncate w-full text-center">
                      {cat.name}
                    </span>
                  </button>
                );
              })}

              {/* Lihat Semua / Custom Selected Button */}
              <button
                type="button"
                onClick={() => setShowCategoryModal(true)}
                className={`flex flex-col items-center justify-center gap-1.5 rounded-xl p-2.5 border transition duration-200 cursor-pointer ${
                  !isSelectedInQuick && categoryId !== null
                    ? 'border-primary bg-primary-light/20 font-bold scale-102 ring-2 ring-primary ring-offset-1'
                    : 'border-gray-200 hover:bg-gray-50'
                }`}
              >
                <div className={`flex h-9 w-9 items-center justify-center rounded-lg bg-gray-50 text-gray-600 ${
                  !isSelectedInQuick && categoryId !== null ? 'bg-primary text-white shadow-sm' : ''
                }`}>
                  {selectedCategory && !isSelectedInQuick ? (
                    <CategoryPhosphorIcon iconName={selectedCategory.icon} size={16} />
                  ) : (
                    <Sparkles size={16} />
                  )}
                </div>
                <span className="text-[10px] text-gray-600 truncate w-full text-center">
                  {selectedCategory && !isSelectedInQuick ? selectedCategory.name : 'Lihat Semua'}
                </span>
              </button>
            </div>
            {validationErrors.categoryId && (
              <p className="mt-2 text-[10px] font-bold text-red-500">{validationErrors.categoryId[0]}</p>
            )}
          </div>

          {/* Date Picker */}
          <div>
            <label className="mb-1.5 block text-[10px] font-black uppercase tracking-wider text-gray-400">
              Pilih Tanggal
            </label>
            <button
              type="button"
              onClick={() => setShowDatePicker(true)}
              className="flex w-full items-center justify-between rounded-xl border border-gray-200 bg-white px-4 py-3 text-left text-sm font-semibold text-gray-800 transition hover:bg-gray-50/50 cursor-pointer"
            >
              <span>{formatDateDisplay(transactionAt) || 'Pilih tanggal'}</span>
              <Calendar size={16} className="text-gray-400" />
            </button>
          </div>

          {/* Notes (Optional) */}
          <div>
            <label className="mb-1.5 block text-[10px] font-black uppercase tracking-wider text-gray-400">
              Catatan (Opsional)
            </label>
            <textarea
              placeholder="Tambahkan catatan singkat di sini..."
              rows={4}
              value={note}
              onChange={(e) => setNote(e.target.value)}
              className="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-semibold text-gray-800 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20 resize-none"
            />
          </div>

          {/* Save Button */}
          <div className="pt-2">
            <button
              type="button"
              onClick={handleSave}
              disabled={saving}
              className="w-full rounded-xl bg-[#FF9800] py-3.5 text-xs font-black uppercase tracking-wider text-white shadow-sm shadow-orange-500/10 transition hover:bg-orange-600 active:scale-98 disabled:opacity-50 cursor-pointer"
            >
              {saving ? 'Menyimpan...' : 'Simpan Perubahan'}
            </button>
          </div>
        </div>
      </div>

      {/* Categories Modal */}
      {showCategoryModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-4">
          <div className="relative w-full max-w-lg h-[520px] rounded-3xl overflow-hidden shadow-2xl">
            <CategoryPickerModal
              open
              categories={filteredCats}
              selectedId={categoryId}
              onSelect={(cat) => setCategoryId(cat.id)}
              onConfirm={() => setShowCategoryModal(false)}
              onBack={() => setShowCategoryModal(false)}
            />
          </div>
        </div>
      )}

      {/* Date Picker Modal */}
      <DatePickerModal
        open={showDatePicker}
        value={transactionAt}
        onConfirm={(date) => {
          setTransactionAt(date);
          setShowDatePicker(false);
        }}
        onClose={() => setShowDatePicker(false)}
      />
    </AppLayout>
  );
}
