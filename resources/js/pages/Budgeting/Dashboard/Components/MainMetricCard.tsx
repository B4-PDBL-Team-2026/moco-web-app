import React from 'react';

interface MainMetricCardProps {
    todaySpent: number;
    todayLimit: number;
    progressPercentage: number;
    rawPercentage: number;
    limitLabel: string;
    progressBarColor: string;
}

export default function MainMetricCard({
    todaySpent,
    todayLimit,
    progressPercentage,
    rawPercentage,
    limitLabel,
    progressBarColor,
}: MainMetricCardProps) {
    
    const formatRupiah = (value: number) => {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
        }).format(value).replace('IDR', 'Rp');
    };

    return (
        <div className="relative overflow-hidden rounded-3xl bg-blue-900 p-8 text-white shadow-xl transition-all duration-300 hover:scale-[1.005] hover:shadow-2xl">
            {/* Background design glow */}
            <div className="absolute -right-20 -top-20 h-48 w-48 rounded-full bg-white/10 blur-3xl" />

            <span className="text-sm font-semibold text-blue-100 uppercase tracking-wider opacity-95">
                Pengeluaran Hari Ini
            </span>
            
            <h2 className="mt-3 text-4xl font-black tracking-tight md:text-5xl">
                {formatRupiah(todaySpent)}
            </h2>

            {/* Progress Bar */}
            <div className="mt-10">
                <div className="mb-2 flex items-center justify-between text-xs font-bold text-blue-100">
                    <span>{limitLabel}</span>
                    <span className="rounded-full bg-white/15 px-2.5 py-0.5 backdrop-blur-md">
                        {rawPercentage}%
                    </span>
                </div>
                
                {/* Track */}
                <div className="h-3 w-full rounded-full bg-blue-950/45 p-[2px]">
                    {/* Fill */}
                    <div 
                        className={`h-full rounded-full transition-all duration-500 ease-out ${progressBarColor}`}
                        style={{ width: `${progressPercentage}%` }}
                    />
                </div>

                <div className="mt-2.5 flex justify-between text-xs font-semibold text-blue-200/90">
                    <span>Batas harian</span>
                    <span>{formatRupiah(todayLimit)}</span>
                </div>
            </div>
        </div>
    );
}
