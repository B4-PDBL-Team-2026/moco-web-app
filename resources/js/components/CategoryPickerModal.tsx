import { ArrowLeft, Check } from 'lucide-react';
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
        <div className="absolute inset-0 z-10 flex flex-col rounded-2xl bg-white">
            {/* Header */}
            <div className="flex items-center gap-3 p-6 pb-4">
                <button
                    onClick={onBack}
                    className="flex h-8 w-8 items-center justify-center rounded-full hover:bg-gray-100"
                >
                    <ArrowLeft size={18} className="text-primary" />
                </button>
                <h2 className="text-lg font-bold text-primary">
                    Edit Biaya Tetap
                </h2>
            </div>

            {/* Content */}
            <div className="flex-1 overflow-y-auto px-6">
                <h3 className="mb-5 text-base font-bold text-gray-800">
                    Semua Kategori
                </h3>

                <div className="grid grid-cols-3 gap-3">
                    {categories.map((cat) => {
                        const isSelected = cat.id === selectedId;
                        return (
                            <button
                                key={cat.id}
                                onClick={() => onSelect(cat)}
                                className={`flex flex-col items-center gap-2 rounded-2xl p-4 transition ${
                                    isSelected
                                        ? 'bg-primary-light ring-2 ring-primary'
                                        : 'bg-gray-50 hover:bg-primary-light/60'
                                }`}
                            >
                                <div
                                    className={`flex h-12 w-12 items-center justify-center rounded-xl ${
                                        isSelected
                                            ? 'bg-primary'
                                            : 'bg-primary-light'
                                    }`}
                                >
                                    <CategoryPhosphorIcon
                                        iconName={cat.icon}
                                        size={24}
                                        weight="regular"
                                        className={
                                            isSelected
                                                ? 'text-white'
                                                : 'text-primary'
                                        }
                                    />
                                </div>
                                <span className="text-center text-[11px] leading-tight text-gray-600">
                                    {cat.name}
                                </span>
                                {isSelected && (
                                    <Check size={14} className="text-primary" />
                                )}
                            </button>
                        );
                    })}
                </div>
            </div>

            {/* Confirm button */}
            <div className="p-6 pt-4">
                <button
                    onClick={onConfirm}
                    disabled={selectedId === null}
                    className="w-full rounded-xl bg-secondary py-3.5 text-sm font-bold text-white shadow-sm transition hover:bg-secondary/90 disabled:opacity-50"
                >
                    Konfirmasi Kategori
                </button>
            </div>
        </div>
    );
}
