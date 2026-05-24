import {
    BowlFoodIcon,
    TaxiIcon,
    InvoiceIcon,
    StudentIcon,
    FilmReelIcon,
    ShoppingBagIcon,
    HeartbeatIcon,
    HandHeartIcon,
    TagIcon,
    MoneyIcon,
    WalletIcon,
    QuestionIcon,
} from '@phosphor-icons/react';
import React, { useState } from 'react';
import ProgressBar from '@/components/ProgressBar';

const CYCLE_OPTIONS = [
    { label: 'Bulanan', value: 'monthly' },
    { label: 'Mingguan', value: 'weekly' },
];

const phosphorMap: Record<string, React.ElementType> = {
    bowl_food: BowlFoodIcon,
    taxi: TaxiIcon,
    invoice: InvoiceIcon,
    student: StudentIcon,
    film_reel: FilmReelIcon,
    shopping_bag: ShoppingBagIcon,
    heart_beat: HeartbeatIcon,
    hand_heart: HandHeartIcon,
    tag: TagIcon,
    money: MoneyIcon,
    wallet: WalletIcon,
};

const CYCLE_LABEL: Record<string, string> = {
    monthly: 'Bulanan',
    weekly: 'Mingguan',
};

function FieldError({ message }: { message?: string }) {
    if (!message) return null;
    return <p className="mt-1 text-xs text-red-500">{message}</p>;
}

function inputClass(hasError: boolean) {
    return `w-full rounded-xl border px-4 py-2.5 text-sm text-gray-700 placeholder-gray-300 outline-none transition ${
        hasError
            ? 'border-red-400 focus:border-red-400 focus:ring-2 focus:ring-red-200'
            : 'border-gray-200 focus:border-primary focus:ring-2 focus:ring-primary/20'
    }`;
}

function EmptyState() {
    return (
        <div className="flex flex-col items-center justify-center rounded-xl bg-gray-50 py-10">
            <svg
                className="mb-3 h-12 w-12 text-gray-300"
                viewBox="0 0 48 48"
                fill="none"
            >
                <rect
                    x="10"
                    y="4"
                    width="28"
                    height="38"
                    rx="4"
                    stroke="currentColor"
                    strokeWidth="2.5"
                />
                <path
                    d="M10 42l4-4 4 4 4-4 4 4 4-4 4 4"
                    stroke="currentColor"
                    strokeWidth="2.5"
                    strokeLinecap="round"
                    strokeLinejoin="round"
                />
                <line
                    x1="16"
                    y1="16"
                    x2="32"
                    y2="16"
                    stroke="currentColor"
                    strokeWidth="2.5"
                    strokeLinecap="round"
                />
                <line
                    x1="16"
                    y1="22"
                    x2="32"
                    y2="22"
                    stroke="currentColor"
                    strokeWidth="2.5"
                    strokeLinecap="round"
                />
                <line
                    x1="16"
                    y1="28"
                    x2="24"
                    y2="28"
                    stroke="currentColor"
                    strokeWidth="2.5"
                    strokeLinecap="round"
                />
            </svg>
            <p className="text-sm font-semibold text-gray-500">
                Belum ada fixed cost
            </p>
            <p className="mt-1 text-xs text-gray-400">
                Anda bisa lewati langkah ini atau tambah satu biaya rutin
                terlebih dulu.
            </p>
        </div>
    );
}

function CategoryDropdown({
    value,
    onChange,
    categories,
    hasError,
}: {
    value: number | string;
    onChange: (val: number) => void;
    categories: any[];
    hasError: boolean;
}) {
    const [isOpen, setIsOpen] = useState(false);
    const selectedCat = categories.find((cat) => cat.id === value);
    const SelectedIcon =
        selectedCat?.icon && phosphorMap[selectedCat.icon]
            ? phosphorMap[selectedCat.icon]
            : QuestionIcon;

    return (
        <div className="relative w-full">
            <button
                type="button"
                onClick={() => setIsOpen(!isOpen)}
                className={`flex w-full items-center justify-between rounded-xl border bg-white px-4 py-2.5 text-sm transition outline-none ${
                    hasError
                        ? 'border-red-400'
                        : 'border-gray-200 focus:border-primary focus:ring-2 focus:ring-primary/20'
                }`}
            >
                <div className="flex items-center gap-2">
                    {selectedCat ? (
                        <>
                            {selectedCat.icon && (
                                <SelectedIcon
                                    size={18}
                                    weight="bold"
                                    className="text-primary"
                                />
                            )}
                            <span className="text-gray-700">
                                {selectedCat.name}
                            </span>
                        </>
                    ) : (
                        <span className="text-gray-400">Pilih Kategori</span>
                    )}
                </div>
                <span className="text-xs text-gray-400">▼</span>
            </button>

            {isOpen && (
                <div className="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-xl border border-gray-200 bg-white p-1 shadow-lg">
                    {categories.map((cat: any) => {
                        const ItemIcon =
                            cat.icon && phosphorMap[cat.icon]
                                ? phosphorMap[cat.icon]
                                : QuestionIcon;
                        return (
                            <button
                                key={cat.id}
                                type="button"
                                onClick={() => {
                                    onChange(cat.id);
                                    setIsOpen(false);
                                }}
                                className={`flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm transition ${
                                    value === cat.id
                                        ? 'bg-primary/10 font-semibold text-primary'
                                        : 'text-gray-700 hover:bg-gray-100'
                                }`}
                            >
                                {cat.icon && (
                                    <ItemIcon
                                        size={18}
                                        weight={
                                            value === cat.id
                                                ? 'fill'
                                                : 'regular'
                                        }
                                    />
                                )}
                                {cat.name}
                            </button>
                        );
                    })}
                </div>
            )}
        </div>
    );
}

export default function OnboardingStep3({
    form,
    prev,
    submit,
    categories,
}: any) {
    // Helper: get error for a specific fixedCost field
    // Laravel sends errors like "fixedCosts.0.name"
    const fixedCostError = (
        index: number,
        field: string,
    ): string | undefined => {
        return (form.errors as Record<string, string>)[
            `fixedCosts.${index}.${field}`
        ];
    };

    const addCost = () => {
        form.setData('fixedCosts', [
            ...form.data.fixedCosts,
            {
                name: '',
                amount: '',
                cycleType: form.data.budgetCycle || 'monthly',
                dueDay: '',
                categoryId: '',
                isActive: true,
            },
        ]);
    };

    const updateCost = (index: number, field: string, value: any) => {
        const updated = [...form.data.fixedCosts];
        updated[index][field] = value;
        form.setData('fixedCosts', updated);
        // Clear the specific field error when user edits
        form.clearErrors(`fixedCosts.${index}.${field}` as any);
    };

    const removeCost = (index: number) => {
        form.setData(
            'fixedCosts',
            form.data.fixedCosts.filter((_: any, i: number) => i !== index),
        );
    };

    const cycleLabel = CYCLE_LABEL[form.data.budgetCycle] ?? 'Bulanan';

    return (
        <>
            <ProgressBar step={3} />

            <h1 className="mb-1 text-3xl font-bold text-primary">
                Biaya Rutin
            </h1>
            <p className="mb-4 text-sm text-gray-500">
                Tambahkan fixed cost agar proyeksi budget lebih akurat.
            </p>

            {/* Active cycle badge */}
            <div className="mb-4 flex items-center gap-2 text-sm text-gray-500">
                <span>Siklus aktif:</span>
                <span className="font-bold text-primary">{cycleLabel}</span>
                <svg
                    className="h-4 w-4 text-gray-400"
                    viewBox="0 0 20 20"
                    fill="currentColor"
                >
                    <path
                        fillRule="evenodd"
                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                        clipRule="evenodd"
                    />
                </svg>
            </div>

            {/* Add button */}
            <button
                type="button"
                onClick={addCost}
                className="mb-4 flex w-full items-center justify-center gap-2 rounded-xl border-2 border-dashed border-primary-medium py-4 text-sm font-semibold text-primary transition hover:bg-primary-light"
            >
                <span className="text-lg leading-none">+</span>
                Tambah Fixed Cost
            </button>

            {/* Top-level fixedCosts array error (e.g. wrong overall format) */}
            {(form.errors as any)['fixedCosts'] && (
                <p className="mb-3 text-xs text-red-500">
                    {(form.errors as any)['fixedCosts']}
                </p>
            )}

            {form.data.fixedCosts.length === 0 && <EmptyState />}

            {form.data.fixedCosts.map((cost: any, i: number) => {
                const maxDay = cost.cycleType === 'weekly' ? 7 : 31;

                return (
                    <div
                        key={i}
                        className="mb-4 space-y-3 rounded-xl border border-gray-200 bg-white p-4"
                    >
                        {/* Header */}
                        <div className="flex items-center justify-between">
                            <span className="text-sm font-semibold text-gray-700">
                                Fixed Cost #{i + 1}
                            </span>
                            <button
                                type="button"
                                onClick={() => removeCost(i)}
                                className="text-xs font-medium text-red-400 hover:text-red-600"
                            >
                                Hapus
                            </button>
                        </div>

                        {/* Name */}
                        <div>
                            <input
                                placeholder="Nama biaya rutin"
                                className={inputClass(
                                    !!fixedCostError(i, 'name'),
                                )}
                                value={cost.name}
                                onChange={(e) =>
                                    updateCost(i, 'name', e.target.value)
                                }
                            />
                            <FieldError message={fixedCostError(i, 'name')} />
                        </div>

                        {/* Amount */}
                        <div>
                            <input
                                placeholder="Rp. 0"
                                type="number"
                                className={inputClass(
                                    !!fixedCostError(i, 'amount'),
                                )}
                                value={cost.amount}
                                onChange={(e) =>
                                    updateCost(i, 'amount', e.target.value)
                                }
                            />
                            <FieldError message={fixedCostError(i, 'amount')} />
                        </div>

                        {/* Cycle Type */}
                        <div>
                            <select
                                className={inputClass(
                                    !!fixedCostError(i, 'cycleType'),
                                )}
                                value={cost.cycleType}
                                onChange={(e) =>
                                    updateCost(i, 'cycleType', e.target.value)
                                }
                            >
                                {CYCLE_OPTIONS.map((opt) => (
                                    <option key={opt.value} value={opt.value}>
                                        {opt.label}
                                    </option>
                                ))}
                            </select>
                            <FieldError
                                message={fixedCostError(i, 'cycleType')}
                            />
                        </div>

                        {/* Due Day */}
                        <div>
                            <input
                                type="number"
                                placeholder={`Hari jatuh tempo (1–${maxDay})`}
                                className={inputClass(
                                    !!fixedCostError(i, 'dueDay'),
                                )}
                                value={cost.dueDay}
                                onChange={(e) =>
                                    updateCost(i, 'dueDay', e.target.value)
                                }
                                min={1}
                                max={maxDay}
                            />
                            <FieldError message={fixedCostError(i, 'dueDay')} />
                        </div>

                        {/* Category */}
                        <div>
                            <CategoryDropdown
                                categories={categories}
                                value={cost.categoryId}
                                onChange={(val) =>
                                    updateCost(i, 'categoryId', val)
                                }
                                hasError={!!fixedCostError(i, 'categoryId')}
                            />
                            <FieldError
                                message={fixedCostError(i, 'categoryId')}
                            />
                        </div>

                        {/* Active toggle */}
                        <label className="flex items-center gap-2.5 text-sm text-gray-600">
                            <input
                                type="checkbox"
                                checked={cost.isActive}
                                className="h-4 w-4 rounded accent-primary"
                                onChange={(e) =>
                                    updateCost(i, 'isActive', e.target.checked)
                                }
                            />
                            Aktif
                        </label>
                    </div>
                );
            })}

            {/* Actions */}
            <div className="mt-4 flex justify-between gap-3">
                <button
                    type="button"
                    onClick={prev}
                    className="flex-1 rounded-xl bg-primary-light py-3 text-sm font-semibold text-primary transition hover:bg-primary/20"
                >
                    Sebelumnya
                </button>
                <button
                    type="button"
                    onClick={submit}
                    className="flex-1 rounded-xl bg-primary py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-primary/90"
                >
                    Selanjutnya
                </button>
            </div>
        </>
    );
}
