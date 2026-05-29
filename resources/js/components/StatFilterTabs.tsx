interface StatFilterTabsProps<T extends string> {
    active: T;
    onChange: (tab: T) => void;
    options: { key: T; label: string }[];
}

export default function StatFilterTabs<T extends string>({
    active,
    onChange,
    options,
}: StatFilterTabsProps<T>) {
    return (
        <div className="flex flex-wrap gap-2">
            {options.map((option) => {
                const isActive = option.key === active;

                return (
                    <button
                        key={option.key}
                        onClick={() => onChange(option.key)}
                        className={`flex items-center gap-1.5 rounded-full px-5 py-2 text-sm font-medium transition-all duration-150 cursor-pointer ${
                            isActive
                                ? 'bg-primary text-white shadow-sm'
                                : 'border border-gray-200 bg-white text-gray-500 hover:border-primary hover:text-primary'
                        }`}
                    >
                        {option.label}
                    </button>
                );
            })}
        </div>
    );
}
