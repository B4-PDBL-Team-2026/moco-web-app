import { Link } from '@inertiajs/react';
import {
    Receipt,
    ArrowUpRight,
    CreditCard,
    FileText
} from 'lucide-react';
import React from 'react';
import type { TransactionItem } from '../types';

interface RecentTransactionsProps {
    transactions: TransactionItem[];
}

export default function RecentTransactions({ transactions }: RecentTransactionsProps) {
    const formatRupiah = (value: number) => {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
        }).format(value).replace('IDR', 'Rp');
    };

    // Helper to pick icons dynamically based on category name or type
    const getTransactionIcon = (item: TransactionItem) => {
        if (item.type === 'income') {
            return (
                <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-green-50 text-green-600">
                    <ArrowUpRight size={20} />
                </div>
            );
        }

        const categoryName = item.category?.name?.toLowerCase() || '';
        if (categoryName.includes('listrik') || categoryName.includes('tagihan') || categoryName.includes('bill')) {
            return (
                <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                    <Receipt size={20} />
                </div>
            );
        }
        if (categoryName.includes('belanja') || categoryName.includes('shop')) {
            return (
                <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50 text-amber-600">
                    <CreditCard size={20} />
                </div>
            );
        }

        return (
            <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-gray-50 text-gray-600">
                <FileText size={20} />
            </div>
        );
    };

    return (
        <div className="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
            <div className="mb-6 flex items-center justify-between">
                <h3 className="text-lg font-black text-gray-900 tracking-tight">
                    Transaksi Terbaru
                </h3>
                <Link
                    href="/transactions"
                    className="text-sm font-extrabold text-blue-600 hover:text-blue-700 transition"
                >
                    Semua
                </Link>
            </div>

            {transactions.length === 0 ? (
                <div className="py-8 text-center text-sm font-medium text-gray-400">
                    Belum ada transaksi terbaru
                </div>
            ) : (
                <div className="divide-y divide-gray-100">
                    {transactions.map((transaction) => {
                        const isExpense = transaction.type === 'expense';
                        return (
                            <div key={transaction.id} className="flex items-center justify-between py-4 first:pt-0 last:pb-0">
                                <div className="flex items-center gap-3">
                                    {getTransactionIcon(transaction)}
                                    <div>
                                        <h4 className="text-sm font-bold text-gray-900">
                                            {transaction.name}
                                        </h4>
                                        <span className="text-xs font-semibold text-gray-400">
                                            {transaction.category?.name || (isExpense ? 'Pengeluaran' : 'Pemasukan')}
                                        </span>
                                    </div>
                                </div>
                                <div className={`text-sm font-black ${isExpense ? 'text-red-500' : 'text-green-500'}`}>
                                    {isExpense ? '-' : '+'} {formatRupiah(transaction.amount)}
                                </div>
                            </div>
                        );
                    })}
                </div>
            )}
        </div>
    );
}
