import { CalendarDays, FileText } from 'lucide-react';
import type { OccurrenceTab } from '@/components/FilterTabs';

export interface Occurrence {
    id: number;
    name: string;
    amount: string;
    dueDate: string; // ISO date string e.g. "2026-04-30"
    status: 'pending' | 'paid' | 'overdue' | 'skipped' | 'void';
    categoryName?: string;
    categoryIcon?: string;
    note?: string;
}

interface OccurrenceCardProps {
    occurrence: Occurrence;
    activeTab: OccurrenceTab;
    onPay: (id: number) => void;
    onSkip: (id: number) => void;
    onCancelPayment: (id: number) => void;
    isLoading?: boolean;
}

function formatRp(value: string | number) {
    const num = Number(value);
    if (isNaN(num)) return 'Rp0';
    return (
        'Rp' +
        num.toLocaleString('id-ID', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
        })
    );
}

function formatDueDate(iso: string) {
    const date = new Date(iso);
    const day = date.getDate();
    const month = date.toLocaleDateString('id-ID', { month: 'short' });
    return `${day} ${month}`;
}

function CategoryIcon({ icon, name }: { icon?: string; name?: string }) {
    return (
        <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-primary-light">
            {icon ? (
                <span className="text-xl">{icon}</span>
            ) : (
                <FileText size={20} className="text-primary" />
            )}
        </div>
    );
}

export default function OccurrenceCard({
    occurrence,
    activeTab,
    onPay,
    onSkip,
    onCancelPayment,
    isLoading,
}: OccurrenceCardProps) {
    const isOverdue =
        occurrence.status === 'overdue' ||
        (occurrence.status === 'pending' &&
            new Date(occurrence.dueDate) < new Date());

    return (
        <div className="flex flex-col rounded-2xl border border-gray-200 bg-white p-4 shadow-sm transition hover:shadow-md">
            {/* Top row */}
            <div className="flex items-start gap-3">
                <CategoryIcon
                    icon={occurrence.categoryIcon}
                    name={occurrence.categoryName}
                />

                <div className="min-w-0 flex-1">
                    <div className="flex items-start justify-between gap-2">
                        <p className="truncate text-sm font-semibold text-gray-800">
                            {occurrence.name}
                        </p>
                        {occurrence.categoryName && (
                            <span className="shrink-0 rounded-full bg-red-100 px-2.5 py-0.5 text-[10px] font-bold tracking-wide text-red-500 uppercase">
                                {occurrence.categoryName}
                            </span>
                        )}
                    </div>
                    <p className="mt-0.5 text-base font-bold text-primary">
                        {formatRp(occurrence.amount)}
                    </p>
                </div>
            </div>

            {/* Due date */}
            <div className="mt-3 flex items-center gap-1.5 text-xs text-gray-500">
                <CalendarDays size={13} className="shrink-0" />
                <span>
                    Jatuh Tempo:{' '}
                    <span
                        className={`font-semibold ${
                            isOverdue ? 'text-secondary' : 'text-gray-700'
                        }`}
                    >
                        {formatDueDate(occurrence.dueDate)}
                    </span>
                </span>
            </div>

            {/* Note */}
            {occurrence.note && (
                <p className="mt-2 text-xs text-gray-400 italic">
                    {occurrence.note}
                </p>
            )}

            {/* Divider */}
            <div className="my-3 border-t border-gray-100" />

            {/* Actions */}
            <div className="flex gap-2">
                {activeTab === 'pending' && (
                    <>
                        <button
                            onClick={() => onSkip(occurrence.id)}
                            disabled={isLoading}
                            className="flex-1 rounded-xl border border-gray-200 py-2.5 text-sm font-semibold text-gray-600 transition hover:bg-gray-50 disabled:opacity-50"
                        >
                            Lewati
                        </button>
                        <button
                            onClick={() => onPay(occurrence.id)}
                            disabled={isLoading}
                            className="flex-1 rounded-xl bg-secondary py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-secondary/90 disabled:opacity-50"
                        >
                            Bayar Sekarang
                        </button>
                    </>
                )}

                {activeTab === 'paid' && (
                    <button
                        onClick={() => onCancelPayment(occurrence.id)}
                        disabled={isLoading}
                        className="flex-1 rounded-xl border border-red-200 py-2.5 text-sm font-semibold text-red-500 transition hover:bg-red-50 disabled:opacity-50"
                    >
                        Batalkan Pembayaran
                    </button>
                )}

                {activeTab === 'skipped' && (
                    <button
                        onClick={() => onPay(occurrence.id)}
                        disabled={isLoading}
                        className="flex-1 rounded-xl bg-primary py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-primary/90 disabled:opacity-50"
                    >
                        Bayar Sekarang
                    </button>
                )}
            </div>
        </div>
    );
}
