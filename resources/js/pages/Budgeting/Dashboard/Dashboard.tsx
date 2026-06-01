import React, { useState } from 'react';
import AppLayout from '@/layouts/AppLayout';
import { Head, Link, usePage } from '@inertiajs/react';
import { 
    Eye, 
    EyeOff, 
    Plus, 
    X, 
    Mic, 
    Scan, 
    PenLine 
} from 'lucide-react';
import MainMetricCard from './Components/MainMetricCard';
import RecentTransactions from './Components/RecentTransactions';
import SideBannerCard from './Components/SideBannerCard';
import { calculateDashboardState } from './utils/dashboardState';
import { DashboardSummary, TransactionItem } from './types';

interface DashboardProps {
    summary: DashboardSummary;
    recentTransactions: TransactionItem[];
    budgetStatus: 'stabil' | 'defisit' | 'kritis' | 'surplus';
}

export default function Dashboard({ summary, recentTransactions, budgetStatus }: DashboardProps) {
    const { auth } = usePage().props as any;
    const userName = auth.user.name;

    // State to toggle actual balance visibility
    const [showBalance, setShowBalance] = useState(false);
    
    // State to toggle Floating Action Button (FAB) quick menu
    const [isFabOpen, setIsFabOpen] = useState(false);

    // Compute the dynamic states based on props
    const state = calculateDashboardState(summary, userName, budgetStatus);

    const formatRupiah = (value: number) => {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
        }).format(value).replace('IDR', 'Rp');
    };

    return (
        <AppLayout status={budgetStatus}>
            <Head title="Dashboard - Moco" />
            
            <div className="relative mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                
                {/* Greeting Header Section */}
                <div className="mb-8">
                    <h1 className="text-3xl font-black text-gray-900 tracking-tight">
                        {state.greetingTitle}
                    </h1>
                    <p className="mt-1 text-sm font-semibold text-gray-500">
                        {state.greetingSubtitle}
                    </p>
                </div>

                {/* Dashboard Grid System */}
                <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    
                    {/* LEFT & CENTER PANEL: Main Balance, Metrics and History */}
                    <div className="space-y-6 lg:col-span-2">
                        
                        {/* Real-time Balance Display Card */}
                        <div className="flex items-center justify-between rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                            <div>
                                <span className="text-xs font-bold text-gray-400 uppercase tracking-wider">
                                    Saldo sebenarnya
                                </span>
                                <div className="mt-1.5 text-2xl font-black text-gray-900 tracking-tight">
                                    {showBalance ? formatRupiah(summary.currentBalance) : 'Rp •••••••'}
                                </div>
                            </div>
                            <button 
                                onClick={() => setShowBalance(!showBalance)}
                                className="rounded-full p-2.5 text-gray-400 hover:bg-gray-50 hover:text-gray-600 transition duration-200"
                                aria-label={showBalance ? "Sembunyikan saldo" : "Tampilkan saldo"}
                            >
                                {showBalance ? <EyeOff size={20} /> : <Eye size={20} />}
                            </button>
                        </div>

                        {/* Large Metric Progress Card OR Serious Warning Card */}
                        {state.isSeriousWarning ? (
                            <div className="relative overflow-hidden rounded-3xl bg-gradient-to-br from-blue-700 to-blue-900 p-8 text-white shadow-xl transition-all duration-300 hover:scale-[1.005]">
                                <h2 className="text-4xl font-black tracking-tight mb-4">Serius!</h2>
                                <p className="text-lg font-bold text-blue-100/90 leading-relaxed mb-6">
                                    Saldo Anda habis dan hutang Anda masih ada. Apakah Anda lari dari tanggung jawab?
                                </p>
                                <div className="text-sm font-black text-red-300 uppercase tracking-widest">
                                    Tidak ada transaksi lebih lanjut!
                                </div>
                            </div>
                        ) : (
                            <MainMetricCard 
                                todaySpent={summary.todaySpent}
                                todayLimit={summary.todayLimit}
                                progressPercentage={state.progressPercentage}
                                rawPercentage={state.rawPercentage}
                                limitLabel={state.mainCardLimitLabel}
                                progressBarColor={state.progressBarColor}
                            />
                        )}

                        {/* Recent Transactions List */}
                        <RecentTransactions transactions={recentTransactions} />

                    </div>

                    {/* RIGHT PANEL: Dynamic Safety Badges and Projections */}
                    <div className="space-y-6">
                        
                        {/* Dynamic Budget Safety banner (Mockup 3 vs Mockup 1/2 vs Defisit) */}
                        {budgetStatus === 'defisit' ? (
                            <SideBannerCard 
                                variant="pink" 
                                title="Total Defisit Saat Ini"
                                value={formatRupiah(summary.currentBalance)} 
                            />
                        ) : state.isOverbudget ? (
                            <SideBannerCard 
                                variant="red" 
                                value="Overbudget!" 
                            />
                        ) : (
                            <SideBannerCard 
                                variant="blue" 
                                title="Saldo aman untuk" 
                                value={`${state.safetyDays} Hari`} 
                            />
                        )}

                        {/* Dynamic Savings or Allowance Prediction Card based on status */}
                        {budgetStatus === 'defisit' ? (
                            state.isSeriousWarning ? (
                                <SideBannerCard 
                                    variant="pink" 
                                    title="Saldo rill" 
                                    value="Rp 0" 
                                />
                            ) : null
                        ) : (
                            <SideBannerCard 
                                variant="blue" 
                                title={
                                    budgetStatus === 'surplus' 
                                        ? (state.isOverbudget ? 'Estimasi Tabungan' : 'Proyeksi Tabungan')
                                        : budgetStatus === 'kritis'
                                            ? 'Sisa Jatah Ekstrem'
                                            : 'Prediksi Jatah Besok'
                                } 
                                value={formatRupiah(summary.tomorrowLimitPrediction)} 
                            />
                        )}

                    </div>


                </div>

                {/* PREMIUM FLOATING ACTION BUTTON (FAB) MENU */}
                <div className="fixed bottom-6 right-6 z-30 flex flex-col items-end gap-3">
                    {/* Collapsible Action Items */}
                    {isFabOpen && (
                        <div className="flex flex-col items-end gap-3 transition-all duration-300 ease-in-out">
                            {/* Voice input option */}
                            <button className="flex items-center gap-3 rounded-full bg-white px-4 py-2 text-sm font-extrabold text-gray-700 shadow-md transition hover:bg-gray-50 hover:scale-105">
                                <span>Voice</span>
                                <div className="flex h-9 w-9 items-center justify-center rounded-full bg-blue-50 text-blue-600">
                                    <Mic size={18} />
                                </div>
                            </button>

                            {/* Scan receipt option */}
                            <button className="flex items-center gap-3 rounded-full bg-white px-4 py-2 text-sm font-extrabold text-gray-700 shadow-md transition hover:bg-gray-50 hover:scale-105">
                                <span>Scan Struk</span>
                                <div className="flex h-9 w-9 items-center justify-center rounded-full bg-blue-50 text-blue-600">
                                    <Scan size={18} />
                                </div>
                            </button>

                            {/* Manual input option */}
                            <Link 
                                href="/transactions/create"
                                className="flex items-center gap-3 rounded-full bg-white px-4 py-2 text-sm font-extrabold text-gray-700 shadow-md transition hover:bg-gray-50 hover:scale-105"
                            >
                                <span>Tambah Manual</span>
                                <div className="flex h-9 w-9 items-center justify-center rounded-full bg-blue-50 text-blue-600">
                                    <PenLine size={18} />
                                </div>
                            </Link>
                        </div>
                    )}

                    {/* Primary Toggle Orange FAB */}
                    <button 
                        onClick={() => setIsFabOpen(!isFabOpen)}
                        className="flex h-14 w-14 items-center justify-center rounded-full bg-amber-500 text-white shadow-lg shadow-amber-500/20 transition-all duration-300 hover:bg-amber-600 hover:scale-110 active:scale-95"
                        aria-label="Aksi Cepat"
                    >
                        {isFabOpen ? <X size={24} /> : <Plus size={24} />}
                    </button>
                </div>

            </div>
        </AppLayout>
    );
}
