import { useState } from 'react';

interface DueDateCalendarModalProps {
    open: boolean;
    value: number | string | null;
    onConfirm: (day: number) => void;
    onCancel: () => void;
    cycleType: 'monthly' | 'weekly';
}

const DAYS = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];

export default function DueDateCalendarModal({
    open,
    value,
    onConfirm,
    onCancel,
    cycleType,
}: DueDateCalendarModalProps) {
    const initialSelected = value ? Number(value) : null;
    const [selected, setSelected] = useState<number | null>(initialSelected);

    if (!open) return null;

    const maxDays = cycleType === 'monthly' ? 31 : 7;
    const cells = Array.from({ length: maxDays }, (_, i) => i + 1);

    const handleConfirm = () => {
        if (!selected) return;
        onConfirm(selected);
    };

    return (
        <div className="absolute inset-0 z-10 flex flex-col rounded-2xl bg-white">
            {/* Header */}
            <div className="p-6 pb-2">
                <h2 className="text-lg font-bold text-gray-800">
                    Pilih Hari Jatuh Tempo
                </h2>
                <p className="mt-1 text-sm text-gray-500">
                    {cycleType === 'monthly'
                        ? 'Pilih tanggal tagihan ini muncul setiap bulannya.'
                        : 'Pilih hari ke berapa tagihan ini muncul setiap minggunya.'}
                </p>
            </div>

            {/* Grid Container */}
            <div className="mx-6 flex-1 overflow-auto rounded-2xl border border-gray-100 bg-white p-4 shadow-sm">
                <div className="mb-2 grid grid-cols-7 border-b border-gray-50 pb-2">
                    {DAYS.map((d) => (
                        <div
                            key={d}
                            className="text-center text-[11px] font-semibold text-gray-400"
                        >
                            {d}
                        </div>
                    ))}
                </div>

                {/* Grid layout */}
                <div className="grid grid-cols-7 gap-x-1 gap-y-2">
                    {cells.map((day) => {
                        const isSelected = day === selected;

                        return (
                            <button
                                key={day}
                                onClick={() => setSelected(day)}
                                className={`relative mx-auto flex h-9 w-9 items-center justify-center rounded-lg text-sm font-medium transition ${
                                    isSelected
                                        ? 'bg-gray-800 text-white shadow-md'
                                        : 'border border-transparent bg-white text-gray-700 hover:border-gray-100 hover:bg-primary-light/50'
                                }`}
                            >
                                {day}
                            </button>
                        );
                    })}
                </div>
            </div>

            {/* Actions */}
            <div className="flex gap-3 p-6 pt-4">
                <button
                    onClick={onCancel}
                    className="flex-1 rounded-xl border border-primary py-3 text-sm font-semibold text-primary transition hover:bg-primary-light"
                >
                    Batal
                </button>
                <button
                    onClick={handleConfirm}
                    disabled={!selected}
                    className="flex-1 rounded-xl bg-secondary py-3 text-sm font-bold text-white transition hover:bg-secondary/90 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    Pilih
                </button>
            </div>
        </div>
    );
}
