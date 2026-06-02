import { ArrowLeft, Check, Sparkles } from 'lucide-react';
import { getIconColorTheme } from '@/utils/categoryColors';
import { CategoryPhosphorIcon } from '@/utils/phosphorIconMap';

export interface Category {
    id: number;
    name: string;
    icon: string | null;
    type: string;
}

interface CategoryPickerModalProps {
    open: boolean;
    categories: Category[];
    selectedId: number | null;
    onSelect: (category: Category) => void;
    onConfirm: () => void;
    onBack: () => void;
}

export default function CategoryPickerModal({
    open,
    categories,
    selectedId,
    onSelect,
    onConfirm,
    onBack,
}: CategoryPickerModalProps) {
    if (!open) return null;

    return (
        <div className="absolute inset-0 z-10 flex flex-col rounded-3xl bg-white shadow-2xl animate-scale-up">
            {/* Header */}
            <div className="flex items-center gap-3 p-6 pb-4 border-b border-gray-100 shrink-0">
                <button
                    onClick={onBack}
                    className="flex h-9 w-9 items-center justify-center rounded-full border border-gray-100 bg-white text-gray-500 shadow-sm transition hover:bg-gray-50 hover:text-primary active:scale-95"
                >
                    <ArrowLeft size={16} strokeWidth={2.5} />
                </button>
                <div>
                    <h2 className="text-base font-black text-gray-900">
                        Pilih Kategori
                    </h2>
                    <p className="text-xs font-semibold text-gray-400">
                        Tentukan kategori untuk transaksi biaya tetap Anda.
                    </p>
                </div>
            </div>

            {/* Content */}
            <div className="flex-1 overflow-y-auto px-6 py-5">
                <div className="mb-4 flex items-center justify-between">
                    <h3 className="text-xs font-black uppercase tracking-wider text-gray-400 flex items-center gap-1.5">
                        <Sparkles size={11} className="text-primary fill-current" />
                        Semua Kategori
                    </h3>
                    <span className="text-[10px] font-bold text-gray-400">Total {categories.length}</span>
                </div>

                <div className="grid grid-cols-3 gap-3">
                    {categories.map((cat) => {
                        const isSelected = cat.id === selectedId;
                        const colors = getIconColorTheme(cat.icon);

                        return (
                            <button
                                key={cat.id}
                                onClick={() => onSelect(cat)}
                                className={`group flex flex-col items-center gap-2 rounded-2xl p-3 border transition-all duration-200 cursor-pointer shadow-sm hover:scale-102 ${
                                    isSelected
                                        ? 'border-primary bg-primary-light/30 shadow-md scale-102 font-bold ring-2 ring-primary ring-offset-1'
                                        : 'border-gray-100 bg-gray-50/50 hover:bg-gray-50'
                                }`}
                            >
                                {/* Icon container */}
                                <div
                                    className={`flex h-11 w-11 items-center justify-center rounded-xl transition-all duration-200 ${
                                        isSelected
                                            ? `bg-gradient-to-br ${colors.gradient} text-white shadow-sm`
                                            : `${colors.bgLight} ${colors.text} shadow-sm group-hover:scale-105`
                                    }`}
                                >
                                    <CategoryPhosphorIcon
                                        iconName={cat.icon}
                                        size={20}
                                        weight={isSelected ? 'fill' : 'regular'}
                                        className={isSelected ? 'text-white' : colors.text}
                                    />
                                </div>

                                <span className={`text-center text-[10px] font-black leading-tight truncate w-full ${
                                    isSelected ? 'text-primary' : 'text-gray-600'
                                }`}>
                                    {cat.name}
                                </span>

                                {isSelected && (
                                    <div className="flex items-center gap-0.5 text-[9px] font-extrabold text-primary animate-scale-up">
                                        <Check size={10} strokeWidth={3} />
                                        <span>Terpilih</span>
                                    </div>
                                )}
                            </button>
                        );
                    })}
                </div>
            </div>

            {/* Confirm button */}
            <div className="p-6 pt-4 border-t border-gray-100 bg-gray-50/50 shrink-0">
                <button
                    onClick={onConfirm}
                    disabled={selectedId === null}
                    className="w-full rounded-2xl bg-secondary py-3.5 text-sm font-extrabold text-white shadow-md shadow-secondary/20 transition-all duration-200 hover:bg-secondary/95 hover:shadow-lg disabled:opacity-50 disabled:pointer-events-none active:scale-98 cursor-pointer"
                >
                    Konfirmasi Kategori
                </button>
            </div>
        </div>
    );
}
