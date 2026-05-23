import { ChevronDown } from 'lucide-react';
import React from 'react';

interface MonthYearPickerProps {
    month: number; // 1-12
    year: number;
    onChange: (month: number, year: number) => void;
    minYear?: number;
    maxYear?: number;
}

const MONTHS = [
    'Januari',
    'Februari',
    'Maret',
    'April',
    'Mei',
    'Juni',
    'Juli',
    'Agustus',
    'September',
    'Oktober',
    'November',
    'Desember',
];

function SelectWrapper({
    value,
    onChange,
    children,
}: {
    value: string | number;
    onChange: (val: string) => void;
    children: React.ReactNode;
}) {
    return (
        <div className="relative">
            <select
                value={value}
                onChange={(e) => onChange(e.target.value)}
                className="cursor-pointer appearance-none rounded-xl border border-gray-200 bg-white py-2.5 pr-9 pl-4 text-sm font-medium text-gray-700 shadow-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
            >
                {children}
            </select>
            <ChevronDown
                size={16}
                className="pointer-events-none absolute top-1/2 right-3 -translate-y-1/2 text-gray-400"
            />
        </div>
    );
}

export default function MonthYearPicker({
    month,
    year,
    onChange,
    minYear = 2020,
    maxYear = new Date().getFullYear() + 1,
}: MonthYearPickerProps) {
    const years = Array.from(
        { length: maxYear - minYear + 1 },
        (_, i) => minYear + i,
    );

    return (
        <div className="flex items-center gap-2">
            <SelectWrapper
                value={month}
                onChange={(val) => onChange(Number(val), year)}
            >
                {MONTHS.map((name, idx) => (
                    <option key={idx + 1} value={idx + 1}>
                        {name}
                    </option>
                ))}
            </SelectWrapper>

            <SelectWrapper
                value={year}
                onChange={(val) => onChange(month, Number(val))}
            >
                {years.map((y) => (
                    <option key={y} value={y}>
                        {y}
                    </option>
                ))}
            </SelectWrapper>
        </div>
    );
}
