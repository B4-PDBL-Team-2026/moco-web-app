import { useForm } from '@inertiajs/react';
import { ArrowLeft, Check, Sparkles, AlertCircle, Trash2, Search, AlertTriangle, X } from 'lucide-react';
import { useState, useEffect, useRef } from 'react';
import DeleteConfirmDialog from '@/components/DeleteConfirmDialog';
import { CategoryPhosphorIcon } from '@/utils/phosphorIconMap';
import { getIconColorTheme } from '@/utils/categoryColors';

export interface Category {
    id: number;
    name: string;
    icon: string | null;
    type: 'expense' | 'income';
    isSystem: boolean;
    transactionsCount?: number;
    fixedCostsCount?: number;
}

interface CategoryFormDrawerProps {
    open: boolean;
    category: Category | null; // null = create mode
    onClose: () => void;
}

const AVAILABLE_ICONS = [
    'bowl_food', 'coffee', 'taxi', 'car', 'airplane', 'train', 'bicycle',
    'invoice', 'credit_card', 'receipt', 'percent', 'lightning', 'drop', 'flame',
    'student', 'book', 'film_reel', 'music_note', 'game_controller',
    'shopping_bag', 'basket', 'heart_beat', 'hand_heart', 'heart',
    'tag', 'money', 'wallet', 'hand_coins', 'gift', 'star', 'house', 'phone',
    'scissors', 'baby', 'paw_print', 'plant', 'chart_line', 'chart_bar',
    'bank', 'calculator', 'plus'
];

const ICON_GROUPS = [
    { id: 'all', name: 'Semua' },
    { id: 'culinary', name: 'Kuliner', icons: ['bowl_food', 'coffee'] },
    { id: 'transport', name: 'Transportasi', icons: ['taxi', 'car', 'airplane', 'train', 'bicycle'] },
    { id: 'finance', name: 'Finansial & Tagihan', icons: ['invoice', 'credit_card', 'receipt', 'percent', 'lightning', 'drop', 'flame', 'money', 'wallet', 'hand_coins', 'bank', 'calculator'] },
    { id: 'edu_hobby', name: 'Hobi & Edukasi', icons: ['student', 'book', 'film_reel', 'music_note', 'game_controller', 'plant'] },
    { id: 'shopping', name: 'Belanja & Kebutuhan', icons: ['shopping_bag', 'basket', 'tag', 'gift', 'scissors'] },
    { id: 'health_home', name: 'Kesehatan & Rumah', icons: ['heart_beat', 'hand_heart', 'heart', 'house', 'phone'] },
    { id: 'others', name: 'Lainnya', icons: ['star', 'baby', 'paw_print', 'chart_line', 'chart_bar', 'plus'] }
];

function FieldError({ message }: { message?: string }) {
    if (!message) return null;
    return (
        <p className="mt-1.5 flex items-center gap-1 text-xs font-semibold text-rose-500 animate-shake">
            <AlertCircle size={12} />
            <span>{message}</span>
        </p>
    );
}

function fieldClass(hasError: boolean) {
    return `w-full rounded-2xl border px-4 py-3.5 text-sm font-semibold text-gray-850 outline-none transition-all duration-200 ${
        hasError
            ? 'border-rose-400 bg-rose-50/20 focus:border-rose-500 focus:bg-white focus:ring-4 focus:ring-rose-100'
            : 'border-gray-200 bg-gray-50/30 hover:bg-gray-50 focus:border-primary focus:bg-white focus:ring-4 focus:ring-primary-light'
    }`;
}

function labelClass(hasError: boolean) {
    return `mb-2 block text-[10px] font-black uppercase tracking-wider ${
        hasError ? 'text-rose-500' : 'text-primary'
    }`;
}

export default function CategoryFormDrawer({
    open,
    category,
    onClose,
}: CategoryFormDrawerProps) {
    const isEdit = category !== null;
    const [showDeleteConfirm, setShowDeleteConfirm] = useState(false);
    const [iconSearchQuery, setIconSearchQuery] = useState('');
    const [activeIconGroup, setActiveIconGroup] = useState('all');

    // Form state
    const form = useForm({
        name: category?.name ?? '',
        type: category?.type ?? 'expense',
        icon: category?.icon ?? 'tag',
    });

    const lastSyncedKey = useRef<string | null>(null);
    useEffect(() => {
        const syncKey = `${open ? '1' : '0'}-${category?.id ?? 'new'}`;
        if (lastSyncedKey.current === syncKey) return;
        lastSyncedKey.current = syncKey;

        if (!open) return;

        setShowDeleteConfirm(false);
        setIconSearchQuery('');
        setActiveIconGroup('all');

        form.setData({
            name: category?.name ?? '',
            type: category?.type ?? 'expense',
            icon: category?.icon ?? 'tag',
        });
        form.clearErrors();
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [open, category?.id]);

    if (!open) return null;

    // Submit
    const handleSave = () => {
        if (isEdit) {
            form.patch(`/categories/${category!.id}`, {
                preserveScroll: true,
                onSuccess: onClose,
            });
        } else {
            form.post('/categories', {
                preserveScroll: true,
                onSuccess: onClose,
            });
        }
    };

    const handleDelete = () => {
        form.delete(`/categories/${category!.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                setShowDeleteConfirm(false);
                onClose();
            },
        });
    };

    // Selected icon colors for dynamic styling
    const previewColors = getIconColorTheme(form.data.icon);

    // Database safety guard details
    const totalUsage = (category?.transactionsCount ?? 0) + (category?.fixedCostsCount ?? 0);
    const isCategoryInUse = isEdit && totalUsage > 0;

    // Filter icons based on search query and group tab
    const filteredIcons = AVAILABLE_ICONS.filter(iconName => {
        // Group filter
        if (activeIconGroup !== 'all') {
            const group = ICON_GROUPS.find(g => g.id === activeIconGroup);
            if (!group || !group.icons?.includes(iconName)) return false;
        }
        // Search filter
        if (iconSearchQuery.trim() !== '') {
            const query = iconSearchQuery.toLowerCase().trim();
            return iconName.replace('_', ' ').toLowerCase().includes(query);
        }
        return true;
    });

    return (
        <>
            {/* Backdrop with elegant blur */}
            <div
                className="fixed inset-0 z-40 bg-slate-900/40 backdrop-blur-md transition-opacity duration-300 animate-fade-in animate-duration-200"
                onClick={onClose}
            />

            {/* Modal panel container */}
            <div className="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 overflow-y-auto">
                <div
                    className="relative w-full max-w-xl overflow-hidden rounded-3xl bg-white shadow-2xl flex flex-col transition-all duration-300 transform scale-100 animate-scale-up"
                    style={{ maxHeight: '90vh', minHeight: 600 }}
                >
                    {/* Header */}
                    <div className="flex items-center gap-3 border-b border-gray-100 px-6 py-5 shrink-0 bg-slate-50/50">
                        <button
                            onClick={onClose}
                            className="flex h-9 w-9 items-center justify-center rounded-xl border border-gray-100 bg-white text-gray-500 shadow-sm transition hover:bg-gray-50 hover:text-primary active:scale-95 cursor-pointer"
                        >
                            <ArrowLeft size={16} strokeWidth={2.5} />
                        </button>
                        <div>
                            <h2 className="text-base font-black text-gray-900 leading-tight">
                                {isEdit ? 'Edit Kategori Kustom' : 'Buat Kategori Kustom'}
                            </h2>
                            <p className="text-xs font-semibold text-gray-400 mt-0.5">
                                Kustomisasikan nama, tipe, dan ikon yang pas dengan selera keuangan Anda.
                            </p>
                        </div>
                    </div>

                    {/* Live Preview Card */}
                    <div className="bg-slate-50/20 border-b border-gray-100 px-6 py-4 shrink-0">
                        <div className="mb-2 flex items-center justify-between">
                            <span className="text-[10px] font-black uppercase tracking-wider text-gray-400">Live Preview</span>
                            <span className="flex items-center gap-1 text-[10px] font-extrabold text-indigo-600">
                                <Sparkles size={10} className="fill-current" />
                                Tampilan Kartu
                            </span>
                        </div>

                        {/* Styled card */}
                        <div className="relative flex items-center gap-4 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm max-w-xs mx-auto transition-all duration-300">
                            {/* Accent dynamic bar */}
                            <div className={`absolute left-0 top-3 bottom-3 w-1 rounded-r-full bg-gradient-to-b ${previewColors.gradient}`} />

                            {/* Icon dynamic Container */}
                            <div className={`flex h-11 w-11 shrink-0 items-center justify-center rounded-xl ${previewColors.bgLight} shadow-sm transition-transform duration-200 scale-100`}>
                                <CategoryPhosphorIcon
                                    iconName={form.data.icon}
                                    size={20}
                                    className={previewColors.text}
                                    weight="fill"
                                />
                            </div>

                            {/* Info */}
                            <div className="flex-1 min-w-0">
                                <p className="truncate text-xs font-black text-gray-800">
                                    {form.data.name || 'Nama Kategori Baru'}
                                </p>
                                <span
                                    className={`inline-block mt-1 rounded-full px-2 py-0.5 text-[8px] font-extrabold uppercase tracking-widest ${
                                        form.data.type === 'expense'
                                            ? 'bg-rose-50 text-rose-600 border border-rose-100/50'
                                            : 'bg-emerald-50 text-emerald-600 border border-emerald-100/50'
                                    }`}
                                >
                                    {form.data.type === 'expense' ? 'Pengeluaran' : 'Pemasukan'}
                                </span>
                            </div>
                        </div>
                    </div>

                    {/* Form body */}
                    <div className="flex-1 space-y-6 overflow-y-auto px-6 py-5">
                        {/* Database Safety Guard Alert */}
                        {isEdit && (
                            <div className={`p-4 rounded-2xl border transition-all duration-300 ${
                                isCategoryInUse 
                                    ? 'bg-rose-50/80 border-rose-200 text-rose-800' 
                                    : 'bg-emerald-50/80 border-emerald-200 text-emerald-800'
                            }`}>
                                <div className="flex gap-3">
                                    <div className="shrink-0 mt-0.5">
                                        {isCategoryInUse ? (
                                            <AlertTriangle size={18} className="text-rose-500 animate-pulse" />
                                        ) : (
                                            <Check size={16} className="text-emerald-500 bg-emerald-100 rounded-full p-0.5" />
                                        )}
                                    </div>
                                    <div>
                                        <h4 className="text-xs font-black uppercase tracking-wider">
                                            {isCategoryInUse ? 'Proteksi: Kategori Aktif Digunakan' : 'Kategori Aman Dihapus'}
                                        </h4>
                                        <p className="text-xs font-semibold mt-1 leading-relaxed opacity-95">
                                            {isCategoryInUse ? (
                                                `Kategori ini sedang terhubung dengan ${category.transactionsCount ?? 0} transaksi dan ${category.fixedCostsCount ?? 0} template rutin. Fitur hapus dikunci demi melindungi integritas data finansial Anda.`
                                            ) : (
                                                'Kategori ini belum digunakan pada data mana pun. Anda dapat menghapusnya dengan aman.'
                                            )}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        )}

                        {/* Name */}
                        <div>
                            <label className={labelClass(!!form.errors.name)}>
                                Nama Kategori
                            </label>
                            <input
                                type="text"
                                maxLength={30}
                                placeholder="Contoh: Makan Siang, Kopi, Netflix..."
                                value={form.data.name}
                                onChange={(e) => form.setData('name', e.target.value)}
                                className={fieldClass(!!form.errors.name)}
                                autoFocus
                            />
                            <FieldError message={form.errors.name} />
                        </div>

                        {/* Type Toggle Cards */}
                        <div>
                            <label className={labelClass(!!form.errors.type)}>
                                Tipe Kategori
                            </label>
                            <div className="flex gap-4">
                                {/* Expense Option */}
                                <button
                                    type="button"
                                    onClick={() => form.setData('type', 'expense')}
                                    className={`flex-1 rounded-2xl py-3 px-4 border text-center transition-all duration-200 cursor-pointer ${
                                        form.data.type === 'expense'
                                            ? 'bg-rose-50 border-rose-300 text-rose-600 shadow-sm ring-4 ring-rose-100 font-bold'
                                            : 'border-gray-200 text-gray-500 bg-white hover:bg-gray-50 font-semibold'
                                    }`}
                                >
                                    <p className="text-xs">Pengeluaran</p>
                                </button>

                                {/* Income Option */}
                                <button
                                    type="button"
                                    onClick={() => form.setData('type', 'income')}
                                    className={`flex-1 rounded-2xl py-3 px-4 border text-center transition-all duration-200 cursor-pointer ${
                                        form.data.type === 'income'
                                            ? 'bg-emerald-50 border-emerald-300 text-emerald-600 shadow-sm ring-4 ring-emerald-100 font-bold'
                                            : 'border-gray-200 text-gray-500 bg-white hover:bg-gray-50 font-semibold'
                                    }`}
                                >
                                    <p className="text-xs">Pemasukan</p>
                                </button>
                            </div>
                            <FieldError message={form.errors.type} />
                        </div>

                        {/* Icon Picker Grid with Filter and Search */}
                        <div>
                            <div className="mb-3 flex flex-col gap-2.5">
                                <div className="flex items-center justify-between">
                                    <label className={labelClass(!!form.errors.icon)}>
                                        Pilih Ikon Kategori
                                    </label>
                                    <span className="text-[10px] font-bold text-gray-400">Total {AVAILABLE_ICONS.length} Ikon</span>
                                </div>

                                {/* Icon Search bar */}
                                <div className="relative">
                                    <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                        <Search size={14} />
                                    </div>
                                    <input
                                        type="text"
                                        placeholder="Cari nama ikon..."
                                        value={iconSearchQuery}
                                        onChange={(e) => setIconSearchQuery(e.target.value)}
                                        className="w-full pl-9 pr-8 py-2.5 bg-gray-50/50 hover:bg-gray-50 border border-gray-200 rounded-xl text-xs font-semibold text-gray-850 outline-none transition focus:border-primary focus:bg-white focus:ring-4 focus:ring-primary-light"
                                    />
                                    {iconSearchQuery && (
                                        <button
                                            type="button"
                                            onClick={() => setIconSearchQuery('')}
                                            className="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600"
                                        >
                                            <X size={14} />
                                        </button>
                                    )}
                                </div>

                                {/* Icon Group Filter Tabs */}
                                <div className="flex gap-1 overflow-x-auto pb-1.5 scrollbar-thin scrollbar-thumb-gray-200">
                                    {ICON_GROUPS.map((group) => (
                                        <button
                                            key={group.id}
                                            type="button"
                                            onClick={() => setActiveIconGroup(group.id)}
                                            className={`px-3 py-1.5 text-[9px] font-black rounded-lg transition-all shrink-0 cursor-pointer ${
                                                activeIconGroup === group.id
                                                    ? 'bg-primary text-white shadow-sm'
                                                    : 'bg-white border border-gray-100 text-gray-400 hover:bg-gray-50 hover:text-gray-650'
                                            }`}
                                        >
                                            {group.name}
                                        </button>
                                    ))}
                                </div>
                            </div>

                            {/* Rendered Icons Grid */}
                            <div className="grid grid-cols-6 gap-3 rounded-2xl border border-gray-100 bg-gray-50/50 p-4 max-h-[175px] overflow-y-auto sm:grid-cols-8">
                                {filteredIcons.length > 0 ? (
                                    filteredIcons.map((iconName) => {
                                        const isSelected = form.data.icon === iconName;
                                        const colors = getIconColorTheme(iconName);

                                        return (
                                            <button
                                                key={iconName}
                                                type="button"
                                                onClick={() => form.setData('icon', iconName)}
                                                className={`relative flex h-11 w-11 items-center justify-center rounded-xl transition-all duration-200 cursor-pointer shadow-sm hover:scale-110 ${
                                                    isSelected
                                                        ? `bg-gradient-to-br ${colors.gradient} text-white scale-105 ring-2 ring-offset-2 ring-primary`
                                                        : 'bg-white border border-gray-100 text-gray-500 hover:bg-primary-light hover:text-primary'
                                                }`}
                                                title={iconName.replace('_', ' ')}
                                            >
                                                <CategoryPhosphorIcon
                                                    iconName={iconName}
                                                    size={20}
                                                    className={isSelected ? 'text-white' : colors.text}
                                                    weight={isSelected ? 'fill' : 'regular'}
                                                />
                                                {isSelected && (
                                                    <span className="absolute -top-1 -right-1 flex h-4 w-4 items-center justify-center rounded-full bg-white border border-primary text-[8px] font-bold text-primary shadow-sm animate-scale-up">
                                                        <Check size={9} strokeWidth={3} />
                                                    </span>
                                                )}
                                            </button>
                                        );
                                    })
                                ) : (
                                    <div className="col-span-full py-8 text-center text-xs font-semibold text-gray-400">
                                        Tidak ada ikon yang cocok dengan pencarian Anda.
                                    </div>
                                )}
                            </div>
                            <FieldError message={form.errors.icon} />
                        </div>
                    </div>

                    {/* Footer Actions */}
                    <div className="flex gap-3 border-t border-gray-100 px-6 py-5 shrink-0 bg-gray-50/50">
                        {isEdit && (
                            <button
                                type="button"
                                disabled={isCategoryInUse}
                                onClick={() => setShowDeleteConfirm(true)}
                                className={`flex items-center justify-center gap-1.5 px-5 rounded-2xl border text-sm font-extrabold transition-all duration-200 active:scale-95 cursor-pointer ${
                                    isCategoryInUse
                                        ? 'border-gray-250 bg-gray-100 text-gray-400 cursor-not-allowed opacity-60'
                                        : 'border-rose-200 text-rose-500 bg-white hover:bg-rose-50 hover:border-rose-300'
                                }`}
                                title={isCategoryInUse ? 'Tidak dapat menghapus kategori aktif' : 'Hapus Kategori'}
                            >
                                <Trash2 size={16} />
                                <span className="hidden sm:inline">Hapus</span>
                            </button>
                        )}
                        <button
                            type="button"
                            onClick={handleSave}
                            disabled={form.processing}
                            className="flex-1 rounded-2xl bg-secondary py-3.5 text-sm font-extrabold text-white shadow-sm transition hover:bg-secondary/95 disabled:opacity-60 disabled:pointer-events-none active:scale-98 cursor-pointer text-center"
                        >
                            {form.processing
                                ? 'Menyimpan...'
                                : isEdit
                                  ? 'Simpan Perubahan'
                                  : 'Tambahkan Kategori'}
                        </button>
                    </div>
                </div>
            </div>

            {/* Delete confirm dialog */}
            <DeleteConfirmDialog
                open={showDeleteConfirm}
                onConfirm={handleDelete}
                onCancel={() => setShowDeleteConfirm(false)}
                isLoading={form.processing}
                title="Hapus Kategori Kustom?"
                description="Kategori ini akan dihapus secara permanen dari daftar Anda. Tindakan ini tidak dapat dibatalkan."
            />
        </>
    );
}
