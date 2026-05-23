import { Head } from '@inertiajs/react';
import { Link } from '@inertiajs/react';
import { ArrowLeft, Plus, Pencil, CalendarDays, RefreshCw } from 'lucide-react';
import { useState } from 'react';
import type { Category } from '@/components/CategoryPickerModal';
import TemplateFormDrawer from '@/components/TemplateFormDrawer';
import type {Template} from '@/components/TemplateFormDrawer';
import AppLayout from '@/layouts/AppLayout';
import { CategoryPhosphorIcon } from '@/utils/phosphorIconMap';

interface Props {
    templates: Template[];
    categories: Category[];
    status?: 'stabil' | 'defisit' | 'kritis' | 'surplus';
}

function formatRp(value: string | number) {
    const num = Number(value);
    if (isNaN(num)) return 'Rp0';
    return (
        'Rp ' +
        num.toLocaleString('id-ID', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
        })
    );
}

function formatDueDay(template: Template): string {
    const days = [
        'Senin',
        'Selasa',
        'Rabu',
        'Kamis',
        'Jumat',
        'Sabtu',
        'Minggu'
    ];

    return template.cycleType === 'monthly'
        ? `Setiap tanggal ${template.dueDay} `
        : `Setiap hari ${days[template.dueDay - 1]} `;
}

const CYCLE_LABEL: Record<string, string> = {
    monthly: 'BULANAN',
    weekly: 'MINGGUAN',
};

function TemplateCard({
    template,
    onEdit,
}: {
    template: Template;
    onEdit: (t: Template) => void;
}) {
    return (
        <div className="relative flex flex-col rounded-2xl border border-gray-100 bg-white p-5 shadow-sm transition hover:shadow-md">
            {/* Edit button */}
            <button
                onClick={() => onEdit(template)}
                className="absolute top-4 right-4 flex h-8 w-8 items-center justify-center rounded-full text-gray-400 transition hover:bg-primary-light hover:text-primary"
            >
                <Pencil size={15} />
            </button>

            {/* Category badge */}
            {template.categoryName && (
                <span className="mb-3 self-start rounded-full bg-red-100 px-2.5 py-0.5 text-[10px] font-bold tracking-wide text-red-500 uppercase">
                    {template.categoryName}
                </span>
            )}

            {/* Icon + name */}
            <div className="mb-2 flex items-center gap-3">
                <div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-primary-light">
                    <CategoryPhosphorIcon
                        iconName={template.categoryIcon}
                        size={22}
                        className="text-primary"
                    />
                </div>
                <div>
                    <p className="text-sm font-semibold text-gray-800">
                        {template.name}
                    </p>
                    <p className="text-base font-bold text-primary">
                        {formatRp(template.amount)}
                    </p>
                </div>
            </div>

            {/* Divider */}
            <div className="my-3 border-t border-gray-100" />

            {/* Meta row */}
            <div className="flex items-center justify-between text-xs text-gray-500">
                <div className="flex items-center gap-1.5">
                    <CalendarDays size={13} className="text-gray-400" />
                    <div>
                        <p className="text-[10px] font-semibold tracking-wide text-gray-400 uppercase">
                            Jatuh Tempo
                        </p>
                        <p className="font-medium text-gray-700">
                            {formatDueDay(template)}
                        </p>
                    </div>
                </div>
                <div className="flex items-center gap-1.5">
                    <RefreshCw size={13} className="text-gray-400" />
                    <div className="text-right">
                        <p className="text-[10px] font-semibold tracking-wide text-gray-400 uppercase">
                            Siklus
                        </p>
                        <p className="font-bold text-gray-700">
                            {CYCLE_LABEL[template.cycleType] ??
                                template.cycleType}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    );
}

function AddCard({ onClick }: { onClick: () => void }) {
    return (
        <button
            onClick={onClick}
            className="flex min-h-45 flex-col items-center justify-center gap-2 rounded-2xl border-2 border-dashed border-gray-200 bg-white p-5 text-gray-400 transition hover:border-primary hover:text-primary"
        >
            <div className="flex h-12 w-12 items-center justify-center rounded-full border-2 border-gray-200 transition group-hover:border-primary">
                <Plus size={24} />
            </div>
            <span className="text-sm font-medium">Tambah Biaya Tetap</span>
        </button>
    );
}

export default function FixedCostTemplateManagement({
    templates,
    categories,
    status,
}: Props) {
    const [drawerOpen, setDrawerOpen] = useState(false);
    const [editingTemplate, setEditingTemplate] = useState<Template | null>(
        null,
    );

    const openCreate = () => {
        setEditingTemplate(null);
        setDrawerOpen(true);
    };

    const openEdit = (template: Template) => {
        setEditingTemplate(template);
        setDrawerOpen(true);
    };

    const closeDrawer = () => {
        setDrawerOpen(false);
        setEditingTemplate(null);
    };

    return (
        <AppLayout status={status}>
            <Head title="Kelola Biaya Tetap" />

            <div className="px-4 py-6 lg:px-8 lg:py-8">
                {/* Page header */}
                <div className="mb-6 flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <Link
                            href="/fixed-costs/occurrences"
                            className="flex h-9 w-9 items-center justify-center rounded-full text-gray-500 transition hover:bg-gray-100"
                        >
                            <ArrowLeft size={20} />
                        </Link>
                        <div>
                            <h1 className="text-xl font-bold text-gray-800 lg:text-2xl">
                                Kelola Biaya Tetap
                            </h1>
                            <p className="text-sm text-gray-500">
                                Atur dan kelola skema biaya tetap Anda
                            </p>
                        </div>
                    </div>

                    <button
                        onClick={openCreate}
                        className="flex items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary/90"
                    >
                        <Plus size={15} strokeWidth={2.5} />
                        <span className="hidden sm:inline">
                            Tambah Biaya Tetap
                        </span>
                        <span className="sm:hidden">Tambah</span>
                    </button>
                </div>

                {/* Grid */}
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    {templates.map((template) => (
                        <TemplateCard
                            key={template.id}
                            template={template}
                            onEdit={openEdit}
                        />
                    ))}

                    {/* Always-visible add card */}
                    <AddCard onClick={openCreate} />
                </div>

                {/* Empty state when no templates */}
                {templates.length === 0 && (
                    <div className="mt-4 flex flex-col items-center justify-center py-16 text-center">
                        <div className="mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-primary-light">
                            <RefreshCw size={28} className="text-primary" />
                        </div>
                        <p className="text-base font-semibold text-gray-700">
                            Belum ada biaya tetap
                        </p>
                        <p className="mt-1 text-sm text-gray-400">
                            Tambah biaya rutin seperti cicilan, listrik, atau
                            langganan.
                        </p>
                    </div>
                )}
            </div>

            {/* Form drawer */}
            <TemplateFormDrawer
                open={drawerOpen}
                template={editingTemplate}
                categories={categories}
                onClose={closeDrawer}
            />
        </AppLayout>
    );
}
