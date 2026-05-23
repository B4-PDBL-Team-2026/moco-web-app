export type OccurrenceTab = 'pending' | 'paid' | 'skipped';

interface FilterTabsProps {
    active: OccurrenceTab;
    onChange: (tab: OccurrenceTab) => void;
    counts?: {
        pending?: number;
        paid?: number;
        skipped?: number;
    };
}

const TABS: { key: OccurrenceTab; label: string }[] = [
    { key: 'pending', label: 'Belum Dibayar' },
    { key: 'paid', label: 'Sudah Dibayar' },
    { key: 'skipped', label: 'Dilewati' },
];

export default function FilterTabs({
    active,
    onChange,
    counts,
}: FilterTabsProps) {
    return (
        <div className="flex flex-wrap gap-2">
            {TABS.map((tab) => {
                const isActive = tab.key === active;
                const count = counts?.[tab.key];

                return (
                    <button
                        key={tab.key}
                        onClick={() => onChange(tab.key)}
                        className={`flex items-center gap-1.5 rounded-full px-5 py-2 text-sm font-medium transition-all duration-150 ${
                            isActive
                                ? 'bg-primary text-white shadow-sm'
                                : 'border border-gray-200 bg-white text-gray-500 hover:border-primary hover:text-primary'
                        }`}
                    >
                        {tab.label}
                        {count !== undefined && count > 0 && (
                            <span
                                className={`rounded-full px-1.5 py-0.5 text-[10px] leading-none font-bold ${
                                    isActive
                                        ? 'bg-white/20 text-white'
                                        : 'bg-primary-light text-primary'
                                }`}
                            >
                                {count}
                            </span>
                        )}
                    </button>
                );
            })}
        </div>
    );
}
