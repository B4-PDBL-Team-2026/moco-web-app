import React from 'react';

interface SideBannerCardProps {
    variant?: 'blue' | 'red' | 'pink';
    title?: string;
    value: string;
}

export default function SideBannerCard({
    variant = 'blue',
    title,
    value,
}: SideBannerCardProps) {
    if (variant === 'red') {
        return (
            <div className="flex h-32 flex-col justify-center rounded-2xl bg-gradient-to-br from-red-500 to-red-600 p-6 text-white shadow-md transition-all duration-300 hover:scale-[1.005] hover:shadow-lg">
                <h3 className="text-2xl font-black tracking-wider uppercase text-center">
                    {value}
                </h3>
            </div>
        );
    }

    if (variant === 'pink') {
        return (
            <div className="flex h-32 flex-col justify-between rounded-2xl bg-red-50/70 border border-red-200 p-6 text-red-600 shadow-sm transition-all duration-300 hover:scale-[1.005] hover:shadow-md">
                {title && (
                    <span className="text-xs font-bold text-red-400 uppercase tracking-wider">
                        {title}
                    </span>
                )}
                <h3 className="text-3xl font-black tracking-tight">{value}</h3>
            </div>
        );
    }

    return (
        <div className="flex h-32 flex-col justify-between rounded-2xl bg-blue-900 p-6 text-white shadow-md transition-all duration-300 hover:scale-[1.005] hover:shadow-lg">
            {title && (
                <span className="text-xs font-bold text-blue-200 uppercase tracking-wider">
                    {title}
                </span>
            )}
            <h3 className="text-3xl font-black tracking-tight">{value}</h3>
        </div>
    );
}
