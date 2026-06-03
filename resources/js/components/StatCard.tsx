import React from 'react';

interface StatCardProps {
    title: string;
    value: string | number;
    change?: {
        value: string;
        trend: 'up' | 'down' | 'neutral';
    };
    description?: string;
    footer?: React.ReactNode;
    icon: React.ReactNode;
    accent?: 'blue' | 'orange' | 'green' | 'purple' | 'emerald' | 'amber';
    layout?: 'vertical' | 'horizontal';
}

const ACCENT_CONFIGS = {
    blue: {
        bg: 'bg-primary-light text-primary',
        border: 'border-primary/10',
    },
    orange: {
        bg: 'bg-secondary-light text-secondary',
        border: 'border-secondary/10',
    },
    green: {
        bg: 'bg-green-50 text-green-600',
        border: 'border-green-100',
    },
    purple: {
        bg: 'bg-purple-50 text-purple-600',
        border: 'border-purple-100',
    },
    emerald: {
        bg: 'bg-emerald-50 text-emerald-600',
        border: 'border-emerald-100',
    },
    amber: {
        bg: 'bg-amber-50 text-amber-500',
        border: 'border-amber-100',
    },
};

export default function StatCard({
    title,
    value,
    change,
    description,
    footer,
    icon,
    accent = 'blue',
    layout = 'vertical',
}: StatCardProps) {
    const config = ACCENT_CONFIGS[accent] || ACCENT_CONFIGS.blue;

    // Shared typography and visual styles
    const titleClass = 'text-sm font-bold text-gray-400';
    const valueClass = 'text-3xl font-black text-gray-950 leading-none';
    const trendClass = (trend: 'up' | 'down' | 'neutral') => 
        `text-xs font-bold ${
            trend === 'up'
                ? 'text-green-600'
                : trend === 'down'
                  ? 'text-red-600'
                  : 'text-gray-500'
        }`;
    const descClass = 'text-xs font-semibold text-gray-400';

    if (layout === 'horizontal') {
        return (
            <div className="flex items-center justify-between rounded-2xl border border-gray-100 bg-white p-6 shadow-sm h-full">
                <div className="space-y-2 lg:space-y-6">
                    <p className={titleClass}>{title}</p>
                    <div className="flex items-baseline gap-2 lg:flex-col">
                        <span className={valueClass}>
                            {value}
                        </span>
                        {change && (
                            <span className={trendClass(change.trend)}>
                                {change.value}
                            </span>
                        )}
                        {description && (
                            <span className={descClass}>
                                {description}
                            </span>
                        )}
                    </div>
                </div>
                <div
                    className={`flex size-12 lg:size-16 shrink-0 items-center justify-center rounded-xl border ${config.bg} ${config.border}`}
                >
                    {icon}
                </div>
            </div>
        );
    }

    // Default vertical style matching feedback style
    return (
        <div className="flex flex-col justify-between rounded-2xl border border-gray-100 bg-white p-6 shadow-sm h-full">
            <div>
                <span className={`inline-flex rounded-xl p-3 border ${config.bg} ${config.border}`}>
                    {icon}
                </span>
                <h3 className={`mt-4 ${titleClass}`}>{title}</h3>
                <p className={`mt-2 ${valueClass}`}>{value}</p>
            </div>
            {(change || description || footer) && (
                <div className="mt-4">
                    {footer ? (
                        footer
                    ) : change ? (
                        <span className={trendClass(change.trend)}>
                            {change.value}
                        </span>
                    ) : (
                        <p className={descClass}>
                            {description}
                        </p>
                    )}
                </div>
            )}
        </div>
    );
}
