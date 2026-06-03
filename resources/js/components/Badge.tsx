import React from 'react';

export type BadgeColor = 'primary' | 'emerald' | 'red' | 'blue' | 'amber' | 'gray';

interface BadgeProps {
    children: React.ReactNode;
    color?: BadgeColor;
    icon?: React.ReactNode;
    dot?: boolean;
    dotPulse?: boolean;
    className?: string;
}

export default function Badge({
    children,
    color = 'gray',
    icon,
    dot = false,
    dotPulse = false,
    className = '',
}: BadgeProps) {
    const colorStyles: Record<BadgeColor, string> = {
        primary: 'bg-primary-light border-primary/20 text-primary',
        emerald: 'bg-emerald-50 border-emerald-200 text-emerald-700',
        red: 'bg-red-50 border-red-200 text-red-700',
        blue: 'bg-blue-50 border-blue-200 text-blue-700',
        amber: 'bg-amber-50 border-amber-200 text-amber-700',
        gray: 'bg-gray-50 border-gray-200 text-gray-400',
    };

    const dotColors: Record<BadgeColor, string> = {
        primary: 'bg-primary',
        emerald: 'bg-emerald-500',
        red: 'bg-red-500',
        blue: 'bg-blue-500',
        amber: 'bg-amber-500',
        gray: 'bg-gray-400',
    };

    return (
        <span
            className={`inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-bold ${colorStyles[color]} ${className}`}
        >
            {icon && <span className="shrink-0">{icon}</span>}
            {dot && (
                <span
                    className={`h-1.5 w-1.5 rounded-full shrink-0 ${dotColors[color]} ${
                        dotPulse ? 'animate-pulse' : ''
                    }`}
                />
            )}
            <span>{children}</span>
        </span>
    );
}
