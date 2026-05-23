import { useForm } from '@inertiajs/react';
import { ArrowLeft, ChevronDown, Calendar } from 'lucide-react';
import { useState, useEffect, useRef } from 'react';
import CategoryPickerModal from '@/components/CategoryPickerModal';
import type { Category } from '@/components/CategoryPickerModal';
import DeleteConfirmDialog from '@/components/DeleteConfirmDialog';
import DueDateCalendarModal from '@/components/DueDateCalendarModal';
import { CategoryPhosphorIcon } from '@/utils/phosphorIconMap';

export interface Template {
    id: number;
    name: string;
    amount: string;
    cycleType: 'monthly' | 'weekly';
    dueDay: number;
    categoryId: number;
    categoryName: string;
    categoryIcon: string | null;
    isActive: boolean;
    dueDateDisplay: string;
}

interface TemplateFormDrawerProps {
    open: boolean;
    template: Template | null; // null = create mode
    categories: Category[];
    onClose: () => void;
    onDeleted?: () => void;
}

type SubView = 'form' | 'category' | 'date' | 'cycle';

const CYCLE_OPTIONS = [
    { value: 'monthly', label: 'Bulanan' },
    { value: 'weekly', label: 'Mingguan' },
];

function FieldError({ message }: { message?: string }) {
    if (!message) return null;
    return <p className="mt-1 text-xs text-red-500">{message}</p>;
}

function fieldClass(hasError: boolean) {
    return `w-full rounded-xl border px-4 py-3.5 text-sm text-gray-800 outline-none transition ${
        hasError
            ? 'border-red-400 focus:border-red-500 focus:ring-2 focus:ring-red-200'
            : 'border-gray-200 focus:border-primary focus:ring-2 focus:ring-primary/20'
    }`;
}

function labelClass(hasError: boolean) {
    return `mb-1.5 block text-[11px] font-bold uppercase tracking-widest ${
        hasError ? 'text-red-500' : 'text-primary'
    }`;
}

export default function TemplateFormDrawer({
    open,
    template,
    categories,
    onClose,
    onDeleted,
}: TemplateFormDrawerProps) {
    const isEdit = template !== null;
    const [subView, setSubView] = useState<SubView>('form');
    const [showDeleteConfirm, setShowDeleteConfirm] = useState(false);
    const [showCycleDropdown, setShowCycleDropdown] = useState(false);
    const [tempCategoryId, setTempCategoryId] = useState<number | null>(null);
    const cycleRef = useRef<HTMLDivElement>(null);

    // Form state
    const form = useForm({
        name: template?.name ?? '',
        amount: template?.amount ?? '',
        cycleType: template?.cycleType ?? 'monthly',
        categoryId: template?.categoryId ?? (null as number | null),
        dueDay: template?.dueDay ?? (null as number | null),
    });

    const lastSyncedKey = useRef<string | null>(null);
    useEffect(() => {
        const syncKey = `${open ? '1' : '0'}-${template?.id ?? 'new'}`;
        if (lastSyncedKey.current === syncKey) return;
        lastSyncedKey.current = syncKey;

        if (!open) return;

        setSubView('form');
        setShowDeleteConfirm(false);
        setShowCycleDropdown(false);

        form.setData({
            name: template?.name ?? '',
            amount: template?.amount ?? '',
            cycleType: template?.cycleType ?? 'monthly',
            categoryId: template?.categoryId ?? null,
            dueDay: template?.dueDay ?? null,
        });
        form.clearErrors();
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [open, template?.id]);

    useEffect(() => {
        const handler = (e: MouseEvent) => {
            if (
                cycleRef.current &&
                !cycleRef.current.contains(e.target as Node)
            ) {
                setShowCycleDropdown(false);
            }
        };
        document.addEventListener('mousedown', handler);
        return () => document.removeEventListener('mousedown', handler);
    }, []);

    if (!open) return null;

    // Derived State
    const selectedCategory = categories.find(
        (c) => c.id === form.data.categoryId,
    );
    const selectedCycleLabel =
        CYCLE_OPTIONS.find((o) => o.value === form.data.cycleType)?.label ?? '';

    const displayDate = form.data.dueDay
        ? form.data.cycleType === 'monthly'
            ? `Setiap tanggal ${form.data.dueDay}`
            : `Hari ke-${form.data.dueDay} setiap minggunya`
        : '';

    // Submit
    const handleSave = () => {
        if (isEdit) {
            form.patch(`/fixed-costs/templates/${template!.id}`, {
                preserveScroll: true,
                onSuccess: onClose,
            });
        } else {
            form.post('/fixed-costs/templates', {
                preserveScroll: true,
                onSuccess: onClose,
            });
        }
    };

    const handleDelete = () => {
        form.delete(`/fixed-costs/templates/${template!.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                setShowDeleteConfirm(false);
                onClose();
                onDeleted?.();
            },
        });
    };

    // Category picker
    const openCategoryPicker = () => {
        setTempCategoryId(form.data.categoryId);
        setSubView('category');
    };

    const confirmCategory = () => {
        if (tempCategoryId !== null) {
            form.setData('categoryId', tempCategoryId);
            form.clearErrors('categoryId' as any);
        }
        setSubView('form');
    };

    // Calendar Picker
    const confirmDate = (day: number) => {
        form.setData('dueDay', day);
        form.clearErrors('dueDay' as any);
        setSubView('form');
    };

    return (
        <>
            {/* Backdrop */}
            <div
                className="fixed inset-0 z-40 bg-black/30 backdrop-blur-sm"
                onClick={subView === 'form' ? onClose : undefined}
            />

            {/* Modal panel */}
            <div className="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6">
                <div
                    className="relative w-full max-w-2xl overflow-hidden rounded-2xl bg-white shadow-2xl"
                    style={{ minHeight: 520 }}
                >
                    {/* MAIN FORM VIEW */}
                    <div
                        className={`flex flex-col transition-opacity duration-200 ${
                            subView === 'form'
                                ? 'opacity-100'
                                : 'pointer-events-none absolute inset-0 opacity-0'
                        }`}
                    >
                        {/* Header */}
                        <div className="flex items-center gap-3 border-b border-gray-100 px-6 py-5">
                            <button
                                onClick={onClose}
                                className="flex h-8 w-8 items-center justify-center rounded-full hover:bg-gray-100"
                            >
                                <ArrowLeft size={18} className="text-primary" />
                            </button>
                            <h2 className="text-base font-bold text-primary">
                                {isEdit
                                    ? 'Edit Biaya Tetap'
                                    : 'Tambah Biaya Tetap'}
                            </h2>
                        </div>

                        {/* Form body */}
                        <div className="flex-1 space-y-5 overflow-y-auto px-6 py-5">
                            {/* Name */}
                            <div>
                                <label
                                    className={labelClass(!!form.errors.name)}
                                >
                                    Nama Biaya
                                </label>
                                <input
                                    type="text"
                                    placeholder="Nama biaya tidak boleh kosong"
                                    value={form.data.name}
                                    onChange={(e) =>
                                        form.setData('name', e.target.value)
                                    }
                                    className={fieldClass(!!form.errors.name)}
                                />
                                <FieldError message={form.errors.name} />
                            </div>

                            {/* Amount */}
                            <div>
                                <label
                                    className={labelClass(!!form.errors.amount)}
                                >
                                    Nominal
                                </label>
                                <input
                                    type="number"
                                    placeholder="Isi nominal biaya"
                                    value={form.data.amount}
                                    onChange={(e) =>
                                        form.setData('amount', e.target.value)
                                    }
                                    className={fieldClass(!!form.errors.amount)}
                                />
                                <FieldError message={form.errors.amount} />
                            </div>

                            {/* Cycle */}
                            <div>
                                <label
                                    className={labelClass(
                                        !!form.errors.cycleType,
                                    )}
                                >
                                    Siklus
                                </label>
                                <div className="relative" ref={cycleRef}>
                                    <button
                                        type="button"
                                        onClick={() =>
                                            setShowCycleDropdown((v) => !v)
                                        }
                                        className={`flex w-full items-center justify-between rounded-xl border px-4 py-3.5 text-left text-sm transition ${
                                            form.errors.cycleType
                                                ? 'border-red-400'
                                                : 'border-gray-200 focus:border-primary'
                                        } ${form.data.cycleType ? 'text-gray-800' : 'text-gray-400'}`}
                                    >
                                        <span>
                                            {selectedCycleLabel ||
                                                'Pilih siklus'}
                                        </span>
                                        <ChevronDown
                                            size={16}
                                            className="text-gray-400"
                                        />
                                    </button>

                                    {showCycleDropdown && (
                                        <div className="absolute top-full right-0 z-10 mt-1 min-w-40 rounded-xl border border-gray-100 bg-white py-2 shadow-lg">
                                            <p className="px-4 pt-1 pb-1.5 text-xs font-semibold text-gray-400">
                                                Siklus
                                            </p>
                                            {CYCLE_OPTIONS.map((opt) => {
                                                const isActive =
                                                    form.data.cycleType ===
                                                    opt.value;
                                                return (
                                                    <button
                                                        key={opt.value}
                                                        type="button"
                                                        onClick={() => {
                                                            // Jika user ganti cycle, reset dueDay biar gak rancu
                                                            if (
                                                                form.data
                                                                    .cycleType !==
                                                                opt.value
                                                            ) {
                                                                form.setData(
                                                                    'dueDay',
                                                                    null,
                                                                );
                                                            }
                                                            form.setData(
                                                                'cycleType',
                                                                opt.value as
                                                                    | 'monthly'
                                                                    | 'weekly',
                                                            );
                                                            form.clearErrors(
                                                                'cycleType' as any,
                                                            );
                                                            setShowCycleDropdown(
                                                                false,
                                                            );
                                                        }}
                                                        className={`flex w-full items-center justify-between px-4 py-2.5 text-sm transition hover:bg-primary-light ${
                                                            isActive
                                                                ? 'font-semibold text-primary'
                                                                : 'text-gray-700'
                                                        }`}
                                                    >
                                                        {opt.label}
                                                        {isActive && (
                                                            <span className="flex h-5 w-5 items-center justify-center rounded-full bg-primary text-[10px] text-white">
                                                                ✓
                                                            </span>
                                                        )}
                                                    </button>
                                                );
                                            })}
                                        </div>
                                    )}
                                </div>
                                <FieldError message={form.errors.cycleType} />
                            </div>

                            {/* Category */}
                            <div>
                                <label
                                    className={labelClass(
                                        !!(form.errors as any).categoryId,
                                    )}
                                >
                                    Kategori
                                </label>
                                <button
                                    type="button"
                                    onClick={openCategoryPicker}
                                    className={`flex w-full items-center justify-between rounded-xl border px-4 py-3.5 text-sm transition ${
                                        (form.errors as any).categoryId
                                            ? 'border-red-400'
                                            : 'border-gray-200 hover:border-primary'
                                    } ${selectedCategory ? 'text-gray-800' : 'text-gray-400'}`}
                                >
                                    <span className="flex items-center gap-2">
                                        {selectedCategory && (
                                            <CategoryPhosphorIcon
                                                iconName={selectedCategory.icon}
                                                size={16}
                                                className="text-primary"
                                            />
                                        )}
                                        {selectedCategory?.name ??
                                            'Pilih kategori'}
                                    </span>
                                    <ChevronDown
                                        size={16}
                                        className="text-gray-400"
                                    />
                                </button>
                                <FieldError
                                    message={(form.errors as any).categoryId}
                                />
                            </div>

                            {/* Due date */}
                            <div>
                                <label
                                    className={labelClass(
                                        !!(form.errors as any).dueDay,
                                    )}
                                >
                                    Jatuh Tempo
                                </label>
                                <button
                                    type="button"
                                    onClick={() => setSubView('date')}
                                    className={`flex w-full items-center justify-between rounded-xl border px-4 py-3.5 text-sm transition ${
                                        (form.errors as any).dueDay
                                            ? 'border-red-400'
                                            : 'border-gray-200 hover:border-primary'
                                    } ${displayDate ? 'text-gray-800' : 'text-gray-400'}`}
                                >
                                    <span>{displayDate || 'Pilih hari'}</span>
                                    <Calendar
                                        size={16}
                                        className="text-gray-400"
                                    />
                                </button>
                                <FieldError
                                    message={(form.errors as any).dueDay}
                                />
                            </div>
                        </div>

                        {/* Footer actions */}
                        <div className="flex gap-3 border-t border-gray-100 px-6 py-5">
                            {isEdit && (
                                <button
                                    type="button"
                                    onClick={() => setShowDeleteConfirm(true)}
                                    className="flex-1 rounded-xl border border-red-300 py-3 text-sm font-bold text-red-500 transition hover:bg-red-50"
                                >
                                    Hapus
                                </button>
                            )}
                            <button
                                type="button"
                                onClick={() => {
                                    if (!form.data.dueDay) {
                                        form.setError(
                                            'dueDay',
                                            'Tanggal wajib dipilih',
                                        );
                                        return;
                                    }

                                    handleSave();
                                }}
                                disabled={form.processing}
                                className="flex-1 rounded-xl bg-secondary py-3 text-sm font-bold text-white shadow-sm transition hover:bg-secondary/90 disabled:opacity-60"
                            >
                                {form.processing
                                    ? 'Menyimpan...'
                                    : isEdit
                                      ? 'Simpan Perubahan'
                                      : 'Tambah'}
                            </button>
                        </div>
                    </div>

                    {/* CATEGORY PICKER SUB-VIEW */}
                    {subView === 'category' && (
                        <CategoryPickerModal
                            open
                            categories={categories}
                            selectedId={tempCategoryId}
                            onSelect={(cat) => setTempCategoryId(cat.id)}
                            onConfirm={confirmCategory}
                            onBack={() => setSubView('form')}
                        />
                    )}

                    {/* CALENDAR SUB-VIEW */}
                    {subView === 'date' && (
                        <DueDateCalendarModal
                            open
                            value={form.data.dueDay}
                            onConfirm={confirmDate}
                            onCancel={() => setSubView('form')}
                            cycleType={form.data.cycleType}
                        />
                    )}
                </div>
            </div>

            {/* Delete confirm dialog */}
            <DeleteConfirmDialog
                open={showDeleteConfirm}
                onConfirm={handleDelete}
                onCancel={() => setShowDeleteConfirm(false)}
                isLoading={form.processing}
            />
        </>
    );
}
