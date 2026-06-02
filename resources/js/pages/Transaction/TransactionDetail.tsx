import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Trash2 } from 'lucide-react';
import { useState } from 'react';
import DeleteConfirmDialog from '@/components/DeleteConfirmDialog';
import AppLayout from '@/layouts/AppLayout';
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

interface TransactionData {
    id: number;
    name: string;
    amount: number;
    type: 'income' | 'expense';
    source: string;
    note: string | null;
    transactionAt: string;
    category: TransactionCategory | null;
}

interface Props {
    transaction: TransactionData;
    categories: Category[];
}

/* ── helpers ─────────────────────────────────────────────── */
function formatRp(value: string | number) {
    const num = typeof value === 'number' ? value : parseFloat(value);
    if (isNaN(num)) return 'Rp 0';
    return 'Rp ' + Math.round(num).toLocaleString('id-ID');
}

const MONTH_ID = [
    'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
];

function formatDate(iso: string) {
    if (!iso) return '';
    const d = new Date(iso);
    if (isNaN(d.getTime())) return '';
    return `${d.getDate()} ${MONTH_ID[d.getMonth()]} ${d.getFullYear()}`;
}

function formatSource(src: string) {
    switch (src) {
        case 'manual': return 'Pencatatan Manual';
        case 'batch': return 'Batch';
        case 'fixed_cost_payment': return 'Biaya Tetap';
        default: return src ?? 'Manual';
    }
}

/* ── Component ───────────────────────────────────────────── */
export default function TransactionDetail({ transaction }: Props) {
    const [showDelete, setShowDelete] = useState(false);
    const [deleting, setDeleting] = useState(false);

    const handleDelete = async () => {
        setDeleting(true);
        try {
            await api.delete(`/transaction/${transaction.id}`);
            router.visit('/history');
        } catch (err) {
            console.error(err);
            alert('Gagal menghapus transaksi.');
        } finally {
            setDeleting(false);
            setShowDelete(false);
        }
    };

    const isExpense = transaction.type === 'expense';

    return (
        <AppLayout>
            <Head title="Detail Transaksi" />

            <div className="px-4 py-6 lg:px-8 lg:py-8 mx-auto max-w-4xl space-y-6">
                {/* Header — matching TransactionCreate */}
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between border-b border-gray-100 pb-6">
                    <div className="flex items-center gap-3">
                        <Link
                            href="/history"
                            className="flex h-9 w-9 items-center justify-center rounded-full text-gray-500 transition hover:bg-gray-100 cursor-pointer"
                        >
                            <ArrowLeft size={20} />
                        </Link>
                        <div>
                            <h1 className="text-xl font-bold text-gray-800 lg:text-2xl">
                                Detail Transaksi
                            </h1>
                            <p className="text-sm text-gray-500">
                                Rincian detail catatan transaksi keuangan Anda
                            </p>
                        </div>
                    </div>

                    {/* Delete button in header */}
                    <button
                        type="button"
                        onClick={() => setShowDelete(true)}
                        className="flex items-center gap-2 rounded-xl border border-red-200 bg-red-50 px-4 py-2.5 text-xs font-bold text-red-500 transition hover:bg-red-100 active:scale-98 cursor-pointer"
                    >
                        <Trash2 size={14} strokeWidth={2.5} />
                        <span>Hapus Transaksi</span>
                    </button>
                </div>

                {/* Main Card Container — same structure as TransactionCreate */}
                <div className="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm space-y-6">
                    {/* Type Display */}
                    <div>
                        <label className="mb-2 block text-[10px] font-black uppercase tracking-wider text-gray-400">
                            Tipe Transaksi
                        </label>
                        <div className="grid grid-cols-2 gap-3 bg-gray-50 p-1.5 rounded-xl border border-gray-100">
                            <div
                                className={`rounded-lg py-3 text-xs font-bold text-center transition-all duration-200 ${
                                    isExpense
                                        ? 'bg-red-500 text-white shadow-md shadow-red-500/20'
                                        : 'text-gray-400'
                                }`}
                            >
                                Pengeluaran
                            </div>
                            <div
                                className={`rounded-lg py-3 text-xs font-bold text-center transition-all duration-200 ${
                                    !isExpense
                                        ? 'bg-emerald-500 text-white shadow-md shadow-emerald-500/20'
                                        : 'text-gray-400'
                                }`}
                            >
                                Pemasukan
                            </div>
                        </div>
                    </div>

                    {/* Nominal */}
                    <div>
                        <label className="mb-1.5 block text-[10px] font-black uppercase tracking-wider text-gray-400">
                            Nominal Transaksi
                        </label>
                        <div className="flex items-center rounded-xl border border-gray-200 bg-gray-50/50 px-4 py-3">
                            <span className="mr-2 text-sm font-black text-gray-400">Rp</span>
                            <span className={`text-base font-bold ${isExpense ? 'text-red-500' : 'text-emerald-600'}`}>
                                {isExpense ? '- ' : '+ '}{formatRp(transaction.amount).replace('Rp ', '')}
                            </span>
                        </div>
                    </div>

                    {/* Nama / Judul */}
                    <div>
                        <label className="mb-1.5 block text-[10px] font-black uppercase tracking-wider text-gray-400">
                            Judul / Nama Transaksi
                        </label>
                        <div className="w-full rounded-xl border border-gray-200 bg-gray-50/50 px-4 py-3 text-sm font-semibold text-gray-800">
                            {transaction.name}
                        </div>
                    </div>

                    {/* Kategori */}
                    <div>
                        <label className="mb-2.5 block text-[10px] font-black uppercase tracking-wider text-gray-400">
                            Kategori
                        </label>
                        {transaction.category ? (
                            <div className="inline-flex items-center gap-3 rounded-xl border border-primary bg-primary-light/20 p-2.5 ring-2 ring-primary ring-offset-1">
                                <div className="flex h-9 w-9 items-center justify-center rounded-lg bg-primary text-white shadow-sm">
                                    <CategoryPhosphorIcon iconName={transaction.category.icon} size={16} />
                                </div>
                                <span className="text-xs font-bold text-gray-700 pr-2">
                                    {transaction.category.name}
                                </span>
                            </div>
                        ) : (
                            <div className="rounded-xl border border-gray-200 bg-gray-50/50 px-4 py-3 text-sm text-gray-400 italic">
                                Tidak ada kategori
                            </div>
                        )}
                    </div>

                    {/* Tanggal */}
                    <div>
                        <label className="mb-1.5 block text-[10px] font-black uppercase tracking-wider text-gray-400">
                            Tanggal Transaksi
                        </label>
                        <div className="w-full rounded-xl border border-gray-200 bg-gray-50/50 px-4 py-3 text-sm font-semibold text-gray-800">
                            {formatDate(transaction.transactionAt) || '—'}
                        </div>
                    </div>

                    {/* Sumber */}
                    <div>
                        <label className="mb-1.5 block text-[10px] font-black uppercase tracking-wider text-gray-400">
                            Sumber Transaksi
                        </label>
                        <div className="w-full rounded-xl border border-gray-200 bg-gray-50/50 px-4 py-3 text-sm font-semibold text-gray-800">
                            {formatSource(transaction.source)}
                        </div>
                    </div>

                    {/* Catatan */}
                    <div>
                        <label className="mb-1.5 block text-[10px] font-black uppercase tracking-wider text-gray-400">
                            Catatan
                        </label>
                        <div className="w-full rounded-xl border border-gray-200 bg-gray-50/50 px-4 py-3 text-sm font-semibold text-gray-800 min-h-[80px] whitespace-pre-wrap">
                            {transaction.note || '—'}
                        </div>
                    </div>

                    {/* Action Button — Update */}
                    <div className="pt-2">
                        <Link
                            href={`/transaction/${transaction.id}/edit`}
                            className="flex w-full justify-center rounded-xl bg-[#FF9800] py-3.5 text-xs font-black uppercase tracking-wider text-white shadow-sm shadow-orange-500/10 transition hover:bg-orange-600 active:scale-98 cursor-pointer"
                        >
                            Update Transaksi
                        </Link>
                    </div>
                </div>
            </div>

            {/* Delete confirm */}
            <DeleteConfirmDialog
                open={showDelete}
                onConfirm={handleDelete}
                onCancel={() => setShowDelete(false)}
                isLoading={deleting}
                title="Hapus Transaksi?"
                description={`Yakin ingin menghapus transaksi "${transaction.name}"? Tindakan ini tidak dapat dibatalkan.`}
            />
        </AppLayout>
    );
}
