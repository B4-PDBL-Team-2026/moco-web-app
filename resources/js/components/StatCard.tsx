import React from 'react';

interface StatCardProps {
    title: string;
    value: string | number;
    change?: {
        value: string;
        trend: 'up' | 'down' | 'neutral';
    };
    icon: React.ReactNode;
    accent?: 'blue' | 'orange' | 'green' | 'purple';
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
};

export default function StatCard({
    title,
    value,
    change,
    icon,
    accent = 'blue',
}: StatCardProps) {
    const config = ACCENT_CONFIGS[accent];

    return (
        <div className="flex items-center justify-between rounded-2xl border border-gray-100 bg-white p-6 shadow-sm h-full">
            <div className="space-y-2 lg:space-y-6">
                <p className="text-sm font-medium text-gray-500">{title}</p>
                <div className="flex items-baseline gap-2 lg:flex-col">
                    <span className="text-3xl font-bold text-gray-900 leading-none">
                        {value}
                    </span>
                    {change && (
                        <span
                            className={`text-xs font-semibold ${
                                change.trend === 'up'
                                    ? 'text-green-600'
                                    : change.trend === 'down'
                                      ? 'text-red-600'
                                      : 'text-gray-500'
                            }`}
                        >
                            {change.value}
                        </span>
                    )}
                </div>
            </div>
            <div
                className={`flex size-12 lg:size-16 shrink-0 items-center justify-center rounded-xl ${config.bg}`}
            >
                {icon}
            </div>
        </div>
    );
}
