import { Head, Link, router } from '@inertiajs/react';
import {
    ArrowLeft,
    CalendarDays,
    FileText,
    PencilLine,
    Layers,
} from 'lucide-react';
import { useState } from 'react';
import type { ReactNode } from 'react';
import AppLayout from '@/layouts/AppLayout';
import DeleteConfirmDialog from '@/components/DeleteConfirmDialog';
import { api } from '@/lib/api';

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

/* ── InfoRow ─────────────────────────────────────────────── */
function InfoRow({
    icon,
    label,
    value,
}: {
    icon: ReactNode;
    label: string;
    value: string;
}) {
    return (
        <div className="flex items-center gap-4 bg-white rounded-2xl px-5 py-4 shadow-sm border border-gray-100">
            <div className="flex size-10 shrink-0 items-center justify-center rounded-xl bg-[#EEF3FF] text-[#2F5FBF]">
                {icon}
            </div>
            <div className="min-w-0">
                <p className="text-xs font-semibold text-gray-400">{label}</p>
                <p className="mt-0.5 text-sm font-bold text-gray-800 truncate">{value}</p>
            </div>
        </div>
    );
}

/* ── Component ───────────────────────────────────────────── */
export default function TransactionDetail({ transaction, categories }: Props) {
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

    return (
        <AppLayout>
            <Head title="Detail Transaksi" />

            <div className="mx-auto w-full max-w-md pb-12">
                {/* Header */}
                <div className="flex items-center gap-3 py-5 px-1 shrink-0">
                    <Link
                        href="/history"
                        className="flex h-9 w-9 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-500 shadow-sm hover:bg-gray-50 hover:text-primary transition active:scale-95 cursor-pointer"
                    >
                        <ArrowLeft size={16} strokeWidth={2.5} />
                    </Link>
                    <div>
                        <h2 className="text-base font-black text-gray-900">
                            Detail Transaksi
                        </h2>
                        <p className="text-xs font-semibold text-gray-400">
                            Rincian detail catatan transaksi keuangan Anda.
                        </p>
                    </div>
                </div>

                {/* Hero Card */}
                <div className="relative mb-6 overflow-hidden rounded-3xl bg-[#2F5FBF] p-6 text-white shadow-lg shadow-blue-500/10">
                    {/* Background decorations */}
                    <div className="absolute -right-12 -top-12 h-36 w-36 rounded-full bg-white/5" />
                    <div className="absolute -left-8 -bottom-8 h-28 w-28 rounded-full bg-white/5" />

                    <div className="flex items-start justify-between">
                        <div>
                            <span className="text-[10px] font-black uppercase tracking-wider text-white/60">
                                Total Nominal
                            </span>
                            <h1 className="mt-1 text-2xl font-black tracking-tight">
                                {formatRp(transaction.amount)}
                            </h1>
                        </div>

                        {/* Type Badge */}
                        <div className={`rounded-xl px-3 py-1.5 text-xs font-black uppercase tracking-wider ${
                            transaction.type === 'expense'
                                ? 'bg-red-500/20 text-red-200'
                                : 'bg-emerald-500/20 text-emerald-200'
                        }`}>
                            {transaction.type === 'expense' ? 'Pengeluaran' : 'Pemasukan'}
                        </div>
                    </div>

                    {transaction.category && (
                        <div className="mt-5 flex items-center gap-2 border-t border-white/10 pt-4">
                            <span className="text-[10px] font-black uppercase tracking-wider text-white/50">
                                Kategori
                            </span>
                            <span className="rounded-lg bg-white/10 px-2.5 py-1 text-xs font-bold text-white">
                                {transaction.category.name}
                            </span>
                        </div>
                    )}
                </div>

                {/* Info rows */}
                <div className="space-y-3">
                    <InfoRow
                        icon={<PencilLine size={18} />}
                        label="Judul / Nama Transaksi"
                        value={transaction.name}
                    />
                    <InfoRow
                        icon={<Layers size={18} />}
                        label="Sumber"
                        value={formatSource(transaction.source)}
                    />
                    <InfoRow
                        icon={<CalendarDays size={18} />}
                        label="Tanggal"
                        value={formatDate(transaction.transactionAt)}
                    />
                    <InfoRow
                        icon={<FileText size={18} />}
                        label="Catatan"
                        value={transaction.note ?? '—'}
                    />
                </div>

                {/* Aksi Section */}
                <div className="mt-6 rounded-3xl bg-white border border-gray-100 p-5 shadow-sm space-y-4">
                    <div className="flex items-center justify-between border-b border-gray-50 pb-3">
                        <h3 className="text-xs font-black uppercase tracking-wider text-gray-400">
                            Aksi Transaksi
                        </h3>
                    </div>
                    <div className="grid grid-cols-2 gap-4">
                        <button
                            type="button"
                            onClick={() => setShowDelete(true)}
                            className="rounded-2xl border border-red-500 py-3.5 text-xs font-black uppercase tracking-wider text-red-500 transition hover:bg-red-50 active:scale-98 cursor-pointer"
                        >
                            Hapus
                        </button>
                        <Link
                            href={`/transaction/${transaction.id}/edit`}
                            className="rounded-2xl bg-[#FF9800] py-3.5 text-xs font-black uppercase tracking-wider text-white shadow-md shadow-orange-500/10 transition hover:bg-orange-600 active:scale-98 cursor-pointer flex justify-center items-center"
                        >
                            Update
                        </Link>
                    </div>
                    <p className="text-[10px] font-semibold text-gray-400 text-center leading-normal">
                        Semua perubahan data akan langsung terupdate secara real-time pada dashboard dan laporan bulanan Anda.
                    </p>
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
