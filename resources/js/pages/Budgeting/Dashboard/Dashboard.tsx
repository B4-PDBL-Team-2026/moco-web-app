import { Link } from '@inertiajs/react';
import {
    CalendarClock,
    Pencil,
    Plus,
    TrendingDown,
    TrendingUp,
    Wallet,
} from 'lucide-react';

import { useState } from 'react';

import AppLayout from '@/layouts/AppLayout';

interface UnpaidFixedCost {
    name: string;
    amount: number;
    cycle: string;
    due_value: number;
}

interface Category {
    id: number;
    name: string;
    icon: string | null;
    type: string;
}

interface Props {
    status: 'stabil' | 'defisit' | 'kritis' | 'surplus';
    currentBalance: number;
    todaySpent: number;
    todayLimit: number;
    rawTodayLimit: number;
    tomorrowLimitPrediction: number;
    safetyCeiling: number;
    safetyFlooring: number;
    budgetCycle: string;
    unpaidFixedCosts: UnpaidFixedCost[];
    categories: Category[];
}

function formatRp(value: string | number) {
    const num = typeof value === 'number' ? value : parseFloat(value);
    if (isNaN(num)) return 'Rp 0';
    return 'Rp ' + Math.round(num).toLocaleString('id-ID');
}

export default function Dashboard({
    status,
    currentBalance,
    todaySpent,
    todayLimit,
    rawTodayLimit,
    tomorrowLimitPrediction,
    safetyCeiling,
    safetyFlooring,
    budgetCycle,
    unpaidFixedCosts,
}: Props) {
    const spendPercent =
        todayLimit > 0
            ? (todaySpent / todayLimit) * 100
            : 0;

    const remaining = todayLimit - todaySpent;



    const [openFabMenu, setOpenFabMenu] =
        useState(false);

    return (
        <AppLayout status={status}>
            <div className="space-y-6 p-6 lg:p-8">
                {/* Saldo */}
                <div className="rounded-2xl bg-gradient-to-br from-primary to-primary/80 p-6 text-white shadow-lg lg:p-8">
                    <p className="text-sm font-medium text-white/70">
                        Saldo Saat Ini
                    </p>

                    <p className="mt-1 text-4xl font-bold tracking-tight">
                        {formatRp(currentBalance)}
                    </p>

                    <div className="mt-4 flex flex-wrap gap-4 text-sm">
                        <div className="rounded-xl bg-white/15 px-4 py-2">
                            <span className="text-white/60">
                                Batas Atas
                            </span>

                            <p className="font-semibold">
                                {formatRp(
                                    safetyCeiling,
                                )}
                            </p>
                        </div>

                        <div className="rounded-xl bg-white/15 px-4 py-2">
                            <span className="text-white/60">
                                Batas Bawah
                            </span>

                            <p className="font-semibold">
                                {formatRp(
                                    safetyFlooring,
                                )}
                            </p>
                        </div>

                        <div className="rounded-xl bg-white/15 px-4 py-2">
                            <span className="text-white/60">
                                Siklus
                            </span>

                            <p className="font-semibold capitalize">
                                {budgetCycle}
                            </p>
                        </div>
                    </div>
                </div>

                {/* Pengeluaran */}
                <div className="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                    <div className="flex items-center justify-between">
                        <div>
                            <p className="text-sm font-medium text-gray-500">
                                Pengeluaran Hari Ini
                            </p>

                            <p className="mt-1 text-3xl font-bold text-gray-900">
                                {formatRp(todaySpent)}
                            </p>
                        </div>

                        <div className="flex size-14 items-center justify-center rounded-xl bg-red-50 text-red-500">
                            <TrendingDown size={28} />
                        </div>
                    </div>

                    <div className="mt-4">
                        <div className="flex items-center justify-between text-sm">
                            <span className="text-gray-500">
                                {formatRp(todaySpent)} /{' '}
                                {formatRp(todayLimit)}
                            </span>

                            <span
                                className={`font-semibold ${
                                    remaining < 0
                                        ? 'text-red-500'
                                        : 'text-green-600'
                                }`}
                            >
                                {remaining >= 0
                                    ? `Sisa ${formatRp(
                                          remaining,
                                      )}`
                                    : `Lebih ${formatRp(
                                          Math.abs(
                                              remaining,
                                          ),
                                      )}`}
                            </span>
                        </div>

                        <div className="mt-2 h-3 w-full overflow-hidden rounded-full bg-gray-100">
                            <div
                                className={`h-full rounded-full transition-all ${
                                    spendPercent > 100
                                        ? 'bg-red-500'
                                        : spendPercent > 80
                                          ? 'bg-orange-500'
                                          : 'bg-secondary'
                                }`}
                                style={{
                                    width: `${Math.min(
                                        spendPercent,
                                        100,
                                    )}%`,
                                }}
                            />
                        </div>
                    </div>

                    <div className="mt-4 grid grid-cols-2 gap-4 text-sm">
                        <div className="rounded-xl bg-gray-50 p-3">
                            <span className="text-gray-500">
                                Batas Mentah
                            </span>

                            <p className="font-semibold text-gray-800">
                                {formatRp(
                                    rawTodayLimit,
                                )}
                            </p>
                        </div>

                        <div className="rounded-xl bg-gray-50 p-3">
                            <span className="text-gray-500">
                                Prediksi Besok
                            </span>

                            <p className="font-semibold text-gray-800">
                                {formatRp(
                                    tomorrowLimitPrediction,
                                )}
                            </p>
                        </div>
                    </div>
                </div>

                {/* Fixed Cost */}
                {unpaidFixedCosts.length > 0 && (
                    <div className="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                        <div className="flex items-center gap-2">
                            <CalendarClock
                                size={20}
                                className="text-orange-500"
                            />

                            <h2 className="text-lg font-semibold text-gray-800">
                                Biaya Tetap Belum
                                Dibayar
                            </h2>
                        </div>

                        <div className="mt-4 space-y-3">
                            {unpaidFixedCosts.map(
                                (cost, i) => (
                                    <div
                                        key={i}
                                        className="flex items-center justify-between rounded-xl border border-gray-100 p-4"
                                    >
                                        <div>
                                            <p className="font-semibold text-gray-800">
                                                {
                                                    cost.name
                                                }
                                            </p>

                                            <p className="text-xs text-gray-400 capitalize">
                                                {
                                                    cost.cycle
                                                }{' '}
                                                — tiap
                                                tanggal{' '}
                                                {
                                                    cost.due_value
                                                }
                                            </p>
                                        </div>

                                        <p className="font-bold text-orange-600">
                                            {formatRp(
                                                cost.amount,
                                            )}
                                        </p>
                                    </div>
                                ),
                            )}
                        </div>
                    </div>
                )}

                {/* Info */}
                <div className="grid grid-cols-2 gap-4">
                    <div className="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                        <div className="flex size-10 items-center justify-center rounded-lg bg-green-50 text-green-600">
                            <TrendingUp size={20} />
                        </div>

                        <p className="mt-3 text-sm text-gray-500">
                            Sisa Bulan Ini
                        </p>

                        <p className="text-xl font-bold text-gray-900">
                            {formatRp(
                                currentBalance -
                                    todaySpent,
                            )}
                        </p>
                    </div>

                    <div className="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                        <div className="flex size-10 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                            <Wallet size={20} />
                        </div>

                        <p className="mt-3 text-sm text-gray-500">
                            Prediksi Akhir Siklus
                        </p>

                        <p className="text-xl font-bold text-gray-900">
                            {formatRp(
                                currentBalance -
                                    todaySpent,
                            )}
                        </p>
                    </div>
                </div>



                {/* Floating Action Button */}
                <div className="fixed bottom-6 right-6 z-50">
                    {/* Menu */}
                    {openFabMenu && (
                        <div className="mb-4 flex flex-col items-end gap-4">

                            {/* Manual */}
                            <Link
                                href="/transaction/create"
                                className="group flex items-center gap-3 cursor-pointer"
                                onClick={() => {
                                    setOpenFabMenu(
                                        false,
                                    );
                                }}
                            >
                                <div className="rounded-full bg-white px-4 py-2 text-sm font-medium text-[#1E1E1E] shadow-[0_4px_12px_rgba(0,0,0,0.12)]">
                                    Tambah Manual
                                </div>

                                <div className="flex h-12 w-12 items-center justify-center rounded-full bg-white shadow-[0_4px_12px_rgba(0,0,0,0.12)] transition group-hover:scale-105">
                                    <Pencil
                                        size={18}
                                        className="text-[#2F5FBF]"
                                    />
                                </div>
                            </Link>
                        </div>
                    )}

                    {/* Main FAB */}
                    <button
                        onClick={() =>
                            setOpenFabMenu(
                                (prev) => !prev,
                            )
                        }
                        className={`flex h-14 w-14 items-center justify-center rounded-full bg-[#FF9800] text-white shadow-[0_10px_20px_rgba(255,152,0,0.35)] transition-all duration-300 ${
                            openFabMenu
                                ? 'rotate-45'
                                : 'rotate-0'
                        }`}
                    >
                        <Plus
                            size={28}
                            strokeWidth={2.5}
                        />
                    </button>
                </div>
            </div>
        </AppLayout>
    );
}
