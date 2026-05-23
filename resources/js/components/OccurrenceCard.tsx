import { CalendarDays, FileText, CheckCircle2, SkipForward } from 'lucide-react';
import type { OccurrenceTab } from '@/components/FilterTabs';

export interface Occurrence {
    id: number;
    name: string;
    amount: string;
    dueDate: string;
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

// Per-tab visual config

type CardTheme = {
    iconBg: string;
    iconColor: string;
    amountColor: string;
    dateColor: string;
    calendarColor: string;
};

const THEME: Record<OccurrenceTab, CardTheme> = {
    pending: {
        iconBg: 'bg-primary-light',
        iconColor: 'text-primary',
        amountColor: 'text-primary',
        dateColor: 'text-secondary',
        calendarColor: 'text-gray-400',
    },
    paid: {
        iconBg: 'bg-green-50',
        iconColor: 'text-green-600',
        amountColor: 'text-green-600',
        dateColor: 'text-green-600',
        calendarColor: 'text-green-400',
    },
    skipped: {
        iconBg: 'bg-gray-100',
        iconColor: 'text-gray-400',
        amountColor: 'text-gray-400',
        dateColor: 'text-gray-400',
        calendarColor: 'text-gray-300',
    },
};

function CategoryIcon({
                          icon,
                          bg,
                          color,
                      }: {
    icon?: string;
    bg: string;
    color: string;
}) {
    return (
        <div
            className={`flex h-12 w-12 shrink-0 items-center justify-center rounded-xl ${bg}`}
        >
            {icon ? (
                <span className="text-xl">{icon}</span>
            ) : (
                <FileText size={20} className={color} />
            )}
        </div>
    );
}

// Status badge (top-right of card)

function StatusBadge({ tab }: { tab: OccurrenceTab }) {
    if (tab === 'paid') {
        return (
            <span className="flex items-center gap-1 rounded-full bg-green-100 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-green-600">
                <CheckCircle2 size={10} />
                Lunas
            </span>
        );
    }
    if (tab === 'skipped') {
        return (
            <span className="flex items-center gap-1 rounded-full bg-gray-100 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-gray-400">
                <SkipForward size={10} />
                Dilewati
            </span>
        );
    }
    return null;
}

// Category badge (top-right of name row)

function CategoryBadge({
                           name,
                           tab,
                       }: {
    name: string;
    tab: OccurrenceTab;
}) {
    const styles: Record<OccurrenceTab, string> = {
        pending: 'bg-red-100 text-red-500',
        paid: 'bg-green-100 text-green-600',
        skipped: 'bg-gray-100 text-gray-400',
    };

    return (
        <span
            className={`shrink-0 rounded-full px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wide ${styles[tab]}`}
        >
            {name}
        </span>
    );
}

// Main component

export default function OccurrenceCard({
                                           occurrence,
                                           activeTab,
                                           onPay,
                                           onSkip,
                                           onCancelPayment,
                                           isLoading,
                                       }: OccurrenceCardProps) {
    const theme = THEME[activeTab];

    const isOverdue =
        activeTab === 'pending' &&
        (occurrence.status === 'overdue' ||
            new Date(occurrence.dueDate) < new Date());

    const dueDateColor =
        activeTab === 'pending'
            ? isOverdue
                ? 'text-secondary font-semibold'
                : 'text-gray-700 font-semibold'
            : `${theme.dateColor} font-semibold`;

    // Card border accent per tab
    const cardBorder: Record<OccurrenceTab, string> = {
        pending: 'border-gray-200',
        paid: 'border-green-100',
        skipped: 'border-gray-100',
    };

    return (
        <div
            className={`flex flex-col rounded-2xl border bg-white p-4 shadow-sm transition hover:shadow-md ${cardBorder[activeTab]}`}
        >
            {/* Top row */}
            <div className="flex items-start gap-3">
                <CategoryIcon
                    icon={occurrence.categoryIcon}
                    bg={theme.iconBg}
                    color={theme.iconColor}
                />

                <div className="min-w-0 flex-1">
                    <div className="flex items-start justify-between gap-2">
                        <p
                            className={`truncate text-sm font-semibold ${
                                activeTab === 'skipped'
                                    ? 'text-gray-400'
                                    : 'text-gray-800'
                            }`}
                        >
                            {occurrence.name}
                        </p>

                        <div className="flex shrink-0 flex-col items-end gap-1">
                            {occurrence.categoryName && (
                                <CategoryBadge
                                    name={occurrence.categoryName}
                                    tab={activeTab}
                                />
                            )}
                            <StatusBadge tab={activeTab} />
                        </div>
                    </div>

                    <p
                        className={`mt-0.5 text-base font-bold ${
                            activeTab === 'skipped'
                                ? 'line-through ' + theme.amountColor
                                : theme.amountColor
                        }`}
                    >
                        {formatRp(occurrence.amount)}
                    </p>
                </div>
            </div>

            {/* Due date */}
            <div
                className={`mt-3 flex items-center gap-1.5 text-xs ${theme.calendarColor}`}
            >
                <CalendarDays size={13} className="shrink-0" />
                <span className="text-gray-500">
                    Jatuh Tempo:{' '}
                    <span className={dueDateColor}>
                        {formatDueDate(occurrence.dueDate)}
                    </span>
                </span>
            </div>

            {/* Note */}
            {occurrence.note && (
                <p className="mt-2 text-xs italic text-gray-400">
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
                        className="flex-1 rounded-xl border border-red-200 py-2.5 text-sm font-semibold text-red-400 transition hover:bg-red-50 disabled:opacity-50"
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
